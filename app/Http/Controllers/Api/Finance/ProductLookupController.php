<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * Lookup ligero de productos para módulos de finanzas (compras, transacciones, etc.).
 * Accesible bajo service:finance sin requerir service:inventory.
 */
class ProductLookupController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::where('is_active', true)->orderBy('name');

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('sku', 'like', "%{$s}%");
            });
        }

        return response()->json(
            $query->limit(200)->get(['id', 'name', 'sku', 'cost', 'price', 'stock'])
        );
    }
}
