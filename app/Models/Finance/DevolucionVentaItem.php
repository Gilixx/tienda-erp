<?php

namespace App\Models\Finance;

use App\Models\Product;
use App\Models\VentaItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DevolucionVentaItem extends Model
{
    protected $table = 'devolucion_venta_items';

    protected $fillable = [
        'devolucion_venta_id', 'product_id', 'venta_item_id',
        'cantidad_devuelta', 'precio_unit', 'subtotal', 'impuesto_monto', 'total',
    ];

    protected $casts = [
        'cantidad_devuelta' => 'decimal:4',
        'precio_unit'       => 'decimal:4',
        'subtotal'          => 'decimal:4',
        'impuesto_monto'    => 'decimal:4',
        'total'             => 'decimal:4',
    ];

    public function devolucion(): BelongsTo { return $this->belongsTo(DevolucionVenta::class, 'devolucion_venta_id'); }
    public function product(): BelongsTo    { return $this->belongsTo(Product::class); }
    public function ventaItem(): BelongsTo  { return $this->belongsTo(VentaItem::class); }
}
