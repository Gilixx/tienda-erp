<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compra_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('impuesto_id')->nullable()->constrained('impuestos')->nullOnDelete();
            $table->decimal('cantidad', 12, 4);
            $table->decimal('costo_unit', 16, 4);
            $table->decimal('subtotal', 16, 4);
            $table->decimal('impuesto_monto', 16, 4)->default(0);
            $table->decimal('total', 16, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compra_items');
    }
};
