<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodos_contables', function (Blueprint $table) {
            $table->id();
            $table->year('anio');
            $table->tinyInteger('mes'); // 1-12
            $table->enum('estado', ['abierto', 'cerrado'])->default('abierto');
            $table->foreignId('cerrado_por')->nullable()->constrained('users');
            $table->timestamp('cerrado_en')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->unique(['anio', 'mes']);
            $table->index(['estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodos_contables');
    }
};
