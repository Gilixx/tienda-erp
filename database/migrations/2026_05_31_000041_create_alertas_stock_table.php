<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertas_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('almacen_id')->nullable()->constrained('almacenes')->nullOnDelete();
            $table->enum('tipo', ['bajo_minimo', 'punto_reorden']);
            $table->integer('stock_actual');
            $table->integer('stock_minimo');
            $table->enum('estado', ['activa', 'vista', 'resuelta'])->default('activa');
            $table->timestamps();

            $table->index(['estado']);
            $table->index(['product_id', 'estado']);
            $table->index(['almacen_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas_stock');
    }
};
