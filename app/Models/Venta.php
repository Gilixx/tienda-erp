<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $table = 'ventas';

    protected $fillable = [
        'user_id', 'total', 'estado', 'fecha', 'referencia', 'notas',
        'forma_pago', 'cuenta_id', 'moneda_id', 'tipo_cambio',
        'fecha_vencimiento', 'cliente', 'cliente_rfc',
    ];

    protected $casts = [
        'total'             => 'decimal:2',
        'fecha'             => 'datetime',
        'fecha_vencimiento' => 'date',
        'tipo_cambio'       => 'decimal:8',
    ];

    public function cuenta()
    {
        return $this->belongsTo(\App\Models\Finance\Cuenta::class, 'cuenta_id');
    }

    public function moneda()
    {
        return $this->belongsTo(\App\Models\Finance\Moneda::class, 'moneda_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(VentaItem::class);
    }
}
