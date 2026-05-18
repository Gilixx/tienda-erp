@extends('layouts.app')
@section('title', 'Estadísticas IA')
@section('page-title', 'Estadísticas con IA')

@section('content')
<div id="stats-app" class="space-y-6">

    <!-- Controles -->
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-slate-600">Periodo:</label>
            <select id="period-select" class="rounded-xl border-slate-200 bg-white text-sm focus:border-violet-500 focus:ring-violet-500">
                <option value="7">Últimos 7 días</option>
                <option value="30">Últimos 30 días</option>
                <option value="90" selected>Últimos 90 días</option>
                <option value="180">Últimos 6 meses</option>
                <option value="365">Último año</option>
            </select>
        </div>
        <button id="generate-report-btn" class="bg-violet-600 hover:bg-violet-700 text-white px-5 py-2.5 rounded-xl font-medium text-sm shadow-sm transition-all focus:ring-4 focus:ring-violet-100 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            <span id="generate-btn-text">Generar informe IA</span>
        </button>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200/60 rounded-2xl p-4 shadow-sm">
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Ventas Totales</p>
            <p id="kpi-ventas" class="text-2xl font-bold text-slate-800 mt-1">—</p>
        </div>
        <div class="bg-white border border-slate-200/60 rounded-2xl p-4 shadow-sm">
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Ingresos</p>
            <p id="kpi-ingresos" class="text-2xl font-bold text-emerald-600 mt-1">—</p>
        </div>
        <div class="bg-white border border-slate-200/60 rounded-2xl p-4 shadow-sm">
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Ticket Promedio</p>
            <p id="kpi-ticket" class="text-2xl font-bold text-slate-800 mt-1">—</p>
        </div>
        <div class="bg-white border border-slate-200/60 rounded-2xl p-4 shadow-sm">
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Unidades Vendidas</p>
            <p id="kpi-unidades" class="text-2xl font-bold text-slate-800 mt-1">—</p>
        </div>
    </div>

    <!-- Gráficas -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white border border-slate-200/60 rounded-2xl p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Top 10 productos más vendidos</h3>
            <div class="relative h-72"><canvas id="chart-top"></canvas></div>
        </div>
        <div class="bg-white border border-slate-200/60 rounded-2xl p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Ingresos diarios</h3>
            <div class="relative h-72"><canvas id="chart-daily"></canvas></div>
        </div>
    </div>

    <!-- Productos estancados -->
    <div class="bg-white border border-slate-200/60 rounded-2xl p-5 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-700 mb-3">Productos sin movimiento (últimos 30 días)</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-3 py-2">SKU</th>
                        <th class="px-3 py-2">Producto</th>
                        <th class="px-3 py-2">Stock</th>
                        <th class="px-3 py-2">Última venta</th>
                        <th class="px-3 py-2">Días sin venta</th>
                    </tr>
                </thead>
                <tbody id="stale-tbody">
                    <tr><td colspan="5" class="px-3 py-4 text-center text-slate-400">Cargando…</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Informe IA -->
    <div class="bg-gradient-to-br from-violet-50 to-white border border-violet-100 rounded-2xl p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-violet-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-800">Informe generado por IA</h3>
                    <p id="report-meta" class="text-xs text-slate-500">Genera un informe para ver análisis y recomendaciones.</p>
                </div>
            </div>
            <button id="refresh-report-btn" class="hidden text-xs text-violet-600 hover:text-violet-800 font-medium">↻ Regenerar</button>
        </div>

        <div id="report-loading" class="hidden flex flex-col items-center py-10">
            <svg class="animate-spin h-7 w-7 text-violet-600 mb-3" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
            <p class="text-sm text-slate-500">La IA está analizando tus datos… (puede tardar 20-60s)</p>
        </div>

        <div id="report-content" class="prose prose-sm max-w-none text-slate-700 hidden"></div>

        <div id="report-empty" class="text-center py-10 text-slate-400 text-sm">
            <p>Presiona <strong class="text-violet-700">"Generar informe IA"</strong> para obtener un análisis personalizado de tus ventas.</p>
        </div>
    </div>

    <!-- Toast -->
    <div id="toast-container" class="fixed bottom-6 right-6 z-[300] flex flex-col gap-2 pointer-events-none"></div>
</div>
@endsection

@section('scripts')
    @vite(['resources/js/statistics.js'])
@endsection
