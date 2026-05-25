<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Presupuesto;
use App\Models\Finance\Transaccion;
use Illuminate\Http\Request;

class PresupuestoController extends Controller
{
    public function index(Request $request)
    {
        $anio = $request->integer('anio', (int) now()->year);
        $mes  = $request->integer('mes',  (int) now()->month);

        $presupuestos = Presupuesto::with(['categoria', 'moneda'])
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->get();

        $resultado = $presupuestos->map(function ($p) use ($anio, $mes) {
            $gastado = (float) Transaccion::where('categoria_id', $p->categoria_id)
                ->whereYear('fecha', $anio)
                ->whereMonth('fecha', $mes)
                ->sum('total_base');

            $limite = (float) $p->monto_limite;
            $pct    = $limite > 0 ? round(($gastado / $limite) * 100, 2) : 0;

            return [
                'id'           => $p->id,
                'categoria'    => $p->categoria,
                'moneda'       => $p->moneda,
                'anio'         => $p->anio,
                'mes'          => $p->mes,
                'monto_limite' => $limite,
                'monto_usado'  => round($gastado, 4),
                'saldo'        => round($limite - $gastado, 4),
                'porcentaje'   => $pct,
                'alerta_pct'   => $p->alerta_pct,
                'en_alerta'    => $pct >= $p->alerta_pct,
                'excedido'     => $pct > 100,
            ];
        });

        return response()->json($resultado);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'categoria_id' => 'required|exists:categorias_finanzas,id',
            'moneda_id'    => 'required|exists:monedas,id',
            'anio'         => 'required|integer|min:2020|max:2100',
            'mes'          => 'required|integer|min:1|max:12',
            'monto_limite' => 'required|numeric|min:0',
            'alerta_pct'   => 'nullable|integer|min:1|max:100',
            'notas'        => 'nullable|string|max:500',
        ]);

        $p = Presupuesto::updateOrCreate(
            [
                'categoria_id' => $validated['categoria_id'],
                'anio'         => $validated['anio'],
                'mes'          => $validated['mes'],
            ],
            $validated
        );

        return response()->json($p->load(['categoria', 'moneda']), 201);
    }

    public function destroy(string $id)
    {
        Presupuesto::findOrFail($id)->delete();
        return response()->json(['message' => 'Presupuesto eliminado.']);
    }
}
