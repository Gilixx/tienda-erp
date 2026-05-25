<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Cuenta;
use App\Models\Finance\CuentaPorCobrar;
use App\Models\Finance\CuentaPorPagar;
use App\Models\Finance\Transaccion;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FinanceStatsController extends Controller
{
    public function index(Request $request, CurrencyService $fx)
    {
        $days = (int) $request->input('days', 30);
        $days = max(7, min(365, $days));
        $since = Carbon::now()->subDays($days)->startOfDay();
        $base  = $fx->base();

        return response()->json([
            'period_days'  => $days,
            'moneda_base'  => $base,
            'summary'      => $this->summary($since),
            'daily_flow'   => $this->dailyFlow($since),
            'by_category'  => $this->byCategory($since),
            'cuentas'      => $this->saldosCuentas(),
            'cxc'          => $this->cxcSummary(),
            'cxp'          => $this->cxpSummary(),
        ]);
    }

    private function summary(Carbon $since): array
    {
        $rows = Transaccion::where('fecha', '>=', $since)
            ->whereIn('tipo', ['ingreso', 'egreso'])
            ->selectRaw('tipo, SUM(total_base) as monto')
            ->groupBy('tipo')
            ->pluck('monto', 'tipo');

        $ingresos = (float) ($rows['ingreso'] ?? 0);
        $egresos  = (float) ($rows['egreso']  ?? 0);
        $utilidad = $ingresos - $egresos;
        $margen   = $ingresos > 0 ? round(($utilidad / $ingresos) * 100, 2) : 0;

        return [
            'ingresos' => round($ingresos, 2),
            'egresos'  => round($egresos, 2),
            'utilidad' => round($utilidad, 2),
            'margen'   => $margen,
        ];
    }

    private function dailyFlow(Carbon $since): array
    {
        $rows = Transaccion::where('fecha', '>=', $since)
            ->whereIn('tipo', ['ingreso', 'egreso'])
            ->selectRaw('fecha, tipo, SUM(total_base) as monto')
            ->groupBy('fecha', 'tipo')
            ->orderBy('fecha')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $key = (string) $r->fecha->toDateString();
            $map[$key] ??= ['fecha' => $key, 'ingresos' => 0, 'egresos' => 0];
            $map[$key][$r->tipo === 'ingreso' ? 'ingresos' : 'egresos'] = round((float) $r->monto, 2);
        }

        return array_values($map);
    }

    private function byCategory(Carbon $since): array
    {
        $rows = DB::table('transacciones')
            ->leftJoin('categorias_finanzas', 'transacciones.categoria_id', '=', 'categorias_finanzas.id')
            ->where('transacciones.fecha', '>=', $since)
            ->whereIn('transacciones.tipo', ['ingreso', 'egreso'])
            ->selectRaw('transacciones.tipo, categorias_finanzas.id as cat_id, categorias_finanzas.nombre, categorias_finanzas.color, SUM(transacciones.total_base) as monto')
            ->groupBy('transacciones.tipo', 'categorias_finanzas.id', 'categorias_finanzas.nombre', 'categorias_finanzas.color')
            ->orderByDesc('monto')
            ->get();

        return $rows->map(fn ($r) => [
            'tipo'   => $r->tipo,
            'id'     => $r->cat_id,
            'nombre' => $r->nombre ?? 'Sin categoría',
            'color'  => $r->color  ?? '#94a3b8',
            'monto'  => round((float) $r->monto, 2),
        ])->toArray();
    }

    private function saldosCuentas(): array
    {
        return Cuenta::with('moneda:id,codigo,simbolo')
            ->where('activa', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn ($c) => [
                'id'           => $c->id,
                'nombre'       => $c->nombre,
                'tipo'         => $c->tipo,
                'moneda'       => $c->moneda,
                'saldo_actual' => (float) $c->saldo_actual,
            ])->toArray();
    }

    private function cxcSummary(): array
    {
        $hoy = now()->toDateString();
        return [
            'pendiente' => (float) CuentaPorCobrar::whereIn('estado', ['pendiente', 'parcial'])->sum(DB::raw('saldo * tipo_cambio')),
            'vencida'   => (float) CuentaPorCobrar::whereIn('estado', ['pendiente', 'parcial'])
                            ->where('fecha_vencimiento', '<', $hoy)->sum(DB::raw('saldo * tipo_cambio')),
            'count'     => CuentaPorCobrar::whereIn('estado', ['pendiente', 'parcial'])->count(),
        ];
    }

    private function cxpSummary(): array
    {
        $hoy = now()->toDateString();
        return [
            'pendiente' => (float) CuentaPorPagar::whereIn('estado', ['pendiente', 'parcial'])->sum(DB::raw('saldo * tipo_cambio')),
            'vencida'   => (float) CuentaPorPagar::whereIn('estado', ['pendiente', 'parcial'])
                            ->where('fecha_vencimiento', '<', $hoy)->sum(DB::raw('saldo * tipo_cambio')),
            'count'     => CuentaPorPagar::whereIn('estado', ['pendiente', 'parcial'])->count(),
        ];
    }
}
