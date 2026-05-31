<?php

namespace App\Models\Inventory;

use App\Models\InventoryMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Almacen extends Model
{
    protected $table = 'almacenes';

    protected $fillable = [
        'nombre', 'codigo', 'descripcion', 'direccion', 'es_principal', 'activo',
    ];

    protected $casts = [
        'es_principal' => 'boolean',
        'activo'       => 'boolean',
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
}
