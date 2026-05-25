<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->enum('forma_pago', ['contado', 'credito'])->default('contado')->after('total');
            $table->foreignId('cuenta_id')->nullable()->after('forma_pago')->constrained('cuentas')->nullOnDelete();
            $table->foreignId('moneda_id')->nullable()->after('cuenta_id')->constrained('monedas')->nullOnDelete();
            $table->decimal('tipo_cambio', 18, 8)->default(1)->after('moneda_id');
            $table->date('fecha_vencimiento')->nullable()->after('fecha');
            $table->string('cliente')->nullable()->after('referencia');
            $table->string('cliente_rfc', 20)->nullable()->after('cliente');
        });

        $cuentaId = DB::table('cuentas')->orderBy('id')->value('id');
        $monedaId = DB::table('monedas')->where('es_base', true)->value('id')
            ?? DB::table('monedas')->orderBy('id')->value('id');

        if ($cuentaId && $monedaId) {
            DB::table('ventas')->whereNull('cuenta_id')->update([
                'cuenta_id' => $cuentaId,
                'moneda_id' => $monedaId,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropForeign(['cuenta_id']);
            $table->dropForeign(['moneda_id']);
            $table->dropColumn([
                'forma_pago', 'cuenta_id', 'moneda_id', 'tipo_cambio',
                'fecha_vencimiento', 'cliente', 'cliente_rfc',
            ]);
        });
    }
};
