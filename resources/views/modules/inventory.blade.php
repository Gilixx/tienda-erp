@extends('layouts.app')
@section('title', 'Sistema de Inventarios')
@section('page-title', 'Catálogo de Inventario')

@section('content')
<div id="inventory-app" class="relative">
    
    <!-- Top Bar -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-800">Tus Productos</h1>
            <p class="text-slate-500 text-sm mt-1">Gestiona el inventario general de tu negocio</p>
        </div>
        <div class="flex gap-3">
            <button id="add-category-btn" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-5 py-2.5 rounded-xl font-medium shadow-sm transition-all focus:ring-4 focus:ring-indigo-100 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                Nueva Categoría
            </button>
            <button id="add-product-btn" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-xl font-medium shadow-sm transition-all focus:ring-4 focus:ring-emerald-100 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Nuevo Producto
            </button>
        </div>
    </div>

    <!-- Categorias Blocks -->
    <div id="categories-container" class="mb-8 flex gap-4 overflow-x-auto pb-4 custom-scrollbar">
        <!-- Bloques de categorías renderizados por JS -->
    </div>

    <!-- Table Container -->
    <div class="bg-white/80 backdrop-blur-xl border border-slate-200/60 shadow-lg shadow-slate-200/40 rounded-2xl overflow-hidden">
        
        <!-- Loading State -->
        <div id="loading-state" class="p-12 flex flex-col items-center justify-center hidden">
            <svg class="animate-spin h-8 w-8 text-emerald-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-slate-500 font-medium">Cargando inventario...</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200/80 text-slate-500 text-xs uppercase tracking-wider font-semibold">
                        <th class="px-6 py-4 rounded-tl-xl">SKU</th>
                        <th class="px-6 py-4">Producto</th>
                        <th class="px-6 py-4">Categoría</th>
                        <th class="px-6 py-4">Precio</th>
                        <th class="px-6 py-4">Stock</th>
                        <th class="px-6 py-4">Estado</th>
                        <th class="px-6 py-4 text-right rounded-tr-xl">Acciones</th>
                    </tr>
                </thead>
                <tbody id="products-tbody">
                    <!-- JS Injected content -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- ADD PRODUCT MODAL -->
    <div id="product-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"></div>
        
        <!-- Modal Content -->
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-100">
            <div class="p-6 sm:p-8">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-slate-800">Registrar Producto</h3>
                    <button type="button" id="close-product-modal" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <form id="product-form" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nombre</label>
                            <input type="text" name="name" required class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">SKU</label>
                            <input type="text" name="sku" required class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Categoría</label>
                            <select name="category_id" id="product-category" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors">
                                <!-- JS injected -->
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Precio de Venta</label>
                            <input type="number" step="0.01" name="price" required class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Costo Unitario</label>
                            <input type="number" step="0.01" name="cost" required class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Stock Mínimo</label>
                            <input type="number" name="min_stock" value="5" required class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors">
                        </div>
                    </div>
                    <div class="pt-4 flex justify-end">
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-xl font-medium shadow-sm transition-all focus:ring-4 focus:ring-emerald-100">Guardar Producto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MOVEMENT MODAL -->
    <div id="movement-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"></div>
        
        <!-- Modal Content -->
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100">
            <div class="p-6 sm:p-8">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-slate-800">Movimiento de Stock</h3>
                    <button type="button" id="close-movement-modal" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <form id="movement-form" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Producto</label>
                        <select name="product_id" id="movement-product" required class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors">
                            <!-- JS injected -->
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Tipo</label>
                            <select name="type" required class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors">
                                <option value="in">Entrada (+)</option>
                                <option value="out">Salida (-)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Cantidad</label>
                            <input type="number" min="1" name="quantity" required class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Notas (Opcional)</label>
                        <textarea name="notes" rows="2" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-emerald-500 focus:ring-emerald-500 transition-colors"></textarea>
                    </div>
                    
                    <div class="pt-4 flex justify-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl font-medium shadow-sm transition-all focus:ring-4 focus:ring-blue-100">Registrar Movimiento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ADD CATEGORY MODAL -->
    <div id="category-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden border border-slate-100">
            <div class="p-6 sm:p-8">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-slate-800">Nueva Categoría</h3>
                    <button type="button" id="close-category-modal" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form id="category-form" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nombre</label>
                        <input type="text" name="name" required class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Descripción (Opcional)</label>
                        <textarea name="description" rows="2" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 transition-colors"></textarea>
                    </div>
                    <div class="pt-4 flex justify-end">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl font-medium shadow-sm transition-all focus:ring-4 focus:ring-indigo-100">Crear Categoría</button>
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
