<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conciliaciones_bancarias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_id')->constrained('cuentas');
            $table->foreignId('user_id')->constrained('users');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->decimal('saldo_banco_statement', 16, 4)->default(0);
            $table->decimal('saldo_sistema', 16, 4)->default(0);
            $table->decimal('diferencia', 16, 4)->default(0);
            $table->enum('estado', ['abierta', 'cerrada'])->default('abierta');
            $table->text('notas')->nullable();
            $table->timestamp('cerrada_en')->nullable();
            $table->foreignId('cerrada_por')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['cuenta_id', 'estado']);
            $table->index(['fecha_inicio', 'fecha_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conciliaciones_bancarias');
    }
};
