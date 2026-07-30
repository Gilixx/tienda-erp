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

        // Estadísticas
        Route::get('/stats', [\App\Http\Controllers\Api\Inventory\StatsController::class, 'index']);
        Route::get('/stats/report', [\App\Http\Controllers\Api\Inventory\StatsController::class, 'report'])
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

        // Lotes y series
        Route::get('/lotes', function (\Illuminate\Http\Request $r) {
            $accesibles = \App\Models\Inventory\Almacen::accesiblesPara($r->user())->pluck('id');

            $query = \App\Models\Inventory\Lote::with([
                'product:id,name,sku',
                'almacen:id,nombre,codigo',
            ])->activos()->conStock()->fefo()
            // Lotes de almacenes accesibles (o sin almacén asignado)
                ->where(fn ($q) => $q->whereNull('almacen_id')->orWhereIn('almacen_id', $accesibles));

            if ($r->filled('product_id')) {
                $query->where('product_id', $r->integer('product_id'));
            }
            if ($r->filled('almacen_id')) {
                abort_unless(
                    \App\Models\Inventory\Almacen::findOrFail($r->integer('almacen_id'))->accesiblePara($r->user()),
                    403, 'No tienes acceso a este almacén.'
                );
                $query->where('almacen_id', $r->integer('almacen_id'));
            }
            if ($r->boolean('vencidos')) {
                $query->whereNotNull('fecha_vencimiento')->where('fecha_vencimiento', '<', now());
            }
            if ($r->filled('vence_en_dias')) {
                $query->whereNotNull('fecha_vencimiento')
                    ->where('fecha_vencimiento', '<=', now()->addDays($r->integer('vence_en_dias')));
            }

            return response()->json($query->paginate(min(100, $r->integer('per_page', 50))));
        });

        Route::post('/lotes', function (\Illuminate\Http\Request $r) {
            $v = $r->validate([
                'product_id' => 'required|exists:products,id',
                'almacen_id' => 'nullable|exists:almacenes,id',
                'proveedor_id' => 'nullable|exists:proveedores,id',
                'numero_lote' => 'required|string|max:100',
                'numero_serie' => 'nullable|string|max:100',
                'fecha_fabricacion' => 'nullable|date',
                'fecha_vencimiento' => 'nullable|date',
                'cantidad_inicial' => 'required|integer|min:0',
            ]);
            $v['cantidad_actual'] = $v['cantidad_inicial'];

            return response()->json(\App\Models\Inventory\Lote::create($v), 201);
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

        // Inventario Físico
        Route::get('/inventario-fisico', [\App\Http\Controllers\Api\Inventory\InventarioFisicoController::class, 'index']);
        Route::post('/inventario-fisico', [\App\Http\Controllers\Api\Inventory\InventarioFisicoController::class, 'store'])
            ->middleware('throttle:10,1');
        Route::get('/inventario-fisico/{id}', [\App\Http\Controllers\Api\Inventory\InventarioFisicoController::class, 'show']);
        Route::patch('/inventario-fisico/{id}/items/{itemId}', [\App\Http\Controllers\Api\Inventory\InventarioFisicoController::class, 'registrarConteo']);
        Route::post('/inventario-fisico/{id}/aplicar', [\App\Http\Controllers\Api\Inventory\InventarioFisicoController::class, 'aplicar'])
            ->middleware('throttle:5,1');
        Route::delete('/inventario-fisico/{id}', [\App\Http\Controllers\Api\Inventory\InventarioFisicoController::class, 'destroy']);
    });

    // ─── Módulo de Finanzas ──────────────────────────────────
    Route::middleware('service:finance')->prefix('finance')->group(function () {

        // Centros de costo
        Route::get('/centros-costo', fn () => response()->json(\App\Models\Finance\CentroCosto::where('activo', true)->orderBy('codigo')->get())
        );
        Route::post('/centros-costo', function (\Illuminate\Http\Request $r) {
            $v = $r->validate(['codigo' => 'required|string|max:20|unique:centros_costo,codigo', 'nombre' => 'required|string|max:100', 'descripcion' => 'nullable|string|max:500']);

            return response()->json(\App\Models\Finance\CentroCosto::create($v), 201);
        });
        Route::put('/centros-costo/{id}', function (\Illuminate\Http\Request $r, string $id) {
            $c = \App\Models\Finance\CentroCosto::findOrFail($id);
            $v = $r->validate(['codigo' => 'sometimes|string|max:20|unique:centros_costo,codigo,'.$id, 'nombre' => 'sometimes|string|max:100', 'descripcion' => 'nullable|string|max:500', 'activo' => 'boolean']);
            $c->update($v);

            return response()->json($c);
        });
        Route::delete('/centros-costo/{id}', function (string $id) {
            $c = \App\Models\Finance\CentroCosto::findOrFail($id);
            $c->update(['activo' => false]);

            return response()->json(['message' => 'Centro de costo desactivado.']);
        });

        // Catálogos
        Route::get('/monedas', [\App\Http\Controllers\Api\Finance\MonedaController::class, 'index']);
        Route::post('/monedas', [\App\Http\Controllers\Api\Finance\MonedaController::class, 'store']);
        Route::put('/monedas/{id}', [\App\Http\Controllers\Api\Finance\MonedaController::class, 'update']);
        Route::delete('/monedas/{id}', [\App\Http\Controllers\Api\Finance\MonedaController::class, 'destroy']);

        Route::get('/tipos-cambio', [\App\Http\Controllers\Api\Finance\TipoCambioController::class, 'index']);
        Route::post('/tipos-cambio', [\App\Http\Controllers\Api\Finance\TipoCambioController::class, 'store']);
        Route::delete('/tipos-cambio/{id}', [\App\Http\Controllers\Api\Finance\TipoCambioController::class, 'destroy']);

        Route::get('/impuestos', [\App\Http\Controllers\Api\Finance\ImpuestoController::class, 'index']);
        Route::post('/impuestos', [\App\Http\Controllers\Api\Finance\ImpuestoController::class, 'store']);
        Route::put('/impuestos/{id}', [\App\Http\Controllers\Api\Finance\ImpuestoController::class, 'update']);
        Route::delete('/impuestos/{id}', [\App\Http\Controllers\Api\Finance\ImpuestoController::class, 'destroy']);

        Route::get('/categorias', [\App\Http\Controllers\Api\Finance\CategoriaController::class, 'index']);
        Route::post('/categorias', [\App\Http\Controllers\Api\Finance\CategoriaController::class, 'store']);
        Route::put('/categorias/{id}', [\App\Http\Controllers\Api\Finance\CategoriaController::class, 'update']);
        Route::delete('/categorias/{id}', [\App\Http\Controllers\Api\Finance\CategoriaController::class, 'destroy']);

        // Cuentas y transferencias
        Route::get('/cuentas', [\App\Http\Controllers\Api\Finance\CuentaController::class, 'index']);
        Route::post('/cuentas', [\App\Http\Controllers\Api\Finance\CuentaController::class, 'store']);
        Route::get('/cuentas/{id}', [\App\Http\Controllers\Api\Finance\CuentaController::class, 'show']);
        Route::put('/cuentas/{id}', [\App\Http\Controllers\Api\Finance\CuentaController::class, 'update']);
        Route::delete('/cuentas/{id}', [\App\Http\Controllers\Api\Finance\CuentaController::class, 'destroy']);
        Route::post('/cuentas/transferir', [\App\Http\Controllers\Api\Finance\CuentaController::class, 'transferir'])
            ->middleware('throttle:30,1');

        // Proveedores
        Route::apiResource('proveedores', \App\Http\Controllers\Api\Finance\ProveedorController::class);

        // Transacciones (writes con throttle más estricto)
        Route::get('/transacciones', [\App\Http\Controllers\Api\Finance\TransaccionController::class, 'index']);
        Route::post('/transacciones', [\App\Http\Controllers\Api\Finance\TransaccionController::class, 'store'])
            ->middleware('throttle:30,1');
        Route::get('/transacciones/{id}', [\App\Http\Controllers\Api\Finance\TransaccionController::class, 'show']);
        Route::delete('/transacciones/{id}', [\App\Http\Controllers\Api\Finance\TransaccionController::class, 'destroy'])
            ->middleware('throttle:20,1');

        // Lookup productos (para compras y transacciones)
        Route::get('/products', [\App\Http\Controllers\Api\Finance\ProductLookupController::class, 'index']);

        // Compras
        Route::get('/compras', [\App\Http\Controllers\Api\Finance\CompraController::class, 'index']);
        Route::post('/compras', [\App\Http\Controllers\Api\Finance\CompraController::class, 'store'])
            ->middleware('throttle:30,1');
        Route::get('/compras/{id}', [\App\Http\Controllers\Api\Finance\CompraController::class, 'show']);
        Route::post('/compras/{id}/recibir', [\App\Http\Controllers\Api\Finance\CompraController::class, 'recibir'])
            ->middleware('throttle:30,1');
        Route::delete('/compras/{id}', [\App\Http\Controllers\Api\Finance\CompraController::class, 'destroy']);

        // CxC / CxP
        Route::get('/cxc', [\App\Http\Controllers\Api\Finance\CxCController::class, 'index']);
        Route::post('/cxc', [\App\Http\Controllers\Api\Finance\CxCController::class, 'store'])
            ->middleware('throttle:30,1');
        Route::post('/cxc/{id}/pagar', [\App\Http\Controllers\Api\Finance\CxCController::class, 'pagar'])
            ->middleware('throttle:30,1');
        Route::delete('/cxc/{id}', [\App\Http\Controllers\Api\Finance\CxCController::class, 'destroy']);

        Route::get('/cxp', [\App\Http\Controllers\Api\Finance\CxPController::class, 'index']);
        Route::post('/cxp/{id}/pagar', [\App\Http\Controllers\Api\Finance\CxPController::class, 'pagar'])
            ->middleware('throttle:30,1');

        // Devoluciones de compra
        Route::get('/devoluciones-compra', [\App\Http\Controllers\Api\Finance\DevolucionCompraController::class, 'index']);
        Route::post('/devoluciones-compra', [\App\Http\Controllers\Api\Finance\DevolucionCompraController::class, 'store'])
            ->middleware('throttle:30,1');
        Route::get('/devoluciones-compra/{id}', [\App\Http\Controllers\Api\Finance\DevolucionCompraController::class, 'show']);

        // Devoluciones de venta
        Route::get('/devoluciones-venta', [\App\Http\Controllers\Api\Finance\DevolucionVentaController::class, 'index']);
        Route::post('/devoluciones-venta', [\App\Http\Controllers\Api\Finance\DevolucionVentaController::class, 'store'])
            ->middleware('throttle:30,1');
        Route::get('/devoluciones-venta/{id}', [\App\Http\Controllers\Api\Finance\DevolucionVentaController::class, 'show']);

        // Presupuestos
        Route::get('/presupuestos', [\App\Http\Controllers\Api\Finance\PresupuestoController::class, 'index']);
        Route::post('/presupuestos', [\App\Http\Controllers\Api\Finance\PresupuestoController::class, 'store']);
        Route::delete('/presupuestos/{id}', [\App\Http\Controllers\Api\Finance\PresupuestoController::class, 'destroy']);

        // Conciliación bancaria
        Route::get('/conciliaciones', [\App\Http\Controllers\Api\Finance\ConciliacionController::class, 'index']);
        Route::post('/conciliaciones', [\App\Http\Controllers\Api\Finance\ConciliacionController::class, 'store'])
            ->middleware('throttle:10,1');
        Route::get('/conciliaciones/{id}', [\App\Http\Controllers\Api\Finance\ConciliacionController::class, 'show']);
        Route::patch('/conciliaciones/{id}/items/{itemId}', [\App\Http\Controllers\Api\Finance\ConciliacionController::class, 'toggleItem']);
        Route::post('/conciliaciones/{id}/cerrar', [\App\Http\Controllers\Api\Finance\ConciliacionController::class, 'cerrar'])
            ->middleware('throttle:10,1');

        // Reportes financieros (read-only, caché 15 min)
        Route::middleware('throttle:20,1')->group(function () {
            Route::get('/reportes/pyg', [\App\Http\Controllers\Api\Finance\ReporteController::class, 'pyg']);
            Route::get('/reportes/flujo-caja', [\App\Http\Controllers\Api\Finance\ReporteController::class, 'flujoCaja']);
            Route::get('/reportes/cuentas-saldo', [\App\Http\Controllers\Api\Finance\ReporteController::class, 'cuentasSaldo']);
            Route::get('/reportes/aging-cxc', [\App\Http\Controllers\Api\Finance\ReporteController::class, 'agingCxC']);
            Route::get('/reportes/aging-cxp', [\App\Http\Controllers\Api\Finance\ReporteController::class, 'agingCxP']);
        });

        // KPIs / Estadísticas
        Route::get('/stats', [\App\Http\Controllers\Api\Finance\FinanceStatsController::class, 'index']);

        // Activos fijos
        Route::get('/activos-fijos', [\App\Http\Controllers\Api\Finance\ActivoFijoController::class, 'index']);
        Route::post('/activos-fijos', [\App\Http\Controllers\Api\Finance\ActivoFijoController::class, 'store']);
        Route::get('/activos-fijos/{id}', [\App\Http\Controllers\Api\Finance\ActivoFijoController::class, 'show']);
        Route::put('/activos-fijos/{id}', [\App\Http\Controllers\Api\Finance\ActivoFijoController::class, 'update']);

        // Períodos contables
        Route::get('/periodos', [\App\Http\Controllers\Api\Finance\PeriodoContableController::class, 'index']);
        Route::post('/periodos', [\App\Http\Controllers\Api\Finance\PeriodoContableController::class, 'store']);
        Route::post('/periodos/{id}/cerrar', [\App\Http\Controllers\Api\Finance\PeriodoContableController::class, 'cerrar'])
            ->middleware('throttle:5,1');
        Route::post('/periodos/{id}/reabrir', [\App\Http\Controllers\Api\Finance\PeriodoContableController::class, 'reabrir'])
            ->middleware('throttle:5,1');

        // DIOT
        Route::get('/reportes/diot', [\App\Http\Controllers\Api\Finance\DiotController::class, 'index'])
            ->middleware('throttle:10,1');

        // Catálogo de cuentas contables
        Route::get('/cuentas-contables', [\App\Http\Controllers\Api\Finance\CuentaContableController::class, 'index']);
        Route::get('/cuentas-contables/flat', [\App\Http\Controllers\Api\Finance\CuentaContableController::class, 'flat']);
        Route::post('/cuentas-contables', [\App\Http\Controllers\Api\Finance\CuentaContableController::class, 'store']);
        Route::put('/cuentas-contables/{id}', [\App\Http\Controllers\Api\Finance\CuentaContableController::class, 'update']);

        // Asientos contables
        Route::get('/asientos', [\App\Http\Controllers\Api\Finance\CuentaContableController::class, 'indexAsientos']);
        Route::post('/asientos', [\App\Http\Controllers\Api\Finance\CuentaContableController::class, 'storeAsiento'])
            ->middleware('throttle:30,1');
        Route::get('/asientos/{id}', [\App\Http\Controllers\Api\Finance\CuentaContableController::class, 'showAsiento']);
        Route::delete('/asientos/{id}', [\App\Http\Controllers\Api\Finance\CuentaContableController::class, 'destroyAsiento']);
        Route::post('/asientos/{id}/cancelar', [\App\Http\Controllers\Api\Finance\CuentaContableController::class, 'cancelarAsiento']);

        // IA — Asesor financiero (rate-limited)
        Route::middleware('throttle:20,1')->group(function () {
            Route::get('/ai/informe', [\App\Http\Controllers\Api\Finance\FinanceAIController::class, 'informe']);
            Route::get('/ai/forecast', [\App\Http\Controllers\Api\Finance\FinanceAIController::class, 'forecast']);
            Route::get('/ai/anomalias', [\App\Http\Controllers\Api\Finance\FinanceAIController::class, 'anomalias']);
            Route::get('/ai/compras', [\App\Http\Controllers\Api\Finance\FinanceAIController::class, 'compras']);
        });
    });

});
