<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migración de datos: crea el almacén "Principal" y
 * migra el stock actual de cada producto a product_stock.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Crear almacén principal
        $almacenId = DB::table('almacenes')->insertGetId([
            'nombre'       => 'Almacén Principal',
            'codigo'       => 'PRINCIPAL',
            'descripcion'  => 'Almacén principal del sistema (migrado automáticamente)',
            'es_principal' => true,
            'activo'       => true,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // Migrar stock de cada producto a product_stock
        $products = DB::table('products')->select('id', 'stock')->get();

        foreach ($products as $product) {
            DB::table('product_stock')->insert([
                'product_id' => $product->id,
                'almacen_id' => $almacenId,
                'cantidad'   => $product->stock,
                'updated_at' => now(),
            ]);
        }

        // Asignar almacén principal a todos los movimientos históricos
        DB::table('inventory_movements')
            ->whereNull('almacen_id')
            ->update(['almacen_id' => $almacenId]);
    }

    public function down(): void
    {
        // Obtener almacén principal
        $almacen = DB::table('almacenes')->where('es_principal', true)->first();

        if ($almacen) {
            DB::table('product_stock')->where('almacen_id', $almacen->id)->delete();
            DB::table('inventory_movements')
                ->where('almacen_id', $almacen->id)
                ->update(['almacen_id' => null]);
            DB::table('almacenes')->where('id', $almacen->id)->delete();
        }
    }
};
