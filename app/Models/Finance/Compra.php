<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    protected $table = 'compras';

    protected $fillable = [
        'proveedor_id', 'user_id', 'moneda_id', 'tipo_cambio',
        'subtotal', 'impuestos', 'total',
        'estado', 'forma_pago', 'fecha', 'fecha_vencimiento',
        'referencia', 'notas',
    ];

    protected $casts = [
        'tipo_cambio'       => 'decimal:8',
        'subtotal'          => 'decimal:4',
        'impuestos'         => 'decimal:4',
        'total'             => 'decimal:4',
        'fecha'             => 'date',
        'fecha_vencimiento' => 'date',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function moneda()
    {
        return $this->belongsTo(Moneda::class);
    }

    public function items()
    {
        return $this->hasMany(CompraItem::class);
    }
}
