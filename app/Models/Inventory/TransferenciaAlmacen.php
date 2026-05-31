<?php

namespace App\Models\Inventory;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransferenciaAlmacen extends Model
{
    use SoftDeletes;

    protected $table = 'transferencias_almacen';

    protected $fillable = [
        'almacen_origen_id', 'almacen_destino_id', 'user_id',
        'estado', 'fecha', 'referencia', 'notas',
        'fecha_recepcion', 'recibido_por',
    ];

    protected $casts = [
        'fecha'           => 'date',
        'fecha_recepcion' => 'datetime',
    ];

    public function almacenOrigen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_origen_id');
    }

    public function almacenDestino(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_destino_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recibidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recibido_por');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransferenciaItem::class, 'transferencia_id');
    }
}
