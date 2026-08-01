@extends('layouts.app')
@section('title', 'Administración')
@section('page-title', 'Administración')

@section('content')

<div id="admin-app"
     data-current-user-id="{{ auth()->id() }}"
     class="space-y-6">

    {{-- ── Tabs ──────────────────────────────────────────────── --}}
    <div class="flex items-center gap-1 border-b border-slate-200/70 dark:border-zinc-800 overflow-x-auto">
        <button data-tab="usuarios"   class="admin-tab px-4 py-2.5 text-sm font-medium border-b-2 whitespace-nowrap transition-colors">Usuarios</button>
        <button data-tab="servicios"  class="admin-tab px-4 py-2.5 text-sm font-medium border-b-2 whitespace-nowrap transition-colors">Servicios</button>
        <button data-tab="metricas"   class="admin-tab px-4 py-2.5 text-sm font-medium border-b-2 whitespace-nowrap transition-colors">Métricas</button>
        <button data-tab="auditoria"  class="admin-tab px-4 py-2.5 text-sm font-medium border-b-2 whitespace-nowrap transition-colors">Auditoría</button>
    </div>

    {{-- ── Panel: Usuarios ───────────────────────────────────── --}}
    <section data-panel="usuarios" class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="relative flex-1">
                <input id="user-search" type="text" placeholder="Buscar por nombre o email…"
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-slate-800 dark:text-zinc-100 placeholder-slate-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <svg class="absolute left-3 top-3 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <select id="filter-role" class="px-3 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-slate-700 dark:text-zinc-200">
                <option value="">Todos los roles</option>
                <option value="admin">Administradores</option>
                <option value="user">Usuarios</option>
            </select>
            <select id="filter-status" class="px-3 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-slate-700 dark:text-zinc-200">
                <option value="">Todos</option>
                <option value="1">Activos</option>
                <option value="0">Inactivos</option>
                <option value="trashed">Eliminados</option>
            </select>
            <button id="btn-nuevo-usuario" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors whitespace-nowrap">+ Nuevo usuario</button>
            <button id="btn-exportar" class="px-4 py-2.5 border border-slate-200 dark:border-zinc-700 hover:bg-slate-50 dark:hover:bg-zinc-800 text-slate-700 dark:text-zinc-200 text-sm font-medium rounded-xl transition-colors whitespace-nowrap">Exportar CSV</button>
        </div>

        <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-zinc-800/50 text-slate-500 dark:text-zinc-400 text-xs uppercase tracking-wide">
                        <tr>
                            <th class="text-left font-semibold px-4 py-3">Usuario</th>
                            <th class="text-left font-semibold px-4 py-3">Rol</th>
                            <th class="text-left font-semibold px-4 py-3">Estado</th>
                            <th class="text-left font-semibold px-4 py-3">Servicios</th>
                            <th class="text-right font-semibold px-4 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="users-tbody" class="divide-y divide-slate-100 dark:divide-zinc-800"></tbody>
                </table>
            </div>
            <div id="users-empty" class="hidden p-10 text-center text-sm text-slate-400 dark:text-zinc-500">Sin resultados.</div>
        </div>

        <div id="users-pagination" class="flex items-center justify-between text-sm text-slate-500 dark:text-zinc-400"></div>
    </section>

    {{-- ── Panel: Servicios ──────────────────────────────────── --}}
    <section data-panel="servicios" class="hidden space-y-4">
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/60 text-amber-800 dark:text-amber-300 text-xs px-4 py-3 rounded-xl">
            El catálogo permite editar nombre, descripción, ícono y visibilidad de los módulos. Crear una <em>clave</em> nueva no genera un módulo funcional por sí solo (requiere código en el sistema).
        </div>
        <div id="services-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"></div>
    </section>

    {{-- ── Panel: Métricas ───────────────────────────────────── --}}
    <section data-panel="metricas" class="hidden space-y-5">
        <div id="stats-cards" class="grid grid-cols-2 lg:grid-cols-5 gap-4"></div>
        <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl p-5">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-zinc-300 mb-4">Usuarios por módulo</h3>
            <div id="stats-por-servicio" class="space-y-3"></div>
        </div>
    </section>

    {{-- ── Panel: Auditoría ──────────────────────────────────── --}}
    <section data-panel="auditoria" class="hidden space-y-4">
        <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-zinc-800/50 text-slate-500 dark:text-zinc-400 text-xs uppercase tracking-wide">
                        <tr>
                            <th class="text-left font-semibold px-4 py-3">Fecha</th>
                            <th class="text-left font-semibold px-4 py-3">Actor</th>
                            <th class="text-left font-semibold px-4 py-3">Acción</th>
                            <th class="text-left font-semibold px-4 py-3">Descripción</th>
                        </tr>
                    </thead>
                    <tbody id="audit-tbody" class="divide-y divide-slate-100 dark:divide-zinc-800"></tbody>
                </table>
            </div>
            <div id="audit-empty" class="hidden p-10 text-center text-sm text-slate-400 dark:text-zinc-500">Sin registros.</div>
        </div>
        <div id="audit-pagination" class="flex items-center justify-between text-sm text-slate-500 dark:text-zinc-400"></div>
    </section>
</div>

{{-- ── Modales (contenedor, poblados por JS) ─────────────────── --}}
<div id="admin-modal-root"></div>

{{-- ── Toasts ────────────────────────────────────────────────── --}}
<div id="toast-container" class="fixed bottom-5 right-5 z-[300] space-y-2"></div>

@endsection

@section('scripts')
    @vite(['resources/js/admin.js'])
@endsection
