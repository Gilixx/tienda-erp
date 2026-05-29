@extends('layouts.app')
@section('title', 'Sistema de Finanzas')
@section('page-title', 'Finanzas')

@section('content')
<div id="finance-app" class="space-y-6">

    {{-- Controles superiores --}}
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-slate-600">Periodo:</label>
            <select id="fin-period" class="rounded-xl border-slate-200 bg-white text-sm focus:border-orange-500 focus:ring-orange-500">
                <option value="7">Últimos 7 días</option>
                <option value="30" selected>Últimos 30 días</option>
                <option value="90">Últimos 90 días</option>
                <option value="180">Últimos 6 meses</option>
                <option value="365">Último año</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button id="btn-nueva-transaccion" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2.5 rounded-xl font-medium text-sm shadow-sm transition-all focus:ring-4 focus:ring-orange-100 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Nueva transacción
            </button>
            <button id="btn-transferir" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-4 py-2.5 rounded-xl font-medium text-sm shadow-sm transition-all focus:ring-4 focus:ring-indigo-100 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                Transferir
            </button>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200/70 border-l-[3px] border-l-emerald-500 rounded-2xl p-4">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-widest">Ingresos</p>
            <p id="kpi-ingresos" class="text-2xl font-mono font-semibold text-emerald-600 mt-2 tabular-nums">—</p>
        </div>
        <div class="bg-white border border-slate-200/70 border-l-[3px] border-l-rose-500 rounded-2xl p-4">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-widest">Egresos</p>
            <p id="kpi-egresos" class="text-2xl font-mono font-semibold text-rose-600 mt-2 tabular-nums">—</p>
        </div>
        <div class="bg-white border border-slate-200/70 border-l-[3px] border-l-amber-500 rounded-2xl p-4">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-widest">Utilidad</p>
            <p id="kpi-utilidad" class="text-2xl font-mono font-semibold text-slate-900 mt-2 tabular-nums">—</p>
            <p id="kpi-margen" class="text-xs font-mono text-slate-400 mt-1 tabular-nums">Margen —%</p>
        </div>
        <div class="bg-white border border-slate-200/70 border-l-[3px] border-l-indigo-500 rounded-2xl p-4">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-widest">Saldo total</p>
            <p id="kpi-saldo" class="text-2xl font-mono font-semibold text-indigo-600 mt-2 tabular-nums">—</p>
            <p id="kpi-cuentas-info" class="text-xs font-mono text-slate-400 mt-1 tabular-nums">— cuentas</p>
        </div>
    </div>

    {{-- Alertas CxC / CxP --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white border border-amber-200/70 border-l-[3px] border-l-amber-500 rounded-2xl p-4 flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-widest">Por cobrar</p>
                <p id="cxc-monto" class="text-xl font-mono font-semibold text-amber-700 mt-2 tabular-nums">—</p>
                <p id="cxc-info" class="text-xs font-mono text-slate-400 mt-1 tabular-nums">—</p>
            </div>
            <svg class="w-9 h-9 text-amber-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
        </div>
        <div class="bg-white border border-rose-200/70 border-l-[3px] border-l-rose-500 rounded-2xl p-4 flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-widest">Por pagar</p>
                <p id="cxp-monto" class="text-xl font-mono font-semibold text-rose-700 mt-2 tabular-nums">—</p>
                <p id="cxp-info" class="text-xs font-mono text-slate-400 mt-1 tabular-nums">—</p>
            </div>
            <svg class="w-9 h-9 text-rose-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
        </div>
    </div>

    {{-- Gráficas --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="bg-white border border-slate-200/70 rounded-2xl p-5">
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-slate-800">Flujo diario</h3>
                <p class="text-xs text-slate-400 mt-0.5">Ingresos vs egresos por día</p>
            </div>
            <div class="relative h-64"><canvas id="chart-flow"></canvas></div>
        </div>
        <div class="bg-white border border-slate-200/70 rounded-2xl p-5">
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-slate-800">Distribución de egresos</h3>
                <p class="text-xs text-slate-400 mt-0.5">Por categoría en el periodo</p>
            </div>
            <div class="relative h-64"><canvas id="chart-cats"></canvas></div>
        </div>
    </div>

    {{-- Asesor IA --}}
    <div class="bg-white border border-slate-200/70 border-l-[3px] border-l-violet-500 rounded-2xl p-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-violet-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Asesor financiero IA</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Análisis local con Ollama · sin enviar datos a la nube</p>
                </div>
            </div>
            <button id="btn-ai-refresh" class="hidden text-xs text-violet-600 hover:text-violet-800 font-semibold">Regenerar</button>
        </div>

        <div class="flex flex-wrap gap-2 mb-5">
            <button data-ai="informe" class="ai-btn bg-violet-600 hover:bg-violet-700 active:-translate-y-px text-white px-4 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Informe ejecutivo
            </button>
            <button data-ai="forecast" class="ai-btn bg-white hover:bg-slate-50 active:-translate-y-px border border-slate-200 text-slate-700 px-4 py-2 rounded-xl text-sm font-medium transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
                Proyección de flujo
            </button>
            <button data-ai="anomalias" class="ai-btn bg-white hover:bg-slate-50 active:-translate-y-px border border-slate-200 text-slate-700 px-4 py-2 rounded-xl text-sm font-medium transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Detectar anomalías
            </button>
            <button data-ai="compras" class="ai-btn bg-white hover:bg-slate-50 active:-translate-y-px border border-slate-200 text-slate-700 px-4 py-2 rounded-xl text-sm font-medium transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Asesor de compras
            </button>
        </div>

        <div id="ai-loading" class="hidden flex items-center gap-3 text-slate-500 text-sm">
            <div class="w-5 h-5 border-2 border-violet-200 border-t-violet-600 rounded-full animate-spin flex-shrink-0"></div>
            Generando análisis con IA local...
        </div>

        <div id="ai-empty" class="text-sm text-slate-400">Selecciona un tipo de análisis arriba.</div>

        <div id="ai-content" class="hidden prose prose-sm max-w-none text-slate-700"></div>
        <p id="ai-meta" class="hidden text-xs text-slate-400 mt-3"></p>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-6 border-b border-slate-200">
        <button data-tab="transacciones" class="tab-btn pb-3 text-sm font-semibold border-b-2 border-orange-500 text-orange-600 transition-colors">Transacciones</button>
        <button data-tab="cuentas" class="tab-btn pb-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition-colors">Cuentas</button>
        <button data-tab="compras" class="tab-btn pb-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition-colors">Compras</button>
        <button data-tab="proveedores" class="tab-btn pb-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition-colors">Proveedores</button>
        <button data-tab="cxc" class="tab-btn pb-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition-colors">Por cobrar</button>
        <button data-tab="cxp" class="tab-btn pb-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition-colors">Por pagar</button>
        <button data-tab="presupuestos" class="tab-btn pb-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition-colors">Presupuestos</button>
    </div>

    {{-- TAB: TRANSACCIONES --}}
    <div id="tab-transacciones" class="tab-content bg-white border border-slate-200/60 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3">Fecha</th>
                        <th class="px-5 py-3">Tipo</th>
                        <th class="px-5 py-3">Categoría</th>
                        <th class="px-5 py-3">Cuenta</th>
                        <th class="px-5 py-3">Descripción</th>
                        <th class="px-5 py-3 text-right">Total</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody id="tbody-transacciones">
                    <tr><td colspan="7" class="px-5 py-6 text-center text-slate-400">Cargando…</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- TAB: CUENTAS --}}
    <div id="tab-cuentas" class="tab-content hidden grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="text-slate-400 text-sm">Cargando…</div>
    </div>

    {{-- TAB: COMPRAS --}}
    <div id="tab-compras" class="tab-content hidden space-y-4">
        <div class="flex justify-between items-center">
            <h3 class="text-sm font-semibold text-slate-700">Órdenes de compra</h3>
            <button id="btn-nueva-compra" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-xl text-sm font-medium flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Nueva compra
            </button>
        </div>
        <div class="bg-white border border-slate-200/60 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-5 py-3">Folio</th>
                            <th class="px-5 py-3">Fecha</th>
                            <th class="px-5 py-3">Proveedor</th>
                            <th class="px-5 py-3">Forma pago</th>
                            <th class="px-5 py-3 text-right">Total</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3 text-right"></th>
                        </tr>
                    </thead>
                    <tbody id="tbody-compras"><tr><td colspan="7" class="px-5 py-6 text-center text-slate-400">Cargando…</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB: PROVEEDORES --}}
    <div id="tab-proveedores" class="tab-content hidden space-y-4">
        <div class="flex justify-between items-center">
            <h3 class="text-sm font-semibold text-slate-700">Catálogo de proveedores</h3>
            <button id="btn-nuevo-proveedor" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-xl text-sm font-medium flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Nuevo proveedor
            </button>
        </div>
        <div class="bg-white border border-slate-200/60 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-5 py-3">Nombre</th>
                            <th class="px-5 py-3">RFC</th>
                            <th class="px-5 py-3">Contacto</th>
                            <th class="px-5 py-3">Teléfono</th>
                            <th class="px-5 py-3">Email</th>
                            <th class="px-5 py-3 text-center">Días crédito</th>
                            <th class="px-5 py-3 text-right"></th>
                        </tr>
                    </thead>
                    <tbody id="tbody-proveedores"><tr><td colspan="7" class="px-5 py-6 text-center text-slate-400">Cargando…</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB: CXC --}}
    <div id="tab-cxc" class="tab-content hidden bg-white border border-slate-200/60 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3">Cliente</th>
                        <th class="px-5 py-3">Emisión</th>
                        <th class="px-5 py-3">Vencimiento</th>
                        <th class="px-5 py-3 text-right">Total</th>
                        <th class="px-5 py-3 text-right">Saldo</th>
                        <th class="px-5 py-3">Estado</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody id="tbody-cxc"><tr><td colspan="7" class="px-5 py-6 text-center text-slate-400">Cargando…</td></tr></tbody>
            </table>
        </div>
    </div>

    {{-- TAB: CXP --}}
    <div id="tab-cxp" class="tab-content hidden bg-white border border-slate-200/60 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3">Proveedor</th>
                        <th class="px-5 py-3">Emisión</th>
                        <th class="px-5 py-3">Vencimiento</th>
                        <th class="px-5 py-3 text-right">Total</th>
                        <th class="px-5 py-3 text-right">Saldo</th>
                        <th class="px-5 py-3">Estado</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody id="tbody-cxp"><tr><td colspan="7" class="px-5 py-6 text-center text-slate-400">Cargando…</td></tr></tbody>
            </table>
        </div>
    </div>

    {{-- MODAL: nueva transacción --}}
    <div id="modal-transaccion" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 flex">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm modal-close" data-modal="modal-transaccion"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-100">
            <div class="p-6 sm:p-8">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xl font-bold text-slate-800">Registrar transacción</h3>
                    <button type="button" class="modal-close text-slate-400 hover:text-slate-600" data-modal="modal-transaccion">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="form-transaccion" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Tipo</label>
                            <select name="tipo" required class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm">
                                <option value="ingreso">Ingreso</option>
                                <option value="egreso">Egreso</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Fecha</label>
                            <input type="date" name="fecha" required class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Cuenta</label>
                            <select name="cuenta_id" id="tx-cuenta" required class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm"></select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Categoría</label>
                            <select name="categoria_id" id="tx-categoria" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm"></select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Moneda</label>
                            <select name="moneda_id" id="tx-moneda" required class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm"></select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Subtotal</label>
                            <input type="number" step="0.01" min="0" name="subtotal" required class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Impuesto (opcional)</label>
                            <select name="impuesto_id" id="tx-impuesto" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm">
                                <option value="">Sin impuesto</option>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Descripción</label>
                            <input type="text" name="descripcion" maxlength="255" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Referencia (opcional)</label>
                            <input type="text" name="referencia" maxlength="100" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm">
                        </div>
                    </div>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" class="modal-close px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-medium text-sm" data-modal="modal-transaccion">Cancelar</button>
                        <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2.5 rounded-xl font-medium text-sm shadow-sm">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL: transferencia --}}
    <div id="modal-transferir" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 flex">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm modal-close" data-modal="modal-transferir"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100">
            <div class="p-6 sm:p-8">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xl font-bold text-slate-800">Transferir entre cuentas</h3>
                    <button type="button" class="modal-close text-slate-400 hover:text-slate-600" data-modal="modal-transferir">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="form-transferir" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Desde</label>
                        <select name="cuenta_origen_id" id="tr-origen" required class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm"></select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Hacia</label>
                        <select name="cuenta_destino_id" id="tr-destino" required class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm"></select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Monto</label>
                            <input type="number" step="0.01" min="0.01" name="monto" required class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Fecha</label>
                            <input type="date" name="fecha" required class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Descripción (opcional)</label>
                        <input type="text" name="descripcion" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm">
                    </div>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" class="modal-close px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-medium text-sm" data-modal="modal-transferir">Cancelar</button>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl font-medium text-sm shadow-sm">Transferir</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- TAB: PRESUPUESTOS --}}
    <div id="tab-presupuestos" class="tab-content hidden space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <h3 class="text-sm font-semibold text-slate-700">Control de presupuestos</h3>
                <div class="flex items-center gap-2">
                    <select id="pre-anio" class="rounded-xl border-slate-200 bg-white text-xs"></select>
                    <select id="pre-mes" class="rounded-xl border-slate-200 bg-white text-xs">
                        <option value="1">Enero</option><option value="2">Febrero</option><option value="3">Marzo</option>
                        <option value="4">Abril</option><option value="5">Mayo</option><option value="6">Junio</option>
                        <option value="7">Julio</option><option value="8">Agosto</option><option value="9">Septiembre</option>
                        <option value="10">Octubre</option><option value="11">Noviembre</option><option value="12">Diciembre</option>
                    </select>
                </div>
            </div>
            <button id="btn-nuevo-presupuesto" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-xl text-sm font-medium flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Nuevo presupuesto
            </button>
        </div>
        <div id="presupuestos-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="text-slate-400 text-sm col-span-full text-center py-6">Cargando…</div>
        </div>
    </div>

    {{-- MODAL: nuevo presupuesto --}}
    <div id="modal-presupuesto" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 flex">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm modal-close" data-modal="modal-presupuesto"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100">
            <div class="p-6 sm:p-8">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xl font-bold text-slate-800">Nuevo presupuesto</h3>
                    <button type="button" class="modal-close text-slate-400 hover:text-slate-600" data-modal="modal-presupuesto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="form-presupuesto" class="space-y-4" autocomplete="off">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Categoría <span class="text-rose-500">*</span></label>
                        <select name="categoria_id" id="pp-categoria" required class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm"></select>
                        <p class="text-xs text-slate-400 mt-1">Solo categorías de tipo egreso</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Año <span class="text-rose-500">*</span></label>
                            <input type="number" name="anio" min="2020" max="2100" required class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Mes <span class="text-rose-500">*</span></label>
                            <select name="mes" required class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm">
                                <option value="1">Enero</option><option value="2">Febrero</option><option value="3">Marzo</option>
                                <option value="4">Abril</option><option value="5">Mayo</option><option value="6">Junio</option>
                                <option value="7">Julio</option><option value="8">Agosto</option><option value="9">Septiembre</option>
                                <option value="10">Octubre</option><option value="11">Noviembre</option><option value="12">Diciembre</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Moneda <span class="text-rose-500">*</span></label>
                            <select name="moneda_id" id="pp-moneda" required class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm"></select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Monto límite <span class="text-rose-500">*</span></label>
                            <input type="number" step="0.01" min="0.01" max="99999999" name="monto_limite" required class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Alertar al alcanzar (%)</label>
                            <input type="number" name="alerta_pct" min="1" max="100" value="80" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Notas</label>
                            <textarea name="notas" rows="2" maxlength="500" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm"></textarea>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400">Si ya existe un presupuesto para esa categoría/mes/año, se sobrescribirá.</p>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" class="modal-close px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-medium text-sm" data-modal="modal-presupuesto">Cancelar</button>
                        <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2.5 rounded-xl font-medium text-sm shadow-sm">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL: nueva compra --}}
    <div id="modal-compra" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 flex">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm modal-close" data-modal="modal-compra"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-3xl overflow-hidden border border-slate-100 max-h-[90vh] flex flex-col">
            <div class="p-6 sm:p-8 overflow-y-auto">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xl font-bold text-slate-800">Nueva orden de compra</h3>
                    <button type="button" class="modal-close text-slate-400 hover:text-slate-600" data-modal="modal-compra">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="form-compra" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Proveedor <span class="text-rose-500">*</span></label>
                            <select name="proveedor_id" id="co-proveedor" required class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm"></select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Fecha <span class="text-rose-500">*</span></label>
                            <input type="date" name="fecha" required class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Moneda <span class="text-rose-500">*</span></label>
                            <select name="moneda_id" id="co-moneda" required class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm"></select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Tipo de cambio</label>
                            <input type="number" step="0.00000001" min="0.00000001" name="tipo_cambio" value="1" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Forma de pago <span class="text-rose-500">*</span></label>
                            <select name="forma_pago" id="co-forma-pago" required class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm">
                                <option value="contado">Contado</option>
                                <option value="credito">Crédito</option>
                            </select>
                        </div>
                        <div id="co-venc-wrap" class="hidden">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Vencimiento</label>
                            <input type="date" name="fecha_vencimiento" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Referencia</label>
                            <input type="text" name="referencia" maxlength="100" placeholder="Ej: Factura A-1234" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm">
                        </div>
                    </div>

                    <div class="border-t border-slate-200 pt-4">
                        <div class="flex justify-between items-center mb-3">
                            <h4 class="text-sm font-semibold text-slate-700">Productos</h4>
                            <button type="button" id="co-add-item" class="text-xs text-orange-600 hover:text-orange-700 font-medium flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Agregar producto
                            </button>
                        </div>
                        <div id="co-items" class="space-y-2"></div>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-3 flex justify-between items-center">
                        <span class="text-sm text-slate-600">Total estimado:</span>
                        <span id="co-total" class="text-lg font-bold text-slate-800">—</span>
                    </div>

                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" class="modal-close px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-medium text-sm" data-modal="modal-compra">Cancelar</button>
                        <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2.5 rounded-xl font-medium text-sm shadow-sm">Guardar borrador</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL: recibir compra --}}
    <div id="modal-recibir" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 flex">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm modal-close" data-modal="modal-recibir"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100">
            <div class="p-6 sm:p-8">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xl font-bold text-slate-800">Recibir mercancía</h3>
                    <button type="button" class="modal-close text-slate-400 hover:text-slate-600" data-modal="modal-recibir">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="form-recibir" class="space-y-4">
                    <input type="hidden" id="recibir-id">
                    <div id="recibir-info" class="bg-amber-50 border border-amber-100 rounded-xl p-3 text-sm text-amber-800"></div>
                    <div id="recibir-cuenta-wrap">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Cuenta de pago</label>
                        <select name="cuenta_id" id="recibir-cuenta" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm"></select>
                        <p class="text-xs text-slate-400 mt-1">Solo aplica si la compra es de contado.</p>
                    </div>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" class="modal-close px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-medium text-sm" data-modal="modal-recibir">Cancelar</button>
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-xl font-medium text-sm shadow-sm">Confirmar recepción</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL: proveedor --}}
    <div id="modal-proveedor" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 flex">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm modal-close" data-modal="modal-proveedor"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-100">
            <div class="p-6 sm:p-8">
                <div class="flex justify-between items-center mb-5">
                    <h3 id="prov-title" class="text-xl font-bold text-slate-800">Nuevo proveedor</h3>
                    <button type="button" class="modal-close text-slate-400 hover:text-slate-600" data-modal="modal-proveedor">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="form-proveedor" class="space-y-4">
                    <input type="hidden" name="_id" id="prov-id">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nombre / Razón social <span class="text-rose-500">*</span></label>
                            <input type="text" name="nombre" required maxlength="150" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">RFC</label>
                            <input type="text" name="rfc" maxlength="20" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Días de crédito</label>
                            <input type="number" name="dias_credito" min="0" max="365" value="0" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Contacto</label>
                            <input type="text" name="contacto" maxlength="100" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Teléfono</label>
                            <input type="text" name="telefono" maxlength="30" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                            <input type="email" name="email" maxlength="150" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Moneda preferida</label>
                            <select name="moneda_id" id="prov-moneda" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm"></select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Dirección</label>
                            <textarea name="direccion" rows="2" maxlength="500" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm"></textarea>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Notas</label>
                            <textarea name="notas" rows="2" maxlength="1000" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm"></textarea>
                        </div>
                    </div>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" class="modal-close px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-medium text-sm" data-modal="modal-proveedor">Cancelar</button>
                        <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2.5 rounded-xl font-medium text-sm shadow-sm">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL: pago CxC/CxP --}}
    <div id="modal-pago" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 flex">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm modal-close" data-modal="modal-pago"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100">
            <div class="p-6 sm:p-8">
                <div class="flex justify-between items-center mb-5">
                    <h3 id="pago-title" class="text-xl font-bold text-slate-800">Registrar pago</h3>
                    <button type="button" class="modal-close text-slate-400 hover:text-slate-600" data-modal="modal-pago">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="form-pago" class="space-y-4">
                    <input type="hidden" name="_modo" id="pago-modo">
                    <input type="hidden" name="_id" id="pago-id">
                    <div id="pago-info" class="bg-slate-50 rounded-xl p-3 text-sm text-slate-600"></div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Cuenta</label>
                        <select name="cuenta_id" id="pago-cuenta" required class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm"></select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Monto</label>
                            <input type="number" step="0.01" min="0.01" name="monto" required class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Fecha</label>
                            <input type="date" name="fecha" required class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Referencia</label>
                        <input type="text" name="referencia" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm">
                    </div>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" class="modal-close px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-medium text-sm" data-modal="modal-pago">Cancelar</button>
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-xl font-medium text-sm shadow-sm">Registrar pago</button>
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
