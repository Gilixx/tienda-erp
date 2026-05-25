<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->enum('tipo', ['caja', 'banco', 'tarjeta_credito', 'tarjeta_debito', 'credito', 'otro'])->default('caja');
            $table->foreignId('moneda_id')->constrained('monedas')->restrictOnDelete();
            $table->decimal('saldo_inicial', 16, 4)->default(0);
            $table->decimal('saldo_actual', 16, 4)->default(0);
            $table->string('banco')->nullable();
            $table->string('numero_cuenta')->nullable();
            $table->decimal('limite_credito', 16, 4)->nullable();
            $table->text('notas')->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->index(['tipo', 'activa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas');
    }
};
