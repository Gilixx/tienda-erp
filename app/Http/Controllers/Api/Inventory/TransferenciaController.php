<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Api\Inventory\Concerns\AuthorizesAlmacen;
use App\Http\Controllers\Controller;
use App\Models\Inventory\ProductStock;
use App\Models\Inventory\TransferenciaAlmacen;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\Inventory\VerificarStockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransferenciaController extends Controller
{
    use AuthorizesAlmacen;

    /** GET /api/inventory/transferencias */
    public function index(Request $request): JsonResponse
    {
        $accesibles = $this->accesibleAlmacenIds();

        $query = TransferenciaAlmacen::with([
            'almacenOrigen:id,nombre,codigo',
            'almacenDestino:id,nombre,codigo',
            'user:id,name',
        ])
            // Solo transferencias cuyos dos almacenes sean accesibles (mismo criterio que show/enviar/recibir/destroy)
            ->whereIn('almacen_origen_id', $accesibles)
            ->whereIn('almacen_destino_id', $accesibles)
            ->latest('fecha')->latest('id');

        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }
        if ($request->filled('almacen_id')) {
            $almId = $request->integer('almacen_id');
            $this->authorizeAlmacen($almId);
            $query->where(fn ($q) => $q->where('almacen_origen_id', $almId)
                ->orWhere('almacen_destino_id', $almId)
            );
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

    /** GET /api/inventory/transferencias/{id} */
    public function show(string $id): JsonResponse
    {
        $transferencia = TransferenciaAlmacen::with([
            'almacenOrigen', 'almacenDestino',
            'user:id,name', 'recibidoPor:id,name',
            'items.product:id,name,sku',
        ])->findOrFail($id);

        $this->authorizeAlmacen($transferencia->almacen_origen_id);
        $this->authorizeAlmacen($transferencia->almacen_destino_id);

        return response()->json($transferencia);
    }

    /** POST /api/inventory/transferencias — crea en estado borrador */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'almacen_origen_id' => 'required|exists:almacenes,id',
            'almacen_destino_id' => 'required|exists:almacenes,id|different:almacen_origen_id',
            'fecha' => 'required|date',
            'referencia' => 'nullable|string|max:100',
            'notas' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.cantidad' => 'required|integer|min:1',
        ]);

        $this->authorizeAlmacen($validated['almacen_origen_id']);
        $this->authorizeAlmacen($validated['almacen_destino_id']);

        try {
            return DB::transaction(function () use ($validated) {
                $transferencia = TransferenciaAlmacen::create([
                    'almacen_origen_id' => $validated['almacen_origen_id'],
                    'almacen_destino_id' => $validated['almacen_destino_id'],
                    'user_id' => auth()->id(),
                    'estado' => 'borrador',
                    'fecha' => $validated['fecha'],
                    'referencia' => $validated['referencia'] ?? null,
                    'notas' => $validated['notas'] ?? null,
                ]);

                foreach ($validated['items'] as $row) {
                    $transferencia->items()->create([
                        'product_id' => $row['product_id'],
                        'cantidad' => $row['cantidad'],
                    ]);
                }

                return response()->json(
                    $transferencia->load(['almacenOrigen', 'almacenDestino', 'items.product']),
                    201
                );
            });
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['error' => 'Error al crear la transferencia: '.$e->getMessage()], 500);
        }
    }

    /**
     * POST /api/inventory/transferencias/{id}/enviar
     * Descuenta stock del origen y pasa a "en_transito".
     */
    public function enviar(string $id): JsonResponse
    {
        try {
            return DB::transaction(function () use ($id) {
                $transferencia = TransferenciaAlmacen::with('items.product')
                    ->lockForUpdate()
                    ->findOrFail($id);

                $this->authorizeAlmacen($transferencia->almacen_origen_id);
                $this->authorizeAlmacen($transferencia->almacen_destino_id);

                if ($transferencia->estado !== 'borrador') {
                    return response()->json(['error' => 'Solo se pueden enviar transferencias en estado borrador.'], 422);
                }

                foreach ($transferencia->items as $item) {
                    $stock = ProductStock::lockForUpdate()
                        ->where('product_id', $item->product_id)
                        ->where('almacen_id', $transferencia->almacen_origen_id)
                        ->first();

                    $cantidadDisponible = $stock?->cantidad ?? 0;

                    if ($cantidadDisponible < $item->cantidad) {
                        return response()->json([
                            'error' => "Stock insuficiente para '{$item->product->name}'. Disponible: {$cantidadDisponible}, requerido: {$item->cantidad}.",
                        ], 422);
                    }

                    // Descontar del origen
                    $stock->decrement('cantidad', $item->cantidad);
                    $stock->touch();

                    // Mantener products.stock en sincronía (total global)
                    Product::where('id', $item->product_id)
                        ->decrement('stock', $item->cantidad);

                    // Movimiento de salida en origen
                    InventoryMovement::create([
                        'product_id' => $item->product_id,
                        'almacen_id' => $transferencia->almacen_origen_id,
                        'user_id' => auth()->id(),
                        'type' => 'out',
                        'quantity' => -$item->cantidad,
                        'reference' => 'TRANSF-'.$transferencia->id,
                        'notes' => 'Salida por transferencia a '.($transferencia->almacenDestino->nombre ?? ''),
                    ]);
                }

                $transferencia->update(['estado' => 'en_transito']);

                // Refrescar alertas del origen (stock descontado).
                app(VerificarStockService::class)->verificar(
                    $transferencia->almacen_origen_id,
                    $transferencia->items->pluck('product_id')->all()
                );

                return response()->json($transferencia->fresh()->load(['almacenOrigen', 'almacenDestino', 'items.product']));
            });
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['error' => 'Error al enviar la transferencia: '.$e->getMessage()], 500);
        }
    }

    /**
     * POST /api/inventory/transferencias/{id}/recibir
     * Acredita stock en el destino y cierra la transferencia.
     */
    public function recibir(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'nullable|array',
            'items.*.transferencia_item_id' => 'required|integer',
            'items.*.cantidad_recibida' => 'required|integer|min:0',
        ]);

        try {
            return DB::transaction(function () use ($id, $validated) {
                $transferencia = TransferenciaAlmacen::with('items')
                    ->lockForUpdate()
                    ->findOrFail($id);

                $this->authorizeAlmacen($transferencia->almacen_origen_id);
                $this->authorizeAlmacen($transferencia->almacen_destino_id);

                if ($transferencia->estado !== 'en_transito') {
                    return response()->json(['error' => 'Solo se pueden recibir transferencias en tránsito.'], 422);
                }

                // Mapa de cantidades recibidas (si el usuario las especificó)
                $recibidas = [];
                foreach ($validated['items'] ?? [] as $row) {
                    $recibidas[$row['transferencia_item_id']] = $row['cantidad_recibida'];
                }

                foreach ($transferencia->items as $item) {
                    $cantidadRecibida = $recibidas[$item->id] ?? $item->cantidad;
                    $item->update(['cantidad_recibida' => $cantidadRecibida]);

                    if ($cantidadRecibida <= 0) {
                        continue;
                    }

                    // Acreditar en destino
                    $stockDestino = ProductStock::firstOrCreate(
                        ['product_id' => $item->product_id, 'almacen_id' => $transferencia->almacen_destino_id],
                        ['cantidad' => 0]
                    );
                    $stockDestino->increment('cantidad', $cantidadRecibida);
                    $stockDestino->touch();

                    // Mantener products.stock en sincronía (total global)
                    Product::where('id', $item->product_id)
                        ->increment('stock', $cantidadRecibida);

                    // Movimiento de entrada en destino
                    InventoryMovement::create([
                        'product_id' => $item->product_id,
                        'almacen_id' => $transferencia->almacen_destino_id,
                        'user_id' => auth()->id(),
                        'type' => 'in',
                        'quantity' => $cantidadRecibida,
                        'reference' => 'TRANSF-'.$transferencia->id,
                        'notes' => 'Entrada por transferencia desde '.($transferencia->almacenOrigen->nombre ?? ''),
                    ]);
                }

                $transferencia->update([
                    'estado' => 'recibida',
                    'fecha_recepcion' => now(),
                    'recibido_por' => auth()->id(),
                ]);

                // Refrescar alertas del destino (stock acreditado).
                app(VerificarStockService::class)->verificar(
                    $transferencia->almacen_destino_id,
                    $transferencia->items->pluck('product_id')->all()
                );

                return response()->json($transferencia->fresh()->load(['almacenOrigen', 'almacenDestino', 'items.product']));
            });
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['error' => 'Error al recibir la transferencia: '.$e->getMessage()], 500);
        }
    }

    /** DELETE /api/inventory/transferencias/{id} — solo borradores */
    public function destroy(string $id): JsonResponse
    {
        $transferencia = TransferenciaAlmacen::findOrFail($id);

        $this->authorizeAlmacen($transferencia->almacen_origen_id);
        $this->authorizeAlmacen($transferencia->almacen_destino_id);

        if ($transferencia->estado !== 'borrador') {
            return response()->json(['error' => 'Solo se pueden cancelar transferencias en estado borrador.'], 422);
        }

        $transferencia->delete();

        return response()->json(['message' => 'Transferencia eliminada.']);
    }
}
