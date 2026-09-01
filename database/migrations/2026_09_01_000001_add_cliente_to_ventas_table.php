<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reañade `ventas.cliente` (nombre del cliente en el POS).
 *
 * La columna la introdujo la migración de finanzas (add_finance_fields_to_ventas)
 * y drop_finance_module la conservaba, pero en el entorno de Supabase/Postgres la
 * columna nunca quedó (esa migración creaba además FKs a tablas de finanzas que ya
 * no existen, y quedó parcialmente aplicada). PosController::store la inserta desde
 * el campo "Cliente" del punto de venta, por lo que sin ella toda venta falla con
 * SQLSTATE[42703] undefined column "cliente". Idempotente: solo la agrega si falta.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ventas', 'cliente')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->string('cliente')->nullable()->after('referencia');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ventas', 'cliente')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->dropColumn('cliente');
            });
        }
    }
};
