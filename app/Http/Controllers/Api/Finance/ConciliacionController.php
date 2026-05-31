<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\ConciliacionBancaria;
use App\Models\Finance\ConciliacionItem;
use App\Models\Finance\Cuenta;
use App\Models\Finance\Transaccion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConciliacionController extends Controller
{
    /** GET /api/finance/conciliaciones */
    public function index(Request $request): JsonResponse
    {
        $query = ConciliacionBancaria::with(['cuenta:id,nombre,tipo', 'user:id,name'])
            ->latest();

        if ($request->filled('cuenta_id')) {
            $query->where('cuenta_id', $request->integer('cuenta_id'));
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        $perPage = min(50, max(10, $request->integer('per_page', 15)));

        return response()->json($query->paginate($perPage));
    }

    /** GET /api/finance/conciliaciones/{id} */
    public function show(string $id): JsonResponse
    {
        $conciliacion = ConciliacionBancaria::with([
            'cuenta', 'user:id,name', 'cerradaPor:id,name',
            'items.transaccion',
        ])->findOrFail($id);

        // Estadísticas rápidas
        $conciliacion->total_items       = $conciliacion->items->count();
        $conciliacion->items_conciliados = $conciliacion->items->where('conciliado', true)->count();

        return response()->json($conciliacion);
    }

    /**
     * POST /api/finance/conciliaciones
     * Abre una nueva conciliación para una cuenta en un período.
     * Carga automáticamente todas las transacciones del período.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cuenta_id'             => 'required|exists:cuentas,id',
            'fecha_inicio'          => 'required|date',
            'fecha_fin'             => 'required|date|after_or_equal:fecha_inicio',
            'saldo_banco_statement' => 'required|numeric',
            'notas'                 => 'nullable|string|max:1000',
        ]);

        // Verificar que no haya conciliación abierta para el mismo período
        $existente = ConciliacionBancaria::where('cuenta_id', $validated['cuenta_id'])
            ->where('estado', 'abierta')
            ->exists();

        if ($existente) {
            return response()->json([
                'error' => 'Ya existe una conciliación abierta para esta cuenta. Ciérrala antes de crear otra.',
            ], 422);
        }

        try {
            return DB::transaction(function () use ($validated) {
                $cuenta = Cuenta::findOrFail($validated['cuenta_id']);

                $conciliacion = ConciliacionBancaria::create([
                    'cuenta_id'             => $cuenta->id,
                    'user_id'               => auth()->id(),
                    'fecha_inicio'          => $validated['fecha_inicio'],
                    'fecha_fin'             => $validated['fecha_fin'],
                    'saldo_banco_statement' => $validated['saldo_banco_statement'],
                    'saldo_sistema'         => $cuenta->saldo_actual,
                    'diferencia'            => 0,
                    'estado'                => 'abierta',
                    'notas'                 => $validated['notas'] ?? null,
                ]);

                // Cargar transacciones del período para esta cuenta
                $transacciones = Transaccion::where('cuenta_id', $cuenta->id)
                    ->whereBetween('fecha', [$validated['fecha_inicio'], $validated['fecha_fin']])
                    ->whereNotIn('estado', ['cancelada'])
                    ->pluck('id');

                foreach ($transacciones as $txId) {
                    ConciliacionItem::create([
                        'conciliacion_id' => $conciliacion->id,
                        'transaccion_id'  => $txId,
                        'conciliado'      => false,
                    ]);
                }

                return response()->json(
                    $conciliacion->load(['cuenta', 'items.transaccion']),
                    201
                );
            });
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Error al crear la conciliación: ' . $e->getMessage()], 500);
        }
    }

    /**
     * PATCH /api/finance/conciliaciones/{id}/items/{itemId}
     * Marca o desmarca un item como conciliado.
     */
    public function toggleItem(string $id, string $itemId): JsonResponse
    {
        $conciliacion = ConciliacionBancaria::findOrFail($id);

        if ($conciliacion->estado !== 'abierta') {
            return response()->json(['error' => 'No se puede modificar una conciliación cerrada.'], 422);
        }

        $item = ConciliacionItem::where('conciliacion_id', $id)->findOrFail($itemId);

        $item->update([
            'conciliado'        => ! $item->conciliado,
            'fecha_conciliacion' => $item->conciliado ? null : now(),
        ]);

        return response()->json($item->fresh());
    }

    /**
     * POST /api/finance/conciliaciones/{id}/cerrar
     * Cierra la conciliación. Marca transacciones conciliadas con estado 'conciliada'.
     */
    public function cerrar(Request $request, string $id): JsonResponse
    {
        try {
            return DB::transaction(function () use ($id) {
                $conciliacion = ConciliacionBancaria::with('items')->lockForUpdate()->findOrFail($id);

                if ($conciliacion->estado !== 'abierta') {
                    return response()->json(['error' => 'Esta conciliación ya está cerrada.'], 422);
                }

                $cuenta = Cuenta::findOrFail($conciliacion->cuenta_id);

                // Calcular saldo sistema y diferencia
                $saldoSistema = $cuenta->saldo_actual;
                $diferencia   = round((float) $conciliacion->saldo_banco_statement - (float) $saldoSistema, 4);

                // Marcar transacciones conciliadas
                $itemsConciliados = $conciliacion->items->where('conciliado', true)->pluck('transaccion_id');

                if ($itemsConciliados->isNotEmpty()) {
                    Transaccion::whereIn('id', $itemsConciliados)
                        ->where('estado', 'pendiente')
                        ->update(['estado' => 'conciliada']);
                }

                $conciliacion->update([
                    'estado'       => 'cerrada',
                    'saldo_sistema' => $saldoSistema,
                    'diferencia'   => $diferencia,
                    'cerrada_en'   => now(),
                    'cerrada_por'  => auth()->id(),
                ]);

                return response()->json($conciliacion->fresh()->load(['cuenta', 'cerradaPor:id,name']));
            });
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Error al cerrar la conciliación: ' . $e->getMessage()], 500);
        }
    }
}
