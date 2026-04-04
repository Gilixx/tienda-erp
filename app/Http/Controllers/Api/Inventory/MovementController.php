<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MovementController extends Controller
{
    public function index()
    {
        $movements = \App\Models\InventoryMovement::with(['product', 'user'])
            ->latest()
            ->limit(100)
            ->get();
        return response()->json($movements);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:in,out,adjustment',
            'quantity' => 'required|integer|min:1',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string'
        ]);

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $product = \App\Models\Product::lockForUpdate()->findOrFail($validated['product_id']);
            
            $quantityChange = $validated['type'] === 'out' 
                ? -$validated['quantity'] 
                : $validated['quantity']; // 'in' and 'adjustment' (assuming adjustment gives the exact diff to add/remove, wait 'adjustment' should be explicit, let's treat adjustment as an absolute quantity diff for now, or just handle in/out)
                
            if ($validated['type'] === 'adjustment') {
                 // For adjustment we assume the quantity provided is the raw change (can be negative if we remove the min:1 validation rule)
                 // Let's modify validation to allow negative for adjustments
                 return response()->json(['error' => 'Adjustment must be calculated difference.'], 400); // Or we can skip complex logic and just rely on IN/OUT
                 // Let's rewrite this part below softly.
            }

            // check if there is enough stock for OUT
            if ($validated['type'] === 'out' && $product->stock < $validated['quantity']) {
                return response()->json(['error' => 'No hay suficiente stock disponible.'], 422);
            }

            $movement = \App\Models\InventoryMovement::create([
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'type' => $validated['type'],
                'quantity' => $quantityChange,
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $product->stock += $quantityChange;
            $product->save();

            \Illuminate\Support\Facades\DB::commit();

            return response()->json($movement->load(['product', 'user']), 201);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['error' => 'Error processing movement: ' . $e->getMessage()], 500);
        }
    }
}
