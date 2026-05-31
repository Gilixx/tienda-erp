<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Depreciacion extends Model
{
    protected $table = 'depreciaciones';

    protected $fillable = [
        'activo_id', 'asiento_id', 'periodo_anio', 'periodo_mes',
        'depreciacion_mensual', 'depreciacion_acumulada', 'valor_libro',
    ];

    protected $casts = [
        'periodo_anio'           => 'integer',
        'periodo_mes'            => 'integer',
        'depreciacion_mensual'   => 'decimal:4',
        'depreciacion_acumulada' => 'decimal:4',
        'valor_libro'            => 'decimal:4',
    ];

    public function activo(): BelongsTo   { return $this->belongsTo(ActivoFijo::class, 'activo_id'); }
    public function asiento(): BelongsTo  { return $this->belongsTo(AsientoContable::class); }
}
