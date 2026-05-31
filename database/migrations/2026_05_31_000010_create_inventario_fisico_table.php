<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_fisico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('almacen_id')->constrained('almacenes');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('estado', ['abierto', 'cerrado', 'aplicado'])->default('abierto');
            $table->timestamp('fecha_apertura')->useCurrent();
            $table->timestamp('fecha_cierre')->nullable();
            $table->timestamp('fecha_aplicacion')->nullable();
            $table->foreignId('aplicado_por')->nullable()->constrained('users');
            $table->text('notas')->nullable();
            $table->decimal('diferencia_total_valor', 16, 4)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['almacen_id', 'estado']);
            $table->index(['estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_fisico');
    }
};
