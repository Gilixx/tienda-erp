<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('description')
                ->constrained('users')->nullOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('is_active')
                ->constrained('users')->nullOnDelete();
        });

        // El SKU deja de ser único global y pasa a ser único por dueño.
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_sku_unique');
            $table->unique(['created_by', 'sku'], 'products_owner_sku_unique');
        });

        // Asignar todo el catálogo existente al admin principal.
        $adminId = DB::table('users')
            ->where('role', 'admin')
            ->orderBy('id')
            ->value('id');

        if ($adminId) {
            DB::table('categories')->whereNull('created_by')->update(['created_by' => $adminId]);
            DB::table('products')->whereNull('created_by')->update(['created_by' => $adminId]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_owner_sku_unique');
            $table->unique('sku', 'products_sku_unique');
            $table->dropConstrainedForeignId('created_by');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
