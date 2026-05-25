<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('rfc', 20)->nullable()->index();
            $table->string('contacto')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('direccion')->nullable();
            $table->foreignId('moneda_id')->nullable()->constrained('monedas')->nullOnDelete();
            $table->unsignedSmallInteger('dias_credito')->default(0);
            $table->text('notas')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
