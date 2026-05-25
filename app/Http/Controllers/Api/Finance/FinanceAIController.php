<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Services\FinanceAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FinanceAIController extends Controller
{
    private const CACHE_TTL = 3600; // 1h

    public function informe(Request $request, FinanceAIService $ai)
    {
        return $this->handle($request, 'informe', fn () => $ai->informeEjecutivo(
            max(7, min(365, (int) $request->input('days', 30)))
        ), $request->input('days', 30));
    }

    public function forecast(Request $request, FinanceAIService $ai)
    {
        return $this->handle($request, 'forecast', fn () => $ai->proyeccionFlujo(
            max(7, min(180, (int) $request->input('dias', 30)))
        ), $request->input('dias', 30));
    }

    public function anomalias(Request $request, FinanceAIService $ai)
    {
        return $this->handle($request, 'anomalias', fn () => $ai->detectarAnomalias(), 'mes');
    }

    public function compras(Request $request, FinanceAIService $ai)
    {
        return $this->handle($request, 'compras', fn () => $ai->asesorCompras(), 'now');
    }

    private function handle(Request $request, string $tipo, \Closure $generator, $variant)
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        $force    = $request->boolean('refresh', false);
        $cacheKey = "finance.ai.{$tipo}.{$variant}";

        if ($force) {
            Cache::forget($cacheKey);
        }

        try {
            $report = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($generator, $tipo) {
                $result = $generator();
                Log::info('Informe IA finanzas generado', [
                    'tipo'    => $tipo,
                    'user_id' => auth()->id(),
                    'chars'   => strlen($result['content'] ?? ''),
                ]);
                return [
                    'content'      => $result['content'],
                    'data'         => $result['data'],
                    'generated_at' => now()->toIso8601String(),
                ];
            });

            return response()->json($report + ['cached' => ! $force]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'hint'    => 'Verifica que Ollama esté corriendo en ' . config('services.ollama.url'),
            ], 503);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Error al generar análisis IA: ' . $e->getMessage()], 500);
        }
    }
}
