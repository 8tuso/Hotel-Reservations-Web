<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Hotels
Route::get('/', function () {
    return view('homePage');
})->name('home.page');
Route::get('/hotels/{hotel}', [HotelController::class, 'show'])->name('hotels.show');
Route::get('/search', [HotelController::class, 'search'])->name('hotels.search');

// Reservations
Route::middleware(['auth'])->group(function () {
    Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
    
    // Payments
    Route::get('/reservations/{reservation}/pay', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('/reservations/{reservation}/pay', [PaymentController::class, 'store'])->name('payments.store');
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Reviews
    Route::post('/hotels/{hotel}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
});

Route::get('/booking-details', [RecommendationController::class, 'getOfferDetails'])->name('booking.details');

// routes/web.php
Auth::routes();

Route::get('/phpinfo', function ()
{
    return view("phpinfo");
});


// Route for temporarily caching offer details when user proceeds to booking
Route::post('/cache-proceed-booking', [ReservationController::class, 'cacheProceedBooking']);

// Route for creating a reservation (simulated, no external API call)
Route::post('/create-reservation', [ReservationController::class, 'createReservation']);

Route::get('/payment-page',[PaymentController::class, 
'pendingReservation'])->name('payment.page');

Route::post('/payment-confirm',[PaymentController::class, 'confirmPayment'])->name('payment.confirm');

Route::post('/create-user', [AuthController::class, 'createUser']);
Route::post('/user-login', [AuthController::class, 'loginUser'])->name('login');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [UserController::class, 'dashboard'])->name('dashboard');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
    Route::get('/logout', [AuthController::class, 'logoutUser'])->name('logout');
});


