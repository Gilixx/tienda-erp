<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            // Actor: quién realizó la acción (nullable para no perder el log si se borra el admin)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 60);            // ej. user.created, service.granted
            $table->string('target_type')->nullable(); // ej. App\Models\User
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('description');           // descripción legible
            $table->json('metadata')->nullable();    // datos extra (antes/después, etc.)
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('action');
            $table->index(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
