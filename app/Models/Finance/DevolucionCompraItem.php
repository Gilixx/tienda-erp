<?php

namespace App\Models\Finance;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DevolucionCompraItem extends Model
{
    protected $table = 'devolucion_compra_items';

    protected $fillable = [
        'devolucion_compra_id', 'product_id', 'compra_item_id',
        'cantidad_devuelta', 'costo_unit', 'subtotal', 'impuesto_monto', 'total',
    ];

    protected $casts = [
        'cantidad_devuelta' => 'decimal:4',
        'costo_unit'        => 'decimal:4',
        'subtotal'          => 'decimal:4',
        'impuesto_monto'    => 'decimal:4',
        'total'             => 'decimal:4',
    ];

    public function devolucion(): BelongsTo  { return $this->belongsTo(DevolucionCompra::class, 'devolucion_compra_id'); }
    public function product(): BelongsTo     { return $this->belongsTo(Product::class); }
    public function compraItem(): BelongsTo  { return $this->belongsTo(CompraItem::class); }
}
