<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            // Tipo de tercero para DIOT: 04=Nacional, 05=Extranjero, 15=Global
            $table->string('tipo_tercero', 2)->default('04')->after('rfc');
        });
    }

    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn('tipo_tercero');
        });
    }
};
