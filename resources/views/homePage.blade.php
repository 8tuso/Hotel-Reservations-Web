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
                <a href="#" class="hover:text-indigo-600 transition-colors duration-300">Hotel Booking</a>
            </h1>
            <nav>
                <ul class="flex space-x-6">
                    @auth
                        <li id="dashboardLinkContainer"><a href="#" class="text-gray-600 hover:text-indigo-600 font-medium transition-colors duration-300">Dashboard</a></li>
                        <li id="dashboardLinkContainer"><a href="{{route('logout')}}" class="text-gray-600 hover:text-indigo-600 font-medium transition-colors duration-300">Logout</a></li>

                    @else
                    <li><a href="#" id="loginLink" class="text-gray-600 hover:text-indigo-600 font-medium transition-colors duration-300">Login</a></li>
                    <li><a href="#" id="registerLink" class="text-gray-600 hover:text-indigo-600 font-medium transition-colors duration-300" >Register</a></li>

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
                <div class="relative"> <label for="cityName" class="block text-gray-700 text-sm font-bold mb-2">
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

        <section id="recommendationsSection" class="relative py-8 bg-gray-50">
            <div class="container mx-auto px-4">
                <h3 class="text-3xl font-bold text-gray-800 text-center mb-6">Recommended Hotels for You</h3>

                <div id="recommendationsLoading" class="flex flex-col justify-center items-center py-10 space-y-4">
                    <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-indigo-500"></div>
                    <span class="text-gray-600 text-lg">Loading recommendations...</span>
                </div>

                <div id="recommendationsList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 hidden">
                    </div>

                <div id="noRecommendationsMessage" class="hidden text-center text-gray-600 text-lg py-10">
                    </div>
            </div>
        </section>

        <div id="geolocationPopup" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-8 rounded-lg shadow-xl text-center max-w-sm mx-auto">
                <h4 class="text-2xl font-semibold mb-4">Allow Location Access?</h4>
                <p class="text-gray-700 mb-6">To give you personalized hotel recommendations, we need your location.</p>
                <div class="flex justify-around">
                    <button id="allowLocationButton" class="bg-indigo-500 hover:bg-indigo-600 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-300">
                        Allow
                    </button>
                    <button id="denyLocationButton" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg transition-colors duration-300">
                        No Thanks
                    </button>
                </div>
            </div>
        </div>

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
    // Helper function to show messages (unrelated to recommendations, kept for completeness)
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

    // Get references to DOM elements
    const searchButton = document.getElementById('searchButton');
    const loadingIndicator = document.getElementById('loadingIndicator'); // For search results
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
    const recommendationsLoading = document.getElementById('recommendationsLoading'); // This will show the loading image
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

    // Assume currentUserId is available globally or passed from Laravel Blade
    // For example, from a meta tag or a global JS variable set in Blade:
    // <meta name="user-id" content="{{ Auth::id() }}">
    // const currentUserId = document.querySelector('meta[name="user-id"]')?.content || null;
    let currentUserId = document.querySelector('meta[name="user-id"]')?.content || null;
    // Fallback for development if not authenticated, adjust as needed
    if (!currentUserId) {
        console.warn("currentUserId is not set. Using a placeholder for testing. Ensure user is authenticated.");
        currentUserId = 'anonymous_dev_user'; // Placeholder, replace with actual logic
    }


    // Helper function to display "No recommendations" message
    function showNoRecommendationsMessage(message) {
        noRecommendationsMessage.textContent = message;
        noRecommendationsMessage.classList.remove('hidden');
        noRecommendationsMessage.classList.add('block');
        recommendationsList.innerHTML = ''; // Ensure list is empty
        // Ensure recommendations section is visible if it was hidden
        recommendationsSection.classList.remove('hidden');
        gsap.fromTo(recommendationsSection,
            { opacity: 0, y: 20 },
            { opacity: 1, y: 0, duration: 0.5, ease: "power2.out" }
        );
    }

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
            alert('User authentication is not ready. Please wait a moment and try again.');
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
                alert('Error searching for hotels: ' + (errorData.error || response.statusText));
                console.groupEnd();
                return [];
            }
            const data = await response.json();
            console.log("Search API Response Data (parsed JSON):", data);
            console.groupEnd();
            return data;
        } catch (error) {
            console.error('Network or Fetch Error:', error);
            alert('A network error occurred. Please check your connection.');
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
                                    data-adults="${room.adults ?? ''}">
                                View Details
                            </button>
                        </div>
                    </div>
                `;
                resultsList.insertAdjacentHTML('beforeend', roomCard);
            });
            // Staggered animation for result cards
            gsap.fromTo(".result-card",
                { opacity: 0, y: 50 },
                { opacity: 1, y: 0, stagger: 0.1, duration: 0.5, ease: "back.out(1.7)" }
            );

            // Attach event listeners to the newly rendered "View Details" buttons
            document.querySelectorAll('#resultsList .view-details-btn').forEach(button => {
                button.addEventListener('click', (event) => {
                    const hotelId = event.target.dataset.hotelId;
                    const offerId = event.target.dataset.offerId;
                    const checkIn = event.target.dataset.checkIn;
                    const checkOut = event.target.dataset.checkOut;
                    const adults = event.target.dataset.adults;

                    // Construct URL for the Laravel Blade route
                    const detailPageUrl = `/booking-details?hotelId=${hotelId}&offerId=${offerId}&checkInDate=${checkIn}&checkOutDate=${checkOut}&adults=${adults}`;
                    window.location.href = detailPageUrl;
                });
            });
        }
    }

    /**
     * Fetches and displays city suggestions using the autocomplete endpoint.
     * Triggered on blur of the input field.
     * @param {string} keyword - The partial city name typed by the user.
     */
    async function fetchCitySuggestions(keyword) {
        console.groupCollapsed("City Autocomplete API Call Debug");
        console.log("Attempting to fetch city suggestions for keyword:", keyword);

        citySuggestionsLoading.classList.remove('hidden'); // Show loading indicator
        citySuggestionsDiv.innerHTML = ''; // Clear previous suggestions
        citySuggestionsDiv.classList.add('hidden'); // Hide suggestions container while loading

        if (keyword.length < 2) { // Only search if at least 2 characters are typed
            console.log("Keyword too short for autocomplete (min 2 chars).");
            citySuggestionsLoading.classList.add('hidden'); // Hide loading
            suggestionsWereShown = false; // No suggestions shown
            console.groupEnd();
            return;
        }

        const apiUrl = `/api/city-autocomplete?keyword=${encodeURIComponent(keyword)}`;
        console.log("City Autocomplete API URL:", apiUrl);

        try {
            const response = await fetch(apiUrl);
            console.log("City Autocomplete API Raw Response:", response);
            if (!response.ok) {
                const errorData = await response.json();
                console.error('City Autocomplete API Error Status:', response.status);
                console.error('City Autocomplete API Error Data:', errorData);
                citySuggestionsDiv.innerHTML = '<div class="p-2 text-red-500">Error fetching suggestions.</div>';
                citySuggestionsDiv.classList.remove('hidden');
                suggestionsWereShown = true; // Still "shown" an error message
                console.groupEnd();
                return;
            }
            const suggestions = await response.json();
            console.log("City Autocomplete API Response Data (parsed JSON):", suggestions);
            displayCitySuggestions(suggestions);
            console.groupEnd();
        } catch (error) {
            console.error('Network or Fetch Error during autocomplete:', error);
            citySuggestionsDiv.innerHTML = '<div class="p-2 text-red-500">Network error.</div>';
            citySuggestionsDiv.classList.remove('hidden');
            suggestionsWereShown = true; // Still "shown" an error message
            console.groupEnd();
        } finally {
            citySuggestionsLoading.classList.add('hidden'); // Always hide loading indicator
        }
    }

    /**
     * Displays city suggestions in the dropdown.
     * @param {Array} suggestions - An array of city suggestion objects.
     */
    function displayCitySuggestions(suggestions) {
        console.log("Displaying city suggestions. Number of suggestions:", suggestions.length);
        citySuggestionsDiv.innerHTML = ''; // Clear previous suggestions

        if (suggestions.length === 0) {
            citySuggestionsDiv.innerHTML = '<div class="p-2 text-gray-500">No suggestions found.</div>';
            citySuggestionsDiv.classList.remove('hidden');
            suggestionsWereShown = true; // A message indicating no suggestions was shown
            console.log("No city suggestions to display.");
            return;
        }

        suggestions.forEach(suggestion => {
            const suggestionItem = document.createElement('div');
            suggestionItem.className = 'p-2 cursor-pointer hover:bg-indigo-100 transition-colors duration-200 border-b border-gray-100 last:border-b-0';
            suggestionItem.textContent = suggestion.name; // e.g., "London (LON) - United Kingdom"

            suggestionItem.dataset.iataCode = suggestion.iataCode;
            suggestionItem.dataset.cityName = suggestion.cityName;

            suggestionItem.addEventListener('click', () => {
                console.log("City suggestion clicked:", suggestion.name, "IATA:", suggestion.iataCode);
                isProgrammaticChange = true; // Set flag to true when a suggestion is clicked
                cityNameInput.value = suggestion.name; // Set input value to the full suggestion name
                selectedIataCode = suggestion.iataCode; // Store the IATA code for search
                suggestionsWereShown = false; // A selection was made, so no longer need to force selection
                citySuggestionsDiv.classList.add('hidden'); // Hide suggestions
                citySuggestionsDiv.innerHTML = ''; // Clear suggestions
                // Reset the flag after a very short delay to allow blur event to process
                setTimeout(() => {
                    isProgrammaticChange = false;
                    console.log("isProgrammaticChange reset to false.");
                }, 50);
            });
            citySuggestionsDiv.appendChild(suggestionItem);
        });
        citySuggestionsDiv.classList.remove('hidden'); // Show the suggestions container
        suggestionsWereShown = true; // Suggestions were successfully displayed
        console.log("City suggestions displayed.");
    }


    // Event listener for the search button
    searchButton.addEventListener('click', async () => {
        console.groupCollapsed("Search Button Click Debug");
        console.log("Search button clicked.");
        // GSAP animation for button click
        gsap.to(searchButton, { scale: 0.95, duration: 0.1, yoyo: true, repeat: 1, ease: "power1.inOut" });

        // Get current typed value
        const typedCityName = cityNameInput.value.trim();
        console.log("Typed City Name:", typedCityName);
        console.log("Selected IATA Code:", selectedIataCode);
        console.log("Suggestions were shown:", suggestionsWereShown);
        console.log("City suggestions div hidden:", citySuggestionsDiv.classList.contains('hidden'));


        // Validate if user selected from suggestions or if input is empty
        if (typedCityName.length > 0 && !selectedIataCode && suggestionsWereShown && citySuggestionsDiv.classList.contains('hidden') && !(typedCityName.length === 3 && /^[a-zA-Z]+$/.test(typedCityName))) {
            console.warn("Validation failed: User typed city, but no IATA selected and suggestions were shown but now hidden.");
            alert('Please select a city from the suggested list, or ensure you entered a valid 3-letter IATA code directly.');
            console.groupEnd();
            return; // Prevent search
        }


        // Hide previous results and show loading indicator
        searchResultsSection.classList.add('hidden');
        loadingIndicator.classList.remove('hidden');
        gsap.fromTo(loadingIndicator,
            { opacity: 0, scale: 0.8 },
            { opacity: 1, scale: 1, duration: 0.5, ease: "back.out(1.7)" }
        );
        console.log("Loading indicator shown for search results.");

        // Get values from input fields
        const roomType = roomTypeInput.value.trim();
        const checkInDate = checkInDateInput.value;
        const checkOutDate = checkOutDateInput.value;
        const adults = adultsInput.value;
        const roomQuantity = roomQuantityInput.value;

        // Determine which city value to send: IATA code if selected, otherwise typed name
        const cityParam = selectedIataCode || (typedCityName.length === 3 && /^[a-zA-Z]+$/.test(typedCityName) ? typedCityName.toUpperCase() : typedCityName);
        console.log("Determined cityParam for search:", cityParam);

        // Basic client-side validation for required fields
        if (!cityParam || !checkInDate || !checkOutDate || !adults || !roomQuantity) {
            console.error("Client-side validation failed: Missing required search fields.");
            alert('Please fill in all required search fields (City, Dates, Adults, Rooms).');
            loadingIndicator.classList.add('hidden'); // Hide loading if validation fails
            console.groupEnd();
            return;
        }

        // Prepare parameters object for the fetch function
        const searchParams = {
            cityName: cityParam, // Send the IATA code if selected, else the typed name
            roomType: roomType,
            checkInDate: checkInDate,
            checkOutDate: checkOutDate,
            adults: adults,
            roomQuantity: roomQuantity
        };
        console.log("Search parameters prepared:", searchParams);

        // Call the backend API
        const results = await fetchSearchResults(searchParams);

        // Hide loading indicator and show results
        gsap.to(loadingIndicator, { opacity: 0, scale: 0.8, duration: 0.3, onComplete: () => {
            loadingIndicator.classList.add('hidden');
            searchResultsSection.classList.remove('hidden');
            gsap.fromTo(searchResultsSection,
                { opacity: 0, y: 20 },
                { opacity: 1, y: 0, duration: 0.5, ease: "power2.out" }
            );
            renderResults(results);
            console.log("Search results displayed.");
            console.groupEnd(); // End of Search Button Click Debug
        }});
    });

    // Autocomplete Trigger: Call fetchCitySuggestions on blur (when input loses focus)
    cityNameInput.addEventListener('blur', (event) => {
        console.log("City Name input blurred. isProgrammaticChange:", isProgrammaticChange);
        // If the change was programmatic (from selecting a suggestion), don't trigger autocomplete
        if (isProgrammaticChange) {
            return;
        }

        // Add a small delay to allow click event on suggestion to fire before blur processes
        setTimeout(() => {
            const keyword = event.target.value.trim();
            console.log("Blur timeout triggered. Keyword:", keyword);
            if (keyword.length > 0 && !selectedIataCode) {
                console.log("Calling fetchCitySuggestions due to blur (keyword > 0 and no IATA selected).");
                fetchCitySuggestions(keyword);
            } else if (keyword.length === 0) {
                console.log("City name input empty on blur. Hiding suggestions.");
                citySuggestionsDiv.innerHTML = '';
                citySuggestionsDiv.classList.add('hidden');
                suggestionsWereShown = false;
            } else if (selectedIataCode) {
                console.log("Blur event: IATA code already selected. Not re-fetching suggestions.");
            }
        }, 150); // Increased delay slightly
    });

    // Reset selectedIataCode and suggestionsWereShown when user types again
    cityNameInput.addEventListener('input', () => {
        console.log("City Name input changed.");
        selectedIataCode = null; // Clear selected IATA if user modifies input
        suggestionsWereShown = false; // User is typing new input, previous suggestions are now irrelevant
        isProgrammaticChange = false; // Reset this flag if user types after a selection
        // Keep the suggestions hidden while typing as per requirement
        citySuggestionsDiv.classList.add('hidden');
        citySuggestionsDiv.innerHTML = '';
        console.log("Selected IATA reset, suggestions hidden.");
    });

    // Hide suggestions when clicking outside the input and suggestions div
    document.addEventListener('click', (event) => {
        if (!cityNameInput.contains(event.target) && !citySuggestionsDiv.contains(event.target)) {
            if (!citySuggestionsDiv.classList.contains('hidden')) {
                console.log("Clicked outside city input/suggestions. Hiding suggestions.");
                citySuggestionsDiv.classList.add('hidden');
                citySuggestionsDiv.innerHTML = '';
            }
        }
    });

    /**
     * Checks geolocation permission status and either prompts, fetches, or informs.
     * This function is now called immediately on window.onload.
     */
    async function checkAndFetchRecommendations() {
        console.groupCollapsed("Geolocation Permission Check");
        // Show loading immediately, hide other elements
        recommendationsLoading.classList.remove('hidden'); // LOADING IMAGE IS VISIBLE
        recommendationsList.classList.add('hidden'); // Hide recommendations list
        noRecommendationsMessage.classList.add('hidden'); // Hide "no recommendations" message
        recommendationsSection.classList.remove('hidden'); // Ensure section is visible

        // Check for secure context and localhost to decide on geolocation attempt
        const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
        const isSecure = window.isSecureContext;

        console.log("Current context: isSecure =", isSecure, ", isLocalhost =", isLocalhost);

        if (!navigator.geolocation) {
            console.warn("Geolocation is NOT supported by this browser.");
            showNoRecommendationsMessage("Your browser does not support geolocation. Please use the search bar.");
            recommendationsLoading.classList.add('hidden'); // Hide loading
            console.groupEnd();
            return;
        }

        // If not secure and not localhost, we know geolocation will be blocked.
        if (!isSecure && !isLocalhost) {
            console.warn("Insecure context detected (not HTTPS and not localhost). Geolocation will likely be blocked by the browser.");
            showNoRecommendationsMessage("Location access is blocked by your browser for insecure connections. Using default recommendations.");
            recommendationsLoading.classList.add('hidden'); // Hide loading
            // Proceed to fetch recommendations without location data
            fetchRecommendations(false, true); // Force prompt=false, skipGeolocation=true
            console.groupEnd();
            return;
        }

        // If we reach here, it's either HTTPS or localhost, so geolocation might work.
        try {
            const permissionStatus = await navigator.permissions.query({ name: 'geolocation' });
            console.log("Geolocation permission status:", permissionStatus.state);

            if (permissionStatus.state === 'granted') {
                console.log("Geolocation permission already granted. Fetching recommendations directly.");
                fetchRecommendations();
            } else if (permissionStatus.state === 'prompt') {
                console.log("Geolocation permission needs to be prompted. Showing popup.");
                geolocationPopup.classList.remove('hidden'); // Show the custom popup
                recommendationsLoading.classList.add('hidden'); // Hide loading while popup is shown
            } else if (permissionStatus.state === 'denied') {
                console.warn("Geolocation permission permanently denied by user.");
                showNoRecommendationsMessage("Location access denied. Please enable it in your browser settings for recommendations.");
                recommendationsLoading.classList.add('hidden'); // Hide loading
            }

            // Listen for changes in permission status (e.g., if user changes it in settings)
            permissionStatus.onchange = () => {
                console.log("Geolocation permission state changed to:", permissionStatus.state);
                if (permissionStatus.state === 'granted') {
                    // If they grant it after initially denying or prompting, fetch recommendations
                    geolocationPopup.classList.add('hidden'); // Hide popup if it was visible
                    fetchRecommendations();
                } else if (permissionStatus.state === 'denied') {
                    geolocationPopup.classList.add('hidden');
                    showNoRecommendationsMessage("Location access denied. Please enable it in your browser settings for recommendations.");
                    recommendationsLoading.classList.add('hidden'); // Hide loading
                }
            };

        } catch (error) {
            console.error("Error querying geolocation permission:", error);
            showNoRecommendationsMessage("Could not check location permission. Please use the search bar.");
            recommendationsLoading.classList.add('hidden'); // Hide loading
        }
        console.groupEnd();
    }


    // Event listener for the "Allow Location" button on the popup
    allowLocationButton.addEventListener('click', () => {
        console.log("Allow Location button clicked.");
        geolocationPopup.classList.add('hidden'); // Hide the custom popup
        recommendationsLoading.classList.remove('hidden'); // Show loading again as we're fetching
        fetchRecommendations(); // Attempt to fetch recommendations, which will trigger browser prompt
    });

    // Event listener for the "No Thanks" button on the popup
    denyLocationButton.addEventListener('click', () => {
        console.log("No Thanks button clicked. Not requesting geolocation.");
        geolocationPopup.classList.add('hidden'); // Hide the custom popup
        showNoRecommendationsMessage("No recommendations without location. Please use the search bar.");
        recommendationsLoading.classList.add('hidden'); // Hide loading
    });


    /**
     * Function to fetch and render recommendations.
     * @param {boolean} forcePrompt - Whether to force the browser's geolocation prompt.
     * @param {boolean} skipGeolocation - If true, skip geolocation attempt and proceed with API call without lat/lon.
     */
    async function fetchRecommendations(forcePrompt = false, skipGeolocation = false) {
        console.groupCollapsed("Recommendations Fetch Debug");
        console.log("Attempting to fetch recommendations...");
        recommendationsLoading.classList.remove('hidden'); // Ensure loading is visible
        recommendationsList.classList.add('hidden'); // Hide content while loading
        noRecommendationsMessage.classList.add('hidden'); // Hide any previous "no recommendations" message

        let latitude = null;
        let longitude = null;

        if (!skipGeolocation && navigator.geolocation) {
            console.log("Geolocation API supported. Attempting to get current position...");
            try {
                const position = await new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(resolve, reject, { timeout: 5000, enableHighAccuracy: false });
                });
                latitude = position.coords.latitude;
                longitude = position.coords.longitude;
                console.log("Geolocation obtained: Latitude", latitude, "Longitude", longitude);
            } catch (error) {
                console.warn("Geolocation error:", error.message);
                console.warn("Geolocation error code:", error.code);
                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        console.warn("Geolocation permission denied by user. Falling back to default recommendations.");
                        showNoRecommendationsMessage("Location access denied. Using default recommendations.");
                        break;
                    case error.POSITION_UNAVAILABLE:
                        console.warn("Geolocation position unavailable. Falling back to default recommendations.");
                        showNoRecommendationsMessage("Could not determine your location. Using default recommendations.");
                        break;
                    case error.TIMEOUT:
                        console.warn("Geolocation request timed out. Falling back to default recommendations.");
                        showNoRecommendationsMessage("Location request timed out. Using default recommendations.");
                        break;
                    default:
                        console.warn("Unknown geolocation error. Falling back to default recommendations.");
                        showNoRecommendationsMessage("An error occurred with location services. Using default recommendations.");
                }
                // If geolocation fails, we still proceed to call the API but without lat/lon
                latitude = null;
                longitude = null;
            }
        } else if (!navigator.geolocation) {
            console.warn("Geolocation is NOT supported by this browser. Using default recommendations.");
            showNoRecommendationsMessage("Your browser does not support geolocation. Using default recommendations.");
        } else {
            console.log("Skipping geolocation as requested or not applicable.");
        }

        const queryParams = new URLSearchParams();
        if (latitude !== null && longitude !== null) {
            queryParams.append('latitude', latitude);
            queryParams.append('longitude', longitude);
        }
        // Add userId to recommendation parameters
        if (currentUserId) {
            queryParams.append('userId', currentUserId);
        } else {
            console.error("Recommendation fetch aborted: currentUserId is null. User authentication not ready.");
            showNoRecommendationsMessage("User authentication not ready. Please try again later or use the search bar.");
            recommendationsLoading.classList.add('hidden'); // Hide loading
            console.groupEnd();
            return;
        }

        const apiUrl = `/api/recommendations?${queryParams.toString()}`;
        console.log("Recommendations API URL:", apiUrl);
        console.log("Recommendations Query Parameters:", { latitude, longitude, userId: currentUserId });

        try {
            const response = await fetch(apiUrl);
            console.log("Recommendations API Raw Response:", response);
            if (!response.ok) {
                const errorData = await response.json();
                console.error('Recommendations API Error Status:', response.status);
                console.error('Recommendations API Error Data:', errorData);
                showNoRecommendationsMessage('Error fetching recommendations: ' + (errorData.error || response.statusText));
                console.groupEnd();
                return;
            }
            const data = await response.json();
            console.log("Recommendations API Response Data (parsed JSON):", data);
            renderRecommendations(data);
            console.groupEnd();
        } catch (error) {
            console.error('Network or Fetch Error during recommendations:', error);
            showNoRecommendationsMessage('A network error occurred while fetching recommendations. Using default.');
            console.groupEnd();
        } finally {
            recommendationsLoading.classList.add('hidden'); // Always hide loading indicator after fetch attempt
            recommendationsList.classList.remove('hidden'); // Show the recommendations list (even if empty)
        }
    }

    /**
     * Renders the recommendation results into the DOM.
     * @param {Array} recommendations - An array of hotel room objects for recommendations.
     */
    function renderRecommendations(recommendations) {
        console.log("Rendering recommendations. Number of recommendations:", recommendations.length);
        recommendationsList.innerHTML = ''; // Clear previous recommendations
        if (recommendations.length === 0) {
            showNoRecommendationsMessage("No recommendations found at this time. Please use the search bar.");
        } else {
            noRecommendationsMessage.classList.add('hidden'); // Hide "no recommendations" message
            recommendations.forEach((room, index) => {
                console.log(`Rendering recommendation room ${index + 1}:`, room); // Log the room object
                const roomCard = `
                    <div class="bg-white rounded-lg shadow-md overflow-hidden transform hover:scale-105 transition-transform duration-300 recommendation-card">
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
                                    data-adults="${room.adults ?? ''}">
                                View Details
                            </button>
                        </div>
                    </div>
                `;
                recommendationsList.insertAdjacentHTML('beforeend', roomCard);
            });
            // Staggered animation for recommendation cards
            gsap.fromTo(".recommendation-card",
                { opacity: 0, y: 50 },
                { opacity: 1, y: 0, stagger: 0.1, duration: 0.5, ease: "back.out(1.7)" }
            );

            // Attach event listeners to the newly rendered "View Details" buttons for recommendations
            document.querySelectorAll('#recommendationsList .view-details-btn').forEach(button => {
                button.addEventListener('click', (event) => {
                    const hotelId = event.target.dataset.hotelId;
                    const offerId = event.target.dataset.offerId;
                    const checkIn = event.target.dataset.checkIn;
                    const checkOut = event.target.dataset.checkOut;
                    const adults = event.target.dataset.adults;

                    const detailPageUrl = `/booking-details?hotelId=${hotelId}&offerId=${offerId}&checkInDate=${checkIn}&checkOutDate=${checkOut}&adults=${adults}`;
                    window.location.href = detailPageUrl;
                });
            });
        }
        // Ensure the recommendations list is visible once rendering is complete (or if no results are shown)
        recommendationsList.classList.remove('hidden');
    }

    // Initial call to fetch recommendations when the page loads
    window.addEventListener('load', () => {
        console.log("Window loaded. Initiating checkAndFetchRecommendations.");
        checkAndFetchRecommendations();
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

</script>
</body>
</html>
