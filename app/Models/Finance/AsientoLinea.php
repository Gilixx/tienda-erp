<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsientoLinea extends Model
{
    protected $table = 'asiento_lineas';

    protected $fillable = [
        'asiento_id', 'cuenta_contable_id', 'descripcion', 'cargo', 'abono',
    ];

    protected $casts = [
        'cargo' => 'decimal:4',
        'abono' => 'decimal:4',
    ];

    public function asiento(): BelongsTo       { return $this->belongsTo(AsientoContable::class); }
    public function cuentaContable(): BelongsTo { return $this->belongsTo(CuentaContable::class); }
}
