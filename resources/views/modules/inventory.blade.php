@extends('layouts.app')
@section('title', 'Sistema de Inventarios')
@section('page-title', 'Inventario')

@section('content')
<div id="inventory-app" class="relative">

    <!-- Stats Bar -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white border border-slate-200/60 rounded-2xl p-4 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-medium">Total Productos</p>
                <p id="stat-total-products" class="text-2xl font-bold text-slate-800">—</p>
            </div>
        </div>
        <div class="bg-white border border-slate-200/60 rounded-2xl p-4 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-rose-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-medium">Stock Bajo</p>
                <p id="stat-low-stock" class="text-2xl font-bold text-rose-600">—</p>
            </div>
        </div>
        <div class="bg-white border border-slate-200/60 rounded-2xl p-4 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-medium">Categorías</p>
                <p id="stat-categories" class="text-2xl font-bold text-slate-800">—</p>
            </div>
        </div>
    </div>

    <!-- Controls: Search + Buttons -->
    <div class="flex flex-col sm:flex-row gap-3 mb-5 items-start sm:items-center justify-between">
        <div class="relative w-full sm:w-80">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
            <input id="search-input" type="search" placeholder="Buscar por nombre o SKU…" class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 transition-all">
        </div>
        <div class="flex gap-3 flex-shrink-0">
            <button id="add-category-btn" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-4 py-2.5 rounded-xl font-medium text-sm shadow-sm transition-all focus:ring-4 focus:ring-indigo-100 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                Nueva Categoría
            </button>
            <button id="add-product-btn" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-xl font-medium text-sm shadow-sm transition-all focus:ring-4 focus:ring-emerald-100 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Nuevo Producto
            </button>
        </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-6 border-b border-slate-200 mb-5">
        <button id="tab-products" class="pb-3 text-sm font-semibold border-b-2 border-emerald-500 text-emerald-600 transition-colors">
            Productos
        </button>
        <button id="tab-movements" class="pb-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition-colors">
            Historial de Movimientos
        </button>
    </div>

    <!-- ═══ SECTION: PRODUCTS ═══ -->
    <div id="section-products">

        <!-- Category Filter -->
        <div id="categories-container" class="mb-5 flex gap-3 overflow-x-auto pb-2">
            <!-- JS rendered -->
        </div>

        <!-- Products Table -->
        <div class="bg-white border border-slate-200/60 shadow-sm rounded-2xl overflow-hidden">

            <!-- Loading State -->
            <div id="loading-state" class="p-12 flex flex-col items-center justify-center hidden">
                <svg class="animate-spin h-7 w-7 text-emerald-600 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-slate-400 text-sm">Cargando inventario…</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 text-xs uppercase tracking-wider font-semibold">
                            <th class="px-5 py-3.5">SKU</th>
                            <th class="px-5 py-3.5">Producto</th>
                            <th class="px-5 py-3.5">Categoría</th>
                            <th class="px-5 py-3.5">Precio</th>
                            <th class="px-5 py-3.5">Costo</th>
                            <th class="px-5 py-3.5">Stock</th>
                            <th class="px-5 py-3.5">Estado</th>
                            <th class="px-5 py-3.5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="products-tbody">
                        <!-- JS rendered -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══ SECTION: MOVEMENTS ═══ -->
    <div id="section-movements" class="hidden">
        <div class="bg-white border border-slate-200/60 shadow-sm rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 text-xs uppercase tracking-wider font-semibold">
                            <th class="px-5 py-3.5">Fecha</th>
                            <th class="px-5 py-3.5">Producto</th>
                            <th class="px-5 py-3.5">SKU</th>
                            <th class="px-5 py-3.5">Tipo</th>
                            <th class="px-5 py-3.5">Cantidad</th>
                            <th class="px-5 py-3.5">Notas / Referencia</th>
                            <th class="px-5 py-3.5">Usuario</th>
                        </tr>
                    </thead>
                    <tbody id="movements-tbody">
                        <!-- JS rendered -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══ TOAST CONTAINER ═══ -->
    <div id="toast-container" class="fixed bottom-6 right-6 z-[300] flex flex-col gap-2 pointer-events-none"></div>

    <!-- ═══ MODAL: PRODUCT (create / edit) ═══ -->
    <div id="product-modal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 flex">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-100">
            <div class="p-6 sm:p-8">
                <div class="flex justify-between items-center mb-6">
                    <h3 id="product-modal-title" class="text-xl font-bold text-slate-800">Registrar Producto</h3>
                    <button type="button" id="close-product-modal" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form id="product-form" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nombre <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" required maxlength="255" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">SKU <span class="text-rose-500">*</span></label>
                            <input type="text" name="sku" required maxlength="100" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Categoría</label>
                            <select name="category_id" id="product-category" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors text-sm">
                                <!-- JS injected -->
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Precio de Venta <span class="text-rose-500">*</span></label>
                            <input type="number" step="0.01" min="0" name="price" required class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Costo Unitario <span class="text-rose-500">*</span></label>
                            <input type="number" step="0.01" min="0" name="cost" required class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Stock Mínimo <span class="text-rose-500">*</span></label>
                            <input type="number" min="0" name="min_stock" value="5" required class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors text-sm">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Descripción</label>
                            <textarea name="description" rows="2" maxlength="1000" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors text-sm"></textarea>
                        </div>
                    </div>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" id="cancel-product-modal" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-medium text-sm transition-colors">Cancelar</button>
                        <button type="submit" id="product-submit-btn" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-xl font-medium text-sm shadow-sm transition-all focus:ring-4 focus:ring-emerald-100">Guardar Producto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ═══ MODAL: MOVEMENT ═══ -->
    <div id="movement-modal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 flex">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100">
            <div class="p-6 sm:p-8">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xl font-bold text-slate-800">Movimiento de Stock</h3>
                    <button type="button" id="close-movement-modal" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form id="movement-form" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Producto</label>
                        <select name="product_id" id="movement-product" required class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors text-sm">
                            <!-- JS injected -->
                        </select>
                        <p class="text-xs text-slate-400 mt-1">Stock actual: <span id="movement-current-stock" class="font-semibold text-slate-600">—</span></p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Tipo</label>
                            <select name="type" id="movement-type" required class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors text-sm">
                                <option value="in">Entrada (+)</option>
                                <option value="out">Salida (-)</option>
                                <option value="adjustment">Ajuste</option>
                            </select>
                        </div>
                        <div>
                            <label id="movement-qty-label" class="block text-sm font-medium text-slate-700 mb-1">Cantidad</label>
                            <input type="number" min="1" id="movement-quantity" name="quantity" required class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors text-sm">
                        </div>
                    </div>
                    <p id="adjustment-hint" class="hidden text-xs text-amber-600 bg-amber-50 px-3 py-2 rounded-lg border border-amber-100">
                        Para ajuste: usa valores positivos (+5) para agregar stock o negativos (-3) para reducirlo.
                    </p>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Notas (Opcional)</label>
                        <textarea name="notes" rows="2" maxlength="1000" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Referencia (Opcional)</label>
                        <input type="text" name="reference" maxlength="255" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors text-sm" placeholder="Ej: Factura #001">
                    </div>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" id="cancel-movement-modal" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-medium text-sm transition-colors">Cancelar</button>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl font-medium text-sm shadow-sm transition-all focus:ring-4 focus:ring-blue-100">Registrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ═══ MODAL: CATEGORY ═══ -->
    <div id="category-modal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 flex">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden border border-slate-100">
            <div class="p-6 sm:p-8">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xl font-bold text-slate-800">Nueva Categoría</h3>
                    <button type="button" id="close-category-modal" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="category-form" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nombre <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required maxlength="255" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Descripción (Opcional)</label>
                        <textarea name="description" rows="2" maxlength="1000" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors text-sm"></textarea>
                    </div>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" id="cancel-category-modal" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-medium text-sm transition-colors">Cancelar</button>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl font-medium text-sm shadow-sm transition-all focus:ring-4 focus:ring-indigo-100">Crear Categoría</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
    @vite(['resources/js/inventory.js'])
@endsection
