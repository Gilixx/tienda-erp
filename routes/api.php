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
            'user' => $request->user()->name,
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

        // Estadísticas (agregados para gráficas)
        Route::get('/stats', [\App\Http\Controllers\Api\Inventory\StatsController::class, 'index']);

        // Asistente IA (chatbot) — rate-limited
        Route::post('/ai/chat', [\App\Http\Controllers\Api\Inventory\StatsController::class, 'chat'])
            ->middleware('throttle:20,1');

        // Movimientos
        Route::get('/movements', [\App\Http\Controllers\Api\Inventory\MovementController::class, 'index']);
        Route::post('/movements', [\App\Http\Controllers\Api\Inventory\MovementController::class, 'store']);

        // Unidades de medida
        Route::get('/unidades-medida', fn () => response()->json(\App\Models\Inventory\UnidadMedida::where('activa', true)->orderBy('nombre')->get())
        );
        Route::post('/unidades-medida', function (\Illuminate\Http\Request $r) {
            $v = $r->validate(['nombre' => 'required|string|max:50', 'simbolo' => 'required|string|max:10|unique:unidades_medida,simbolo', 'tipo' => 'required|in:masa,volumen,longitud,pieza,tiempo,otro']);

            return response()->json(\App\Models\Inventory\UnidadMedida::create($v), 201);
        });
        Route::put('/unidades-medida/{id}', function (\Illuminate\Http\Request $r, string $id) {
            $u = \App\Models\Inventory\UnidadMedida::findOrFail($id);
            $v = $r->validate(['nombre' => 'sometimes|string|max:50', 'simbolo' => 'sometimes|string|max:10|unique:unidades_medida,simbolo,'.$id, 'activa' => 'boolean']);
            $u->update($v);

            return response()->json($u);
        });

        // Alertas de stock (reorden automático)
        Route::get('/alertas', function (\Illuminate\Http\Request $r) {
            $accesibles = \App\Models\Inventory\Almacen::accesiblesPara($r->user())->pluck('id');

            $query = \App\Models\Inventory\AlertaStock::with([
                'product:id,name,sku,min_stock,punto_reorden',
                'almacen:id,nombre,codigo',
            ])->latest()
            // Alertas de almacenes accesibles (o globales sin almacén)
                ->where(fn ($q) => $q->whereNull('almacen_id')->orWhereIn('almacen_id', $accesibles));

            if ($r->filled('almacen_id')) {
                abort_unless(
                    \App\Models\Inventory\Almacen::findOrFail($r->integer('almacen_id'))->accesiblePara($r->user()),
                    403, 'No tienes acceso a este almacén.'
                );
                $query->where('almacen_id', $r->integer('almacen_id'));
            }
            if ($r->filled('estado')) {
                $query->where('estado', $r->input('estado'));
            } else {
                $query->where('estado', 'activa');
            }
            if ($r->filled('tipo')) {
                $query->where('tipo', $r->input('tipo'));
            }

            return response()->json($query->paginate(min(100, $r->integer('per_page', 50))));
        });

        Route::patch('/alertas/{id}/resolver', function (\Illuminate\Http\Request $r, string $id) {
            $alerta = \App\Models\Inventory\AlertaStock::findOrFail($id);

            if ($alerta->almacen_id) {
                abort_unless(
                    \App\Models\Inventory\Almacen::findOrFail($alerta->almacen_id)->accesiblePara($r->user()),
                    403, 'No tienes acceso a este almacén.'
                );
            }

            $alerta->update(['estado' => 'resuelta']);

            return response()->json(['message' => 'Alerta resuelta.']);
        });

        // Almacenes
        Route::get('/almacenes', [\App\Http\Controllers\Api\Inventory\AlmacenController::class, 'index']);
        Route::post('/almacenes', [\App\Http\Controllers\Api\Inventory\AlmacenController::class, 'store']);
        Route::get('/almacenes/{id}', [\App\Http\Controllers\Api\Inventory\AlmacenController::class, 'show']);
        Route::put('/almacenes/{id}', [\App\Http\Controllers\Api\Inventory\AlmacenController::class, 'update']);
        Route::delete('/almacenes/{id}', [\App\Http\Controllers\Api\Inventory\AlmacenController::class, 'destroy']);
        // Permisos de acceso por usuario (solo dueño/admin)
        Route::get('/almacenes/{id}/usuarios', [\App\Http\Controllers\Api\Inventory\AlmacenController::class, 'usuarios']);
        Route::get('/almacenes/{id}/usuarios-disponibles', [\App\Http\Controllers\Api\Inventory\AlmacenController::class, 'usuariosDisponibles']);
        Route::post('/almacenes/{id}/usuarios', [\App\Http\Controllers\Api\Inventory\AlmacenController::class, 'agregarUsuario']);
        Route::delete('/almacenes/{id}/usuarios/{userId}', [\App\Http\Controllers\Api\Inventory\AlmacenController::class, 'quitarUsuario']);

        Route::get('/almacenes/{id}/ubicaciones', [\App\Http\Controllers\Api\Inventory\AlmacenController::class, 'ubicaciones']);
        Route::post('/almacenes/{id}/ubicaciones', [\App\Http\Controllers\Api\Inventory\AlmacenController::class, 'storeUbicacion']);
        Route::delete('/almacenes/{almacenId}/ubicaciones/{ubicacionId}', [\App\Http\Controllers\Api\Inventory\AlmacenController::class, 'destroyUbicacion']);

        // Transferencias entre almacenes
        Route::get('/transferencias', [\App\Http\Controllers\Api\Inventory\TransferenciaController::class, 'index']);
        Route::post('/transferencias', [\App\Http\Controllers\Api\Inventory\TransferenciaController::class, 'store'])
            ->middleware('throttle:30,1');
        Route::get('/transferencias/{id}', [\App\Http\Controllers\Api\Inventory\TransferenciaController::class, 'show']);
        Route::post('/transferencias/{id}/enviar', [\App\Http\Controllers\Api\Inventory\TransferenciaController::class, 'enviar'])
            ->middleware('throttle:30,1');
        Route::post('/transferencias/{id}/recibir', [\App\Http\Controllers\Api\Inventory\TransferenciaController::class, 'recibir'])
            ->middleware('throttle:30,1');
        Route::delete('/transferencias/{id}', [\App\Http\Controllers\Api\Inventory\TransferenciaController::class, 'destroy']);
    });

    // ─── Módulo POS (Punto de Venta) ──────────────────────────
    Route::middleware('service:pos')->prefix('pos')->group(function () {
        Route::get('/products/search', [\App\Http\Controllers\PosController::class, 'search']);

        Route::get('/ventas', [\App\Http\Controllers\PosController::class, 'index']);
        Route::post('/ventas', [\App\Http\Controllers\PosController::class, 'store'])
            ->middleware('throttle:30,1');
        Route::get('/ventas/{id}', [\App\Http\Controllers\PosController::class, 'show']);

        Route::get('/stats', [\App\Http\Controllers\PosController::class, 'stats']);
    });

    // ─── Panel de Administración ───────────────────────────────
    Route::middleware('admin')->prefix('admin')->group(function () {
        // Usuarios
        Route::get('/users', [\App\Http\Controllers\Api\Admin\UserController::class, 'index']);
        Route::post('/users', [\App\Http\Controllers\Api\Admin\UserController::class, 'store']);
        Route::get('/users/{user}', [\App\Http\Controllers\Api\Admin\UserController::class, 'show']);
        Route::match(['put', 'patch'], '/users/{user}', [\App\Http\Controllers\Api\Admin\UserController::class, 'update']);
        Route::delete('/users/{user}', [\App\Http\Controllers\Api\Admin\UserController::class, 'destroy']);
        Route::post('/users/{id}/restore', [\App\Http\Controllers\Api\Admin\UserController::class, 'restore']);
        Route::patch('/users/{user}/toggle-active', [\App\Http\Controllers\Api\Admin\UserController::class, 'toggleActive']);
        Route::post('/users/{user}/reset-password', [\App\Http\Controllers\Api\Admin\UserController::class, 'resetPassword'])
            ->middleware('throttle:10,1');

        // Acceso (servicios + almacenes) por usuario
        Route::get('/users/{user}/access', [\App\Http\Controllers\Api\Admin\UserAccessController::class, 'show']);
        Route::put('/users/{user}/access', [\App\Http\Controllers\Api\Admin\UserAccessController::class, 'sync']);

        // Catálogo de servicios
        Route::get('/services', [\App\Http\Controllers\Api\Admin\ServiceController::class, 'index']);
        Route::post('/services', [\App\Http\Controllers\Api\Admin\ServiceController::class, 'store']);
        Route::match(['put', 'patch'], '/services/{service}', [\App\Http\Controllers\Api\Admin\ServiceController::class, 'update']);

        // Métricas, auditoría y exportación
        Route::get('/stats', \App\Http\Controllers\Api\Admin\StatsController::class);
        Route::get('/audit-logs', [\App\Http\Controllers\Api\Admin\AuditLogController::class, 'index']);
        Route::get('/export/users', [\App\Http\Controllers\Api\Admin\ExportController::class, 'users']);
    });

});
