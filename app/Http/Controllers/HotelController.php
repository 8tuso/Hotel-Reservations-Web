<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// In Controller
use Illuminate\Support\Facades\App;
use App\Models\Hotel; // Add this import

class HotelController extends Controller
{
    public function index()
    {
        $featuredHotels = Hotel::with('rooms')
            ->where('is_featured', true)
            ->take(6)
            ->get();

        return view('hotels.index', compact('featuredHotels'));
    }
    public function search()
    {
        $results = App::make('hotelApi')->searchHotels([
            'location' => request('city'),
            'check_in' => now()->format('Y-m-d')
        ]);
        
        $recommendations = App::make('aiRecommender')->recommend([
            'user_id' => auth()->id(),
            'search_params' => request()->all()
        ]);

        return view('hotels', compact('results', 'recommendations'));
    }
}
