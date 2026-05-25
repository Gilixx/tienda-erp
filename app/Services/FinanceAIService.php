<?php

namespace App\Services;

use App\Models\Finance\Cuenta;
use App\Models\Finance\CuentaPorCobrar;
use App\Models\Finance\CuentaPorPagar;
use App\Models\Finance\Presupuesto;
use App\Models\Finance\Transaccion;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FinanceAIService
{
    public function __construct(
        private OllamaService $ollama,
        private CurrencyService $fx,
    ) {}

    private function systemPrompt(): string
    {
        return 'Eres un asesor financiero experto en pequeñas empresas mexicanas (PyME retail). '
             . 'Respondes SIEMPRE en español, en formato Markdown limpio, con secciones claras y bullets cortos. '
             . 'Sé conciso, directo, y orientado a acciones concretas. No inventes cifras; usa solo los datos provistos. '
             . 'Si una cifra está vacía o es 0, dilo explícitamente en vez de adornar.';
    }

    // ─── 1. Informe ejecutivo del periodo ──────────────────────
    public function informeEjecutivo(int $days = 30): array
    {
        $since = Carbon::now()->subDays($days);
        $data = [
            'periodo_dias'  => $days,
            'moneda_base'   => $this->fx->base()->codigo,
            'kpis'          => $this->kpis($since),
            'top_categorias_egreso' => $this->topCategorias($since, 'egreso', 5),
            'top_categorias_ingreso' => $this->topCategorias($since, 'ingreso', 5),
            'cxc'           => $this->resumenCxC(),
            'cxp'           => $this->resumenCxP(),
            'cuentas'       => $this->saldosCuentas(),
        ];

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $prompt = <<<PROMPT
Analiza estos datos financieros de los últimos {$days} días.

DATOS:
```json
{$json}
```

Genera un informe BREVE con EXACTAMENTE estas secciones (máximo 2 bullets cada una):

## Resumen del periodo
1 línea con utilidad, margen y tendencia.

## Lo bueno
2 puntos positivos concretos basados en los datos.

## Focos rojos
2 alertas (egreso alto, CxC vencida, baja liquidez, etc.).

## Acciones recomendadas
3 acciones priorizadas y específicas (no genéricas).
PROMPT;

        $content = $this->ollama->generate($prompt, $this->systemPrompt());
        return ['content' => $content, 'data' => $data];
    }

    // ─── 2. Proyección de flujo de caja ────────────────────────
    public function proyeccionFlujo(int $dias = 30): array
    {
        $hoy   = Carbon::now()->startOfDay();
        $hasta = $hoy->copy()->addDays($dias);
        $base  = $this->fx->base();

        // Promedio diario histórico (60 días)
        $desde = $hoy->copy()->subDays(60);
        $hist = Transaccion::where('fecha', '>=', $desde)
            ->whereIn('tipo', ['ingreso', 'egreso'])
            ->selectRaw('tipo, SUM(total_base) as total, COUNT(DISTINCT fecha) as dias_activos')
            ->groupBy('tipo')
            ->get()
            ->keyBy('tipo');

        $promIngresoDia = isset($hist['ingreso']) && $hist['ingreso']->dias_activos > 0
            ? round($hist['ingreso']->total / max(60, $hist['ingreso']->dias_activos), 2) : 0;
        $promEgresoDia  = isset($hist['egreso']) && $hist['egreso']->dias_activos > 0
            ? round($hist['egreso']->total / max(60, $hist['egreso']->dias_activos), 2) : 0;

        // CxC esperadas en los próximos N días
        $cxcEsperado = (float) CuentaPorCobrar::whereIn('estado', ['pendiente', 'parcial'])
            ->whereBetween('fecha_vencimiento', [$hoy->toDateString(), $hasta->toDateString()])
            ->sum(DB::raw('saldo * tipo_cambio'));

        // CxP esperadas a pagar
        $cxpEsperado = (float) CuentaPorPagar::whereIn('estado', ['pendiente', 'parcial'])
            ->whereBetween('fecha_vencimiento', [$hoy->toDateString(), $hasta->toDateString()])
            ->sum(DB::raw('saldo * tipo_cambio'));

        $cxcVencidas = (float) CuentaPorCobrar::whereIn('estado', ['pendiente', 'parcial'])
            ->where('fecha_vencimiento', '<', $hoy->toDateString())
            ->sum(DB::raw('saldo * tipo_cambio'));

        $cxpVencidas = (float) CuentaPorPagar::whereIn('estado', ['pendiente', 'parcial'])
            ->where('fecha_vencimiento', '<', $hoy->toDateString())
            ->sum(DB::raw('saldo * tipo_cambio'));

        $saldoActual = (float) Cuenta::where('activa', true)
            ->sum(DB::raw('saldo_actual * 1'));

        $ingresoProyectado = round($promIngresoDia * $dias + $cxcEsperado, 2);
        $egresoProyectado  = round($promEgresoDia * $dias + $cxpEsperado, 2);
        $saldoFinal        = round($saldoActual + $ingresoProyectado - $egresoProyectado, 2);

        $data = [
            'horizonte_dias'       => $dias,
            'moneda_base'          => $base->codigo,
            'saldo_actual'         => round($saldoActual, 2),
            'promedio_ingreso_dia' => $promIngresoDia,
            'promedio_egreso_dia'  => $promEgresoDia,
            'cxc_a_cobrar'         => round($cxcEsperado, 2),
            'cxp_a_pagar'          => round($cxpEsperado, 2),
            'cxc_ya_vencidas'      => round($cxcVencidas, 2),
            'cxp_ya_vencidas'      => round($cxpVencidas, 2),
            'ingreso_proyectado'   => $ingresoProyectado,
            'egreso_proyectado'    => $egresoProyectado,
            'saldo_proyectado_fin' => $saldoFinal,
        ];

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $prompt = <<<PROMPT
Analiza esta proyección de flujo de caja a {$dias} días basada en histórico de 60 días y CxC/CxP comprometidas.

DATOS:
```json
{$json}
```

Genera un análisis BREVE con estas secciones:

## Veredicto
1 línea: ¿llega bien o hay riesgo de liquidez?

## Lo que se ve venir
2 bullets con los movimientos clave del periodo.

## Riesgos
1-2 puntos críticos (si saldo proyectado < 0, CxP > entrada, vencidas, etc.).

## Acciones sugeridas
2-3 acciones concretas (acelerar cobros, renegociar plazos, etc.).
PROMPT;

        $content = $this->ollama->generate($prompt, $this->systemPrompt());
        return ['content' => $content, 'data' => $data];
    }

    // ─── 3. Detección de anomalías ─────────────────────────────
    public function detectarAnomalias(): array
    {
        $hoy = Carbon::now();
        $mesActual = $hoy->copy()->startOfMonth();
        $mesAnterior = $mesActual->copy()->subMonth();

        // Egresos por categoría: mes actual vs promedio últimos 3 meses
        $tresMesesAtras = $mesActual->copy()->subMonths(3);

        $actual = DB::table('transacciones')
            ->where('tipo', 'egreso')
            ->where('fecha', '>=', $mesActual)
            ->selectRaw('categoria_id, SUM(total_base) as monto')
            ->groupBy('categoria_id')
            ->pluck('monto', 'categoria_id');

        $promedios = DB::table('transacciones')
            ->where('tipo', 'egreso')
            ->whereBetween('fecha', [$tresMesesAtras, $mesActual])
            ->selectRaw('categoria_id, SUM(total_base) / 3 as monto')
            ->groupBy('categoria_id')
            ->pluck('monto', 'categoria_id');

        $categorias = DB::table('categorias_finanzas')->where('tipo', 'egreso')->pluck('nombre', 'id');

        $anomalias = [];
        foreach ($actual as $catId => $monto) {
            $promedio = (float) ($promedios[$catId] ?? 0);
            $delta = $promedio > 0 ? round((($monto - $promedio) / $promedio) * 100, 1) : null;

            if ($promedio === 0.0 && $monto > 0) {
                $anomalias[] = [
                    'categoria' => $categorias[$catId] ?? 'Sin categoría',
                    'monto_actual' => (float) $monto,
                    'promedio_3m'  => 0,
                    'variacion_pct' => null,
                    'tipo' => 'nuevo_gasto',
                ];
            } elseif ($delta !== null && abs($delta) >= 30) {
                $anomalias[] = [
                    'categoria' => $categorias[$catId] ?? 'Sin categoría',
                    'monto_actual' => round((float) $monto, 2),
                    'promedio_3m'  => round($promedio, 2),
                    'variacion_pct' => $delta,
                    'tipo' => $delta > 0 ? 'incremento' : 'reduccion',
                ];
            }
        }

        // Presupuestos excedidos
        $presupuestos = Presupuesto::with('categoria')
            ->where('anio', $hoy->year)
            ->where('mes', $hoy->month)
            ->get();
        $excedidos = [];
        foreach ($presupuestos as $p) {
            $gastado = (float) DB::table('transacciones')
                ->where('categoria_id', $p->categoria_id)
                ->whereYear('fecha', $p->anio)
                ->whereMonth('fecha', $p->mes)
                ->sum('total_base');
            if ($gastado > (float) $p->monto_limite) {
                $excedidos[] = [
                    'categoria' => $p->categoria->nombre,
                    'limite'    => (float) $p->monto_limite,
                    'gastado'   => round($gastado, 2),
                    'exceso'    => round($gastado - (float) $p->monto_limite, 2),
                ];
            }
        }

        $data = [
            'mes_analizado' => $mesActual->format('Y-m'),
            'moneda_base'   => $this->fx->base()->codigo,
            'anomalias_egresos' => $anomalias,
            'presupuestos_excedidos' => $excedidos,
        ];

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $prompt = <<<PROMPT
Analiza estas anomalías financieras del mes en curso.

DATOS:
```json
{$json}
```

Genera un análisis BREVE:

## Diagnóstico
1 línea: ¿hay algo raro o todo normal?

## Anomalías detectadas
Lista cada anomalía con 1 línea explicando posible causa.

## Recomendaciones
2-3 acciones para investigar/corregir.

Si no hay anomalías, di "Sin desviaciones relevantes" y explica brevemente por qué.
PROMPT;

        $content = $this->ollama->generate($prompt, $this->systemPrompt());
        return ['content' => $content, 'data' => $data];
    }

    // ─── 4. Asesor de compras (cruza inventario + ventas) ──────
    public function asesorCompras(): array
    {
        $since = Carbon::now()->subDays(30);

        // Productos con stock bajo Y ventas recientes
        $criticos = DB::table('products')
            ->leftJoin('venta_items', 'venta_items.product_id', '=', 'products.id')
            ->leftJoin('ventas', function ($j) use ($since) {
                $j->on('ventas.id', '=', 'venta_items.venta_id')
                  ->where('ventas.estado', '=', 'completada')
                  ->where('ventas.fecha', '>=', $since);
            })
            ->where('products.is_active', true)
            ->whereRaw('products.stock <= products.min_stock * 1.5')
            ->selectRaw('products.id, products.sku, products.name, products.stock, products.min_stock, products.cost, products.price,
                COALESCE(SUM(venta_items.cantidad), 0) as vendidas_30d')
            ->groupBy('products.id', 'products.sku', 'products.name', 'products.stock', 'products.min_stock', 'products.cost', 'products.price')
            ->orderByDesc('vendidas_30d')
            ->limit(15)
            ->get()
            ->map(function ($r) {
                $velocidadDia = round($r->vendidas_30d / 30, 2);
                $diasRestantes = $velocidadDia > 0 ? round($r->stock / $velocidadDia, 0) : null;
                $sugerido = max(0, (int) round($velocidadDia * 30 - $r->stock));
                return [
                    'sku'            => $r->sku,
                    'producto'       => $r->name,
                    'stock'          => (int) $r->stock,
                    'min_stock'      => (int) $r->min_stock,
                    'vendidas_30d'   => (int) $r->vendidas_30d,
                    'velocidad_dia'  => $velocidadDia,
                    'dias_restantes' => $diasRestantes,
                    'cantidad_sugerida' => $sugerido,
                    'costo_unitario' => (float) $r->cost,
                    'inversion_estimada' => round($sugerido * (float) $r->cost, 2),
                ];
            })
            ->all();

        $saldoLiquido = (float) Cuenta::where('activa', true)
            ->whereIn('tipo', ['caja', 'banco', 'tarjeta_debito'])
            ->sum(DB::raw('saldo_actual * 1'));

        $inversionTotal = array_sum(array_column($criticos, 'inversion_estimada'));

        $data = [
            'moneda_base' => $this->fx->base()->codigo,
            'saldo_liquido_disponible' => round($saldoLiquido, 2),
            'inversion_recomendada_total' => round($inversionTotal, 2),
            'productos_criticos' => $criticos,
        ];

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $prompt = <<<PROMPT
Analiza estos productos críticos de inventario cruzados con ventas y liquidez disponible.

DATOS:
```json
{$json}
```

Genera un asesor de compras BREVE:

## Prioridad alta
Top 3-5 productos a comprar YA (los que se quedarán sin stock pronto y se venden bien).

## Prioridad media
2-3 productos a considerar.

## ¿Alcanza el dinero?
1 línea comparando inversión recomendada vs saldo líquido.

## Estrategia sugerida
2 puntos: cómo distribuir la compra (todo de golpe, escalonado, priorizar más rentables, etc.).
PROMPT;

        $content = $this->ollama->generate($prompt, $this->systemPrompt());
        return ['content' => $content, 'data' => $data];
    }

    // ─── Helpers internos ──────────────────────────────────────
    private function kpis(Carbon $since): array
    {
        $rows = Transaccion::where('fecha', '>=', $since)
            ->whereIn('tipo', ['ingreso', 'egreso'])
            ->selectRaw('tipo, SUM(total_base) as monto')
            ->groupBy('tipo')
            ->pluck('monto', 'tipo');

        $ing = (float) ($rows['ingreso'] ?? 0);
        $egr = (float) ($rows['egreso'] ?? 0);

        return [
            'ingresos'  => round($ing, 2),
            'egresos'   => round($egr, 2),
            'utilidad'  => round($ing - $egr, 2),
            'margen'    => $ing > 0 ? round((($ing - $egr) / $ing) * 100, 2) : 0,
        ];
    }

    private function topCategorias(Carbon $since, string $tipo, int $limit): array
    {
        return DB::table('transacciones')
            ->leftJoin('categorias_finanzas', 'transacciones.categoria_id', '=', 'categorias_finanzas.id')
            ->where('transacciones.fecha', '>=', $since)
            ->where('transacciones.tipo', $tipo)
            ->selectRaw('categorias_finanzas.nombre, SUM(transacciones.total_base) as monto')
            ->groupBy('categorias_finanzas.nombre')
            ->orderByDesc('monto')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => ['categoria' => $r->nombre ?? 'Sin categoría', 'monto' => round((float) $r->monto, 2)])
            ->all();
    }

    private function resumenCxC(): array
    {
        $hoy = now()->toDateString();
        return [
            'pendiente_total' => (float) CuentaPorCobrar::whereIn('estado', ['pendiente', 'parcial'])
                ->sum(DB::raw('saldo * tipo_cambio')),
            'vencido_total'   => (float) CuentaPorCobrar::whereIn('estado', ['pendiente', 'parcial'])
                ->where('fecha_vencimiento', '<', $hoy)
                ->sum(DB::raw('saldo * tipo_cambio')),
            'cuentas_count'   => CuentaPorCobrar::whereIn('estado', ['pendiente', 'parcial'])->count(),
        ];
    }

    private function resumenCxP(): array
    {
        $hoy = now()->toDateString();
        return [
            'pendiente_total' => (float) CuentaPorPagar::whereIn('estado', ['pendiente', 'parcial'])
                ->sum(DB::raw('saldo * tipo_cambio')),
            'vencido_total'   => (float) CuentaPorPagar::whereIn('estado', ['pendiente', 'parcial'])
                ->where('fecha_vencimiento', '<', $hoy)
                ->sum(DB::raw('saldo * tipo_cambio')),
            'cuentas_count'   => CuentaPorPagar::whereIn('estado', ['pendiente', 'parcial'])->count(),
        ];
    }

    private function saldosCuentas(): array
    {
        return Cuenta::where('activa', true)
            ->get(['nombre', 'tipo', 'saldo_actual'])
            ->map(fn ($c) => [
                'nombre' => $c->nombre,
                'tipo'   => $c->tipo,
                'saldo'  => (float) $c->saldo_actual,
            ])
            ->all();
    }
}
