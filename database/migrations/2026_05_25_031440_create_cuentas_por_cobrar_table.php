<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas_por_cobrar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
            $table->string('cliente');
            $table->string('cliente_rfc', 20)->nullable();
            $table->foreignId('moneda_id')->constrained('monedas')->restrictOnDelete();
            $table->decimal('tipo_cambio', 18, 8)->default(1);
            $table->decimal('monto_total', 16, 4);
            $table->decimal('monto_pagado', 16, 4)->default(0);
            $table->decimal('saldo', 16, 4);
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento')->index();
            $table->enum('estado', ['pendiente', 'parcial', 'pagada', 'vencida', 'cancelada'])->default('pendiente');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['estado', 'fecha_vencimiento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas_por_cobrar');
    }
};
