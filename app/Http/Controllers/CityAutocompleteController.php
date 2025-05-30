<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CityAutocompleteController extends Controller
{
    /**
     * Search for city suggestions based on a keyword using /v1/reference-data/locations/cities.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string|min:1',
        ]);

        $keyword = $request->input('keyword');

        $amadeusConfig = config('services.amadeus');
        $tokenUrl = $amadeusConfig['base_url'] . '/v1/security/oauth2/token';

        // --- Step 1: Get Amadeus Access Token ---
        try {
            $tokenResponse = Http::asForm()->post($tokenUrl, [
                'grant_type' => 'client_credentials',
                'client_id' => $amadeusConfig['client_id'],
                'client_secret' => $amadeusConfig['client_secret'],
            ]);

            if ($tokenResponse->failed()) {
                Log::error('Amadeus Token Error (City Autocomplete): ' . $tokenResponse->body());
                return response()->json(['error' => 'Failed to retrieve Amadeus access token for autocomplete.'], 500);
            }

            $accessToken = $tokenResponse->json('access_token');
            if (!$accessToken) {
                Log::error('Amadeus Token Error (City Autocomplete): Access token not found in response.');
                return response()->json(['error' => 'Invalid Amadeus access token response for autocomplete.'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Amadeus Token Exception (City Autocomplete): ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while fetching Amadeus token for autocomplete.'], 500);
        }

        // --- Step 2: Call Amadeus Cities API for suggestions ---
        // Using the more specific /v1/reference-data/locations/cities endpoint
        $citySearchUrl = $amadeusConfig['base_url'] . '/v1/reference-data/locations/cities';

        try {
            $cityResponse = Http::withToken($accessToken)->get($citySearchUrl, [
                'keyword' => $keyword,
                // 'countryCode' => 'US', // Optional: You can add this if you want to limit to a specific country
                'max' => 3 // Limit results to 10 for performance
            ]);

            if ($cityResponse->failed()) {
                Log::error('Amadeus City Autocomplete API Error: ' . $cityResponse->body());
                return response()->json(['error' => 'Failed to fetch city suggestions from Amadeus.'], 500);
            }

            $locations = $cityResponse->json('data', []);

            // Format suggestions for frontend
            $formattedSuggestions = collect($locations)->filter(function($location) {
                // Ensure it has an IATA code and is a city
                return isset($location['iataCode']) && isset($location['subType']) && $location['subType'] === 'city';
            })->map(function($location) {
                $city = $location['name'] ?? 'Unknown City';
                $country = $location['address']['countryCode'] ?? 'Unknown Country'; // Using countryCode for brevity
                $iataCode = $location['iataCode'];

                return [
                    'name' => "$city ($iataCode) - $country", // Display format
                    'iataCode' => $iataCode,
                    'cityName' => $city // Send back the city name for the main search
                ];
            })->unique('iataCode')->values()->toArray(); // Ensure unique IATA codes

            return response()->json($formattedSuggestions);

        } catch (\Exception $e) {
            Log::error('Amadeus City Autocomplete Exception: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred during city suggestion.'], 500);
        }
    }
}
