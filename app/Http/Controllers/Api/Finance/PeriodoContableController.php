<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\PeriodoContable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PeriodoContableController extends Controller
{
    /** GET /api/finance/periodos */
    public function index(): JsonResponse
    {
        return response()->json(
            PeriodoContable::with('cerradoPor:id,name')
                ->orderBy('anio', 'desc')
                ->orderBy('mes', 'desc')
                ->get()
        );
    }

    /**
     * POST /api/finance/periodos
     * Crea un período (abierto por defecto).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'anio'  => 'required|integer|min:2020|max:2099',
            'mes'   => 'required|integer|min:1|max:12',
            'notas' => 'nullable|string|max:500',
        ]);

        $periodo = PeriodoContable::firstOrCreate(
            ['anio' => $validated['anio'], 'mes' => $validated['mes']],
            ['estado' => 'abierto', 'notas' => $validated['notas'] ?? null]
        );

        return response()->json($periodo, 201);
    }

    /**
     * POST /api/finance/periodos/{id}/cerrar
     * Cierra el período. Las transacciones en ese mes/año quedarán bloqueadas.
     */
    public function cerrar(Request $request, string $id): JsonResponse
    {
        $periodo = PeriodoContable::findOrFail($id);

        if ($periodo->estado === 'cerrado') {
            return response()->json(['error' => 'El período ya está cerrado.'], 422);
        }

        $periodo->update([
            'estado'     => 'cerrado',
            'cerrado_por' => auth()->id(),
            'cerrado_en' => now(),
            'notas'      => $request->input('notas', $periodo->notas),
        ]);

        return response()->json($periodo->fresh());
    }

    /**
     * POST /api/finance/periodos/{id}/reabrir
     * Reabre un período cerrado (operación sensible).
     */
    public function reabrir(string $id): JsonResponse
    {
        $periodo = PeriodoContable::findOrFail($id);

        if ($periodo->estado === 'abierto') {
            return response()->json(['error' => 'El período ya está abierto.'], 422);
        }

        $periodo->update([
            'estado'     => 'abierto',
            'cerrado_por' => null,
            'cerrado_en' => null,
        ]);

        return response()->json($periodo->fresh());
    }
}
