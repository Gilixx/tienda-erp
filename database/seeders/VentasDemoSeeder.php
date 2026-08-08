<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class VentasDemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'admin@crmac.com')->first()
            ?? User::first();

        if (! $user) {
            $this->command->warn('No hay usuarios; ejecuta DatabaseSeeder primero.');

            return;
        }

        // Limpiar ventas previas para regenerar
        VentaItem::query()->delete();
        Venta::query()->delete();

        // Productos demo (crea los que falten). La categoría es por almacén y
        // se gestiona desde el módulo de inventario, no en este seeder de ventas.
        $demo = [
            ['Coca Cola 600ml', 'BEB-001', 18.00, 12.00, 120],
            ['Sabritas Original', 'SNK-001', 22.00, 15.00, 80],
            ['Galletas Marías', 'GAL-001', 15.00, 9.00, 60],
            ['Pan Bimbo Grande', 'PAN-001', 55.00, 38.00, 40],
            ['Leche Lala 1L', 'LAC-001', 28.00, 20.00, 90],
            ['Café Nescafé 100g', 'CAF-001', 75.00, 50.00, 25],
            ['Atún Dolores', 'ENL-001', 24.00, 16.00, 70],
            ['Frijol Bayo 1kg', 'GRA-001', 38.00, 28.00, 35],
            ['Aceite 1L', 'ACE-001', 45.00, 32.00, 50],
            ['Detergente 1kg', 'LIM-001', 65.00, 45.00, 20],
        ];

        foreach ($demo as [$name, $sku, $price, $cost, $stock]) {
            Product::firstOrCreate(
                ['sku' => $sku, 'created_by' => $user->id],
                [
                    'name' => $name,
                    'price' => $price,
                    'cost' => $cost,
                    'stock' => $stock,
                    'min_stock' => 5,
                    'is_active' => true,
                ]
            );
        }

        $products = Product::all();
        $topIds = $products->take(3)->pluck('id')->all();

        // Generar ventas de los últimos 90 días
        for ($i = 0; $i < 90; $i++) {
            $fecha = Carbon::now()->subDays($i);
            $ventasDia = rand(1, 8);

            for ($v = 0; $v < $ventasDia; $v++) {
                $venta = Venta::create([
                    'user_id' => $user->id,
                    'total' => 0,
                    'estado' => 'completada',
                    'fecha' => $fecha->copy()->setTime(rand(8, 20), rand(0, 59)),
                ]);

                $total = 0;
                // Productos con probabilidad sesgada (algunos más vendidos)
                $itemsCount = rand(1, 4);
                $picked = $products->random(min($itemsCount, $products->count()));

                foreach ($picked as $producto) {
                    // Sesgo: top productos se venden más
                    $cantidad = in_array($producto->id, $topIds, true) ? rand(2, 6) : rand(1, 2);
                    $precio = (float) $producto->price;
                    $subtotal = $cantidad * $precio;
                    $total += $subtotal;

                    VentaItem::create([
                        'venta_id' => $venta->id,
                        'product_id' => $producto->id,
                        'cantidad' => $cantidad,
                        'precio_unit' => $precio,
                        'subtotal' => $subtotal,
                    ]);
                }

                $venta->update(['total' => $total]);
            }
        }

        $this->command->info('Ventas demo generadas correctamente.');
    }
}
