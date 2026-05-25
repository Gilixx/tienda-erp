<?php

namespace App\Console\Commands;

use App\Models\Finance\CategoriaFinanza;
use App\Models\Finance\Cuenta;
use App\Models\Finance\CuentaPorCobrar;
use App\Models\Finance\Transaccion;
use App\Models\Venta;
use App\Services\CurrencyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillVentasCommand extends Command
{
    protected $signature = 'finance:backfill-ventas {--fresh : Borra transacciones y CxC vinculadas antes de regenerar}';

    protected $description = 'Genera transacciones / CxC retroactivas para ventas completadas que no las tienen.';

    public function handle(CurrencyService $fx): int
    {
        if ($this->option('fresh')) {
            $this->warn('Eliminando transacciones y CxC previas vinculadas a ventas...');
            $txIds = Transaccion::whereNotNull('venta_id')->pluck('id', 'cuenta_id');
            DB::table('transaccion_impuesto')->whereIn('transaccion_id', $txIds->values())->delete();

            // Revertir saldos de cuentas afectadas
            foreach (Transaccion::whereNotNull('venta_id')->get() as $tx) {
                Cuenta::where('id', $tx->cuenta_id)->decrement('saldo_actual', $tx->total);
            }
            Transaccion::whereNotNull('venta_id')->delete();
            CuentaPorCobrar::whereNotNull('venta_id')->whereDoesntHave('venta', fn ($q) => $q->whereNull('id'))->delete();
        }

        $categoriaVentas = CategoriaFinanza::where('tipo', 'ingreso')->where('nombre', 'Ventas')->first();

        $ventas = Venta::where('estado', 'completada')
            ->whereNotNull('cuenta_id')
            ->whereNotNull('moneda_id')
            ->whereNotIn('id', Transaccion::whereNotNull('venta_id')->pluck('venta_id'))
            ->whereNotIn('id', CuentaPorCobrar::whereNotNull('venta_id')->pluck('venta_id'))
            ->get();

        if ($ventas->isEmpty()) {
            $this->info('No hay ventas pendientes de backfill.');
            return self::SUCCESS;
        }

        $this->info("Procesando {$ventas->count()} ventas...");
        $bar = $this->output->createProgressBar($ventas->count());
        $bar->start();

        $creadas = 0;
        $errores = 0;

        foreach ($ventas as $venta) {
            try {
                DB::transaction(function () use ($venta, $fx, $categoriaVentas) {
                    $tcBase = $fx->rate($venta->moneda_id, $fx->base()->id, $venta->fecha);
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
                    } else {
                        Transaccion::create([
                            'cuenta_id'    => $venta->cuenta_id,
                            'categoria_id' => $categoriaVentas?->id,
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
                    }
                });
                $creadas++;
            } catch (\Throwable $e) {
                $errores++;
                $this->newLine();
                $this->error("Venta #{$venta->id}: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Backfill completado: {$creadas} creadas, {$errores} errores.");

        return self::SUCCESS;
    }
}
