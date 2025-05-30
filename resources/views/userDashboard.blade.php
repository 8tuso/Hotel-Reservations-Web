<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Custom styles for demonstration, adjust as needed */
        .profile-input {
            @apply w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500;
        }
        .profile-label {
            @apply block text-sm font-medium text-gray-700 mb-1;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased">
    <div class="min-h-screen flex flex-col">
        <header class="bg-white shadow-sm p-4">
            <div class="container mx-auto flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-800">My Dashboard</h1>
                <nav>
                    <a href="{{route('home.page')}}" class="text-indigo-600 hover:text-indigo-800 mr-4">Home</a>
                    {{-- Assuming you have a logout route --}}
                    <a href="{{route('logout')}}" type="submit" class="text-gray-600 hover:text-gray-800">Logout</a>
                </nav>
            </div>
        </header>

        <main class="flex-grow container mx-auto p-4 md:p-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <aside class="lg:col-span-1 bg-white p-6 rounded-lg shadow-md h-full">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Navigation</h2>
                    <ul class="space-y-2">
                        <li><a href="#profile" class="block py-2 px-3 rounded-md text-indigo-600 bg-indigo-50 hover:bg-indigo-100 font-medium">Profile</a></li>
                        <li><a href="#offers" class="block py-2 px-3 rounded-md text-gray-700 hover:bg-gray-50">Viewed Offers</a></li>
                        <li><a href="#bookings" class="block py-2 px-3 rounded-md text-gray-700 hover:bg-gray-50">Running Bookings</a></li>
                    </ul>
                </aside>

                <div class="lg:col-span-2 space-y-8">

                    <section id="profile" class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">My Profile</h2>
                        {{-- Add a success message for profile updates --}}
                        @if (session('status'))
                            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                                <span class="block sm:inline">{{ session('status') }}</span>
                            </div>
                        @endif
                        <form action="{{ route('profile.update') }}" method="POST">
                            @csrf
                            @method('PUT') {{-- Use PUT method for updates --}}

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="name" class="profile-label">Full Name</label>
                                    {{-- Use $user->customer->full_name if customer exists, otherwise $user->name --}}
                                    <input type="text" id="name" name="name" value="{{ old('name', $user->customer->full_name ?? $user->name) }}" class="profile-input">
                                    @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="email" class="profile-label">Email Address</label>
                                    {{-- Email is usually from User model, but check Customer too if applicable --}}
                                    <input type="email" id="email" name="email" value="{{ old('email', $user->customer->email ?? $user->email) }}" class="profile-input" disabled>
                                    <p class="text-gray-500 text-xs mt-1">Email cannot be changed directly here.</p>
                                </div>
                                <div>
                                    <label for="phone" class="profile-label">Phone Number</label>
                                    {{-- Access phone from customer or user model --}}
                                    <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->customer->phone ?? '') }}" class="profile-input">
                                    @error('phone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="date_of_birth" class="profile-label">Date of Birth</label>
                                    {{-- Access date_of_birth from customer --}}
                                    <input type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', $user->customer->date_of_birth ?? '') }}" class="profile-input">
                                    @error('date_of_birth')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="gender" class="profile-label">Gender</label>
                                    <select id="gender" name="gender" class="profile-input">
                                        <option value="">Select Gender</option>
                                        <option value="Male" {{ old('gender', $user->customer->gender ?? '') == 'Male' ? 'selected' : '' }}>Male</option>
                                        <option value="Female" {{ old('gender', $user->customer->gender ?? '') == 'Female' ? 'selected' : '' }}>Female</option>
                                        <option value="Other" {{ old('gender', $user->customer->gender ?? '') == 'Other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    @error('gender')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="nationality" class="profile-label">Nationality</label>
                                    <input type="text" id="nationality" name="nationality" value="{{ old('nationality', $user->customer->nationality ?? '') }}" class="profile-input">
                                    @error('nationality')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="passport_number" class="profile-label">Passport Number</label>
                                    <input type="text" id="passport_number" name="passport_number" value="{{ old('passport_number', $user->customer->passport_number ?? '') }}" class="profile-input">
                                    @error('passport_number')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div class="md:col-span-2">
                                    <label for="address" class="profile-label">Address</label>
                                    <input type="text" id="address" name="address" value="{{ old('address', $user->customer->address ?? '') }}" class="profile-input">
                                    @error('address')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="city" class="profile-label">City</label>
                                    <input type="text" id="city" name="city" value="{{ old('city', $user->customer->city ?? '') }}" class="profile-input">
                                    @error('city')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="country" class="profile-label">Country</label>
                                    <input type="text" id="country" name="country" value="{{ old('country', $user->customer->country ?? '') }}" class="profile-input">
                                    @error('country')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="postal_code" class="profile-label">Postal Code</label>
                                    <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $user->customer->postal_code ?? '') }}" class="profile-input">
                                    @error('postal_code')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-md transition-colors duration-300">
                                    Update Profile
                                </button>
                            </div>
                        </form>
                    </section>

                    <section id="offers" class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Continue Exploring Offers</h2>
                        {{-- Check if the $viewedOffers array is empty --}}
                        @if (empty($viewedOffers))
                            <p class="text-gray-600">You haven't viewed any offers yet. Start Browse to see them here!</p>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                @foreach ($viewedOffers as $offer)
                                    <div class="bg-gray-50 rounded-lg shadow-sm overflow-hidden border border-gray-100">
                                        {{-- Using urlencode for alt text in case hotel name has special characters --}}
                                        <img src="https://placehold.co/300x200/E0F2F7/2C3E50?text={{ urlencode($offer['hotel_name'] ?? 'Hotel') }}" alt="{{ $offer['hotel_name'] ?? 'Hotel Offer' }}" class="w-full h-40 object-cover">
                                        <div class="p-4">
                                            <h3 class="font-semibold text-lg text-gray-800 mb-1">{{ $offer['hotel_name'] ?? 'Hotel Name Missing' }}</h3>
                                            {{-- Use description_text from controller --}}
                                            <p class="text-sm text-gray-600 mb-2">{{ Str::limit($offer['description_text'] ?? 'No description.', 70) }}</p>
                                            <div class="flex justify-between items-center mt-3">
                                                {{-- Use display_price and display_currency from controller --}}
                                                <span class="font-bold text-indigo-600 text-xl">{{ $offer['display_currency'] ?? '$' }}{{ $offer['display_price'] ?? '0.00' }}</span>
                                                {{-- Use the generated link from controller --}}
                                                <a href="{{ $offer['link'] ?? '#' }}" class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-md text-sm transition-colors duration-300">View Offer</a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    <section id="bookings" class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Your Running Bookings</h2>
                        @if ($runningBookings->isEmpty())
                            <p class="text-gray-600">You have no active bookings at the moment. Plan your next trip!</p>
                            <a href="{{route('home.page')}}" class="inline-block mt-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-md transition-colors duration-300">Find Hotels</a>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hotel</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check-in</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check-out</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Price</th>
                                            <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach ($runningBookings as $booking)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $booking->Hotel_name }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($booking->Check_in_date)->format('M d, Y') }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($booking->Check_out_date)->format('M d, Y') }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                        @if($booking->status == 'PAYED') bg-green-100 text-green-800
                                                        @elseif($booking->status == 'PENDING') bg-yellow-100 text-yellow-800
                                                        @elseif($booking->status == 'CACNELLED') bg-red-100 text-red-800
                                                        @else bg-gray-100 text-gray-800 @endif">
                                                        {{ $booking->status }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">{{$booking->currency}}{{ $booking->Totel_price }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    {{-- Assuming you have actual routes for details and cancel --}}
                                                    <a href="{{ route('payment.page', 'paymentId='.$booking->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">View Details</a>
                                                    @if($booking->status == 'PAYED' || $booking->status == 'PENDING')
                                                        <a href="" class="text-red-600 hover:text-red-900">Cancel</a>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </section>

                </div>
            </div>
        </main>

        <footer class="bg-white shadow-sm p-4 mt-8">
            <div class="container mx-auto text-center text-gray-600 text-sm">
                &copy; {{ date('Y') }} Your Company. All rights reserved.
            </div>
        </footer>
    </div>
</body>
</html>