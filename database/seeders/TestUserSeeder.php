<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Service;

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

        $service = Service::firstOrCreate(
            ['key' => 'inventory'],
            ['name' => 'Sistema de Inventarios', 'description' => 'Módulo de Inventarios', 'icon' => 'box']
        );

        $user->services()->syncWithoutDetaching([
            $service->id => ['expires_at' => now()->addYears(10)]
        ]);
        
        $serviceFinance = Service::firstOrCreate(
            ['key' => 'finance'],
            ['name' => 'Sistema de Finanzas', 'description' => 'Módulo de Finanzas', 'icon' => 'dollar-sign']
        );
        
        $user->services()->syncWithoutDetaching([
            $serviceFinance->id => ['expires_at' => now()->addYears(10)]
        ]);
    }
}
