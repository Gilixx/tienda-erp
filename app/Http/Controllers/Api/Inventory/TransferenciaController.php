<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Api\Inventory\Concerns\AuthorizesAlmacen;
use App\Http\Controllers\Controller;
use App\Models\Category;
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

    /**
     * GET /api/inventory/transferencias/{id}/preview-categorias
     * Por cada producto: su categoría en el origen, la categoría del destino con
     * el mismo nombre (si existe) y su categoría actual en el destino.
     * Sirve para armar el diálogo de categorías al recibir.
     */
    public function previewCategorias(string $id): JsonResponse
    {
        $t = TransferenciaAlmacen::with('items.product:id,name,sku')->findOrFail($id);
        $this->authorizeAlmacen($t->almacen_origen_id);
        $this->authorizeAlmacen($t->almacen_destino_id);

        $items = $t->items->map(function ($item) use ($t) {
            $catOrigen = ProductStock::with('category:id,name,description,created_by')
                ->where('product_id', $item->product_id)
                ->where('almacen_id', $t->almacen_origen_id)
                ->first()?->category;

            $match = null;
            if ($catOrigen) {
                // Coincidencia por nombre dentro del almacén destino (sin importar creador).
                $match = Category::where('almacen_id', $t->almacen_destino_id)
                    ->where('name', $catOrigen->name)
                    ->first(['id', 'name']);
            }

            $destinoActual = ProductStock::with('category:id,name')
                ->where('product_id', $item->product_id)
                ->where('almacen_id', $t->almacen_destino_id)
                ->first()?->category;

            return [
                'transferencia_item_id' => $item->id,
                'cantidad' => $item->cantidad,
                'product' => [
                    'id' => $item->product_id,
                    'name' => $item->product->name ?? '',
                    'sku' => $item->product->sku ?? '',
                ],
                'categoria_origen' => $catOrigen ? ['id' => $catOrigen->id, 'name' => $catOrigen->name] : null,
                'categoria_destino_match' => $match ? ['id' => $match->id, 'name' => $match->name] : null,
                'categoria_destino_actual' => $destinoActual ? ['id' => $destinoActual->id, 'name' => $destinoActual->name] : null,
            ];
        });

        return response()->json([
            'almacen_destino_id' => $t->almacen_destino_id,
            'items' => $items,
        ]);
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
            // Acción de categoría por ítem: general | asignar | crear.
            'items.*.categoria_accion' => 'nullable|in:general,asignar,crear',
            'items.*.categoria_destino_id' => 'nullable|integer',
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

                // Mapas por ítem: cantidad recibida y acción de categoría.
                $recibidas = [];
                $accionesCat = [];
                foreach ($validated['items'] ?? [] as $row) {
                    $recibidas[$row['transferencia_item_id']] = $row['cantidad_recibida'];
                    if (isset($row['categoria_accion'])) {
                        $accionesCat[$row['transferencia_item_id']] = [
                            'accion' => $row['categoria_accion'],
                            'categoria_destino_id' => $row['categoria_destino_id'] ?? null,
                        ];
                    }
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

                    // Aplicar la categoría en destino solo si se envió una acción explícita.
                    if (isset($accionesCat[$item->id])) {
                        $this->aplicarCategoriaDestino($transferencia, $item, $stockDestino, $accionesCat[$item->id]);
                    }

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

    /**
     * Aplica la categoría del producto en el almacén destino según la acción elegida:
     *  - general: sin categoría (NULL).
     *  - asignar: usa una categoría existente del destino (validada).
     *  - crear:   crea (o reutiliza) en el destino la categoría que trae del origen.
     */
    private function aplicarCategoriaDestino(TransferenciaAlmacen $t, $item, ProductStock $stockDestino, array $accion): void
    {
        $catDestinoId = null;

        if ($accion['accion'] === 'asignar' && $accion['categoria_destino_id']) {
            $cat = Category::where('id', $accion['categoria_destino_id'])
                ->where('almacen_id', $t->almacen_destino_id)
                ->first();
            if (! $cat) {
                return; // categoría inválida: no tocar lo existente
            }
            $catDestinoId = $cat->id;
        } elseif ($accion['accion'] === 'crear') {
            $catOrigen = ProductStock::with('category')
                ->where('product_id', $item->product_id)
                ->where('almacen_id', $t->almacen_origen_id)
                ->first()?->category;
            if (! $catOrigen) {
                return;
            }
            // Identidad de categoría por (almacén, nombre); reutiliza si ya existe.
            $nueva = Category::firstOrCreate(
                [
                    'almacen_id' => $t->almacen_destino_id,
                    'name' => $catOrigen->name,
                ],
                [
                    'description' => $catOrigen->description,
                    'created_by' => $catOrigen->created_by,
                ]
            );
            $catDestinoId = $nueva->id;
        }

        // 'general' => queda en NULL.
        $stockDestino->category_id = $catDestinoId;
        $stockDestino->save();
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
