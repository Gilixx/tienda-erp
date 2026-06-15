<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permisos de acceso a almacenes: usuarios a los que el dueño (creador)
 * les concedió acceso completo a un almacén.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('almacen_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('almacen_id')->constrained('almacenes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['almacen_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('almacen_user');
    }
};
