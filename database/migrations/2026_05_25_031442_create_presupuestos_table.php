<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presupuestos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias_finanzas')->cascadeOnDelete();
            $table->foreignId('moneda_id')->constrained('monedas')->restrictOnDelete();
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');
            $table->decimal('monto_limite', 16, 4);
            $table->unsignedTinyInteger('alerta_pct')->default(80);
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->unique(['categoria_id', 'anio', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuestos');
    }
};
