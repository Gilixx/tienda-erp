<?php

namespace App\Models\Finance;

use App\Models\Venta;
use Illuminate\Database\Eloquent\Model;

class CuentaPorCobrar extends Model
{
    protected $table = 'cuentas_por_cobrar';

    protected $fillable = [
        'venta_id', 'cliente', 'cliente_rfc',
        'moneda_id', 'tipo_cambio',
        'monto_total', 'monto_pagado', 'saldo',
        'fecha_emision', 'fecha_vencimiento',
        'estado', 'notas',
    ];

    protected $casts = [
        'tipo_cambio'       => 'decimal:8',
        'monto_total'       => 'decimal:4',
        'monto_pagado'      => 'decimal:4',
        'saldo'             => 'decimal:4',
        'fecha_emision'     => 'date',
        'fecha_vencimiento' => 'date',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function moneda()
    {
        return $this->belongsTo(Moneda::class);
    }
}
