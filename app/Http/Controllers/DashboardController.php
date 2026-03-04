<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the main dashboard.
     */
    public function index(Request $request): View
    {
        $user     = $request->user();
        $services = $user->isAdmin()
            ? \App\Models\Service::all()
            : $user->services()->get();

        return view('dashboard', compact('user', 'services'));
    }
}
