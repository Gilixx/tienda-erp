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

    /** Dueño de la categoría (cliente/tenant). */
    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: categorías accesibles para el usuario.
     * Admin ve todas; el resto solo las suyas.
     */
    public function scopeAccesiblesPara($query, User $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where('categories.created_by', $user->id);
    }
}
