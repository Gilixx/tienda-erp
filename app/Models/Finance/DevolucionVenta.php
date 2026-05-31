<?php

namespace App\Models\Finance;

use App\Models\Inventory\Almacen;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DevolucionVenta extends Model
{
    use SoftDeletes;

    protected $table = 'devolucion_ventas';

    protected $fillable = [
        'venta_id', 'user_id', 'moneda_id', 'almacen_id', 'cuenta_id',
        'tipo_cambio', 'subtotal', 'impuestos', 'total', 'total_base',
        'motivo', 'estado', 'genera_nota_credito', 'fecha', 'referencia', 'notas',
    ];

    protected $casts = [
        'tipo_cambio'        => 'decimal:8',
        'subtotal'           => 'decimal:4',
        'impuestos'          => 'decimal:4',
        'total'              => 'decimal:4',
        'total_base'         => 'decimal:4',
        'genera_nota_credito' => 'boolean',
        'fecha'              => 'date',
    ];

    public function venta(): BelongsTo    { return $this->belongsTo(Venta::class); }
    public function user(): BelongsTo     { return $this->belongsTo(User::class); }
    public function moneda(): BelongsTo   { return $this->belongsTo(Moneda::class); }
    public function almacen(): BelongsTo  { return $this->belongsTo(Almacen::class); }
    public function cuenta(): BelongsTo   { return $this->belongsTo(Cuenta::class); }

    public function items(): HasMany
    {
        return $this->hasMany(DevolucionVentaItem::class);
    }
}
