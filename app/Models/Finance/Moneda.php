<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class Moneda extends Model
{
    protected $table = 'monedas';

    protected $fillable = [
        'codigo', 'nombre', 'simbolo', 'decimales', 'es_base', 'activa',
    ];

    protected $casts = [
        'es_base'   => 'boolean',
        'activa'    => 'boolean',
        'decimales' => 'integer',
    ];

    public function tasasOrigen()
    {
        return $this->hasMany(TipoCambio::class, 'moneda_origen_id');
    }

    public function tasasDestino()
    {
        return $this->hasMany(TipoCambio::class, 'moneda_destino_id');
    }

    public static function base(): ?self
    {
        return static::where('es_base', true)->first();
    }
}
