<?php

namespace App\Models\Inventory;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventarioFisico extends Model
{
    use SoftDeletes;

    protected $table = 'inventario_fisico';

    protected $fillable = [
        'almacen_id', 'user_id', 'estado',
        'fecha_apertura', 'fecha_cierre', 'fecha_aplicacion',
        'aplicado_por', 'notas', 'diferencia_total_valor',
    ];

    protected $casts = [
        'fecha_apertura'   => 'datetime',
        'fecha_cierre'     => 'datetime',
        'fecha_aplicacion' => 'datetime',
        'diferencia_total_valor' => 'decimal:4',
    ];

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function aplicadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aplicado_por');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventarioFisicoItem::class, 'inventario_fisico_id');
    }
}
