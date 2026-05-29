@extends('layouts.app')
@section('title', 'Estadísticas IA')
@section('page-title', 'Estadísticas con IA')

@section('content')
<div id="stats-app" class="space-y-6">

    <!-- Controles -->
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <div class="flex items-center gap-2.5">
            <label class="text-xs font-semibold text-slate-500 uppercase tracking-widest">Periodo</label>
            <select id="period-select" class="rounded-lg border-slate-200 bg-white text-sm focus:border-violet-500 focus:ring-violet-500 font-medium text-slate-700">
                <option value="7">Últimos 7 días</option>
                <option value="30">Últimos 30 días</option>
                <option value="90" selected>Últimos 90 días</option>
                <option value="180">Últimos 6 meses</option>
                <option value="365">Último año</option>
            </select>
        </div>
        <button id="generate-report-btn"
            class="bg-violet-600 hover:bg-violet-700 active:-translate-y-px text-white px-5 py-2.5 rounded-xl font-semibold text-sm transition-all focus:ring-4 focus:ring-violet-100 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
            <span id="generate-btn-text">Generar informe IA</span>
        </button>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200/70 border-l-[3px] border-l-violet-500 rounded-2xl p-4">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-widest">Ventas Totales</p>
            <p id="kpi-ventas" class="text-2xl font-mono font-semibold text-slate-900 mt-2 tabular-nums">—</p>
        </div>
        <div class="bg-white border border-slate-200/70 border-l-[3px] border-l-emerald-500 rounded-2xl p-4">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-widest">Ingresos</p>
            <p id="kpi-ingresos" class="text-2xl font-mono font-semibold text-emerald-600 mt-2 tabular-nums">—</p>
        </div>
        <div class="bg-white border border-slate-200/70 border-l-[3px] border-l-slate-400 rounded-2xl p-4">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-widest">Ticket Promedio</p>
            <p id="kpi-ticket" class="text-2xl font-mono font-semibold text-slate-900 mt-2 tabular-nums">—</p>
        </div>
        <div class="bg-white border border-slate-200/70 border-l-[3px] border-l-slate-400 rounded-2xl p-4">
            <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-widest">Unidades Vendidas</p>
            <p id="kpi-unidades" class="text-2xl font-mono font-semibold text-slate-900 mt-2 tabular-nums">—</p>
        </div>
    </div>

    <!-- Gráficas -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        <!-- Top 10 productos -->
        <div class="bg-white border border-slate-200/70 rounded-2xl p-5">
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-slate-800">Top 10 productos más vendidos</h3>
                <p class="text-xs text-slate-400 mt-0.5">Por unidades en el periodo seleccionado</p>
            </div>
            <div class="relative h-64">
                <canvas id="chart-top"></canvas>
            </div>
        </div>

        <!-- Ingresos diarios -->
        <div class="bg-white border border-slate-200/70 rounded-2xl p-5">
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-slate-800">Ingresos diarios</h3>
                <p class="text-xs text-slate-400 mt-0.5">Evolución de ingresos por día</p>
            </div>
            <div class="relative h-64">
                <canvas id="chart-daily"></canvas>
            </div>
        </div>
    </div>

    <!-- Productos estancados -->
    <div class="bg-white border border-slate-200/70 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="text-sm font-semibold text-slate-800">Productos sin movimiento</h3>
            <p class="text-xs text-slate-400 mt-0.5">Sin ventas en los últimos 30 días</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr class="text-[11px] font-semibold text-slate-400 uppercase tracking-widest">
                        <th class="px-5 py-3">SKU</th>
                        <th class="px-5 py-3">Producto</th>
                        <th class="px-5 py-3">Stock</th>
                        <th class="px-5 py-3">Última venta</th>
                        <th class="px-5 py-3">Días sin venta</th>
                    </tr>
                </thead>
                <tbody id="stale-tbody">
                    <tr>
                        <td colspan="5" class="px-5 py-6 text-center text-slate-400 text-sm">Cargando...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Informe IA -->
    <div class="bg-white border border-slate-200/70 border-l-[3px] border-l-violet-500 rounded-2xl p-6">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-violet-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Informe generado por IA</h3>
                    <p id="report-meta" class="text-xs text-slate-400 mt-0.5">Genera un informe para ver análisis y recomendaciones.</p>
                </div>
            </div>
            <button id="refresh-report-btn" class="hidden text-xs text-violet-600 hover:text-violet-800 font-semibold">Regenerar</button>
        </div>

        <div id="report-loading" class="hidden flex flex-col items-center py-10">
            <div class="w-7 h-7 border-2 border-violet-200 border-t-violet-600 rounded-full animate-spin mb-3"></div>
            <p class="text-sm text-slate-400">La IA está analizando tus datos... (puede tardar 20–60s)</p>
        </div>

        <div id="report-content" class="prose prose-sm max-w-none text-slate-700 hidden"></div>

        <div id="report-empty" class="text-center py-10 text-slate-400 text-sm">
            Presiona <span class="font-semibold text-violet-600">"Generar informe IA"</span> para obtener un análisis personalizado de tus ventas.
        </div>
    </div>

    <!-- Toast -->
    <div id="toast-container" class="fixed bottom-6 right-6 z-[300] flex flex-col gap-2 pointer-events-none"></div>

</div>
@endsection

@section('scripts')
    @vite(['resources/js/statistics.js'])
@endsection
