<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Prepara el módulo POS:
 *  - Añade `metodo_pago` y `almacen_id` a la tabla `ventas`.
 *  - Registra el servicio `pos` en el catálogo y lo concede a los admins.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            if (! Schema::hasColumn('ventas', 'metodo_pago')) {
                $table->enum('metodo_pago', ['efectivo', 'tarjeta', 'transferencia'])
                    ->default('efectivo')->after('total');
            }
            if (! Schema::hasColumn('ventas', 'almacen_id')) {
                $table->foreignId('almacen_id')->nullable()->after('user_id')
                    ->constrained('almacenes')->nullOnDelete();
            }
        });

        // Registrar servicio POS
        $posId = DB::table('services')->where('key', 'pos')->value('id');
        if (! $posId) {
            $posId = DB::table('services')->insertGetId([
                'key' => 'pos',
                'name' => 'Punto de Venta',
                'description' => 'Terminal de ventas con escáner, carrito, cobro y reportes de ventas.',
                'icon' => 'pos',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Conceder POS a todos los administradores existentes
        $adminIds = DB::table('users')->where('role', 'admin')->pluck('id');
        foreach ($adminIds as $uid) {
            $exists = DB::table('user_service')
                ->where('user_id', $uid)->where('service_id', $posId)->exists();
            if (! $exists) {
                DB::table('user_service')->insert([
                    'user_id' => $uid,
                    'service_id' => $posId,
                    'granted_at' => now(),
                    'expires_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            if (Schema::hasColumn('ventas', 'almacen_id')) {
                try {
                    $table->dropForeign(['almacen_id']);
                } catch (\Throwable $e) {
                    // ignorar
                }
                $table->dropColumn('almacen_id');
            }
            if (Schema::hasColumn('ventas', 'metodo_pago')) {
                $table->dropColumn('metodo_pago');
            }
        });

        $posId = DB::table('services')->where('key', 'pos')->value('id');
        if ($posId) {
            DB::table('user_service')->where('service_id', $posId)->delete();
            DB::table('services')->where('id', $posId)->delete();
        }
    }
};
