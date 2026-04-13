<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MovementController extends Controller
{
    public function index()
    {
        $movements = InventoryMovement::with(['product', 'user'])
            ->latest()
            ->limit(200)
            ->get();

        return response()->json($movements);
    }

    public function store(Request $request)
    {
        $isAdjustment = $request->input('type') === 'adjustment';

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'type'       => 'required|in:in,out,adjustment',
            'quantity'   => ['required', 'integer', $isAdjustment ? 'min:-999999' : 'min:1'],
            'reference'  => 'nullable|string|max:255',
            'notes'      => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $product = Product::lockForUpdate()->findOrFail($validated['product_id']);

            if ($validated['type'] === 'out') {
                if ($product->stock < $validated['quantity']) {
                    DB::rollBack();
                    return response()->json(['error' => 'No hay suficiente stock disponible.'], 422);
                }
                $quantityChange = -$validated['quantity'];

            } elseif ($validated['type'] === 'adjustment') {
                $newStock = $product->stock + $validated['quantity'];
                if ($newStock < 0) {
                    DB::rollBack();
                    return response()->json(['error' => 'El ajuste resultaría en stock negativo.'], 422);
                }
                $quantityChange = $validated['quantity'];

            } else { // 'in'
                $quantityChange = $validated['quantity'];
            }

            $movement = InventoryMovement::create([
                'product_id' => $product->id,
                'user_id'    => auth()->id(),
                'type'       => $validated['type'],
                'quantity'   => $quantityChange,
                'reference'  => $validated['reference'] ?? null,
                'notes'      => $validated['notes'] ?? null,
            ]);

            $product->increment('stock', $quantityChange);

            DB::commit();

            return response()->json($movement->load(['product', 'user']), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return response()->json(['error' => 'Error al procesar el movimiento. Inténtalo de nuevo.'], 500);
        }
    }
}
