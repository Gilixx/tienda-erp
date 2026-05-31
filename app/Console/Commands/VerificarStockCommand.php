<?php

namespace App\Console\Commands;

use App\Models\Inventory\AlertaStock;
use App\Models\Inventory\ProductStock;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerificarStockCommand extends Command
{
    protected $signature   = 'inventory:verificar-stock {--almacen= : ID del almacén específico}';
    protected $description = 'Verifica stock mínimo y puntos de reorden, genera alertas automáticas';

    public function handle(): int
    {
        $almacenId = $this->option('almacen');

        $query = ProductStock::with('product')
            ->where('cantidad', '>', 0)
            ->orWhere(fn($q) => $q->whereHas('product', fn($p) => $p->where('is_active', true)));

        if ($almacenId) {
            $query->where('almacen_id', $almacenId);
        }

        $stocks = ProductStock::with(['product', 'almacen'])
            ->whereHas('product', fn($q) => $q->where('is_active', true))
            ->when($almacenId, fn($q) => $q->where('almacen_id', $almacenId))
            ->get();

        $alertasCreadas   = 0;
        $alertasResueltas = 0;

        foreach ($stocks as $stockRow) {
            $product = $stockRow->product;
            $cantidad = $stockRow->cantidad;

            // ── Resolver alertas si el stock ya está bien ──
            AlertaStock::where('product_id', $product->id)
                ->where('almacen_id', $stockRow->almacen_id)
                ->where('estado', 'activa')
                ->when(
                    $cantidad > $product->min_stock,
                    fn($q) => $q->where('tipo', 'bajo_minimo')
                )
                ->when(
                    $cantidad > $product->punto_reorden,
                    fn($q) => $q->where('tipo', 'punto_reorden')
                )
                ->update(['estado' => 'resuelta']);

            // ── Crear alerta por bajo mínimo ──
            if ($product->min_stock > 0 && $cantidad <= $product->min_stock) {
                $existente = AlertaStock::where('product_id', $product->id)
                    ->where('almacen_id', $stockRow->almacen_id)
                    ->where('tipo', 'bajo_minimo')
                    ->where('estado', 'activa')
                    ->exists();

                if (! $existente) {
                    AlertaStock::create([
                        'product_id'   => $product->id,
                        'almacen_id'   => $stockRow->almacen_id,
                        'tipo'         => 'bajo_minimo',
                        'stock_actual' => $cantidad,
                        'stock_minimo' => $product->min_stock,
                        'estado'       => 'activa',
                    ]);
                    $alertasCreadas++;
                }
            }

            // ── Crear alerta por punto de reorden ──
            if ($product->punto_reorden > 0 && $cantidad <= $product->punto_reorden) {
                $existente = AlertaStock::where('product_id', $product->id)
                    ->where('almacen_id', $stockRow->almacen_id)
                    ->where('tipo', 'punto_reorden')
                    ->where('estado', 'activa')
                    ->exists();

                if (! $existente) {
                    AlertaStock::create([
                        'product_id'   => $product->id,
                        'almacen_id'   => $stockRow->almacen_id,
                        'tipo'         => 'punto_reorden',
                        'stock_actual' => $cantidad,
                        'stock_minimo' => $product->punto_reorden,
                        'estado'       => 'activa',
                    ]);
                    $alertasCreadas++;
                }
            }
        }

        $this->info("✓ Verificación completada. Alertas creadas: {$alertasCreadas}.");

        return Command::SUCCESS;
    }
}
