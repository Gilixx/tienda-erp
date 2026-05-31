<?php

namespace App\Models\Inventory;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertaStock extends Model
{
    protected $table = 'alertas_stock';

    protected $fillable = [
        'product_id', 'almacen_id', 'tipo',
        'stock_actual', 'stock_minimo', 'estado',
    ];

    public function product(): BelongsTo  { return $this->belongsTo(Product::class); }
    public function almacen(): BelongsTo  { return $this->belongsTo(Almacen::class); }
}
