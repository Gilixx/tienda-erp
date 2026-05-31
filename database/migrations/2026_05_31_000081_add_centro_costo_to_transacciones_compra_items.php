<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transacciones', function (Blueprint $table) {
            $table->foreignId('centro_costo_id')
                  ->nullable()
                  ->after('categoria_id')
                  ->constrained('centros_costo')
                  ->nullOnDelete();
        });

        Schema::table('compra_items', function (Blueprint $table) {
            $table->foreignId('centro_costo_id')
                  ->nullable()
                  ->after('unidad_medida_id')
                  ->constrained('centros_costo')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transacciones', function (Blueprint $table) {
            $table->dropForeign(['centro_costo_id']);
            $table->dropColumn('centro_costo_id');
        });

        Schema::table('compra_items', function (Blueprint $table) {
            $table->dropForeign(['centro_costo_id']);
            $table->dropColumn('centro_costo_id');
        });
    }
};
