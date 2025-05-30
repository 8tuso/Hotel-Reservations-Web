<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache; // Added: For caching
use Illuminate\Support\Facades\Session; // Added: For session management
use Carbon\Carbon;
use Illuminate\Support\Str;

class RecommendationController extends Controller
{
    private $amadeusBaseUrl;
    private $amadeusClientId;
    private $amadeusClientSecret;

    public function __construct()
    {
        $this->amadeusBaseUrl = config('services.amadeus.base_url');
        $this->amadeusClientId = config('services.amadeus.client_id');
        $this->amadeusClientSecret = config('services.amadeus.client_secret');
    }

    /**
     * Get Amadeus API access token.
     * @return string|null
     */
    private function getAmadeusAccessToken()
    {
        Log::info('Attempting to get Amadeus access token.');
        try {
            $response = Http::asForm()->post("{$this->amadeusBaseUrl}/v1/security/oauth2/token", [
                'client_id' => $this->amadeusClientId,
                'client_secret' => $this->amadeusClientSecret,
                'grant_type' => 'client_credentials',
            ]);

            if ($response->successful()) {
                Log::info('Amadeus Token obtained successfully.');
                return $response->json()['access_token'];
            } else {
                Log::error('Amadeus Token Error:', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Amadeus Token Exception:', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get AI-powered hotel recommendations based on user's geolocation, search history, or default.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecommendations(Request $request)
    {
        Log::info('Recommendation request received.');
        $accessToken = $this->getAmadeusAccessToken();

        if (!$accessToken) {
            return response()->json(['error' => 'Failed to obtain Amadeus access token for recommendations.'], 500);
        }

        // --- User Session ID Management ---
        $userSessionId = Session::get('user_unique_id');
        if (!$userSessionId) {
            $userSessionId = Str::uuid();
            Session::put('user_unique_id', $userSessionId);
            Log::info("New user session ID created for recommendations: {$userSessionId}");
        } else {
            Log::info("Existing user session ID for recommendations: {$userSessionId}");
        }

        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        $hotelIds = [];
        $recommendationCityIata = 'AMM'; // Default city for recommendations if no history or geolocation
        $recommendationAdults = 1;
        $recommendationRoomQuantity = 1;
        $recommendationCheckInDate = Carbon::now()->addMonth()->startOfMonth()->format('Y-m-d');
        $recommendationCheckOutDate = Carbon::now()->addMonth()->startOfMonth()->addDays(2)->format('Y-m-d');
        $recommendations = [];

        // --- Strategy 1: Attempt to get hotel IDs by geolocation (if provided) ---
        if ($latitude && $longitude) {
            Log::info('Attempting to find hotels by geolocation for recommendations.', ['latitude' => $latitude, 'longitude' => $longitude]);
            try {
                $geoHotelResponse = Http::withHeaders([
                    'Authorization' => "Bearer {$accessToken}",
                ])->get("{$this->amadeusBaseUrl}/v1/reference-data/locations/hotels/by-geocode", [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'radius' => 50, // Increased radius to 50km for better chance of finding hotels
                    'radiusUnit' => 'KM',
                    'hotelSource' => 'ALL'
                ]);

                if ($geoHotelResponse->successful() && !empty($geoHotelResponse->json('data'))) {
                    Log::info('Successfully retrieved hotels by geolocation.');
                    $hotelIds = collect($geoHotelResponse->json('data'))->pluck('hotelId')->unique()->take(10)->toArray();
                } else {
                    Log::warning('Amadeus Geo Hotel Search failed or returned no data for recommendations.', ['response' => $geoHotelResponse->body()]);
                }
            } catch (\Exception $e) {
                Log::error('Amadeus Geo Hotel Search Exception for recommendations:', ['message' => $e->getMessage()]);
            }
        } else {
            Log::info('Geolocation not provided for recommendations.');
        }

        // --- Strategy 2: Fallback to user's last search history (if no hotel IDs from geolocation) ---
        if (empty($hotelIds)) {
            Log::info('No hotel IDs from geolocation. Attempting to fetch last search history from Laravel Cache for recommendations.');
            $historyKey = 'user_search_history_' . $userSessionId;
            $searchHistory = Cache::get($historyKey, []);

            if (!empty($searchHistory)) {
                $lastSearch = $searchHistory[0]; // Get the most recent search
                $recommendationCityIata = $lastSearch['iataCode'] ?? $recommendationCityIata;
                $recommendationAdults = $lastSearch['adults'] ?? $recommendationAdults;
                $recommendationRoomQuantity = $lastSearch['roomQuantity'] ?? $recommendationRoomQuantity;

                $lastSearchCheckIn = Carbon::parse($lastSearch['checkInDate']);
                $lastSearchCheckOut = Carbon::parse($lastSearch['checkOutDate']);
                $duration = $lastSearchCheckIn->diffInDays($lastSearchCheckOut);

                $recommendationCheckInDate = Carbon::now()->addMonth()->startOfMonth()->format('Y-m-d');
                $recommendationCheckOutDate = Carbon::parse($recommendationCheckInDate)->addDays($duration)->format('Y-m-d');

                Log::info("Using last search history for recommendations for user session {$userSessionId}. City: {$recommendationCityIata}");

                // Now, get hotel IDs based on this city from history
                try {
                    $cityHotelResponse = Http::withHeaders([
                        'Authorization' => "Bearer {$accessToken}",
                    ])->get("{$this->amadeusBaseUrl}/v1/reference-data/locations/hotels/by-city", [
                        'cityCode' => $recommendationCityIata,
                    ]);

                    if ($cityHotelResponse->successful() && !empty($cityHotelResponse->json('data'))) {
                        $hotelIds = collect($cityHotelResponse->json('data'))->pluck('hotelId')->unique()->take(10)->toArray();
                        Log::info("Found " . count($hotelIds) . " hotel IDs from history city for recommendations.");
                    } else {
                        Log::warning('Amadeus City Hotel Search (from history) failed or returned no data.', ['response' => $cityHotelResponse->body()]);
                    }
                } catch (\Exception $e) {
                    Log::error('Amadeus City Hotel Search Exception (from history) for recommendations:', ['message' => $e->getMessage()]);
                }
            } else {
                Log::info("No search history found for user session {$userSessionId}.");
            }
        }

        // --- Strategy 3: Fallback to default city (if no hotel IDs from geolocation or history) ---
        if (empty($hotelIds)) {
            Log::info("No hotel IDs from geolocation or history. Using default city {$recommendationCityIata} for recommendations.");
            try {
                $cityHotelResponse = Http::withHeaders([
                    'Authorization' => "Bearer {$accessToken}",
                ])->get("{$this->amadeusBaseUrl}/v1/reference-data/locations/hotels/by-city", [
                    'cityCode' => $recommendationCityIata,
                ]);

                if ($cityHotelResponse->successful() && !empty($cityHotelResponse->json('data'))) {
                    $hotelIds = collect($cityHotelResponse->json('data'))->pluck('hotelId')->unique()->take(10)->toArray();
                    Log::info("Found " . count($hotelIds) . " hotel IDs from default city for recommendations.");
                } else {
                    Log::warning('Amadeus City Hotel Search (from default city) failed or returned no data.', ['response' => $cityHotelResponse->body()]);
                }
            } catch (\Exception $e) {
                Log::error('Amadeus City Hotel Search Exception (from default city) for recommendations:', ['message' => $e->getMessage()]);
            }
        }

        // --- If still no hotel IDs after all strategies, return empty recommendations ---
        if (empty($hotelIds)) {
            Log::warning('No hotel IDs found for recommendations after all strategies.');
            return response()->json([]);
        }

        // Ensure unique hotel IDs and limit to a reasonable number for the offers API
        $hotelIds = array_unique($hotelIds);
        $hotelIds = array_slice($hotelIds, 0, 10);

        // --- Fetch Hotel Offers using the retrieved Hotel IDs ---
        Log::info("Step 3: Searching for Hotel Offers for recommendations using " . count($hotelIds) . " hotel IDs.");
        try {
            $offerParams = [
                'hotelIds' => implode(',', $hotelIds),
                'adults' => $recommendationAdults,
                'checkInDate' => $recommendationCheckInDate,
                'checkOutDate' => $recommendationCheckOutDate,
                'roomQuantity' => $recommendationRoomQuantity,
                'view' => 'FULL_ALL_PRICES'
            ];

            $recommendationOffersResponse = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
            ])->get("{$this->amadeusBaseUrl}/v3/shopping/hotel-offers", $offerParams);

            if ($recommendationOffersResponse->successful()) {
                $rawOffers = $recommendationOffersResponse->json('data', []);
                Log::info("Received " . count($rawOffers) . " raw offers for recommendations.");
                $recommendations = collect($rawOffers)->map(function ($offer) use ($recommendationCheckInDate, $recommendationCheckOutDate, $recommendationAdults) {
                    $hotel = $offer['hotel'] ?? [];
                    $firstOfferDetails = !empty($offer['offers']) ? $offer['offers'][0] : [];

                    $roomType = $firstOfferDetails['room']['type'] ?? ($firstOfferDetails['room']['typeEstimated']['category'] ?? 'N/A');
                    $price = $firstOfferDetails['price']['total'] ?? 'N/A';
                    $currency = $firstOfferDetails['price']['currency'] ?? '$';
                    $amenities = collect($firstOfferDetails['amenities'] ?? [])->pluck('description')->implode(', ');
                    if (empty($amenities) && isset($firstOfferDetails['room']['description']['text'])) {
                         $amenities = \Illuminate\Support\Str::limit($firstOfferDetails['room']['description']['text'], 100);
                    } else {
                        $amenities = Str::limit($amenities, 100);
                    }

                    $imageUrl = 'https://placehold.co/400x250/E0F2F7/2C3E50?text=Rec+Room';

                    if (empty($hotel['name']) || $price === 'N/A' || !isset($firstOfferDetails['room'])) {
                        return null;
                    }

                    return [
                        'id' => $firstOfferDetails['id'] ?? uniqid('rec_offer_'),
                        'hotelId' => $hotel['hotelId'] ?? null,
                        'checkInDate' => $recommendationCheckInDate,
                        'checkOutDate' => $recommendationCheckOutDate,
                        'adults' => $recommendationAdults,
                        'hotel' => $hotel['name'],
                        'type' => $roomType,
                        'price' => $currency . ' ' . $price . '/night',
                        'amenities' => $amenities ?: 'No specific amenities listed',
                        'imageUrl' => $imageUrl,
                    ];
                })->filter()->values()->toArray();
            } else {
                Log::error('Amadeus Recommendation Offers Error: ' . $recommendationOffersResponse->body());
            }

            Log::info("Recommendations generated for user session {$userSessionId}:", $recommendations);
            return response()->json($recommendations);

        } catch (\Exception $e) {
            Log::error('Recommendation Fetch Exception: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while fetching recommendations.'], 500);
        }
    }

    /**
     * Fetches details for a specific hotel offer and returns a Blade view.
     * This method will also cache the specific offer data for "proceed to booking" scenarios.
     * It will first attempt to retrieve the offer from cache.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function getOfferDetails(Request $request)
    {
        Log::info('Step 0: Offer details request received (for Blade view).');
        $accessToken = $this->getAmadeusAccessToken();

        if (!$accessToken) {
            return response()->json(['error' => 'Failed to obtain Amadeus access token for offer details.'], 500);
        }

        $offerId = $request->query('offerId');
        $checkInDate = $request->query('checkInDate');
        $checkOutDate = $request->query('checkOutDate');
        $adults = $request->query('adults') ?? 1;

        if (!$offerId || !$checkInDate || !$checkOutDate) {
            Log::error('Missing required parameters for offer details view.', $request->query());
            return view('booking-details', ['error' => 'Missing essential booking details.']);
        }

        // --- User Session ID Management for "Proceed" Cache ---
        $userSessionId = Session::get('user_unique_id');
        if (!$userSessionId) {
            $userSessionId = Str::uuid();
            Session::put('user_unique_id', $userSessionId);
            Log::info("New user session ID created for offer details: {$userSessionId}");
        } else {
            Log::info("Existing user session ID for offer details: {$userSessionId}");
        }

        // Define the cache key for the "proceed" offer
        $proceedCacheKey = 'proceed_offer_' . $userSessionId; // Cache name: id_proceed

        // --- Step 0.1: Attempt to retrieve from "proceed" cache first ---
        $cachedOffer = Cache::get($proceedCacheKey);

        if ($cachedOffer) {
            Log::info("Returning cached 'proceed' offer for user session {$userSessionId}.");
            return view('booking-details', [
                'offerData' => $cachedOffer,
                'checkInDate' => $checkInDate,
                'checkOutDate' => $checkOutDate,
                'adults' => $adults,
                'userId' => $userSessionId, // Pass the session ID to the Blade view
            ]);
        }

        // --- Step 1: Fetch offer details from Amadeus (if not in cache) ---
        Log::info("Step 1: Sending Amadeus Hotel Offers (v3) for single offer details directly by ID: {$offerId}.");
        try {
            $offerDetailsUrl = "{$this->amadeusBaseUrl}/v3/shopping/hotel-offers/{$offerId}";

            Log::info('Sending Amadeus Hotel Offers (v3) for single offer details directly by ID:', ['url' => $offerDetailsUrl]);

            Log::info('step1');
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
            ])->get($offerDetailsUrl);

            Log::info('Amadeus Single Offer Details Response Status:', ['status' => $response->status()]);
            Log::info('Amadeus Single Offer Details Raw Response:', ['body' => $response->body()]);

            if ($response->successful() && !empty($response->json('data'))) {
                $foundOffer = $response->json('data');

                if ($foundOffer) {
                    Log::info('Successfully retrieved specific hotel offer details for view.');

                    // --- Cache the offer data for "proceed" scenario ---
                    Cache::put($proceedCacheKey, $foundOffer, 24 * 60 * 60); // Cache for 24 hours
                    Log::info("Offer data cached for 'proceed' scenario with key: {$proceedCacheKey}");

                    Log::info('',[$foundOffer]);

                    return view('booking-details', [
                        'offerData' => $foundOffer,
                        'checkInDate' => $checkInDate,
                        'checkOutDate' => $checkOutDate,
                        'adults' => $adults,
                        'userId' => $userSessionId, // Pass the session ID to the Blade view
                    ]);
                } else {
                    Log::warning('Offer data was empty despite successful response for view.', ['offerId' => $offerId]);
                    return view('booking-details', ['error' => 'Offer details not found.']);
                }
            } else {
                Log::error('Failed to retrieve offer details from Amadeus.', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return view('booking-details', ['error' => 'Could not retrieve offer details from provider.']);
            }

        } catch (\Exception $e) {
            Log::error('Exception fetching offer details:', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'An unexpected error occurred.']);
        }
    }

    /**
     * Fetches details for a specific hotel offer and returns JSON.
     * This method is specifically for API calls from the frontend.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOfferDetailsJson(Request $request)
    {
        Log::info('API: Offer details request received (for JSON response).');
        $accessToken = $this->getAmadeusAccessToken();

        if (!$accessToken) {
            return response()->json(['error' => 'Failed to obtain Amadeus access token for offer details.'], 500);
        }

        // Validate required parameters from frontend
        $offerId = $request->input('offerId');
        $hotelId = $request->input('hotelId');
        $checkInDate = $request->input('checkInDate');
        $checkOutDate = $request->input('checkOutDate');
        $adults = $request->input('adults') ?? 1; // Default to 1 if not provided, same as recommendations

        if (!$offerId || !$hotelId || !$checkInDate || !$checkOutDate) {
            return response()->json(['error' => 'Missing required parameters for offer details.'], 400);
        }

        // --- User Session ID Management for "Proceed" Cache ---
        $userSessionId = Session::get('user_unique_id');
        if (!$userSessionId) {
            $userSessionId = Str::uuid();
            Session::put('user_unique_id', $userSessionId);
            Log::info("New user session ID created for offer details JSON: {$userSessionId}");
        } else {
            Log::info("Existing user session ID for offer details JSON: {$userSessionId}");
        }

        // Define the cache key for the "proceed" offer (same as the view method uses)
        $proceedCacheKey = 'proceed_offer_' . $userSessionId;

        // --- Attempt to retrieve from "proceed" cache first ---
        $cachedOffer = Cache::get($proceedCacheKey);

        if ($cachedOffer && ($cachedOffer['id'] ?? null) === $offerId) {
            Log::info("Returning cached 'proceed' offer (JSON) for user session {$userSessionId}.");
            return response()->json($cachedOffer);
        }

        // --- Fetch offer details from Amadeus (if not in cache or cache mismatch) ---
        Log::info("API: Sending Amadeus Hotel Offers (v3) for single offer details directly by ID: {$offerId}.");
        try {
            $offerDetailsUrl = "{$this->amadeusBaseUrl}/v3/shopping/hotel-offers/{$offerId}";

            Log::info('API: Sending Amadeus Hotel Offers (v3) for single offer details directly by ID:', ['url' => $offerDetailsUrl]);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
            ])->get($offerDetailsUrl);

            Log::info('API: Amadeus Single Offer Details Response Status:', ['status' => $response->status()]);
            Log::info('API: Amadeus Single Offer Details Raw Response:', ['body' => $response->body()]);

            if ($response->successful() && !empty($response->json('data'))) {
                $foundOffer = $response->json('data');

                if ($foundOffer) {
                    Log::info('API: Successfully retrieved specific hotel offer details (JSON).');

                    // Cache the offer data for "proceed" scenario (for both view and API calls)
                    Cache::put($proceedCacheKey, $foundOffer, 24 * 60 * 60); // Cache for 24 hours
                    Log::info("API: Offer data cached for 'proceed' scenario with key: {$proceedCacheKey}");

                    return response()->json($foundOffer);
                } else {
                    Log::warning('API: Offer data was empty despite successful response.', ['offerId' => $offerId]);
                    return response()->json(['error' => 'Offer details not found.'], 404);
                }
            } else {
                Log::error('API: Failed to retrieve offer details from Amadeus.', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return response()->json(['error' => 'Could not retrieve offer details from provider.'], 500);
            }

        } catch (\Exception $e) {
            Log::error('API: Exception fetching offer details:', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'An unexpected error occurred while fetching offer details.'], 500);
        }
    }
}
