<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Almacen;
use App\Models\Product;
use App\Models\VentaItem;
use App\Services\GroqService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StatsController extends Controller
{
    private const STALE_DAYS = 30;

    /**
     * Devuelve agregados para las gráficas (sin IA).
     */
    public function index(Request $request)
    {
        $days = (int) $request->input('days', 90);
        $days = max(7, min(365, $days));
        $since = Carbon::now()->subDays($days);
        $almacenIds = $this->accesibleAlmacenIds();

        return response()->json([
            'top_products' => $this->topProducts($since, 10, $almacenIds),
            'daily_sales' => $this->dailySales($since, $almacenIds),
            'stale_products' => $this->staleProducts($almacenIds),
            'summary' => $this->summary($since, $almacenIds),
            'period_days' => $days,
        ]);
    }

    /**
     * Chatbot IA: responde preguntas sobre el inventario con datos reales.
     */
    public function chat(Request $request, GroqService $ai)
    {
        // El modelo local puede tardar; ampliamos el límite de ejecución.
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        try {
            $context = $this->inventorySnapshot();
            $reply = $ai->chat($validated['message'], $context);

            Log::info('Chat IA inventario', [
                'user_id' => auth()->id(),
                'chars' => strlen($reply),
            ]);

            return response()->json(['reply' => $reply]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => 'El asistente IA no está disponible. Intenta de nuevo en unos momentos.',
            ], 503);
        }
    }

    /**
     * Snapshot compacto del inventario para dar contexto a la IA.
     *
     * @return array<string,mixed>
     */
    private function inventorySnapshot(): array
    {
        $since = Carbon::now()->subDays(30);
        $user = auth()->user();
        $almacenIds = $this->accesibleAlmacenIds();

        $totalProductos = Product::accesiblesPara($user)->where('is_active', true)->count();
        $stockTotal = (int) Product::accesiblesPara($user)->where('is_active', true)->sum('stock');
        $valorInventario = (float) Product::accesiblesPara($user)->where('is_active', true)
            ->selectRaw('COALESCE(SUM(stock * cost), 0) as v')->value('v');

        $stockBajo = Product::accesiblesPara($user)
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->orderBy('stock')
            ->limit(20)
            ->get(['sku', 'name', 'stock', 'min_stock'])
            ->map(fn ($p) => [
                'sku' => $p->sku,
                'name' => $p->name,
                'stock' => (int) $p->stock,
                'min_stock' => (int) $p->min_stock,
            ])
            ->all();

        return [
            'generado' => now()->toDateTimeString(),
            'total_productos_activos' => $totalProductos,
            'unidades_en_stock' => $stockTotal,
            'valor_inventario_costo' => round($valorInventario, 2),
            'productos_stock_bajo' => $stockBajo,
            'ventas_ultimos_30d' => $this->summary($since, $almacenIds),
            'top_productos_30d' => $this->topProducts($since, 5, $almacenIds),
        ];
    }

    /**
     * IDs de almacenes accesibles para el usuario actual (admin ve todos).
     *
     * @return array<int,int>
     */
    private function accesibleAlmacenIds(): array
    {
        return Almacen::accesiblesPara(auth()->user())->pluck('id')->all();
    }

    // ─── Agregados ────────────────────────────────────────────

    /**
     * @param  array<int,int>  $almacenIds
     */
    private function topProducts(Carbon $since, int $limit, array $almacenIds): array
    {
        return VentaItem::query()
            ->select('product_id',
                DB::raw('SUM(cantidad) as total_unidades'),
                DB::raw('SUM(subtotal) as total_ingresos'))
            ->whereHas('venta', fn ($q) => $q->where('fecha', '>=', $since)
                ->where('estado', 'completada')
                ->whereIn('almacen_id', $almacenIds))
            ->with('product:id,name,sku')
            ->groupBy('product_id')
            ->orderByDesc('total_unidades')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'sku' => $r->product?->sku ?? 'N/A',
                'name' => $r->product?->name ?? 'Eliminado',
                'unidades' => (int) $r->total_unidades,
                'ingresos' => (float) $r->total_ingresos,
            ])
            ->all();
    }

    /**
     * @param  array<int,int>  $almacenIds
     */
    private function dailySales(Carbon $since, array $almacenIds): array
    {
        return DB::table('ventas')
            ->select(
                DB::raw('DATE(fecha) as dia'),
                DB::raw('COUNT(*) as ventas'),
                DB::raw('SUM(total) as ingresos')
            )
            ->where('fecha', '>=', $since)
            ->where('estado', 'completada')
            ->whereIn('almacen_id', $almacenIds)
            ->groupBy('dia')
            ->orderBy('dia')
            ->get()
            ->map(fn ($r) => [
                'dia' => $r->dia,
                'ventas' => (int) $r->ventas,
                'ingresos' => (float) $r->ingresos,
            ])
            ->all();
    }

    /**
     * @param  array<int,int>  $almacenIds
     */
    private function staleProducts(array $almacenIds): array
    {
        $threshold = Carbon::now()->subDays(self::STALE_DAYS);

        return Product::accesiblesPara(auth()->user())
            ->select('products.id', 'products.sku', 'products.name', 'products.stock',
                DB::raw('MAX(ventas.fecha) as ultima_venta'))
            ->leftJoin('venta_items', 'venta_items.product_id', '=', 'products.id')
            ->leftJoin('ventas', function ($j) use ($almacenIds) {
                $j->on('ventas.id', '=', 'venta_items.venta_id')
                    ->where('ventas.estado', '=', 'completada')
                    ->whereIn('ventas.almacen_id', $almacenIds);
            })
            ->where('products.is_active', true)
            ->groupBy('products.id', 'products.sku', 'products.name', 'products.stock')
            ->havingRaw('MAX(ventas.fecha) IS NULL OR MAX(ventas.fecha) < ?', [$threshold])
            ->orderByRaw('MAX(ventas.fecha) ASC')
            ->limit(20)
            ->get()
            ->map(fn ($r) => [
                'sku' => $r->sku,
                'name' => $r->name,
                'stock' => (int) $r->stock,
                'ultima_venta' => $r->ultima_venta,
                'dias_sin_venta' => $r->ultima_venta
                    ? Carbon::parse($r->ultima_venta)->diffInDays(now())
                    : null,
            ])
            ->all();
    }

    /**
     * @param  array<int,int>  $almacenIds
     */
    private function summary(Carbon $since, array $almacenIds): array
    {
        $ventas = DB::table('ventas')
            ->where('fecha', '>=', $since)
            ->where('estado', 'completada')
            ->whereIn('almacen_id', $almacenIds)
            ->selectRaw('COUNT(*) as total_ventas, COALESCE(SUM(total),0) as ingresos, COALESCE(AVG(total),0) as ticket_prom')
            ->first();

        $unidades = DB::table('venta_items')
            ->join('ventas', 'ventas.id', '=', 'venta_items.venta_id')
            ->where('ventas.fecha', '>=', $since)
            ->where('ventas.estado', 'completada')
            ->whereIn('ventas.almacen_id', $almacenIds)
            ->sum('cantidad');

        return [
            'total_ventas' => (int) $ventas->total_ventas,
            'ingresos_totales' => (float) $ventas->ingresos,
            'ticket_promedio' => round((float) $ventas->ticket_prom, 2),
            'unidades_vendidas' => (int) $unidades,
        ];
    }
}
