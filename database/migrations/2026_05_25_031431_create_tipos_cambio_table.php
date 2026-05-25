<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_cambio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('moneda_origen_id')->constrained('monedas')->cascadeOnDelete();
            $table->foreignId('moneda_destino_id')->constrained('monedas')->cascadeOnDelete();
            $table->decimal('tasa', 18, 8);
            $table->date('fecha')->index();
            $table->string('fuente')->nullable();
            $table->timestamps();

            $table->unique(['moneda_origen_id', 'moneda_destino_id', 'fecha'], 'uniq_tc_par_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_cambio');
    }
};
