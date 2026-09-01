<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Raíz: sin landing pública. Redirige según estado de sesión.
Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

// Auth routes (guest only). El registro público está deshabilitado:
// los usuarios se crean desde el panel de administración.
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email')->middleware('throttle:3,1');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update')->middleware('throttle:3,1');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Cambio de contraseña obligatorio (fuera del gate password.change para evitar bucle)
    Route::get('/cambiar-password', [PasswordChangeController::class, 'show'])->name('password.change.show');
    Route::post('/cambiar-password', [PasswordChangeController::class, 'update'])->name('password.change.update');

    // Rutas que exigen tener la contraseña actualizada
    Route::middleware('password.change')->group(function () {
        // Main dashboard (all authenticated users)
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Panel de administración — solo administradores
        Route::middleware('admin')->group(function () {
            Route::get('/dashboard/admin', fn () => view('modules.admin'))->name('admin');
        });

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
});
