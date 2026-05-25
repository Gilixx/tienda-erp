<?php

namespace App\Models\Finance;

use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Model;

class Transaccion extends Model
{
    protected $table = 'transacciones';

    protected $fillable = [
        'cuenta_id', 'categoria_id', 'user_id', 'tipo',
        'moneda_id', 'tipo_cambio',
        'subtotal', 'impuestos', 'retenciones', 'total', 'total_base',
        'fecha', 'descripcion', 'referencia',
        'venta_id', 'compra_id', 'cuenta_destino_id',
        'estado', 'notas',
    ];

    protected $casts = [
        'tipo_cambio' => 'decimal:8',
        'subtotal'    => 'decimal:4',
        'impuestos'   => 'decimal:4',
        'retenciones' => 'decimal:4',
        'total'       => 'decimal:4',
        'total_base'  => 'decimal:4',
        'fecha'       => 'date',
    ];

    public function cuenta()
    {
        return $this->belongsTo(Cuenta::class);
    }

    public function cuentaDestino()
    {
        return $this->belongsTo(Cuenta::class, 'cuenta_destino_id');
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaFinanza::class, 'categoria_id');
    }

    public function moneda()
    {
        return $this->belongsTo(Moneda::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function compra()
    {
        return $this->belongsTo(Compra::class);
    }

    public function impuestosDetalle()
    {
        return $this->belongsToMany(Impuesto::class, 'transaccion_impuesto')
                    ->withPivot('base', 'tasa', 'monto')
                    ->withTimestamps();
    }
}
