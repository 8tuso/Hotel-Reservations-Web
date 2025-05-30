<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm & Pay - Hotel Reservation</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f7f7f7; /* Light gray background */
            min-height: 100vh;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .container {
            max-width: 1200px;
            width: 100%;
            padding: 1.5rem;
            box-sizing: border-box;
        }

        /* Styles for logged-in saved card options (kept for completeness) */
        .card-option {
            border: 1px solid #e5e7eb; /* Gray-200 */
            border-radius: 0.5rem;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }
        .card-option:hover {
            border-color: #6366f1; /* Indigo-500 */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .card-option.selected {
            border-color: #4f46e5; /* Indigo-600 */
            background-color: #eef2ff; /* Indigo-50 */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .radio-dot {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid #d1d5db; /* Gray-300 */
            display: inline-block;
            vertical-align: middle;
            margin-right: 0.5rem;
            transition: all 0.2s ease-in-out;
        }
        .card-option.selected .radio-dot {
            border-color: #4f46e5; /* Indigo-600 */
            background-color: #4f46e5; /* Indigo-600 */
        }
        .radio-dot::after {
            content: '';
            display: block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #fff;
            margin: 2px;
            opacity: 0;
            transition: opacity 0.2s ease-in-out;
        }
        .card-option.selected .radio-dot::after {
            opacity: 1;
        }
        .add-payment-btn { /* For logged-in users */
            border: 1px dashed #d1d5db; /* Gray-300 */
            color: #4b5563; /* Gray-600 */
            background-color: transparent;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
        }
        .add-payment-btn:hover {
            border-color: #6366f1; /* Indigo-500 */
            color: #4f46e5; /* Indigo-600 */
            background-color: #eef2ff; /* Indigo-50 */
        }

        /* Updated Input Field Style */
        .input-field {
            @apply block w-full px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400
                   border border-gray-300 rounded-md shadow-sm
                   focus:outline-none focus:ring-2 focus:ring-offset-0 focus:ring-indigo-500 focus:border-indigo-500
                   hover:border-gray-400
                   transition-all duration-200 ease-in-out;
        }

        /* Footer styles (unchanged from original) */
        .footer-link-group h4 {
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: #374151; /* Gray-700 */
        }
        .footer-link-group ul li a {
            color: #6b7280; /* Gray-500 */
            transition: color 0.2s;
        }
        .footer-link-group ul li a:hover {
            color: #4f46e5; /* Indigo-600 */
        }

        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease-out, visibility 0.3s ease-out;
        }

        .popup-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        /* Popup Content */
        .popup-content {
            background-color: #fff;
            padding: 2rem; /* Adjusted padding */
            border-radius: 0.75rem;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 500px;
            width: 90%;
            transform: scale(0.9);
            transition: transform 0.3s ease-out;
            position: relative; /* For close button positioning */
        }

        .popup-overlay.show .popup-content {
            transform: scale(1);
        }

        /* Close Button */
        .close-button {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #9ca3af; /* gray-400 */
            transition: color 0.2s;
        }

        .close-button:hover {
            color: #4b5563; /* gray-700 */
        }
    </style>
