<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use function PHPUnit\Framework\isEmpty;

class PaymentController extends Controller
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
    public function pendingReservation(Request $request)
    {
        $userSessionId = Session::get('user_unique_id');
        $tokenId = $request->query('token');
        $paymnetId = $request->query('paymentId');

        if($tokenId){

            Log::info('HI');
            $id = Crypt::decryptString($tokenId);

            $savedInfo = Reservation::find($id);

            Log::info('info',[$savedInfo]);

            return view('payment', ['savedInfo' => $savedInfo]);
        }
        else if(auth()->check() && $paymnetId)
        {

            $customerId = Auth::user()->customer->id;

            $reservation = Reservation::where('id',$paymnetId)->where('customer_id', $customerId)
                    ->first();

            return view('payment',['savedInfo'=> $reservation]);

        }
        else if ($userSessionId && isEmpty($tokenId)) {
            $cacheOffer = Cache::get('final_proceed_booking_'. $userSessionId);

            $accessToken = $this->getAmadeusAccessToken();

            // Variables
            $offerId = $cacheOffer['offerId'];

            if ($accessToken) {
                $offerDetailsUrl = "{$this->amadeusBaseUrl}/v3/shopping/hotel-offers/{$offerId}";

                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$accessToken}",
                ])->get($offerDetailsUrl);

                if ($response->successful() && !empty($response->json('data'))) {
                    
                    $foundOffer = $response->json('data');

                    if ($foundOffer) {

                        return view('payment',['cache'=> $cacheOffer, 'extra'=> $foundOffer]);
                    }
                }

            }

            return view('payment',$cacheOffer);
        }
        else {
            Log::warning('No user session ID found for reservation. Cannot proceed without cached offer.');
            return response()->json(['error' => 'Your session has expired or offer details are missing. Please go back to search and select an offer again.'], 400);
        }

    }

    public function confirmPayment(Request $request)
    {
        $customerId = $request->customerId;

        Log::info($customerId);

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'card_number' => ['required', 'digits_between:13,19'],
            'expiry_month' => ['required', 'integer', 'between:1,12'],
            'expiry_year' => ['required', 'integer', 'min:' . date('Y')],
            'cvv' => ['required', 'digits_between:3,4'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Run Luhn algorithm to simulate credit card check
        if (!$this->passesLuhnCheck($request->card_number)) {
            return response()->json(['error' => 'Invalid credit card number.'], 400);
        }

        $userSessionId = Session::get('user_unique_id');
        if (!$userSessionId) {
            Log::warning('No user session ID found for reservation. Cannot proceed without cached offer.');
            return response()->json(['error' => 'Your session has expired or offer details are missing. Please go back to search and select an offer again.'], 400);
        }

        $cacheOffer = Cache::get('final_proceed_booking_'. $userSessionId);

        // Log::info('hi', [$cacheOffer]);
        $reservation = Reservation::create([
            'id',
            'offerId'=>$cacheOffer['offerId'],
            'status' => 'PAYED',
            'Hotel_name'=>$cacheOffer['offerDetails']['hotelName'],
            'Room_type'=> $cacheOffer['offerDetails']['roomType'],
            'Totel_price'=> $cacheOffer['offerDetails']['totalPrice'],
            'currency'=> $cacheOffer['offerDetails']['currency'],
            'Check_in_date'=> $cacheOffer['offerDetails']['checkInDate'],
            'Check_out_date'=> $cacheOffer['offerDetails']['checkOutDate'],
            'Customer_id'=>$customerId,
            'multi_customer_id'=> null,
            'created_at'=> now(),
        ]);

        $encrypted = Crypt::encryptString($reservation->id);
        $url = route('payment.page', ['token' => $encrypted]);

        Cache::forget('final_proceed_booking_' . $userSessionId);

        // Simulate success
        return response()->json([
            'message' => 'Payment confirmed.',
            'link' => $url,
        ], 200);
    }

    private function passesLuhnCheck($number)
    {
        $number = preg_replace('/\D/', '', $number);
        $sum = 0;
        $alt = false;

        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $n = (int) $number[$i];
            if ($alt) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
            $alt = !$alt;
        }

        return $sum % 10 === 0;
    }
    
}