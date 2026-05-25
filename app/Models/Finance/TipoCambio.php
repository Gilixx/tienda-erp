<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class TipoCambio extends Model
{
    protected $table = 'tipos_cambio';

    protected $fillable = [
        'moneda_origen_id', 'moneda_destino_id', 'tasa', 'fecha', 'fuente',
    ];

    protected $casts = [
        'tasa'  => 'decimal:8',
        'fecha' => 'date',
    ];

    public function origen()
    {
        return $this->belongsTo(Moneda::class, 'moneda_origen_id');
    }

    public function destino()
    {
        return $this->belongsTo(Moneda::class, 'moneda_destino_id');
    }
}
