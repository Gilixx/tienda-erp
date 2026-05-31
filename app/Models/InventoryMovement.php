<?php

namespace App\Models;

use App\Models\Inventory\Almacen;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    protected $fillable = [
        'product_id', 'almacen_id', 'user_id',
        'type', 'quantity', 'reference', 'notes',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }
}
