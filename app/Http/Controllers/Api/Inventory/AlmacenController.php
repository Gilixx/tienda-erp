<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Api\Inventory\Concerns\AuthorizesAlmacen;
use App\Http\Controllers\Controller;
use App\Models\Inventory\Almacen;
use App\Models\Inventory\AlmacenUbicacion;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AlmacenController extends Controller
{
    use AuthorizesAlmacen;

    /** GET /api/inventory/almacenes */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Almacen::accesiblesPara($user)
            ->withCount(['stocks as total_productos' => function ($q) {
                $q->where('cantidad', '>', 0);
            }])
            ->orderBy('es_principal', 'desc')
            ->orderBy('nombre');

        if ($request->boolean('activos', true)) {
            $query->where('activo', true);
        }

        $almacenes = $query->get()->each(function (Almacen $almacen) use ($user) {
            $almacen->puede_gestionar = $almacen->puedeGestionar($user);
        });

        return response()->json($almacenes);
    }

    /** POST /api/inventory/almacenes */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'codigo' => 'required|string|max:20|unique:almacenes,codigo',
            'descripcion' => 'nullable|string|max:500',
            'direccion' => 'nullable|string|max:500',
            'activo' => 'boolean',
        ]);

        $validated['es_principal'] = false; // solo uno puede ser principal (el migrado)
        $validated['created_by'] = $request->user()->id;

        $almacen = Almacen::create($validated);
        $almacen->puede_gestionar = true;

        return response()->json($almacen, 201);
    }

    /** GET /api/inventory/almacenes/{id} */
    public function show(Request $request, string $id): JsonResponse
    {
        $almacen = $this->authorizeAlmacen($id);

        $almacen->load(['ubicaciones' => fn ($q) => $q->where('activo', true)]);

        // Stock total del almacén
        $almacen->total_stock = $almacen->stocks()->sum('cantidad');
        $almacen->total_skus = $almacen->stocks()->where('cantidad', '>', 0)->count();
        $almacen->puede_gestionar = $almacen->puedeGestionar($request->user());

        return response()->json($almacen);
    }

    /** PUT /api/inventory/almacenes/{id} */
    public function update(Request $request, string $id): JsonResponse
    {
        $almacen = $this->authorizeAlmacen($id);
        $this->authorizeGestion($almacen, $request);

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'codigo' => 'sometimes|string|max:20|unique:almacenes,codigo,'.$almacen->id,
            'descripcion' => 'nullable|string|max:500',
            'direccion' => 'nullable|string|max:500',
            'activo' => 'boolean',
        ]);

        $almacen->update($validated);

        return response()->json($almacen);
    }

    /** DELETE /api/inventory/almacenes/{id} */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $almacen = $this->authorizeAlmacen($id);
        $this->authorizeGestion($almacen, $request);

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

    // ── Permisos de usuarios ─────────────────────────────────

    /** GET /api/inventory/almacenes/{id}/usuarios */
    public function usuarios(Request $request, string $id): JsonResponse
    {
        $almacen = $this->authorizeAlmacen($id);
        $this->authorizeGestion($almacen, $request);

        $almacen->load(['creador:id,name,email', 'usuariosConAcceso:id,name,email']);

        $usuarios = [];

        if ($almacen->creador) {
            $usuarios[] = [
                'id' => $almacen->creador->id,
                'name' => $almacen->creador->name,
                'email' => $almacen->creador->email,
                'es_creador' => true,
            ];
        }

        foreach ($almacen->usuariosConAcceso as $u) {
            $usuarios[] = [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'es_creador' => false,
            ];
        }

        return response()->json($usuarios);
    }

    /** GET /api/inventory/almacenes/{id}/usuarios-disponibles */
    public function usuariosDisponibles(Request $request, string $id): JsonResponse
    {
        $almacen = $this->authorizeAlmacen($id);
        $this->authorizeGestion($almacen, $request);

        $excluidos = $almacen->usuariosConAcceso()->pluck('users.id')->all();
        if ($almacen->created_by) {
            $excluidos[] = $almacen->created_by;
        }

        // Solo usuarios con acceso vigente al módulo (admins o con el servicio inventory sin expirar)
        $usuarios = User::query()
            ->whereNotIn('id', $excluidos)
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->filter(fn (User $u) => $u->hasService('inventory'))
            ->values();

        return response()->json($usuarios);
    }

    /** POST /api/inventory/almacenes/{id}/usuarios */
    public function agregarUsuario(Request $request, string $id): JsonResponse
    {
        $almacen = $this->authorizeAlmacen($id);
        $this->authorizeGestion($almacen, $request);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validated['user_id'] == $almacen->created_by) {
            return response()->json(['error' => 'El dueño ya tiene acceso al almacén.'], 422);
        }

        $target = User::findOrFail($validated['user_id']);
        if (! $target->hasService('inventory')) {
            return response()->json(['error' => 'El usuario no tiene acceso al módulo de inventario.'], 422);
        }

        $almacen->usuariosConAcceso()->syncWithoutDetaching([
            $validated['user_id'] => ['granted_by' => $request->user()->id],
        ]);

        return response()->json(['message' => 'Acceso concedido.'], 201);
    }

    /** DELETE /api/inventory/almacenes/{id}/usuarios/{userId} */
    public function quitarUsuario(Request $request, string $id, string $userId): JsonResponse
    {
        $almacen = $this->authorizeAlmacen($id);
        $this->authorizeGestion($almacen, $request);

        $almacen->usuariosConAcceso()->detach($userId);

        return response()->json(['message' => 'Acceso revocado.']);
    }

    // ── Ubicaciones ──────────────────────────────────────────

    /** GET /api/inventory/almacenes/{id}/ubicaciones */
    public function ubicaciones(string $id): JsonResponse
    {
        $almacen = $this->authorizeAlmacen($id);

        return response()->json(
            $almacen->ubicaciones()->where('activo', true)->orderBy('codigo')->get()
        );
    }

    /** POST /api/inventory/almacenes/{id}/ubicaciones */
    public function storeUbicacion(Request $request, string $id): JsonResponse
    {
        $almacen = $this->authorizeAlmacen($id);
        $this->authorizeGestion($almacen, $request);

        $validated = $request->validate([
            'codigo' => 'required|string|max:30',
            'nombre' => 'required|string|max:100',
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
    public function destroyUbicacion(Request $request, string $almacenId, string $ubicacionId): JsonResponse
    {
        $almacen = $this->authorizeAlmacen($almacenId);
        $this->authorizeGestion($almacen, $request);

        $ubicacion = AlmacenUbicacion::where('almacen_id', $almacenId)
            ->findOrFail($ubicacionId);

        $ubicacion->update(['activo' => false]);

        return response()->json(['message' => 'Ubicación desactivada.']);
    }

    /** Aborta con 403 si el usuario no puede gestionar permisos del almacén. */
    private function authorizeGestion(Almacen $almacen, Request $request): void
    {
        if (! $almacen->puedeGestionar($request->user())) {
            throw new HttpException(403, 'Solo el dueño del almacén puede gestionarlo.');
        }
    }
}
