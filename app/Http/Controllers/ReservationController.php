<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache; // Added: For caching
use Illuminate\Support\Facades\Session; // Added: For session management
use Carbon\Carbon;
use Illuminate\Support\Str; // For Str::uuid

class ReservationController extends Controller
{
    // Removed Amadeus API credentials and getAmadeusAccessToken method
    // as per the requirement that this controller does not call external APIs.

    /**
     * Handles the creation of a hotel reservation.
     * This method will first attempt to retrieve the offer details from cache.
     * No external API calls are made for the actual booking process as per requirement.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createReservation(Request $request)
    {
        Log::info('Step 0: Create reservation request received.');


        // --- User Session ID Management ---
        $userSessionId = Session::get('user_unique_id');
        if (!$userSessionId) {
            Log::warning('No user session ID found for reservation. Cannot proceed without cached offer.');
            return response()->json(['error' => 'Your session has expired or offer details are missing. Please go back to search and select an offer again.'], 400);
        } else {
            Log::info("Existing user session ID for reservation: {$userSessionId}");
        }

                        Log::info('Step 2');

        // Define the cache key for the "proceed" offer (from RecommendationController)
        $proceedCacheKey = 'proceed_offer_' . $userSessionId; // Cache name: id_proceed

                        Log::info('Step 3');

        // --- Step 1: Attempt to retrieve offer details from "proceed" cache ---
        $offerData = Cache::get($proceedCacheKey);

        if (!$offerData) {
            Log::warning("No cached offer found for user session {$userSessionId}.");
            return response()->json(['error' => 'The offer you were viewing has expired or was not found in cache. Please go back to search and select an offer again.'], 404);
        }

                                Log::info('Step 4');

        // You now have $offerData which contains the full details of the offer
        // that the user proceeded to book.

        $guestName=null;
        $guestEmail =null;
        $guestPhone =null;
        if(!auth()->check())
        {
            $request->validate([
                        'guestName' => 'required|string|max:255',
                        'guestEmail' => 'required|email|max:255',
                        'guestPhone' => 'nullable|string|max:20',
                        // Add any other booking-specific fields you expect (e.g., payment details, special requests)
                    ]);

            $guestName = $request->input('guestName');
            $guestEmail = $request->input('guestEmail');
            $guestPhone = $request->input('guestPhone');
        }
        else{
            $guestName =Auth::user()->name;
            $guestEmail = Auth::user()->email;
            $guestEmail = Auth::user()->phone;
        }
        

        // --- Step 2: Simulate Booking Confirmation ---
        // As per the requirement, no external API calls are made here.
        // You would typically save this reservation to your internal database here.
        // For this example, we'll just log and return a simulated confirmation.

        $bookingConfirmation = [
            'bookingId' => 'RES-' . Str::random(10), // Generate a dummy booking ID
            'status' => 'PENDING',
            'offerId'=>$offerData['offers'][0]['id'],
            'customers_num' => $offerData['adults'] ?? ($offerData['offers'][0]['adults'] ?? 'N/A'), // Added adults count from offerData
            'offerDetails' => [
                'hotelName' => $offerData['hotel']['name'] ?? 'N/A',
                'roomType' => $offerData['offers'][0]['room']['type'] ?? 'N/A',
                'totalPrice' => $offerData['offers'][0]['price']['total'] ?? 'N/A',
                'currency' => $offerData['offers'][0]['price']['currency'] ?? '$',
                // Use the checkInDate and checkOutDate from the original request/offerData
                'checkInDate' => $offerData['offers'][0]['checkInDate'] ?? 'N/A',
                'checkOutDate' => $offerData['offers'][0]['checkOutDate'] ?? 'N/A',
                'img'=>$offerData['offers']
            ],
            'guestDetails' => [
                'name' => $guestName,
                'email' => $guestEmail,
                'phone' => $guestPhone,
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        $customer = null;
        if(Auth()->check())
        {
            
            $customer = Auth::user()->customer;
        }
        else
        {
            $customer = Customer::updateOrCreate(
    ['email' => $guestEmail], // unique identifier (email)
        [
                    'id',
                    'full_name'=> $guestName,
                    'phone'=>$guestPhone
                ]
            );
        }

        Log::info('Step 8');

        Log::info('Simulated Booking successful:', $bookingConfirmation);

        // Also clear the new 'final_proceed_booking' cache if it exists, as the booking is complete
        Cache::put('final_proceed_booking_' . $userSessionId, $bookingConfirmation, now()->addDay());


        return response()->json([
            'message' => 'Reservation created successfully!',
            'confirmation' => $bookingConfirmation,
            'customerId' => $customer->id,
        ], 200);
    }

    /**
     * Caches the offer details when the user clicks "Proceed to Booking".
     * This acts as a temporary marker before the final booking form.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cacheProceedBooking(Request $request)
    {
        Log::info('Cache proceed booking request received.');

        // --- User Session ID Management ---
        $userSessionId = Session::get('user_unique_id');
        if (!$userSessionId) {
            Log::warning('No user session ID found for cacheProceedBooking. Cannot proceed.');
            return response()->json(['error' => 'Your session has expired. Please return to the search page.'], 400);
        } else {
            Log::info("Existing user session ID for cacheProceedBooking: {$userSessionId}");
        }

        Log::info("step 1");

        // Validate required parameters from the frontend (these are used to identify the offer)
        // $request->validate([
        //     'offerId' => 'required|string',
        //     'hotelId' => 'required|string',
        //     'checkInDate' => 'required|date_format:Y-m-d',
        //     'checkOutDate' => 'required|date_format:Y-m-d',
        //     'adults' => 'required|integer|min:1',
        // ]);

        Log::info("step 2");


        $offerId = $request->input('offerId');
        $hotelId = $request->input('hotelId');
        $checkInDate = $request->input('checkInDate');
        $checkOutDate = $request->input('checkOutDate');
        $adults = $request->input('adults');

        Log::info("step 3");

        // Retrieve the full offer data from the initial 'proceed_offer' cache
        $initialProceedCacheKey = 'proceed_offer_' . $userSessionId;
        $fullOfferData = Cache::get($initialProceedCacheKey);

                Log::info("step 4");
                Log::info('cache',[$fullOfferData]);
                Log::info('offer',[$offerId]);


                    Log::info('cache',[$fullOfferData['offers'][0]['id']]);


        if (!$fullOfferData || ($fullOfferData['offers'][0]['id'] ?? null) != $offerId) {
            Log::warning("Full offer data not found in initial cache or ID mismatch for user {$userSessionId}.");
            return response()->json(['error' => 'Offer details not found or mismatched in cache. Please go back and select the offer again.'], 404);
        }

                Log::info("step 5");

        // Define the new cache key for the "final proceed" state
        $finalProceedCacheKey = 'final_proceed_booking_' . $userSessionId; // Cache name: final_proceed_booking_id

        // Store the relevant offer details in the new cache.
        // We'll store the full offer data for simplicity, but you could store a subset.
        Cache::put($finalProceedCacheKey, $fullOfferData, 60 * 60); // Cache for 1 hour

                Log::info("step 6");

        Log::info("Offer for final booking step cached successfully for user {$userSessionId} with key: {$finalProceedCacheKey}");

        return response()->json(['message' => 'Offer details temporarily saved for booking process.'], 200);
    }
}
