<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class CategoriaFinanza extends Model
{
    protected $table = 'categorias_finanzas';

    protected $fillable = [
        'nombre', 'tipo', 'color', 'icono', 'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function transacciones()
    {
        return $this->hasMany(Transaccion::class, 'categoria_id');
    }

    public function presupuestos()
    {
        return $this->hasMany(Presupuesto::class, 'categoria_id');
    }
}
