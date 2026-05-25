<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class Presupuesto extends Model
{
    protected $table = 'presupuestos';

    protected $fillable = [
        'categoria_id', 'moneda_id', 'anio', 'mes',
        'monto_limite', 'alerta_pct', 'notas',
    ];

    protected $casts = [
        'monto_limite' => 'decimal:4',
        'anio'         => 'integer',
        'mes'          => 'integer',
        'alerta_pct'   => 'integer',
    ];

    public function categoria()
    {
        return $this->belongsTo(CategoriaFinanza::class, 'categoria_id');
    }

    public function moneda()
    {
        return $this->belongsTo(Moneda::class);
    }
}
