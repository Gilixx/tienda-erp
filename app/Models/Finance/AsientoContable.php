<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AsientoContable extends Model
{
    use SoftDeletes;

    protected $table = 'asientos_contables';

    protected $fillable = [
        'tipo', 'referencia', 'descripcion',
        'fecha', 'user_id', 'estado',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(AsientoLinea::class, 'asiento_id');
    }

    /** Valida que sum(cargo) = sum(abono) */
    public function estaBalanceado(): bool
    {
        $totales = $this->lineas()
            ->selectRaw('SUM(cargo) as total_cargo, SUM(abono) as total_abono')
            ->first();

        return abs((float) $totales->total_cargo - (float) $totales->total_abono) < 0.001;
    }
}
