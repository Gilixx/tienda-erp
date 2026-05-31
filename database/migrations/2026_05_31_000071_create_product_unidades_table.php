<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_unidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('unidad_compra_id')->constrained('unidades_medida');
            $table->foreignId('unidad_venta_id')->constrained('unidades_medida');
            $table->decimal('factor_conversion', 12, 6)->default(1.000000);
            $table->timestamps();

            $table->unique(['product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_unidades');
    }
};
