<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class UnidadMedida extends Model
{
    protected $table = 'unidades_medida';

    protected $fillable = ['nombre', 'simbolo', 'tipo', 'activa'];

    protected $casts = ['activa' => 'boolean'];
}
