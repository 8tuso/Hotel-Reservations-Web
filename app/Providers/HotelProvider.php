<?php
// app/Providers/HotelProvider.php

namespace App\Providers;

use GuzzleHttp\Client;
use Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class HotelProvider extends ServiceProvider
{
    public function register()
    {
       $this->app->singleton('hotelApi', function () {
    // Get authentication token first if required
    $authResponse = Http::post(config('services.hotel_api.url'), [
        'api_key' => config('services.hotel_api.key'),
        'api_secret' => config('services.hotel_api.secret')
    ]);

    return new Client([
        'base_uri' => config('services.hotel_api.url'),
        'headers' => [
            'Accept' => 'application/json',
        ],
    ]);
        });
    }

    public function boot()
    {
        //
    }
}