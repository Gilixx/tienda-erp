<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_fisico_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventario_fisico_id')
                  ->constrained('inventario_fisico')
                  ->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->integer('cantidad_teorica');        // snapshot al abrir sesión
            $table->integer('cantidad_contada')->nullable(); // llenado durante el conteo
            $table->integer('diferencia')->virtualAs('COALESCE(cantidad_contada, cantidad_teorica) - cantidad_teorica');
            $table->decimal('costo_unit', 10, 4)->default(0);
            $table->decimal('valor_diferencia', 16, 4)->virtualAs('(COALESCE(cantidad_contada, cantidad_teorica) - cantidad_teorica) * costo_unit');
            $table->foreignId('contado_por')->nullable()->constrained('users');
            $table->timestamp('contado_en')->nullable();
            $table->timestamps();

            $table->unique(['inventario_fisico_id', 'product_id']);
            $table->index(['inventario_fisico_id']);
            $table->index(['product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_fisico_items');
    }
};
