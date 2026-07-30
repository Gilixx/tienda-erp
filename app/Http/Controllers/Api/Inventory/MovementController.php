<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Api\Inventory\Concerns\AuthorizesAlmacen;
use App\Http\Controllers\Controller;
use App\Models\Inventory\ProductStock;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MovementController extends Controller
{
    use AuthorizesAlmacen;

    /** GET /api/inventory/movements */
    public function index(Request $request): JsonResponse
    {
        $query = InventoryMovement::with(['product:id,name,sku', 'user:id,name', 'almacen:id,nombre,codigo'])
            ->latest()
            ->limit(200);

        if ($request->filled('almacen_id')) {
            $this->authorizeAlmacen($request->integer('almacen_id'));
            $query->where('almacen_id', $request->integer('almacen_id'));
        } else {
            // Limitar a almacenes accesibles para el usuario
            $query->whereIn('almacen_id', $this->accesibleAlmacenIds());
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        return response()->json($query->get());
    }

    /** POST /api/inventory/movements */
    public function store(Request $request): JsonResponse
    {
        $isAdjustment = $request->input('type') === 'adjustment';

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'almacen_id' => 'required|exists:almacenes,id',
            'type' => 'required|in:in,out,adjustment',
            'quantity' => ['required', 'integer', $isAdjustment ? 'min:-999999' : 'min:1'],
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->authorizeAlmacen($validated['almacen_id']);

        try {
            DB::beginTransaction();

            // Lock para evitar race conditions
            $product = Product::lockForUpdate()->findOrFail($validated['product_id']);

            $stockRow = ProductStock::lockForUpdate()
                ->where('product_id', $validated['product_id'])
                ->where('almacen_id', $validated['almacen_id'])
                ->first();

            $stockEnAlmacen = $stockRow?->cantidad ?? 0;

            if ($validated['type'] === 'out') {
                if ($stockEnAlmacen < $validated['quantity']) {
                    DB::rollBack();

                    return response()->json([
                        'error' => "No hay suficiente stock en este almacén. Disponible: {$stockEnAlmacen}.",
                    ], 422);
                }
                $quantityChange = -$validated['quantity'];

            } elseif ($validated['type'] === 'adjustment') {
                $nuevoStock = $stockEnAlmacen + $validated['quantity'];
                if ($nuevoStock < 0) {
                    DB::rollBack();

                    return response()->json(['error' => 'El ajuste resultaría en stock negativo en este almacén.'], 422);
                }
                $quantityChange = $validated['quantity'];

            } else { // 'in'
                $quantityChange = $validated['quantity'];
            }

            // Actualizar o crear registro de stock por almacén
            if ($stockRow) {
                $stockRow->increment('cantidad', $quantityChange);
                $stockRow->touch();
            } else {
                ProductStock::create([
                    'product_id' => $validated['product_id'],
                    'almacen_id' => $validated['almacen_id'],
                    'cantidad' => $quantityChange,
                ]);
            }

            // Mantener products.stock (total global) en sincronía
            $product->increment('stock', $quantityChange);

            $movement = InventoryMovement::create([
                'product_id' => $product->id,
                'almacen_id' => $validated['almacen_id'],
                'user_id' => auth()->id(),
                'type' => $validated['type'],
                'quantity' => $quantityChange,
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            DB::commit();

            return response()->json(
                $movement->load(['product:id,name,sku', 'user:id,name', 'almacen:id,nombre,codigo']),
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            return response()->json(['error' => 'Error al procesar el movimiento. Inténtalo de nuevo.'], 500);
        }
    }
}
