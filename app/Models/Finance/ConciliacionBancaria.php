<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConciliacionBancaria extends Model
{
    protected $table = 'conciliaciones_bancarias';

    protected $fillable = [
        'cuenta_id', 'user_id', 'fecha_inicio', 'fecha_fin',
        'saldo_banco_statement', 'saldo_sistema', 'diferencia',
        'estado', 'notas', 'cerrada_en', 'cerrada_por',
    ];

    protected $casts = [
        'fecha_inicio'          => 'date',
        'fecha_fin'             => 'date',
        'saldo_banco_statement' => 'decimal:4',
        'saldo_sistema'         => 'decimal:4',
        'diferencia'            => 'decimal:4',
        'cerrada_en'            => 'datetime',
    ];

    public function cuenta(): BelongsTo    { return $this->belongsTo(Cuenta::class); }
    public function user(): BelongsTo      { return $this->belongsTo(User::class); }
    public function cerradaPor(): BelongsTo { return $this->belongsTo(User::class, 'cerrada_por'); }

    public function items(): HasMany
    {
        return $this->hasMany(ConciliacionItem::class, 'conciliacion_id');
    }
}
