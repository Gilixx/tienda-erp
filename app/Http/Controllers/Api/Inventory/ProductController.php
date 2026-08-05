<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Api\Inventory\Concerns\AuthorizesAlmacen;
use App\Http\Controllers\Controller;
use App\Models\Inventory\ProductStock;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\Inventory\VerificarStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    use AuthorizesAlmacen;

    public function index(Request $request)
    {
        $user = $request->user();

        // Cuando se pide un almacén, se listan SOLO los productos presentes en
        // ese almacén (tienen fila en product_stock). Así cada almacén tiene su
        // catálogo independiente; un producto llega a otro almacén vía traspaso.
        if ($request->filled('almacen_id')) {
            $almacenId = $request->integer('almacen_id');
            $this->authorizeAlmacen($almacenId);

            $query = Product::with('category')
                ->accesiblesPara($user)
                ->join('product_stock', function ($join) use ($almacenId) {
                    $join->on('product_stock.product_id', '=', 'products.id')
                        ->where('product_stock.almacen_id', '=', $almacenId);
                })
                ->select('products.*', DB::raw('product_stock.cantidad as stock'))
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
            'almacen_id' => 'required|integer|exists:almacenes,id',
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('created_by', $userId)],
            'name' => 'required|string|max:255',
            'sku' => ['required', 'string', 'max:100',
                Rule::unique('products', 'sku')->where('created_by', $userId)],
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0|max:9999999',
            'cost' => 'required|numeric|min:0|max:9999999',
            'min_stock' => 'required|integer|min:0|max:99999',
            'stock_inicial' => 'nullable|integer|min:0|max:9999999',
        ]);

        // El producto se da de alta dentro del almacén seleccionado.
        $this->authorizeAlmacen($validated['almacen_id']);
        $inicial = (int) ($validated['stock_inicial'] ?? 0);

        $product = DB::transaction(function () use ($validated, $userId, $inicial) {
            $product = Product::create([
                'category_id' => $validated['category_id'] ?? null,
                'name' => $validated['name'],
                'sku' => $validated['sku'],
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'],
                'cost' => $validated['cost'],
                'min_stock' => $validated['min_stock'],
                'stock' => $inicial,
                'created_by' => $userId,
            ]);

            ProductStock::create([
                'product_id' => $product->id,
                'almacen_id' => $validated['almacen_id'],
                'cantidad' => $inicial,
            ]);

            if ($inicial > 0) {
                InventoryMovement::create([
                    'product_id' => $product->id,
                    'almacen_id' => $validated['almacen_id'],
                    'user_id' => auth()->id(),
                    'type' => 'in',
                    'quantity' => $inicial,
                    'reference' => 'Alta de producto',
                ]);
            }

            return $product;
        });

        app(VerificarStockService::class)->verificar((int) $validated['almacen_id'], [$product->id]);

        // Devolver el stock del almacén para que el frontend lo muestre.
        $product->stock = $inicial;

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
