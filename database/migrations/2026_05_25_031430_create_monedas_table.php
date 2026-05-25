<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monedas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 3)->unique();
            $table->string('nombre');
            $table->string('simbolo', 5);
            $table->unsignedTinyInteger('decimales')->default(2);
            $table->boolean('es_base')->default(false);
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monedas');
    }
};
