<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina las funciones de inventario físico y lotes/FEFO del módulo de
 * inventario. El seguimiento por serie se conserva vía `products.requiere_serie`
 * y las notas de `inventory_movements`.
 *
 * Migración destructiva e irreversible: down() es intencionalmente un no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['movement_lote', 'lotes', 'inventario_fisico_items', 'inventario_fisico'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Migración destructiva: no hay reversa.
    }
};
