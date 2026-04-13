<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — CRM-AC
|--------------------------------------------------------------------------
|
| Todas las rutas API están protegidas por:
| - Middleware 'auth:sanctum' (requiere sesión autenticada)
| - Throttle para prevenir abuso
| - CSRF via cookie de sesión (SPA mode)
|
*/

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {

    // Ping — útil para verificar sesión desde el frontend
    Route::get('/ping', function (\Illuminate\Http\Request $request) {
        return response()->json([
            'message' => 'pong',
            'user'    => $request->user()->name,
        ]);
    });

    // ─── Módulo de Inventarios ────────────────────────────────
    Route::middleware('service:inventory')->prefix('inventory')->group(function () {

        // Categorías
        Route::get('/categories', [\App\Http\Controllers\Api\Inventory\CategoryController::class, 'index']);
        Route::post('/categories', [\App\Http\Controllers\Api\Inventory\CategoryController::class, 'store']);
        Route::put('/categories/{id}', [\App\Http\Controllers\Api\Inventory\CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [\App\Http\Controllers\Api\Inventory\CategoryController::class, 'destroy']);

        // Productos
        Route::apiResource('products', \App\Http\Controllers\Api\Inventory\ProductController::class);

        // Movimientos
        Route::get('/movements', [\App\Http\Controllers\Api\Inventory\MovementController::class, 'index']);
        Route::post('/movements', [\App\Http\Controllers\Api\Inventory\MovementController::class, 'store']);
    });

    // ─── Módulo de Finanzas ──────────────────────────────────
    Route::middleware('service:finance')->prefix('finance')->group(function () {
        // Rutas de finanzas...
    });

});
