<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    private const CACHE_TTL = 900; // 15 minutos

    // ─────────────────────────────────────────────
    // GET /api/finance/reportes/pyg
    // Estado de Resultados (P&L)
    // ─────────────────────────────────────────────
    public function pyg(Request $request): JsonResponse
    {
        $request->validate([
            'desde'   => 'required|date',
            'hasta'   => 'required|date|after_or_equal:desde',
            'moneda'  => 'nullable|string|size:3',
        ]);

        $desde  = $request->input('desde');
        $hasta  = $request->input('hasta');
        $cacheKey = "reporte_pyg_{$desde}_{$hasta}";

        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($desde, $hasta) {
            // Ingresos por categoría
            $ingresos = DB::select("
                SELECT cf.nombre AS categoria, cf.color,
                       SUM(t.total_base) AS total
                FROM transacciones t
                LEFT JOIN categorias_finanzas cf ON cf.id = t.categoria_id
                WHERE t.tipo = 'ingreso'
                  AND t.estado != 'cancelada'
                  AND t.fecha BETWEEN ? AND ?
                GROUP BY cf.nombre, cf.color
                ORDER BY total DESC
            ", [$desde, $hasta]);

            // Egresos por categoría
            $egresos = DB::select("
                SELECT cf.nombre AS categoria, cf.color,
                       SUM(t.total_base) AS total
                FROM transacciones t
                LEFT JOIN categorias_finanzas cf ON cf.id = t.categoria_id
                WHERE t.tipo = 'egreso'
                  AND t.estado != 'cancelada'
                  AND t.fecha BETWEEN ? AND ?
                GROUP BY cf.nombre, cf.color
                ORDER BY total DESC
            ", [$desde, $hasta]);

            $totalIngresos = array_sum(array_column($ingresos, 'total'));
            $totalEgresos  = array_sum(array_column($egresos, 'total'));
            $utilidad      = $totalIngresos - $totalEgresos;
            $margen        = $totalIngresos > 0 ? round(($utilidad / $totalIngresos) * 100, 2) : 0;

            return [
                'periodo'         => ['desde' => $desde, 'hasta' => $hasta],
                'total_ingresos'  => round($totalIngresos, 2),
                'total_egresos'   => round($totalEgresos, 2),
                'utilidad_bruta'  => round($utilidad, 2),
                'margen_pct'      => $margen,
                'ingresos'        => $ingresos,
                'egresos'         => $egresos,
            ];
        });

        return response()->json($data);
    }

    // ─────────────────────────────────────────────
    // GET /api/finance/reportes/flujo-caja
    // Flujo de caja real por período
    // ─────────────────────────────────────────────
    public function flujoCaja(Request $request): JsonResponse
    {
        $request->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
        ]);

        $desde    = $request->input('desde');
        $hasta    = $request->input('hasta');
        $cacheKey = "reporte_flujo_{$desde}_{$hasta}";

        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($desde, $hasta) {
            $rows = DB::select("
                SELECT fecha,
                       SUM(CASE WHEN tipo = 'ingreso'  THEN total_base ELSE 0 END) AS ingresos,
                       SUM(CASE WHEN tipo = 'egreso'   THEN total_base ELSE 0 END) AS egresos,
                       SUM(CASE WHEN tipo = 'ingreso'  THEN total_base
                                WHEN tipo = 'egreso'   THEN -total_base
                                ELSE 0 END) AS flujo_neto
                FROM transacciones
                WHERE estado != 'cancelada'
                  AND fecha BETWEEN ? AND ?
                GROUP BY fecha
                ORDER BY fecha ASC
            ", [$desde, $hasta]);

            // Acumular flujo
            $acumulado = 0;
            foreach ($rows as &$row) {
                $acumulado += $row->flujo_neto;
                $row->flujo_acumulado = round($acumulado, 2);
                $row->ingresos        = round($row->ingresos, 2);
                $row->egresos         = round($row->egresos, 2);
                $row->flujo_neto      = round($row->flujo_neto, 2);
            }

            return [
                'periodo' => ['desde' => $desde, 'hasta' => $hasta],
                'dias'    => $rows,
                'totales' => [
                    'ingresos'    => round(array_sum(array_column($rows, 'ingresos')), 2),
                    'egresos'     => round(array_sum(array_column($rows, 'egresos')), 2),
                    'flujo_neto'  => round(array_sum(array_column($rows, 'flujo_neto')), 2),
                ],
            ];
        });

        return response()->json($data);
    }

    // ─────────────────────────────────────────────
    // GET /api/finance/reportes/cuentas-saldo
    // Saldo de todas las cuentas activas
    // ─────────────────────────────────────────────
    public function cuentasSaldo(Request $request): JsonResponse
    {
        $cacheKey = 'reporte_cuentas_saldo';

        $data = Cache::remember($cacheKey, 300, function () { // 5 min, más dinámico
            $cuentas = DB::select("
                SELECT c.id, c.nombre, c.tipo, c.banco,
                       c.saldo_actual, m.codigo AS moneda, m.simbolo
                FROM cuentas c
                JOIN monedas m ON m.id = c.moneda_id
                WHERE c.activa = 1
                ORDER BY c.tipo, c.nombre
            ");

            $resumen = [
                'total_activos'  => 0,
                'total_pasivos'  => 0,
            ];

            foreach ($cuentas as $cuenta) {
                if (in_array($cuenta->tipo, ['caja', 'banco', 'tarjeta_debito'])) {
                    $resumen['total_activos'] += $cuenta->saldo_actual;
                } elseif (in_array($cuenta->tipo, ['tarjeta_credito', 'credito'])) {
                    $resumen['total_pasivos'] += $cuenta->saldo_actual;
                }
            }

            return [
                'cuentas'       => $cuentas,
                'resumen'       => $resumen,
                'generado_en'   => now()->toIso8601String(),
            ];
        });

        return response()->json($data);
    }

    // ─────────────────────────────────────────────
    // GET /api/finance/reportes/aging-cxc
    // Antigüedad de saldos de cuentas por cobrar
    // ─────────────────────────────────────────────
    public function agingCxC(Request $request): JsonResponse
    {
        $al       = $request->input('al', now()->toDateString());
        $cacheKey = "reporte_aging_cxc_{$al}";

        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($al) {
            $rows = DB::select("
                SELECT cxc.id, cxc.cliente, cxc.saldo,
                       cxc.fecha_emision, cxc.fecha_vencimiento, cxc.estado,
                       m.codigo AS moneda, m.simbolo,
                       DATEDIFF(?, cxc.fecha_vencimiento) AS dias_vencido
                FROM cuentas_por_cobrar cxc
                JOIN monedas m ON m.id = cxc.moneda_id
                WHERE cxc.estado IN ('pendiente', 'parcial', 'vencida')
                  AND cxc.saldo > 0
                ORDER BY dias_vencido DESC
            ", [$al]);

            $buckets = [
                'vigente'      => ['label' => 'Vigente',        'dias' => '0',       'total' => 0, 'items' => []],
                '1_30'         => ['label' => '1-30 días',      'dias' => '1-30',    'total' => 0, 'items' => []],
                '31_60'        => ['label' => '31-60 días',     'dias' => '31-60',   'total' => 0, 'items' => []],
                '61_90'        => ['label' => '61-90 días',     'dias' => '61-90',   'total' => 0, 'items' => []],
                'mas_90'       => ['label' => 'Más de 90 días', 'dias' => '>90',     'total' => 0, 'items' => []],
            ];

            foreach ($rows as $row) {
                $dias = max(0, (int) $row->dias_vencido);
                $row->dias_vencido = $dias;

                if ($dias <= 0)       { $b = 'vigente'; }
                elseif ($dias <= 30)  { $b = '1_30'; }
                elseif ($dias <= 60)  { $b = '31_60'; }
                elseif ($dias <= 90)  { $b = '61_90'; }
                else                  { $b = 'mas_90'; }

                $buckets[$b]['items'][] = $row;
                $buckets[$b]['total'] += $row->saldo;
            }

            return [
                'al'          => $al,
                'buckets'     => array_values($buckets),
                'total_saldo' => round(array_sum(array_column($buckets, 'total')), 2),
            ];
        });

        return response()->json($data);
    }

    // ─────────────────────────────────────────────
    // GET /api/finance/reportes/aging-cxp
    // Antigüedad de saldos de cuentas por pagar
    // ─────────────────────────────────────────────
    public function agingCxP(Request $request): JsonResponse
    {
        $al       = $request->input('al', now()->toDateString());
        $cacheKey = "reporte_aging_cxp_{$al}";

        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($al) {
            $rows = DB::select("
                SELECT cxp.id, p.nombre AS proveedor, cxp.saldo,
                       cxp.fecha_emision, cxp.fecha_vencimiento, cxp.estado,
                       m.codigo AS moneda, m.simbolo,
                       DATEDIFF(?, cxp.fecha_vencimiento) AS dias_vencido
                FROM cuentas_por_pagar cxp
                JOIN proveedores p  ON p.id  = cxp.proveedor_id
                JOIN monedas m      ON m.id  = cxp.moneda_id
                WHERE cxp.estado IN ('pendiente', 'parcial', 'vencida')
                  AND cxp.saldo > 0
                ORDER BY dias_vencido DESC
            ", [$al]);

            $buckets = [
                'vigente' => ['label' => 'Vigente',        'total' => 0, 'items' => []],
                '1_30'    => ['label' => '1-30 días',      'total' => 0, 'items' => []],
                '31_60'   => ['label' => '31-60 días',     'total' => 0, 'items' => []],
                '61_90'   => ['label' => '61-90 días',     'total' => 0, 'items' => []],
                'mas_90'  => ['label' => 'Más de 90 días', 'total' => 0, 'items' => []],
            ];

            foreach ($rows as $row) {
                $dias = max(0, (int) $row->dias_vencido);
                $row->dias_vencido = $dias;

                if ($dias <= 0)       { $b = 'vigente'; }
                elseif ($dias <= 30)  { $b = '1_30'; }
                elseif ($dias <= 60)  { $b = '31_60'; }
                elseif ($dias <= 90)  { $b = '61_90'; }
                else                  { $b = 'mas_90'; }

                $buckets[$b]['items'][] = $row;
                $buckets[$b]['total'] += $row->saldo;
            }

            return [
                'al'          => $al,
                'buckets'     => array_values($buckets),
                'total_saldo' => round(array_sum(array_column($buckets, 'total')), 2),
            ];
        });

        return response()->json($data);
    }
}
