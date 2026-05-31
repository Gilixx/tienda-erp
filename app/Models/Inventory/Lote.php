<?php

namespace App\Models\Inventory;

use App\Models\Finance\Proveedor;
use App\Models\Finance\CompraItem;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Lote extends Model
{
    protected $table = 'lotes';

    protected $fillable = [
        'product_id', 'almacen_id', 'proveedor_id', 'compra_item_id',
        'numero_lote', 'numero_serie', 'fecha_fabricacion', 'fecha_vencimiento',
        'cantidad_inicial', 'cantidad_actual', 'activo',
    ];

    protected $casts = [
        'fecha_fabricacion' => 'date',
        'fecha_vencimiento' => 'date',
        'cantidad_inicial'  => 'integer',
        'cantidad_actual'   => 'integer',
        'activo'            => 'boolean',
    ];

    public function product(): BelongsTo    { return $this->belongsTo(Product::class); }
    public function almacen(): BelongsTo    { return $this->belongsTo(Almacen::class); }
    public function proveedor(): BelongsTo  { return $this->belongsTo(Proveedor::class); }
    public function compraItem(): BelongsTo { return $this->belongsTo(CompraItem::class); }

    public function movements(): BelongsToMany
    {
        return $this->belongsToMany(
            InventoryMovement::class,
            'movement_lote',
            'lote_id',
            'inventory_movement_id'
        )->withPivot('cantidad')->withTimestamps();
    }

    /** ¿Está este lote por vencer en N días? */
    public function venceEnDias(int $dias = 30): bool
    {
        return $this->fecha_vencimiento
            && $this->fecha_vencimiento->lte(now()->addDays($dias));
    }

    public function scopeActivos($query)     { return $query->where('activo', true); }
    public function scopeConStock($query)    { return $query->where('cantidad_actual', '>', 0); }

    /** Orden FEFO: fecha_vencimiento ASC (nulos al final) */
    public function scopeFefo($query)
    {
        return $query->orderByRaw('CASE WHEN fecha_vencimiento IS NULL THEN 1 ELSE 0 END')
                     ->orderBy('fecha_vencimiento', 'asc');
    }
}
