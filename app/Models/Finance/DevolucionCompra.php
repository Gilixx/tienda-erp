<?php

namespace App\Models\Finance;

use App\Models\Inventory\Almacen;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DevolucionCompra extends Model
{
    use SoftDeletes;

    protected $table = 'devolucion_compras';

    protected $fillable = [
        'compra_id', 'proveedor_id', 'user_id', 'moneda_id', 'almacen_id',
        'tipo_cambio', 'subtotal', 'impuestos', 'total', 'total_base',
        'motivo', 'estado', 'fecha', 'referencia', 'notas',
    ];

    protected $casts = [
        'tipo_cambio' => 'decimal:8',
        'subtotal'    => 'decimal:4',
        'impuestos'   => 'decimal:4',
        'total'       => 'decimal:4',
        'total_base'  => 'decimal:4',
        'fecha'       => 'date',
    ];

    public function compra(): BelongsTo    { return $this->belongsTo(Compra::class); }
    public function proveedor(): BelongsTo { return $this->belongsTo(Proveedor::class); }
    public function user(): BelongsTo      { return $this->belongsTo(User::class); }
    public function moneda(): BelongsTo    { return $this->belongsTo(Moneda::class); }
    public function almacen(): BelongsTo   { return $this->belongsTo(Almacen::class); }

    public function items(): HasMany
    {
        return $this->hasMany(DevolucionCompraItem::class);
    }
}
