<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $table = 'ventas';

    protected $fillable = [
        'user_id', 'total', 'estado', 'fecha', 'referencia', 'notas',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'fecha' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(VentaItem::class);
    }
}
