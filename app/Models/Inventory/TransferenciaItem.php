<?php

namespace App\Models\Inventory;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferenciaItem extends Model
{
    protected $table = 'transferencia_items';

    protected $fillable = [
        'transferencia_id', 'product_id', 'cantidad', 'cantidad_recibida',
    ];

    protected $casts = [
        'cantidad'          => 'integer',
        'cantidad_recibida' => 'integer',
    ];

    public function transferencia(): BelongsTo
    {
        return $this->belongsTo(TransferenciaAlmacen::class, 'transferencia_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
