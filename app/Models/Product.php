<?php

namespace App\Models;

use App\Models\Inventory\ProductStock;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'name', 'sku', 'description',
        'price', 'cost', 'stock', 'min_stock', 'is_active',
        'punto_reorden', 'cantidad_reorden',
        'requiere_lote', 'requiere_serie',
    ];

    protected $casts = [
        'price'          => 'decimal:2',
        'cost'           => 'decimal:2',
        'is_active'      => 'boolean',
        'requiere_lote'  => 'boolean',
        'requiere_serie' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function ventaItems()
    {
        return $this->hasMany(VentaItem::class);
    }

    /** Stock distribuido por almacén */
    public function stockPorAlmacen()
    {
        return $this->hasMany(ProductStock::class);
    }

    /**
     * Stock en un almacén específico.
     * Devuelve la cantidad o 0 si no existe registro.
     */
    public function stockEnAlmacen(int $almacenId): int
    {
        return $this->stockPorAlmacen()
            ->where('almacen_id', $almacenId)
            ->value('cantidad') ?? 0;
    }
}
