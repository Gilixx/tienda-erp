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
     * Implementación set-based: en vez de 3-5 queries por fila de stock, carga
     * los stocks y las alertas activas en memoria y aplica un solo UPDATE (resolver)
     * y un solo INSERT (crear). Portable entre MySQL (app) y sqlite (tests).
     *
     * @param  int|null  $almacenId  Limita a un almacén específico.
     * @param  array<int>|null  $productIds  Limita a ciertos productos (refresco puntual).
     * @return array{creadas:int,resueltas:int}
     */
    public function verificar(?int $almacenId = null, ?array $productIds = null): array
    {
        // 1) Stocks + umbrales del producto, en una sola consulta.
        $stocks = ProductStock::query()
            ->join('products', 'products.id', '=', 'product_stock.product_id')
            ->where('products.is_active', true)
            ->when($almacenId, fn ($q) => $q->where('product_stock.almacen_id', $almacenId))
            ->when($productIds, fn ($q) => $q->whereIn('product_stock.product_id', $productIds))
            ->get([
                'product_stock.product_id',
                'product_stock.almacen_id',
                'product_stock.cantidad',
                'products.min_stock',
                'products.punto_reorden',
            ]);

        if ($stocks->isEmpty()) {
            return ['creadas' => 0, 'resueltas' => 0];
        }

        // 2) Alertas activas de los pares involucrados, indexadas en memoria.
        $activas = AlertaStock::query()
            ->where('estado', 'activa')
            ->whereIn('product_id', $stocks->pluck('product_id')->unique()->all())
            ->whereIn('almacen_id', $stocks->pluck('almacen_id')->unique()->all())
            ->get(['id', 'product_id', 'almacen_id', 'tipo']);

        $activaId = [];
        foreach ($activas as $a) {
            $activaId["{$a->product_id}|{$a->almacen_id}|{$a->tipo}"] = $a->id;
        }

        // 3) Computar en PHP qué resolver y qué crear.
        $resolverIds = [];
        $insertRows = [];
        $now = now();

        $umbrales = ['bajo_minimo' => 'min_stock', 'punto_reorden' => 'punto_reorden'];

        foreach ($stocks as $s) {
            foreach ($umbrales as $tipo => $campo) {
                $umbral = (int) $s->{$campo};
                $id = $activaId["{$s->product_id}|{$s->almacen_id}|{$tipo}"] ?? null;

                // Crear si está en/por debajo del umbral (y el umbral aplica) y no hay activa.
                if ($umbral > 0 && $s->cantidad <= $umbral) {
                    if (! $id) {
                        $insertRows[] = [
                            'product_id' => $s->product_id,
                            'almacen_id' => $s->almacen_id,
                            'tipo' => $tipo,
                            'stock_actual' => $s->cantidad,
                            'stock_minimo' => $umbral,
                            'estado' => 'activa',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                } elseif ($s->cantidad > $umbral && $id) {
                    // Stock recuperado por encima del umbral: resolver la activa.
                    $resolverIds[] = $id;
                }
            }
        }

        // 4) Un UPDATE y un INSERT, sin importar cuántas filas.
        $resueltas = 0;
        if ($resolverIds) {
            $resueltas = AlertaStock::whereIn('id', $resolverIds)
                ->update(['estado' => 'resuelta', 'updated_at' => $now]);
        }

        $creadas = 0;
        if ($insertRows) {
            AlertaStock::insert($insertRows);
            $creadas = count($insertRows);
        }

        return ['creadas' => $creadas, 'resueltas' => $resueltas];
    }
}
