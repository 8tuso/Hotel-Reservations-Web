<?php
// app/Providers/RecommendationProvider.php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class RecommendationProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('aiRecommender', function () {
            return new class() {
                public function recommend(array $userData)
                {
                    try {
                        $response = Http::withHeaders([
                                'AI-API-KEY' => config('services.ai.key'),
                            ])
                            ->post(config('services.ai.url'), $userData);

                        return $response->json()['recommendations'];
                    } catch (\Exception $e) {
                        return $this->fallbackRecommendation();
                    }
                }

                private function fallbackRecommendation()
                {
                    // Simple fallback logic
                    return [
                        'basic_room',
                        'standard_room',
                        'deluxe_room'
                    ];
                }
            };
        });
    }

    public function boot()
    {
        //
    }
}