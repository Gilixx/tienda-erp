<?php

namespace Tests\Feature\Inventory;

use App\Models\Category;
use App\Models\Inventory\Almacen;
use App\Models\Inventory\ProductStock;
use App\Models\Inventory\TransferenciaAlmacen;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriasPorAlmacenTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Almacen $almA;

    private Almacen $almB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->almA = $this->almacen('A', 'ALM-A', true);
        $this->almB = $this->almacen('B', 'ALM-B', false);
    }

    private function almacen(string $nombre, string $codigo, bool $principal): Almacen
    {
        return Almacen::create([
            'nombre' => $nombre,
            'codigo' => $codigo,
            'es_principal' => $principal,
            'activo' => true,
            'created_by' => $this->admin->id,
        ]);
    }

    /** Crea un producto y su fila de stock (con categoría opcional) en un almacén. */
    private function productoEn(Almacen $alm, string $sku, ?Category $cat = null, int $cantidad = 10): Product
    {
        $p = Product::create([
            'name' => "Prod {$sku}",
            'sku' => $sku,
            'price' => 10,
            'cost' => 5,
            'stock' => $cantidad,
            'min_stock' => 1,
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        ProductStock::create([
            'product_id' => $p->id,
            'almacen_id' => $alm->id,
            'cantidad' => $cantidad,
            'category_id' => $cat?->id,
        ]);

        return $p;
    }

    private function categoria(Almacen $alm, string $name): Category
    {
        return Category::create([
            'name' => $name,
            'created_by' => $this->admin->id,
            'almacen_id' => $alm->id,
        ]);
    }

    public function test_index_de_categorias_requiere_almacen_y_filtra_por_el(): void
    {
        $this->categoria($this->almA, 'Bebidas');
        $this->categoria($this->almB, 'Abarrotes');

        $this->actingAs($this->admin)
            ->getJson('/api/inventory/categories')
            ->assertStatus(422)
            ->assertJsonValidationErrors('almacen_id');

        $this->actingAs($this->admin)
            ->getJson('/api/inventory/categories?almacen_id='.$this->almA->id)
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['name' => 'Bebidas'])
            ->assertJsonMissing(['name' => 'Abarrotes']);
    }

    public function test_no_se_permite_nombre_duplicado_en_el_mismo_almacen(): void
    {
        $this->categoria($this->almA, 'Bebidas');

        // Mismo nombre en otro almacén: permitido.
        $this->actingAs($this->admin)
            ->postJson('/api/inventory/categories', ['almacen_id' => $this->almB->id, 'name' => 'Bebidas'])
            ->assertCreated();

        // Mismo nombre en el mismo almacén: rechazado.
        $this->actingAs($this->admin)
            ->postJson('/api/inventory/categories', ['almacen_id' => $this->almA->id, 'name' => 'Bebidas'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_asignar_categoria_solo_afecta_el_almacen_de_la_categoria(): void
    {
        $catA = $this->categoria($this->almA, 'Bebidas');
        $prod = $this->productoEn($this->almA, 'SKU-1');
        // El mismo producto existe también en B (sin categoría).
        ProductStock::create(['product_id' => $prod->id, 'almacen_id' => $this->almB->id, 'cantidad' => 5]);

        $this->actingAs($this->admin)
            ->postJson("/api/inventory/categories/{$catA->id}/productos", ['product_ids' => [$prod->id]])
            ->assertOk()
            ->assertJsonFragment(['asignados' => 1]);

        $this->assertDatabaseHas('product_stock', [
            'product_id' => $prod->id, 'almacen_id' => $this->almA->id, 'category_id' => $catA->id,
        ]);
        // B queda intacto (general).
        $this->assertDatabaseHas('product_stock', [
            'product_id' => $prod->id, 'almacen_id' => $this->almB->id, 'category_id' => null,
        ]);
    }

    public function test_quitar_producto_lo_deja_en_general(): void
    {
        $catA = $this->categoria($this->almA, 'Bebidas');
        $prod = $this->productoEn($this->almA, 'SKU-1', $catA);

        $this->actingAs($this->admin)
            ->deleteJson("/api/inventory/categories/{$catA->id}/productos/{$prod->id}")
            ->assertOk();

        $this->assertDatabaseHas('product_stock', [
            'product_id' => $prod->id, 'almacen_id' => $this->almA->id, 'category_id' => null,
        ]);
    }

    public function test_crear_producto_guarda_categoria_por_almacen(): void
    {
        $catA = $this->categoria($this->almA, 'Bebidas');

        $this->actingAs($this->admin)
            ->postJson('/api/inventory/products', [
                'almacen_id' => $this->almA->id,
                'category_id' => $catA->id,
                'name' => 'Refresco',
                'sku' => 'REF-1',
                'price' => 20,
                'cost' => 12,
                'min_stock' => 2,
                'stock_inicial' => 8,
            ])
            ->assertCreated();

        $prod = Product::where('sku', 'REF-1')->first();
        $this->assertDatabaseHas('product_stock', [
            'product_id' => $prod->id, 'almacen_id' => $this->almA->id, 'category_id' => $catA->id,
        ]);
    }

    public function test_transferencia_crear_categoria_en_destino(): void
    {
        $catA = $this->categoria($this->almA, 'Bebidas');
        $prod = $this->productoEn($this->almA, 'SKU-1', $catA);
        $transf = $this->transferenciaEnTransito($prod);

        $this->actingAs($this->admin)
            ->postJson("/api/inventory/transferencias/{$transf->id}/recibir", [
                'items' => [[
                    'transferencia_item_id' => $transf->items->first()->id,
                    'cantidad_recibida' => 10,
                    'categoria_accion' => 'crear',
                ]],
            ])
            ->assertOk();

        $catB = Category::where('almacen_id', $this->almB->id)->where('name', 'Bebidas')->first();
        $this->assertNotNull($catB, 'Debió crearse la categoría Bebidas en el destino.');
        $this->assertDatabaseHas('product_stock', [
            'product_id' => $prod->id, 'almacen_id' => $this->almB->id, 'category_id' => $catB->id,
        ]);
    }

    public function test_transferencia_asignar_categoria_existente_del_destino(): void
    {
        $catA = $this->categoria($this->almA, 'Bebidas');
        $catB = $this->categoria($this->almB, 'Bebidas');
        $prod = $this->productoEn($this->almA, 'SKU-1', $catA);
        $transf = $this->transferenciaEnTransito($prod);

        // El preview debe detectar la coincidencia por nombre en el destino.
        $this->actingAs($this->admin)
            ->getJson("/api/inventory/transferencias/{$transf->id}/preview-categorias")
            ->assertOk()
            ->assertJsonPath('items.0.categoria_destino_match.id', $catB->id);

        $this->actingAs($this->admin)
            ->postJson("/api/inventory/transferencias/{$transf->id}/recibir", [
                'items' => [[
                    'transferencia_item_id' => $transf->items->first()->id,
                    'cantidad_recibida' => 10,
                    'categoria_accion' => 'asignar',
                    'categoria_destino_id' => $catB->id,
                ]],
            ])
            ->assertOk();

        $this->assertDatabaseHas('product_stock', [
            'product_id' => $prod->id, 'almacen_id' => $this->almB->id, 'category_id' => $catB->id,
        ]);
        // No se duplicó la categoría en el destino.
        $this->assertEquals(1, Category::where('almacen_id', $this->almB->id)->where('name', 'Bebidas')->count());
    }

    public function test_transferencia_general_deja_sin_categoria(): void
    {
        $catA = $this->categoria($this->almA, 'Bebidas');
        $prod = $this->productoEn($this->almA, 'SKU-1', $catA);
        $transf = $this->transferenciaEnTransito($prod);

        $this->actingAs($this->admin)
            ->postJson("/api/inventory/transferencias/{$transf->id}/recibir", [
                'items' => [[
                    'transferencia_item_id' => $transf->items->first()->id,
                    'cantidad_recibida' => 10,
                    'categoria_accion' => 'general',
                ]],
            ])
            ->assertOk();

        $this->assertDatabaseHas('product_stock', [
            'product_id' => $prod->id, 'almacen_id' => $this->almB->id, 'category_id' => null,
        ]);
        $this->assertEquals(0, Category::where('almacen_id', $this->almB->id)->count());
    }

    public function test_usuario_con_acceso_compartido_ve_categorias_aunque_no_las_creo(): void
    {
        // El almacén y la categoría los crea el dueño (otro usuario).
        $owner = User::factory()->create(['role' => 'user']);
        $almacen = Almacen::create([
            'nombre' => 'Compartido', 'codigo' => 'CMP-1',
            'es_principal' => false, 'activo' => true, 'created_by' => $owner->id,
        ]);
        Category::create(['name' => 'Lacteos', 'created_by' => $owner->id, 'almacen_id' => $almacen->id]);

        // Usuario invitado: servicio inventory + acceso compartido al almacén.
        $invitado = $this->usuarioConInventario();
        $almacen->usuariosConAcceso()->attach($invitado->id, ['granted_by' => $owner->id]);

        $this->actingAs($invitado)
            ->getJson('/api/inventory/categories?almacen_id='.$almacen->id)
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['name' => 'Lacteos']);
    }

    public function test_usuario_sin_acceso_al_almacen_no_ve_sus_categorias(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $almacen = Almacen::create([
            'nombre' => 'Privado', 'codigo' => 'PRV-1',
            'es_principal' => false, 'activo' => true, 'created_by' => $owner->id,
        ]);
        Category::create(['name' => 'Lacteos', 'created_by' => $owner->id, 'almacen_id' => $almacen->id]);

        $extrano = $this->usuarioConInventario();

        $this->actingAs($extrano)
            ->getJson('/api/inventory/categories?almacen_id='.$almacen->id)
            ->assertStatus(403);
    }

    /** Usuario no-admin con el servicio de inventario concedido. */
    private function usuarioConInventario(): User
    {
        $user = User::factory()->create(['role' => 'user', 'is_active' => true]);
        $inventory = Service::firstOrCreate(['key' => 'inventory'], ['name' => 'Inventario']);
        $user->services()->attach($inventory->id, ['granted_at' => now()]);

        return $user;
    }

    /** Crea una transferencia A→B en tránsito con un ítem del producto dado. */
    private function transferenciaEnTransito(Product $prod): TransferenciaAlmacen
    {
        $t = TransferenciaAlmacen::create([
            'almacen_origen_id' => $this->almA->id,
            'almacen_destino_id' => $this->almB->id,
            'user_id' => $this->admin->id,
            'estado' => 'en_transito',
            'fecha' => now()->toDateString(),
        ]);
        $t->items()->create(['product_id' => $prod->id, 'cantidad' => 10]);

        return $t->load('items');
    }
}
