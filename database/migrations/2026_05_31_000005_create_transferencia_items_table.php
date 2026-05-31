<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transferencia_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transferencia_id')
                  ->constrained('transferencias_almacen')
                  ->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->integer('cantidad');
            $table->integer('cantidad_recibida')->nullable();
            $table->timestamps();

            $table->index(['transferencia_id']);
            $table->index(['product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transferencia_items');
    }
};
