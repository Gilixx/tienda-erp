<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create the module/service catalogue
        $inventory = Service::create([
            'key' => 'inventory',
            'name' => 'Sistema de Inventarios',
            'description' => 'Control completo de existencias, variantes, alertas y movimientos de almacén.',
            'icon' => 'inventory',
        ]);

        $pos = Service::create([
            'key' => 'pos',
            'name' => 'Punto de Venta',
            'description' => 'Terminal de ventas con escáner, carrito, cobro y reportes de ventas.',
            'icon' => 'pos',
        ]);

        // Create an admin user (full access to all modules)
        $admin = User::create([
            'name' => 'Administrador',
            'email' => 'admin@crmac.com',
            'password' => Hash::make('Admin1234!'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Create a demo user with only inventory access
        $demoUser = User::create([
            'name' => 'Usuario Demo',
            'email' => 'demo@crmac.com',
            'password' => Hash::make('Demo1234!'),
            'role' => 'user',
            'is_active' => true,
        ]);

        // Grant demo user access to inventory and POS
        $demoUser->services()->attach([
            $inventory->id => ['granted_at' => now(), 'expires_at' => null],
            $pos->id => ['granted_at' => now(), 'expires_at' => null],
        ]);
    }
}
