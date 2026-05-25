<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('moneda_id')->constrained('monedas')->restrictOnDelete();
            $table->decimal('tipo_cambio', 18, 8)->default(1);
            $table->decimal('subtotal', 16, 4)->default(0);
            $table->decimal('impuestos', 16, 4)->default(0);
            $table->decimal('total', 16, 4)->default(0);
            $table->enum('estado', ['borrador', 'recibida', 'pagada', 'cancelada'])->default('borrador');
            $table->enum('forma_pago', ['contado', 'credito'])->default('contado');
            $table->date('fecha')->index();
            $table->date('fecha_vencimiento')->nullable();
            $table->string('referencia')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
