<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Api\Inventory\Concerns\AuthorizesAlmacen;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Inventory\ProductStock;
use App\Models\Product;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use AuthorizesAlmacen;

    /** GET /api/inventory/categories?almacen_id= — categorías del almacén. */
    public function index(Request $request)
    {
        $request->validate(['almacen_id' => 'required|integer|exists:almacenes,id']);
        $almacenId = $request->integer('almacen_id');
        $this->authorizeAlmacen($almacenId);

        // Cualquier usuario con acceso al almacén ve TODAS sus categorías, sin
        // importar quién las creó (los almacenes se comparten entre usuarios).
        return response()->json(
            Category::where('almacen_id', $almacenId)
                ->withCount('stocks as products_count')
                ->orderBy('name')
                ->get()
        );
    }

    /** POST /api/inventory/categories — crea una categoría en un almacén. */
    public function store(Request $request)
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'almacen_id' => 'required|integer|exists:almacenes,id',
            'name' => ['required', 'string', 'max:255'],
            'description' => 'nullable|string|max:1000',
        ]);

        $this->authorizeAlmacen($validated['almacen_id']);
        $this->assertNombreUnico($validated['name'], $validated['almacen_id']);

        $category = Category::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'created_by' => $userId,
            'almacen_id' => $validated['almacen_id'],
        ]);
        $category->products_count = 0;

        return response()->json($category, 201);
    }

    /** PUT /api/inventory/categories/{id} */
    public function update(Request $request, string $id)
    {
        $category = Category::findOrFail($id);
        $this->authorizeAlmacen($category->almacen_id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => 'nullable|string|max:1000',
        ]);

        $this->assertNombreUnico($validated['name'], $category->almacen_id, $category->id);
        $category->update($validated);

        return response()->json($category->loadCount('stocks as products_count'));
    }

    /** DELETE /api/inventory/categories/{id} */
    public function destroy(Request $request, string $id)
    {
        $category = Category::findOrFail($id);
        $this->authorizeAlmacen($category->almacen_id);
        $category->delete();

        return response()->json(['message' => 'Categoría eliminada correctamente']);
    }

    /**
     * GET /api/inventory/categories/{id}/productos
     * Productos actualmente en esta categoría (en su almacén).
     */
    public function productos(Request $request, string $id)
    {
        $category = Category::findOrFail($id);
        $this->authorizeAlmacen($category->almacen_id);

        $productos = Product::query()
            ->join('product_stock', 'product_stock.product_id', '=', 'products.id')
            ->where('product_stock.almacen_id', $category->almacen_id)
            ->where('product_stock.category_id', $category->id)
            ->orderBy('products.name')
            ->get(['products.id', 'products.name', 'products.sku', 'product_stock.cantidad as stock']);

        return response()->json($productos);
    }

    /**
     * POST /api/inventory/categories/{id}/productos { product_ids: [] }
     * Asigna esta categoría a productos que existen en el almacén de la categoría.
     */
    public function agregarProductos(Request $request, string $id)
    {
        $category = Category::findOrFail($id);
        $this->authorizeAlmacen($category->almacen_id);

        $validated = $request->validate([
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'integer',
        ]);

        // Solo afecta productos con fila en product_stock del almacén de la categoría.
        $afectados = ProductStock::where('almacen_id', $category->almacen_id)
            ->whereIn('product_id', $validated['product_ids'])
            ->update(['category_id' => $category->id]);

        return response()->json([
            'message' => 'Productos actualizados.',
            'asignados' => $afectados,
        ]);
    }

    /**
     * DELETE /api/inventory/categories/{id}/productos/{productId}
     * Quita el producto de la categoría (lo deja en general).
     */
    public function quitarProducto(Request $request, string $id, string $productId)
    {
        $category = Category::findOrFail($id);
        $this->authorizeAlmacen($category->almacen_id);

        ProductStock::where('almacen_id', $category->almacen_id)
            ->where('product_id', $productId)
            ->where('category_id', $category->id)
            ->update(['category_id' => null]);

        return response()->json(['message' => 'Producto movido a general.']);
    }

    /** Aborta con 422 si ya existe otra categoría con ese nombre en el almacén. */
    private function assertNombreUnico(string $name, int $almacenId, ?int $ignoreId = null): void
    {
        $exists = Category::where('almacen_id', $almacenId)
            ->where('name', $name)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            abort(response()->json([
                'message' => 'Ya existe una categoría con ese nombre en este almacén.',
                'errors' => ['name' => ['Ya existe una categoría con ese nombre en este almacén.']],
            ], 422));
        }
    }
}
