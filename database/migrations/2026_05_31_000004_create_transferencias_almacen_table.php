<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transferencias_almacen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('almacen_origen_id')->constrained('almacenes');
            $table->foreignId('almacen_destino_id')->constrained('almacenes');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('estado', ['borrador', 'en_transito', 'recibida', 'cancelada'])->default('borrador');
            $table->date('fecha');
            $table->string('referencia', 100)->nullable();
            $table->text('notas')->nullable();
            $table->timestamp('fecha_recepcion')->nullable();
            $table->foreignId('recibido_por')->nullable()->constrained('users');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['estado', 'fecha']);
            $table->index(['almacen_origen_id']);
            $table->index(['almacen_destino_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transferencias_almacen');
    }
};
