<?php

use App\Http\Controllers\CityAutocompleteController;
use App\Http\Controllers\HotelSearchController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\ReservationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Route for hotel search
Route::get('/hotel-search', [HotelSearchController::class, 'search']);
Route::get('/city-autocomplete', [CityAutocompleteController::class, 'search']); // New route for autocomplete
Route::get('/recommendations', [RecommendationController::class, 'getRecommendations']); // New route for recommendations
Route::get('/hotel-offer-details', [RecommendationController::class, 'getOfferDetails']);

