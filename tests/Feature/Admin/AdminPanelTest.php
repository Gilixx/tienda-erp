<?php

namespace Tests\Feature\Admin;

use App\Models\Inventory\Almacen;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function seedServices(): void
    {
        // firstOrCreate: algunas migraciones ya registran 'pos' en el catálogo.
        Service::firstOrCreate(['key' => 'inventory'], ['name' => 'Inventario']);
        Service::firstOrCreate(['key' => 'pos'], ['name' => 'Punto de Venta']);
    }

    private function almacen(): Almacen
    {
        return Almacen::create([
            'nombre' => 'Central',
            'codigo' => 'ALM-01',
            'es_principal' => true,
            'activo' => true,
        ]);
    }

    public function test_usuario_normal_no_accede_al_panel(): void
    {
        $user = User::factory()->create(['role' => 'user', 'is_active' => true]);

        $this->actingAs($user)
            ->getJson('/api/admin/users')
            ->assertStatus(403);
    }

    public function test_admin_lista_usuarios(): void
    {
        $admin = $this->admin();
        User::factory()->count(3)->create(['role' => 'user']);

        $this->actingAs($admin)
            ->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'total']);
    }

    public function test_admin_crea_usuario(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->postJson('/api/admin/users', [
                'name' => 'Nuevo', 'email' => 'nuevo@test.com',
                'password' => 'secret123', 'role' => 'user',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('users', ['email' => 'nuevo@test.com', 'role' => 'user']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.created']);
    }

    public function test_asigna_inventory_luego_pos_con_almacen(): void
    {
        $admin = $this->admin();
        $this->seedServices();
        $alm = $this->almacen();
        $target = User::factory()->create(['role' => 'user']);

        $this->actingAs($admin)
            ->putJson("/api/admin/users/{$target->id}/access", [
                'services' => [
                    ['key' => 'inventory', 'expires_at' => null],
                    ['key' => 'pos', 'expires_at' => null],
                ],
                'almacen_ids' => [$alm->id],
            ])
            ->assertOk();

        $this->assertTrue($target->fresh()->hasService('inventory'));
        $this->assertTrue($target->fresh()->hasService('pos'));
    }

    public function test_pos_sin_inventory_falla(): void
    {
        $admin = $this->admin();
        $this->seedServices();
        $alm = $this->almacen();
        $target = User::factory()->create(['role' => 'user']);

        $this->actingAs($admin)
            ->putJson("/api/admin/users/{$target->id}/access", [
                'services' => [['key' => 'pos', 'expires_at' => null]],
                'almacen_ids' => [$alm->id],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('services');
    }

    public function test_pos_sin_almacen_falla(): void
    {
        $admin = $this->admin();
        $this->seedServices();
        $target = User::factory()->create(['role' => 'user']);

        $this->actingAs($admin)
            ->putJson("/api/admin/users/{$target->id}/access", [
                'services' => [
                    ['key' => 'inventory', 'expires_at' => null],
                    ['key' => 'pos', 'expires_at' => null],
                ],
                'almacen_ids' => [],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('almacenes');
    }

    public function test_revocar_inventory_revoca_pos_en_cascada(): void
    {
        $admin = $this->admin();
        $this->seedServices();
        $alm = $this->almacen();
        $target = User::factory()->create(['role' => 'user']);

        // Concede ambos
        $this->actingAs($admin)->putJson("/api/admin/users/{$target->id}/access", [
            'services' => [
                ['key' => 'inventory', 'expires_at' => null],
                ['key' => 'pos', 'expires_at' => null],
            ],
            'almacen_ids' => [$alm->id],
        ])->assertOk();

        // Revoca todo (la UI habría desmarcado pos al quitar inventory)
        $this->actingAs($admin)->putJson("/api/admin/users/{$target->id}/access", [
            'services' => [],
            'almacen_ids' => [],
        ])->assertOk();

        $this->assertFalse($target->fresh()->hasService('inventory'));
        $this->assertFalse($target->fresh()->hasService('pos'));
    }

    public function test_reset_password_marca_cambio_obligatorio(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create(['role' => 'user']);

        $this->actingAs($admin)
            ->postJson("/api/admin/users/{$target->id}/reset-password", ['password' => 'nueva12345'])
            ->assertOk();

        $target->refresh();
        $this->assertTrue($target->force_password_change);
        $this->assertTrue(Hash::check('nueva12345', $target->password));
    }

    public function test_soft_delete_y_restore(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create(['role' => 'user', 'name' => 'Borrable']);

        $this->actingAs($admin)->deleteJson("/api/admin/users/{$target->id}")->assertOk();
        $this->assertSoftDeleted('users', ['id' => $target->id]);

        // No aparece en el listado normal
        $this->actingAs($admin)->getJson('/api/admin/users')
            ->assertJsonMissing(['name' => 'Borrable']);

        // Restaurar
        $this->actingAs($admin)->postJson("/api/admin/users/{$target->id}/restore")->assertOk();
        $this->assertDatabaseHas('users', ['id' => $target->id, 'deleted_at' => null]);
    }

    public function test_no_puede_eliminarse_a_si_mismo(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/users/{$admin->id}")
            ->assertStatus(422);
    }

    public function test_no_puede_degradar_al_unico_admin(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->patchJson("/api/admin/users/{$admin->id}", ['role' => 'user'])
            ->assertStatus(422);
    }
}
