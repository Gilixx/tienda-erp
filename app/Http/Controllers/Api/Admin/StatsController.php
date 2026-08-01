<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    /** GET /api/admin/stats */
    public function __invoke(): JsonResponse
    {
        // Usuarios por servicio (solo accesos vigentes)
        $porServicio = Service::query()
            ->withCount(['users as usuarios_count' => function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            }])
            ->orderBy('name')
            ->get(['id', 'key', 'name'])
            ->map(fn ($s) => [
                'key' => $s->key,
                'name' => $s->name,
                'total' => $s->usuarios_count,
            ]);

        return response()->json([
            'total_usuarios' => User::count(),
            'activos' => User::where('is_active', true)->count(),
            'inactivos' => User::where('is_active', false)->count(),
            'admins' => User::where('role', 'admin')->count(),
            'eliminados' => User::onlyTrashed()->count(),
            'por_servicio' => $porServicio,
        ]);
    }
}
