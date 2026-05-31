<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activos_fijos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('categoria', 50)->nullable();
            $table->foreignId('cuenta_contable_id')->nullable()
                  ->constrained('cuentas_contables')->nullOnDelete();
            $table->foreignId('proveedor_id')->nullable()
                  ->constrained('proveedores')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('costo_adquisicion', 16, 4);
            $table->decimal('valor_residual', 16, 4)->default(0);
            $table->date('fecha_adquisicion');
            $table->integer('vida_util_meses');
            $table->enum('metodo_depreciacion', ['lineal', 'acelerado'])->default('lineal');
            $table->enum('estado', ['activo', 'vendido', 'dado_de_baja'])->default('activo');
            $table->string('numero_serie', 100)->nullable();
            $table->string('ubicacion', 200)->nullable();
            $table->text('notas')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['estado']);
            $table->index(['categoria']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activos_fijos');
    }
};
