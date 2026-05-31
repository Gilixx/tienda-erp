<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\CuentaPorCobrar;
use App\Models\Finance\DevolucionVenta;
use App\Models\Finance\DevolucionVentaItem;
use App\Models\Finance\Cuenta;
use App\Models\Finance\Transaccion;
use App\Models\Finance\CategoriaFinanza;
use App\Models\Inventory\Almacen;
use App\Models\Inventory\ProductStock;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Venta;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DevolucionVentaController extends Controller
{
    /** GET /api/finance/devoluciones-venta */
    public function index(Request $request): JsonResponse
    {
        $query = DevolucionVenta::with([
            'venta:id,referencia,fecha',
            'moneda:id,codigo,simbolo',
            'user:id,name',
        ])->latest('fecha')->latest('id');

        if ($request->filled('venta_id')) {
            $query->where('venta_id', $request->integer('venta_id'));
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        $perPage = min(100, max(10, $request->integer('per_page', 25)));

        return response()->json($query->paginate($perPage));
    }

    /** GET /api/finance/devoluciones-venta/{id} */
    public function show(string $id): JsonResponse
    {
        $devolucion = DevolucionVenta::with([
            'venta', 'moneda', 'user:id,name',
            'almacen:id,nombre', 'cuenta:id,nombre,tipo',
            'items.product:id,name,sku', 'items.ventaItem',
        ])->findOrFail($id);

        return response()->json($devolucion);
    }

    /**
     * POST /api/finance/devoluciones-venta
     * Crea y aplica la devolución de venta.
     */
    public function store(Request $request, CurrencyService $fx): JsonResponse
    {
        $validated = $request->validate([
            'venta_id'           => 'required|exists:ventas,id',
            'almacen_id'         => 'nullable|exists:almacenes,id',
            'cuenta_id'          => 'nullable|exists:cuentas,id',
            'fecha'              => 'required|date',
            'motivo'             => 'nullable|string|max:500',
            'referencia'         => 'nullable|string|max:100',
            'genera_nota_credito' => 'boolean',
            'notas'              => 'nullable|string|max:1000',
            'items'                       => 'required|array|min:1',
            'items.*.product_id'          => 'required|exists:products,id',
            'items.*.venta_item_id'       => 'nullable|exists:venta_items,id',
            'items.*.cantidad_devuelta'   => 'required|numeric|min:0.0001',
            'items.*.precio_unit'         => 'required|numeric|min:0',
        ]);

        try {
            return DB::transaction(function () use ($validated, $fx) {
                $venta = Venta::with('items')->findOrFail($validated['venta_id']);

                if (! in_array($venta->estado, ['completada'])) {
                    return response()->json(['error' => 'Solo se pueden devolver ventas completadas.'], 422);
                }

                $almacenId = $validated['almacen_id'] ?? null;
                if (! $almacenId) {
                    $almacenId = Almacen::where('es_principal', true)->value('id');
                }

                $subtotal  = 0;
                $itemsData = [];

                foreach ($validated['items'] as $row) {
                    $sub = round($row['cantidad_devuelta'] * $row['precio_unit'], 4);
                    $subtotal += $sub;
                    $itemsData[] = [
                        'product_id'        => $row['product_id'],
                        'venta_item_id'     => $row['venta_item_id'] ?? null,
                        'cantidad_devuelta' => $row['cantidad_devuelta'],
                        'precio_unit'       => $row['precio_unit'],
                        'subtotal'          => $sub,
                        'impuesto_monto'    => 0,
                        'total'             => $sub,
                    ];
                }

                $total     = round($subtotal, 4);
                $monedaId  = $venta->moneda_id;
                $tcBase    = $fx->rate($monedaId, $fx->base()->id, Carbon::parse($validated['fecha']));
                $totalBase = round($total * $tcBase, 4);

                $devolucion = DevolucionVenta::create([
                    'venta_id'           => $venta->id,
                    'user_id'            => auth()->id(),
                    'moneda_id'          => $monedaId,
                    'almacen_id'         => $almacenId,
                    'cuenta_id'          => $validated['cuenta_id'] ?? null,
                    'tipo_cambio'        => $tcBase,
                    'subtotal'           => $subtotal,
                    'impuestos'          => 0,
                    'total'              => $total,
                    'total_base'         => $totalBase,
                    'motivo'             => $validated['motivo'] ?? null,
                    'estado'             => 'borrador',
                    'genera_nota_credito' => $validated['genera_nota_credito'] ?? false,
                    'fecha'              => $validated['fecha'],
                    'referencia'         => $validated['referencia'] ?? null,
                    'notas'              => $validated['notas'] ?? null,
                ]);

                foreach ($itemsData as $row) {
                    $devolucion->items()->create($row);
                }

                // Aplicar devolución
                $this->aplicarDevolucion($devolucion, $almacenId, $fx);

                return response()->json(
                    $devolucion->fresh()->load(['venta', 'moneda', 'items.product']),
                    201
                );
            });
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Error al registrar la devolución: ' . $e->getMessage()], 500);
        }
    }

    /** Lógica interna de aplicación de devolución de venta */
    private function aplicarDevolucion(DevolucionVenta $devolucion, ?int $almacenId, CurrencyService $fx): void
    {
        foreach ($devolucion->items as $item) {
            $cantidad = (int) $item->cantidad_devuelta;
            $product  = Product::lockForUpdate()->findOrFail($item->product_id);

            // Reintegrar stock global
            $product->increment('stock', $cantidad);

            // Reintegrar stock por almacén
            if ($almacenId) {
                $stockRow = ProductStock::lockForUpdate()
                    ->where('product_id', $item->product_id)
                    ->where('almacen_id', $almacenId)
                    ->first();

                if ($stockRow) {
                    $stockRow->increment('cantidad', $cantidad);
                    $stockRow->touch();
                } else {
                    ProductStock::create([
                        'product_id' => $item->product_id,
                        'almacen_id' => $almacenId,
                        'cantidad'   => $cantidad,
                    ]);
                }
            }

            InventoryMovement::create([
                'product_id'  => $item->product_id,
                'almacen_id'  => $almacenId,
                'user_id'     => auth()->id(),
                'type'        => 'in',
                'quantity'    => $cantidad,
                'reference'   => 'DEV-VENTA-' . $devolucion->id,
                'notes'       => 'Reingreso por devolución de venta. Motivo: ' . ($devolucion->motivo ?? 'N/E'),
            ]);
        }

        // Si hay cuenta, registrar reembolso
        if ($devolucion->cuenta_id) {
            $cuenta = Cuenta::lockForUpdate()->findOrFail($devolucion->cuenta_id);

            $categoria = CategoriaFinanza::where('tipo', 'egreso')
                ->where('nombre', 'like', '%Devolucion%')
                ->first();

            Transaccion::create([
                'cuenta_id'    => $cuenta->id,
                'categoria_id' => $categoria?->id,
                'user_id'      => auth()->id(),
                'tipo'         => 'egreso',
                'moneda_id'    => $devolucion->moneda_id,
                'tipo_cambio'  => $devolucion->tipo_cambio,
                'subtotal'     => $devolucion->subtotal,
                'impuestos'    => 0,
                'total'        => $devolucion->total,
                'total_base'   => $devolucion->total_base,
                'fecha'        => $devolucion->fecha,
                'descripcion'  => 'Devolución de venta #' . $devolucion->venta_id,
                'estado'       => 'conciliada',
            ]);

            $cuenta->decrement('saldo_actual', $devolucion->total);
        }

        // Ajustar CxC si existe
        $cxc = CuentaPorCobrar::where('venta_id', $devolucion->venta_id)
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->first();

        if ($cxc) {
            $nuevoSaldo = max(0, $cxc->saldo - $devolucion->total);
            $nuevoEstado = $nuevoSaldo <= 0 ? 'pagada' : ($nuevoSaldo < $cxc->monto_total ? 'parcial' : 'pendiente');
            $cxc->update(['saldo' => $nuevoSaldo, 'estado' => $nuevoEstado]);
        }

        $devolucion->update(['estado' => 'aplicada']);
    }
}
