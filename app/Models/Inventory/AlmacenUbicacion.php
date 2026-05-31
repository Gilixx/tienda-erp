<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlmacenUbicacion extends Model
{
    protected $table = 'almacen_ubicaciones';

    protected $fillable = [
        'almacen_id', 'codigo', 'nombre', 'descripcion', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }
}
