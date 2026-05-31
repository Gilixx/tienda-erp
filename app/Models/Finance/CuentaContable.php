<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CuentaContable extends Model
{
    protected $table = 'cuentas_contables';

    protected $fillable = [
        'codigo', 'nombre', 'tipo', 'subtipo',
        'nivel', 'parent_id', 'es_auxiliar', 'activa',
    ];

    protected $casts = [
        'nivel'      => 'integer',
        'es_auxiliar' => 'boolean',
        'activa'     => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CuentaContable::class, 'parent_id');
    }

    public function hijos(): HasMany
    {
        return $this->hasMany(CuentaContable::class, 'parent_id');
    }

    public function lineasAsiento(): HasMany
    {
        return $this->hasMany(AsientoLinea::class);
    }

    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }
}
