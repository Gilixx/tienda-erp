<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\CategoriaFinanza;
use App\Models\Finance\Cuenta;
use App\Models\Finance\CuentaPorPagar;
use App\Models\Finance\Transaccion;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CxPController extends Controller
{
    public function index(Request $request)
    {
        $query = CuentaPorPagar::with(['proveedor:id,nombre', 'moneda:id,codigo,simbolo', 'compra:id,referencia'])
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

    public function pagar(Request $request, string $id, CurrencyService $fx)
    {
        $validated = $request->validate([
            'cuenta_id'  => 'required|exists:cuentas,id',
            'monto'      => 'required|numeric|min:0.01',
            'fecha'      => 'required|date',
            'referencia' => 'nullable|string|max:100',
        ]);

        try {
            return DB::transaction(function () use ($id, $validated, $fx) {
                $cxp = CuentaPorPagar::lockForUpdate()->findOrFail($id);

                if (in_array($cxp->estado, ['pagada', 'cancelada'])) {
                    return response()->json(['error' => 'Esta cuenta ya está saldada o cancelada.'], 422);
                }

                $monto = round((float) $validated['monto'], 4);
                if ($monto > (float) $cxp->saldo) {
                    return response()->json(['error' => 'El pago excede el saldo pendiente.'], 422);
                }

                $cuenta = Cuenta::lockForUpdate()->findOrFail($validated['cuenta_id']);

                if ($cuenta->saldo_actual < $monto) {
                    return response()->json(['error' => 'Saldo insuficiente en la cuenta.'], 422);
                }

                $tcBase = $fx->rate($cxp->moneda_id, $fx->base()->id, Carbon::parse($validated['fecha']));
                $totalBase = round($monto * $tcBase, 4);

                $categoria = CategoriaFinanza::where('tipo', 'egreso')
                    ->where('nombre', 'like', '%Compras de inventario%')->first();

                Transaccion::create([
                    'cuenta_id'    => $cuenta->id,
                    'categoria_id' => $categoria?->id,
                    'user_id'      => auth()->id(),
                    'tipo'         => 'egreso',
                    'moneda_id'    => $cxp->moneda_id,
                    'tipo_cambio'  => $tcBase,
                    'subtotal'     => $monto,
                    'total'        => $monto,
                    'total_base'   => $totalBase,
                    'fecha'        => $validated['fecha'],
                    'descripcion'  => 'Pago CxP #' . $cxp->id,
                    'referencia'   => $validated['referencia'] ?? null,
                    'compra_id'    => $cxp->compra_id,
                    'estado'       => 'conciliada',
                ]);

                $cuenta->decrement('saldo_actual', $monto);

                $cxp->monto_pagado = round((float) $cxp->monto_pagado + $monto, 4);
                $cxp->saldo        = round((float) $cxp->monto_total - $cxp->monto_pagado, 4);
                $cxp->estado       = $cxp->saldo <= 0.0001 ? 'pagada' : 'parcial';
                $cxp->save();

                return response()->json($cxp->fresh()->load(['proveedor', 'moneda']));
            });
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Error al registrar el pago.'], 500);
        }
    }
}
