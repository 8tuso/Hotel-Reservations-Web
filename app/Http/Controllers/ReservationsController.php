<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Firestore; // Import Firestore
use Carbon\Carbon; // For timestamp

class ReservationsController extends Controller
{
    protected $firestore;

    public function __construct(Firestore $firestore)
    {
        $this->firestore = $firestore;
    }

    /**
     * Caches the offer details for a specific user.
     * The cache is unique per user and offerId.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cacheOffer(Request $request)
    {
        Log::info('Cache offer request received.');

        // Validate required fields
        $request->validate([
            'userId' => 'required|string',
            'offerId' => 'required|string',
            'hotelId' => 'nullable|string',
            'checkInDate' => 'required|date_format:Y-m-d',
            'checkOutDate' => 'required|date_format:Y-m-d',
            'adults' => 'required|integer|min:1',
            'hotelName' => 'nullable|string',
            'roomType' => 'nullable|string',
            'price' => 'nullable|string', // Price as formatted string
            'amenities' => 'nullable|string',
            'imageUrl' => 'nullable|string',
            'rawOfferData' => 'required|array', // The full raw offer data from Amadeus
        ]);

        $userId = $request->input('userId');
        $offerId = $request->input('offerId');
        $hotelId = $request->input('hotelId');
        $checkInDate = $request->input('checkInDate');
        $checkOutDate = $request->input('checkOutDate');
        $adults = $request->input('adults');
        $hotelName = $request->input('hotelName');
        $roomType = $request->input('roomType');
        $price = $request->input('price');
        $amenities = $request->input('amenities');
        $imageUrl = $request->input('imageUrl');
        $rawOfferData = $request->input('rawOfferData'); // Get the raw offer data

        try {
            $appId = env('APP_ID', 'default-app-id');
            // Path for cached offers for a specific user
            $collectionPath = "artifacts/{$appId}/users/{$userId}/cached_offers";
            $documentId = $offerId; // Use offerId as the document ID for uniqueness per offer

            $cachedOfferData = [
                'offerId' => $offerId,
                'hotelId' => $hotelId,
                'checkInDate' => $checkInDate,
                'checkOutDate' => $checkOutDate,
                'adults' => $adults,
                'hotelName' => $hotelName,
                'roomType' => $roomType,
                'price' => $price,
                'amenities' => $amenities,
                'imageUrl' => $imageUrl,
                'rawOfferData' => $rawOfferData, // Store the full raw data
                'cachedAt' => Carbon::now()->toIso8601String(),
                'expiresAt' => Carbon::now()->addHours(24)->toIso8601String(), // Cache for 24 hours
            ];

            // Set the document in Firestore
            $this->firestore->collection($collectionPath)->document($documentId)->set($cachedOfferData);

            Log::info("Offer {$offerId} cached successfully for user {$userId}.");
            return response()->json(['status' => 'success', 'message' => 'Offer cached successfully.', 'offerId' => $offerId], 200);

        } catch (\Exception $e) {
            Log::error('Failed to cache offer for user ' . $userId . ': ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['status' => 'error', 'message' => 'Failed to cache offer.', 'details' => $e->getMessage()], 500);
        }
    }
}
