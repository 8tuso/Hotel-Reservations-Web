<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User; // Assuming User model
use App\Models\Customer; // Assuming Customer model
use Carbon\Carbon; 
use Log;
use Session;// For date formatting in dummy data

class UserController extends Controller
{
    /**
     * Display the user's dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        $user = Auth::user();

        $viewedOffers = []; // Initialize as an empty array
        $userSessionId = Session::get('user_unique_id');

        if ($userSessionId) {
            $proceedCacheKey = 'proceed_offer_' . $userSessionId;
            $cachedOfferData = Cache::get($proceedCacheKey); // Get the full cached data

            if ($cachedOfferData && isset($cachedOfferData['offers']) && is_array($cachedOfferData['offers'])) {
                // If the cached data exists and contains an 'offers' array,
                // assign that 'offers' array to $viewedOffers
                $viewedOffers = $cachedOfferData['offers'];

                // Optionally, add hotel name to each offer for easier display
                $hotelName = $cachedOfferData['hotel']['name'] ?? 'Unknown Hotel';
                foreach ($viewedOffers as &$offer) {
                    $offer['hotel_name'] = $hotelName; // Add hotel name for display
                    // You might also want to format price and create a link here
                    $offer['display_price'] = number_format($offer['price']['total'] ?? 0, 2);
                    $offer['display_currency'] = $offer['price']['currency'] ?? '$';
                    $offer['link'] = route('booking.details', [ // Assuming you have a route for offer details
                        'hotelId' => $cachedOfferData['hotel']['hotelId'],
                        'offerId' => $offer['id'],
                        'checkInDate' => $offer['checkInDate'],
                        'checkOutDate' => $offer['checkOutDate'],
                        'adults' => $offer['guests']['adults']
                    ]);
                    $offer['description_text'] = $offer['description']['text'] ?? 'No description available.'; // Extract description text
                }
                unset($offer); // Unset the reference after the loop
            } else {
                Log::info('No valid offers array found in cache for user session: ' . $userSessionId);
            }
        } else {
            Log::info('No user session ID found for dashboard.');
        }

        // Fetch running bookings
        $runningBookings = collect(); // Initialize as empty collection
        if ($user && $user->customer_id) { // Ensure user and customer_id exist
            $runningBookings = Reservation::where('customer_id', $user->customer_id)->get();
        }

        return view('userDashboard', [
            'user' => $user,
            'viewedOffers' => $viewedOffers, // Now this is an array of actual offers
            'runningBookings' => $runningBookings,
        ]);
    }

    /**
     * Handle the profile update request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        // Validate incoming request data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|max:10',
            'nationality' => 'nullable|string|max:100',
            'passport_number' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
        ]);

        // Update the Customer record associated with the user
        // Ensure you have a 'customer' relationship defined in your User model
        // e.g., public function customer() { return $this->hasOne(Customer::class); }
        if ($user->customer) {
            $user->customer->update([
                'full_name'       => $validatedData['name'],
                'phone'           => $validatedData['phone'],
                'date_of_birth'   => $validatedData['date_of_birth'],
                'gender'          => $validatedData['gender'],
                'nationality'     => $validatedData['nationality'],
                'passport_number' => $validatedData['passport_number'],
                'address'         => $validatedData['address'],
                'city'            => $validatedData['city'],
                'country'         => $validatedData['country'],
                'postal_code'     => $validatedData['postal_code'],
            ]);
            // You might also want to update the user's name if it's separate
            $user->update(['name' => $validatedData['name']]);
        } else {
            // Handle case where user might not have a customer record yet
            // This scenario might mean creating a new Customer record or just updating User model directly.
            // For simplicity, we'll just update the user's name if customer doesn't exist
             $user->update(['name' => $validatedData['name']]);
             // Or, create a new customer record here if it's mandatory
        }


        // Redirect back with a success message
        return redirect()->back()->with('success', 'Profile updated successfully!');
    }
}