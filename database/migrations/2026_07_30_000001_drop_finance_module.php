<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina por completo el módulo de finanzas: desacopla la tabla `ventas`
 * (que ahora pertenece al POS), elimina las tablas financieras y quita el
 * servicio `finance` del catálogo y de la pivote de accesos.
 *
 * Migración destructiva e irreversible: down() es intencionalmente un no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Desacoplar `ventas` de finanzas (FKs + columnas financieras).
        if (Schema::hasTable('ventas')) {
            Schema::table('ventas', function (Blueprint $table) {
                foreach (['cuenta_id', 'moneda_id'] as $col) {
                    if (Schema::hasColumn('ventas', $col)) {
                        try {
                            $table->dropForeign([$col]);
                        } catch (\Throwable $e) {
                            // FK inexistente o driver sin soporte: ignorar.
                        }
                    }
                }

                $drop = array_values(array_filter(
                    ['forma_pago', 'cuenta_id', 'moneda_id', 'tipo_cambio', 'fecha_vencimiento', 'cliente_rfc'],
                    fn ($c) => Schema::hasColumn('ventas', $c)
                ));

                if ($drop) {
                    $table->dropColumn($drop);
                }
            });
        }

        // 2) Eliminar todas las tablas financieras (orden indiferente: FKs off).
        $tables = [
            'transaccion_impuesto',
            'transacciones',
            'compra_items',
            'compras',
            'devolucion_compra_items',
            'devolucion_compras',
            'devoluciones_compra',
            'devolucion_venta_items',
            'devolucion_ventas',
            'devoluciones_venta',
            'conciliacion_items',
            'conciliaciones_bancarias',
            'asiento_lineas',
            'asientos_contables',
            'cuentas_contables',
            'cuentas_por_cobrar',
            'cuentas_por_pagar',
            'presupuestos',
            'centros_costo',
            'depreciaciones',
            'activos_fijos',
            'periodos_contables',
            'proveedores',
            'impuestos',
            'categorias_finanzas',
            'categorias_finanza',
            'tipos_cambio',
            'monedas',
            'cuentas',
        ];

        Schema::disableForeignKeyConstraints();
        foreach ($tables as $t) {
            Schema::dropIfExists($t);
        }
        Schema::enableForeignKeyConstraints();

        // 3) Quitar el servicio `finance` del catálogo y de los accesos.
        $financeId = DB::table('services')->where('key', 'finance')->value('id');
        if ($financeId) {
            DB::table('user_service')->where('service_id', $financeId)->delete();
            DB::table('services')->where('id', $financeId)->delete();
        }
    }

    public function down(): void
    {
        // Migración destructiva: no hay reversa.
    }
};
