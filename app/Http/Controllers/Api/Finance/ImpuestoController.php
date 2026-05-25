<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Impuesto;
use Illuminate\Http\Request;

class ImpuestoController extends Controller
{
    public function index()
    {
        return response()->json(Impuesto::orderBy('tipo')->orderBy('codigo')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo'             => 'required|string|max:20|unique:impuestos,codigo',
            'nombre'             => 'required|string|max:100',
            'tipo'               => 'required|in:iva,ieps,isr,retencion,otro',
            'tasa'               => 'required|numeric|min:0|max:100',
            'aplicacion'         => 'required|in:traslado,retencion',
            'incluido_en_precio' => 'boolean',
            'activo'             => 'boolean',
        ]);

        return response()->json(Impuesto::create($validated), 201);
    }

    public function update(Request $request, string $id)
    {
        $impuesto = Impuesto::findOrFail($id);

        $validated = $request->validate([
            'codigo'             => 'required|string|max:20|unique:impuestos,codigo,' . $impuesto->id,
            'nombre'             => 'required|string|max:100',
            'tipo'               => 'required|in:iva,ieps,isr,retencion,otro',
            'tasa'               => 'required|numeric|min:0|max:100',
            'aplicacion'         => 'required|in:traslado,retencion',
            'incluido_en_precio' => 'boolean',
            'activo'             => 'boolean',
        ]);

        $impuesto->update($validated);
        return response()->json($impuesto);
    }

    public function destroy(string $id)
    {
        Impuesto::findOrFail($id)->delete();
        return response()->json(['message' => 'Impuesto eliminado.']);
    }
}
