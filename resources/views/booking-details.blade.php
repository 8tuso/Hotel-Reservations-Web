<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    {{-- Include CSRF token for AJAX requests --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #eef2f6; /* Lighter, subtle blue-gray background, matching homepage */
            min-height: 100vh;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: flex-start; /* Align content to top */
            align-items: center; /* Center content horizontally */
        }
        /* Custom spinner for loading */
        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-left-color: #4f46e5; /* Indigo-600 */
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Styles for the new booking confirmation popup */
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
        }
        .popup-content {
            background-color: #fff;
            padding: 2.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 500px; /* Increased max-width for inputs */
            width: 90%;
            animation: fadeInScale 0.3s ease-out;
            position: relative; /* For the close button */
        }
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        .close-button {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #9ca3af; /* gray-400 */
        }
        .close-button:hover {
            color: #4b5563; /* gray-700 */
        }
        .message-box {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 1rem 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: none; /* Hidden by default */
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        .message-box.show {
            display: block;
            opacity: 1;
        }
        .message-box.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message-box.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body class="overflow-x-hidden">
    <div class="w-full max-w-4xl mx-auto bg-white shadow-lg rounded-xl p-8 space-y-8 my-8 md:my-12">

        <header class="flex justify-between items-center py-4 border-b border-blue-100 mb-6">
            <h1 class="text-3xl font-bold text-gray-800">
                <a href="/" class="hover:text-indigo-600 transition-colors duration-300">Hotel Booking</a>
            </h1>
            <nav>
                <ul class="flex space-x-6">
                    <li><a href="/" class="text-gray-600 hover:text-indigo-600 font-medium transition-colors duration-300">Back to Search</a></li>
                </ul>
            </nav>
        </header>

        <h2 class="text-3xl font-extrabold text-gray-900 text-center mb-6">Hotel Offer Details</h2>

        {{-- Display error message if passed from controller --}}
        @if(isset($error))
            <div id="error-message" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative text-center" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline" id="error-text">{{ $error }}</span>
            </div>
            {{-- Hide the offer details content if there's an error --}}
            <div id="offer-details-content" class="hidden"></div>
        @else
            {{-- This div is for potential JS errors, hidden by default --}}
            <div id="error-message" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative text-center" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline" id="error-text"></span>
            </div>

            {{-- Display offer details if data is present --}}
            @if(isset($offerData) && isset($offerData['hotel']) && isset($offerData['offers'][0]))
                @php
                    $hotel = $offerData['hotel'];
                    $offer = $offerData['offers'][0];
                @endphp

                <div id="offer-details-content" class="space-y-6">

                    <div class="bg-indigo-50 p-6 rounded-lg shadow-inner">
                        <h3 class="text-2xl font-semibold text-gray-800 mb-4 border-b pb-2 border-indigo-200">Hotel Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700">
                            <div class="detail-item"><strong>Name:</strong> <span>{{ $hotel['name'] ?? 'N/A' }}</span></div>
                            <div class="detail-item"><strong>Chain Code:</strong> <span>{{ $hotel['chainCode'] ?? 'N/A' }}</span></div>
                            <div class="detail-item"><strong>Address:</strong> <span>
                                {{ ($hotel['address']['lines'][0] ?? '') . ', ' .
                                   ($hotel['address']['cityName'] ?? $hotel['cityCode']) . ', ' .
                                   ($hotel['address']['stateCode'] ?? '') . ' ' .
                                   ($hotel['address']['postalCode'] ?? '') . ', ' .
                                   ($hotel['address']['countryCode'] ?? 'N/A') }}
                            </span></div>
                            <div class="detail-item"><strong>Contact:</strong> <span>{{ $hotel['contact']['phone'] ?? 'N/A' }}</span></div>
                            <div class="detail-item"><strong>Rating:</strong> <span>{{ str_repeat('⭐', $hotel['rating'] ?? 0) ?: 'N/A' }}</span></div>
                        </div>
                    </div>

                    <div class="bg-indigo-50 p-6 rounded-lg shadow-inner">
                        <h3 class="text-2xl font-semibold text-gray-800 mb-4 border-b pb-2 border-indigo-200">Booking Dates</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700">
                            <div class="detail-item"><strong>Check-in:</strong> <span>{{ $checkInDate ?? 'N/A' }}</span></div>
                            <div class="detail-item"><strong>Check-out:</strong> <span>{{ $checkOutDate ?? 'N/A' }}</span></div>
                            <div class="detail-item"><strong>Number of Nights:</strong> <span>
                                @php
                                    $checkInDateObj = \Carbon\Carbon::parse($checkInDate);
                                    $checkOutDateObj = \Carbon\Carbon::parse($checkOutDate);
                                    $numberOfNights = $checkInDateObj->diffInDays($checkOutDateObj);
                                @endphp
                                {{ $numberOfNights ?? 'N/A' }}
                            </span></div>
                            <div class="detail-item"><strong>Adults:</strong> <span>{{ $adults ?? 'N/A' }}</span></div>
                        </div>
                    </div>

                    <div class="bg-indigo-50 p-6 rounded-lg shadow-inner">
                        <h3 class="text-2xl font-semibold text-gray-800 mb-4 border-b pb-2 border-indigo-200">Room Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700">
                            <div class="detail-item"><strong>Room Type:</strong> <span>{{ ($offer['room']['type'] ?? '') ?: ($offer['room']['typeEstimated']['category'] ?? 'N/A') }}</span></div>
                            <div class="detail-item"><strong>Description:</strong> <span>{{ $offer['room']['description']['text'] ?? 'No description available.' }}</span></div>
                            <div class="detail-item"><strong>Bed Type:</strong> <span>{{ $offer['room']['typeEstimated']['beds'] ?? 'N/A' }}</span></div>
                            <div class="detail-item col-span-full"><strong>Amenities:</strong>
                                <ul id="room-amenities" class="flex flex-wrap gap-2 mt-2">
                                    @forelse ($offer['amenities'] ?? [] as $amenity)
                                        <li class="bg-indigo-200 text-indigo-800 text-sm px-3 py-1 rounded-full">{{ $amenity['description'] ?? '' }}</li>
                                    @empty
                                        <li class="text-gray-500 text-sm">No specific amenities listed.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="bg-indigo-50 p-6 rounded-lg shadow-inner">
                        <h3 class="text-2xl font-semibold text-gray-800 mb-4 border-b pb-2 border-indigo-200">Offer Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700">
                            <div class="detail-item"><strong>Offer ID:</strong> <span>{{ $offer['id'] ?? 'N/A' }}</span></div>
                            <div class="detail-item"><strong>Board Type:</strong> <span>{{ $offer['boardType'] ?? 'N/A' }}</span></div>
                            <div class="detail-item col-span-full"><strong>Cancellation Policy:</strong> <span>
                                @php
                                    $cancellationPolicy = $offer['policies']['cancellation']['description']['text'] ??
                                                          $offer['policies']['cancellation']['type'] ?? 'N/A';
                                    if ($cancellationPolicy === 'N/A' && isset($offer['policies']['cancellation']['amount'])) {
                                        $cancellationPolicy = 'Cancellation fee: ' . ($offer['policies']['cancellation']['amount']['currency'] ?? '') . ' ' . ($offer['policies']['cancellation']['amount']['value'] ?? '');
                                    }
                                @endphp
                                {{ $cancellationPolicy }}
                            </span></div>
                        </div>
                    </div>

                    <div class="bg-indigo-100 p-6 rounded-lg shadow-md text-right">
                        <p class="text-2xl font-bold text-indigo-700">Total Price: <span>{{ ($offer['price']['currency'] ?? '$') . ' ' . number_format($offer['price']['total'] ?? 0, 2) }}</span></p>
                        <button id="proceedToBookingButton" class="mt-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-full shadow-lg
                                     transition-all duration-300 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-indigo-300">
                            Proceed to Booking
                        </button>
                    </div>

                </div>
            @else
                {{-- Fallback message if offerData is not set or incomplete after initial check --}}
                <div class="flex flex-col items-center justify-center py-10">
                    <p class="text-gray-600 text-lg">Failed to load offer details. Please try again from the search page.</p>
                </div>
            @endif
        @endif
    </div>

    {{-- The booking confirmation popup. It will be hidden by default and shown only when needed by JS. --}}
    <div id="bookingConfirmationPopup" class="popup-overlay hidden">
        <div class="popup-content">
            <button class="close-button">&times;</button>
            <h4 class="text-2xl font-semibold text-gray-800 mb-6" id="popup-main-title">How would you like to proceed?</h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="border border-gray-200 rounded-lg p-6 flex flex-col items-center justify-between">
                    <p class="text-lg font-medium text-gray-700 mb-4">Login for a better experience</p>
                    <button id="loginButton" class="w-full bg-gray-800 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded-lg
                                         transition-colors duration-300 focus:outline-none focus:ring-4 focus:ring-gray-300">
                        Login
                    </button>
                    <p class="text-sm text-gray-500 mt-4">New to our website? <button href="/register" class="text-indigo-600 hover:underline" id="regButton">Start your account here</button></p>
                </div>

                <div class="border border-gray-200 rounded-lg p-6 flex flex-col items-center justify-between">
                    <p class="text-lg font-medium text-gray-700 mb-4">Proceed payment as guest</p>
                    <div class="w-full space-y-4 mb-4">
                        <input type="text" id="guestNameInput" placeholder="Your Full Name" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <input type="email" id="guestEmailInput" placeholder="Your Email Address" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <input type="text" id="guestPhoneInput" placeholder="Your Phone number" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <button id="continueAsGuestButton" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg
                                         transition-colors duration-300 focus:outline-none focus:ring-4 focus:ring-indigo-300">
                        Continue
                    </button>
                </div>
            </div>
            <div id="reservationStatusMessage" class="mt-6 text-center text-sm font-medium hidden"></div>
        </div>
        
    </div>

    <input id="auth_check" value="{{auth()->check()}}" hidden></input>

    <div id="messageBox" class="message-box"></div>
    {{-- Login Popup --}}
    <div id="loginPopUp" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-lg p-8 relative">
            <button id="closeLoginPopup" class="absolute top-4 right-4 text-gray-500 hover:text-red-500 text-xl font-bold">
                &times;
            </button>

            <h4 class="text-2xl font-semibold text-gray-800 mb-4">Login</h4>
            <p class="text-gray-600 mb-6">Please enter your credentials to login.</p>

            <form id="loginForm">
                <input type="email" id="loginEmail" name="email" placeholder="Email Address" class="w-full px-4 py-2 mb-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                <input type="password" id="loginPassword" name="password" placeholder="Password" class="w-full px-4 py-2 mb-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500" required>

                <button type="submit" id="loginSubmit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-full transition-colors duration-300">
                    Login
                </button>
            </form>
        </div>
    </div>

    {{-- Registration Popup --}}
    <div id="regPopUp" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-lg p-8 relative">
            <button id="closeRegPopup" class="absolute top-4 right-4 text-gray-500 hover:text-red-500 text-xl font-bold">
                &times;
            </button>

            <h4 class="text-2xl font-semibold text-gray-800 mb-4">Register Account</h4>
            <p class="text-gray-600 mb-6">Please fill in the following information to continue.</p>

            <form id="registrationForm">
                <input type="text" id="regName" name="name" placeholder="Full Name" class="w-full px-4 py-2 mb-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                <input type="email" id="regEmail" name="email" placeholder="Email Address" class="w-full px-4 py-2 mb-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                <input type="tel" id="regPhone" name="phone" placeholder="Phone" class="w-full px-4 py-2 mb-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                <input type="password" id="regPassword" name="password" placeholder="Password" class="w-full px-4 py-2 mb-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus="ring-indigo-500" required>
                <input type="password" id="regPasswordConfirm" name="password_confirmation" placeholder="Confirm Password" class="w-full px-4 py-2 mb-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500" required>

                <button type="button" id="toggleOptionalFields" class="mb-4 text-sm text-indigo-600 hover:underline focus:outline-none">
                    Show Optional Fields
                </button>

                <div id="optionalFields" class="hidden">
                    <input type="date" id="regDateOfBirth" name="date_of_birth" placeholder="Date of birth (optional)" class="w-full px-4 py-2 mb-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="text" id="regGender" name="gender" placeholder="Gender (optional)" class="w-full px-4 py-2 mb-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="text" id="regNationality" name="nationality" placeholder="Nationality (optional)" class="w-full px-4 py-2 mb-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="text" id="regPassport" name="passport_number" placeholder="Passport Number (optional)" class="w-full px-4 py-2 mb-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="text" id="regAddress" name="address" placeholder="Address (optional)" class="w-full px-4 py-2 mb-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="text" id="regCity" name="city" placeholder="City (optional)" class="w-full px-4 py-2 mb-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="text" id="regCountry" name="country" placeholder="Country (optional)" class="w-full px-4 py-2 mb-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <input type="text" id="regPostal" name="postal_code" placeholder="Postal Code (optional)" class="w-full px-4 py-2 mb-6 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <button type="submit" id="regCreate" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-full transition-colors duration-300">
                    Create Account
                </button>
            </form>
        </div>
    </div>


    <script type="module">
        const authCheck= document.getElementById('auth_check').value;
        const offerData = @json($offerData ?? null);
        const checkInDate = "{{ $checkInDate ?? '' }}";
        const checkOutDate = "{{ $checkOutDate ?? '' }}";
        const adults = "{{ $adults ?? '' }}";

        const errorMessage = document.getElementById('error-message');
        const errorText = document.getElementById('error-text');
        const proceedToBookingButton = document.getElementById('proceedToBookingButton');
        const bookingConfirmationPopup = document.getElementById('bookingConfirmationPopup');
        const popupCloseButton = document.querySelector('.popup-content .close-button');
        const continueAsGuestButton = document.getElementById('continueAsGuestButton');
        const loginButton = document.getElementById('loginButton');
        const regButton = document.getElementById('regButton');
        const guestNameInput = document.getElementById('guestNameInput');
        const guestEmailInput = document.getElementById('guestEmailInput');
        const guestPhoneInput = document.getElementById('guestPhoneInput');
        const reservationStatusMessage = document.getElementById('reservationStatusMessage');

        document.addEventListener('DOMContentLoaded', function() {
            const initialErrorElement = document.getElementById('error-text');
            if (initialErrorElement && initialErrorElement.textContent.trim() !== '') {
                errorMessage.classList.remove('hidden');
            }

            if (loginButton) {
                loginButton.addEventListener('click', login);
            }

            if (regButton) {
                regButton.addEventListener('click', regsiterFunction);
            }

            if (proceedToBookingButton) {
                proceedToBookingButton.addEventListener('click', handleProceedToBookingClick);
            }
            if (popupCloseButton) {
                popupCloseButton.addEventListener('click', closeBookingPopup);
            }
            
            if (continueAsGuestButton) {
                continueAsGuestButton.addEventListener('click', handleContinueAsGuestClick);
            }
        });

        async function handleProceedToBookingClick() {
            if (!offerData || !offerData.offers || offerData.offers.length === 0 || !offerData.hotel) {
                reservationStatusMessage.textContent = 'Error: Offer details not loaded or incomplete. Cannot proceed.';
                reservationStatusMessage.className = 'mt-6 text-center text-sm font-medium text-red-600';
                reservationStatusMessage.classList.remove('hidden');
                bookingConfirmationPopup.classList.remove('hidden'); // Show popup with error
                return;
            }

            // Show loading indicator
            proceedToBookingButton.textContent = 'Processing...';
            proceedToBookingButton.disabled = true;
            reservationStatusMessage.textContent = 'Processing your reservation...'; // Display message in popup area
            reservationStatusMessage.className = 'mt-6 text-center text-sm font-medium text-indigo-600';
            reservationStatusMessage.classList.remove('hidden');

            try {
                // First, cache the offer data for the session regardless of user status
                const cacheResponse = await fetch('/cache-proceed-booking', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        offerId: String(offerData.offers[0].id),
                        hotelId: String(offerData.hotel.hotelId),
                        checkInDate: String(checkInDate),
                        checkOutDate: String(checkOutDate),
                        adults: parseInt(adults, 10),
                    })
                });

                if (!cacheResponse.ok) {
                    const errorData = await cacheResponse.json();
                    throw new Error(errorData.error || `HTTP error! status: ${cacheResponse.status} during caching.`);
                }

                // If user is authenticated, attempt direct reservation
                if (authCheck == 1) {
                    await createReservation(); // Call the function to handle authenticated booking
                } else if(cacheResponse.ok)
                {
                    bookingConfirmationPopup.classList.remove('hidden');

                }
                else {
                    // User is not authenticated, show the popup
                    bookingConfirmationPopup.classList.remove('hidden');
                    // Clear status message from the main page area, it will be handled by the popup's internal status
                    reservationStatusMessage.textContent = '';
                    reservationStatusMessage.classList.add('hidden');
                }

            } catch (error) {
                console.error('Error during booking process:', error);
                // Display error directly in the popup's status area or main error message
                reservationStatusMessage.textContent = `Error: ${error.message}. Please try again.`;
                reservationStatusMessage.className = 'mt-6 text-center text-sm font-medium text-red-600';
                reservationStatusMessage.classList.remove('hidden');
                bookingConfirmationPopup.classList.remove('hidden'); // Ensure popup is shown on error
            } finally {
                proceedToBookingButton.textContent = 'Proceed to Booking';
                proceedToBookingButton.disabled = false;
            }
        }

        /**
         * Handles direct reservation for authenticated users.
         * @param {string} userId - The authenticated user's ID.
         */
        async function createReservation() {
            try {
                const response = await fetch('/create-reservation', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        // No guest details needed here as it's an authenticated user
                        // The backend should use the `Auth::id()` or current user from session
                    })
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                reservationStatusMessage.textContent = data.message || 'Reservation successful! Redirecting to payment...';
                reservationStatusMessage.className = 'mt-6 text-center text-sm font-medium text-green-600';
                reservationStatusMessage.classList.remove('hidden');

                // Redirect to payment page
                window.location.href = `{{ route('payment.page') }}?customerId=${encodeURIComponent(data.customerId)}`;

            } catch (error) {
                console.error('Error creating authenticated reservation:', error);
                reservationStatusMessage.textContent = `Reservation failed: ${error.message}.`;
                reservationStatusMessage.className = 'mt-6 text-center text-sm font-medium text-red-600';
                reservationStatusMessage.classList.remove('hidden');
                // If an error occurs during direct booking, ensure the popup is shown to display the error
                bookingConfirmationPopup.classList.remove('hidden');
            }
        }

        function closeBookingPopup() {
            bookingConfirmationPopup.classList.add('hidden');
            reservationStatusMessage.classList.add('hidden');
        }

        const loginPopUp = document.getElementById('loginPopUp');
        const closeLoginPopup = document.getElementById('closeLoginPopup');
        const loginForm = document.getElementById('loginForm');
        const loginSubmit = document.getElementById('loginSubmit');

        function login()
        {
            loginPopUp.classList.remove('hidden');
            bookingConfirmationPopup.classList.add('hidden');
        }

        
        closeLoginPopup.addEventListener('click', () => {
            loginPopUp.classList.add('hidden');
            // Clear any validation errors when closing
            document.querySelectorAll('.error-msg').forEach(el => el.remove());
        });

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Clear previous errors
            document.querySelectorAll('.error-msg').forEach(el => el.remove());

            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;

            try {
                const response = await fetch('/user-login', { // Use your route name: user-login
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                if (response.ok) { // Status 200 OK
                    showMessage(data.message, 'success');
                    loginForm.reset(); // Clear the form
                    loginPopUp.classList.add('hidden'); // Hide the popup
                    window.location.reload(); // Reload to show dashboard link
                } else { // Status 401 or 422
                    // Prioritize specific error message if available
                    const errorMessage = data.error || data.message || 'Login failed';
                    showMessage(errorMessage, 'error');
                    if (data.errors) { // For validation errors (e.g., if you add custom login validation)
                        showValidationErrors(data.errors);
                    }
                }
            } catch (error) {
                console.error('Login Error:', error);
                showMessage('An unexpected error occurred during login.', 'error');
            }
        });

        const regPopUp = document.getElementById('regPopUp');
        const closeRegPopup = document.getElementById('closeRegPopup');
        const registrationForm = document.getElementById('registrationForm');
        const toggleOptionalFieldsButton = document.getElementById('toggleOptionalFields');
        const optionalFieldsDiv = document.getElementById('optionalFields');

        closeRegPopup.addEventListener('click', () => {
            regPopUp.classList.add('hidden');
            // Clear any validation errors when closing
            document.querySelectorAll('.error-msg').forEach(el => el.remove());
        });

        function regsiterFunction()
        {
            regPopUp.classList.remove('hidden');
            bookingConfirmationPopup.classList.add('hidden');
        }

        toggleOptionalFieldsButton.addEventListener('click', () => {
            optionalFieldsDiv.classList.toggle('hidden');
            if (optionalFieldsDiv.classList.contains('hidden')) {
                toggleOptionalFieldsButton.textContent = 'Show Optional Fields';
            } else {
                toggleOptionalFieldsButton.textContent = 'Hide Optional Fields';
            }
        });

        registrationForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Clear previous errors
            document.querySelectorAll('.error-msg').forEach(el => el.remove());

            const formData = new FormData(registrationForm);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('/register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (response.ok) {
                    showMessage(result.message, 'success');
                    registrationForm.reset(); // Clear the form
                    regPopUp.classList.add('hidden'); // Hide the popup
                    // Optionally, redirect or reload
                    window.location.reload(); // Reload to show dashboard link
                } else if (response.status === 422) { // Validation errors
                    // Prioritize specific error message if available, otherwise fallback to generic
                    const errorMessage = result.error || 'Please correct the errors below.';
                    showMessage(errorMessage, 'error');
                    showValidationErrors(result.errors);
                } else {
                    // Prioritize specific error message if available, otherwise fallback to generic
                    const errorMessage = result.error || result.message || 'Registration failed.';
                    showMessage(errorMessage, 'error');
                }
            } catch (error) {
                console.error('Registration Error:', error);
                showMessage('An unexpected error occurred during registration.', 'error');
            }
        });
        async function handleContinueAsGuestClick() {
            const guestName = guestNameInput.value.trim();
            const guestEmail = guestEmailInput.value.trim();
            const guestPhone = guestPhoneInput.value.trim();

            if (!guestName || !guestEmail) {
                reservationStatusMessage.textContent = 'Please enter your full name and email address.';
                reservationStatusMessage.className = 'mt-6 text-center text-sm font-medium text-red-600';
                reservationStatusMessage.classList.remove('hidden');
                return;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(guestEmail)) {
                reservationStatusMessage.textContent = 'Please enter a valid email address.';
                reservationStatusMessage.className = 'mt-6 text-center text-sm font-medium text-red-600';
                reservationStatusMessage.classList.remove('hidden');
                return;
            }

            continueAsGuestButton.textContent = 'Booking...';
            continueAsGuestButton.disabled = true;
            reservationStatusMessage.textContent = 'Processing your reservation...';
            reservationStatusMessage.className = 'mt-6 text-center text-sm font-medium text-indigo-600';
            reservationStatusMessage.classList.remove('hidden');

            try {
                const response = await fetch('/create-reservation', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        guestName: guestName,
                        guestEmail: guestEmail,
                        guestPhone: guestPhone,
                    })
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                window.location.href = `{{ route('payment.page') }}?customerId=${encodeURIComponent(data.customerId)}`;

            } catch (error) {
                console.error('Error creating guest reservation:', error);
                reservationStatusMessage.textContent = `Reservation failed: ${error.message}.`;
                reservationStatusMessage.className = 'mt-6 text-center text-sm font-medium text-red-600';
            } finally {
                continueAsGuestButton.textContent = 'Continue';
                continueAsGuestButton.disabled = false;
            }
        }

        function showMessage(message, type) {
        const messageBox = document.getElementById('messageBox');
        messageBox.textContent = message;
        messageBox.className = `message-box show ${type}`; // Add 'show' and type class
        setTimeout(() => {
            messageBox.classList.remove('show'); // Hide after 3 seconds
        }, 3000);
    }

    // Helper function to display validation errors under fields (unrelated)
    function showValidationErrors(errors) {
        // Remove old errors
        document.querySelectorAll('.error-msg').forEach(el => el.remove());

        Object.keys(errors).forEach(field => {
            // The name attribute is used to match with Laravel's validation error keys
            const input = document.querySelector(`[name="${field}"]`);
            if (input) {
                const error = document.createElement('div');
                error.className = 'error-msg text-sm text-red-600 mt-1';
                error.innerText = errors[field][0]; // Display the first error message for the field
                input.parentNode.insertBefore(error, input.nextSibling); // Insert error after the input
            }
        });
    }
    </script>
</body>
</html>