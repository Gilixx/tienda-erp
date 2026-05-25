<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transacciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_id')->constrained('cuentas')->restrictOnDelete();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias_finanzas')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('tipo', ['ingreso', 'egreso', 'transferencia']);
            $table->foreignId('moneda_id')->constrained('monedas')->restrictOnDelete();
            $table->decimal('tipo_cambio', 18, 8)->default(1);
            $table->decimal('subtotal', 16, 4)->default(0);
            $table->decimal('impuestos', 16, 4)->default(0);
            $table->decimal('retenciones', 16, 4)->default(0);
            $table->decimal('total', 16, 4);
            $table->decimal('total_base', 16, 4)->comment('Total convertido a moneda base');
            $table->date('fecha')->index();
            $table->string('descripcion')->nullable();
            $table->string('referencia')->nullable();
            // Relaciones polimórficas suaves con otros módulos
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
            $table->foreignId('compra_id')->nullable()->constrained('compras')->nullOnDelete();
            $table->foreignId('cuenta_destino_id')->nullable()->constrained('cuentas')->nullOnDelete()->comment('Para transferencias');
            $table->enum('estado', ['pendiente', 'conciliada', 'cancelada'])->default('conciliada');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['tipo', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transacciones');
    }
};
