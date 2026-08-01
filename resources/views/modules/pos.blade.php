<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Punto de Venta — CRM-AC</title>

    {{-- Anti-flash dark mode --}}
    <script>if (localStorage.getItem('theme') === 'dark') { document.documentElement.classList.add('dark'); }</script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/pos.js'])
    @endif

    <style>
        /* Ticket térmico — impresión */
        @media print {
            html, body {
                height: auto !important;
                overflow: visible !important;
                background: #fff !important;
            }
            body * { visibility: hidden !important; }
            #ticket-print, #ticket-print * {
                visibility: visible !important;
                color: #000 !important;
                border-color: #000 !important;
            }
            #ticket-print {
                position: fixed; left: 0; top: 0;
                width: 80mm; padding: 4mm; margin: 0;
                font-family: 'JetBrains Mono', monospace;
                background: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            /* nombres largos se ajustan en vez de cortarse */
            #ticket-print .truncate {
                overflow: visible !important;
                white-space: normal !important;
                text-overflow: clip !important;
            }
            @page { size: 80mm auto; margin: 0; }
        }
    </style>
</head>
<body class="bg-slate-100 dark:bg-zinc-950 text-slate-800 dark:text-zinc-100 antialiased h-screen overflow-hidden"
      x-data="posApp({{ $almacenes->first()?->id ?? 'null' }})" x-init="init()">

@php $inp = 'rounded-xl border border-slate-200 dark:border-zinc-700 bg-slate-50 dark:bg-zinc-800 text-slate-800 dark:text-zinc-100 focus:bg-white dark:focus:bg-zinc-700 focus:border-emerald-500 focus:ring-emerald-500 transition-colors text-sm'; @endphp

