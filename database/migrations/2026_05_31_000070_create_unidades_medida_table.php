<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unidades_medida', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50);
            $table->string('simbolo', 10);
            $table->enum('tipo', ['masa', 'volumen', 'longitud', 'pieza', 'tiempo', 'otro'])
                  ->default('pieza');
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->unique(['simbolo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unidades_medida');
    }
};
