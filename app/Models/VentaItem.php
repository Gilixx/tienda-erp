<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentaItem extends Model
{
    protected $table = 'venta_items';

    protected $fillable = [
        'venta_id', 'product_id', 'cantidad', 'precio_unit', 'subtotal',
    ];

    protected $casts = [
        'precio_unit' => 'decimal:2',
        'subtotal'    => 'decimal:2',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
