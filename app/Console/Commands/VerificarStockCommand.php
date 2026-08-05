<?php

namespace App\Console\Commands;

use App\Services\Inventory\VerificarStockService;
use Illuminate\Console\Command;

class VerificarStockCommand extends Command
{
    protected $signature = 'inventory:verificar-stock {--almacen= : ID del almacén específico}';

    protected $description = 'Verifica stock mínimo y puntos de reorden, genera alertas automáticas';

    public function handle(VerificarStockService $service): int
    {
        $almacenId = $this->option('almacen');

        $resultado = $service->verificar($almacenId ? (int) $almacenId : null);

        $this->info("✓ Verificación completada. Alertas creadas: {$resultado['creadas']}, resueltas: {$resultado['resueltas']}.");

        return Command::SUCCESS;
    }
}
