@extends('layouts.app')
@section('title', 'Sistema de Inventarios')
@section('page-title', 'Inventario')

@section('content')
<div id="inventory-app" class="relative">

    <!-- Stats Bar -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 border-l-[3px] border-l-emerald-500 rounded-2xl p-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <div>
                <p class="text-[11px] font-semibold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">Total Productos</p>
                <p id="stat-total-products" class="text-2xl font-mono font-semibold text-slate-900 dark:text-zinc-50 mt-1 tabular-nums">—</p>
            </div>
        </div>
        <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 border-l-[3px] border-l-rose-500 rounded-2xl p-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-[11px] font-semibold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">Stock Bajo</p>
                <p id="stat-low-stock" class="text-2xl font-mono font-semibold text-rose-600 dark:text-rose-400 mt-1 tabular-nums">—</p>
            </div>
        </div>
        <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 border-l-[3px] border-l-indigo-400 rounded-2xl p-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
            </div>
            <div>
                <p class="text-[11px] font-semibold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">Categorías</p>
                <p id="stat-categories" class="text-2xl font-mono font-semibold text-slate-900 dark:text-zinc-50 mt-1 tabular-nums">—</p>
            </div>
        </div>
    </div>

    <!-- Controls: Search + Buttons -->
    <div class="flex flex-col sm:flex-row gap-3 mb-5 items-start sm:items-center justify-between">
        <div class="relative w-full sm:w-80">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
            </svg>
            <input id="search-input" type="search" placeholder="Buscar por nombre o SKU..."
                class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-slate-800 dark:text-zinc-100 placeholder-slate-400 dark:placeholder-zinc-500 focus:outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 dark:focus:ring-emerald-900/30 transition-all">
        </div>
        <div class="flex gap-3 flex-shrink-0">
            <button id="import-products-btn"
                class="bg-amber-50 dark:bg-amber-900/20 hover:bg-amber-100 dark:hover:bg-amber-900/30 text-amber-700 dark:text-amber-400 px-4 py-2.5 rounded-xl font-medium text-sm transition-all flex items-center gap-2 border border-amber-200/70 dark:border-amber-800/40">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"/>
                </svg>
                Importar CSV
            </button>
            <button id="add-category-btn"
                class="bg-indigo-50 dark:bg-indigo-900/20 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 px-4 py-2.5 rounded-xl font-medium text-sm transition-all flex items-center gap-2 border border-indigo-200/70 dark:border-indigo-800/40">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                Nueva Categoría
            </button>
            <button id="add-product-btn"
                class="bg-emerald-600 hover:bg-emerald-700 active:-translate-y-px text-white px-4 py-2.5 rounded-xl font-semibold text-sm transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Nuevo Producto
            </button>
        </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-6 border-b border-slate-200 dark:border-zinc-800 mb-5">
        <button id="tab-products"
            class="pb-3 text-sm font-semibold border-b-2 border-emerald-500 text-emerald-600 dark:text-emerald-400 transition-colors">
            Productos
        </button>
        <button id="tab-movements"
            class="pb-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 dark:text-zinc-500 hover:text-slate-700 dark:hover:text-zinc-300 transition-colors">
            Historial de Movimientos
        </button>
    </div>

    <!-- SECTION: PRODUCTS -->
    <div id="section-products">
        <div id="categories-container" class="mb-5 flex gap-3 overflow-x-auto pb-2">
            <!-- JS rendered -->
        </div>

        <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl overflow-hidden">
            <div id="loading-state" class="p-12 flex flex-col items-center justify-center hidden">
                <div class="w-7 h-7 border-2 border-emerald-200 dark:border-emerald-800 border-t-emerald-600 dark:border-t-emerald-400 rounded-full animate-spin mb-3"></div>
                <p class="text-slate-400 dark:text-zinc-500 text-sm">Cargando inventario...</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-zinc-800/60 border-b border-slate-200 dark:border-zinc-700 text-[11px] font-semibold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">
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
                    <tbody id="products-tbody" class="divide-y divide-slate-100 dark:divide-zinc-800">
                        <!-- JS rendered -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SECTION: MOVEMENTS -->
    <div id="section-movements" class="hidden">
        <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-zinc-800/60 border-b border-slate-200 dark:border-zinc-700 text-[11px] font-semibold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">
                            <th class="px-5 py-3.5">Fecha</th>
                            <th class="px-5 py-3.5">Producto</th>
                            <th class="px-5 py-3.5">SKU</th>
                            <th class="px-5 py-3.5">Tipo</th>
                            <th class="px-5 py-3.5">Cantidad</th>
                            <th class="px-5 py-3.5">Notas / Referencia</th>
                            <th class="px-5 py-3.5">Usuario</th>
                        </tr>
                    </thead>
                    <tbody id="movements-tbody" class="divide-y divide-slate-100 dark:divide-zinc-800">
                        <!-- JS rendered -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL: IMPORT CSV -->
    <div id="import-modal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 flex">
        <div class="absolute inset-0 bg-slate-900/50 dark:bg-black/60 backdrop-blur-sm"></div>
        <div class="relative bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-100 dark:border-zinc-800">
            <div class="p-6 sm:p-8">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xl font-bold text-slate-800 dark:text-zinc-50">Importar Catálogo</h3>
                    <button type="button" id="close-import-modal" class="text-slate-400 dark:text-zinc-500 hover:text-slate-600 dark:hover:text-zinc-300 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800/40 rounded-xl p-3 mb-4 text-xs text-amber-800 dark:text-amber-300">
                    <p class="font-semibold mb-1">Instrucciones:</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        <li>Descarga la plantilla y llénala con tus productos.</li>
                        <li>Columnas requeridas: <code class="dark:text-amber-200">sku</code>, <code class="dark:text-amber-200">nombre</code>, <code class="dark:text-amber-200">precio</code>.</li>
                        <li>Opcionales: <code class="dark:text-amber-200">categoria</code>, <code class="dark:text-amber-200">costo</code>, <code class="dark:text-amber-200">stock</code>, <code class="dark:text-amber-200">stock_minimo</code>, <code class="dark:text-amber-200">descripcion</code>.</li>
                        <li>Si el SKU ya existe, se actualizará el producto.</li>
                        <li>Máximo 5,000 filas / 5 MB.</li>
                    </ul>
                </div>

                <button id="download-template-btn" type="button"
                    class="w-full mb-4 bg-slate-100 dark:bg-zinc-800 hover:bg-slate-200 dark:hover:bg-zinc-700 text-slate-700 dark:text-zinc-300 px-4 py-2.5 rounded-xl font-medium text-sm transition-colors flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"/></svg>
                    Descargar plantilla
                </button>

                <form id="import-form" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1">Archivo CSV <span class="text-rose-500">*</span></label>
                        <input type="file" name="file" id="import-file" accept=".csv,text/csv" required
                            class="w-full text-sm text-slate-700 dark:text-zinc-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-amber-50 dark:file:bg-amber-900/30 file:text-amber-700 dark:file:text-amber-400 hover:file:bg-amber-100 dark:hover:file:bg-amber-900/40">
                    </div>
                    <div id="import-result" class="hidden text-sm rounded-xl p-3"></div>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" id="cancel-import-modal"
                            class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-400 hover:bg-slate-50 dark:hover:bg-zinc-800 font-medium text-sm transition-colors">Cancelar</button>
                        <button type="submit" id="import-submit-btn"
                            class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition-all">Importar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- TOAST CONTAINER -->
    <div id="toast-container" class="fixed bottom-6 right-6 z-[300] flex flex-col gap-2 pointer-events-none"></div>

    <!-- MODAL: PRODUCT (create / edit) -->
    <div id="product-modal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 flex">
        <div class="absolute inset-0 bg-slate-900/50 dark:bg-black/60 backdrop-blur-sm"></div>
        <div class="relative bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-100 dark:border-zinc-800">
            <div class="p-6 sm:p-8">
                <div class="flex justify-between items-center mb-6">
                    <h3 id="product-modal-title" class="text-xl font-bold text-slate-800 dark:text-zinc-50">Registrar Producto</h3>
                    <button type="button" id="close-product-modal" class="text-slate-400 dark:text-zinc-500 hover:text-slate-600 dark:hover:text-zinc-300 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                @php $inp = 'w-full rounded-xl border border-slate-200 dark:border-zinc-700 bg-slate-50 dark:bg-zinc-800 text-slate-800 dark:text-zinc-100 focus:bg-white dark:focus:bg-zinc-700 focus:border-emerald-500 focus:ring-emerald-500 transition-colors text-sm placeholder-slate-400 dark:placeholder-zinc-500'; @endphp

                <form id="product-form" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1">Nombre <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" required maxlength="255" class="{{ $inp }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1">SKU <span class="text-rose-500">*</span></label>
                            <input type="text" name="sku" required maxlength="100" class="{{ $inp }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1">Categoría</label>
                            <select name="category_id" id="product-category" class="{{ $inp }}">
                                <!-- JS injected -->
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1">Precio de Venta <span class="text-rose-500">*</span></label>
                            <input type="number" step="0.01" min="0" name="price" required class="{{ $inp }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1">Costo Unitario <span class="text-rose-500">*</span></label>
                            <input type="number" step="0.01" min="0" name="cost" required class="{{ $inp }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1">Stock Mínimo <span class="text-rose-500">*</span></label>
                            <input type="number" min="0" name="min_stock" value="5" required class="{{ $inp }}">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1">Descripción</label>
                            <textarea name="description" rows="2" maxlength="1000" class="{{ $inp }}"></textarea>
                        </div>
                    </div>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" id="cancel-product-modal"
                            class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-400 hover:bg-slate-50 dark:hover:bg-zinc-800 font-medium text-sm transition-colors">Cancelar</button>
                        <button type="submit" id="product-submit-btn"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition-all">Guardar Producto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: MOVEMENT -->
    <div id="movement-modal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 flex">
        <div class="absolute inset-0 bg-slate-900/50 dark:bg-black/60 backdrop-blur-sm"></div>
        <div class="relative bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100 dark:border-zinc-800">
            <div class="p-6 sm:p-8">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xl font-bold text-slate-800 dark:text-zinc-50">Movimiento de Stock</h3>
                    <button type="button" id="close-movement-modal" class="text-slate-400 dark:text-zinc-500 hover:text-slate-600 dark:hover:text-zinc-300 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                @php $inp2 = 'w-full rounded-xl border border-slate-200 dark:border-zinc-700 bg-slate-50 dark:bg-zinc-800 text-slate-800 dark:text-zinc-100 focus:bg-white dark:focus:bg-zinc-700 focus:border-emerald-500 focus:ring-emerald-500 transition-colors text-sm'; @endphp

                <form id="movement-form" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1">Producto</label>
                        <select name="product_id" id="movement-product" required class="{{ $inp2 }}">
                            <!-- JS injected -->
                        </select>
                        <p class="text-xs text-slate-400 dark:text-zinc-500 mt-1">Stock actual: <span id="movement-current-stock" class="font-semibold text-slate-600 dark:text-zinc-300">—</span></p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1">Tipo</label>
                            <select name="type" id="movement-type" required class="{{ $inp2 }}">
                                <option value="in">Entrada (+)</option>
                                <option value="out">Salida (-)</option>
                                <option value="adjustment">Ajuste</option>
                            </select>
                        </div>
                        <div>
                            <label id="movement-qty-label" class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1">Cantidad</label>
                            <input type="number" min="1" id="movement-quantity" name="quantity" required class="{{ $inp2 }}">
                        </div>
                    </div>
                    <p id="adjustment-hint" class="hidden text-xs text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 px-3 py-2 rounded-lg border border-amber-100 dark:border-amber-800/40">
                        Para ajuste: usa valores positivos (+5) para agregar stock o negativos (-3) para reducirlo.
                    </p>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1">Notas (Opcional)</label>
                        <textarea name="notes" rows="2" maxlength="1000" class="{{ $inp2 }}"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1">Referencia (Opcional)</label>
                        <input type="text" name="reference" maxlength="255" class="{{ $inp2 }}" placeholder="Ej: Factura #001">
                    </div>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" id="cancel-movement-modal"
                            class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-400 hover:bg-slate-50 dark:hover:bg-zinc-800 font-medium text-sm transition-colors">Cancelar</button>
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition-all">Registrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: CATEGORY -->
    <div id="category-modal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 flex">
        <div class="absolute inset-0 bg-slate-900/50 dark:bg-black/60 backdrop-blur-sm"></div>
        <div class="relative bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden border border-slate-100 dark:border-zinc-800">
            <div class="p-6 sm:p-8">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xl font-bold text-slate-800 dark:text-zinc-50">Nueva Categoría</h3>
                    <button type="button" id="close-category-modal" class="text-slate-400 dark:text-zinc-500 hover:text-slate-600 dark:hover:text-zinc-300 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                @php $inp3 = 'w-full rounded-xl border border-slate-200 dark:border-zinc-700 bg-slate-50 dark:bg-zinc-800 text-slate-800 dark:text-zinc-100 focus:bg-white dark:focus:bg-zinc-700 focus:border-indigo-500 focus:ring-indigo-500 transition-colors text-sm'; @endphp
                <form id="category-form" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1">Nombre <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required maxlength="255" class="{{ $inp3 }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1">Descripción (Opcional)</label>
                        <textarea name="description" rows="2" maxlength="1000" class="{{ $inp3 }}"></textarea>
                    </div>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" id="cancel-category-modal"
                            class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-400 hover:bg-slate-50 dark:hover:bg-zinc-800 font-medium text-sm transition-colors">Cancelar</button>
                        <button type="submit"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition-all">Crear Categoría</button>
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
