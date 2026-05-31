<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Almacen;
use App\Models\Inventory\AlmacenUbicacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlmacenController extends Controller
{
    /** GET /api/inventory/almacenes */
    public function index(Request $request): JsonResponse
    {
        $query = Almacen::withCount(['stocks as total_productos' => function ($q) {
                $q->where('cantidad', '>', 0);
            }])
            ->orderBy('es_principal', 'desc')
            ->orderBy('nombre');

        if ($request->boolean('activos', true)) {
            $query->where('activo', true);
        }

        return response()->json($query->get());
    }

    /** POST /api/inventory/almacenes */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre'      => 'required|string|max:100',
            'codigo'      => 'required|string|max:20|unique:almacenes,codigo',
            'descripcion' => 'nullable|string|max:500',
            'direccion'   => 'nullable|string|max:500',
            'activo'      => 'boolean',
        ]);

        $validated['es_principal'] = false; // solo uno puede ser principal (el migrado)

        $almacen = Almacen::create($validated);

        return response()->json($almacen, 201);
    }

    /** GET /api/inventory/almacenes/{id} */
    public function show(string $id): JsonResponse
    {
        $almacen = Almacen::with([
            'ubicaciones' => fn($q) => $q->where('activo', true),
        ])->findOrFail($id);

        // Stock total del almacén
        $almacen->total_stock = $almacen->stocks()->sum('cantidad');
        $almacen->total_skus  = $almacen->stocks()->where('cantidad', '>', 0)->count();

        return response()->json($almacen);
    }

    /** PUT /api/inventory/almacenes/{id} */
    public function update(Request $request, string $id): JsonResponse
    {
        $almacen = Almacen::findOrFail($id);

        $validated = $request->validate([
            'nombre'      => 'sometimes|string|max:100',
            'codigo'      => 'sometimes|string|max:20|unique:almacenes,codigo,' . $almacen->id,
            'descripcion' => 'nullable|string|max:500',
            'direccion'   => 'nullable|string|max:500',
            'activo'      => 'boolean',
        ]);

        $almacen->update($validated);

        return response()->json($almacen);
    }

    /** DELETE /api/inventory/almacenes/{id} */
    public function destroy(string $id): JsonResponse
    {
        $almacen = Almacen::findOrFail($id);

        if ($almacen->es_principal) {
            return response()->json(['error' => 'No se puede eliminar el almacén principal.'], 422);
        }

        $tieneStock = $almacen->stocks()->where('cantidad', '>', 0)->exists();
        if ($tieneStock) {
            return response()->json(['error' => 'El almacén tiene stock activo. Transfiere el inventario antes de eliminarlo.'], 422);
        }

        $almacen->delete();

        return response()->json(['message' => 'Almacén eliminado correctamente.']);
    }

    // ── Ubicaciones ──────────────────────────────────────────

    /** GET /api/inventory/almacenes/{id}/ubicaciones */
    public function ubicaciones(string $id): JsonResponse
    {
        $almacen = Almacen::findOrFail($id);

        return response()->json(
            $almacen->ubicaciones()->where('activo', true)->orderBy('codigo')->get()
        );
    }

    /** POST /api/inventory/almacenes/{id}/ubicaciones */
    public function storeUbicacion(Request $request, string $id): JsonResponse
    {
        $almacen = Almacen::findOrFail($id);

        $validated = $request->validate([
            'codigo'      => 'required|string|max:30',
            'nombre'      => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:300',
        ]);

        $existe = AlmacenUbicacion::where('almacen_id', $almacen->id)
            ->where('codigo', $validated['codigo'])
            ->exists();

        if ($existe) {
            return response()->json(['error' => 'Ya existe una ubicación con ese código en este almacén.'], 422);
        }

        $ubicacion = $almacen->ubicaciones()->create($validated);

        return response()->json($ubicacion, 201);
    }

    /** DELETE /api/inventory/almacenes/{almacenId}/ubicaciones/{ubicacionId} */
    public function destroyUbicacion(string $almacenId, string $ubicacionId): JsonResponse
    {
        $ubicacion = AlmacenUbicacion::where('almacen_id', $almacenId)
            ->findOrFail($ubicacionId);

        $ubicacion->update(['activo' => false]);

        return response()->json(['message' => 'Ubicación desactivada.']);
    }
}
