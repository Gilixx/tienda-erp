<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivoFijo extends Model
{
    use SoftDeletes;

    protected $table = 'activos_fijos';

    protected $fillable = [
        'nombre', 'categoria', 'cuenta_contable_id', 'proveedor_id', 'user_id',
        'costo_adquisicion', 'valor_residual', 'fecha_adquisicion',
        'vida_util_meses', 'metodo_depreciacion', 'estado',
        'numero_serie', 'ubicacion', 'notas',
    ];

    protected $casts = [
        'costo_adquisicion' => 'decimal:4',
        'valor_residual'    => 'decimal:4',
        'fecha_adquisicion' => 'date',
        'vida_util_meses'   => 'integer',
    ];

    public function cuentaContable(): BelongsTo { return $this->belongsTo(CuentaContable::class); }
    public function proveedor(): BelongsTo       { return $this->belongsTo(Proveedor::class); }
    public function user(): BelongsTo            { return $this->belongsTo(User::class); }

    public function depreciaciones(): HasMany
    {
        return $this->hasMany(Depreciacion::class, 'activo_id');
    }

    /** Depreciación mensual lineal */
    public function depreciacionMensualLineal(): float
    {
        $base = (float) $this->costo_adquisicion - (float) $this->valor_residual;
        return $this->vida_util_meses > 0
            ? round($base / $this->vida_util_meses, 4)
            : 0;
    }

    /** Valor libro actual */
    public function valorLibroActual(): float
    {
        $acumulada = $this->depreciaciones()->sum('depreciacion_mensual');
        return (float) $this->costo_adquisicion - (float) $acumulada;
    }
}
