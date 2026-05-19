<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\VentaItem;
use App\Services\OllamaService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StatsController extends Controller
{
    private const CACHE_TTL_REPORT = 7200; // 2h
    private const STALE_DAYS = 30;

    /**
     * Devuelve agregados para las gráficas (sin IA).
     */
    public function index(Request $request)
    {
        $days = (int) $request->input('days', 90);
        $days = max(7, min(365, $days));
        $since = Carbon::now()->subDays($days);

        return response()->json([
            'top_products'   => $this->topProducts($since, 10),
            'daily_sales'    => $this->dailySales($since),
            'stale_products' => $this->staleProducts(),
            'summary'        => $this->summary($since),
            'period_days'    => $days,
        ]);
    }

    /**
     * Genera informe IA (cacheado).
     */
    public function report(Request $request, OllamaService $ollama)
    {
        // PHP corta scripts en 30s por defecto; el informe IA puede tardar más.
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        $days = (int) $request->input('days', 90);
        $days = max(7, min(365, $days));

        $force = $request->boolean('refresh', false);
        $cacheKey = "stats.report.{$days}";

        if ($force) {
            Cache::forget($cacheKey);
        }

        try {
            $report = Cache::remember($cacheKey, self::CACHE_TTL_REPORT, function () use ($ollama, $days) {
                $since = Carbon::now()->subDays($days);

                $data = [
                    'period_days'    => $days,
                    'summary'        => $this->summary($since),
                    'top_products'   => $this->topProducts($since, 10),
                    'stale_products' => array_slice($this->staleProducts(), 0, 10),
                    'monthly_trend'  => $this->monthlyTrend($since),
                ];

                $prompt = $this->buildPrompt($data);
                $system = 'Eres un analista de negocios experto en retail. Respondes SIEMPRE en español, en formato Markdown limpio, con secciones claras y bullets. Sé conciso y orientado a acciones.';

                $content = $ollama->generate($prompt, $system);

                Log::info('Informe IA generado', [
                    'user_id' => auth()->id(),
                    'days'    => $days,
                    'chars'   => strlen($content),
                ]);

                return [
                    'content'      => $content,
                    'generated_at' => now()->toIso8601String(),
                    'data'         => $data,
                ];
            });

            return response()->json($report + ['cached' => ! $force]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'hint'    => 'Verifica que Ollama esté corriendo en ' . config('services.ollama.url'),
            ], 503);
        }
    }

    // ─── Agregados ────────────────────────────────────────────

    private function topProducts(Carbon $since, int $limit = 10): array
    {
        return VentaItem::query()
            ->select('product_id',
                DB::raw('SUM(cantidad) as total_unidades'),
                DB::raw('SUM(subtotal) as total_ingresos'))
            ->whereHas('venta', fn ($q) => $q->where('fecha', '>=', $since)->where('estado', 'completada'))
            ->with('product:id,name,sku')
            ->groupBy('product_id')
            ->orderByDesc('total_unidades')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'sku'       => $r->product?->sku ?? 'N/A',
                'name'      => $r->product?->name ?? 'Eliminado',
                'unidades'  => (int) $r->total_unidades,
                'ingresos'  => (float) $r->total_ingresos,
            ])
            ->all();
    }

    private function dailySales(Carbon $since): array
    {
        return DB::table('ventas')
            ->select(
                DB::raw('DATE(fecha) as dia'),
                DB::raw('COUNT(*) as ventas'),
                DB::raw('SUM(total) as ingresos')
            )
            ->where('fecha', '>=', $since)
            ->where('estado', 'completada')
            ->groupBy('dia')
            ->orderBy('dia')
            ->get()
            ->map(fn ($r) => [
                'dia'      => $r->dia,
                'ventas'   => (int) $r->ventas,
                'ingresos' => (float) $r->ingresos,
            ])
            ->all();
    }

    private function staleProducts(): array
    {
        $threshold = Carbon::now()->subDays(self::STALE_DAYS);

        return Product::query()
            ->select('products.id', 'products.sku', 'products.name', 'products.stock',
                DB::raw('MAX(ventas.fecha) as ultima_venta'))
            ->leftJoin('venta_items', 'venta_items.product_id', '=', 'products.id')
            ->leftJoin('ventas', function ($j) {
                $j->on('ventas.id', '=', 'venta_items.venta_id')
                  ->where('ventas.estado', '=', 'completada');
            })
            ->where('products.is_active', true)
            ->groupBy('products.id', 'products.sku', 'products.name', 'products.stock')
            ->havingRaw('MAX(ventas.fecha) IS NULL OR MAX(ventas.fecha) < ?', [$threshold])
            ->orderByRaw('MAX(ventas.fecha) ASC')
            ->limit(20)
            ->get()
            ->map(fn ($r) => [
                'sku'           => $r->sku,
                'name'          => $r->name,
                'stock'         => (int) $r->stock,
                'ultima_venta'  => $r->ultima_venta,
                'dias_sin_venta' => $r->ultima_venta
                    ? Carbon::parse($r->ultima_venta)->diffInDays(now())
                    : null,
            ])
            ->all();
    }

    private function summary(Carbon $since): array
    {
        $ventas = DB::table('ventas')
            ->where('fecha', '>=', $since)
            ->where('estado', 'completada')
            ->selectRaw('COUNT(*) as total_ventas, COALESCE(SUM(total),0) as ingresos, COALESCE(AVG(total),0) as ticket_prom')
            ->first();

        $unidades = DB::table('venta_items')
            ->join('ventas', 'ventas.id', '=', 'venta_items.venta_id')
            ->where('ventas.fecha', '>=', $since)
            ->where('ventas.estado', 'completada')
            ->sum('cantidad');

        return [
            'total_ventas'      => (int) $ventas->total_ventas,
            'ingresos_totales'  => (float) $ventas->ingresos,
            'ticket_promedio'   => round((float) $ventas->ticket_prom, 2),
            'unidades_vendidas' => (int) $unidades,
        ];
    }

    private function monthlyTrend(Carbon $since): array
    {
        return DB::table('ventas')
            ->select(
                DB::raw("DATE_FORMAT(fecha, '%Y-%m') as mes"),
                DB::raw('COUNT(*) as ventas'),
                DB::raw('SUM(total) as ingresos')
            )
            ->where('fecha', '>=', $since)
            ->where('estado', 'completada')
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->map(fn ($r) => [
                'mes'      => $r->mes,
                'ventas'   => (int) $r->ventas,
                'ingresos' => (float) $r->ingresos,
            ])
            ->all();
    }

    private function buildPrompt(array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
Analiza estos datos de ventas ({$data['period_days']} días) y genera un informe BREVE en español, Markdown.

DATOS:
```json
{$json}
```

Usa EXACTAMENTE estas secciones, MUY concisas (máximo 2 bullets cada una):

## Resumen
1 línea con lo clave.

## Top 3 Productos
Solo los 3 más vendidos con número de unidades.

## Productos Estancados
2-3 productos sin movimiento + acción sugerida (1 línea c/u).

## Proyección
1 línea: estimación próximas 4 semanas.

## Acciones Recomendadas
3 bullets cortos, accionables.

REGLAS:
- Sé telegráfico. Sin párrafos largos.
- Usa números reales del JSON.
- No inventes datos.
PROMPT;
    }
}
