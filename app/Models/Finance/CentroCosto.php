<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class CentroCosto extends Model
{
    protected $table = 'centros_costo';

    protected $fillable = ['codigo', 'nombre', 'descripcion', 'activo'];

    protected $casts = ['activo' => 'boolean'];
}
