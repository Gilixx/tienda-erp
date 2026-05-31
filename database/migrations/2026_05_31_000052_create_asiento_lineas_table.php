<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asiento_lineas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asiento_id')
                  ->constrained('asientos_contables')
                  ->cascadeOnDelete();
            $table->foreignId('cuenta_contable_id')
                  ->constrained('cuentas_contables');
            $table->string('descripcion')->nullable();
            $table->decimal('cargo', 16, 4)->default(0);
            $table->decimal('abono', 16, 4)->default(0);
            $table->timestamps();

            $table->index(['asiento_id']);
            $table->index(['cuenta_contable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asiento_lineas');
    }
};
