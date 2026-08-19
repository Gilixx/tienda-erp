<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\InventoryMovement;
use App\Models\Inventory\Almacen;
use App\Models\Inventory\ProductStock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventoryDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'admin@crmac.com')->first()
            ?? User::first();

        if (!$user) {
            $this->command->warn('No hay usuarios en el sistema. Ejecuta DatabaseSeeder primero.');
            return;
        }

        $almacen = Almacen::where('es_principal', true)->first()
            ?? Almacen::first();

        if (!$almacen) {
            $this->command->warn('No hay almacenes en el sistema. Crea al menos un almacén.');
            return;
        }

        // Garantizar que el almacén tenga dueño; si es huérfano (created_by NULL),
        // el control de acceso por almacén lo dejaría invisible y los datos demo
        // no cargarían para ningún usuario.
        if (is_null($almacen->created_by)) {
            $almacen->update(['created_by' => $user->id]);
        }

        // Limpiar datos previos de inventario para evitar duplicados
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ProductStock::truncate();
        InventoryMovement::truncate();
        Product::truncate();
        Category::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Crear Categorías
        $categoriesData = [
            ['name' => 'Electrónica', 'description' => 'Dispositivos electrónicos, accesorios y gadgets.'],
            ['name' => 'Alimentos', 'description' => 'Bebidas, snacks y productos de consumo alimenticio.'],
            ['name' => 'Limpieza', 'description' => 'Productos de limpieza del hogar y desinfectantes.'],
        ];

        $categories = [];
        foreach ($categoriesData as $cat) {
            $categories[$cat['name']] = Category::create([
                'name' => $cat['name'],
                'description' => $cat['description'],
                'created_by' => $user->id,
                'almacen_id' => $almacen->id,
            ]);
        }

        // 2. Crear Productos y sus Existencias
        $productsData = [
            [
                'name' => 'Mouse Inalámbrico',
                'sku' => 'ELE-001',
                'description' => 'Mouse ergonómico inalámbrico de 2.4GHz.',
                'price' => 250.00,
                'cost' => 150.00,
                'stock' => 50,
                'min_stock' => 10,
                'category' => 'Electrónica',
            ],
            [
                'name' => 'Teclado Mecánico',
                'sku' => 'ELE-002',
                'description' => 'Teclado mecánico con luces RGB y switches azules.',
                'price' => 850.00,
                'cost' => 450.00,
                'stock' => 30,
                'min_stock' => 5,
                'category' => 'Electrónica',
            ],
            [
                'name' => 'Coca Cola 600ml',
                'sku' => 'ALI-001',
                'description' => 'Refresco de cola de 600ml botella no retornable.',
                'price' => 18.00,
                'cost' => 12.00,
                'stock' => 120,
                'min_stock' => 20,
                'category' => 'Alimentos',
            ],
            [
                'name' => 'Frijol Bayo 1kg',
                'sku' => 'ALI-002',
                'description' => 'Bolsa de frijol bayo seleccionado de 1kg.',
                'price' => 38.00,
                'cost' => 28.00,
                'stock' => 8, // Menos que min_stock para disparar alerta!
                'min_stock' => 15,
                'category' => 'Alimentos',
            ],
            [
                'name' => 'Detergente Líquido 1L',
                'sku' => 'LIM-001',
                'description' => 'Detergente líquido biodegradable para ropa de color.',
                'price' => 65.00,
                'cost' => 45.00,
                'stock' => 20,
                'min_stock' => 5,
                'category' => 'Limpieza',
            ],
        ];

        foreach ($productsData as $pData) {
            $product = Product::create([
                'name' => $pData['name'],
                'sku' => $pData['sku'],
                'description' => $pData['description'],
                'price' => $pData['price'],
                'cost' => $pData['cost'],
                'stock' => $pData['stock'],
                'min_stock' => $pData['min_stock'],
                'created_by' => $user->id,
                'is_active' => true,
            ]);

            // Asignar stock y categoría al almacén
            $category = $categories[$pData['category']];
            ProductStock::create([
                'product_id' => $product->id,
                'almacen_id' => $almacen->id,
                'cantidad' => $pData['stock'],
                'category_id' => $category->id,
            ]);

            // Crear movimiento de inventario inicial
            InventoryMovement::create([
                'product_id' => $product->id,
                'almacen_id' => $almacen->id,
                'user_id' => $user->id,
                'type' => 'in',
                'quantity' => $pData['stock'],
                'reference' => 'Carga Inicial',
                'notes' => 'Registro de inventario inicial a través del seeder de demostración.',
            ]);
        }

        $this->command->info('¡Datos demo de inventario cargados exitosamente!');
    }
}
