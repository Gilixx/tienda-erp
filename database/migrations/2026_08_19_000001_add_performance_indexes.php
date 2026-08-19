<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índices para las consultas más frecuentes:
 * - ventas: se filtra por (almacen_id, estado) y rango de fecha en POS y reportes.
 * - inventory_movements: se filtra por almacen_id y se ordena por created_at (latest).
 * - audit_logs: se ordena y filtra por created_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->index(['almacen_id', 'estado', 'fecha'], 'ventas_almacen_estado_fecha_index');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->index(['almacen_id', 'created_at'], 'inv_mov_almacen_created_index');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('created_at', 'audit_logs_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropIndex('ventas_almacen_estado_fecha_index');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropIndex('inv_mov_almacen_created_index');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_created_at_index');
        });
    }
};
