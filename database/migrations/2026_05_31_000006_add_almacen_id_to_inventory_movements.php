<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            // Se agrega después de product_id; nullable para no romper registros históricos
            $table->foreignId('almacen_id')
                  ->nullable()
                  ->after('product_id')
                  ->constrained('almacenes')
                  ->nullOnDelete();

            $table->index(['almacen_id']);
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['almacen_id']);
            $table->dropIndex(['almacen_id']);
            $table->dropColumn('almacen_id');
        });
    }
};
