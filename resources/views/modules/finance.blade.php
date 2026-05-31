@extends('layouts.app')
@section('title', 'Sistema de Finanzas')
@section('page-title', 'Finanzas')

@section('content')
<div id="finance-app" class="space-y-6">

    {{-- Controles superiores --}}
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-slate-600 dark:text-zinc-400">Periodo:</label>
            <select id="fin-period" class="rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-700 dark:text-zinc-200 text-sm focus:border-amber-500 focus:ring-amber-500">
                <option value="7">Últimos 7 días</option>
                <option value="30" selected>Últimos 30 días</option>
                <option value="90">Últimos 90 días</option>
                <option value="180">Últimos 6 meses</option>
                <option value="365">Último año</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button id="btn-nueva-transaccion" class="bg-amber-600 hover:bg-amber-700 active:-translate-y-px text-white px-4 py-2.5 rounded-xl font-semibold text-sm transition-all focus:ring-4 focus:ring-amber-100 dark:focus:ring-amber-900/30 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Nueva transacción
            </button>
            <button id="btn-transferir" class="bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 text-indigo-700 dark:text-indigo-400 px-4 py-2.5 rounded-xl font-semibold text-sm transition-all focus:ring-4 focus:ring-indigo-100 dark:focus:ring-indigo-900/30 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                Transferir
            </button>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 border-l-[3px] border-l-emerald-500 rounded-2xl p-4">
            <p class="text-[11px] font-semibold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">Ingresos</p>
            <p id="kpi-ingresos" class="text-2xl font-mono font-semibold text-emerald-600 dark:text-emerald-400 mt-2 tabular-nums">—</p>
        </div>
        <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 border-l-[3px] border-l-rose-500 rounded-2xl p-4">
            <p class="text-[11px] font-semibold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">Egresos</p>
            <p id="kpi-egresos" class="text-2xl font-mono font-semibold text-rose-600 dark:text-rose-400 mt-2 tabular-nums">—</p>
        </div>
        <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 border-l-[3px] border-l-amber-500 rounded-2xl p-4">
            <p class="text-[11px] font-semibold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">Utilidad</p>
            <p id="kpi-utilidad" class="text-2xl font-mono font-semibold text-slate-900 dark:text-zinc-50 mt-2 tabular-nums">—</p>
            <p id="kpi-margen" class="text-xs font-mono text-slate-400 dark:text-zinc-500 mt-1 tabular-nums">Margen —%</p>
        </div>
        <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 border-l-[3px] border-l-indigo-500 rounded-2xl p-4">
            <p class="text-[11px] font-semibold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">Saldo total</p>
            <p id="kpi-saldo" class="text-2xl font-mono font-semibold text-indigo-600 dark:text-indigo-400 mt-2 tabular-nums">—</p>
            <p id="kpi-cuentas-info" class="text-xs font-mono text-slate-400 dark:text-zinc-500 mt-1 tabular-nums">— cuentas</p>
        </div>
    </div>

    {{-- Alertas CxC / CxP --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white dark:bg-zinc-900 border border-amber-200/70 dark:border-amber-800/30 border-l-[3px] border-l-amber-500 rounded-2xl p-4 flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">Por cobrar</p>
                <p id="cxc-monto" class="text-xl font-mono font-semibold text-amber-700 dark:text-amber-400 mt-2 tabular-nums">—</p>
                <p id="cxc-info" class="text-xs font-mono text-slate-400 dark:text-zinc-500 mt-1 tabular-nums">—</p>
            </div>
            <svg class="w-9 h-9 text-amber-200 dark:text-amber-800/60" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
        </div>
        <div class="bg-white dark:bg-zinc-900 border border-rose-200/70 dark:border-rose-800/30 border-l-[3px] border-l-rose-500 rounded-2xl p-4 flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">Por pagar</p>
                <p id="cxp-monto" class="text-xl font-mono font-semibold text-rose-700 dark:text-rose-400 mt-2 tabular-nums">—</p>
                <p id="cxp-info" class="text-xs font-mono text-slate-400 dark:text-zinc-500 mt-1 tabular-nums">—</p>
            </div>
            <svg class="w-9 h-9 text-rose-200 dark:text-rose-800/60" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
        </div>
    </div>

    {{-- Gráficas --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl p-5">
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-slate-800 dark:text-zinc-100">Flujo diario</h3>
                <p class="text-xs text-slate-400 dark:text-zinc-500 mt-0.5">Ingresos vs egresos por día</p>
            </div>
            <div class="relative h-64"><canvas id="chart-flow"></canvas></div>
        </div>
        <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl p-5">
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-slate-800 dark:text-zinc-100">Distribución de egresos</h3>
                <p class="text-xs text-slate-400 dark:text-zinc-500 mt-0.5">Por categoría en el periodo</p>
            </div>
            <div class="relative h-64"><canvas id="chart-cats"></canvas></div>
        </div>
    </div>

    {{-- Asesor IA --}}
    <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 border-l-[3px] border-l-violet-500 rounded-2xl p-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-violet-50 dark:bg-violet-900/30 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-zinc-50">Asesor financiero IA</h3>
                    <p class="text-xs text-slate-400 dark:text-zinc-500 mt-0.5">Análisis local con Ollama · sin enviar datos a la nube</p>
                </div>
            </div>
            <button id="btn-ai-refresh" class="hidden text-xs text-violet-600 dark:text-violet-400 hover:text-violet-800 dark:hover:text-violet-300 font-semibold">Regenerar</button>
        </div>

        <div class="flex flex-wrap gap-2 mb-5">
            <button data-ai="informe" class="ai-btn bg-violet-600 hover:bg-violet-700 active:-translate-y-px text-white px-4 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Informe ejecutivo
            </button>
            <button data-ai="forecast" class="ai-btn bg-white dark:bg-zinc-800 hover:bg-slate-50 dark:hover:bg-zinc-700 active:-translate-y-px border border-slate-200 dark:border-zinc-700 text-slate-700 dark:text-zinc-300 px-4 py-2 rounded-xl text-sm font-medium transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
                Proyección de flujo
            </button>
            <button data-ai="anomalias" class="ai-btn bg-white dark:bg-zinc-800 hover:bg-slate-50 dark:hover:bg-zinc-700 active:-translate-y-px border border-slate-200 dark:border-zinc-700 text-slate-700 dark:text-zinc-300 px-4 py-2 rounded-xl text-sm font-medium transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Detectar anomalías
            </button>
            <button data-ai="compras" class="ai-btn bg-white dark:bg-zinc-800 hover:bg-slate-50 dark:hover:bg-zinc-700 active:-translate-y-px border border-slate-200 dark:border-zinc-700 text-slate-700 dark:text-zinc-300 px-4 py-2 rounded-xl text-sm font-medium transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Asesor de compras
            </button>
        </div>

        <div id="ai-loading" class="hidden flex items-center gap-3 text-slate-500 dark:text-zinc-400 text-sm">
            <div class="w-5 h-5 border-2 border-violet-200 dark:border-violet-800 border-t-violet-600 dark:border-t-violet-400 rounded-full animate-spin flex-shrink-0"></div>
            Generando análisis con IA local...
        </div>

        <div id="ai-empty" class="text-sm text-slate-400 dark:text-zinc-500">Selecciona un tipo de análisis arriba.</div>

        <div id="ai-content" class="hidden prose prose-sm dark:prose-invert max-w-none text-slate-700 dark:text-zinc-300"></div>
        <p id="ai-meta" class="hidden text-xs text-slate-400 dark:text-zinc-500 mt-3"></p>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-6 border-b border-slate-200 dark:border-zinc-800 overflow-x-auto">
        <button data-tab="transacciones" class="tab-btn pb-3 text-sm font-semibold border-b-2 border-amber-500 text-amber-600 dark:text-amber-400 transition-colors whitespace-nowrap">Transacciones</button>
        <button data-tab="cuentas" class="tab-btn pb-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 dark:text-zinc-500 hover:text-slate-700 dark:hover:text-zinc-300 transition-colors whitespace-nowrap">Cuentas</button>
        <button data-tab="compras" class="tab-btn pb-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 dark:text-zinc-500 hover:text-slate-700 dark:hover:text-zinc-300 transition-colors whitespace-nowrap">Compras</button>
        <button data-tab="proveedores" class="tab-btn pb-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 dark:text-zinc-500 hover:text-slate-700 dark:hover:text-zinc-300 transition-colors whitespace-nowrap">Proveedores</button>
        <button data-tab="cxc" class="tab-btn pb-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 dark:text-zinc-500 hover:text-slate-700 dark:hover:text-zinc-300 transition-colors whitespace-nowrap">Por cobrar</button>
        <button data-tab="cxp" class="tab-btn pb-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 dark:text-zinc-500 hover:text-slate-700 dark:hover:text-zinc-300 transition-colors whitespace-nowrap">Por pagar</button>
        <button data-tab="presupuestos" class="tab-btn pb-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 dark:text-zinc-500 hover:text-slate-700 dark:hover:text-zinc-300 transition-colors whitespace-nowrap">Presupuestos</button>
        <button data-tab="devoluciones" class="tab-btn pb-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 dark:text-zinc-500 hover:text-slate-700 dark:hover:text-zinc-300 transition-colors whitespace-nowrap">Devoluciones</button>
        <button data-tab="conciliacion" class="tab-btn pb-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 dark:text-zinc-500 hover:text-slate-700 dark:hover:text-zinc-300 transition-colors whitespace-nowrap">Conciliación</button>
        <button data-tab="reportes" class="tab-btn pb-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 dark:text-zinc-500 hover:text-slate-700 dark:hover:text-zinc-300 transition-colors whitespace-nowrap">Reportes</button>
        <button data-tab="activos" class="tab-btn pb-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 dark:text-zinc-500 hover:text-slate-700 dark:hover:text-zinc-300 transition-colors whitespace-nowrap">Activos Fijos</button>
    </div>

    @php $th = 'px-5 py-3 text-[11px] font-semibold text-slate-400 dark:text-zinc-500 uppercase tracking-widest'; @endphp

    {{-- TAB: TRANSACCIONES --}}
    <div id="tab-transacciones" class="tab-content bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 dark:bg-zinc-800/60 border-b border-slate-200 dark:border-zinc-700">
                    <tr>
                        <th class="{{ $th }}">Fecha</th>
                        <th class="{{ $th }}">Tipo</th>
                        <th class="{{ $th }}">Categoría</th>
                        <th class="{{ $th }}">Cuenta</th>
                        <th class="{{ $th }}">Descripción</th>
                        <th class="{{ $th }} text-right">Total</th>
                        <th class="{{ $th }}"></th>
                    </tr>
                </thead>
                <tbody id="tbody-transacciones" class="divide-y divide-slate-100 dark:divide-zinc-800">
                    <tr><td colspan="7" class="px-5 py-6 text-center text-slate-400 dark:text-zinc-500">Cargando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- TAB: CUENTAS --}}
    <div id="tab-cuentas" class="tab-content hidden grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="text-slate-400 dark:text-zinc-500 text-sm">Cargando...</div>
    </div>

    {{-- TAB: COMPRAS --}}
    <div id="tab-compras" class="tab-content hidden space-y-4">
        <div class="flex justify-between items-center">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-zinc-300">Órdenes de compra</h3>
            <button id="btn-nueva-compra" class="bg-amber-600 hover:bg-amber-700 active:-translate-y-px text-white px-4 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Nueva compra
            </button>
        </div>
        <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 dark:bg-zinc-800/60 border-b border-slate-200 dark:border-zinc-700">
                        <tr>
                            <th class="{{ $th }}">Folio</th>
                            <th class="{{ $th }}">Fecha</th>
                            <th class="{{ $th }}">Proveedor</th>
                            <th class="{{ $th }}">Forma pago</th>
                            <th class="{{ $th }} text-right">Total</th>
                            <th class="{{ $th }}">Estado</th>
                            <th class="{{ $th }} text-right"></th>
                        </tr>
                    </thead>
                    <tbody id="tbody-compras" class="divide-y divide-slate-100 dark:divide-zinc-800"><tr><td colspan="7" class="px-5 py-6 text-center text-slate-400 dark:text-zinc-500">Cargando...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB: PROVEEDORES --}}
    <div id="tab-proveedores" class="tab-content hidden space-y-4">
        <div class="flex justify-between items-center">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-zinc-300">Catálogo de proveedores</h3>
            <button id="btn-nuevo-proveedor" class="bg-amber-600 hover:bg-amber-700 active:-translate-y-px text-white px-4 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Nuevo proveedor
            </button>
        </div>
        <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 dark:bg-zinc-800/60 border-b border-slate-200 dark:border-zinc-700">
                        <tr>
                            <th class="{{ $th }}">Nombre</th>
                            <th class="{{ $th }}">RFC</th>
                            <th class="{{ $th }}">Contacto</th>
                            <th class="{{ $th }}">Teléfono</th>
                            <th class="{{ $th }}">Email</th>
                            <th class="{{ $th }} text-center">Días crédito</th>
                            <th class="{{ $th }} text-right"></th>
                        </tr>
                    </thead>
                    <tbody id="tbody-proveedores" class="divide-y divide-slate-100 dark:divide-zinc-800"><tr><td colspan="7" class="px-5 py-6 text-center text-slate-400 dark:text-zinc-500">Cargando...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB: CXC --}}
    <div id="tab-cxc" class="tab-content hidden bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 dark:bg-zinc-800/60 border-b border-slate-200 dark:border-zinc-700">
                    <tr>
                        <th class="{{ $th }}">Cliente</th>
                        <th class="{{ $th }}">Emisión</th>
                        <th class="{{ $th }}">Vencimiento</th>
                        <th class="{{ $th }} text-right">Total</th>
                        <th class="{{ $th }} text-right">Saldo</th>
                        <th class="{{ $th }}">Estado</th>
                        <th class="{{ $th }}"></th>
                    </tr>
                </thead>
                <tbody id="tbody-cxc" class="divide-y divide-slate-100 dark:divide-zinc-800"><tr><td colspan="7" class="px-5 py-6 text-center text-slate-400 dark:text-zinc-500">Cargando...</td></tr></tbody>
            </table>
        </div>
    </div>

    {{-- TAB: CXP --}}
    <div id="tab-cxp" class="tab-content hidden bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 dark:bg-zinc-800/60 border-b border-slate-200 dark:border-zinc-700">
                    <tr>
                        <th class="{{ $th }}">Proveedor</th>
                        <th class="{{ $th }}">Emisión</th>
                        <th class="{{ $th }}">Vencimiento</th>
                        <th class="{{ $th }} text-right">Total</th>
                        <th class="{{ $th }} text-right">Saldo</th>
                        <th class="{{ $th }}">Estado</th>
                        <th class="{{ $th }}"></th>
                    </tr>
                </thead>
                <tbody id="tbody-cxp" class="divide-y divide-slate-100 dark:divide-zinc-800"><tr><td colspan="7" class="px-5 py-6 text-center text-slate-400 dark:text-zinc-500">Cargando...</td></tr></tbody>
            </table>
        </div>
    </div>

    {{-- TAB: DEVOLUCIONES --}}
    <div id="tab-devoluciones" class="tab-content hidden space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Devoluciones de Compra --}}
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <h3 class="text-sm font-semibold text-slate-700 dark:text-zinc-300">Devoluciones de Compra</h3>
                    <button id="btn-nueva-dev-compra" class="bg-rose-600 hover:bg-rose-700 active:-translate-y-px text-white px-3 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Nueva devolución
                    </button>
                </div>
                <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 dark:bg-zinc-800/60 border-b border-slate-200 dark:border-zinc-700">
                                <tr>
                                    <th class="{{ $th ?? 'px-4 py-3 text-[11px] font-semibold text-slate-400 uppercase tracking-widest' }}">Proveedor</th>
                                    <th class="{{ $th ?? 'px-4 py-3 text-[11px] font-semibold text-slate-400 uppercase tracking-widest' }}">Fecha</th>
                                    <th class="{{ $th ?? 'px-4 py-3 text-[11px] font-semibold text-slate-400 uppercase tracking-widest' }} text-right">Total</th>
                                    <th class="{{ $th ?? 'px-4 py-3 text-[11px] font-semibold text-slate-400 uppercase tracking-widest' }}">Estado</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-dev-compras" class="divide-y divide-slate-100 dark:divide-zinc-800">
                                <tr><td colspan="4" class="px-5 py-6 text-center text-slate-400 dark:text-zinc-500">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            {{-- Devoluciones de Venta --}}
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <h3 class="text-sm font-semibold text-slate-700 dark:text-zinc-300">Devoluciones de Venta</h3>
                    <button id="btn-nueva-dev-venta" class="bg-rose-600 hover:bg-rose-700 active:-translate-y-px text-white px-3 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Nueva devolución
                    </button>
                </div>
                <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 dark:bg-zinc-800/60 border-b border-slate-200 dark:border-zinc-700">
                                <tr>
                                    <th class="{{ $th ?? 'px-4 py-3 text-[11px] font-semibold text-slate-400 uppercase tracking-widest' }}">Venta</th>
                                    <th class="{{ $th ?? 'px-4 py-3 text-[11px] font-semibold text-slate-400 uppercase tracking-widest' }}">Fecha</th>
                                    <th class="{{ $th ?? 'px-4 py-3 text-[11px] font-semibold text-slate-400 uppercase tracking-widest' }} text-right">Total</th>
                                    <th class="{{ $th ?? 'px-4 py-3 text-[11px] font-semibold text-slate-400 uppercase tracking-widest' }}">Estado</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-dev-ventas" class="divide-y divide-slate-100 dark:divide-zinc-800">
                                <tr><td colspan="4" class="px-5 py-6 text-center text-slate-400 dark:text-zinc-500">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TAB: CONCILIACIÓN --}}
    <div id="tab-conciliacion" class="tab-content hidden space-y-4">
        <div class="flex justify-between items-center">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-zinc-300">Conciliaciones bancarias</h3>
            <button id="btn-nueva-conciliacion" class="bg-amber-600 hover:bg-amber-700 active:-translate-y-px text-white px-4 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Nueva conciliación
            </button>
        </div>
        <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 dark:bg-zinc-800/60 border-b border-slate-200 dark:border-zinc-700">
                        <tr>
                            <th class="{{ $th ?? 'px-4 py-3 text-[11px] font-semibold text-slate-400 uppercase tracking-widest' }}">Cuenta</th>
                            <th class="{{ $th ?? 'px-4 py-3 text-[11px] font-semibold text-slate-400 uppercase tracking-widest' }}">Período</th>
                            <th class="{{ $th ?? 'px-4 py-3 text-[11px] font-semibold text-slate-400 uppercase tracking-widest' }} text-right">Saldo banco</th>
                            <th class="{{ $th ?? 'px-4 py-3 text-[11px] font-semibold text-slate-400 uppercase tracking-widest' }} text-right">Saldo sistema</th>
                            <th class="{{ $th ?? 'px-4 py-3 text-[11px] font-semibold text-slate-400 uppercase tracking-widest' }} text-right">Diferencia</th>
                            <th class="{{ $th ?? 'px-4 py-3 text-[11px] font-semibold text-slate-400 uppercase tracking-widest' }}">Estado</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-conciliaciones" class="divide-y divide-slate-100 dark:divide-zinc-800">
                        <tr><td colspan="6" class="px-5 py-6 text-center text-slate-400 dark:text-zinc-500">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB: REPORTES --}}
    <div id="tab-reportes" class="tab-content hidden space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1">Desde</label>
                <input type="date" id="rep-desde" class="w-full rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1">Hasta</label>
                <input type="date" id="rep-hasta" class="w-full rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm px-3 py-2">
            </div>
            <div class="flex items-end gap-2">
                <button id="btn-rep-pyg" class="flex-1 bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-all">Ver P&G</button>
                <button id="btn-rep-flujo" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-all">Flujo Caja</button>
            </div>
        </div>

        {{-- Estado de Resultados --}}
        <div id="rep-pyg-container" class="hidden space-y-4">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-zinc-300">Estado de Resultados (P&G)</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200/70 dark:border-emerald-800/40 rounded-2xl p-4">
                    <p class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">Ingresos</p>
                    <p id="rep-pyg-ingresos" class="text-2xl font-mono font-semibold text-emerald-700 dark:text-emerald-300 mt-2">—</p>
                </div>
                <div class="bg-rose-50 dark:bg-rose-900/20 border border-rose-200/70 dark:border-rose-800/40 rounded-2xl p-4">
                    <p class="text-[11px] font-semibold text-rose-600 dark:text-rose-400 uppercase tracking-widest">Egresos</p>
                    <p id="rep-pyg-egresos" class="text-2xl font-mono font-semibold text-rose-700 dark:text-rose-300 mt-2">—</p>
                </div>
                <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl p-4">
                    <p class="text-[11px] font-semibold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">Utilidad</p>
                    <p id="rep-pyg-utilidad" class="text-2xl font-mono font-semibold text-slate-900 dark:text-zinc-50 mt-2">—</p>
                    <p id="rep-pyg-margen" class="text-xs font-mono text-slate-400 mt-1">Margen —%</p>
                </div>
            </div>
        </div>

        {{-- Aging CxC / CxP --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-sm font-semibold text-slate-700 dark:text-zinc-300">Antigüedad CxC</h3>
                    <button id="btn-rep-aging-cxc" class="text-xs font-semibold text-amber-600 hover:text-amber-700">Actualizar</button>
                </div>
                <div id="rep-aging-cxc" class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl p-4 text-sm text-slate-400 dark:text-zinc-500">
                    Haz clic en Actualizar.
                </div>
            </div>
            <div>
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-sm font-semibold text-slate-700 dark:text-zinc-300">Antigüedad CxP</h3>
                    <button id="btn-rep-aging-cxp" class="text-xs font-semibold text-amber-600 hover:text-amber-700">Actualizar</button>
                </div>
                <div id="rep-aging-cxp" class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl p-4 text-sm text-slate-400 dark:text-zinc-500">
                    Haz clic en Actualizar.
                </div>
            </div>
        </div>
    </div>

    {{-- TAB: ACTIVOS FIJOS --}}
    <div id="tab-activos" class="tab-content hidden space-y-4">
        <div class="flex justify-between items-center">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-zinc-300">Activos fijos</h3>
            <button id="btn-nuevo-activo" class="bg-amber-600 hover:bg-amber-700 active:-translate-y-px text-white px-4 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Nuevo activo
            </button>
        </div>
        <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 dark:bg-zinc-800/60 border-b border-slate-200 dark:border-zinc-700">
                        <tr>
                            <th class="{{ $th ?? 'px-4 py-3 text-[11px] font-semibold text-slate-400 uppercase tracking-widest' }}">Nombre</th>
                            <th class="{{ $th ?? 'px-4 py-3 text-[11px] font-semibold text-slate-400 uppercase tracking-widest' }}">Categoría</th>
                            <th class="{{ $th ?? 'px-4 py-3 text-[11px] font-semibold text-slate-400 uppercase tracking-widest' }} text-right">Costo Orig.</th>
                            <th class="{{ $th ?? 'px-4 py-3 text-[11px] font-semibold text-slate-400 uppercase tracking-widest' }} text-right">Valor Libro</th>
                            <th class="{{ $th ?? 'px-4 py-3 text-[11px] font-semibold text-slate-400 uppercase tracking-widest' }}">Método</th>
                            <th class="{{ $th ?? 'px-4 py-3 text-[11px] font-semibold text-slate-400 uppercase tracking-widest' }}">Estado</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-activos" class="divide-y divide-slate-100 dark:divide-zinc-800">
                        <tr><td colspan="6" class="px-5 py-6 text-center text-slate-400 dark:text-zinc-500">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @php $fi = 'w-full rounded-xl border border-slate-200 dark:border-zinc-700 bg-slate-50 dark:bg-zinc-800 text-slate-800 dark:text-zinc-100 text-sm'; @endphp
    @php $fl = 'block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1'; @endphp
    @php $fb = 'px-5 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-400 hover:bg-slate-50 dark:hover:bg-zinc-800 font-medium text-sm transition-colors'; @endphp

    {{-- MODAL: nueva transacción --}}
    <div id="modal-transaccion" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 flex">
        <div class="absolute inset-0 bg-slate-900/50 dark:bg-black/60 backdrop-blur-sm modal-close" data-modal="modal-transaccion"></div>
        <div class="relative bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-100 dark:border-zinc-800">
            <div class="p-6 sm:p-8">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xl font-bold text-slate-800 dark:text-zinc-50">Registrar transacción</h3>
                    <button type="button" class="modal-close text-slate-400 dark:text-zinc-500 hover:text-slate-600 dark:hover:text-zinc-300" data-modal="modal-transaccion">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="form-transaccion" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="{{ $fl }}">Tipo</label>
                            <select name="tipo" required class="{{ $fi }}">
                                <option value="ingreso">Ingreso</option>
                                <option value="egreso">Egreso</option>
                            </select>
                        </div>
                        <div>
                            <label class="{{ $fl }}">Fecha</label>
                            <input type="date" name="fecha" required class="{{ $fi }}">
                        </div>
                        <div>
                            <label class="{{ $fl }}">Cuenta</label>
                            <select name="cuenta_id" id="tx-cuenta" required class="{{ $fi }}"></select>
                        </div>
                        <div>
                            <label class="{{ $fl }}">Categoría</label>
                            <select name="categoria_id" id="tx-categoria" class="{{ $fi }}"></select>
                        </div>
                        <div>
                            <label class="{{ $fl }}">Moneda</label>
                            <select name="moneda_id" id="tx-moneda" required class="{{ $fi }}"></select>
                        </div>
                        <div>
                            <label class="{{ $fl }}">Subtotal</label>
                            <input type="number" step="0.01" min="0" name="subtotal" required class="{{ $fi }}">
                        </div>
                        <div class="col-span-2">
                            <label class="{{ $fl }}">Impuesto (opcional)</label>
                            <select name="impuesto_id" id="tx-impuesto" class="{{ $fi }}">
                                <option value="">Sin impuesto</option>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="{{ $fl }}">Descripción</label>
                            <input type="text" name="descripcion" maxlength="255" class="{{ $fi }}">
                        </div>
                        <div class="col-span-2">
                            <label class="{{ $fl }}">Referencia (opcional)</label>
                            <input type="text" name="referencia" maxlength="100" class="{{ $fi }}">
                        </div>
                    </div>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" class="modal-close {{ $fb }}" data-modal="modal-transaccion">Cancelar</button>
                        <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition-all">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL: transferencia --}}
    <div id="modal-transferir" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 flex">
        <div class="absolute inset-0 bg-slate-900/50 dark:bg-black/60 backdrop-blur-sm modal-close" data-modal="modal-transferir"></div>
        <div class="relative bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100 dark:border-zinc-800">
            <div class="p-6 sm:p-8">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xl font-bold text-slate-800 dark:text-zinc-50">Transferir entre cuentas</h3>
                    <button type="button" class="modal-close text-slate-400 dark:text-zinc-500 hover:text-slate-600 dark:hover:text-zinc-300" data-modal="modal-transferir">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="form-transferir" class="space-y-4">
                    <div>
                        <label class="{{ $fl }}">Desde</label>
                        <select name="cuenta_origen_id" id="tr-origen" required class="{{ $fi }}"></select>
                    </div>
                    <div>
                        <label class="{{ $fl }}">Hacia</label>
                        <select name="cuenta_destino_id" id="tr-destino" required class="{{ $fi }}"></select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="{{ $fl }}">Monto</label>
                            <input type="number" step="0.01" min="0.01" name="monto" required class="{{ $fi }}">
                        </div>
                        <div>
                            <label class="{{ $fl }}">Fecha</label>
                            <input type="date" name="fecha" required class="{{ $fi }}">
                        </div>
                    </div>
                    <div>
                        <label class="{{ $fl }}">Descripción (opcional)</label>
                        <input type="text" name="descripcion" class="{{ $fi }}">
                    </div>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" class="modal-close {{ $fb }}" data-modal="modal-transferir">Cancelar</button>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition-all">Transferir</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- TAB: PRESUPUESTOS --}}
    <div id="tab-presupuestos" class="tab-content hidden space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-zinc-300">Control de presupuestos</h3>
                <div class="flex items-center gap-2">
                    <select id="pre-anio" class="rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-700 dark:text-zinc-200 text-xs"></select>
                    <select id="pre-mes" class="rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-700 dark:text-zinc-200 text-xs">
                        <option value="1">Enero</option><option value="2">Febrero</option><option value="3">Marzo</option>
                        <option value="4">Abril</option><option value="5">Mayo</option><option value="6">Junio</option>
                        <option value="7">Julio</option><option value="8">Agosto</option><option value="9">Septiembre</option>
                        <option value="10">Octubre</option><option value="11">Noviembre</option><option value="12">Diciembre</option>
                    </select>
                </div>
            </div>
            <button id="btn-nuevo-presupuesto" class="bg-amber-600 hover:bg-amber-700 active:-translate-y-px text-white px-4 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Nuevo presupuesto
            </button>
        </div>
        <div id="presupuestos-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="text-slate-400 dark:text-zinc-500 text-sm col-span-full text-center py-6">Cargando...</div>
        </div>
    </div>

    {{-- MODAL: nuevo presupuesto --}}
    <div id="modal-presupuesto" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 flex">
        <div class="absolute inset-0 bg-slate-900/50 dark:bg-black/60 backdrop-blur-sm modal-close" data-modal="modal-presupuesto"></div>
        <div class="relative bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100 dark:border-zinc-800">
            <div class="p-6 sm:p-8">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xl font-bold text-slate-800 dark:text-zinc-50">Nuevo presupuesto</h3>
                    <button type="button" class="modal-close text-slate-400 dark:text-zinc-500 hover:text-slate-600 dark:hover:text-zinc-300" data-modal="modal-presupuesto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="form-presupuesto" class="space-y-4" autocomplete="off">
                    <div>
                        <label class="{{ $fl }}">Categoría <span class="text-rose-500">*</span></label>
                        <select name="categoria_id" id="pp-categoria" required class="{{ $fi }}"></select>
                        <p class="text-xs text-slate-400 dark:text-zinc-500 mt-1">Solo categorías de tipo egreso</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="{{ $fl }}">Año <span class="text-rose-500">*</span></label>
                            <input type="number" name="anio" min="2020" max="2100" required class="{{ $fi }}">
                        </div>
                        <div>
                            <label class="{{ $fl }}">Mes <span class="text-rose-500">*</span></label>
                            <select name="mes" required class="{{ $fi }}">
                                <option value="1">Enero</option><option value="2">Febrero</option><option value="3">Marzo</option>
                                <option value="4">Abril</option><option value="5">Mayo</option><option value="6">Junio</option>
                                <option value="7">Julio</option><option value="8">Agosto</option><option value="9">Septiembre</option>
                                <option value="10">Octubre</option><option value="11">Noviembre</option><option value="12">Diciembre</option>
                            </select>
                        </div>
                        <div>
                            <label class="{{ $fl }}">Moneda <span class="text-rose-500">*</span></label>
                            <select name="moneda_id" id="pp-moneda" required class="{{ $fi }}"></select>
                        </div>
                        <div>
                            <label class="{{ $fl }}">Monto límite <span class="text-rose-500">*</span></label>
                            <input type="number" step="0.01" min="0.01" max="99999999" name="monto_limite" required class="{{ $fi }}">
                        </div>
                        <div class="col-span-2">
                            <label class="{{ $fl }}">Alertar al alcanzar (%)</label>
                            <input type="number" name="alerta_pct" min="1" max="100" value="80" class="{{ $fi }}">
                        </div>
                        <div class="col-span-2">
                            <label class="{{ $fl }}">Notas</label>
                            <textarea name="notas" rows="2" maxlength="500" class="{{ $fi }}"></textarea>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400 dark:text-zinc-500">Si ya existe un presupuesto para esa categoría/mes/año, se sobrescribirá.</p>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" class="modal-close {{ $fb }}" data-modal="modal-presupuesto">Cancelar</button>
                        <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition-all">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL: nueva compra --}}
    <div id="modal-compra" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 flex">
        <div class="absolute inset-0 bg-slate-900/50 dark:bg-black/60 backdrop-blur-sm modal-close" data-modal="modal-compra"></div>
        <div class="relative bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl w-full max-w-3xl overflow-hidden border border-slate-100 dark:border-zinc-800 max-h-[90vh] flex flex-col">
            <div class="p-6 sm:p-8 overflow-y-auto">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xl font-bold text-slate-800 dark:text-zinc-50">Nueva orden de compra</h3>
                    <button type="button" class="modal-close text-slate-400 dark:text-zinc-500 hover:text-slate-600 dark:hover:text-zinc-300" data-modal="modal-compra">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="form-compra" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="{{ $fl }}">Proveedor <span class="text-rose-500">*</span></label>
                            <select name="proveedor_id" id="co-proveedor" required class="{{ $fi }}"></select>
                        </div>
                        <div>
                            <label class="{{ $fl }}">Fecha <span class="text-rose-500">*</span></label>
                            <input type="date" name="fecha" required class="{{ $fi }}">
                        </div>
                        <div>
                            <label class="{{ $fl }}">Moneda <span class="text-rose-500">*</span></label>
                            <select name="moneda_id" id="co-moneda" required class="{{ $fi }}"></select>
                        </div>
                        <div>
                            <label class="{{ $fl }}">Tipo de cambio</label>
                            <input type="number" step="0.00000001" min="0.00000001" name="tipo_cambio" value="1" class="{{ $fi }}">
                        </div>
                        <div>
                            <label class="{{ $fl }}">Forma de pago <span class="text-rose-500">*</span></label>
                            <select name="forma_pago" id="co-forma-pago" required class="{{ $fi }}">
                                <option value="contado">Contado</option>
                                <option value="credito">Crédito</option>
                            </select>
                        </div>
                        <div id="co-venc-wrap" class="hidden">
                            <label class="{{ $fl }}">Vencimiento</label>
                            <input type="date" name="fecha_vencimiento" class="{{ $fi }}">
                        </div>
                        <div class="col-span-2">
                            <label class="{{ $fl }}">Referencia</label>
                            <input type="text" name="referencia" maxlength="100" placeholder="Ej: Factura A-1234" class="{{ $fi }}">
                        </div>
                    </div>

                    <div class="border-t border-slate-200 dark:border-zinc-700 pt-4">
                        <div class="flex justify-between items-center mb-3">
                            <h4 class="text-sm font-semibold text-slate-700 dark:text-zinc-300">Productos</h4>
                            <button type="button" id="co-add-item" class="text-xs text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 font-medium flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                Agregar producto
                            </button>
                        </div>
                        <div id="co-items" class="space-y-2"></div>
                    </div>

                    <div class="bg-slate-50 dark:bg-zinc-800 rounded-xl p-3 flex justify-between items-center">
                        <span class="text-sm text-slate-600 dark:text-zinc-400">Total estimado:</span>
                        <span id="co-total" class="text-lg font-mono font-bold text-slate-800 dark:text-zinc-100 tabular-nums">—</span>
                    </div>

                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" class="modal-close {{ $fb }}" data-modal="modal-compra">Cancelar</button>
                        <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition-all">Guardar borrador</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL: recibir compra --}}
    <div id="modal-recibir" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 flex">
        <div class="absolute inset-0 bg-slate-900/50 dark:bg-black/60 backdrop-blur-sm modal-close" data-modal="modal-recibir"></div>
        <div class="relative bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100 dark:border-zinc-800">
            <div class="p-6 sm:p-8">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xl font-bold text-slate-800 dark:text-zinc-50">Recibir mercancía</h3>
                    <button type="button" class="modal-close text-slate-400 dark:text-zinc-500 hover:text-slate-600 dark:hover:text-zinc-300" data-modal="modal-recibir">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="form-recibir" class="space-y-4">
                    <input type="hidden" id="recibir-id">
                    <div id="recibir-info" class="bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800/40 rounded-xl p-3 text-sm text-amber-800 dark:text-amber-300"></div>
                    <div id="recibir-cuenta-wrap">
                        <label class="{{ $fl }}">Cuenta de pago</label>
                        <select name="cuenta_id" id="recibir-cuenta" class="{{ $fi }}"></select>
                        <p class="text-xs text-slate-400 dark:text-zinc-500 mt-1">Solo aplica si la compra es de contado.</p>
                    </div>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" class="modal-close {{ $fb }}" data-modal="modal-recibir">Cancelar</button>
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition-all">Confirmar recepción</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL: proveedor --}}
    <div id="modal-proveedor" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 flex">
        <div class="absolute inset-0 bg-slate-900/50 dark:bg-black/60 backdrop-blur-sm modal-close" data-modal="modal-proveedor"></div>
        <div class="relative bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-100 dark:border-zinc-800">
            <div class="p-6 sm:p-8">
                <div class="flex justify-between items-center mb-5">
                    <h3 id="prov-title" class="text-xl font-bold text-slate-800 dark:text-zinc-50">Nuevo proveedor</h3>
                    <button type="button" class="modal-close text-slate-400 dark:text-zinc-500 hover:text-slate-600 dark:hover:text-zinc-300" data-modal="modal-proveedor">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="form-proveedor" class="space-y-4">
                    <input type="hidden" name="_id" id="prov-id">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="{{ $fl }}">Nombre / Razón social <span class="text-rose-500">*</span></label>
                            <input type="text" name="nombre" required maxlength="150" class="{{ $fi }}">
                        </div>
                        <div>
                            <label class="{{ $fl }}">RFC</label>
                            <input type="text" name="rfc" maxlength="20" class="{{ $fi }}">
                        </div>
                        <div>
                            <label class="{{ $fl }}">Días de crédito</label>
                            <input type="number" name="dias_credito" min="0" max="365" value="0" class="{{ $fi }}">
                        </div>
                        <div>
                            <label class="{{ $fl }}">Contacto</label>
                            <input type="text" name="contacto" maxlength="100" class="{{ $fi }}">
                        </div>
                        <div>
                            <label class="{{ $fl }}">Teléfono</label>
                            <input type="text" name="telefono" maxlength="30" class="{{ $fi }}">
                        </div>
                        <div class="col-span-2">
                            <label class="{{ $fl }}">Email</label>
                            <input type="email" name="email" maxlength="150" class="{{ $fi }}">
                        </div>
                        <div class="col-span-2">
                            <label class="{{ $fl }}">Moneda preferida</label>
                            <select name="moneda_id" id="prov-moneda" class="{{ $fi }}"></select>
                        </div>
                        <div class="col-span-2">
                            <label class="{{ $fl }}">Dirección</label>
                            <textarea name="direccion" rows="2" maxlength="500" class="{{ $fi }}"></textarea>
                        </div>
                        <div class="col-span-2">
                            <label class="{{ $fl }}">Notas</label>
                            <textarea name="notas" rows="2" maxlength="1000" class="{{ $fi }}"></textarea>
                        </div>
                    </div>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" class="modal-close {{ $fb }}" data-modal="modal-proveedor">Cancelar</button>
                        <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition-all">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL: pago CxC/CxP --}}
    <div id="modal-pago" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 flex">
        <div class="absolute inset-0 bg-slate-900/50 dark:bg-black/60 backdrop-blur-sm modal-close" data-modal="modal-pago"></div>
        <div class="relative bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100 dark:border-zinc-800">
            <div class="p-6 sm:p-8">
                <div class="flex justify-between items-center mb-5">
                    <h3 id="pago-title" class="text-xl font-bold text-slate-800 dark:text-zinc-50">Registrar pago</h3>
                    <button type="button" class="modal-close text-slate-400 dark:text-zinc-500 hover:text-slate-600 dark:hover:text-zinc-300" data-modal="modal-pago">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="form-pago" class="space-y-4">
                    <input type="hidden" name="_modo" id="pago-modo">
                    <input type="hidden" name="_id" id="pago-id">
                    <div id="pago-info" class="bg-slate-50 dark:bg-zinc-800 rounded-xl p-3 text-sm text-slate-600 dark:text-zinc-400"></div>
                    <div>
                        <label class="{{ $fl }}">Cuenta</label>
                        <select name="cuenta_id" id="pago-cuenta" required class="{{ $fi }}"></select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="{{ $fl }}">Monto</label>
                            <input type="number" step="0.01" min="0.01" name="monto" required class="{{ $fi }}">
                        </div>
                        <div>
                            <label class="{{ $fl }}">Fecha</label>
                            <input type="date" name="fecha" required class="{{ $fi }}">
                        </div>
                    </div>
                    <div>
                        <label class="{{ $fl }}">Referencia</label>
                        <input type="text" name="referencia" class="{{ $fi }}">
                    </div>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" class="modal-close {{ $fb }}" data-modal="modal-pago">Cancelar</button>
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 active:-translate-y-px text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition-all">Registrar pago</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Toasts --}}
    <div id="toast-container" class="fixed bottom-6 right-6 z-[300] flex flex-col gap-2 pointer-events-none"></div>
</div>
@endsection

@section('scripts')
    @vite(['resources/js/finance.js'])
@endsection
