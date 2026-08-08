<?php

namespace App\Models;

use App\Models\Inventory\Almacen;
use App\Models\Inventory\ProductStock;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'description', 'created_by', 'almacen_id'];

    /** Asignaciones producto↔categoría (por almacén) vía product_stock. */
    public function stocks()
    {
        return $this->hasMany(ProductStock::class, 'category_id');
    }

    /** Almacén dueño de la categoría (las categorías son por almacén). */
    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }

    /** Creador de la categoría (registro; la visibilidad va por acceso al almacén). */
    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
