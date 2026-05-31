<?php

namespace App\Console\Commands;

use App\Models\Finance\ActivoFijo;
use App\Models\Finance\Depreciacion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CalcularDepreciacionCommand extends Command
{
    protected $signature = 'finance:calcular-depreciacion
                            {--mes= : Mes (1-12). Default: mes actual}
                            {--anio= : Año. Default: año actual}
                            {--dry-run : Solo muestra sin guardar}';

    protected $description = 'Calcula y registra la depreciación mensual de activos fijos';

    public function handle(): int
    {
        $mes  = (int) ($this->option('mes')  ?? now()->month);
        $anio = (int) ($this->option('anio') ?? now()->year);
        $dry  = $this->option('dry-run');

        if ($mes < 1 || $mes > 12) {
            $this->error('El mes debe estar entre 1 y 12.');
            return Command::FAILURE;
        }

        $this->info("Calculando depreciación para {$mes}/{$anio}" . ($dry ? ' [DRY-RUN]' : '') . '...');

        $activos = ActivoFijo::where('estado', 'activo')->get();
        $rows    = [];

        foreach ($activos as $activo) {
            // Verificar si ya fue calculada
            $yaExiste = Depreciacion::where('activo_id', $activo->id)
                ->where('periodo_anio', $anio)
                ->where('periodo_mes', $mes)
                ->exists();

            if ($yaExiste) {
                $this->line("  <comment>⚠ {$activo->nombre}</comment>: ya calculado para {$mes}/{$anio}");
                continue;
            }

            // Verificar que el activo ya fue adquirido
            if ($activo->fecha_adquisicion->isAfter(now()->setMonth($mes)->setYear($anio)->endOfMonth())) {
                continue;
            }

            // Acumulado anterior
            $acumuladaAnterior = Depreciacion::where('activo_id', $activo->id)->sum('depreciacion_mensual');
            $valorLibroAnterior = (float) $activo->costo_adquisicion - $acumuladaAnterior;

            if ($valorLibroAnterior <= (float) $activo->valor_residual) {
                $this->line("  <info>✓ {$activo->nombre}</info>: totalmente depreciado.");
                continue;
            }

            // Calcular cuota mensual
            if ($activo->metodo_depreciacion === 'lineal') {
                $cuota = $activo->depreciacionMensualLineal();
            } else {
                // Método acelerado (doble tasa)
                $tasaAnual  = ($activo->vida_util_meses > 0) ? (1 / ($activo->vida_util_meses / 12)) * 2 : 0;
                $cuota      = round($valorLibroAnterior * $tasaAnual / 12, 4);
            }

            // No depreciar más del valor libro - valor residual
            $cuota = min($cuota, $valorLibroAnterior - (float) $activo->valor_residual);

            $acumuladaNueva = round($acumuladaAnterior + $cuota, 4);
            $valorLibroNuevo = round((float) $activo->costo_adquisicion - $acumuladaNueva, 4);

            $rows[] = [
                'activo'     => $activo->nombre,
                'cuota'      => number_format($cuota, 2),
                'acumulada'  => number_format($acumuladaNueva, 2),
                'valor_libro' => number_format($valorLibroNuevo, 2),
            ];

            if (! $dry) {
                Depreciacion::create([
                    'activo_id'               => $activo->id,
                    'periodo_anio'            => $anio,
                    'periodo_mes'             => $mes,
                    'depreciacion_mensual'    => $cuota,
                    'depreciacion_acumulada'  => $acumuladaNueva,
                    'valor_libro'             => $valorLibroNuevo,
                ]);
            }
        }

        if (! empty($rows)) {
            $this->table(['Activo', 'Cuota Mensual', 'Dep. Acumulada', 'Valor Libro'], $rows);
        }

        $this->info($dry
            ? 'Simulación completada. Usa sin --dry-run para guardar.'
            : "✓ Depreciaciones guardadas: " . count($rows) . " activos."
        );

        return Command::SUCCESS;
    }
}
