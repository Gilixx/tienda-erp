<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Inventory\Almacen;
use App\Models\Inventory\InventarioFisico;
use App\Models\Inventory\InventarioFisicoItem;
use App\Models\Inventory\ProductStock;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventarioFisicoController extends Controller
{
    /** GET /api/inventory/inventario-fisico */
    public function index(Request $request): JsonResponse
    {
        $query = InventarioFisico::with(['almacen:id,nombre,codigo', 'user:id,name'])
            ->latest();

        if ($request->filled('almacen_id')) {
            $query->where('almacen_id', $request->integer('almacen_id'));
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        $perPage = min(50, max(10, $request->integer('per_page', 15)));

        return response()->json($query->paginate($perPage));
    }

    /** GET /api/inventory/inventario-fisico/{id} */
    public function show(string $id): JsonResponse
    {
        $sesion = InventarioFisico::with([
            'almacen', 'user:id,name', 'aplicadoPor:id,name',
            'items.product:id,name,sku,cost',
            'items.contadoPor:id,name',
        ])->findOrFail($id);

        return response()->json($sesion);
    }

    /**
     * POST /api/inventory/inventario-fisico
     * Abre una sesión y hace snapshot del stock actual del almacén.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'almacen_id' => 'required|exists:almacenes,id',
            'notas'      => 'nullable|string|max:1000',
        ]);

        // No permitir dos sesiones abiertas para el mismo almacén
        $sesiónAbierta = InventarioFisico::where('almacen_id', $validated['almacen_id'])
            ->whereIn('estado', ['abierto', 'cerrado'])
            ->exists();

        if ($sesiónAbierta) {
            return response()->json([
                'error' => 'Ya existe una sesión de inventario activa para este almacén. Ciérrala antes de abrir otra.',
            ], 422);
        }

        try {
            return DB::transaction(function () use ($validated) {
                $sesion = InventarioFisico::create([
                    'almacen_id'    => $validated['almacen_id'],
                    'user_id'       => auth()->id(),
                    'estado'        => 'abierto',
                    'fecha_apertura' => now(),
                    'notas'         => $validated['notas'] ?? null,
                ]);

                // Snapshot del stock actual del almacén
                $stocks = ProductStock::with('product:id,name,sku,cost')
                    ->where('almacen_id', $validated['almacen_id'])
                    ->get();

                foreach ($stocks as $stockRow) {
                    InventarioFisicoItem::create([
                        'inventario_fisico_id' => $sesion->id,
                        'product_id'           => $stockRow->product_id,
                        'cantidad_teorica'     => $stockRow->cantidad,
                        'cantidad_contada'     => null,
                        'costo_unit'           => $stockRow->product->cost ?? 0,
                    ]);
                }

                return response()->json(
                    $sesion->load(['almacen', 'items.product:id,name,sku']),
                    201
                );
            });
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Error al abrir la sesión: ' . $e->getMessage()], 500);
        }
    }

    /**
     * PATCH /api/inventory/inventario-fisico/{id}/items/{itemId}
     * Registra la cantidad contada para un item.
     */
    public function registrarConteo(Request $request, string $id, string $itemId): JsonResponse
    {
        $sesion = InventarioFisico::findOrFail($id);

        if ($sesion->estado !== 'abierto') {
            return response()->json(['error' => 'Solo se puede registrar conteo en sesiones abiertas.'], 422);
        }

        $validated = $request->validate([
            'cantidad_contada' => 'required|integer|min:0',
        ]);

        $item = InventarioFisicoItem::where('inventario_fisico_id', $id)
            ->findOrFail($itemId);

        $item->update([
            'cantidad_contada' => $validated['cantidad_contada'],
            'contado_por'      => auth()->id(),
            'contado_en'       => now(),
        ]);

        return response()->json($item->fresh());
    }

    /**
     * POST /api/inventory/inventario-fisico/{id}/aplicar
     * Calcula diferencias y genera movimientos de ajuste. Cierra la sesión.
     */
    public function aplicar(string $id): JsonResponse
    {
        try {
            return DB::transaction(function () use ($id) {
                $sesion = InventarioFisico::with('items')->lockForUpdate()->findOrFail($id);

                if ($sesion->estado === 'aplicado') {
                    return response()->json(['error' => 'Esta sesión ya fue aplicada.'], 422);
                }
                if ($sesion->estado !== 'abierto' && $sesion->estado !== 'cerrado') {
                    return response()->json(['error' => 'Estado de sesión inválido para aplicar.'], 422);
                }

                $diferenciaTotal = 0;

                foreach ($sesion->items as $item) {
                    // Si no fue contado, asumir igual al teórico (diferencia 0)
                    $contada = $item->cantidad_contada ?? $item->cantidad_teorica;
                    $diferencia = $contada - $item->cantidad_teorica;

                    if ($diferencia === 0) {
                        continue;
                    }

                    // Aplicar ajuste en product_stock
                    $stockRow = ProductStock::lockForUpdate()
                        ->where('product_id', $item->product_id)
                        ->where('almacen_id', $sesion->almacen_id)
                        ->first();

                    if ($stockRow) {
                        $stockRow->increment('cantidad', $diferencia);
                        $stockRow->touch();
                    } else {
                        ProductStock::create([
                            'product_id' => $item->product_id,
                            'almacen_id' => $sesion->almacen_id,
                            'cantidad'   => max(0, $diferencia),
                        ]);
                    }

                    // Mantener products.stock en sincronía
                    Product::where('id', $item->product_id)
                        ->increment('stock', $diferencia);

                    // Movimiento de ajuste
                    InventoryMovement::create([
                        'product_id'  => $item->product_id,
                        'almacen_id'  => $sesion->almacen_id,
                        'user_id'     => auth()->id(),
                        'type'        => 'adjustment',
                        'quantity'    => $diferencia,
                        'reference'   => 'INV-FISICO-' . $sesion->id,
                        'notes'       => 'Ajuste por inventario físico. Teórico: ' . $item->cantidad_teorica . ', contado: ' . $contada,
                    ]);

                    $diferenciaTotal += abs($diferencia * (float) $item->costo_unit);
                }

                $sesion->update([
                    'estado'                 => 'aplicado',
                    'fecha_cierre'           => now(),
                    'fecha_aplicacion'       => now(),
                    'aplicado_por'           => auth()->id(),
                    'diferencia_total_valor' => round($diferenciaTotal, 4),
                ]);

                return response()->json($sesion->fresh()->load(['almacen', 'items.product:id,name,sku']));
            });
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Error al aplicar el inventario: ' . $e->getMessage()], 500);
        }
    }

    /** DELETE /api/inventory/inventario-fisico/{id} — solo sesiones abiertas */
    public function destroy(string $id): JsonResponse
    {
        $sesion = InventarioFisico::findOrFail($id);

        if ($sesion->estado === 'aplicado') {
            return response()->json(['error' => 'No se puede eliminar un inventario ya aplicado.'], 422);
        }

        $sesion->delete();

        return response()->json(['message' => 'Sesión de inventario eliminada.']);
    }
}
