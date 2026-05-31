<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConciliacionItem extends Model
{
    protected $table = 'conciliacion_items';

    protected $fillable = [
        'conciliacion_id', 'transaccion_id', 'conciliado', 'fecha_conciliacion',
    ];

    protected $casts = [
        'conciliado'        => 'boolean',
        'fecha_conciliacion' => 'datetime',
    ];

    public function conciliacion(): BelongsTo   { return $this->belongsTo(ConciliacionBancaria::class, 'conciliacion_id'); }
    public function transaccion(): BelongsTo     { return $this->belongsTo(Transaccion::class); }
}
