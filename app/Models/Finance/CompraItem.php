<?php

namespace App\Models\Finance;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

class CompraItem extends Model
{
    protected $table = 'compra_items';

    protected $fillable = [
        'compra_id', 'product_id', 'impuesto_id',
        'cantidad', 'costo_unit', 'subtotal', 'impuesto_monto', 'total',
    ];

    protected $casts = [
        'cantidad'       => 'decimal:4',
        'costo_unit'     => 'decimal:4',
        'subtotal'       => 'decimal:4',
        'impuesto_monto' => 'decimal:4',
        'total'          => 'decimal:4',
    ];

    public function compra()
    {
        return $this->belongsTo(Compra::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function impuesto()
    {
        return $this->belongsTo(Impuesto::class);
    }
}
