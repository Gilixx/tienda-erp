<?php

namespace Tests\Feature\Inventory;

use App\Models\Inventory\AlertaStock;
use App\Models\Inventory\Almacen;
use App\Models\Inventory\ProductStock;
use App\Models\Inventory\TransferenciaAlmacen;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Models\VentaItem;
use App\Services\Inventory\VerificarStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresiones de la Fase 1 (integridad de stock + seguridad HIGH).
 * Cada test corresponde a un bug verificado y corregido.
 */
class Fase1SeguridadIntegridadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Service::firstOrCreate(['key' => 'inventory'], ['name' => 'Inventario']);
        Service::firstOrCreate(['key' => 'pos'], ['name' => 'POS']);
        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function almacen(string $codigo, User $owner): Almacen
    {
        return Almacen::create([
            'nombre' => "Almacén {$codigo}", 'codigo' => $codigo,
            'es_principal' => false, 'activo' => true, 'created_by' => $owner->id,
        ]);
    }

    private function producto(User $owner, string $sku, float $price = 10, int $min = 1, int $reorden = 0): Product
    {
        return Product::create([
            'name' => "Prod {$sku}", 'sku' => $sku, 'price' => $price, 'cost' => 5,
            'stock' => 0, 'min_stock' => $min, 'punto_reorden' => $reorden,
            'is_active' => true, 'created_by' => $owner->id,
        ]);
    }

    private function stock(Product $p, Almacen $a, int $cantidad): ProductStock
    {
        $p->increment('stock', $cantidad);

        return ProductStock::create(['product_id' => $p->id, 'almacen_id' => $a->id, 'cantidad' => $cantidad]);
    }

    private function usuarioCon(array $servicios, bool $force = false): User
    {
        $u = User::factory()->create(['role' => 'user', 'is_active' => true, 'force_password_change' => $force]);
        foreach ($servicios as $key) {
            $u->services()->attach(Service::where('key', $key)->value('id'), ['granted_at' => now()]);
        }

        return $u;
    }

    /** enviar: si un ítem posterior no tiene stock, los previos NO deben descontarse. */
    public function test_enviar_no_descuenta_items_previos_si_uno_falla(): void
    {
        $origen = $this->almacen('ORI', $this->admin);
        $destino = $this->almacen('DES', $this->admin);
        $p1 = $this->producto($this->admin, 'P1');
        $p2 = $this->producto($this->admin, 'P2');
        $this->stock($p1, $origen, 5);
        $this->stock($p2, $origen, 1); // insuficiente para 5

        $transf = TransferenciaAlmacen::create([
            'almacen_origen_id' => $origen->id, 'almacen_destino_id' => $destino->id,
            'user_id' => $this->admin->id, 'estado' => 'borrador', 'fecha' => now(),
        ]);
        $transf->items()->create(['product_id' => $p1->id, 'cantidad' => 5]);
        $transf->items()->create(['product_id' => $p2->id, 'cantidad' => 5]);

        $this->actingAs($this->admin)
            ->postJson("/api/inventory/transferencias/{$transf->id}/enviar")
            ->assertStatus(422);

        // El stock de P1 en el origen sigue intacto (no hubo commit parcial).
        $this->assertDatabaseHas('product_stock', [
            'product_id' => $p1->id, 'almacen_id' => $origen->id, 'cantidad' => 5,
        ]);
        $this->assertDatabaseHas('transferencias_almacen', ['id' => $transf->id, 'estado' => 'borrador']);
    }

    /** recibir: no se puede recibir más de lo enviado. */
    public function test_recibir_rechaza_sobre_recepcion(): void
    {
        $origen = $this->almacen('ORI', $this->admin);
        $destino = $this->almacen('DES', $this->admin);
        $p1 = $this->producto($this->admin, 'P1');
        $this->stock($p1, $origen, 5);

        $transf = TransferenciaAlmacen::create([
            'almacen_origen_id' => $origen->id, 'almacen_destino_id' => $destino->id,
            'user_id' => $this->admin->id, 'estado' => 'borrador', 'fecha' => now(),
        ]);
        $item = $transf->items()->create(['product_id' => $p1->id, 'cantidad' => 5]);

        $this->actingAs($this->admin)->postJson("/api/inventory/transferencias/{$transf->id}/enviar")->assertOk();

        $this->actingAs($this->admin)
            ->postJson("/api/inventory/transferencias/{$transf->id}/recibir", [
                'items' => [['transferencia_item_id' => $item->id, 'cantidad_recibida' => 999]],
            ])
            ->assertStatus(422);

        // El destino no se infló.
        $this->assertDatabaseMissing('product_stock', [
            'product_id' => $p1->id, 'almacen_id' => $destino->id, 'cantidad' => 999,
        ]);
    }

    /** Las alertas se resuelven cuando el stock supera AMBOS umbrales. */
    public function test_alerta_se_resuelve_al_recuperarse_el_stock(): void
    {
        $alm = $this->almacen('A1', $this->admin);
        $p = $this->producto($this->admin, 'P1', 10, min: 5, reorden: 10);
        $row = $this->stock($p, $alm, 2); // por debajo de ambos umbrales

        $svc = app(VerificarStockService::class);
        $svc->verificar($alm->id, [$p->id]);
        $this->assertSame(2, AlertaStock::where('product_id', $p->id)->where('estado', 'activa')->count());

        // Se recupera por encima de min_stock y punto_reorden.
        $row->update(['cantidad' => 20]);
        $p->update(['stock' => 20]);
        $svc->verificar($alm->id, [$p->id]);

        $this->assertSame(0, AlertaStock::where('product_id', $p->id)->where('estado', 'activa')->count());
        $this->assertSame(2, AlertaStock::where('product_id', $p->id)->where('estado', 'resuelta')->count());
    }

    /** IDOR: no se puede crear un movimiento sobre un producto de otro tenant. */
    public function test_movimiento_rechaza_producto_de_otro_tenant(): void
    {
        $tenantA = $this->usuarioCon(['inventory']);
        $tenantB = $this->usuarioCon(['inventory']);
        $almA = $this->almacen('AA', $tenantA);
        $prodB = $this->producto($tenantB, 'B-SECRETO'); // sin stock en almacenes de A

        $this->actingAs($tenantA)
            ->postJson('/api/inventory/movements', [
                'product_id' => $prodB->id, 'almacen_id' => $almA->id, 'type' => 'in', 'quantity' => 1,
            ])
            ->assertStatus(403);

        $this->assertDatabaseMissing('product_stock', [
            'product_id' => $prodB->id, 'almacen_id' => $almA->id,
        ]);
    }

    /** El flag force_password_change bloquea toda la API. */
    public function test_force_password_change_bloquea_la_api(): void
    {
        $u = $this->usuarioCon(['inventory'], force: true);

        $this->actingAs($u)
            ->getJson('/api/inventory/almacenes')
            ->assertStatus(403)
            ->assertJsonFragment(['force_password_change' => true]);
    }

    /** POS: el precio se toma del producto, no del cliente. */
    public function test_pos_usa_precio_del_producto_no_del_cliente(): void
    {
        $u = $this->usuarioCon(['inventory', 'pos']);
        $alm = $this->almacen('POSA', $u);
        $p = $this->producto($u, 'POS-1', price: 100);
        $this->stock($p, $alm, 10);

        $this->actingAs($u)
            ->postJson('/api/pos/ventas', [
                'almacen_id' => $alm->id, 'metodo_pago' => 'efectivo',
                'items' => [['product_id' => $p->id, 'cantidad' => 1, 'precio_unit' => 1]], // precio manipulado
            ])
            ->assertCreated();

        $item = VentaItem::where('product_id', $p->id)->first();
        $this->assertEquals(100.0, (float) $item->precio_unit);
        $this->assertEquals(100.0, (float) $item->subtotal);
    }
}
