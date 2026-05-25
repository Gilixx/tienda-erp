<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class CuentaPorPagar extends Model
{
    protected $table = 'cuentas_por_pagar';

    protected $fillable = [
        'compra_id', 'proveedor_id',
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

    public function compra()
    {
        return $this->belongsTo(Compra::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function moneda()
    {
        return $this->belongsTo(Moneda::class);
    }
}
