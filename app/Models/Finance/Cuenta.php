<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class Cuenta extends Model
{
    protected $table = 'cuentas';

    protected $fillable = [
        'nombre', 'tipo', 'moneda_id', 'saldo_inicial', 'saldo_actual',
        'banco', 'numero_cuenta', 'limite_credito', 'notas', 'activa',
    ];

    protected $casts = [
        'saldo_inicial'  => 'decimal:4',
        'saldo_actual'   => 'decimal:4',
        'limite_credito' => 'decimal:4',
        'activa'         => 'boolean',
    ];

    public function moneda()
    {
        return $this->belongsTo(Moneda::class);
    }

    public function transacciones()
    {
        return $this->hasMany(Transaccion::class);
    }
}
