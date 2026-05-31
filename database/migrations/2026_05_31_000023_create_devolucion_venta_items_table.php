<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devolucion_venta_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('devolucion_venta_id')
                  ->constrained('devolucion_ventas')
                  ->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('venta_item_id')->nullable()->constrained('venta_items')->nullOnDelete();
            $table->decimal('cantidad_devuelta', 10, 4);
            $table->decimal('precio_unit', 16, 4)->default(0);
            $table->decimal('subtotal', 16, 4)->default(0);
            $table->decimal('impuesto_monto', 16, 4)->default(0);
            $table->decimal('total', 16, 4)->default(0);
            $table->timestamps();

            $table->index(['devolucion_venta_id']);
            $table->index(['product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devolucion_venta_items');
    }
};
