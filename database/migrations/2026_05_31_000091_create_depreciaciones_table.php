<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depreciaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activo_id')->constrained('activos_fijos')->cascadeOnDelete();
            $table->foreignId('asiento_id')->nullable()->constrained('asientos_contables')->nullOnDelete();
            $table->year('periodo_anio');
            $table->tinyInteger('periodo_mes'); // 1-12
            $table->decimal('depreciacion_mensual', 16, 4);
            $table->decimal('depreciacion_acumulada', 16, 4);
            $table->decimal('valor_libro', 16, 4);
            $table->timestamps();

            $table->unique(['activo_id', 'periodo_anio', 'periodo_mes']);
            $table->index(['periodo_anio', 'periodo_mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depreciaciones');
    }
};
