<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaccion_impuesto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaccion_id')->constrained('transacciones')->cascadeOnDelete();
            $table->foreignId('impuesto_id')->constrained('impuestos')->restrictOnDelete();
            $table->decimal('base', 16, 4);
            $table->decimal('tasa', 8, 4);
            $table->decimal('monto', 16, 4);
            $table->timestamps();

            $table->unique(['transaccion_id', 'impuesto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaccion_impuesto');
    }
};
