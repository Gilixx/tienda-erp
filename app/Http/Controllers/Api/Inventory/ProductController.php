<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Api\Inventory\Concerns\AuthorizesAlmacen;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    use AuthorizesAlmacen;

    public function index(Request $request)
    {
        $user = $request->user();

        // Cuando se pide un almacén, el campo `stock` refleja la existencia en
        // ese almacén (product_stock.cantidad); si no, es el stock global.
        if ($request->filled('almacen_id')) {
            $almacenId = $request->integer('almacen_id');
            $this->authorizeAlmacen($almacenId);

            $query = Product::with('category')
                ->accesiblesPara($user)
                ->leftJoin('product_stock', function ($join) use ($almacenId) {
                    $join->on('product_stock.product_id', '=', 'products.id')
                        ->where('product_stock.almacen_id', '=', $almacenId);
                })
                ->select('products.*', DB::raw('COALESCE(product_stock.cantidad, 0) as stock'))
                ->latest('products.created_at');
        } else {
            $query = Product::with('category')->accesiblesPara($user)->latest();
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
        $userId = $request->user()->id;

        $validated = $request->validate([
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('created_by', $userId)],
            'name' => 'required|string|max:255',
            'sku' => ['required', 'string', 'max:100',
                Rule::unique('products', 'sku')->where('created_by', $userId)],
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0|max:9999999',
            'cost' => 'required|numeric|min:0|max:9999999',
            'min_stock' => 'required|integer|min:0|max:99999',
        ]);

        $validated['created_by'] = $userId;
        $product = Product::create($validated);

        return response()->json($product->load('category'), 201);
    }

    public function show(Request $request, string $id)
    {
        $product = Product::accesiblesPara($request->user())
            ->with('category', 'movements.user')
            ->findOrFail($id);

        return response()->json($product);
    }

    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);
        $this->authorizeDueno($product, $request);

        $validated = $request->validate([
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('created_by', $product->created_by)],
            'name' => 'required|string|max:255',
            'sku' => ['required', 'string', 'max:100',
                Rule::unique('products', 'sku')->where('created_by', $product->created_by)->ignore($product->id)],
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0|max:9999999',
            'cost' => 'required|numeric|min:0|max:9999999',
            'min_stock' => 'required|integer|min:0|max:99999',
            'is_active' => 'boolean',
        ]);

        $product->update($validated);

        return response()->json($product->load('category'));
    }

    public function destroy(Request $request, string $id)
    {
        $product = Product::findOrFail($id);
        $this->authorizeDueno($product, $request);
        $product->delete();

        return response()->json(['message' => 'Producto eliminado correctamente']);
    }

    /** Aborta con 403 si el usuario no es dueño del producto (ni admin). */
    private function authorizeDueno(Product $product, Request $request): void
    {
        $user = $request->user();
        if (! $user->isAdmin() && $product->created_by !== $user->id) {
            abort(403, 'Solo el dueño del producto puede modificarlo.');
        }
    }
}
