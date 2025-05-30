<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $reservations = $user->reservations()
            ->with(['room.hotel'])
            ->orderBy('check_in_date', 'desc')
            ->paginate(5);

        return view('dashboard', compact('reservations'));
    }
}