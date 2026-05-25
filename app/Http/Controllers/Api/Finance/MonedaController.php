<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Moneda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MonedaController extends Controller
{
    public function index()
    {
        return response()->json(Moneda::orderByDesc('es_base')->orderBy('codigo')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo'    => 'required|string|size:3|unique:monedas,codigo',
            'nombre'    => 'required|string|max:100',
            'simbolo'   => 'required|string|max:5',
            'decimales' => 'nullable|integer|min:0|max:6',
            'es_base'   => 'boolean',
            'activa'    => 'boolean',
        ]);

        $validated['codigo'] = strtoupper($validated['codigo']);

        $moneda = DB::transaction(function () use ($validated) {
            if (! empty($validated['es_base'])) {
                Moneda::where('es_base', true)->update(['es_base' => false]);
            }
            return Moneda::create($validated);
        });

        Cache::forget('finance.moneda_base');

        return response()->json($moneda, 201);
    }

    public function update(Request $request, string $id)
    {
        $moneda = Moneda::findOrFail($id);

        $validated = $request->validate([
            'codigo'    => 'required|string|size:3|unique:monedas,codigo,' . $moneda->id,
            'nombre'    => 'required|string|max:100',
            'simbolo'   => 'required|string|max:5',
            'decimales' => 'nullable|integer|min:0|max:6',
            'es_base'   => 'boolean',
            'activa'    => 'boolean',
        ]);

        $validated['codigo'] = strtoupper($validated['codigo']);

        DB::transaction(function () use ($moneda, $validated) {
            if (! empty($validated['es_base'])) {
                Moneda::where('id', '!=', $moneda->id)->update(['es_base' => false]);
            }
            $moneda->update($validated);
        });

        Cache::forget('finance.moneda_base');

        return response()->json($moneda->fresh());
    }

    public function destroy(string $id)
    {
        $moneda = Moneda::findOrFail($id);

        if ($moneda->es_base) {
            return response()->json(['error' => 'No se puede eliminar la moneda base.'], 422);
        }

        $moneda->delete();
        return response()->json(['message' => 'Moneda eliminada.']);
    }
}
