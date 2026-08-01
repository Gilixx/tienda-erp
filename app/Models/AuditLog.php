<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'target_type', 'target_id', 'description', 'metadata', 'ip_address',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /** Usuario que realizó la acción (actor). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Registra un evento de auditoría.
     *
     * @param  string  $action  ej. 'user.created', 'service.granted'
     * @param  string  $description  descripción legible
     * @param  Model|null  $target  entidad afectada (User, Service, etc.)
     * @param  array  $metadata  datos adicionales (antes/después, etc.)
     */
    public static function record(string $action, string $description, ?Model $target = null, array $metadata = []): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'target_type' => $target ? $target::class : null,
            'target_id' => $target?->getKey(),
            'description' => $description,
            'metadata' => $metadata ?: null,
            'ip_address' => request()->ip(),
        ]);
    }
}
