<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\CategoriaFinanza;
use App\Models\Finance\Cuenta;
use App\Models\Finance\CuentaPorCobrar;
use App\Models\Finance\Transaccion;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CxCController extends Controller
{
    public function index(Request $request)
    {
        $query = CuentaPorCobrar::with(['moneda:id,codigo,simbolo', 'venta:id,referencia'])
            ->orderBy('fecha_vencimiento');

        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        if ($request->boolean('vencidas')) {
            $query->whereIn('estado', ['pendiente', 'parcial'])
                  ->where('fecha_vencimiento', '<', now()->toDateString());
        }

        return response()->json($query->paginate(50));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'venta_id'          => 'nullable|exists:ventas,id',
            'cliente'           => 'required|string|max:150',
            'cliente_rfc'       => 'nullable|string|max:20',
            'moneda_id'         => 'required|exists:monedas,id',
            'tipo_cambio'       => 'nullable|numeric|min:0.00000001',
            'monto_total'       => 'required|numeric|min:0.01',
            'fecha_emision'     => 'required|date',
            'fecha_vencimiento' => 'required|date|after_or_equal:fecha_emision',
            'notas'             => 'nullable|string|max:1000',
        ]);

        $validated['tipo_cambio']  = $validated['tipo_cambio'] ?? 1;
        $validated['monto_pagado'] = 0;
        $validated['saldo']        = $validated['monto_total'];
        $validated['estado']       = 'pendiente';

        return response()->json(CuentaPorCobrar::create($validated)->load('moneda'), 201);
    }

    public function pagar(Request $request, string $id, CurrencyService $fx)
    {
        $validated = $request->validate([
            'cuenta_id' => 'required|exists:cuentas,id',
            'monto'     => 'required|numeric|min:0.01',
            'fecha'     => 'required|date',
            'referencia'=> 'nullable|string|max:100',
        ]);

        try {
            return DB::transaction(function () use ($id, $validated, $fx) {
                $cxc = CuentaPorCobrar::lockForUpdate()->findOrFail($id);

                if (in_array($cxc->estado, ['pagada', 'cancelada'])) {
                    return response()->json(['error' => 'Esta cuenta ya está saldada o cancelada.'], 422);
                }

                $monto = round((float) $validated['monto'], 4);
                if ($monto > (float) $cxc->saldo) {
                    return response()->json(['error' => 'El pago excede el saldo pendiente.'], 422);
                }

                $cuenta = Cuenta::lockForUpdate()->findOrFail($validated['cuenta_id']);

                $tcBase = $fx->rate($cxc->moneda_id, $fx->base()->id, Carbon::parse($validated['fecha']));
                $totalBase = round($monto * $tcBase, 4);

                $categoria = CategoriaFinanza::where('tipo', 'ingreso')->where('nombre', 'Ventas')->first();

                Transaccion::create([
                    'cuenta_id'    => $cuenta->id,
                    'categoria_id' => $categoria?->id,
                    'user_id'      => auth()->id(),
                    'tipo'         => 'ingreso',
                    'moneda_id'    => $cxc->moneda_id,
                    'tipo_cambio'  => $tcBase,
                    'subtotal'     => $monto,
                    'total'        => $monto,
                    'total_base'   => $totalBase,
                    'fecha'        => $validated['fecha'],
                    'descripcion'  => 'Cobro CxC #' . $cxc->id . ' — ' . $cxc->cliente,
                    'referencia'   => $validated['referencia'] ?? null,
                    'venta_id'     => $cxc->venta_id,
                    'estado'       => 'conciliada',
                ]);

                $cuenta->increment('saldo_actual', $monto);

                $cxc->monto_pagado = round((float) $cxc->monto_pagado + $monto, 4);
                $cxc->saldo        = round((float) $cxc->monto_total - $cxc->monto_pagado, 4);
                $cxc->estado       = $cxc->saldo <= 0.0001 ? 'pagada' : 'parcial';
                $cxc->save();

                return response()->json($cxc->fresh()->load('moneda'));
            });
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Error al registrar el pago.'], 500);
        }
    }

    public function destroy(string $id)
    {
        $cxc = CuentaPorCobrar::findOrFail($id);
        if ($cxc->monto_pagado > 0) {
            return response()->json(['error' => 'No se puede eliminar: ya tiene pagos registrados.'], 422);
        }
        $cxc->delete();
        return response()->json(['message' => 'CxC eliminada.']);
    }
}
