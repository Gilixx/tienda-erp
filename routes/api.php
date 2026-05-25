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

        // Importación catálogo
        Route::get('/products-template', [\App\Http\Controllers\Api\Inventory\ImportController::class, 'template']);
        Route::post('/products-import', [\App\Http\Controllers\Api\Inventory\ImportController::class, 'import'])
            ->middleware('throttle:10,1');

        // Estadísticas
        Route::get('/stats', [\App\Http\Controllers\Api\Inventory\StatsController::class, 'index']);
        Route::get('/stats/report', [\App\Http\Controllers\Api\Inventory\StatsController::class, 'report'])
            ->middleware('throttle:20,1');

        // Movimientos
        Route::get('/movements', [\App\Http\Controllers\Api\Inventory\MovementController::class, 'index']);
        Route::post('/movements', [\App\Http\Controllers\Api\Inventory\MovementController::class, 'store']);
    });

    // ─── Módulo de Finanzas ──────────────────────────────────
    Route::middleware('service:finance')->prefix('finance')->group(function () {

        // Catálogos
        Route::get('/monedas',                 [\App\Http\Controllers\Api\Finance\MonedaController::class, 'index']);
        Route::post('/monedas',                [\App\Http\Controllers\Api\Finance\MonedaController::class, 'store']);
        Route::put('/monedas/{id}',            [\App\Http\Controllers\Api\Finance\MonedaController::class, 'update']);
        Route::delete('/monedas/{id}',         [\App\Http\Controllers\Api\Finance\MonedaController::class, 'destroy']);

        Route::get('/tipos-cambio',            [\App\Http\Controllers\Api\Finance\TipoCambioController::class, 'index']);
        Route::post('/tipos-cambio',           [\App\Http\Controllers\Api\Finance\TipoCambioController::class, 'store']);
        Route::delete('/tipos-cambio/{id}',    [\App\Http\Controllers\Api\Finance\TipoCambioController::class, 'destroy']);

        Route::get('/impuestos',               [\App\Http\Controllers\Api\Finance\ImpuestoController::class, 'index']);
        Route::post('/impuestos',              [\App\Http\Controllers\Api\Finance\ImpuestoController::class, 'store']);
        Route::put('/impuestos/{id}',          [\App\Http\Controllers\Api\Finance\ImpuestoController::class, 'update']);
        Route::delete('/impuestos/{id}',       [\App\Http\Controllers\Api\Finance\ImpuestoController::class, 'destroy']);

        Route::get('/categorias',              [\App\Http\Controllers\Api\Finance\CategoriaController::class, 'index']);
        Route::post('/categorias',             [\App\Http\Controllers\Api\Finance\CategoriaController::class, 'store']);
        Route::put('/categorias/{id}',         [\App\Http\Controllers\Api\Finance\CategoriaController::class, 'update']);
        Route::delete('/categorias/{id}',      [\App\Http\Controllers\Api\Finance\CategoriaController::class, 'destroy']);

        // Cuentas y transferencias
        Route::get('/cuentas',                 [\App\Http\Controllers\Api\Finance\CuentaController::class, 'index']);
        Route::post('/cuentas',                [\App\Http\Controllers\Api\Finance\CuentaController::class, 'store']);
        Route::get('/cuentas/{id}',            [\App\Http\Controllers\Api\Finance\CuentaController::class, 'show']);
        Route::put('/cuentas/{id}',            [\App\Http\Controllers\Api\Finance\CuentaController::class, 'update']);
        Route::delete('/cuentas/{id}',         [\App\Http\Controllers\Api\Finance\CuentaController::class, 'destroy']);
        Route::post('/cuentas/transferir',     [\App\Http\Controllers\Api\Finance\CuentaController::class, 'transferir'])
            ->middleware('throttle:30,1');

        // Proveedores
        Route::apiResource('proveedores',      \App\Http\Controllers\Api\Finance\ProveedorController::class);

        // Transacciones (writes con throttle más estricto)
        Route::get('/transacciones',           [\App\Http\Controllers\Api\Finance\TransaccionController::class, 'index']);
        Route::post('/transacciones',          [\App\Http\Controllers\Api\Finance\TransaccionController::class, 'store'])
            ->middleware('throttle:30,1');
        Route::get('/transacciones/{id}',      [\App\Http\Controllers\Api\Finance\TransaccionController::class, 'show']);
        Route::delete('/transacciones/{id}',   [\App\Http\Controllers\Api\Finance\TransaccionController::class, 'destroy'])
            ->middleware('throttle:20,1');

        // Lookup productos (para compras y transacciones)
        Route::get('/products',                [\App\Http\Controllers\Api\Finance\ProductLookupController::class, 'index']);

        // Compras
        Route::get('/compras',                 [\App\Http\Controllers\Api\Finance\CompraController::class, 'index']);
        Route::post('/compras',                [\App\Http\Controllers\Api\Finance\CompraController::class, 'store'])
            ->middleware('throttle:30,1');
        Route::get('/compras/{id}',            [\App\Http\Controllers\Api\Finance\CompraController::class, 'show']);
        Route::post('/compras/{id}/recibir',   [\App\Http\Controllers\Api\Finance\CompraController::class, 'recibir'])
            ->middleware('throttle:30,1');
        Route::delete('/compras/{id}',         [\App\Http\Controllers\Api\Finance\CompraController::class, 'destroy']);

        // CxC / CxP
        Route::get('/cxc',                     [\App\Http\Controllers\Api\Finance\CxCController::class, 'index']);
        Route::post('/cxc',                    [\App\Http\Controllers\Api\Finance\CxCController::class, 'store'])
            ->middleware('throttle:30,1');
        Route::post('/cxc/{id}/pagar',         [\App\Http\Controllers\Api\Finance\CxCController::class, 'pagar'])
            ->middleware('throttle:30,1');
        Route::delete('/cxc/{id}',             [\App\Http\Controllers\Api\Finance\CxCController::class, 'destroy']);

        Route::get('/cxp',                     [\App\Http\Controllers\Api\Finance\CxPController::class, 'index']);
        Route::post('/cxp/{id}/pagar',         [\App\Http\Controllers\Api\Finance\CxPController::class, 'pagar'])
            ->middleware('throttle:30,1');

        // Presupuestos
        Route::get('/presupuestos',            [\App\Http\Controllers\Api\Finance\PresupuestoController::class, 'index']);
        Route::post('/presupuestos',           [\App\Http\Controllers\Api\Finance\PresupuestoController::class, 'store']);
        Route::delete('/presupuestos/{id}',    [\App\Http\Controllers\Api\Finance\PresupuestoController::class, 'destroy']);

        // KPIs / Estadísticas
        Route::get('/stats',                   [\App\Http\Controllers\Api\Finance\FinanceStatsController::class, 'index']);

        // IA — Asesor financiero (rate-limited)
        Route::middleware('throttle:20,1')->group(function () {
            Route::get('/ai/informe',          [\App\Http\Controllers\Api\Finance\FinanceAIController::class, 'informe']);
            Route::get('/ai/forecast',         [\App\Http\Controllers\Api\Finance\FinanceAIController::class, 'forecast']);
            Route::get('/ai/anomalias',        [\App\Http\Controllers\Api\Finance\FinanceAIController::class, 'anomalias']);
            Route::get('/ai/compras',          [\App\Http\Controllers\Api\Finance\FinanceAIController::class, 'compras']);
        });
    });

});
