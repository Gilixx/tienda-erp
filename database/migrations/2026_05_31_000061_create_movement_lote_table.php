<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movement_lote', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_movement_id')
                  ->constrained('inventory_movements')
                  ->cascadeOnDelete();
            $table->foreignId('lote_id')->constrained('lotes');
            $table->integer('cantidad');
            $table->timestamps();

            $table->index(['inventory_movement_id']);
            $table->index(['lote_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movement_lote');
    }
};
