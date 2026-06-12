<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Cuenta;
use App\Models\Finance\Impuesto;
use App\Models\Finance\Transaccion;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TransaccionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaccion::with(['cuenta:id,nombre,moneda_id', 'categoria:id,nombre,tipo,color', 'moneda:id,codigo,simbolo', 'user:id,name'])
            ->latest('fecha')
            ->latest('id');

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->input('tipo'));
        }
        if ($request->filled('cuenta_id')) {
            $query->where(function ($q) use ($request) {
                $cuentaId = $request->integer('cuenta_id');
                $q->where('cuenta_id', $cuentaId)->orWhere('cuenta_destino_id', $cuentaId);
            });
        }
        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->integer('categoria_id'));
        }
        if ($request->filled('desde')) {
            $query->where('fecha', '>=', $request->input('desde'));
        }
        if ($request->filled('hasta')) {
            $query->where('fecha', '<=', $request->input('hasta'));
        }

        $perPage = min(100, max(10, $request->integer('per_page', 50)));
        return response()->json($query->paginate($perPage));
    }

    public function show(string $id)
    {
        $tx = Transaccion::with([
            'cuenta.moneda', 'cuentaDestino.moneda', 'categoria',
            'moneda', 'user:id,name', 'impuestosDetalle',
        ])->findOrFail($id);

        return response()->json($tx);
    }

    public function store(Request $request, CurrencyService $fx)
    {
        $validated = $request->validate([
            'cuenta_id'    => 'required|exists:cuentas,id',
            'categoria_id' => 'nullable|exists:categorias_finanzas,id',
            'tipo'         => 'required|in:ingreso,egreso',
            'moneda_id'    => 'required|exists:monedas,id',
            'tipo_cambio'  => 'nullable|numeric|min:0.00000001',
            'subtotal'     => 'required|numeric|min:0',
            'fecha'        => 'required|date',
            'descripcion'  => 'nullable|string|max:255',
            'referencia'   => 'nullable|string|max:100',
            'notas'        => 'nullable|string|max:1000',
            'impuestos'    => 'nullable|array',
            'impuestos.*.impuesto_id' => 'required_with:impuestos|exists:impuestos,id',
        ]);

        try {
            return DB::transaction(function () use ($validated, $fx) {
                $cuenta = Cuenta::lockForUpdate()->findOrFail($validated['cuenta_id']);

                $subtotal     = (float) $validated['subtotal'];
                $impuestosTotal = 0;
                $retencionesTotal = 0;
                $detalleImpuestos = [];

                foreach ($validated['impuestos'] ?? [] as $row) {
                    $imp = Impuesto::findOrFail($row['impuesto_id']);
                    $monto = round($subtotal * ((float) $imp->tasa / 100), 4);

                    if ($imp->aplicacion === 'retencion') {
                        $retencionesTotal += $monto;
                    } else {
                        $impuestosTotal += $monto;
                    }

                    $detalleImpuestos[$imp->id] = [
                        'base'  => $subtotal,
                        'tasa'  => $imp->tasa,
                        'monto' => $monto,
                    ];
                }

                $total = round($subtotal + $impuestosTotal - $retencionesTotal, 4);

                $fecha = Carbon::parse($validated['fecha']);
                $tc    = isset($validated['tipo_cambio'])
                    ? (float) $validated['tipo_cambio']
                    : $fx->rate($validated['moneda_id'], $fx->base()->id, $fecha);

                $totalBase = round($total * $tc, 4);

                $tx = Transaccion::create([
                    'cuenta_id'    => $cuenta->id,
                    'categoria_id' => $validated['categoria_id'] ?? null,
                    'user_id'      => auth()->id(),
                    'tipo'         => $validated['tipo'],
                    'moneda_id'    => $validated['moneda_id'],
                    'tipo_cambio'  => $tc,
                    'subtotal'     => $subtotal,
                    'impuestos'    => $impuestosTotal,
                    'retenciones'  => $retencionesTotal,
                    'total'        => $total,
                    'total_base'   => $totalBase,
                    'fecha'        => $validated['fecha'],
                    'descripcion'  => $validated['descripcion'] ?? null,
                    'referencia'   => $validated['referencia'] ?? null,
                    'notas'        => $validated['notas'] ?? null,
                    'estado'       => 'conciliada',
                ]);

                if (! empty($detalleImpuestos)) {
                    $tx->impuestosDetalle()->attach($detalleImpuestos);
                }

                $delta = $validated['tipo'] === 'ingreso' ? $total : -$total;
                $cuenta->increment('saldo_actual', $delta);

                return response()->json($tx->load(['cuenta.moneda', 'categoria', 'impuestosDetalle']), 201);
            });
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Error al registrar la transacción: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        if (! auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Solo administradores pueden eliminar transacciones.'], 403);
        }

        try {
            return DB::transaction(function () use ($id) {
                $tx = Transaccion::lockForUpdate()->findOrFail($id);

                if ($tx->venta_id || $tx->compra_id) {
                    return response()->json(['error' => 'No se puede eliminar: la transacción está vinculada a una venta o compra.'], 422);
                }

                if ($tx->tipo === 'transferencia') {
                    Cuenta::where('id', $tx->cuenta_id)->increment('saldo_actual', $tx->total);
                    Cuenta::where('id', $tx->cuenta_destino_id)->decrement('saldo_actual', $tx->total);
                } elseif ($tx->tipo === 'ingreso') {
                    Cuenta::where('id', $tx->cuenta_id)->decrement('saldo_actual', $tx->total);
                } else {
                    Cuenta::where('id', $tx->cuenta_id)->increment('saldo_actual', $tx->total);
                }

                $tx->delete();
                return response()->json(['message' => 'Transacción eliminada y saldos revertidos.']);
            });
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Error al eliminar.'], 500);
        }
    }
}