<div class="h-screen flex flex-col">

    {{-- ── Top bar ──────────────────────────────────────────── --}}
    <header class="flex items-center justify-between gap-4 px-4 py-3 bg-white dark:bg-zinc-900 border-b border-slate-200/70 dark:border-zinc-800 flex-shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-emerald-600 flex items-center justify-center font-bold text-white text-sm">T</div>
            <div>
                <p class="text-sm font-semibold text-slate-900 dark:text-zinc-50 leading-tight">CRM-AC · POS</p>
                <p class="text-[11px] text-slate-400 dark:text-zinc-500 leading-tight">Punto de Venta</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            {{-- Selector de almacén --}}
            <select x-model="almacenId" class="{{ $inp }} py-2 px-3">
                @forelse($almacenes as $a)
                    <option value="{{ $a->id }}">{{ $a->nombre }} ({{ $a->codigo }}){{ $a->es_principal ? ' ★' : '' }}</option>
                @empty
                    <option value="">Sin almacenes</option>
                @endforelse
            </select>

            {{-- Tabs --}}
            <div class="flex rounded-xl bg-slate-100 dark:bg-zinc-800 p-1">
                <button @click="view='terminal'" :class="view==='terminal' ? 'bg-white dark:bg-zinc-700 shadow-sm text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-zinc-400'"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all">Terminal</button>
                <button @click="view='ventas'; loadStats(); loadVentas()" :class="view==='ventas' ? 'bg-white dark:bg-zinc-700 shadow-sm text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-zinc-400'"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all">Ventas</button>
            </div>

            <button onclick="toggleTheme()" class="w-9 h-9 rounded-lg flex items-center justify-center text-slate-500 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors" title="Cambiar tema">
                <svg class="w-[18px] h-[18px] dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                <svg class="w-[18px] h-[18px] hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </button>

            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-300 hover:bg-slate-50 dark:hover:bg-zinc-800 text-xs font-medium transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Dashboard
            </a>
        </div>
    </header>

    {{-- ── TERMINAL ─────────────────────────────────────────── --}}
    <div x-show="view==='terminal'" class="flex-1 flex min-h-0">

        {{-- Carrito (izquierda) --}}
        <aside class="w-[380px] flex-shrink-0 bg-white dark:bg-zinc-900 border-r border-slate-200/70 dark:border-zinc-800 flex flex-col">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-800 dark:text-zinc-100">Carrito</h2>
                <button x-show="cart.length" @click="cart=[]" class="text-xs text-rose-500 hover:text-rose-600 font-medium">Vaciar</button>
            </div>

            <div class="flex-1 overflow-y-auto p-3 space-y-2">
                <template x-if="!cart.length">
                    <div class="text-center text-slate-400 dark:text-zinc-500 text-sm py-16">
                        <svg class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        Escanea o busca un producto
                    </div>
                </template>

                <template x-for="(item, i) in cart" :key="item.product_id">
                    <div class="border border-slate-200 dark:border-zinc-800 rounded-xl p-3">
                        <div class="flex justify-between items-start gap-2">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-800 dark:text-zinc-100 truncate" x-text="item.name"></p>
                                <p class="text-[11px] text-slate-400 dark:text-zinc-500 font-mono" x-text="item.sku"></p>
                            </div>
                            <button @click="removeItem(i)" class="text-slate-300 hover:text-rose-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="flex items-center justify-between mt-2">
                            <div class="flex items-center gap-1.5">
                                <button @click="updateQty(i,-1)" class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-300 font-bold hover:bg-slate-200 dark:hover:bg-zinc-700">−</button>
                                <input type="number" min="1" x-model.number="item.qty" @change="clampQty(i)" class="{{ $inp }} w-14 text-center py-1 px-1">
                                <button @click="updateQty(i,1)" class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-300 font-bold hover:bg-slate-200 dark:hover:bg-zinc-700">+</button>
                            </div>
                            <p class="text-sm font-semibold text-slate-800 dark:text-zinc-100" x-text="money(item.price * item.qty)"></p>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Footer del carrito --}}
            <div class="border-t border-slate-100 dark:border-zinc-800 p-4 space-y-3">
                <input type="text" x-model="cliente" placeholder="Cliente (opcional)" class="{{ $inp }} w-full py-2.5 px-3">
                <select x-model="metodoPago" class="{{ $inp }} w-full py-2.5 px-3">
                    <option value="efectivo">Efectivo</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="transferencia">Transferencia</option>
                </select>
                <div class="flex items-center justify-between pt-1">
                    <span class="text-sm text-slate-500 dark:text-zinc-400">Total</span>
                    <span class="text-2xl font-bold text-slate-900 dark:text-zinc-50" x-text="money(total)"></span>
                </div>
                <button @click="cobrar()" :disabled="!cart.length || cobrando"
                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-3.5 rounded-xl font-bold text-base transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                    <span x-show="!cobrando">COBRAR</span>
                    <span x-show="cobrando">Procesando…</span>
                </button>
            </div>
        </aside>

        {{-- Búsqueda + resultados (derecha) --}}
        <section class="flex-1 flex flex-col min-w-0 p-5">
            <div class="relative mb-4">
                <svg class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input x-ref="search" x-model="q" @input="onSearchInput()" @keydown="onKeydown($event)"
                    type="text" autocomplete="off" placeholder="Escanea un código o busca por nombre / SKU…"
                    class="{{ $inp }} w-full pl-12 pr-4 py-3.5 text-base">
            </div>

            <div class="flex-1 overflow-y-auto">
                <template x-if="searching">
                    <p class="text-sm text-slate-400 dark:text-zinc-500 py-8 text-center">Buscando…</p>
                </template>
                <template x-if="!searching && q && !results.length">
                    <p class="text-sm text-slate-400 dark:text-zinc-500 py-8 text-center">Sin resultados para “<span x-text="q"></span>”.</p>
                </template>

                <div class="grid grid-cols-2 xl:grid-cols-3 gap-3">
                    <template x-for="p in results" :key="p.id">
                        <button @click="addToCart(p)"
                            class="text-left bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl p-4 hover:border-emerald-400 hover:shadow-sm transition-all">
                            <div class="flex justify-between items-start gap-2 mb-2">
                                <p class="text-sm font-semibold text-slate-800 dark:text-zinc-100 leading-snug" x-text="p.name"></p>
                                <span class="text-[10px] px-1.5 py-0.5 rounded-md font-semibold flex-shrink-0"
                                    :class="p.stock_almacen > 0 ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400' : 'bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400'"
                                    x-text="p.stock_almacen + ' u.'"></span>
                            </div>
                            <p class="text-[11px] text-slate-400 dark:text-zinc-500 font-mono mb-2" x-text="p.sku"></p>
                            <p class="text-base font-bold text-emerald-600 dark:text-emerald-400" x-text="money(p.price)"></p>
                        </button>
                    </template>
                </div>
            </div>
        </section>
    </div>

    {{-- ── VENTAS (reportes) ────────────────────────────────── --}}
    <div x-show="view==='ventas'" class="flex-1 overflow-y-auto p-6" style="display:none;">
        <div class="max-w-6xl mx-auto space-y-6">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-zinc-400 mb-1">Desde</label>
                    <input type="date" x-model="rep.desde" class="{{ $inp }} py-2 px-3">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-zinc-400 mb-1">Hasta</label>
                    <input type="date" x-model="rep.hasta" class="{{ $inp }} py-2 px-3">
                </div>
                <button @click="loadStats(); loadVentas()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-all">Aplicar</button>
            </div>

            {{-- Cards --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl p-5">
                    <p class="text-xs text-slate-400 dark:text-zinc-500 mb-1">Ventas</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-zinc-50" x-text="stats.total_ventas ?? 0"></p>
                </div>
                <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl p-5">
                    <p class="text-xs text-slate-400 dark:text-zinc-500 mb-1">Ingresos</p>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400" x-text="money(stats.ingresos_totales ?? 0)"></p>
                </div>
                <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl p-5">
                    <p class="text-xs text-slate-400 dark:text-zinc-500 mb-1">Ticket promedio</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-zinc-50" x-text="money(stats.ticket_promedio ?? 0)"></p>
                </div>
                <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl p-5">
                    <p class="text-xs text-slate-400 dark:text-zinc-500 mb-1">Método top</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-zinc-50 capitalize" x-text="stats.metodo_top ?? '—'"></p>
                </div>
            </div>

            <div class="grid lg:grid-cols-2 gap-6">
                {{-- Ventas del período --}}
                <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-slate-100 dark:border-zinc-800">
                        <h3 class="text-sm font-semibold text-slate-700 dark:text-zinc-300">Ventas</h3>
                    </div>
                    <div class="overflow-x-auto max-h-[420px] overflow-y-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 dark:bg-zinc-800/60 sticky top-0">
                                <tr class="text-[11px] font-semibold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">
                                    <th class="px-4 py-2.5 text-left">#</th>
                                    <th class="px-4 py-2.5 text-left">Fecha</th>
                                    <th class="px-4 py-2.5 text-left">Cliente</th>
                                    <th class="px-4 py-2.5 text-left">Método</th>
                                    <th class="px-4 py-2.5 text-right">Total</th>
                                    <th class="px-4 py-2.5"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                                <template x-for="v in ventas" :key="v.id">
                                    <tr class="hover:bg-slate-50 dark:hover:bg-zinc-800/40">
                                        <td class="px-4 py-2.5 font-mono text-slate-500 dark:text-zinc-400" x-text="'#'+v.id"></td>
                                        <td class="px-4 py-2.5 text-slate-600 dark:text-zinc-300" x-text="fmtDate(v.fecha)"></td>
                                        <td class="px-4 py-2.5 text-slate-600 dark:text-zinc-300" x-text="v.cliente || 'Mostrador'"></td>
                                        <td class="px-4 py-2.5 capitalize text-slate-600 dark:text-zinc-300" x-text="v.metodo_pago"></td>
                                        <td class="px-4 py-2.5 text-right font-semibold text-slate-800 dark:text-zinc-100" x-text="money(v.total)"></td>
                                        <td class="px-4 py-2.5 text-right">
                                            <button @click="verTicket(v.id)" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline font-medium">Ticket</button>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="!ventas.length">
                                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400 dark:text-zinc-500">Sin ventas en el período.</td></tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Top productos --}}
                <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-slate-100 dark:border-zinc-800">
                        <h3 class="text-sm font-semibold text-slate-700 dark:text-zinc-300">Productos más vendidos</h3>
                    </div>
                    <div class="overflow-x-auto max-h-[420px] overflow-y-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 dark:bg-zinc-800/60 sticky top-0">
                                <tr class="text-[11px] font-semibold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">
                                    <th class="px-4 py-2.5 text-left">SKU</th>
                                    <th class="px-4 py-2.5 text-left">Producto</th>
                                    <th class="px-4 py-2.5 text-right">Unid.</th>
                                    <th class="px-4 py-2.5 text-right">Ingresos</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                                <template x-for="(p, i) in (stats.top_productos || [])" :key="i">
                                    <tr class="hover:bg-slate-50 dark:hover:bg-zinc-800/40">
                                        <td class="px-4 py-2.5 font-mono text-slate-500 dark:text-zinc-400" x-text="p.sku"></td>
                                        <td class="px-4 py-2.5 text-slate-700 dark:text-zinc-200" x-text="p.name"></td>
                                        <td class="px-4 py-2.5 text-right font-semibold" x-text="p.unidades"></td>
                                        <td class="px-4 py-2.5 text-right text-emerald-600 dark:text-emerald-400" x-text="money(p.ingresos)"></td>
                                    </tr>
                                </template>
                                <template x-if="!(stats.top_productos || []).length">
                                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400 dark:text-zinc-500">Sin datos.</td></tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Modal de ticket ──────────────────────────────────────── --}}
<div x-show="ticket" style="display:none;" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50 dark:bg-black/60 backdrop-blur-sm" @click="ticket=null"></div>
    <div class="relative bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden border border-slate-100 dark:border-zinc-800">
        <div class="p-6">
            {{-- Contenido imprimible --}}
            <div id="ticket-print" class="text-sm" x-show="ticket">
                <div class="text-center mb-3">
                    <p class="font-bold text-base">CRM-AC</p>
                    <p class="text-xs">Ticket de Venta</p>
                </div>
                <div class="text-xs space-y-0.5 mb-3">
                    <p>Ticket: <span class="font-semibold" x-text="ticket && ('#'+ticket.id)"></span></p>
                    <p>Fecha: <span x-text="ticket && fmtDateTime(ticket.fecha)"></span></p>
                    <p>Cliente: <span x-text="(ticket && ticket.cliente) || 'Mostrador'"></span></p>
                    <p>Almacén: <span x-text="ticket && ticket.almacen ? ticket.almacen.nombre : ''"></span></p>
                </div>
                <div class="border-t border-b border-dashed border-slate-400 py-2 my-2">
                    <template x-for="(it, i) in (ticket ? ticket.items : [])" :key="i">
                        <div class="flex justify-between text-xs py-0.5">
                            <span class="truncate pr-2" x-text="it.cantidad + '× ' + (it.product ? it.product.name : '')"></span>
                            <span x-text="money(it.subtotal)"></span>
                        </div>
                    </template>
                </div>
                <div class="flex justify-between font-bold text-sm">
                    <span>TOTAL</span>
                    <span x-text="ticket && money(ticket.total)"></span>
                </div>
                <p class="text-xs mt-1 capitalize">Pago: <span x-text="ticket && ticket.metodo_pago"></span></p>
                <p class="text-center text-xs mt-3">¡Gracias por su compra!</p>
            </div>

            <div class="flex gap-3 mt-5">
                <button @click="ticket=null" class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-400 hover:bg-slate-50 dark:hover:bg-zinc-800 font-medium text-sm transition-colors">Cerrar</button>
                <button @click="printTicket()" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-xl font-semibold text-sm transition-all">Imprimir</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Toast ────────────────────────────────────────────────── --}}
<div x-show="toast.show" style="display:none;" x-transition
     class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[110] px-4 py-2.5 rounded-xl text-sm font-medium shadow-lg"
     :class="toast.type==='error' ? 'bg-rose-600 text-white' : 'bg-slate-900 dark:bg-zinc-100 text-white dark:text-zinc-900'"
     x-text="toast.msg"></div>

<script>
    function toggleTheme() {
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    }
</script>

{{-- Alpine.js vía CDN (posApp() ya está definido por pos.js, cargado antes con defer) --}}
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>

</body>
</html>
