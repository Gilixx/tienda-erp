<?php

namespace App\Models\Inventory;

use App\Models\InventoryMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Almacen extends Model
{
    protected $table = 'almacenes';

    protected $fillable = [
        'nombre', 'codigo', 'descripcion', 'direccion', 'es_principal', 'activo', 'created_by',
    ];

    protected $casts = [
        'es_principal' => 'boolean',
        'activo' => 'boolean',
    ];

    public function ubicaciones(): HasMany
    {
        return $this->hasMany(AlmacenUbicacion::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function transferenciasOrigen(): HasMany
    {
        return $this->hasMany(TransferenciaAlmacen::class, 'almacen_origen_id');
    }

    public function transferenciasDestino(): HasMany
    {
        return $this->hasMany(TransferenciaAlmacen::class, 'almacen_destino_id');
    }

    /** Scope: solo almacenes activos */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // ── Permisos de acceso por dueño ─────────────────────────

    /** Usuario que creó el almacén (dueño). Null para el Principal migrado. */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Usuarios a los que el dueño concedió acceso. */
    public function usuariosConAcceso(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'almacen_user')
            ->withPivot('granted_by')
            ->withTimestamps();
    }

    /**
     * ¿El usuario puede ver/operar este almacén?
     * Admins, el almacén Principal, el dueño y los usuarios con acceso concedido.
     */
    public function accesiblePara(User $user): bool
    {
        if ($user->isAdmin() || $this->es_principal) {
            return true;
        }

        if ($this->created_by === $user->id) {
            return true;
        }

        return $this->usuariosConAcceso()->where('users.id', $user->id)->exists();
    }

    /** ¿El usuario puede gestionar los permisos del almacén? Solo dueño o admin. */
    public function puedeGestionar(User $user): bool
    {
        return $user->isAdmin() || $this->created_by === $user->id;
    }

    /**
     * Scope: almacenes accesibles para el usuario.
     * Admin ve todos; el resto ve el Principal, los que creó y los concedidos.
     */
    public function scopeAccesiblesPara($query, User $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('es_principal', true)
                ->orWhere('created_by', $user->id)
                ->orWhereIn('id', function ($sub) use ($user) {
                    $sub->select('almacen_id')
                        ->from('almacen_user')
                        ->where('user_id', $user->id);
                });
        });
    }
}
