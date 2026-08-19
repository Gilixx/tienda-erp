<?php

namespace Tests\Feature\Seguridad;

use App\Models\Inventory\Almacen;
use App\Models\Inventory\ProductStock;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Regresiones de la Fase 2 (endurecimiento de seguridad MED/LOW). */
class Fase2EndurecimientoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Service::firstOrCreate(['key' => 'inventory'], ['name' => 'Inventario']);
    }

    /** El export CSV neutraliza fórmulas en campos controlados por el usuario. */
    public function test_export_csv_neutraliza_inyeccion_de_formulas(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        User::factory()->create(['role' => 'user', 'is_active' => true, 'name' => '=cmd|calc!A1']);

        $csv = $this->actingAs($admin)->get('/api/admin/export/users')
            ->assertOk()
            ->streamedContent();

        // La celda peligrosa queda escapada con apóstrofo y no como fórmula cruda.
        $this->assertStringContainsString("'=cmd|calc!A1", $csv);
        $this->assertStringNotContainsString(',=cmd|calc!A1', $csv);
    }

    /** show de producto no revela movimientos de almacenes no accesibles. */
    public function test_show_no_filtra_movimientos_de_otros_almacenes(): void
    {
        $duenoA = $this->usuarioInventory();
        $duenoB = $this->usuarioInventory();
        $almA = Almacen::create(['nombre' => 'A', 'codigo' => 'A1', 'activo' => true, 'created_by' => $duenoA->id]);
        $almB = Almacen::create(['nombre' => 'B', 'codigo' => 'B1', 'activo' => true, 'created_by' => $duenoB->id]);

        $p = Product::create([
            'name' => 'Compartido', 'sku' => 'SH-1', 'price' => 10, 'cost' => 5,
            'stock' => 0, 'min_stock' => 1, 'is_active' => true, 'created_by' => $duenoA->id,
        ]);
        ProductStock::create(['product_id' => $p->id, 'almacen_id' => $almA->id, 'cantidad' => 5]);
        ProductStock::create(['product_id' => $p->id, 'almacen_id' => $almB->id, 'cantidad' => 5]);

        $movA = InventoryMovement::create(['product_id' => $p->id, 'almacen_id' => $almA->id, 'user_id' => $duenoA->id, 'type' => 'in', 'quantity' => 5, 'reference' => 'SECRETO-A']);
        $movB = InventoryMovement::create(['product_id' => $p->id, 'almacen_id' => $almB->id, 'user_id' => $duenoB->id, 'type' => 'in', 'quantity' => 5, 'reference' => 'VISIBLE-B']);

        // duenoB solo tiene acceso a almB.
        $resp = $this->actingAs($duenoB)->getJson("/api/inventory/products/{$p->id}")->assertOk();

        $ids = collect($resp->json('movements'))->pluck('id');
        $this->assertTrue($ids->contains($movB->id));
        $this->assertFalse($ids->contains($movA->id), 'No debe exponer movimientos del almacén A');
    }

    /** Login: contraseña incorrecta devuelve el mensaje genérico aunque la cuenta exista/esté inactiva. */
    public function test_login_no_permite_enumerar_cuentas(): void
    {
        User::factory()->create([
            'email' => 'inactivo@crmac.com', 'is_active' => false,
            'password' => bcrypt('Secreto123!'),
        ]);

        $resp = $this->from('/login')->post('/login', [
            'email' => 'inactivo@crmac.com', 'password' => 'contraseña-incorrecta',
        ]);

        $resp->assertRedirect('/login');
        $errors = session('errors')->get('email');
        $this->assertStringContainsString('credenciales no coinciden', $errors[0]);
        $this->assertStringNotContainsString('desactivada', $errors[0]);
        $this->assertGuest();
    }

    /** Con credenciales correctas pero cuenta inactiva: no autentica y avisa desactivación. */
    public function test_login_cuenta_inactiva_no_autentica(): void
    {
        User::factory()->create([
            'email' => 'inactivo2@crmac.com', 'is_active' => false,
            'password' => bcrypt('Secreto123!'),
        ]);

        $this->from('/login')->post('/login', [
            'email' => 'inactivo2@crmac.com', 'password' => 'Secreto123!',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /** Las respuestas de la API llevan los headers de seguridad. */
    public function test_la_api_incluye_headers_de_seguridad(): void
    {
        $u = User::factory()->create(['is_active' => true]);

        $this->actingAs($u)->getJson('/api/ping')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY');
    }

    private function usuarioInventory(): User
    {
        $u = User::factory()->create(['role' => 'user', 'is_active' => true]);
        $u->services()->attach(Service::where('key', 'inventory')->value('id'), ['granted_at' => now()]);

        return $u;
    }
}
