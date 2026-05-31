<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('punto_reorden')->default(0)->after('min_stock');
            $table->integer('cantidad_reorden')->default(0)->after('punto_reorden');
            $table->boolean('requiere_lote')->default(false)->after('is_active');
            $table->boolean('requiere_serie')->default(false)->after('requiere_lote');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['punto_reorden', 'cantidad_reorden', 'requiere_lote', 'requiere_serie']);
        });
    }
};
