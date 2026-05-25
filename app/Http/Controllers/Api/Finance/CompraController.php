<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\CategoriaFinanza;
use App\Models\Finance\Compra;
use App\Models\Finance\CompraItem;
use App\Models\Finance\Cuenta;
use App\Models\Finance\CuentaPorPagar;
use App\Models\Finance\Impuesto;
use App\Models\Finance\Proveedor;
use App\Models\Finance\Transaccion;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
    public function index(Request $request)
    {
        $query = Compra::with(['proveedor:id,nombre', 'moneda:id,codigo,simbolo', 'user:id,name'])
            ->latest('fecha')
            ->latest('id');

        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }
        if ($request->filled('proveedor_id')) {
            $query->where('proveedor_id', $request->integer('proveedor_id'));
        }
        if ($request->filled('desde')) {
            $query->where('fecha', '>=', $request->input('desde'));
        }
        if ($request->filled('hasta')) {
            $query->where('fecha', '<=', $request->input('hasta'));
        }

        $perPage = min(100, max(10, $request->integer('per_page', 25)));
        return response()->json($query->paginate($perPage));
    }

    public function show(string $id)
    {
        $compra = Compra::with([
            'proveedor', 'moneda', 'user:id,name',
            'items.product:id,name,sku', 'items.impuesto',
        ])->findOrFail($id);

        return response()->json($compra);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'proveedor_id'      => 'required|exists:proveedores,id',
            'moneda_id'         => 'required|exists:monedas,id',
            'tipo_cambio'       => 'nullable|numeric|min:0.00000001',
            'forma_pago'        => 'required|in:contado,credito',
            'fecha'             => 'required|date',
            'fecha_vencimiento' => 'nullable|date|after_or_equal:fecha',
            'referencia'        => 'nullable|string|max:100',
            'notas'             => 'nullable|string|max:1000',
            'items'             => 'required|array|min:1',
            'items.*.product_id'  => 'required|exists:products,id',
            'items.*.impuesto_id' => 'nullable|exists:impuestos,id',
            'items.*.cantidad'    => 'required|numeric|min:0.0001',
            'items.*.costo_unit'  => 'required|numeric|min:0',
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                $subtotal = 0;
                $impuestos = 0;
                $itemsData = [];

                foreach ($validated['items'] as $row) {
                    $sub = round($row['cantidad'] * $row['costo_unit'], 4);
                    $impMonto = 0;

                    if (! empty($row['impuesto_id'])) {
                        $imp = Impuesto::find($row['impuesto_id']);
                        if ($imp && $imp->aplicacion === 'traslado') {
                            $impMonto = round($sub * ((float) $imp->tasa / 100), 4);
                        }
                    }

                    $subtotal  += $sub;
                    $impuestos += $impMonto;

                    $itemsData[] = [
                        'product_id'     => $row['product_id'],
                        'impuesto_id'    => $row['impuesto_id'] ?? null,
                        'cantidad'       => $row['cantidad'],
                        'costo_unit'     => $row['costo_unit'],
                        'subtotal'       => $sub,
                        'impuesto_monto' => $impMonto,
                        'total'          => $sub + $impMonto,
                    ];
                }

                $total = round($subtotal + $impuestos, 4);

                $compra = Compra::create([
                    'proveedor_id'      => $validated['proveedor_id'],
                    'user_id'           => auth()->id(),
                    'moneda_id'         => $validated['moneda_id'],
                    'tipo_cambio'       => $validated['tipo_cambio'] ?? 1,
                    'subtotal'          => $subtotal,
                    'impuestos'         => $impuestos,
                    'total'             => $total,
                    'estado'            => 'borrador',
                    'forma_pago'        => $validated['forma_pago'],
                    'fecha'             => $validated['fecha'],
                    'fecha_vencimiento' => $validated['fecha_vencimiento'] ?? null,
                    'referencia'        => $validated['referencia'] ?? null,
                    'notas'             => $validated['notas'] ?? null,
                ]);

                foreach ($itemsData as $row) {
                    $compra->items()->create($row);
                }

                return response()->json($compra->load(['proveedor', 'moneda', 'items.product']), 201);
            });
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Error al registrar la compra: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Recibe la compra: entra el stock, recalcula costo promedio del producto,
     * crea la transacción financiera (egreso) y si es a crédito, crea la CxP.
     */
    public function recibir(Request $request, string $id, CurrencyService $fx)
    {
        $validated = $request->validate([
            'cuenta_id' => 'nullable|exists:cuentas,id',
        ]);

        try {
            return DB::transaction(function () use ($id, $validated, $fx) {
                $compra = Compra::with('items.product')->lockForUpdate()->findOrFail($id);

                if ($compra->estado !== 'borrador') {
                    return response()->json(['error' => 'Solo se pueden recibir compras en estado borrador.'], 422);
                }

                // Si es contado, la cuenta es obligatoria y debe coincidir en moneda (defensa)
                if ($compra->forma_pago === 'contado' && empty($validated['cuenta_id'])) {
                    return response()->json(['error' => 'Se requiere cuenta_id para compras de contado.'], 422);
                }

                foreach ($compra->items as $item) {
                    $product = Product::lockForUpdate()->findOrFail($item->product_id);

                    $stockActual = $product->stock;
                    $costoActual = (float) $product->cost;
                    $entrada     = (float) $item->cantidad;
                    $costoEntrada = (float) $item->costo_unit;

                    $nuevoCosto = ($stockActual + $entrada) > 0
                        ? (($stockActual * $costoActual) + ($entrada * $costoEntrada)) / ($stockActual + $entrada)
                        : $costoEntrada;

                    $product->update(['cost' => round($nuevoCosto, 4)]);
                    $product->increment('stock', (int) $entrada);

                    InventoryMovement::create([
                        'product_id' => $product->id,
                        'user_id'    => auth()->id(),
                        'type'       => 'in',
                        'quantity'   => (int) $entrada,
                        'reference'  => 'COMPRA-' . $compra->id,
                        'notes'      => 'Recepción de compra a ' . ($compra->proveedor->nombre ?? '—'),
                    ]);
                }

                $compra->update(['estado' => 'recibida']);

                $categoria = CategoriaFinanza::where('tipo', 'egreso')
                    ->where('nombre', 'like', '%Compras de inventario%')
                    ->first();

                $tcBase = $fx->rate($compra->moneda_id, $fx->base()->id, Carbon::parse($compra->fecha));
                $totalBase = round($compra->total * $tcBase, 4);

                if ($compra->forma_pago === 'contado') {
                    if (empty($validated['cuenta_id'])) {
                        return response()->json(['error' => 'Se requiere cuenta_id para pagos de contado.'], 422);
                    }
                    $cuenta = Cuenta::lockForUpdate()->findOrFail($validated['cuenta_id']);

                    Transaccion::create([
                        'cuenta_id'    => $cuenta->id,
                        'categoria_id' => $categoria?->id,
                        'user_id'      => auth()->id(),
                        'tipo'         => 'egreso',
                        'moneda_id'    => $compra->moneda_id,
                        'tipo_cambio'  => $tcBase,
                        'subtotal'     => $compra->subtotal,
                        'impuestos'    => $compra->impuestos,
                        'total'        => $compra->total,
                        'total_base'   => $totalBase,
                        'fecha'        => $compra->fecha,
                        'descripcion'  => 'Compra #' . $compra->id . ' — ' . ($compra->proveedor->nombre ?? ''),
                        'compra_id'    => $compra->id,
                        'estado'       => 'conciliada',
                    ]);

                    $cuenta->decrement('saldo_actual', $compra->total);
                    $compra->update(['estado' => 'pagada']);
                } else {
                    CuentaPorPagar::create([
                        'compra_id'         => $compra->id,
                        'proveedor_id'      => $compra->proveedor_id,
                        'moneda_id'         => $compra->moneda_id,
                        'tipo_cambio'       => $compra->tipo_cambio,
                        'monto_total'       => $compra->total,
                        'monto_pagado'      => 0,
                        'saldo'             => $compra->total,
                        'fecha_emision'     => $compra->fecha,
                        'fecha_vencimiento' => $compra->fecha_vencimiento ?? Carbon::parse($compra->fecha)->addDays($compra->proveedor->dias_credito ?? 30),
                        'estado'            => 'pendiente',
                    ]);
                }

                return response()->json($compra->fresh()->load(['proveedor', 'moneda', 'items.product']));
            });
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Error al recibir la compra: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        $compra = Compra::findOrFail($id);

        if ($compra->estado !== 'borrador') {
            return response()->json(['error' => 'Solo se pueden eliminar compras en estado borrador.'], 422);
        }

        $compra->delete();
        return response()->json(['message' => 'Compra eliminada.']);
    }
}
