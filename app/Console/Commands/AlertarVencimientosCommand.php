<?php

namespace App\Console\Commands;

use App\Models\Inventory\Lote;
use Illuminate\Console\Command;

class AlertarVencimientosCommand extends Command
{
    protected $signature   = 'inventory:alertar-vencimientos {--dias=30 : Alertar si vence en los próximos N días}';
    protected $description = 'Lista lotes próximos a vencer o ya vencidos';

    public function handle(): int
    {
        $dias = (int) $this->option('dias');

        $lotes = Lote::with(['product:id,name,sku', 'almacen:id,nombre'])
            ->activos()
            ->conStock()
            ->whereNotNull('fecha_vencimiento')
            ->where('fecha_vencimiento', '<=', now()->addDays($dias))
            ->fefo()
            ->get();

        if ($lotes->isEmpty()) {
            $this->info("No hay lotes próximos a vencer en los próximos {$dias} días.");
            return Command::SUCCESS;
        }

        $this->warn("Lotes próximos a vencer (dentro de {$dias} días):");
        $this->newLine();

        $rows = $lotes->map(fn($l) => [
            $l->product->sku ?? '—',
            $l->product->name ?? '—',
            $l->numero_lote,
            $l->almacen->nombre ?? '—',
            $l->cantidad_actual,
            $l->fecha_vencimiento?->format('Y-m-d'),
            $l->fecha_vencimiento?->isPast() ? '⚠ VENCIDO' : $l->fecha_vencimiento?->diffForHumans(),
        ])->toArray();

        $this->table(
            ['SKU', 'Producto', 'Lote', 'Almacén', 'Stock', 'Vencimiento', 'Estado'],
            $rows
        );

        $this->newLine();
        $this->error("Total lotes afectados: {$lotes->count()}");

        return Command::SUCCESS;
    }
}
