<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestUserSeeder extends Seeder
{
    public function run()
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@tienda-erp.com'],
            ['name' => 'Admin Test', 'password' => bcrypt('password123')]
        );
        $user->role = 'admin';
        $user->is_active = true;
        $user->save();

        $inventory = Service::firstOrCreate(
            ['key' => 'inventory'],
            ['name' => 'Sistema de Inventarios', 'description' => 'Módulo de Inventarios', 'icon' => 'box']
        );

        $pos = Service::firstOrCreate(
            ['key' => 'pos'],
            ['name' => 'Punto de Venta', 'description' => 'Terminal de ventas y reportes', 'icon' => 'pos']
        );

        $user->services()->syncWithoutDetaching([
            $inventory->id => ['expires_at' => now()->addYears(10)],
            $pos->id => ['expires_at' => now()->addYears(10)],
        ]);
    }
}
