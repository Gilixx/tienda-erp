<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            Category::accesiblesPara($request->user())
                ->withCount('products')
                ->orderBy('name')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255',
                Rule::unique('categories', 'name')->where('created_by', $userId)],
            'description' => 'nullable|string|max:1000',
        ]);

        $validated['created_by'] = $userId;
        $category = Category::create($validated);
        $category->products_count = 0;

        return response()->json($category, 201);
    }

    public function update(Request $request, string $id)
    {
        $category = Category::findOrFail($id);
        $this->authorizeDueno($category, $request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255',
                Rule::unique('categories', 'name')->where('created_by', $category->created_by)->ignore($category->id)],
            'description' => 'nullable|string|max:1000',
        ]);

        $category->update($validated);

        return response()->json($category->loadCount('products'));
    }

    public function destroy(Request $request, string $id)
    {
        $category = Category::findOrFail($id);
        $this->authorizeDueno($category, $request);
        $category->delete();

        return response()->json(['message' => 'Categoría eliminada correctamente']);
    }

    /** Aborta con 403 si el usuario no es dueño de la categoría (ni admin). */
    private function authorizeDueno(Category $category, Request $request): void
    {
        $user = $request->user();
        if (! $user->isAdmin() && $category->created_by !== $user->id) {
            abort(403, 'Solo el dueño de la categoría puede modificarla.');
        }
    }
}
