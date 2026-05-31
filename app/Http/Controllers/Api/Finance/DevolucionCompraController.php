<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Compra;
use App\Models\Finance\CuentaPorPagar;
use App\Models\Finance\DevolucionCompra;
use App\Models\Finance\DevolucionCompraItem;
use App\Models\Inventory\Almacen;
use App\Models\Inventory\ProductStock;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DevolucionCompraController extends Controller
{
    /** GET /api/finance/devoluciones-compra */
    public function index(Request $request): JsonResponse
    {
        $query = DevolucionCompra::with([
            'proveedor:id,nombre',
            'moneda:id,codigo,simbolo',
            'user:id,name',
        ])->latest('fecha')->latest('id');

        if ($request->filled('proveedor_id')) {
            $query->where('proveedor_id', $request->integer('proveedor_id'));
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }
        if ($request->filled('compra_id')) {
            $query->where('compra_id', $request->integer('compra_id'));
        }

        $perPage = min(100, max(10, $request->integer('per_page', 25)));

        return response()->json($query->paginate($perPage));
    }

    /** GET /api/finance/devoluciones-compra/{id} */
    public function show(string $id): JsonResponse
    {
        $devolucion = DevolucionCompra::with([
            'compra', 'proveedor', 'moneda', 'user:id,name', 'almacen:id,nombre',
            'items.product:id,name,sku', 'items.compraItem',
        ])->findOrFail($id);

        return response()->json($devolucion);
    }

    /**
     * POST /api/finance/devoluciones-compra
     * Crea la devolución en estado borrador y la aplica en un solo paso.
     */
    public function store(Request $request, CurrencyService $fx): JsonResponse
    {
        $validated = $request->validate([
            'compra_id'   => 'required|exists:compras,id',
            'almacen_id'  => 'nullable|exists:almacenes,id',
            'fecha'       => 'required|date',
            'motivo'      => 'nullable|string|max:500',
            'referencia'  => 'nullable|string|max:100',
            'notas'       => 'nullable|string|max:1000',
            'items'                         => 'required|array|min:1',
            'items.*.product_id'            => 'required|exists:products,id',
            'items.*.compra_item_id'        => 'nullable|exists:compra_items,id',
            'items.*.cantidad_devuelta'     => 'required|numeric|min:0.0001',
            'items.*.costo_unit'            => 'required|numeric|min:0',
        ]);

        try {
            return DB::transaction(function () use ($validated, $fx) {
                $compra = Compra::with('proveedor')->findOrFail($validated['compra_id']);

                if (! in_array($compra->estado, ['recibida', 'pagada'])) {
                    return response()->json(['error' => 'Solo se pueden devolver compras recibidas o pagadas.'], 422);
                }

                // Determinar almacén origen (de donde se retira el stock)
                $almacenId = $validated['almacen_id'] ?? null;
                if (! $almacenId) {
                    $almacenId = Almacen::where('es_principal', true)->value('id');
                }

                $subtotal  = 0;
                $impuestos = 0;
                $itemsData = [];

                foreach ($validated['items'] as $row) {
                    $sub = round($row['cantidad_devuelta'] * $row['costo_unit'], 4);
                    $subtotal += $sub;
                    $itemsData[] = [
                        'product_id'        => $row['product_id'],
                        'compra_item_id'    => $row['compra_item_id'] ?? null,
                        'cantidad_devuelta' => $row['cantidad_devuelta'],
                        'costo_unit'        => $row['costo_unit'],
                        'subtotal'          => $sub,
                        'impuesto_monto'    => 0,
                        'total'             => $sub,
                    ];
                }

                $total     = round($subtotal + $impuestos, 4);
                $tcBase    = $fx->rate($compra->moneda_id, $fx->base()->id, Carbon::parse($validated['fecha']));
                $totalBase = round($total * $tcBase, 4);

                $devolucion = DevolucionCompra::create([
                    'compra_id'    => $compra->id,
                    'proveedor_id' => $compra->proveedor_id,
                    'user_id'      => auth()->id(),
                    'moneda_id'    => $compra->moneda_id,
                    'almacen_id'   => $almacenId,
                    'tipo_cambio'  => $tcBase,
                    'subtotal'     => $subtotal,
                    'impuestos'    => $impuestos,
                    'total'        => $total,
                    'total_base'   => $totalBase,
                    'motivo'       => $validated['motivo'] ?? null,
                    'estado'       => 'borrador',
                    'fecha'        => $validated['fecha'],
                    'referencia'   => $validated['referencia'] ?? null,
                    'notas'        => $validated['notas'] ?? null,
                ]);

                foreach ($itemsData as $row) {
                    $devolucion->items()->create($row);
                }

                // Aplicar inmediatamente
                $this->aplicarDevolucion($devolucion, $almacenId);

                return response()->json(
                    $devolucion->fresh()->load(['proveedor', 'moneda', 'items.product']),
                    201
                );
            });
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Error al registrar la devolución: ' . $e->getMessage()], 500);
        }
    }

    /** Lógica interna de aplicación de devolución de compra */
    private function aplicarDevolucion(DevolucionCompra $devolucion, ?int $almacenId): void
    {
        foreach ($devolucion->items as $item) {
            $cantidad = (int) $item->cantidad_devuelta;
            $product  = Product::lockForUpdate()->findOrFail($item->product_id);

            // Restar stock global
            $product->decrement('stock', $cantidad);

            // Restar stock por almacén
            if ($almacenId) {
                $stockRow = ProductStock::lockForUpdate()
                    ->where('product_id', $item->product_id)
                    ->where('almacen_id', $almacenId)
                    ->first();

                if ($stockRow) {
                    $nuevaCantidad = max(0, $stockRow->cantidad - $cantidad);
                    $stockRow->update(['cantidad' => $nuevaCantidad]);
                    $stockRow->touch();
                }
            }

            // Movimiento de salida (devolución al proveedor)
            InventoryMovement::create([
                'product_id'  => $item->product_id,
                'almacen_id'  => $almacenId,
                'user_id'     => auth()->id(),
                'type'        => 'out',
                'quantity'    => -$cantidad,
                'reference'   => 'DEV-COMPRA-' . $devolucion->id,
                'notes'       => 'Devolución de compra al proveedor. Motivo: ' . ($devolucion->motivo ?? 'N/E'),
            ]);
        }

        // Ajustar CxP si existe (reducir saldo pendiente)
        $cxp = CuentaPorPagar::where('compra_id', $devolucion->compra_id)
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->first();

        if ($cxp) {
            $nuevoSaldo = max(0, $cxp->saldo - $devolucion->total);
            $nuevoEstado = $nuevoSaldo <= 0 ? 'pagada' : ($nuevoSaldo < $cxp->monto_total ? 'parcial' : 'pendiente');
            $cxp->update(['saldo' => $nuevoSaldo, 'estado' => $nuevoEstado]);
        }

        $devolucion->update(['estado' => 'aplicada']);
    }
}
