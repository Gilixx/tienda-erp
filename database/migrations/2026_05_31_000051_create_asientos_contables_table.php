<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asientos_contables', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['manual', 'automatico'])->default('manual');
            $table->string('referencia', 100)->nullable();
            $table->string('descripcion');
            $table->date('fecha');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('estado', ['borrador', 'publicado', 'cancelado'])->default('borrador');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['fecha', 'estado']);
            $table->index(['tipo', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asientos_contables');
    }
};
