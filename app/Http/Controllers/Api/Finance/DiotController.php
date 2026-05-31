<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reporte DIOT — Declaración Informativa de Operaciones con Terceros (SAT México).
 * Formato: CSV con layout SAT (versión simplificada para PYMES).
 */
class DiotController extends Controller
{
    /**
     * GET /api/finance/reportes/diot?anio=2026&mes=5
     * Devuelve JSON con los datos o exporta CSV si ?formato=csv
     */
    public function index(Request $request): mixed
    {
        $request->validate([
            'anio' => 'required|integer|min:2020|max:2099',
            'mes'  => 'required|integer|min:1|max:12',
        ]);

        $anio  = $request->integer('anio');
        $mes   = $request->integer('mes');
        $desde = sprintf('%d-%02d-01', $anio, $mes);
        $hasta = date('Y-m-t', strtotime($desde)); // último día del mes

        /**
         * Agrupa compras por proveedor + tasa de IVA para el período.
         * Incluye: subtotal, IVA pagado, retenciones.
         */
        $rows = DB::select("
            SELECT
                p.rfc                                          AS rfc_tercero,
                p.nombre                                       AS nombre_tercero,
                p.tipo_tercero                                 AS tipo_tercero,
                COALESCE(i.tasa, 0)                            AS tasa_iva,
                SUM(ci.subtotal)                               AS valor_actos,
                SUM(CASE WHEN i.aplicacion = 'traslado' THEN ci.impuesto_monto ELSE 0 END) AS iva_pagado,
                SUM(CASE WHEN i.aplicacion = 'retencion' THEN ci.impuesto_monto ELSE 0 END) AS iva_retenido,
                COUNT(DISTINCT c.id)                           AS num_compras
            FROM compras c
            JOIN proveedores p       ON p.id  = c.proveedor_id
            JOIN compra_items ci     ON ci.compra_id = c.id
            LEFT JOIN impuestos i    ON i.id  = ci.impuesto_id
            WHERE c.estado IN ('recibida','pagada')
              AND c.fecha BETWEEN ? AND ?
            GROUP BY p.rfc, p.nombre, p.tipo_tercero, i.tasa, i.aplicacion
            ORDER BY p.nombre ASC
        ", [$desde, $hasta]);

        if ($request->input('formato') === 'csv') {
            return $this->exportarCsv($rows, $anio, $mes);
        }

        return response()->json([
            'periodo'      => ['anio' => $anio, 'mes' => $mes, 'desde' => $desde, 'hasta' => $hasta],
            'total_filas'  => count($rows),
            'total_iva'    => round(array_sum(array_column($rows, 'iva_pagado')), 2),
            'rows'         => $rows,
        ]);
    }

    private function exportarCsv(array $rows, int $anio, int $mes): StreamedResponse
    {
        $filename = "DIOT_{$anio}_{$mes}.csv";

        return response()->stream(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 para compatibilidad con Excel
            fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Encabezado DIOT simplificado
            fputcsv($handle, [
                'RFC_TERCERO', 'NOMBRE_TERCERO', 'TIPO_TERCERO',
                'TASA_IVA', 'VALOR_ACTOS', 'IVA_PAGADO',
                'IVA_RETENIDO', 'NUM_COMPRAS',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->rfc_tercero    ?? 'XAXX010101000', // RFC genérico si no tiene
                    $row->nombre_tercero ?? '',
                    $row->tipo_tercero   ?? '04', // 04 = proveedor nacional
                    number_format((float) $row->tasa_iva, 2, '.', ''),
                    number_format((float) $row->valor_actos, 2, '.', ''),
                    number_format((float) $row->iva_pagado, 2, '.', ''),
                    number_format((float) $row->iva_retenido, 2, '.', ''),
                    $row->num_compras,
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
