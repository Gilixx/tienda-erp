import api from './api';

/**
 * Punto de Venta (POS) — componente Alpine.
 *
 * Se expone como `window.posApp` para que la instancia de Alpine (cargada
 * después vía CDN con `defer`) lo encuentre al inicializar `x-data`.
 */
window.posApp = function (defaultAlmacenId = null) {
    return {
        // ── Estado ──────────────────────────────────────────────
        view: 'terminal',
        almacenId: defaultAlmacenId ? String(defaultAlmacenId) : '',

        // Búsqueda
        q: '',
        results: [],
        searching: false,
        _searchTimer: null,
        _lastKeyAt: 0,
        _fastInput: false,

        // Carrito
        cart: [],
        cliente: '',
        metodoPago: 'efectivo',
        cobrando: false,

        // Ticket / reportes
        ticket: null,
        stats: {},
        ventas: [],
        rep: { desde: todayStr(), hasta: todayStr() },

        // Toast
        toast: { show: false, msg: '', type: 'info' },

        // ── Init ────────────────────────────────────────────────
        init() {
            // Asegura que el <select> refleje el almacén por defecto.
            this.$nextTick(() => this.$refs.search?.focus());
        },

        // ── Búsqueda ────────────────────────────────────────────
        onSearchInput() {
            clearTimeout(this._searchTimer);
            const term = this.q;
            if (!term.trim()) {
                this.results = [];
                this.searching = false;
                return;
            }
            this._searchTimer = setTimeout(() => this.doSearch(term), 300);
        },

        onKeydown(e) {
            // Heurística de escáner: teclas muy rápidas (< 50ms) => entrada automática.
            const now = Date.now();
            this._fastInput = (now - this._lastKeyAt) < 50;
            this._lastKeyAt = now;

            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(this._searchTimer);
                this.handleScan();
            }
        },

        async doSearch(term) {
            if (!term.trim()) { this.results = []; return; }
            this.searching = true;
            try {
                const { data } = await api.get('/api/pos/products/search', {
                    q: term, almacen_id: this.almacenId,
                });
                // Evita pisar resultados de una búsqueda posterior.
                if (term === this.q) this.results = data;
            } catch (e) {
                this.notify('Error al buscar productos', 'error');
            } finally {
                this.searching = false;
            }
        },

        async handleScan() {
            const term = this.q.trim();
            if (!term) return;
            try {
                const { data } = await api.get('/api/pos/products/search', {
                    q: term, almacen_id: this.almacenId,
                });
                this.results = data;
                // Coincidencia exacta por SKU, o único resultado => al carrito.
                const exact = data.find(p => p.sku.toLowerCase() === term.toLowerCase());
                const target = exact || (data.length === 1 ? data[0] : null);
                if (target) {
                    this.addToCart(target);
                    this.q = '';
                    this.results = [];
                }
            } catch (e) {
                this.notify('Error al buscar', 'error');
            }
        },

        // ── Carrito ─────────────────────────────────────────────
        addToCart(p) {
            if (p.stock_almacen <= 0) {
                this.notify(`Sin stock de ${p.name} en este almacén`, 'error');
                return;
            }
            const existing = this.cart.find(i => i.product_id === p.id);
            if (existing) {
                if (existing.qty < p.stock_almacen) existing.qty++;
                else this.notify(`Stock máximo alcanzado (${p.stock_almacen})`, 'error');
            } else {
                this.cart.push({
                    product_id: p.id,
                    sku: p.sku,
                    name: p.name,
                    price: p.price,
                    qty: 1,
                    stock: p.stock_almacen,
                });
            }
            this.$nextTick(() => this.$refs.search?.focus());
        },

        updateQty(i, delta) {
            const item = this.cart[i];
            if (!item) return;
            const next = item.qty + delta;
            if (next < 1) { this.removeItem(i); return; }
            if (item.stock && next > item.stock) {
                this.notify(`Stock máximo: ${item.stock}`, 'error');
                return;
            }
            item.qty = next;
        },

        clampQty(i) {
            const item = this.cart[i];
            if (!item) return;
            item.qty = Math.max(1, Math.floor(item.qty || 1));
            if (item.stock && item.qty > item.stock) item.qty = item.stock;
        },

        removeItem(i) {
            this.cart.splice(i, 1);
        },

        get total() {
            return this.cart.reduce((s, i) => s + i.price * i.qty, 0);
        },

        // ── Cobro ───────────────────────────────────────────────
        async cobrar() {
            if (!this.cart.length || this.cobrando) return;
            if (!this.almacenId) { this.notify('Selecciona un almacén', 'error'); return; }

            this.cobrando = true;
            try {
                const payload = {
                    almacen_id: this.almacenId,
                    metodo_pago: this.metodoPago,
                    cliente_nombre: this.cliente || null,
                    items: this.cart.map(i => ({
                        product_id: i.product_id,
                        cantidad: i.qty,
                        precio_unit: i.price,
                    })),
                };
                const { data } = await api.post('/api/pos/ventas', payload);
                this.ticket = data;
                this.cart = [];
                this.cliente = '';
                this.q = '';
                this.results = [];
                this.notify('Venta registrada');
            } catch (e) {
                const msg = e.response?.data?.message
                    || Object.values(e.response?.data?.errors || {}).flat().join(' ')
                    || 'No se pudo completar la venta';
                this.notify(msg, 'error');
            } finally {
                this.cobrando = false;
            }
        },

        // ── Ticket ──────────────────────────────────────────────
        async verTicket(id) {
            try {
                const { data } = await api.get(`/api/pos/ventas/${id}`);
                this.ticket = data;
            } catch (e) {
                this.notify('No se pudo cargar el ticket', 'error');
            }
        },

        printTicket() {
            window.print();
        },

        // ── Reportes ────────────────────────────────────────────
        async loadStats() {
            try {
                const { data } = await api.get('/api/pos/stats', {
                    desde: this.rep.desde, hasta: this.rep.hasta,
                });
                this.stats = data;
            } catch (e) {
                this.notify('Error al cargar estadísticas', 'error');
            }
        },

        async loadVentas() {
            try {
                const { data } = await api.get('/api/pos/ventas', {
                    desde: this.rep.desde, hasta: this.rep.hasta, per_page: 100,
                });
                this.ventas = data.data ?? [];
            } catch (e) {
                this.notify('Error al cargar ventas', 'error');
            }
        },

        // ── Utilidades ──────────────────────────────────────────
        money(v) {
            return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(v) || 0);
        },

        fmtDate(v) {
            if (!v) return '';
            return new Date(v).toLocaleDateString('es-MX', { day: '2-digit', month: 'short' });
        },

        fmtDateTime(v) {
            if (!v) return '';
            return new Date(v).toLocaleString('es-MX', { dateStyle: 'short', timeStyle: 'short' });
        },

        notify(msg, type = 'info') {
            this.toast = { show: true, msg, type };
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => { this.toast.show = false; }, 2600);
        },
    };
};

function todayStr() {
    const d = new Date();
    const p = n => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}`;
}
