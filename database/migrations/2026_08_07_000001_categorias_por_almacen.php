<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las categorías dejan de ser globales por dueño y pasan a ser por almacén.
     * La categoría de un producto se guarda por par (producto, almacén) en
     * product_stock.category_id (NULL = "general").
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('almacen_id')->nullable()->after('created_by')
                ->constrained('almacenes')->cascadeOnDelete();
        });

        Schema::table('product_stock', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('almacen_id')
                ->constrained('categories')->nullOnDelete();
            $table->index('category_id');
        });

        // Backfill: llevar la categoría global (products.category_id) a la
        // categoría por almacén en cada fila de product_stock del producto.
        $rows = DB::table('product_stock')
            ->join('products', 'products.id', '=', 'product_stock.product_id')
            ->whereNotNull('products.category_id')
            ->select(
                'product_stock.id as ps_id',
                'product_stock.almacen_id',
                'products.category_id',
                'products.created_by'
            )
            ->get();

        $map = [];     // "catId-almacenId" => nueva categoría por almacén
        $reused = [];  // catId global => almacenId al que se reasignó la fila original

        foreach ($rows as $r) {
            $key = $r->category_id.'-'.$r->almacen_id;

            if (! isset($map[$key])) {
                $orig = DB::table('categories')->find($r->category_id);
                if (! $orig) {
                    continue;
                }

                if (! isset($reused[$r->category_id])) {
                    // Reusar la fila original para el primer almacén donde aparece.
                    DB::table('categories')->where('id', $r->category_id)
                        ->update(['almacen_id' => $r->almacen_id]);
                    $reused[$r->category_id] = $r->almacen_id;
                    $map[$key] = $r->category_id;
                } elseif ($reused[$r->category_id] == $r->almacen_id) {
                    $map[$key] = $r->category_id;
                } else {
                    // Crear copia de la categoría para este otro almacén.
                    $map[$key] = DB::table('categories')->insertGetId([
                        'name' => $orig->name,
                        'description' => $orig->description,
                        'created_by' => $orig->created_by,
                        'almacen_id' => $r->almacen_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::table('product_stock')->where('id', $r->ps_id)
                ->update(['category_id' => $map[$key]]);
        }

        // Categorías sin almacén (vacías / sin productos): asignarlas al almacén
        // principal del dueño para no perderlas. Si el dueño no tiene almacenes,
        // se eliminan (una categoría sin almacén ya no tiene sentido).
        foreach (DB::table('categories')->whereNull('almacen_id')->get() as $o) {
            $almId = DB::table('almacenes')
                ->where('created_by', $o->created_by)
                ->orderByDesc('es_principal')->orderBy('id')
                ->value('id');

            if ($almId) {
                DB::table('categories')->where('id', $o->id)->update(['almacen_id' => $almId]);
            } else {
                DB::table('categories')->where('id', $o->id)->delete();
            }
        }

        // La categoría global del producto ya no se usa.
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('id')
                ->constrained('categories')->nullOnDelete();
        });

        // Restaurar (best-effort) la categoría global desde product_stock.
        $back = DB::table('product_stock')
            ->whereNotNull('category_id')
            ->select('product_id', 'category_id')
            ->get()
            ->unique('product_id');

        foreach ($back as $r) {
            DB::table('products')->where('id', $r->product_id)
                ->update(['category_id' => $r->category_id]);
        }

        Schema::table('product_stock', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('almacen_id');
        });
    }
};
