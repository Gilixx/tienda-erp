<?php

namespace App\Models;

use App\Models\Inventory\Almacen;
use App\Models\Inventory\ProductStock;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'sku', 'description',
        'price', 'cost', 'stock', 'min_stock', 'is_active',
        'punto_reorden', 'cantidad_reorden',
        'requiere_lote', 'requiere_serie', 'created_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'is_active' => 'boolean',
        'requiere_lote' => 'boolean',
        'requiere_serie' => 'boolean',
    ];

    /**
     * Categoría del producto. Ahora la categoría es por almacén y vive en
     * product_stock.category_id; esta relación resuelve cuando el listado
     * expone `category_id` (alias del join con product_stock por almacén).
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /** Dueño del producto (cliente/tenant). */
    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: productos accesibles para el usuario.
     * Admin ve todos; el resto ve los suyos y los que tienen stock en
     * algún almacén al que tiene acceso (permite compartir por almacén).
     */
    public function scopeAccesiblesPara($query, User $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        $almacenIds = Almacen::accesiblesPara($user)->pluck('id');

        return $query->where(function ($q) use ($user, $almacenIds) {
            $q->where('products.created_by', $user->id)
                ->orWhereIn('products.id', function ($sub) use ($almacenIds) {
                    $sub->select('product_id')
                        ->from('product_stock')
                        ->whereIn('almacen_id', $almacenIds);
                });
        });
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
