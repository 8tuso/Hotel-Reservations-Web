<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Reservation</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.9.1/gsap.min.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #eef2f6; /* Lighter, subtle blue-gray background for the entire page */
            min-height: 100vh; /* Ensure it takes full viewport height */
            margin: 0;
            padding: 0; /* Remove body padding to allow content to stretch */
            box-sizing: border-box; /* Include padding in element's total width and height */
            display: flex; /* Use flexbox for full height layout */
            flex-direction: column; /* Stack content vertically */
            justify-content: space-between; /* Distribute space between main content and footer */
        }
        /* Custom styles for the main search loading spinner */
        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-left-color: #4f46e5; /* Indigo-600 spinner to match new palette */
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        /* Custom styles for the autocomplete loading spinner */
        .autocomplete-spinner {
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-left-color: #6366f1; /* Lighter indigo */
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            vertical-align: middle;
            margin-left: 8px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Styles for the geolocation permission popup */
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
            max-width: 400px;
            animation: fadeInScale 0.3s ease-out;
        }
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
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
    <div class="flex-grow w-full max-w-6xl mx-auto bg-white shadow-lg rounded-xl p-8 space-y-8 my-8 md:my-12">

        <header class="flex justify-between items-center py-4 border-b border-blue-100">
            <h1 class="text-3xl font-bold text-gray-800">
                <a href="/" class="hover:text-indigo-600 transition-colors duration-300">Hotel Booking</a>
            </h1>
            <nav>
                <ul class="flex space-x-6">
                    @auth
                        <li><a href="{{ route('dashboard') }}" class="text-gray-600 hover:text-indigo-600 font-medium transition-colors duration-300">Dashboard</a></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-gray-600 hover:text-indigo-600 font-medium transition-colors duration-300">Logout</button>
                            </form>
                        </li>
                    @else
                        <li><a href="#" id="loginLink" class="text-gray-600 hover:text-indigo-600 font-medium transition-colors duration-300">Login</a></li>
                        <li><a href="#" id="registerLink" class="text-gray-600 hover:text-indigo-600 font-medium transition-colors duration-300">Register</a></li>
                    @endauth
                </ul>
            </nav>
        </header>

        <section class="text-center py-10">
            <h2 class="text-4xl font-extrabold text-gray-900 mb-4 animate-fade-in-up">Find Your Perfect Stay</h2>
            <p class="text-lg text-gray-600 animate-fade-in-up delay-100">Search for hotels and rooms that fit your needs.</p>
        </section>

        <section class="bg-indigo-50 p-6 rounded-lg shadow-inner">
            <h3 class="text-2xl font-semibold text-gray-800 mb-6 text-center">Search for Hotels</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="relative">
                    <label for="cityName" class="block text-gray-700 text-sm font-bold mb-2">
                        City Name or Code
                        <span id="citySuggestionsLoading" class="autocomplete-spinner hidden"></span>
                    </label>
                    <input type="text" id="cityName" placeholder="e.g., London, NYC, PAR" required
                           class="shadow appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-400 transition-all duration-300">
                    <div id="citySuggestions" class="absolute z-10 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 max-h-60 overflow-y-auto hidden">
                    </div>
                </div>
                <div>
                    <label for="roomType" class="block text-gray-700 text-sm font-bold mb-2">Room Type (Optional)</label>
                    <input type="text" id="roomType" placeholder="e.g., Standard, Deluxe, Suite"
                           class="shadow appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-400 transition-all duration-300">
                </div>
                <div>
                    <label for="checkInDate" class="block text-gray-700 text-sm font-bold mb-2">Check-in Date</label>
                    <input type="date" id="checkInDate" required
                           class="shadow appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-400 transition-all duration-300">
                </div>
                <div>
                    <label for="checkOutDate" class="block text-gray-700 text-sm font-bold mb-2">Check-out Date</label>
                    <input type="date" id="checkOutDate" required
                           class="shadow appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-400 transition-all duration-300">
                </div>
                <div>
                    <label for="adults" class="block text-gray-700 text-sm font-bold mb-2">Adults per Room</label>
                    <input type="number" id="adults" value="1" min="1" required
                           class="shadow appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-400 transition-all duration-300">
                </div>
                <div>
                    <label for="roomQuantity" class="block text-gray-700 text-sm font-bold mb-2">Number of Rooms</label>
                    <input type="number" id="roomQuantity" value="1" min="1" required
                           class="shadow appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-400 transition-all duration-300">
                </div>
            </div>
            <div class="mt-8 text-center">
                <button id="searchButton"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-full shadow-lg
                               transition-all duration-300 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-indigo-300">
                    Search Hotels
                </button>
            </div>
        </section>

        <div id="loadingIndicator" class="hidden flex flex-col items-center justify-center py-10">
            <div class="spinner mb-4"></div>
            <p class="text-gray-600 text-lg">Searching for the best deals...</p>
        </div>

        <section id="searchResults" class="hidden py-8">
            <h3 class="text-2xl font-semibold text-gray-800 mb-6 text-center">Available Rooms</h3>
            <div id="resultsList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            </div>
            <p id="noResultsMessage" class="hidden text-center text-gray-500 text-lg mt-8">No results found for your search criteria.</p>
        </section>

        <section id="recommendationsSection" class="hidden py-8">
            <h3 class="text-2xl font-semibold text-gray-800 mb-6 text-center">Recommended for You</h3>
            <div id="recommendationsLoading" class="flex flex-col items-center justify-center py-5 hidden">
                <div class="spinner mb-2"></div>
                <p class="text-gray-600 text-md">Loading recommendations...</p>
            </div>
            <div id="recommendationsList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            </div>
            <p id="noRecommendationsMessage" class="hidden text-center text-gray-500 text-lg mt-8">No recommendations available. Please enable location services or perform a search!</p>
        </section>

        <footer class="text-center py-6 border-t border-gray-200 mt-8 text-gray-500 text-sm">
            <p>&copy; 2024 Hotel Booking. All rights reserved.</p>
            <div class="flex justify-center space-x-4 mt-2">
                <a href="#" class="hover:text-indigo-600 transition-colors duration-300">Privacy Policy</a>
                <a href="#" class="hover:text-indigo-600 transition-colors duration-300">Terms of Service</a>
                <a href="#" class="hover:text-indigo-600 transition-colors duration-300">Contact Us</a>
            </div>
        </footer>

    </div>

    <div id="geolocationPopup" class="popup-overlay hidden">
        <div class="popup-content">
            <h4 class="text-xl font-semibold text-gray-800 mb-4">Enable Location Services</h4>
            <p class="text-gray-600 mb-6">To get personalized hotel recommendations near you, please allow this website to access your location.</p>
            <button id="allowLocationButton" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-full transition-colors duration-300 mr-2">
                Allow Location
            </button>
            <button id="denyLocationButton" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded-full transition-colors duration-300">
                No Thanks
            </button>
        </div>
    </div>

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
        // Helper function to show messages
        function showMessage(message, type) {
            const messageBox = document.getElementById('messageBox');
            messageBox.textContent = message;
            messageBox.className = `message-box show ${type}`; // Add 'show' and type class
            setTimeout(() => {
                messageBox.classList.remove('show'); // Hide after 3 seconds
            }, 3000);
        }

        // Helper function to display validation errors under fields
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


    

        // Get references to DOM elements
        const searchButton = document.getElementById('searchButton');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const searchResultsSection = document.getElementById('searchResults');
        const resultsList = document.getElementById('resultsList');

        // Updated input references
        const cityNameInput = document.getElementById('cityName');
        const roomTypeInput = document.getElementById('roomType');
        const checkInDateInput = document.getElementById('checkInDate');
        const checkOutDateInput = document.getElementById('checkOutDate');
        const adultsInput = document.getElementById('adults');
        const roomQuantityInput = document.getElementById('roomQuantity');

        const noResultsMessage = document.getElementById('noResultsMessage');

        // Autocomplete elements
        const citySuggestionsDiv = document.getElementById('citySuggestions');
        const citySuggestionsLoading = document.getElementById('citySuggestionsLoading');

        // Recommendation elements
        const recommendationsSection = document.getElementById('recommendationsSection');
        const recommendationsList = document.getElementById('recommendationsList');
        const recommendationsLoading = document.getElementById('recommendationsLoading');
        const noRecommendationsMessage = document.getElementById('noRecommendationsMessage');

        // Geolocation Popup elements
        const geolocationPopup = document.getElementById('geolocationPopup');
        const allowLocationButton = document.getElementById('allowLocationButton');
        const denyLocationButton = document.getElementById('denyLocationButton');


        // Variable to store the selected IATA code from autocomplete
        let selectedIataCode = null;
        // Flag to track if suggestions were shown (for validation)
        let suggestionsWereShown = false;
        // New flag to prevent blur from re-triggering autocomplete after a selection
        let isProgrammaticChange = false;

        /**
         * Fetches search results from the Laravel backend API.
         * @param {object} params - Object containing search parameters.
         * @returns {Promise<Array>} A promise that resolves with filtered hotel rooms or an empty array on error.
         */
        async function fetchSearchResults(params) {
            console.groupCollapsed("Search API Call Debug");
            console.log("Attempting to fetch search results...");
            console.log("currentUserId at search initiation:", currentUserId);

            const queryParams = new URLSearchParams();
            // Append all parameters
            for (const key in params) {
                if (params[key]) { // Only append if value exists
                    queryParams.append(key, params[key]);
                }
            }
            // Add userId to search parameters (for potential future backend logging/features)
            if (currentUserId) {
                queryParams.append('userId', currentUserId);
            } else {
                console.error("Search aborted: currentUserId is null. User authentication not ready.");
                showMessage('User authentication is not ready. Please wait a moment and try again.', 'error');
                loadingIndicator.classList.add('hidden'); // Hide loading if validation fails
                console.groupEnd();
                return [];
            }

            const apiUrl = `/api/hotel-search?${queryParams.toString()}`;
            console.log("Search API URL:", apiUrl);
            console.log("Search Query Parameters:", params);

            try {
                const response = await fetch(apiUrl);
                console.log("Search API Raw Response:", response);
                if (!response.ok) {
                    const errorData = await response.json();
                    console.error('Search API Error Status:', response.status);
                    console.error('Search API Error Data:', errorData);
                    showMessage('Error searching for hotels: ' + (errorData.error || response.statusText), 'error');
                    console.groupEnd();
                    return [];
                }
                const data = await response.json();
                console.log("Search API Response Data (parsed JSON):", data);
                console.groupEnd();
                return data;
            } catch (error) {
                console.error('Network or Fetch Error:', error);
                showMessage('A network error occurred. Please check your connection.', 'error');
                console.groupEnd();
                return [];
            }
        }

        /**
         * Renders the search results into the DOM.
         * @param {Array} results - An array of hotel room objects.
         */
        function renderResults(results) {
            console.log("Rendering search results. Number of results:", results.length);
            resultsList.innerHTML = ''; // Clear previous results
            if (results.length === 0) {
                noResultsMessage.classList.remove('hidden');
                noResultsMessage.classList.add('block');
                console.log("No search results found.");
            } else {
                noResultsMessage.classList.add('hidden');
                noResultsMessage.classList.remove('block');
                results.forEach((room, index) => {
                    console.log(`Rendering search result room ${index + 1}:`, room); // Log the room object
                    const roomCard = `
                        <div class="bg-white rounded-lg shadow-md overflow-hidden transform hover:scale-105 transition-transform duration-300 result-card">
                            <img src="${room.imageUrl}" alt="${room.type}" class="w-full h-48 object-cover">
                            <div class="p-4">
                                <h4 class="text-xl font-semibold text-gray-800 mb-2">${room.type} at ${room.hotel}</h4>
                                <p class="text-indigo-600 font-bold text-lg mb-2">${room.price}</p>
                                <p class="text-gray-600 text-sm mb-4">${room.amenities}</p>
                                <button class="bg-indigo-500 hover:bg-indigo-600 text-white font-bold py-2 px-4 rounded-lg w-full transition-colors duration-300 view-details-btn"
                                        data-hotel-id="${room.hotelId ?? ''}"
                                        data-offer-id="${room.id ?? ''}"
                                        data-check-in="${room.checkInDate ?? ''}"
                                        data-check-out="${room.checkOutDate ?? ''}"
                                        data-adults="${adultsInput.value}"
                                        data-room-quantity="${roomQuantityInput.value}"
                                        data-display-price="${room.price}"
                                        data-hotel-name="${room.hotel}"
                                        data-room-type="${room.type}">
                                    View Details
                                </button>
                            </div>
                        </div>
                    `;
                    resultsList.insertAdjacentHTML('beforeend', roomCard);
                });

                // Add event listeners to the new "View Details" buttons
                document.querySelectorAll('.view-details-btn').forEach(button => {
                    button.addEventListener('click', async (event) => {
                        const hotelId = event.target.dataset.hotelId;
                        const offerId = event.target.dataset.offerId;
                        const checkInDate = event.target.dataset.checkIn;
                        const checkOutDate = event.target.dataset.checkOut;
                        const adults = event.target.dataset.adults;
                        const roomQuantity = event.target.dataset.roomQuantity;
                        const displayPrice = event.target.dataset.displayPrice;
                        const hotelName = event.target.dataset.hotelName;
                        const roomType = event.target.dataset.roomType;

                        console.log("View Details clicked:", { hotelId, offerId, checkInDate, checkOutDate, adults, roomQuantity, displayPrice, hotelName, roomType });

                        if (!currentUserId) {
                            showMessage('Please log in or wait for authentication to view offer details.', 'error');
                            return;
                        }

                        // Store offer view data in backend for "Viewed Offers" dashboard section
                        try {
                            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                            const response = await fetch('/api/store-viewed-offer', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken
                                },
                                body: JSON.stringify({
                                    userId: currentUserId,
                                    hotelId: hotelId,
                                    offerId: offerId,
                                    checkInDate: checkInDate,
                                    checkOutDate: checkOutDate,
                                    adults: adults,
                                    roomQuantity: roomQuantity,
                                    displayPrice: displayPrice,
                                    hotelName: hotelName,
                                    roomType: roomType
                                })
                            });

                            if (!response.ok) {
                                const errorData = await response.json();
                                console.error('Failed to store viewed offer:', errorData);
                                showMessage('Failed to log viewed offer: ' + (errorData.message || 'Unknown error'), 'error');
                            } else {
                                console.log('Viewed offer stored successfully.');
                                // Redirect to a booking details page or similar
                                // For now, just show a success message
                                showMessage('Offer details ready!', 'success');
                                // Example: You might redirect to a specific booking details page
                                // window.location.href = `/booking/details?offerId=${offerId}&hotelId=${hotelId}`;
                            }
                        } catch (error) {
                            console.error('Network error storing viewed offer:', error);
                            showMessage('Network error while logging offer view.', 'error');
                        }
                    });
                });
            }
            searchResultsSection.classList.remove('hidden');
        }

        /**
         * Fetches city suggestions for autocomplete.
         * @param {string} query - The search query for cities.
         * @returns {Promise<Array>} A promise that resolves with city suggestions.
         */
        async function fetchCitySuggestions(query) {
            console.log("Fetching city suggestions for:", query);
            if (!query) {
                return [];
            }
            citySuggestionsLoading.classList.remove('hidden');
            try {
                // This is the correct route for city suggestions based on previous conversations.
                const response = await fetch(`/api/cities?query=${query}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();
                console.log("City suggestions received:", data);
                return data;
            } catch (error) {
                console.error("Error fetching city suggestions:", error);
                return [];
            } finally {
                citySuggestionsLoading.classList.add('hidden');
            }
        }

        /**
         * Renders city suggestions in the autocomplete dropdown.
         * @param {Array} suggestions - An array of city suggestion objects.
         */
        function renderCitySuggestions(suggestions) {
            citySuggestionsDiv.innerHTML = '';
            if (suggestions.length > 0) {
                suggestionsWereShown = true;
                suggestions.forEach(city => {
                    const div = document.createElement('div');
                    div.classList.add('px-4', 'py-2', 'cursor-pointer', 'hover:bg-indigo-100', 'text-gray-800');
                    // Display name for the user, store IATA code
                    div.textContent = `${city.name} (${city.iataCode})`;
                    div.dataset.iata = city.iataCode;
                    div.dataset.cityName = city.name;
                    div.addEventListener('click', () => {
                        isProgrammaticChange = true; // Set flag
                        cityNameInput.value = city.name; // Display full city name
                        selectedIataCode = city.iataCode; // Store IATA code
                        citySuggestionsDiv.classList.add('hidden');
                        suggestionsWereShown = false; // Reset flag after selection
                        setTimeout(() => { isProgrammaticChange = false; }, 50); // Reset after a short delay
                    });
                    citySuggestionsDiv.appendChild(div);
                });
                citySuggestionsDiv.classList.remove('hidden');
            } else {
                citySuggestionsDiv.classList.add('hidden');
                suggestionsWereShown = false;
            }
        }

        // Autocomplete event listener
        let autocompleteTimeout;
        cityNameInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            if (autocompleteTimeout) {
                clearTimeout(autocompleteTimeout);
            }
            if (query.length >= 2) { // Fetch suggestions after 2 characters
                autocompleteTimeout = setTimeout(async () => {
                    selectedIataCode = null; // Clear previously selected IATA code on new input
                    const suggestions = await fetchCitySuggestions(query);
                    renderCitySuggestions(suggestions);
                }, 300); // Debounce
            } else {
                citySuggestionsDiv.classList.add('hidden');
                suggestionsWereShown = false;
                selectedIataCode = null; // Ensure IATA is cleared if input is too short
            }
        });

        cityNameInput.addEventListener('blur', () => {
            if (!isProgrammaticChange) { // Only hide if not a programmatic change from clicking a suggestion
                // Give a small delay to allow click event on suggestion to fire
                setTimeout(() => {
                    if (!citySuggestionsDiv.contains(document.activeElement)) { // Check if focus isn't within suggestions
                        citySuggestionsDiv.classList.add('hidden');
                    }
                }, 100);
            }
        });

        // Ensure suggestions close if the input is clicked again after blur
        cityNameInput.addEventListener('focus', () => {
            if (citySuggestionsDiv.innerHTML !== '' && cityNameInput.value.trim().length >= 2) {
                citySuggestionsDiv.classList.remove('hidden');
            }
        });


        // Handle search button click
        searchButton.addEventListener('click', async () => {
            // Basic validation
            const cityName = cityNameInput.value.trim();
            const checkInDate = checkInDateInput.value;
            const checkOutDate = checkOutDateInput.value;
            const adults = adultsInput.value;
            const roomQuantity = roomQuantityInput.value;

            if (!cityName || !selectedIataCode) {
                showMessage('Please enter a valid city and select from suggestions.', 'error');
                return;
            }
            if (!checkInDate) {
                showMessage('Please select a check-in date.', 'error');
                return;
            }
            if (!checkOutDate) {
                showMessage('Please select a check-out date.', 'error');
                return;
            }
            if (new Date(checkInDate) >= new Date(checkOutDate)) {
                showMessage('Check-out date must be after check-in date.', 'error');
                return;
            }
            if (new Date() > new Date(checkInDate)) {
                showMessage('Check-in date cannot be in the past.', 'error');
                return;
            }
            if (parseInt(adults) < 1) {
                showMessage('At least one adult is required per room.', 'error');
                return;
            }
            if (parseInt(roomQuantity) < 1) {
                showMessage('At least one room is required.', 'error');
                return;
            }

            // Hide recommendations when performing a search
            recommendationsSection.classList.add('hidden');
            noRecommendationsMessage.classList.add('hidden');
            recommendationsList.innerHTML = '';


            loadingIndicator.classList.remove('hidden');
            searchResultsSection.classList.add('hidden');
            resultsList.innerHTML = ''; // Clear previous results
            noResultsMessage.classList.add('hidden');

            const searchParams = {
                cityCode: selectedIataCode, // Use the IATA code for the search API
                roomType: roomTypeInput.value.trim(),
                checkInDate: checkInDate,
                checkOutDate: checkOutDate,
                adults: adults,
                roomQuantity: roomQuantity
            };

            const results = await fetchSearchResults(searchParams);
            renderResults(results);

            loadingIndicator.classList.add('hidden');
        });


        // Geolocation and Recommendations Logic
        let userLatitude = null;
        let userLongitude = null;
        let geolocationPermissionStatus = localStorage.getItem('geolocationPermissionStatus'); // 'granted', 'denied', 'prompt'

        // Function to fetch recommendations based on user location (REVERTED TO PREVIOUS LOGIC)
        async function fetchRecommendations(latitude, longitude) {
            console.groupCollapsed("Recommendations API Call Debug (REVERTED)");
            console.log("Attempting to fetch recommendations...");
            console.log("currentUserId at recommendations initiation:", currentUserId);

            recommendationsLoading.classList.remove('hidden');
            recommendationsList.innerHTML = '';
            noRecommendationsMessage.classList.add('hidden');
            recommendationsSection.classList.remove('hidden'); // Show the section header

            if (!currentUserId) {
                console.warn("Recommendations skipped: currentUserId is null. User authentication not ready.");
                recommendationsLoading.classList.add('hidden');
                noRecommendationsMessage.textContent = 'User authentication not ready. Please wait a moment.';
                noRecommendationsMessage.classList.remove('hidden');
                console.groupEnd();
                return [];
            }

            // This is the route you used previously for recommendations
            const apiUrl = `/api/recommendations?latitude=${latitude}&longitude=${longitude}&userId=${currentUserId}`;
            console.log("Recommendations API URL:", apiUrl);

            try {
                const response = await fetch(apiUrl);
                console.log("Recommendations API Raw Response:", response);
                if (!response.ok) {
                    const errorData = await response.json();
                    console.error('Recommendations API Error Status:', response.status);
                    console.error('Recommendations API Error Data:', errorData);
                    showMessage('Error fetching recommendations: ' + (errorData.error || response.statusText), 'error');
                    console.groupEnd();
                    return [];
                }
                const data = await response.json();
                console.log("Recommendations API Response Data (parsed JSON):", data);
                console.groupEnd();
                return data;
            } catch (error) {
                console.error('Network or Fetch Error for recommendations:', error);
                showMessage('A network error occurred while fetching recommendations. Please check your connection.', 'error');
                console.groupEnd();
                return [];
            } finally {
                recommendationsLoading.classList.add('hidden');
            }
        }

        // Function to render recommendations (REVERTED TO PREVIOUS LOGIC)
        function renderRecommendations(recommendations) {
            console.log("Rendering recommendations. Number of recommendations:", recommendations.length);
            recommendationsList.innerHTML = ''; // Clear previous recommendations
            if (recommendations.length === 0) {
                noRecommendationsMessage.textContent = 'No recommendations available for your current location.';
                noRecommendationsMessage.classList.remove('hidden');
            } else {
                noRecommendationsMessage.classList.add('hidden');
                recommendations.forEach((offer) => {
                    // Assuming the structure of 'offer' object from your previous recommendation API output
                    const offerCard = `
                        <div class="bg-white rounded-lg shadow-md overflow-hidden transform hover:scale-105 transition-transform duration-300">
                            <img src="${offer.image_url || 'https://via.placeholder.com/300x200?text=Hotel'}" alt="${offer.hotel_name || 'Hotel Offer'}" class="w-full h-48 object-cover">
                            <div class="p-4">
                                <h4 class="text-xl font-semibold text-gray-800 mb-2">${offer.hotel_name || 'Unknown Hotel'}</h4>
                                <p class="text-gray-600 text-sm mb-2">${offer.description_text || 'No description available.'}</p>
                                <p class="text-indigo-600 font-bold text-lg mb-2">${offer.display_currency || '$'}${offer.display_price || '0.00'}</p>
                                <a href="${offer.link}" target="_blank" class="bg-indigo-500 hover:bg-indigo-600 text-white font-bold py-2 px-4 rounded-lg w-full text-center inline-block transition-colors duration-300">
                                    View Offer
                                </a>
                            </div>
                        </div>
                    `;
                    recommendationsList.insertAdjacentHTML('beforeend', offerCard);
                });
            }
            recommendationsSection.classList.remove('hidden');
        }

        // Function to request geolocation permission
        function requestGeolocationPermission() {
            console.log("Requesting geolocation permission...");
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    async (position) => {
                        userLatitude = position.coords.latitude;
                        userLongitude = position.coords.longitude;
                        localStorage.setItem('geolocationPermissionStatus', 'granted');
                        geolocationPopup.classList.add('hidden');
                        console.log("Geolocation granted. Lat:", userLatitude, "Lon:", userLongitude);
                        if (isFirebaseReady && currentUserId) {
                             // Only fetch if Firebase is ready and user is authenticated
                            const recommendations = await fetchRecommendations(userLatitude, userLongitude);
                            renderRecommendations(recommendations);
                        } else {
                            console.log("Firebase not ready or user not authenticated, will fetch recommendations once ready.");
                        }
                    },
                    (error) => {
                        console.error("Geolocation error:", error);
                        localStorage.setItem('geolocationPermissionStatus', 'denied');
                        geolocationPopup.classList.add('hidden');
                        noRecommendationsMessage.textContent = 'Geolocation denied. Cannot provide local recommendations.';
                        noRecommendationsMessage.classList.remove('hidden');
                        recommendationsSection.classList.remove('hidden'); // Show section with denial message
                        showMessage('Geolocation access denied. Cannot provide personalized recommendations.', 'error');
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            } else {
                console.warn("Geolocation is not supported by this browser.");
                localStorage.setItem('geolocationPermissionStatus', 'denied');
                geolocationPopup.classList.add('hidden');
                noRecommendationsMessage.textContent = 'Geolocation is not supported by your browser.';
                noRecommendationsMessage.classList.remove('hidden');
                recommendationsSection.classList.remove('hidden'); // Show section with message
                showMessage('Geolocation is not supported by your browser.', 'error');
            }
        }

        // Function to check and possibly request/fetch recommendations
        async function checkAndFetchRecommendations() {
            if (geolocationPermissionStatus === 'granted') {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        async (position) => {
                            userLatitude = position.coords.latitude;
                            userLongitude = position.coords.longitude;
                            console.log("Using stored geolocation. Lat:", userLatitude, "Lon:", userLongitude);
                            if (isFirebaseReady && currentUserId) {
                                const recommendations = await fetchRecommendations(userLatitude, userLongitude);
                                renderRecommendations(recommendations);
                            } else {
                                console.log("Firebase not ready or user not authenticated, will fetch recommendations once ready.");
                            }
                        },
                        (error) => {
                            console.error("Geolocation error (on page load, after granted):", error);
                            // If user had granted but now denies/error, show prompt again.
                            localStorage.setItem('geolocationPermissionStatus', 'prompt');
                            geolocationPopup.classList.remove('hidden');
                            recommendationsSection.classList.add('hidden'); // Hide recommendations section until decision
                        },
                        { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 } // Shorter timeout for initial load
                    );
                }
            } else if (geolocationPermissionStatus === 'prompt' || geolocationPermissionStatus === null) {
                geolocationPopup.classList.remove('hidden');
                recommendationsSection.classList.add('hidden'); // Hide recommendations section until decision
            } else if (geolocationPermissionStatus === 'denied') {
                noRecommendationsMessage.textContent = 'Geolocation access previously denied. Enable it in your browser settings to see recommendations.';
                noRecommendationsMessage.classList.remove('hidden');
                recommendationsSection.classList.remove('hidden');
            }
        }


        // Event listeners for geolocation popup buttons
        allowLocationButton.addEventListener('click', () => {
            requestGeolocationPermission();
        });

        denyLocationButton.addEventListener('click', () => {
            localStorage.setItem('geolocationPermissionStatus', 'denied');
            geolocationPopup.classList.add('hidden');
            noRecommendationsMessage.textContent = 'Geolocation access denied. Cannot provide local recommendations.';
            noRecommendationsMessage.classList.remove('hidden');
            recommendationsSection.classList.remove('hidden'); // Show section with denial message
            showMessage('Geolocation access denied. Cannot provide personalized recommendations.', 'info');
        });


        // Handle registration popup
        const registerLink = document.getElementById('registerLink');
        const regPopUp = document.getElementById('regPopUp');
        const closeRegPopup = document.getElementById('closeRegPopup');
        const registrationForm = document.getElementById('registrationForm');
        const toggleOptionalFieldsButton = document.getElementById('toggleOptionalFields');
        const optionalFieldsDiv = document.getElementById('optionalFields');

        registerLink.addEventListener('click', (e) => {
            e.preventDefault();
            regPopUp.classList.remove('hidden');
        });

        closeRegPopup.addEventListener('click', () => {
            regPopUp.classList.add('hidden');
            // Clear any validation errors when closing
            document.querySelectorAll('.error-msg').forEach(el => el.remove());
        });

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


        // Handle Login popup
        const loginLink = document.getElementById('loginLink');
        const loginPopUp = document.getElementById('loginPopUp');
        const closeLoginPopup = document.getElementById('closeLoginPopup');
        const loginForm = document.getElementById('loginForm');
        const loginSubmit = document.getElementById('loginSubmit');

        loginLink.addEventListener('click', (e) => {
            e.preventDefault();
            loginPopUp.classList.remove('hidden');
        });

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
                    window.location.href = '/dashboard'; // Redirect to your dashboard route
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

        // Initialize date inputs to today's date + 1 for check-in and +2 for check-out
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(today.getDate() + 1);
        const dayAfterTomorrow = new Date(today);
        dayAfterTomorrow.setDate(today.getDate() + 2);

        const formatDate = (date) => date.toISOString().split('T')[0];

        checkInDateInput.value = formatDate(tomorrow);
        checkOutDateInput.value = formatDate(dayAfterTomorrow);

        // Ensure check-out is always after check-in
        checkInDateInput.addEventListener('change', () => {
            const checkIn = new Date(checkInDateInput.value);
            const checkOut = new Date(checkOutDateInput.value);
            if (checkIn >= checkOut) {
                const newCheckOut = new Date(checkIn);
                newCheckOut.setDate(checkIn.getDate() + 1);
                checkOutDateInput.value = formatDate(newCheckOut);
            }
        });

        // Add a handler for the 'View Details' button in the results section to trigger the API call
        // This part is already included in renderResults, but keeping this comment as a reminder.

    </script>
</body>
</html>