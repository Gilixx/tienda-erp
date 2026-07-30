<?php

namespace App\Http\Controllers;

use App\Models\Inventory\Almacen;
use App\Models\Inventory\ProductStock;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Venta;
use App\Models\VentaItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PosController extends Controller
{
    private const METODOS = ['efectivo', 'tarjeta', 'transferencia'];

    /** Carga el almacén y aborta 403 si el usuario no tiene acceso. */
    private function authorizeAlmacen($almacenId): Almacen
    {
        $almacen = Almacen::findOrFail($almacenId);

        if (! $almacen->accesiblePara(request()->user())) {
            throw new HttpException(403, 'No tienes acceso a este almacén.');
        }

        return $almacen;
    }

    /** IDs de almacenes accesibles para el usuario actual. */
    private function accesibleAlmacenIds()
    {
        return Almacen::accesiblesPara(request()->user())->pluck('id');
    }

    /**
     * GET /api/pos/products/search?q=&almacen_id=
     * Busca productos por nombre o SKU y devuelve stock en el almacén dado.
     */
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        if ($q === '') {
            return response()->json([]);
        }

        $almacenId = $request->integer('almacen_id') ?: null;
        if ($almacenId) {
            $this->authorizeAlmacen($almacenId);
        }

        $productos = Product::query()
            ->where('is_active', true)
            ->where(function ($w) use ($q) {
                $w->where('sku', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'sku', 'name', 'price', 'stock', 'requiere_serie']);

        $stockMap = [];
        if ($almacenId && $productos->isNotEmpty()) {
            $stockMap = ProductStock::where('almacen_id', $almacenId)
                ->whereIn('product_id', $productos->pluck('id'))
                ->pluck('cantidad', 'product_id')
                ->all();
        }

        $data = $productos->map(fn ($p) => [
            'id' => $p->id,
            'sku' => $p->sku,
            'name' => $p->name,
            'price' => (float) $p->price,
            'stock_almacen' => $almacenId ? (int) ($stockMap[$p->id] ?? 0) : (int) $p->stock,
            'requiere_serie' => (bool) $p->requiere_serie,
        ]);

        return response()->json($data);
    }

    /**
     * POST /api/pos/ventas
     * Crea una venta, descuenta stock y registra los movimientos de salida.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'almacen_id' => 'required|exists:almacenes,id',
            'metodo_pago' => 'required|in:'.implode(',', self::METODOS),
            'cliente_nombre' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.precio_unit' => 'required|numeric|min:0',
            'items.*.serial' => 'nullable|string|max:255',
        ]);

        $this->authorizeAlmacen($validated['almacen_id']);

        try {
            $venta = DB::transaction(function () use ($validated) {
                $almacenId = $validated['almacen_id'];
                $total = 0.0;

                // Validación de stock con lock por producto
                $lineas = [];
                foreach ($validated['items'] as $item) {
                    $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                    $stockRow = ProductStock::lockForUpdate()
                        ->where('product_id', $product->id)
                        ->where('almacen_id', $almacenId)
                        ->first();

                    $disponible = $stockRow?->cantidad ?? 0;
                    if ($disponible < $item['cantidad']) {
                        throw new HttpException(422, "Stock insuficiente de {$product->name} (disponible: {$disponible}).");
                    }

                    $subtotal = round($item['cantidad'] * $item['precio_unit'], 2);
                    $total += $subtotal;

                    $lineas[] = [
                        'product' => $product,
                        'stockRow' => $stockRow,
                        'cantidad' => $item['cantidad'],
                        'precio' => round((float) $item['precio_unit'], 2),
                        'subtotal' => $subtotal,
                        'serial' => $item['serial'] ?? null,
                    ];
                }

                $venta = Venta::create([
                    'user_id' => auth()->id(),
                    'almacen_id' => $almacenId,
                    'total' => round($total, 2),
                    'estado' => 'completada',
                    'fecha' => now(),
                    'metodo_pago' => $validated['metodo_pago'],
                    'cliente' => $validated['cliente_nombre'] ?? null,
                    'referencia' => 'POS',
                ]);

                foreach ($lineas as $l) {
                    VentaItem::create([
                        'venta_id' => $venta->id,
                        'product_id' => $l['product']->id,
                        'cantidad' => $l['cantidad'],
                        'precio_unit' => $l['precio'],
                        'subtotal' => $l['subtotal'],
                    ]);

                    // Descontar stock por almacén y global
                    $l['stockRow']->decrement('cantidad', $l['cantidad']);
                    $l['stockRow']->touch();
                    $l['product']->decrement('stock', $l['cantidad']);

                    InventoryMovement::create([
                        'product_id' => $l['product']->id,
                        'almacen_id' => $almacenId,
                        'user_id' => auth()->id(),
                        'type' => 'out',
                        'quantity' => -$l['cantidad'],
                        'reference' => 'Venta #'.$venta->id,
                        'notes' => $l['serial'] ? ('Serie: '.$l['serial']) : null,
                    ]);
                }

                return $venta;
            });
        } catch (HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }

        return response()->json(
            $venta->load(['items.product:id,name,sku', 'almacen:id,nombre,codigo', 'user:id,name']),
            201
        );
    }

    /**
     * GET /api/pos/ventas — listado con filtros (fecha, método de pago).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Venta::query()
            ->with(['almacen:id,nombre,codigo', 'user:id,name'])
            ->whereIn('almacen_id', $this->accesibleAlmacenIds())
            ->where('estado', 'completada')
            ->latest('fecha');

        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->date('fecha'));
        }
        if ($request->filled('desde')) {
            $query->whereDate('fecha', '>=', $request->date('desde'));
        }
        if ($request->filled('hasta')) {
            $query->whereDate('fecha', '<=', $request->date('hasta'));
        }
        if ($request->filled('metodo_pago')) {
            $query->where('metodo_pago', $request->input('metodo_pago'));
        }

        return response()->json($query->paginate(min(100, $request->integer('per_page', 30))));
    }

    /**
     * GET /api/pos/ventas/{id} — detalle para el ticket.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $venta = Venta::with(['items.product:id,name,sku', 'almacen:id,nombre,codigo', 'user:id,name'])
            ->findOrFail($id);

        if ($venta->almacen_id && ! $this->accesibleAlmacenIds()->contains($venta->almacen_id)) {
            throw new HttpException(403, 'No tienes acceso a esta venta.');
        }

        return response()->json($venta);
    }

    /**
     * GET /api/pos/stats — agregados del período (por defecto hoy).
     */
    public function stats(Request $request): JsonResponse
    {
        $desde = $request->filled('desde') ? $request->date('desde')->startOfDay() : now()->startOfDay();
        $hasta = $request->filled('hasta') ? $request->date('hasta')->endOfDay() : now()->endOfDay();

        $almacenes = $this->accesibleAlmacenIds();

        $base = Venta::query()
            ->whereIn('almacen_id', $almacenes)
            ->where('estado', 'completada')
            ->whereBetween('fecha', [$desde, $hasta]);

        $resumen = (clone $base)
            ->selectRaw('COUNT(*) as total_ventas, COALESCE(SUM(total),0) as ingresos, COALESCE(AVG(total),0) as ticket_prom')
            ->first();

        $porMetodo = (clone $base)
            ->selectRaw('metodo_pago, COUNT(*) as n, COALESCE(SUM(total),0) as ingresos')
            ->groupBy('metodo_pago')
            ->orderByDesc('n')
            ->get();

        $topProductos = VentaItem::query()
            ->select('product_id',
                DB::raw('SUM(cantidad) as unidades'),
                DB::raw('SUM(subtotal) as ingresos'))
            ->whereHas('venta', fn ($q) => $q
                ->whereIn('almacen_id', $almacenes)
                ->where('estado', 'completada')
                ->whereBetween('fecha', [$desde, $hasta]))
            ->with('product:id,name,sku')
            ->groupBy('product_id')
            ->orderByDesc('unidades')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'sku' => $r->product?->sku ?? 'N/A',
                'name' => $r->product?->name ?? 'Eliminado',
                'unidades' => (int) $r->unidades,
                'ingresos' => (float) $r->ingresos,
            ]);

        return response()->json([
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'total_ventas' => (int) $resumen->total_ventas,
            'ingresos_totales' => (float) $resumen->ingresos,
            'ticket_promedio' => round((float) $resumen->ticket_prom, 2),
            'metodo_top' => $porMetodo->first()->metodo_pago ?? null,
            'por_metodo' => $porMetodo,
            'top_productos' => $topProductos,
        ]);
    }
}
