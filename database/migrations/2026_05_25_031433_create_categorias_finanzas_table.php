<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_finanzas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->enum('tipo', ['ingreso', 'egreso']);
            $table->string('color', 20)->default('#64748b');
            $table->string('icono', 40)->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->index(['tipo', 'activa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_finanzas');
    }
};
