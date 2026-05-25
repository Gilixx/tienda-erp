<?php

namespace App\Services;

use App\Models\Finance\Moneda;
use App\Models\Finance\TipoCambio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class CurrencyService
{
    /**
     * Devuelve la moneda base del sistema.
     */
    public function base(): Moneda
    {
        return Cache::remember('finance.moneda_base', 3600, function () {
            $base = Moneda::where('es_base', true)->first();
            if (! $base) {
                throw new \RuntimeException('No hay moneda base configurada.');
            }
            return $base;
        });
    }

    /**
     * Convierte un monto entre monedas, usando la tasa más reciente <= $fecha.
     */
    public function convert(float $monto, int $monedaOrigenId, int $monedaDestinoId, ?Carbon $fecha = null): float
    {
        if ($monedaOrigenId === $monedaDestinoId) {
            return $monto;
        }

        $tasa = $this->rate($monedaOrigenId, $monedaDestinoId, $fecha);
        return round($monto * $tasa, 4);
    }

    /**
     * Convierte un monto a la moneda base del sistema.
     */
    public function toBase(float $monto, int $monedaOrigenId, ?Carbon $fecha = null): float
    {
        $base = $this->base();
        return $this->convert($monto, $monedaOrigenId, $base->id, $fecha);
    }

    /**
     * Obtiene la tasa de cambio aplicable. Busca primero directa, luego inversa.
     */
    public function rate(int $origenId, int $destinoId, ?Carbon $fecha = null): float
    {
        if ($origenId === $destinoId) {
            return 1.0;
        }

        $fecha ??= Carbon::now();

        $directa = TipoCambio::where('moneda_origen_id', $origenId)
            ->where('moneda_destino_id', $destinoId)
            ->where('fecha', '<=', $fecha)
            ->orderByDesc('fecha')
            ->value('tasa');

        if ($directa !== null) {
            return (float) $directa;
        }

        $inversa = TipoCambio::where('moneda_origen_id', $destinoId)
            ->where('moneda_destino_id', $origenId)
            ->where('fecha', '<=', $fecha)
            ->orderByDesc('fecha')
            ->value('tasa');

        if ($inversa !== null && (float) $inversa > 0) {
            return round(1 / (float) $inversa, 8);
        }

        throw new \RuntimeException("No hay tipo de cambio definido entre las monedas {$origenId} y {$destinoId}.");
    }
}
