<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas_contables', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('nombre');
            $table->enum('tipo', ['activo', 'pasivo', 'capital', 'ingreso', 'egreso', 'costo']);
            $table->string('subtipo', 50)->nullable();
            $table->tinyInteger('nivel')->default(1); // 1-5
            $table->foreignId('parent_id')->nullable()->constrained('cuentas_contables')->nullOnDelete();
            $table->boolean('es_auxiliar')->default(true); // false = solo agrupadora
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->index(['codigo']);
            $table->index(['tipo', 'activa']);
            $table->index(['parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas_contables');
    }
};