</head>
<body class="overflow-x-hidden">

    <div id="myPopupOverlay" class="popup-overlay">
        <div class="popup-content">
            <button id="closePopupButton" class="close-button">&times;</button>
            <div id="popupInnerContent">
                {{-- Initial popup content, will be dynamically updated --}}
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Processing Payment...</h2>
                <div class="spinner mx-auto mb-4" style="border-left-color: #4f46e5; border: 4px solid rgba(0, 0, 0, 0.1); border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite;"></div>
                <p class="text-gray-600">Please wait while we confirm your payment.</p>
            </div>
        </div>
    </div>

    <div class="container bg-white shadow-lg rounded-xl my-8 md:my-12">

        <header class="flex flex-col sm:flex-row justify-between items-center py-4 border-b border-gray-200 mb-6 px-4">
            <div class="flex items-center mb-4 sm:mb-0">
                <span class="text-3xl font-bold text-gray-800">Hotel Reservation</span>
            </div>
            <div class="flex items-center">
                <a href="#" class="text-gray-600 hover:text-indigo-600 font-medium transition-colors duration-300 text-sm sm:text-base">
                    &lt; Confirm & pay
                </a>
            </div>
        </header>

        {{-- Main content conditional display starts here --}}
        @if (isset($savedInfo) && $savedInfo->status == "PAYED")
            <div class="p-6 sm:p-10 rounded-xl bg-white my-6 text-center grid " style="grid-template-columns: 70% 1fr;">
                    <div class="flex flex-col items-center justify-center min-h-[300px]">
                        <svg class="w-20 h-20 text-green-500 mb-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <h2 class="text-3xl font-bold text-gray-800 mb-3">Payment Confirmed!</h2>
                        <p class="text-gray-600 text-lg mb-6">Thank you for your booking. Your payment has been successfully processed.</p>
                        @auth
                        <a href="{{route('dashboard')}}"
                           class="mt-4 px-8 py-3 bg-indigo-600 text-white text-base font-semibold rounded-lg shadow-md hover:bg-indigo-700 transition-colors duration-300">
                            View My Bookings
                        </a>
                        @endauth
                    </div>
                    <div class="lg:col-span-1 space-y-8">
                    <div class="bg-white rounded-lg shadow-xl overflow-hidden">
                        <img src="https://placehold.co/400x250/E0F2F7/2C3E50?text={{$savedInfo->Hotel_name}}" alt="{{$savedInfo->Hotel_name}}" class="w-full h-48 object-cover">
                        <div class="p-5">
                            <h4 class="text-lg font-semibold text-gray-800">Place to stay</h4>
                            <p class="text-gray-600 text-sm mb-1">{{$savedInfo->Hotel_name}}</p>
                            <p class="text-gray-500 text-xs">{{$savedInfo->Room_type}}</p>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-xl">
                        <h3 class="text-xl font-bold text-gray-900 mb-5">Your Trip Summary</h3>
                        <div class="space-y-3 text-gray-700 mb-5 text-sm">
                            <div class="flex justify-between items-center">
                                <span class="font-medium">Check-In</span>
                                <span class="font-semibold">{{$savedInfo->Check_in_date}}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-medium">Check-Out</span>
                                <span class="font-semibold">{{$savedInfo->Check_out_date}}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-medium">Guests</span>
                                <span class="font-semibold">{{($savedInfo->multi_customer_id != null) ? count($savedInfo->multi_customer_id) : 'N/A'}}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-medium">Total Price</span>
                                <span class="font-semibold">{{$savedInfo->currency. $savedInfo->Totel_price}}</span>
                            </div>
                        </div>
                      
                    </div>
                </div>
            </div>
        @else
            {{-- This is the original detailed content view --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 p-4">
                <div class="lg:col-span-2 space-y-8">

                    @if (isset($isLoggedIn) && $isLoggedIn)
                    <section id="savedPaymentSection" class="bg-white p-6 rounded-lg shadow-md">
                           <h3 class="text-2xl font-bold text-gray-900 mb-6">Payment Method</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <label class="card-option flex items-center justify-between p-4" data-card="paypal">
                                <div class="flex items-center">
                                    <span class="radio-dot"></span>
                                    <span class="text-gray-700 font-medium">Paypal ending in 1234</span>
                                </div>
                                <span class="text-gray-500 text-sm">Expiry 06/2008</span>
                            </label>
                            <label class="card-option flex items-center justify-between p-4 selected" data-card="mastercard">
                                <div class="flex items-center">
                                    <span class="radio-dot"></span>
                                    <span class="text-gray-700 font-medium">Mastercard ending in 1234</span>
                                </div>
                                <span class="text-gray-500 text-sm">Expiry 06/2024</span>
                            </label>
                            <label class="card-option flex items-center justify-between p-4" data-card="visa">
                                <div class="flex items-center">
                                    <span class="radio-dot"></span>
                                    <span class="text-gray-700 font-medium">Visa ending in 1234</span>
                                </div>
                                <span class="text-gray-500 text-sm">Expiry 06/2008</span>
                            </label>
                            <button id="addNewPaymentBtn" class="add-payment-btn flex items-center justify-center p-4 rounded-lg text-gray-600">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                Add New Payment
                            </button>
                        </div>
                    </section>
                    @endif

                    <section id="newPaymentFormSection" class="bg-white p-6 sm:p-8 rounded-xl shadow-md @if (isset($isLoggedIn) && $isLoggedIn) hidden @endif">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                            <h3 class="text-xl font-semibold text-gray-800 mb-3 sm:mb-0">Pay with card</h3>
                            <div class="flex space-x-2 items-center">
                                <span class="text-xs text-gray-500 mr-1 hidden sm:inline">We accept:</span>
                                <img src="{{ asset('pics/visa.png') }}" alt="Visa" class="h-5">
                                <img src="{{ asset('pics/mastercard.png') }}" alt="Mastercard" class="h-5">
                                <img src="{{ asset('pics/american.png') }}" alt="American Express" class="h-5">
                                <img src="{{ asset('pics/discover.png') }}" alt="Discover" class="h-5">
                            </div>
                        </div>
                        <form id="paymentCardForm" class="space-y-5"> {{-- Added an ID to the form --}}
                            <div>
                                <label for="cardName" class="block text-sm font-medium text-gray-700 mb-1.5">Name on card</label>
                                <input type="text" id="cardName" name="cardName" placeholder="Full name as it appears on card" class="input-field" autocomplete="cc-name" required>
                            </div>
                            <div>
                                <label for="cardNumber" class="block text-sm font-medium text-gray-700 mb-1.5">Card number</label>
                                <input type="text" id="cardNumber" name="cardNumber" placeholder="0000 0000 0000 0000" class="input-field" autocomplete="cc-number" pattern="[\d ]{16,22}" required>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-4 gap-y-5">
                                <div class="sm:col-span-1">
                                    <label for="expiryDate" class="block text-sm font-medium text-gray-700 mb-1.5">Expiration date</label>
                                    <input type="text" id="expiryDate" name="expiryDate" placeholder="MM / YY" class="input-field" autocomplete="cc-exp" pattern="\d{2}\s*\/\s*\d{2}" required>
                                </div>
                                <div class="sm:col-span-1">
                                    <label for="securityCode" class="block text-sm font-medium text-gray-700 mb-1.5">Security code (CVV)</label>
                                    <input type="text" id="securityCode" name="securityCode" placeholder="123" class="input-field" autocomplete="cc-csc" pattern="\d{3,4}" required>
                                </div>
                                <div class="sm:col-span-1">
                                    <label for="zipCode" class="block text-sm font-medium text-gray-700 mb-1.5">ZIP / Postal code</label>
                                    <input type="text" id="zipCode" name="zipCode" placeholder="90210" class="input-field" autocomplete="postal-code" required>
                                </div>
                            </div>
                            <div class="pt-1">
                                   <p class="text-xs text-gray-500">Your payment information is encrypted and securely processed.</p>
                            </div>
                        </form>
                        @if (isset($isLoggedIn) && $isLoggedIn)
                        <button id="backToSavedBtn" class="mt-6 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2.5 px-4 rounded-md w-full transition-colors duration-200 ease-in-out">
                            Back to Saved Cards
                        </button>
                        @endif
                    </section>

                    <section class="bg-white p-6 rounded-lg shadow-md">
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Cancellation policy</h3>
                        <p class="text-gray-700 mb-2 text-sm">Free cancellation before Nov 30.</p>
                        <p class="text-gray-600 text-xs">After that, the reservation is non-refundable. <a href="#" class="text-indigo-600 hover:underline">Learn more</a></p>
                    </section>

                    <section class="bg-white p-6 rounded-lg shadow-md">
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Ground rules</h3>
                        <p class="text-gray-700 mb-4 text-sm">We ask every guest to remember a few simple things about what makes a great guest:</p>
                        <ul class="list-disc list-inside text-gray-700 space-y-1.5 text-sm">
                            <li>Follow the house rules</li>
                            <li>Treat your Host's home like your own</li>
                        </ul>
                    </section>
                </div>

                <div class="lg:col-span-1 space-y-8">
                    <div class="bg-white rounded-lg shadow-xl overflow-hidden">
                      <img 
                        src="https://placehold.co/400x250/E0F2F7/2C3E50?text={{ isset($cache) ? $cache['offerDetails']['hotelName'] : (isset($savedInfo) ? $savedInfo->Hotel_name : '') }}" 
                        alt="{{ isset($cache) ? $cache['offerDetails']['hotelName'] : (isset($savedInfo) ? $savedInfo->Hotel_name : '') }}" 
                        class="w-full h-48 object-cover">
                        <div class="p-5">
                            <h4 class="text-lg font-semibold text-gray-800">Place to stay</h4>
                            <p class="text-gray-600 text-sm mb-1">{{isset($cache) ? $cache['offerDetails']['hotelName'] : (isset($savedInfo) ? $savedInfo->Hotel_name : '')}}</p>
                            <p class="text-gray-500 text-xs">{{isset($cache) ? $cache['offerDetails']['roomType'] : (isset($savedInfo) ? $savedInfo->Room_type : '')}}</p>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-xl">
                        <h3 class="text-xl font-bold text-gray-900 mb-5">Your Trip Summary</h3>
                        <div class="space-y-3 text-gray-700 mb-5 text-sm">
                            <div class="flex justify-between items-center">
                                <span class="font-medium">Check-In</span>
                                <span class="font-semibold">{{isset($cache) ? $cache['offerDetails']['checkInDate'] : (isset($savedInfo) ? $savedInfo->Check_in_date : '')}}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-medium">Check-Out</span>
                                <span class="font-semibold">{{isset($cache) ? $cache['offerDetails']['checkOutDate'] : (isset($savedInfo) ? $savedInfo->Check_out_date : '')}}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-medium">Guests</span>
                                <span class="font-semibold">{{isset($cache) ? $cache['customers_num'] : (isset($savedInfo) ? ($savedInfo->multi_customer_id != null ? count($savedInfo->multi_customer_id) : '') : '')}}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-medium">Booking Id</span>
                                <span class="font-semibold">{{isset($cache) ? $cache['bookingId'] :''}}</span>
                            </div>
                        </div>
                        <div class="border-t border-gray-200 pt-5 space-y-3 text-gray-700 text-sm">
                            <h4 class="text-lg font-bold text-gray-900 mb-3">Pricing Breakdown</h4>
                            <div class="flex justify-between items-center">
                                <span>{{isset($cache) ? $cache['offerDetails']['currency'] : (isset($savedInfo) ? $savedInfo->currency : '')}}30 X 1 night</span>
                                <span>{{isset($cache) ? $cache['offerDetails']['currency'] : (isset($savedInfo) ? $savedInfo->currency : '')}}30</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span>Cleaning Fee</span>
                                <span>{{isset($cache) ? $cache['offerDetails']['currency'] : (isset($savedInfo) ? $savedInfo->currency : '')}}0</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span>Airbnb Service Fee</span>
                                <span>{{isset($cache) ? $cache['offerDetails']['currency'] : (isset($savedInfo) ? $savedInfo->currency : '')}}15</span>
                            </div>
                            <div class="flex justify-between items-center text-base font-bold text-gray-900 border-t border-gray-300 pt-3 mt-3">
                                <span>Total before taxes</span>
                                <span>{{isset($cache) ? $cache['offerDetails']['currency'] : (isset($savedInfo) ? $savedInfo->currency : '')}}{{isset($cache) ? $cache['offerDetails']['totalPrice'] : (isset($savedInfo) ? $savedInfo->Totel_price : '')}}</span>
                            </div>
                        </div>
                        <button id="payment_button" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg shadow-lg
                                       hover:shadow-indigo-500/50 transition-all duration-300 transform hover:scale-105 focus:outline-none
                                       focus:ring-4 focus:ring-indigo-300 mt-8 text-base">
                            Confirm & pay {{isset($cache) ? $cache['offerDetails']['currency'] : (isset($savedInfo) ? $savedInfo->currency : '')}}{{isset($cache) ? $cache['offerDetails']['totalPrice'] : (isset($savedInfo) ? $savedInfo->Totel_price : '')}}
                        </button>
                    </div>
                </div>
            </div>
        @endif
        {{-- Main content conditional display ends here --}}

        <footer class="w-full bg-gray-100 py-10 px-4 mt-12 rounded-b-xl">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                   <div class="col-span-full md:col-span-1 mb-6 md:mb-0">
                    <span class="text-3xl font-bold text-gray-800">Hotel Reservation</span>
                    <p class="text-gray-600 text-sm mt-4">Select a date and time that works for you, reach out with any special requests, and add your team right to the booking.</p>
                </div>
                <div class="footer-link-group">
                    <h4>Support</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="block hover:underline">Help Center</a></li>
                        <li><a href="#" class="block hover:underline">Get help with a safety issue</a></li>
                        <li><a href="#" class="block hover:underline">AirCover</a></li>
                        <li><a href="#" class="block hover:underline">Anti-discrimination</a></li>
                        <li><a href="#" class="block hover:underline">Disability support</a></li>
                        <li><a href="#" class="block hover:underline">Cancellation options</a></li>
                    </ul>
                </div>
                <div class="footer-link-group">
                    <h4>Hosting</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="block hover:underline">Airbnb your home</a></li>
                        <li><a href="#" class="block hover:underline">AirCover for Hosts</a></li>
                        <li><a href="#" class="block hover:underline">Hosting resources</a></li>
                        <li><a href="#" class="block hover:underline">Community forum</a></li>
                        <li><a href="#" class="block hover:underline">Hosting responsibly</a></li>
                        <li><a href="#" class="block hover:underline">Airbnb-friendly apartments</a></li>
                    </ul>
                </div>
                <div class="footer-link-group">
                    <h4>Airbnb</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="block hover:underline">Newsroom</a></li>
                        <li><a href="#" class="block hover:underline">New features</a></li>
                        <li><a href="#" class="block hover:underline">Careers</a></li>
                        <li><a href="#" class="block hover:underline">Investors</a></li>
                        <li><a href="#" class="block hover:underline">Gift cards</a></li>
                        <li><a href="#" class="block hover:underline">Airbnb.org emergency st</a></li>
                    </ul>
                </div>
            </div>
            <div class="text-center text-gray-500 text-sm mt-8 border-t border-gray-200 pt-6">
                <p>&copy; 2024 Hotel Reservation. All rights reserved.</p>
            </div>
        </footer>

    </div> {{-- End of .container --}}

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cardOptions = document.querySelectorAll('.card-option');
            const addNewPaymentBtn = document.getElementById('addNewPaymentBtn');
            const backToSavedBtn = document.getElementById('backToSavedBtn');
            const savedPaymentSection = document.getElementById('savedPaymentSection');
            const newPaymentFormSection = document.getElementById('newPaymentFormSection');

            const paymentButton = document.getElementById('payment_button');
            const myPopupOverlay = document.getElementById('myPopupOverlay');
            const closePopupButton = document.getElementById('closePopupButton');
            const popupInnerContent = document.getElementById('popupInnerContent'); // Target for content update

            // Input fields for formatting
            const cardNumberInput = document.getElementById('cardNumber');
            const expiryDateInput = document.getElementById('expiryDate');
            const securityCodeInput = document.getElementById('securityCode');

            // --- Popup Functions ---
            function openPopup() {
                if (myPopupOverlay) myPopupOverlay.classList.add('show');
            }

            function closePopup() {
                if (myPopupOverlay) myPopupOverlay.classList.remove('show');
            }

            if (closePopupButton) {
                closePopupButton.addEventListener('click', closePopup);
            }

            if (myPopupOverlay) {
                myPopupOverlay.addEventListener('click', function(event) {
                    if (event.target === myPopupOverlay) {
                        closePopup();
                    }
                });
            }

            // --- Saved Card Options Logic ---
            if (cardOptions.length > 0) {
                cardOptions.forEach(option => {
                    option.addEventListener('click', function() {
                        cardOptions.forEach(opt => opt.classList.remove('selected'));
                        this.classList.add('selected');
                    });
                });
            }

            // --- Toggle New Payment Form Logic ---
            if (addNewPaymentBtn) {
                addNewPaymentBtn.addEventListener('click', function() {
                    if (savedPaymentSection) savedPaymentSection.classList.add('hidden');
                    if (newPaymentFormSection) newPaymentFormSection.classList.remove('hidden');
                });
            }

            if (backToSavedBtn) {
                backToSavedBtn.addEventListener('click', function() {
                    if (newPaymentFormSection) newPaymentFormSection.classList.add('hidden');
                    if (savedPaymentSection) savedPaymentSection.classList.remove('hidden');
                });
            }

            // --- Input Formatting Functions ---

            // Format Card Number (XXXX XXXX XXXX XXXX)
            if (cardNumberInput) {
                cardNumberInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
                    value = value.substring(0, 16); // Limit to 16 digits
                    let formattedValue = value.replace(/(\d{4})(?=\d)/g, '$1 '); // Add space every 4 digits
                    e.target.value = formattedValue;
                });
            }

            // Format Expiry Date (MM / YY)
            if (expiryDateInput) {
                expiryDateInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
                    if (value.length > 2) {
                        value = value.substring(0, 2) + ' / ' + value.substring(2, 4); // MM / YY
                    } else if (value.length === 2) {
                        value = value + ' / '; // Add space after MM
                    }
                    e.target.value = value.substring(0, 7); // Limit to MM / YY (7 chars)
                });
            }

            // Format Security Code (CVV) - Max 4 digits
            if (securityCodeInput) {
                securityCodeInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
                    e.target.value = value.substring(0, 4); // Limit to 4 digits
                });
            }


            // --- Payment Button Logic ---
            if (paymentButton) {
                paymentButton.addEventListener('click', async function(event) {
                    event.preventDefault(); // Prevent any default button action

                    // Show processing message in popup
                    popupInnerContent.innerHTML = `
                        <h2 class="text-2xl font-bold text-gray-800 mb-6">Processing Payment...</h2>
                        <div class="spinner mx-auto mb-4" style="border-left-color: #4f46e5; border: 4px solid rgba(0, 0, 0, 0.1); border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite;"></div>
                        <p class="text-gray-600">Please wait while we confirm your payment.</p>
                    `;
                    openPopup();


                    // --- 1. Get Data from Form ---
                    const cardName = document.getElementById('cardName').value.trim();
                    const cardNumber = cardNumberInput.value.replace(/\s+/g, ''); // Remove spaces for backend
                    const expiryDateValue = expiryDateInput.value; // "MM / YY"
                    const cvv = securityCodeInput.value;
                    const zipCode = document.getElementById('zipCode').value.trim();


                    // Basic client-side validation (more robust validation should be on server)
                    if (!cardName || !cardNumber || !expiryDateValue || !cvv || !zipCode) {
                        alert('Please fill in all required card details.');
                        closePopup(); // Close processing popup
                        return;
                    }

                    const expiryParts = expiryDateValue.split(/\s*\/\s*/);
                    if (expiryParts.length !== 2 || expiryParts[0].length !== 2 || expiryParts[1].length !== 2) {
                        alert('Please enter expiry date in MM / YY format (e.g., 01 / 25).');
                        closePopup(); // Close processing popup
                        return;
                    }
                    const expiryMonth = parseInt(expiryParts[0], 10); // Parse as integer
                    const expiryYearShort = expiryParts[1];
                    // Convert YY to YYYY - assuming current century (e.g., 25 -> 2025)
                    const currentYearPrefix = Math.floor(new Date().getFullYear() / 100); // e.g., 20
                    const expiryYear = parseInt(currentYearPrefix + expiryYearShort, 10); // Parse as integer

                    const urlParams = new URLSearchParams(window.location.search);
                    const customerId = urlParams.get('customerId');

                    const paymentData = {
                        card_name: cardName,
                        card_number: cardNumber,
                        expiry_month: expiryMonth, // Now an integer
                        expiry_year: expiryYear,   // Now an integer
                        cvv: cvv,
                        zip_code: zipCode,
                        customerId: customerId,
                    };

                    console.log("Payment data to send:", paymentData);

                    // --- 2. Fetch Request to Backend ---
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    if (!csrfToken) {
                        console.error('CSRF token not found. Make sure it is set in a meta tag.');
                        alert('A security token is missing. Please refresh the page.');
                        closePopup(); // Close processing popup
                        return;
                    }

                    try {
                        const response = await fetch('/payment-confirm', { // Your Laravel route
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify(paymentData)
                        });

                        if (!response.ok) {
                            const errorData = await response.json();
                            throw new Error(errorData.message || 'Payment failed.');
                        }

                        const result = await response.json();
                        const confirmationLink = result.link;

                        // --- Update popup content with link and buttons ---
                        popupInnerContent.innerHTML = `
                            <h2 class="text-2xl font-bold text-gray-800 mb-4">Payment Confirmed!</h2>
                            <p class="text-gray-600 mb-4">Here is your link, don't share it with anyone:</p>
                            <div class="relative w-full mb-6">
                                <input type="text" id="generatedLink" class="input-field pr-16" value="${confirmationLink}" readonly>
                                <button id="copyLinkBtn" class="absolute right-2 top-1/2 -translate-y-1/2 bg-indigo-100 hover:bg-indigo-200 text-indigo-800 text-xs font-semibold py-1 px-2 rounded-md transition-colors">Copy</button>
                            </div>
                            <a href="${confirmationLink}" target="_blank" class="block w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg shadow-lg transition-all duration-300 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-indigo-300">
                                Go to Link
                            </a>
                        `;

                        // Add event listener for the new Copy button
                        const copyLinkBtn = document.getElementById('copyLinkBtn');
                        if (copyLinkBtn) {
                            copyLinkBtn.addEventListener('click', function() {
                                const linkInput = document.getElementById('generatedLink');
                                if (linkInput) {
                                    linkInput.select();
                                    document.execCommand('copy'); // Use execCommand for broader compatibility in iframes
                                    alert('Link copied to clipboard!');
                                }
                            });
                        }

                    } catch (error) {
                        console.error('Payment error:', error);
                        popupInnerContent.innerHTML = `
                            <h2 class="text-2xl font-bold text-red-600 mb-4">Payment Failed!</h2>
                            <p class="text-gray-600 mb-6">Error: ${error.message}. Please try again.</p>
                            <button onclick="closePopup()" class="px-6 py-3 bg-red-600 text-white font-semibold rounded-lg shadow-md hover:bg-red-700 transition-colors duration-300">
                                Close
                            </button>
                        `;
                    }
                });
            }

        });
    </script>
</body>
</html>
