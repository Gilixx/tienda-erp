<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('almacen_id')->nullable()->constrained('almacenes')->nullOnDelete();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->foreignId('compra_item_id')->nullable()->constrained('compra_items')->nullOnDelete();
            $table->string('numero_lote', 100);
            $table->string('numero_serie', 100)->nullable();
            $table->date('fecha_fabricacion')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->integer('cantidad_inicial')->default(0);
            $table->integer('cantidad_actual')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'numero_lote', 'almacen_id']);
            $table->index(['product_id', 'activo']);
            $table->index(['fecha_vencimiento']);
            $table->index(['almacen_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotes');
    }
};
