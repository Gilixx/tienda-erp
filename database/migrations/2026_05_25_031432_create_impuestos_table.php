<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('impuestos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('nombre');
            $table->enum('tipo', ['iva', 'ieps', 'isr', 'retencion', 'otro'])->default('iva');
            $table->decimal('tasa', 8, 4)->default(0);
            $table->enum('aplicacion', ['traslado', 'retencion'])->default('traslado');
            $table->boolean('incluido_en_precio')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impuestos');
    }
};
