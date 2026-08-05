<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'description', 'created_by'];

    public function products()
    {
        return $this->hasMany(Product::class);
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
