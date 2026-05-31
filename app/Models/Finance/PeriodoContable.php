<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodoContable extends Model
{
    protected $table = 'periodos_contables';

    protected $fillable = [
        'anio', 'mes', 'estado', 'cerrado_por', 'cerrado_en', 'notas',
    ];

    protected $casts = [
        'anio'      => 'integer',
        'mes'       => 'integer',
        'cerrado_en' => 'datetime',
    ];

    public function cerradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cerrado_por');
    }

    /** Verifica si una fecha cae dentro de un período cerrado */
    public static function esFechaCerrada(\DateTimeInterface $fecha): bool
    {
        return self::where('anio', $fecha->format('Y'))
            ->where('mes', $fecha->format('n'))
            ->where('estado', 'cerrado')
            ->exists();
    }
}
