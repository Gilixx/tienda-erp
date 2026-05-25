<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Cuenta;
use App\Models\Finance\Transaccion;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CuentaController extends Controller
{
    public function index()
    {
        return response()->json(
            Cuenta::with('moneda')->orderBy('nombre')->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'         => 'required|string|max:100',
            'tipo'           => 'required|in:caja,banco,tarjeta_credito,tarjeta_debito,credito,otro',
            'moneda_id'      => 'required|exists:monedas,id',
            'saldo_inicial'  => 'required|numeric',
            'banco'          => 'nullable|string|max:100',
            'numero_cuenta'  => 'nullable|string|max:50',
            'limite_credito' => 'nullable|numeric|min:0',
            'notas'          => 'nullable|string|max:1000',
            'activa'         => 'boolean',
        ]);

        $validated['saldo_actual'] = $validated['saldo_inicial'];
        $cuenta = Cuenta::create($validated);

        return response()->json($cuenta->load('moneda'), 201);
    }

    public function show(string $id)
    {
        $cuenta = Cuenta::with('moneda')->findOrFail($id);

        $cuenta->transacciones_recientes = Transaccion::with(['categoria', 'user:id,name'])
            ->where('cuenta_id', $cuenta->id)
            ->orWhere('cuenta_destino_id', $cuenta->id)
            ->latest('fecha')
            ->limit(20)
            ->get();

        return response()->json($cuenta);
    }

    public function update(Request $request, string $id)
    {
        $cuenta = Cuenta::findOrFail($id);

        $validated = $request->validate([
            'nombre'         => 'required|string|max:100',
            'tipo'           => 'required|in:caja,banco,tarjeta_credito,tarjeta_debito,credito,otro',
            'banco'          => 'nullable|string|max:100',
            'numero_cuenta'  => 'nullable|string|max:50',
            'limite_credito' => 'nullable|numeric|min:0',
            'notas'          => 'nullable|string|max:1000',
            'activa'         => 'boolean',
        ]);

        $cuenta->update($validated);
        return response()->json($cuenta->load('moneda'));
    }

    public function destroy(string $id)
    {
        $cuenta = Cuenta::findOrFail($id);

        if ($cuenta->transacciones()->exists()) {
            return response()->json(['error' => 'No se puede eliminar: la cuenta tiene transacciones.'], 422);
        }

        $cuenta->delete();
        return response()->json(['message' => 'Cuenta eliminada.']);
    }

    /**
     * Transferencia entre dos cuentas. Si difieren en moneda, convierte usando el TC vigente.
     */
    public function transferir(Request $request, CurrencyService $fx)
    {
        $validated = $request->validate([
            'cuenta_origen_id'  => 'required|exists:cuentas,id',
            'cuenta_destino_id' => 'required|exists:cuentas,id|different:cuenta_origen_id',
            'monto'             => 'required|numeric|min:0.01',
            'fecha'             => 'required|date',
            'descripcion'       => 'nullable|string|max:255',
            'referencia'        => 'nullable|string|max:100',
        ]);

        try {
            return DB::transaction(function () use ($validated, $fx) {
                $origen  = Cuenta::lockForUpdate()->findOrFail($validated['cuenta_origen_id']);
                $destino = Cuenta::lockForUpdate()->findOrFail($validated['cuenta_destino_id']);

                $monto = (float) $validated['monto'];

                if ($origen->saldo_actual < $monto) {
                    return response()->json(['error' => 'Saldo insuficiente en la cuenta origen.'], 422);
                }

                $montoDestino = $fx->convert($monto, $origen->moneda_id, $destino->moneda_id);
                $totalBase    = $fx->toBase($monto, $origen->moneda_id);

                $tx = Transaccion::create([
                    'cuenta_id'         => $origen->id,
                    'cuenta_destino_id' => $destino->id,
                    'user_id'           => auth()->id(),
                    'tipo'              => 'transferencia',
                    'moneda_id'         => $origen->moneda_id,
                    'tipo_cambio'       => $origen->moneda_id === $destino->moneda_id ? 1 : ($montoDestino / $monto),
                    'subtotal'          => $monto,
                    'total'             => $monto,
                    'total_base'        => $totalBase,
                    'fecha'             => $validated['fecha'],
                    'descripcion'       => $validated['descripcion'] ?? "Transferencia {$origen->nombre} → {$destino->nombre}",
                    'referencia'        => $validated['referencia'] ?? null,
                    'estado'            => 'conciliada',
                ]);

                $origen->decrement('saldo_actual', $monto);
                $destino->increment('saldo_actual', $montoDestino);

                return response()->json($tx->load(['cuenta.moneda', 'cuentaDestino.moneda']), 201);
            });
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Error al procesar la transferencia.'], 500);
        }
    }
}
