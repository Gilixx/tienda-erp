<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // El código deja de ser único global y pasa a ser único por dueño,
        // para que dos clientes distintos puedan usar el mismo código.
        Schema::table('almacenes', function (Blueprint $table) {
            $table->dropUnique('almacenes_codigo_unique');
            $table->unique(['created_by', 'codigo'], 'almacenes_owner_codigo_unique');
        });

        // Asignar el almacén Principal (y cualquier almacén huérfano) al admin
        // principal, para que deje de ser visible para todos los usuarios.
        $adminId = DB::table('users')
            ->where('role', 'admin')
            ->orderBy('id')
            ->value('id');

        if ($adminId) {
            DB::table('almacenes')
                ->whereNull('created_by')
                ->update(['created_by' => $adminId]);
        }
    }

    public function down(): void
    {
        Schema::table('almacenes', function (Blueprint $table) {
            $table->dropUnique('almacenes_owner_codigo_unique');
            $table->unique('codigo', 'almacenes_codigo_unique');
        });
    }
};
