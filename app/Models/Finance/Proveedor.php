<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $table = 'proveedores';

    protected $fillable = [
        'nombre', 'rfc', 'contacto', 'telefono', 'email', 'direccion',
        'moneda_id', 'dias_credito', 'notas', 'activo',
    ];

    protected $casts = [
        'activo'       => 'boolean',
        'dias_credito' => 'integer',
    ];

    public function moneda()
    {
        return $this->belongsTo(Moneda::class);
    }

    public function compras()
    {
        return $this->hasMany(Compra::class);
    }

    public function cuentasPorPagar()
    {
        return $this->hasMany(CuentaPorPagar::class);
    }
}
