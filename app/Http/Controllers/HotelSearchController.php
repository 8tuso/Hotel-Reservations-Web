<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache; // NEW: For caching search results and history
use Illuminate\Support\Facades\Session; // NEW: For session management and user ID
use Illuminate\Validation\Rule;
use Illuminate\Support\Str; // For Str::limit and Str::uuid
use Carbon\Carbon; // Import Carbon for date manipulation

class HotelSearchController extends Controller
{
    /**
     * Handle the hotel search request by city name, then fetch hotel IDs, then fetch offers.
     * Uses Amadeus v3 shopping offers API.
     * This method will now save search history and cache to Laravel's cache based on session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        Log::info('Step 0: Hotel search request received in HotelSearchController.');

        // Validate inputs for city search and offer search
        $request->validate([
            'cityName' => 'required|string|max:255',
            'checkInDate' => 'required|date_format:Y-m-d|after_or_equal:today',
            'checkOutDate' => 'required|date_format:Y-m-d|after:checkInDate',
            'adults' => 'required|integer|min:1|max:9',
            'roomQuantity' => 'required|integer|min:1|max:9',
            'roomType' => 'nullable|string|max:255',
        ]);

        $cityName = $request->input('cityName');
        $checkInDate = $request->input('checkInDate');
        $checkOutDate = $request->input('checkOutDate');
        $adults = (int)$request->input('adults');
        $roomQuantity = (int)$request->input('roomQuantity');
        $roomType = $request->input('roomType');

        $amadeusConfig = config('services.amadeus');
        $tokenUrl = $amadeusConfig['base_url'] . '/v1/security/oauth2/token';

        // --- User Session ID Management ---
        $userSessionId = Session::get('user_unique_id');
        if (!$userSessionId) {
            $userSessionId = Str::uuid();
            Session::put('user_unique_id', $userSessionId);
            Log::info("New user session ID created: {$userSessionId}");
        } else {
            Log::info("Existing user session ID: {$userSessionId}");
        }

        // --- Step 0.1: Check Cache in Laravel's Cache for direct search results ---
        Log::info('Step 0.1: Attempting to check cache in Laravel Cache for current search.');
        $cacheKey = 'search_results_' . $userSessionId . '_' . md5(json_encode([
            'cityName' => $cityName,
            'checkInDate' => $checkInDate,
            'checkOutDate' => $checkOutDate,
            'adults' => $adults,
            'roomQuantity' => $roomQuantity,
            'roomType' => $roomType,
        ]));

        $cachedData = Cache::get($cacheKey);

        // Cache valid for 60 minutes
        if ($cachedData && Carbon::parse($cachedData['timestamp'])->diffInMinutes(now()) < 60) {
            Log::info("Returning cached search results for user {$userSessionId}.");
            return response()->json($cachedData['results']);
        }

        // --- Step 1: Get Amadeus Access Token ---
        try {
            $tokenResponse = Http::asForm()->post($tokenUrl, [
                'grant_type' => 'client_credentials',
                'client_id' => $amadeusConfig['client_id'],
                'client_secret' => $amadeusConfig['client_secret'],
            ]);

            if ($tokenResponse->failed()) {
                Log::error('Amadeus Token Error: ' . $tokenResponse->body());
                return response()->json(['error' => 'Failed to retrieve Amadeus access token.'], 500);
            }

            $accessToken = $tokenResponse->json('access_token');
            if (!$accessToken) {
                Log::error('Amadeus Token Error: Access token not found in response.');
                return response()->json(['error' => 'Invalid Amadeus access token response.'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Amadeus Token Exception: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while fetching Amadeus token.'], 500);
        }

        // --- Step 2: Convert City Name to IATA City Code using /v1/reference-data/locations/cities ---
        $iataCityCode = null;

        if (strlen($cityName) === 3 && ctype_alpha($cityName)) {
            $iataCityCode = strtoupper($cityName);
        } else {
            $cityLookupUrl = $amadeusConfig['base_url'] . '/v1/reference-data/locations/cities';
            try {
                $cityLookupResponse = Http::withToken($accessToken)->get($cityLookupUrl, [
                    'keyword' => $cityName,
                ]);

                if ($cityLookupResponse->failed()) {
                    Log::error('Amadeus City Lookup Error: ' . $cityLookupResponse->body());
                    return response()->json(['error' => 'Failed to find city code for: ' . $cityName], 500);
                }

                $locations = $cityLookupResponse->json('data', []);
                if (!empty($locations) && isset($locations[0]['iataCode'])) {
                    $iataCityCode = $locations[0]['iataCode'];
                }
            } catch (\Exception $e) {
                Log::error('Amadeus City Lookup Exception: ' . $e->getMessage());
                return response()->json(['error' => 'An error occurred while looking up the city.'], 500);
            }
        }

        if (!$iataCityCode) {
            return response()->json(['error' => 'Could not find a valid city code for "' . $cityName . '". Please try a more specific city name (e.g., "Paris") or a 3-letter IATA code (e.g., "PAR").'], 404);
        }

        // --- Step 3: Search for Hotel IDs by City Code ---
        $hotelSearchUrl = $amadeusConfig['base_url'] . '/v1/reference-data/locations/hotels/by-city';
        $hotelIds = [];

        try {
            $hotelIdResponse = Http::withToken($accessToken)->get($hotelSearchUrl, [
                'cityCode' => $iataCityCode,
            ]);

            if ($hotelIdResponse->failed()) {
                Log::error('Amadeus Hotel ID Search Error: ' . $hotelIdResponse->body());
                return response()->json(['error' => 'Failed to find hotels for the specified city code.'], 500);
            }

            $hotelsInCity = $hotelIdResponse->json('data', []);
            if (empty($hotelsInCity)) {
                return response()->json([], 200);
            }

            $hotelIds = collect($hotelsInCity)->pluck('hotelId')->unique()->take(10)->toArray();
            if (empty($hotelIds)) {
                return response()->json([], 200);
            }
        } catch (\Exception $e) {
            Log::error('Amadeus Hotel ID Search Exception: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while searching for hotel IDs in the city.'], 500);
        }

        // --- Step 4: Search for Hotel Offers using the retrieved Hotel IDs (NOW USING V3 API) ---
        $offersSearchUrl = $amadeusConfig['base_url'] . '/v3/shopping/hotel-offers'; // Changed to V3

        try {
            $hotelOffersResponse = Http::withToken($accessToken)->get($offersSearchUrl, [
                'hotelIds' => implode(',', $hotelIds),
                'checkInDate' => $checkInDate,
                'checkOutDate' => $checkOutDate,
                'adults' => $adults,
                'roomQuantity' => $roomQuantity,
                'view' => 'FULL_ALL_PRICES' // To get more detailed offer info for parsing
            ]);

            if ($hotelOffersResponse->failed()) {
                Log::error('Amadeus Hotel Offers V3 Error: ' . $hotelOffersResponse->body());
                return response()->json(['error' => 'Failed to fetch hotel offers (V3 API): ' . ($hotelOffersResponse->json('errors')[0]['detail'] ?? 'Unknown error')], 500);
            }

            $offers = $hotelOffersResponse->json('data', []);

            // --- Step 5: Filter Results based on Frontend's Room Type Input ---
            $filteredOffers = collect($offers)->filter(function ($offer) use ($roomType) {
                if (!$roomType) {
                    return true;
                }
                foreach ($offer['offers'] as $offerDetail) {
                    $roomDetails = $offerDetail['room'] ?? null;
                    if ($roomDetails && isset($roomDetails['type'])) {
                        if (stripos($roomDetails['type'], $roomType) !== false) {
                            return true;
                        }
                    }
                    if ($roomDetails && isset($roomDetails['description']['text'])) {
                        if (stripos($roomDetails['description']['text'], $roomType) !== false) {
                            return true;
                        }
                    }
                }
                return false;
            })->values()->toArray();

            // Prepare results for frontend
            $formattedResults = collect($filteredOffers)->map(function ($offer) use ($checkInDate, $checkOutDate, $adults) {
                $hotel = $offer['hotel'] ?? [];
                $firstOfferDetails = !empty($offer['offers']) ? $offer['offers'][0] : [];

                $roomType = $firstOfferDetails['room']['type'] ?? ($firstOfferDetails['room']['typeEstimated']['category'] ?? 'N/A');
                $price = $firstOfferDetails['price']['total'] ?? 'N/A';
                $currency = $firstOfferDetails['price']['currency'] ?? '$';
                $amenities = collect($firstOfferDetails['amenities'] ?? [])->pluck('description')->implode(', ');
                if (empty($amenities) && isset($firstOfferDetails['room']['description']['text'])) {
                     $amenities = $firstOfferDetails['room']['description']['text'];
                }
                $amenities = Str::limit($amenities, 100);

                $imageUrl = 'https://placehold.co/400x250/E0F2F7/2C3E50?text=Room';

                if (empty($hotel['name']) || $price === 'N/A' || !isset($firstOfferDetails['room'])) {
                    return null;
                }

                return [
                    'id' => $firstOfferDetails['id'] ?? uniqid('search_offer_'),
                    'hotelId' => $hotel['hotelId'] ?? null,
                    'checkInDate' => $checkInDate,
                    'checkOutDate' => $checkOutDate,
                    'adults' => $adults,
                    'hotel' => $hotel['name'] ?? 'Unknown Hotel',
                    'type' => $roomType,
                    'price' => $currency . ' ' . $price . '/night',
                    'amenities' => $amenities ?: 'No specific amenities listed',
                    'imageUrl' => $imageUrl,
                ];
            })->filter()->values()->toArray(); // Filter out any null results and re-index

            // --- Step 6: Save successful search to Laravel's Cache and History ---
            if (!empty($formattedResults)) {
                try {
                    // Save to search results cache (for direct re-query) - lasts 60 minutes
                    Cache::put($cacheKey, [
                        'timestamp' => now()->toIso8601String(),
                        'results' => $formattedResults,
                    ], 60 * 60); // 60 minutes in seconds

                    // Save to user search history (for recommendations) - lasts 7 days
                    $historyKey = 'user_search_history_' . $userSessionId;
                    $searchHistory = Cache::get($historyKey, []);

                    // Add new search to the beginning of the history
                    array_unshift($searchHistory, [
                        'timestamp' => now()->toIso8601String(),
                        'city' => $cityName,
                        'iataCode' => $iataCityCode,
                        'checkInDate' => $checkInDate,
                        'checkOutDate' => $checkOutDate,
                        'adults' => (int)$adults,
                        'roomQuantity' => (int)$roomQuantity,
                        'roomType' => $roomType,
                        'topResults' => array_slice($formattedResults, 0, 3) // Save top 3 results for quick display
                    ]);

                    // Limit history to last 10 searches to prevent it from growing too large
                    $searchHistory = array_slice($searchHistory, 0, 10);

                    Cache::put($historyKey, $searchHistory, 7 * 24 * 60 * 60); // 7 days in seconds

                    Log::info("Search results cached and history saved for user session {$userSessionId} in city {$cityName}.");
                } catch (\Exception $e) {
                    Log::error('Laravel Cache Save Error in HotelSearchController: ' . $e->getMessage());
                }
            }

            Log::info('Successful Hotel Search Results (Formatted for Frontend):', $formattedResults);
            return response()->json($formattedResults);

        } catch (\Exception $e) {
            Log::error('Amadeus Hotel Offers Exception in HotelSearchController: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred while fetching hotel offers.'], 500);
        }
    }
}