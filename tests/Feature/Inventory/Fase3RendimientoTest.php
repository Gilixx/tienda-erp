<?php

namespace Tests\Feature\Inventory;

use App\Models\Inventory\AlertaStock;
use App\Models\Inventory\Almacen;
use App\Models\Inventory\ProductStock;
use App\Models\Product;
use App\Models\User;
use App\Services\Inventory\VerificarStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Regresiones de rendimiento (Fase 3). */
class Fase3RendimientoTest extends TestCase
{
    use RefreshDatabase;

    /** VerificarStock usa un número de queries constante, no proporcional a N productos. */
    public function test_verificar_stock_es_set_based(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $alm = Almacen::create(['nombre' => 'A', 'codigo' => 'A1', 'activo' => true, 'created_by' => $admin->id]);

        // 15 productos, todos por debajo del mínimo.
        for ($i = 1; $i <= 15; $i++) {
            $p = Product::create([
                'name' => "P{$i}", 'sku' => "SKU-{$i}", 'price' => 10, 'cost' => 5,
                'stock' => 0, 'min_stock' => 5, 'is_active' => true, 'created_by' => $admin->id,
            ]);
            ProductStock::create(['product_id' => $p->id, 'almacen_id' => $alm->id, 'cantidad' => 1]);
        }

        DB::enableQueryLog();
        $r = app(VerificarStockService::class)->verificar($alm->id);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(15, $r['creadas']);
        $this->assertSame(15, AlertaStock::where('estado', 'activa')->count());
        // Debe rondar 3 (stocks + activas + insert), muy por debajo de 15.
        $this->assertLessThanOrEqual(5, $queries, "Se esperaban pocas queries, hubo {$queries}");
    }

    /** El import CSV es por lotes: upsert + una sincronización, no ~7 queries por fila. */
    public function test_import_csv_por_lotes(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $alm = Almacen::create(['nombre' => 'A', 'codigo' => 'A1', 'activo' => true, 'created_by' => $admin->id]);

        // Producto existente que el CSV va a actualizar.
        Product::create([
            'name' => 'Viejo', 'sku' => 'EX-1', 'price' => 1, 'cost' => 1,
            'stock' => 0, 'min_stock' => 5, 'is_active' => true, 'created_by' => $admin->id,
        ]);

        $lineas = ['sku,nombre,categoria,precio,costo,stock,stock_minimo,descripcion'];
        $lineas[] = 'EX-1,Actualizado,Bebidas,25,10,8,5,desc';
        for ($i = 1; $i <= 19; $i++) {
            $lineas[] = "NEW-{$i},Producto {$i},Bebidas,20,10,3,5,desc";
        }
        $csv = implode("\n", $lineas)."\n";
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('catalogo.csv', $csv);

        DB::enableQueryLog();
        $resp = $this->actingAs($admin)->post('/api/inventory/products-import', [
            'almacen_id' => $alm->id, 'file' => $file,
        ]);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $resp->assertOk()->assertJsonFragment(['created' => 19, 'updated' => 1]);

        // Categoría creada una sola vez en el almacén.
        $this->assertSame(1, \App\Models\Category::where('almacen_id', $alm->id)->where('name', 'Bebidas')->count());

        // Stock por almacén y total global sincronizados.
        $nuevo = Product::where('sku', 'NEW-1')->first();
        $this->assertSame(3, (int) $nuevo->stock);
        $this->assertDatabaseHas('product_stock', ['product_id' => $nuevo->id, 'almacen_id' => $alm->id, 'cantidad' => 3]);
        $this->assertSame(8, (int) Product::where('sku', 'EX-1')->value('stock'));

        // 20 filas: con batching debe estar muy por debajo de ~7/fila (140+).
        $this->assertLessThan(40, $queries, "Import no batcheado: {$queries} queries");
    }
}
