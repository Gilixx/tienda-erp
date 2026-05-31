<?php

namespace App\Models\Inventory;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStock extends Model
{
    protected $table = 'product_stock';

    public $timestamps = false; // solo tiene updated_at manejado por DB

    protected $fillable = [
        'product_id', 'almacen_id', 'cantidad',
    ];

    protected $casts = [
        'cantidad'   => 'integer',
        'updated_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }
}
