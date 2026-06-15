<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Api\Inventory\Concerns\AuthorizesAlmacen;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    use AuthorizesAlmacen;

    public function index(Request $request)
    {
        // Cuando se pide un almacén, el campo `stock` refleja la existencia en
        // ese almacén (product_stock.cantidad); si no, es el stock global.
        if ($request->filled('almacen_id')) {
            $almacenId = $request->integer('almacen_id');
            $this->authorizeAlmacen($almacenId);

            $query = Product::with('category')
                ->leftJoin('product_stock', function ($join) use ($almacenId) {
                    $join->on('product_stock.product_id', '=', 'products.id')
                        ->where('product_stock.almacen_id', '=', $almacenId);
                })
                ->select('products.*', DB::raw('COALESCE(product_stock.cantidad, 0) as stock'))
                ->latest('products.created_at');
        } else {
            $query = Product::with('category')->latest();
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0|max:9999999',
            'cost' => 'required|numeric|min:0|max:9999999',
            'min_stock' => 'required|integer|min:0|max:99999',
        ]);

        $product = Product::create($validated);

        return response()->json($product->load('category'), 201);
    }

    public function show(string $id)
    {
        $product = Product::with('category', 'movements.user')->findOrFail($id);

        return response()->json($product);
    }

    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku,'.$product->id,
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0|max:9999999',
            'cost' => 'required|numeric|min:0|max:9999999',
            'min_stock' => 'required|integer|min:0|max:99999',
            'is_active' => 'boolean',
        ]);

        $product->update($validated);

        return response()->json($product->load('category'));
    }

    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Producto eliminado correctamente']);
    }
}
