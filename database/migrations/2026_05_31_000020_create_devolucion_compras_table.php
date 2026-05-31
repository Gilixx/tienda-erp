<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devolucion_compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras');
            $table->foreignId('proveedor_id')->constrained('proveedores');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('moneda_id')->constrained('monedas');
            $table->foreignId('almacen_id')->nullable()->constrained('almacenes')->nullOnDelete();
            $table->decimal('tipo_cambio', 18, 8)->default(1);
            $table->decimal('subtotal', 16, 4)->default(0);
            $table->decimal('impuestos', 16, 4)->default(0);
            $table->decimal('total', 16, 4)->default(0);
            $table->decimal('total_base', 16, 4)->default(0);
            $table->string('motivo')->nullable();
            $table->enum('estado', ['borrador', 'aplicada', 'cancelada'])->default('borrador');
            $table->date('fecha');
            $table->string('referencia', 100)->nullable();
            $table->text('notas')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['compra_id']);
            $table->index(['proveedor_id']);
            $table->index(['estado', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devolucion_compras');
    }
};
