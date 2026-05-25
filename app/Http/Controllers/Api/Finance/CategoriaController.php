<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\CategoriaFinanza;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index(Request $request)
    {
        $query = CategoriaFinanza::query()->orderBy('tipo')->orderBy('nombre');

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->input('tipo'));
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'tipo'   => 'required|in:ingreso,egreso',
            'color'  => 'nullable|string|max:20',
            'icono'  => 'nullable|string|max:40',
            'activa' => 'boolean',
        ]);

        return response()->json(CategoriaFinanza::create($validated), 201);
    }

    public function update(Request $request, string $id)
    {
        $cat = CategoriaFinanza::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'tipo'   => 'required|in:ingreso,egreso',
            'color'  => 'nullable|string|max:20',
            'icono'  => 'nullable|string|max:40',
            'activa' => 'boolean',
        ]);

        $cat->update($validated);
        return response()->json($cat);
    }

    public function destroy(string $id)
    {
        CategoriaFinanza::findOrFail($id)->delete();
        return response()->json(['message' => 'Categoría eliminada.']);
    }
}
