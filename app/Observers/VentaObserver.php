<?php

namespace App\Observers;

use App\Models\Finance\CategoriaFinanza;
use App\Models\Finance\Cuenta;
use App\Models\Finance\CuentaPorCobrar;
use App\Models\Finance\Transaccion;
use App\Models\Venta;
use App\Services\CurrencyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VentaObserver
{
    public function __construct(private CurrencyService $fx) {}

    /**
     * Al crear una venta completada, dispara el flujo financiero.
     */
    public function created(Venta $venta): void
    {
        if ($venta->estado === 'completada') {
            $this->procesarPago($venta);
        }
    }

    /**
     * Si una venta pendiente pasa a completada, dispara el flujo.
     * Si una completada pasa a cancelada, revierte.
     */
    public function updated(Venta $venta): void
    {
        if (! $venta->wasChanged('estado')) {
            return;
        }

        $estadoAnterior = $venta->getOriginal('estado');
        $estadoNuevo    = $venta->estado;

        if ($estadoAnterior !== 'completada' && $estadoNuevo === 'completada') {
            $this->procesarPago($venta);
        }

        if ($estadoAnterior === 'completada' && $estadoNuevo === 'cancelada') {
            $this->revertir($venta);
        }
    }

    private function procesarPago(Venta $venta): void
    {
        if (! $venta->cuenta_id || ! $venta->moneda_id) {
            Log::warning("Venta {$venta->id} sin cuenta/moneda — omitida del flujo financiero.");
            return;
        }

        // Evita duplicados si el observer corre dos veces
        if (Transaccion::where('venta_id', $venta->id)->exists()
            || CuentaPorCobrar::where('venta_id', $venta->id)->exists()) {
            return;
        }

        try {
            DB::transaction(function () use ($venta) {
                $tcBase = $this->fx->rate($venta->moneda_id, $this->fx->base()->id, $venta->fecha);
                $totalBase = round((float) $venta->total * $tcBase, 4);

                if ($venta->forma_pago === 'credito') {
                    CuentaPorCobrar::create([
                        'venta_id'          => $venta->id,
                        'cliente'           => $venta->cliente ?? 'Cliente mostrador',
                        'cliente_rfc'       => $venta->cliente_rfc,
                        'moneda_id'         => $venta->moneda_id,
                        'tipo_cambio'       => $tcBase,
                        'monto_total'       => $venta->total,
                        'monto_pagado'      => 0,
                        'saldo'             => $venta->total,
                        'fecha_emision'     => $venta->fecha->toDateString(),
                        'fecha_vencimiento' => $venta->fecha_vencimiento ?? $venta->fecha->copy()->addDays(30)->toDateString(),
                        'estado'            => 'pendiente',
                    ]);
                    return;
                }

                $categoria = CategoriaFinanza::where('tipo', 'ingreso')
                    ->where('nombre', 'Ventas')
                    ->first();

                Transaccion::create([
                    'cuenta_id'    => $venta->cuenta_id,
                    'categoria_id' => $categoria?->id,
                    'user_id'      => $venta->user_id,
                    'tipo'         => 'ingreso',
                    'moneda_id'    => $venta->moneda_id,
                    'tipo_cambio'  => $tcBase,
                    'subtotal'     => $venta->total,
                    'total'        => $venta->total,
                    'total_base'   => $totalBase,
                    'fecha'        => $venta->fecha->toDateString(),
                    'descripcion'  => 'Venta #' . $venta->id . ($venta->cliente ? ' — ' . $venta->cliente : ''),
                    'referencia'   => $venta->referencia,
                    'venta_id'     => $venta->id,
                    'estado'       => 'conciliada',
                ]);

                Cuenta::where('id', $venta->cuenta_id)->increment('saldo_actual', $venta->total);
            });
        } catch (\Throwable $e) {
            Log::error('VentaObserver::procesarPago falló', [
                'venta_id' => $venta->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    private function revertir(Venta $venta): void
    {
        try {
            DB::transaction(function () use ($venta) {
                $tx = Transaccion::where('venta_id', $venta->id)->first();
                if ($tx) {
                    Cuenta::where('id', $tx->cuenta_id)->decrement('saldo_actual', $tx->total);
                    $tx->delete();
                }

                $cxc = CuentaPorCobrar::where('venta_id', $venta->id)->first();
                if ($cxc && (float) $cxc->monto_pagado === 0.0) {
                    $cxc->delete();
                }
            });
        } catch (\Throwable $e) {
            Log::error('VentaObserver::revertir falló', [
                'venta_id' => $venta->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
