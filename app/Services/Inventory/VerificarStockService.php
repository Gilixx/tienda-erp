<?php

namespace App\Services\Inventory;

use App\Models\Inventory\AlertaStock;
use App\Models\Inventory\ProductStock;

class VerificarStockService
{
    /**
     * Verifica stock mínimo y punto de reorden por almacén, creando o
     * resolviendo alertas en la tabla `alertas_stock`.
     *
     * @param  int|null  $almacenId  Limita a un almacén específico.
     * @param  array<int>|null  $productIds  Limita a ciertos productos (refresco puntual).
     * @return array{creadas:int,resueltas:int}
     */
    public function verificar(?int $almacenId = null, ?array $productIds = null): array
    {
        $stocks = ProductStock::with(['product', 'almacen'])
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->when($almacenId, fn ($q) => $q->where('almacen_id', $almacenId))
            ->when($productIds, fn ($q) => $q->whereIn('product_id', $productIds))
            ->get();

        $creadas = 0;
        $resueltas = 0;

        foreach ($stocks as $stockRow) {
            $product = $stockRow->product;
            $cantidad = $stockRow->cantidad;

            // ── Resolver alertas si el stock ya está bien ──
            // Cada tipo se resuelve contra SU propio umbral. Encadenar dos where('tipo',…)
            // daría `tipo='bajo_minimo' AND tipo='punto_reorden'` (imposible) y nunca
            // resolvería nada cuando el stock supera ambos umbrales.
            $tiposResueltos = [];
            if ($cantidad > $product->min_stock) {
                $tiposResueltos[] = 'bajo_minimo';
            }
            if ($cantidad > $product->punto_reorden) {
                $tiposResueltos[] = 'punto_reorden';
            }

            if ($tiposResueltos) {
                $resueltas += AlertaStock::where('product_id', $product->id)
                    ->where('almacen_id', $stockRow->almacen_id)
                    ->where('estado', 'activa')
                    ->whereIn('tipo', $tiposResueltos)
                    ->update(['estado' => 'resuelta']);
            }

            // ── Crear alerta por bajo mínimo ──
            if ($product->min_stock > 0 && $cantidad <= $product->min_stock) {
                $creadas += $this->crearSiFalta($product->id, $stockRow->almacen_id, 'bajo_minimo', $cantidad, $product->min_stock);
            }

            // ── Crear alerta por punto de reorden ──
            if ($product->punto_reorden > 0 && $cantidad <= $product->punto_reorden) {
                $creadas += $this->crearSiFalta($product->id, $stockRow->almacen_id, 'punto_reorden', $cantidad, $product->punto_reorden);
            }
        }

        return ['creadas' => $creadas, 'resueltas' => $resueltas];
    }

    /** Crea la alerta si no hay una activa del mismo tipo. Devuelve 1 si la creó. */
    private function crearSiFalta(int $productId, ?int $almacenId, string $tipo, int $stockActual, int $stockMinimo): int
    {
        $existente = AlertaStock::where('product_id', $productId)
            ->where('almacen_id', $almacenId)
            ->where('tipo', $tipo)
            ->where('estado', 'activa')
            ->exists();

        if ($existente) {
            return 0;
        }

        AlertaStock::create([
            'product_id' => $productId,
            'almacen_id' => $almacenId,
            'tipo' => $tipo,
            'stock_actual' => $stockActual,
            'stock_minimo' => $stockMinimo,
            'estado' => 'activa',
        ]);

        return 1;
    }
}
