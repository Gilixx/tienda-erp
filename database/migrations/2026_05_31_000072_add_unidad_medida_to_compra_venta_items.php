<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compra_items', function (Blueprint $table) {
            $table->foreignId('unidad_medida_id')
                  ->nullable()
                  ->after('impuesto_id')
                  ->constrained('unidades_medida')
                  ->nullOnDelete();
        });

        Schema::table('venta_items', function (Blueprint $table) {
            $table->foreignId('unidad_medida_id')
                  ->nullable()
                  ->after('product_id')
                  ->constrained('unidades_medida')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('compra_items', function (Blueprint $table) {
            $table->dropForeign(['unidad_medida_id']);
            $table->dropColumn('unidad_medida_id');
        });

        Schema::table('venta_items', function (Blueprint $table) {
            $table->dropForeign(['unidad_medida_id']);
            $table->dropColumn('unidad_medida_id');
        });
    }
};
