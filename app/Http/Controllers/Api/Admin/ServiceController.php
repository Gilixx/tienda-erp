<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /** GET /api/admin/services */
    public function index(): JsonResponse
    {
        return response()->json(
            Service::withCount('users')->orderBy('name')->get()
        );
    }

    /**
     * POST /api/admin/services
     * Nota: crear un registro NO crea un módulo funcional (eso requiere código:
     * ruta, vista y uso del middleware service:<key>). Útil sólo para keys ya codificadas.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string|max:50|alpha_dash|unique:services,key',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:100',
        ]);

        $service = Service::create($validated);

        AuditLog::record('service.created', "Creó el servicio '{$service->name}' ({$service->key})", $service);

        return response()->json($service, 201);
    }

    /** PUT/PATCH /api/admin/services/{service} */
    public function update(Request $request, Service $service): JsonResponse
    {
        // La 'key' no se edita: está ligada al código del módulo.
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:100',
            'is_active' => 'sometimes|boolean',
        ]);

        $service->update($validated);

        AuditLog::record('service.updated', "Editó el servicio '{$service->name}' ({$service->key})", $service, [
            'cambios' => array_keys($validated),
        ]);

        return response()->json($service);
    }
}
