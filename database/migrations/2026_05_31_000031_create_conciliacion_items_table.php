<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conciliacion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conciliacion_id')
                  ->constrained('conciliaciones_bancarias')
                  ->cascadeOnDelete();
            $table->foreignId('transaccion_id')->constrained('transacciones');
            $table->boolean('conciliado')->default(false);
            $table->timestamp('fecha_conciliacion')->nullable();
            $table->timestamps();

            $table->unique(['conciliacion_id', 'transaccion_id']);
            $table->index(['conciliacion_id', 'conciliado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conciliacion_items');
    }
};
