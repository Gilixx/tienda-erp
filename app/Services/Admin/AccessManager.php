<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Centraliza la lógica de asignación de acceso (servicios + almacenes) de un usuario.
 *
 * Reglas de negocio:
 *  - El servicio dependiente sólo puede otorgarse si el usuario tiene su requisito.
 *    (POS 'pos' requiere Inventario 'inventory').
 *  - Otorgar POS exige al menos un almacén asignado.
 *  - Revocar un requisito revoca en cascada los servicios que dependen de él.
 */
class AccessManager
{
    /** Mapa declarativo de dependencias: servicio => requisito. */
    public const DEPENDENCIES = [
        'pos' => 'inventory',
    ];

    /**
     * Sincroniza el acceso completo del usuario en una sola operación atómica.
     *
     * @param  array  $services  lista de ['key' => string, 'expires_at' => string|null]
     * @param  array  $almacenIds  ids de almacenes a conceder (pivote almacen_user)
     *
     * @throws ValidationException si se violan las reglas de dependencia/almacén
     */
    public function syncAccess(User $user, array $services, array $almacenIds): void
    {
        // Normaliza: key => expires_at
        $requested = collect($services)
            ->filter(fn ($s) => ! empty($s['key']))
            ->mapWithKeys(fn ($s) => [$s['key'] => $s['expires_at'] ?? null]);

        $requestedKeys = $requested->keys();

        // 1) Validar dependencias en cascada.
        foreach (self::DEPENDENCIES as $dependiente => $requisito) {
            if ($requestedKeys->contains($dependiente) && ! $requestedKeys->contains($requisito)) {
                throw ValidationException::withMessages([
                    'services' => "El servicio '{$dependiente}' requiere que el usuario también tenga '{$requisito}'.",
                ]);
            }
        }

        // 2) POS exige al menos un almacén.
        if ($requestedKeys->contains('pos') && empty($almacenIds)) {
            throw ValidationException::withMessages([
                'almacenes' => 'Debes asignar al menos un almacén para otorgar acceso a Ventas (POS).',
            ]);
        }

        // 3) Todas las keys deben existir en el catálogo.
        $serviciosCatalogo = Service::whereIn('key', $requestedKeys)->get()->keyBy('key');
        $desconocidas = $requestedKeys->diff($serviciosCatalogo->keys());
        if ($desconocidas->isNotEmpty()) {
            throw ValidationException::withMessages([
                'services' => 'Servicio(s) inexistente(s): '.$desconocidas->implode(', '),
            ]);
        }

        $antes = $this->accessSnapshot($user);

        DB::transaction(function () use ($user, $requested, $serviciosCatalogo, $almacenIds) {
            // Sincroniza el pivote user_service preservando granted_at de los ya existentes.
            $existentes = $user->services()->pluck('key')->all();

            $syncPayload = [];
            foreach ($requested as $key => $expiresAt) {
                $serviceId = $serviciosCatalogo[$key]->id;
                $syncPayload[$serviceId] = [
                    'expires_at' => $expiresAt,
                    'granted_at' => in_array($key, $existentes, true)
                        ? $user->services()->where('key', $key)->first()->pivot->granted_at
                        : now(),
                ];
            }

            $user->services()->sync($syncPayload);

            // Sincroniza almacenes concedidos (pivote almacen_user).
            $almacenPayload = collect($almacenIds)
                ->mapWithKeys(fn ($id) => [$id => ['granted_by' => auth()->id()]])
                ->all();

            $user->almacenesConAcceso()->sync($almacenPayload);
        });

        $despues = $this->accessSnapshot($user->refresh());

        AuditLog::record(
            'access.synced',
            "Actualizó el acceso de {$user->name} ({$user->email})",
            $user,
            ['antes' => $antes, 'despues' => $despues],
        );
    }

    /**
     * Snapshot legible del acceso actual del usuario (para auditoría).
     */
    public function accessSnapshot(User $user): array
    {
        return [
            'services' => $user->services()->pluck('key')->all(),
            'almacenes' => $user->almacenesConAcceso()->pluck('almacenes.id')->all(),
        ];
    }
}
