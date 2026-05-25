<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\TipoCambio;
use Illuminate\Http\Request;

class TipoCambioController extends Controller
{
    public function index(Request $request)
    {
        $query = TipoCambio::with(['origen', 'destino'])->latest('fecha');

        if ($request->filled('origen')) {
            $query->where('moneda_origen_id', $request->integer('origen'));
        }
        if ($request->filled('destino')) {
            $query->where('moneda_destino_id', $request->integer('destino'));
        }

        return response()->json($query->limit(200)->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'moneda_origen_id'  => 'required|exists:monedas,id',
            'moneda_destino_id' => 'required|exists:monedas,id|different:moneda_origen_id',
            'tasa'              => 'required|numeric|min:0.00000001',
            'fecha'             => 'required|date',
            'fuente'            => 'nullable|string|max:100',
        ]);

        $tc = TipoCambio::updateOrCreate(
            [
                'moneda_origen_id'  => $validated['moneda_origen_id'],
                'moneda_destino_id' => $validated['moneda_destino_id'],
                'fecha'             => $validated['fecha'],
            ],
            [
                'tasa'   => $validated['tasa'],
                'fuente' => $validated['fuente'] ?? null,
            ]
        );

        return response()->json($tc->load(['origen', 'destino']), 201);
    }

    public function destroy(string $id)
    {
        TipoCambio::findOrFail($id)->delete();
        return response()->json(['message' => 'Tipo de cambio eliminado.']);
    }
}
