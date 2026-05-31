<?php

namespace App\Models\Inventory;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarioFisicoItem extends Model
{
    protected $table = 'inventario_fisico_items';

    protected $fillable = [
        'inventario_fisico_id', 'product_id',
        'cantidad_teorica', 'cantidad_contada',
        'costo_unit', 'contado_por', 'contado_en',
    ];

    protected $casts = [
        'cantidad_teorica'  => 'integer',
        'cantidad_contada'  => 'integer',
        'diferencia'        => 'integer',
        'costo_unit'        => 'decimal:4',
        'valor_diferencia'  => 'decimal:4',
        'contado_en'        => 'datetime',
    ];

    public function inventarioFisico(): BelongsTo
    {
        return $this->belongsTo(InventarioFisico::class, 'inventario_fisico_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function contadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contado_por');
    }
}
