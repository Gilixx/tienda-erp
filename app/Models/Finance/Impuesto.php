<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class Impuesto extends Model
{
    protected $table = 'impuestos';

    protected $fillable = [
        'codigo', 'nombre', 'tipo', 'tasa', 'aplicacion', 'incluido_en_precio', 'activo',
    ];

    protected $casts = [
        'tasa'               => 'decimal:4',
        'incluido_en_precio' => 'boolean',
        'activo'             => 'boolean',
    ];

    public function transacciones()
    {
        return $this->belongsToMany(Transaccion::class, 'transaccion_impuesto')
                    ->withPivot('base', 'tasa', 'monto')
                    ->withTimestamps();
    }
}
