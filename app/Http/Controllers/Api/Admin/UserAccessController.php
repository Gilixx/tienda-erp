<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Almacen;
use App\Models\Service;
use App\Models\User;
use App\Services\Admin\AccessManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserAccessController extends Controller
{
    public function __construct(private readonly AccessManager $accessManager) {}

    /**
     * GET /api/admin/users/{user}/access
     * Estado actual del acceso + catálogo disponible para poblar el modal.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'services_disponibles' => Service::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'key', 'name', 'description', 'icon']),
            'almacenes_disponibles' => Almacen::where('activo', true)
                ->orderByDesc('es_principal')
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'codigo', 'es_principal']),
            'dependencias' => AccessManager::DEPENDENCIES,
            'acceso_actual' => [
                'services' => $user->services()->get(['services.id', 'key', 'name'])
                    ->map(fn ($s) => [
                        'key' => $s->key,
                        'expires_at' => optional($s->pivot->expires_at)?->toDateString(),
                    ]),
                'almacen_ids' => $user->almacenesConAcceso()->pluck('almacenes.id'),
            ],
        ]);
    }

    /**
     * PUT /api/admin/users/{user}/access
     * Sincroniza servicios y almacenes en una sola operación (aplica reglas de negocio).
     */
    public function sync(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'services' => 'present|array',
            'services.*.key' => 'required|string|exists:services,key',
            'services.*.expires_at' => 'nullable|date|after:today',
            'almacen_ids' => 'array',
            'almacen_ids.*' => 'integer|exists:almacenes,id',
        ]);

        $this->accessManager->syncAccess(
            $user,
            $validated['services'] ?? [],
            $validated['almacen_ids'] ?? [],
        );

        return response()->json([
            'message' => 'Acceso actualizado correctamente.',
            'acceso' => $this->accessManager->accessSnapshot($user->refresh()),
        ]);
    }
}
