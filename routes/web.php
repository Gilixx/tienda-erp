<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Landing page (public)
Route::get('/', function () {
    return view('welcome');
});

// Auth routes (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:3,1');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email')->middleware('throttle:3,1');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update')->middleware('throttle:3,1');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Main dashboard (all authenticated users)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Module routes — protected by service access middleware
    Route::middleware('service:inventory')->group(function () {
        Route::get('/dashboard/inventory', function () {
            return view('modules.inventory');
        })->name('inventory');
    });

    // Punto de venta — pantalla completa
    Route::middleware('service:pos')->group(function () {
        Route::get('/dashboard/pos', function (\Illuminate\Http\Request $request) {
            $almacenes = \App\Models\Inventory\Almacen::accesiblesPara($request->user())
                ->orderByDesc('es_principal')
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'codigo', 'es_principal']);

            return view('modules.pos', compact('almacenes'));
        })->name('pos');
    });
});
