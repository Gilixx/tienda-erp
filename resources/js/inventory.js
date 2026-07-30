import api from './api';

document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('inventory-app');
    if (!app) return;

    // ─── Auth (para permisos de almacén) ──────────────────────
    const auth = {
        id: parseInt(app.dataset.userId) || null,
        isAdmin: app.dataset.isAdmin === '1',
    };

    // ─── State ────────────────────────────────────────────────
    const state = {
        products: [],
        categories: [],
        movements: [],
        selectedCategory: null,
        searchQuery: '',
        currentTab: 'products',
        editingProductId: null,
        selectedAlmacen: localStorage.getItem('inventory_almacen') || null,
    };

    // ─── DOM refs ─────────────────────────────────────────────
    const productsTbody      = document.getElementById('products-tbody');
    const movementsTbody     = document.getElementById('movements-tbody');
    const loadingState       = document.getElementById('loading-state');
    const categoriesContainer = document.getElementById('categories-container');
    const searchInput        = document.getElementById('search-input');
    const statLowStock       = document.getElementById('stat-low-stock');
    const statTotalProducts  = document.getElementById('stat-total-products');
    const statCategories     = document.getElementById('stat-categories');

    const addProductBtn      = document.getElementById('add-product-btn');
    const addCategoryBtn     = document.getElementById('add-category-btn');
    const tabProductsBtn     = document.getElementById('tab-products');
    const tabMovementsBtn    = document.getElementById('tab-movements');
    const productsSection    = document.getElementById('section-products');
    const movementsSection   = document.getElementById('section-movements');

    // Product modal
    const productModal       = document.getElementById('product-modal');
    const productModalTitle  = document.getElementById('product-modal-title');
    const closeProductModal  = document.getElementById('close-product-modal');
    const cancelProductModal = document.getElementById('cancel-product-modal');
    const productForm        = document.getElementById('product-form');
    const categorySelect     = document.getElementById('product-category');

    // Category modal
    const categoryModal      = document.getElementById('category-modal');
    const closeCategoryModal = document.getElementById('close-category-modal');
    const cancelCategoryModal = document.getElementById('cancel-category-modal');
    const categoryForm       = document.getElementById('category-form');

    // Movement modal
    const movementModal        = document.getElementById('movement-modal');
    const closeMovementModal   = document.getElementById('close-movement-modal');
    const cancelMovementModal  = document.getElementById('cancel-movement-modal');
    const movementForm         = document.getElementById('movement-form');
    const movementProductSelect = document.getElementById('movement-product');
    const movementCurrentStock = document.getElementById('movement-current-stock');
    const movementTypeSelect   = document.getElementById('movement-type');
    const movementQtyLabel     = document.getElementById('movement-qty-label');
    const movementQtyInput     = document.getElementById('movement-quantity');
    const adjustmentHint       = document.getElementById('adjustment-hint');

    const toastContainer = document.getElementById('toast-container');

    // ─── Security: HTML escape ─────────────────────────────────
    function esc(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function fmt(num) {
        return Number(num).toLocaleString('es-MX', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    function formatDate(dateStr) {
        if (!dateStr) return '—';
        return new Date(dateStr).toLocaleString('es-MX', {
            dateStyle: 'short',
            timeStyle: 'short',
        });
    }

    // ─── Toast notifications ───────────────────────────────────
    function showToast(msg, type = 'success') {
        const colors = {
            success: 'bg-emerald-600',
            error:   'bg-rose-600',
            warning: 'bg-amber-500',
        };
        const icons = {
            success: '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
            error:   '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>',
            warning: '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        };

        const el = document.createElement('div');
        el.className = `pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-xl text-white shadow-lg text-sm font-medium transition-all duration-300 ${colors[type] || colors.success}`;
        el.innerHTML = (icons[type] || icons.success) + `<span>${esc(msg)}</span>`;
        toastContainer.appendChild(el);

        setTimeout(() => {
            el.style.opacity = '0';
            el.style.transform = 'translateX(110%)';
            setTimeout(() => el.remove(), 300);
        }, 3500);
    }

    // ─── Confirm dialog ────────────────────────────────────────
    function showConfirm(message) {
        return new Promise(resolve => {
            const overlay = document.createElement('div');
            overlay.className = 'fixed inset-0 z-[200] flex items-center justify-center p-4';
            overlay.innerHTML = `
                <div class="absolute inset-0 bg-slate-900/50 dark:bg-black/60 backdrop-blur-sm"></div>
                <div class="relative bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl w-full max-w-sm p-6 border border-slate-100 dark:border-zinc-800">
                    <p class="text-slate-700 dark:text-zinc-200 font-medium mb-6 text-center text-sm leading-relaxed whitespace-pre-line">${esc(message)}</p>
                    <div class="flex gap-3 justify-center">
                        <button id="confirm-cancel" class="px-5 py-2 rounded-xl border border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-300 hover:bg-slate-50 dark:hover:bg-zinc-800 font-medium text-sm transition-colors">Cancelar</button>
                        <button id="confirm-ok" class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-medium text-sm transition-colors">Confirmar</button>
                    </div>
                </div>`;
            document.body.appendChild(overlay);
            overlay.querySelector('#confirm-ok').onclick = () => { overlay.remove(); resolve(true); };
            overlay.querySelector('#confirm-cancel').onclick = () => { overlay.remove(); resolve(false); };
        });
    }

    // ─── Init ──────────────────────────────────────────────────
    async function init() {
        showLoading();
        try {
            // Almacenes primero para resolver el almacén activo antes de pedir productos/movimientos
            await Promise.all([fetchCategories(), fetchAlmacenes()]);
            await Promise.all([fetchProducts(), fetchMovements()]);
            renderStats();
            renderCategories();
            renderProducts();
            renderMovements();
            populateCategorySelects();
            populateProductSelects();
            // Cargar alertas en background para mostrar badge
            fetchAndRenderAlertas().catch(() => {});
        } catch (error) {
            console.error('Error inicializando inventario', error);
            showToast('Error al cargar los datos del inventario', 'error');
        } finally {
            hideLoading();
        }
    }

    // ─── Fetches ───────────────────────────────────────────────
    async function fetchProducts() {
        const params = state.selectedAlmacen ? `?almacen_id=${state.selectedAlmacen}` : '';
        const { data } = await api.get(`/api/inventory/products${params}`);
        state.products = data;
    }

    async function fetchCategories() {
        const { data } = await api.get('/api/inventory/categories');
        state.categories = data;
    }

    async function fetchMovements() {
        const params = state.selectedAlmacen ? `?almacen_id=${state.selectedAlmacen}` : '';
        const { data } = await api.get(`/api/inventory/movements${params}`);
        state.movements = data;
    }

    // ─── Stats ─────────────────────────────────────────────────
    function renderStats() {
        const lowCount = state.products.filter(p => p.stock <= p.min_stock).length;
        if (statTotalProducts) statTotalProducts.textContent = state.products.length;
        if (statLowStock)      statLowStock.textContent = lowCount;
        if (statCategories)    statCategories.textContent = state.categories.length;
    }

    // ─── Categories filter ─────────────────────────────────────
    function renderCategories() {
        if (!categoriesContainer) return;

        let html = `
            <button class="category-btn flex-shrink-0 min-w-[100px] px-4 py-2.5 text-left rounded-xl border-2 transition-all text-sm
                ${state.selectedCategory === null
                    ? 'border-emerald-500 bg-emerald-50 text-emerald-700 font-semibold'
                    : 'border-slate-200 bg-white text-slate-600 hover:border-emerald-200'}"
                data-id="null">
                Todas
                <span class="block text-xs font-normal mt-0.5 opacity-70">${state.products.length}</span>
            </button>`;

        html += state.categories.map(c => `
            <div class="relative group flex-shrink-0">
                <button class="category-btn min-w-[100px] px-4 py-2.5 text-left rounded-xl border-2 transition-all text-sm w-full
                    ${state.selectedCategory == c.id
                        ? 'border-indigo-500 bg-indigo-50 text-indigo-700 font-semibold'
                        : 'border-slate-200 bg-white text-slate-600 hover:border-indigo-200'}"
                    data-id="${c.id}">
                    <span class="pr-5 block truncate max-w-[120px]">${esc(c.name)}</span>
                    <span class="block text-xs font-normal mt-0.5 opacity-70">${c.products_count || 0}</span>
                </button>
                <button class="btn-delete-category absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity p-1 rounded-lg text-slate-300 hover:text-rose-500 hover:bg-rose-50"
                    data-id="${c.id}" data-name="${esc(c.name)}" title="Eliminar categoría">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>`).join('');

        categoriesContainer.innerHTML = html;

        categoriesContainer.querySelectorAll('.category-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                state.selectedCategory = id === 'null' ? null : parseInt(id);
                renderCategories();
                renderProducts();
            });
        });

        categoriesContainer.querySelectorAll('.btn-delete-category').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.stopPropagation();
                const ok = await showConfirm(`¿Eliminar la categoría "${btn.dataset.name}"?\nLos productos asociados quedarán sin categoría.`);
                if (!ok) return;
                try {
                    await api.delete(`/api/inventory/categories/${btn.dataset.id}`);
                    const id = parseInt(btn.dataset.id);
                    state.categories = state.categories.filter(c => c.id !== id);
                    if (state.selectedCategory === id) state.selectedCategory = null;
                    state.products = state.products.map(p =>
                        p.category_id === id ? { ...p, category_id: null, category: null } : p
                    );
                    renderStats();
                    renderCategories();
                    renderProducts();
                    populateCategorySelects();
                    showToast('Categoría eliminada');
                } catch (err) {
                    showToast(err.response?.data?.message || 'Error al eliminar la categoría', 'error');
                }
            });
        });
    }

    // ─── Products table ────────────────────────────────────────
    function getFilteredProducts() {
        let products = state.products;
        if (state.selectedCategory !== null) {
            products = products.filter(p => p.category_id === state.selectedCategory);
        }
        if (state.searchQuery) {
            const q = state.searchQuery.toLowerCase();
            products = products.filter(p =>
                p.name.toLowerCase().includes(q) ||
                p.sku.toLowerCase().includes(q) ||
                (p.category?.name || '').toLowerCase().includes(q)
            );
        }
        return products;
    }

    function renderProducts() {
        const list = getFilteredProducts();

        if (list.length === 0) {
            productsTbody.innerHTML = `
                <tr><td colspan="8" class="px-6 py-12 text-center">
                    <svg class="w-10 h-10 mx-auto mb-3 text-slate-200 dark:text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    <p class="text-slate-400 dark:text-zinc-500 text-sm">${state.searchQuery ? 'Sin resultados para tu búsqueda.' : 'No hay productos registrados.'}</p>
                </td></tr>`;
            return;
        }

        productsTbody.innerHTML = list.map(p => {
            const isLow     = p.stock <= p.min_stock;
            const badgeCls  = isLow
                ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800/50'
                : 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/50';
            const badgeTxt  = isLow ? 'Stock Bajo' : 'Normal';
            const stockCls  = isLow
                ? 'text-red-600 dark:text-red-400 font-bold'
                : 'text-slate-700 dark:text-zinc-200 font-semibold';

            return `
            <tr class="border-b border-slate-100 dark:border-zinc-800 hover:bg-slate-50/60 dark:hover:bg-zinc-800/40 transition-colors">
                <td class="px-5 py-3 whitespace-nowrap text-xs font-mono text-slate-500 dark:text-zinc-400">${esc(p.sku)}</td>
                <td class="px-5 py-3 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-zinc-100">${esc(p.name)}</td>
                <td class="px-5 py-3 whitespace-nowrap text-sm text-slate-500 dark:text-zinc-400">${esc(p.category?.name || '—')}</td>
                <td class="px-5 py-3 whitespace-nowrap text-sm font-semibold text-slate-800 dark:text-zinc-100">$${fmt(p.price)}</td>
                <td class="px-5 py-3 whitespace-nowrap text-sm text-slate-500 dark:text-zinc-400">$${fmt(p.cost)}</td>
                <td class="px-5 py-3 whitespace-nowrap text-sm ${stockCls}">${p.stock}</td>
                <td class="px-5 py-3 whitespace-nowrap">
                    <span class="px-2.5 py-1 inline-flex text-xs font-semibold rounded-full border ${badgeCls}">${badgeTxt}</span>
                </td>
                <td class="px-5 py-3 whitespace-nowrap text-right">
                    <button class="btn-edit p-1.5 rounded-lg text-slate-400 dark:text-zinc-500 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors" data-id="${p.id}" title="Editar producto">
                        <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button class="btn-move p-1.5 rounded-lg text-slate-400 dark:text-zinc-500 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 transition-colors" data-id="${p.id}" title="Mover stock">
                        <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                    </button>
                    <button class="btn-delete p-1.5 rounded-lg text-slate-400 dark:text-zinc-500 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/30 transition-colors" data-id="${p.id}" title="Eliminar producto">
                        <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </td>
            </tr>`;
        }).join('');

        productsTbody.querySelectorAll('.btn-edit').forEach(btn =>
            btn.addEventListener('click', () => openEditProductModal(btn.dataset.id))
        );
        productsTbody.querySelectorAll('.btn-move').forEach(btn =>
            btn.addEventListener('click', () => openMovementModal(btn.dataset.id))
        );
        productsTbody.querySelectorAll('.btn-delete').forEach(btn =>
            btn.addEventListener('click', () => deleteProduct(btn.dataset.id))
        );
    }

    // ─── Movements table ───────────────────────────────────────
    function renderMovements() {
        if (!movementsTbody) return;

        if (state.movements.length === 0) {
            movementsTbody.innerHTML = `<tr><td colspan="7" class="px-6 py-10 text-center text-slate-400 dark:text-zinc-500 text-sm">No hay movimientos registrados.</td></tr>`;
            return;
        }

        const typeMap = {
            in:         { label: 'Entrada',  cls: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/50' },
            out:        { label: 'Salida',   cls: 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-800/50' },
            adjustment: { label: 'Ajuste',   cls: 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800/50' },
        };

        movementsTbody.innerHTML = state.movements.map(m => {
            const t       = typeMap[m.type] || { label: m.type, cls: '' };
            const qty     = m.quantity > 0 ? `+${m.quantity}` : m.quantity;
            const qtyCls  = m.quantity >= 0 ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-rose-600 dark:text-rose-400 font-bold';
            const nota    = m.notes || m.reference || '—';

            return `
            <tr class="border-b border-slate-100 dark:border-zinc-800 hover:bg-slate-50/60 dark:hover:bg-zinc-800/40 transition-colors">
                <td class="px-5 py-3 whitespace-nowrap text-xs text-slate-400 dark:text-zinc-500">${formatDate(m.created_at)}</td>
                <td class="px-5 py-3 whitespace-nowrap text-sm font-medium text-slate-800 dark:text-zinc-100">${esc(m.product?.name || '—')}</td>
                <td class="px-5 py-3 whitespace-nowrap text-xs font-mono text-slate-500 dark:text-zinc-400">${esc(m.product?.sku || '—')}</td>
                <td class="px-5 py-3 whitespace-nowrap">
                    <span class="px-2.5 py-1 inline-flex text-xs font-semibold rounded-full border ${t.cls}">${t.label}</span>
                </td>
                <td class="px-5 py-3 whitespace-nowrap text-sm ${qtyCls}">${qty}</td>
                <td class="px-5 py-3 text-sm text-slate-500 dark:text-zinc-400 max-w-[200px] truncate">${esc(nota)}</td>
                <td class="px-5 py-3 whitespace-nowrap text-sm text-slate-500 dark:text-zinc-400">${esc(m.user?.name || '—')}</td>
            </tr>`;
        }).join('');
    }

    // ─── Populate selects ──────────────────────────────────────
    function populateCategorySelects() {
        if (!categorySelect) return;
        categorySelect.innerHTML = '<option value="">Sin categoría</option>' +
            state.categories.map(c => `<option value="${c.id}">${esc(c.name)}</option>`).join('');
    }

    function populateProductSelects() {
        if (!movementProductSelect) return;
        movementProductSelect.innerHTML = state.products.map(p =>
            `<option value="${p.id}">${esc(p.name)} (${esc(p.sku)})</option>`
        ).join('');
    }

    // ─── Delete product ────────────────────────────────────────
    async function deleteProduct(id) {
        const ok = await showConfirm('¿Seguro que deseas eliminar este producto?\nEsta acción no se puede deshacer.');
        if (!ok) return;
        try {
            await api.delete(`/api/inventory/products/${id}`);
            state.products = state.products.filter(p => p.id != id);
            renderStats();
            renderCategories();
            renderProducts();
            populateProductSelects();
            showToast('Producto eliminado correctamente');
        } catch (err) {
            showToast(err.response?.data?.message || 'Error al eliminar el producto', 'error');
        }
    }

    // ─── Product modal ─────────────────────────────────────────
    function openCreateProductModal() {
        state.editingProductId = null;
        productModalTitle.textContent = 'Registrar Producto';
        productForm.reset();
        showModal(productModal);
    }

    function openEditProductModal(id) {
        const product = state.products.find(p => p.id == id);
        if (!product) return;
        state.editingProductId = id;
        productModalTitle.textContent = 'Editar Producto';
        productForm.querySelector('[name="name"]').value        = product.name;
        productForm.querySelector('[name="sku"]').value         = product.sku;
        productForm.querySelector('[name="category_id"]').value = product.category_id || '';
        productForm.querySelector('[name="price"]').value       = product.price;
        productForm.querySelector('[name="cost"]').value        = product.cost;
        productForm.querySelector('[name="min_stock"]').value   = product.min_stock;
        const descField = productForm.querySelector('[name="description"]');
        if (descField) descField.value = product.description || '';
        showModal(productModal);
    }

    addProductBtn.addEventListener('click', openCreateProductModal);
    [closeProductModal, cancelProductModal].forEach(el => el?.addEventListener('click', () => hideModal(productModal)));

    productForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(productForm));
        if (!data.category_id) delete data.category_id;

        const isEditing = !!state.editingProductId;
        const submitBtn = productForm.querySelector('[type="submit"]');
        submitBtn.disabled = true;

        try {
            let res;
            if (isEditing) {
                res = await api.put(`/api/inventory/products/${state.editingProductId}`, data);
                const idx = state.products.findIndex(p => p.id == state.editingProductId);
                if (idx !== -1) state.products[idx] = res.data;
            } else {
                res = await api.post('/api/inventory/products', data);
                state.products.unshift(res.data);
                if (data.category_id) {
                    const cat = state.categories.find(c => c.id == data.category_id);
                    if (cat) cat.products_count = (cat.products_count || 0) + 1;
                }
            }
            renderStats();
            renderCategories();
            renderProducts();
            populateProductSelects();
            hideModal(productModal);
            showToast(isEditing ? 'Producto actualizado correctamente' : 'Producto registrado con éxito');
        } catch (err) {
            const errors = err.response?.data?.errors;
            const msg = errors
                ? Object.values(errors).flat().join(' ')
                : (err.response?.data?.message || 'Error al guardar el producto');
            showToast(msg, 'error');
        } finally {
            submitBtn.disabled = false;
        }
    });

    // ─── Category modal ────────────────────────────────────────
    addCategoryBtn.addEventListener('click', () => {
        categoryForm.reset();
        showModal(categoryModal);
    });
    [closeCategoryModal, cancelCategoryModal].forEach(el => el?.addEventListener('click', () => hideModal(categoryModal)));

    categoryForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(categoryForm));
        const submitBtn = categoryForm.querySelector('[type="submit"]');
        submitBtn.disabled = true;

        try {
            const res = await api.post('/api/inventory/categories', data);
            res.data.products_count = 0;
            state.categories.push(res.data);
            renderStats();
            renderCategories();
            populateCategorySelects();
            hideModal(categoryModal);
            showToast('Categoría creada con éxito');
        } catch (err) {
            const msg = err.response?.data?.message || 'Error al guardar la categoría';
            showToast(msg, 'error');
        } finally {
            submitBtn.disabled = false;
        }
    });

    // ─── Movement modal ────────────────────────────────────────
    function openMovementModal(productId) {
        movementForm.reset();
        if (movementProductSelect) movementProductSelect.value = productId;
        const movementAlmacenSel = document.getElementById('movement-almacen');
        if (movementAlmacenSel && state.selectedAlmacen) movementAlmacenSel.value = state.selectedAlmacen;
        syncMovementStockDisplay();
        syncMovementTypeUI();
        showModal(movementModal);
    }

    [closeMovementModal, cancelMovementModal].forEach(el => el?.addEventListener('click', () => hideModal(movementModal)));

    async function syncMovementStockDisplay() {
        if (!movementProductSelect || !movementCurrentStock) return;
        const productId = movementProductSelect.value;
        if (!productId) { movementCurrentStock.textContent = '—'; return; }

        const almacenSel = document.getElementById('movement-almacen');
        const almacenId = almacenSel ? almacenSel.value : state.selectedAlmacen;

        // Si el almacén elegido en el modal es el activo, ya tenemos su stock en memoria
        if (!almacenId || String(almacenId) === String(state.selectedAlmacen)) {
            const product = state.products.find(p => p.id == productId);
            movementCurrentStock.textContent = product != null ? product.stock : '—';
            return;
        }

        // El usuario eligió otro almacén dentro del modal: consultar su stock específico
        try {
            const { data } = await api.get(`/api/inventory/products?almacen_id=${almacenId}`);
            const product = data.find(p => p.id == productId);
            movementCurrentStock.textContent = product != null ? product.stock : '—';
        } catch (err) {
            movementCurrentStock.textContent = '—';
        }
    }

    function syncMovementTypeUI() {
        if (!movementTypeSelect || !movementQtyLabel || !movementQtyInput || !adjustmentHint) return;
        const isAdj = movementTypeSelect.value === 'adjustment';
        movementQtyLabel.textContent = isAdj ? 'Cantidad de ajuste' : 'Cantidad';
        movementQtyInput.min         = isAdj ? '-999999' : '1';
        movementQtyInput.placeholder = isAdj ? 'Ej: -5 ó +10' : '';
        adjustmentHint.classList.toggle('hidden', !isAdj);
    }

    if (movementProductSelect) movementProductSelect.addEventListener('change', syncMovementStockDisplay);
    if (movementTypeSelect)    movementTypeSelect.addEventListener('change', syncMovementTypeUI);
    const movementAlmacenSelect = document.getElementById('movement-almacen');
    if (movementAlmacenSelect) movementAlmacenSelect.addEventListener('change', syncMovementStockDisplay);

    movementForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(movementForm));
        const submitBtn = movementForm.querySelector('[type="submit"]');
        submitBtn.disabled = true;

        try {
            const res = await api.post('/api/inventory/movements', data);
            // Solo insertarlo directo si pertenece al almacén activo; si no, refrescar desde el servidor
            if (!state.selectedAlmacen || String(res.data.almacen_id) === String(state.selectedAlmacen)) {
                state.movements.unshift(res.data);
            } else {
                await fetchMovements();
            }
            // Refrescar stock por almacén activo (el response no incluye el stock por almacén)
            await fetchProducts();
            renderStats();
            renderProducts();
            renderMovements();
            populateProductSelects();
            hideModal(movementModal);
            showToast('Movimiento registrado correctamente');
        } catch (err) {
            const msg = err.response?.data?.error || err.response?.data?.message || 'Error al registrar el movimiento';
            showToast(msg, 'error');
        } finally {
            submitBtn.disabled = false;
        }
    });

    // ─── Almacenes ─────────────────────────────────────────────
    let almacenes = [];       // solo activos — para selectores operativos
    let almacenesAll = [];    // activos + inactivos — para la gestión en la pestaña Almacenes

    async function fetchAlmacenes() {
        const { data } = await api.get('/api/inventory/almacenes');
        almacenes = data;

        // Resolver almacén activo: el guardado si sigue accesible, si no el Principal o el primero
        const ids = almacenes.map(a => String(a.id));
        if (!state.selectedAlmacen || !ids.includes(String(state.selectedAlmacen))) {
            const principal = almacenes.find(a => a.es_principal) || almacenes[0];
            state.selectedAlmacen = principal ? String(principal.id) : null;
            if (state.selectedAlmacen) localStorage.setItem('inventory_almacen', state.selectedAlmacen);
        }

        populateAlmacenSelects();
        populateGlobalAlmacenSelector();
    }

    function populateAlmacenSelects() {
        const sel = document.getElementById('movement-almacen');
        if (sel) {
            sel.innerHTML = almacenes.map(a =>
                `<option value="${a.id}">${esc(a.nombre)} (${esc(a.codigo)})</option>`
            ).join('');
            if (state.selectedAlmacen) sel.value = state.selectedAlmacen;
        }
    }

    // ── Selector global de almacén activo ──
    const globalAlmacenSelector = document.getElementById('almacen-selector');

    function populateGlobalAlmacenSelector() {
        if (!globalAlmacenSelector) return;
        if (!almacenes.length) {
            globalAlmacenSelector.innerHTML = '<option value="">Sin almacenes</option>';
            return;
        }
        globalAlmacenSelector.innerHTML = almacenes.map(a =>
            `<option value="${a.id}">${esc(a.nombre)} (${esc(a.codigo)})${a.es_principal ? ' ★' : ''}</option>`
        ).join('');
        if (state.selectedAlmacen) globalAlmacenSelector.value = state.selectedAlmacen;
    }

    globalAlmacenSelector?.addEventListener('change', async () => {
        state.selectedAlmacen = globalAlmacenSelector.value || null;
        if (state.selectedAlmacen) localStorage.setItem('inventory_almacen', state.selectedAlmacen);
        else localStorage.removeItem('inventory_almacen');

        // Sincronizar selects dependientes y recargar datos del almacén activo
        populateAlmacenSelects();
        try {
            await Promise.all([fetchProducts(), fetchMovements()]);
            renderStats();
            renderCategories();
            renderProducts();
            renderMovements();
            fetchAndRenderAlertas().catch(() => {});
        } catch (err) {
            showToast('Error al cambiar de almacén', 'error');
        }
    });

    async function fetchAlmacenesAll() {
        try {
            const { data } = await api.get('/api/inventory/almacenes', { activos: 0 });
            almacenesAll = data;
        } catch (err) {
            almacenesAll = [];
        }
    }

    async function renderAlmacenesGrid() {
        const grid = document.getElementById('almacenes-grid');
        if (!grid) return;

        // La gestión incluye almacenes inactivos (para poder reactivarlos).
        await fetchAlmacenesAll();

        if (!almacenesAll.length) {
            grid.innerHTML = '<p class="text-slate-400 dark:text-zinc-500 text-sm col-span-3">No hay almacenes registrados.</p>';
            return;
        }

        grid.innerHTML = almacenesAll.map(a => `
            <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 border-l-[3px] ${a.es_principal ? 'border-l-amber-500' : 'border-l-emerald-500'} rounded-2xl p-5">
                <div class="flex items-start justify-between mb-3">
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-800 dark:text-zinc-100 truncate">${esc(a.nombre)}</p>
                        <p class="text-xs text-slate-400 dark:text-zinc-500 font-mono mt-0.5">${esc(a.codigo)}</p>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full flex-shrink-0 ${a.activo
                        ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400'
                        : 'bg-slate-100 dark:bg-zinc-800 text-slate-500 dark:text-zinc-400'}">
                        ${a.activo ? 'Activo' : 'Inactivo'}
                    </span>
                </div>
                ${a.descripcion ? `<p class="text-xs text-slate-500 dark:text-zinc-400 mb-3 line-clamp-2">${esc(a.descripcion)}</p>` : ''}
                ${a.direccion ? `<p class="text-xs text-slate-400 dark:text-zinc-500 mb-3 truncate"><svg class="inline w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>${esc(a.direccion)}</p>` : ''}
                <div class="flex items-center justify-between text-xs text-slate-400 dark:text-zinc-500 pt-2 border-t border-slate-100 dark:border-zinc-800">
                    <span>${a.total_productos ?? 0} SKUs con stock</span>
                    ${a.es_principal ? '<span class="text-amber-600 dark:text-amber-400 font-semibold">Principal</span>' : ''}
                </div>
                <div class="flex flex-wrap gap-2 mt-3">
                    ${a.puede_gestionar ? `<button class="btn-editar-almacen flex-1 min-w-[72px] text-xs px-3 py-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 font-medium transition-colors" data-id="${a.id}">
                        Editar
                    </button>` : ''}
                    ${(a.puede_gestionar && (!a.es_principal || !a.activo)) ? `<button class="btn-toggle-almacen flex-1 min-w-[72px] text-xs px-3 py-1.5 rounded-lg font-medium transition-colors ${a.activo
                        ? 'bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-300 hover:bg-slate-200 dark:hover:bg-zinc-700'
                        : 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/40'}" data-id="${a.id}" data-activo="${a.activo ? 1 : 0}">
                        ${a.activo ? 'Desactivar' : 'Activar'}
                    </button>` : ''}
                    ${a.puede_gestionar ? `<button class="btn-permisos-almacen flex-1 min-w-[72px] text-xs px-3 py-1.5 rounded-lg bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-900/40 font-medium transition-colors" data-id="${a.id}" data-nombre="${esc(a.nombre)}">
                        Permisos
                    </button>` : ''}
                    ${(a.puede_gestionar && !a.es_principal) ? `<button class="btn-eliminar-almacen flex-1 min-w-[72px] text-xs px-3 py-1.5 rounded-lg bg-rose-50 dark:bg-rose-900/20 text-rose-700 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900/40 font-medium transition-colors" data-id="${a.id}" data-nombre="${esc(a.nombre)}">
                        Eliminar
                    </button>` : ''}
                </div>
            </div>`).join('');

        grid.querySelectorAll('.btn-editar-almacen').forEach(btn =>
            btn.addEventListener('click', () => openAlmacenModal(parseInt(btn.dataset.id)))
        );
        grid.querySelectorAll('.btn-toggle-almacen').forEach(btn =>
            btn.addEventListener('click', () => toggleAlmacenActivo(parseInt(btn.dataset.id), btn.dataset.activo === '1'))
        );
        grid.querySelectorAll('.btn-eliminar-almacen').forEach(btn =>
            btn.addEventListener('click', () => eliminarAlmacen(parseInt(btn.dataset.id), btn.dataset.nombre))
        );
        grid.querySelectorAll('.btn-permisos-almacen').forEach(btn =>
            btn.addEventListener('click', () => openPermisosModal(parseInt(btn.dataset.id), btn.dataset.nombre))
        );
    }

    async function toggleAlmacenActivo(id, isActive) {
        try {
            await api.put(`/api/inventory/almacenes/${id}`, { activo: !isActive });
            // Refresca selectores operativos (solo activos) y el grid (incluye inactivos).
            await fetchAlmacenes();
            await renderAlmacenesGrid();
            showToast(isActive ? 'Almacén desactivado' : 'Almacén activado');
        } catch (err) {
            showToast(err.response?.data?.error || 'Error al actualizar el almacén', 'error');
        }
    }

    async function eliminarAlmacen(id, nombre) {
        const ok = await showConfirm(`¿Eliminar el almacén "${nombre}"?\nNo se puede eliminar si tiene stock activo.`);
        if (!ok) return;
        try {
            await api.delete(`/api/inventory/almacenes/${id}`);
            await fetchAlmacenes();
            renderAlmacenesGrid();
            showToast('Almacén eliminado correctamente');
        } catch (err) {
            showToast(err.response?.data?.error || 'Error al eliminar el almacén', 'error');
        }
    }

    // ── Modal de almacén (crear/editar) ──
    let editingAlmacenId = null;
    const almacenModal      = document.getElementById('almacen-modal');
    const almacenForm       = document.getElementById('almacen-form');
    const almacenModalTitle = document.getElementById('almacen-modal-title');
    const closeAlmacenModalBtn  = document.getElementById('close-almacen-modal');
    const cancelAlmacenModalBtn = document.getElementById('cancel-almacen-modal');

    function openAlmacenModal(id = null) {
        editingAlmacenId = id;
        almacenForm.reset();
        if (id) {
            const a = almacenesAll.find(x => x.id === id) || almacenes.find(x => x.id === id);
            if (!a) return;
            almacenModalTitle.textContent = 'Editar Almacén';
            almacenForm.querySelector('[name="nombre"]').value      = a.nombre;
            almacenForm.querySelector('[name="codigo"]').value      = a.codigo;
            almacenForm.querySelector('[name="descripcion"]').value = a.descripcion ?? '';
            almacenForm.querySelector('[name="direccion"]').value   = a.direccion ?? '';
            almacenForm.querySelector('[name="activo"]').checked    = !!a.activo;
        } else {
            almacenModalTitle.textContent = 'Nuevo Almacén';
            almacenForm.querySelector('[name="activo"]').checked = true;
        }
        showModal(almacenModal);
    }

    document.getElementById('btn-nuevo-almacen')?.addEventListener('click', () => openAlmacenModal());
    [closeAlmacenModalBtn, cancelAlmacenModalBtn].forEach(el => el?.addEventListener('click', () => hideModal(almacenModal)));
    almacenModal?.addEventListener('click', (e) => {
        if (e.target === almacenModal || e.target.classList.contains('absolute')) hideModal(almacenModal);
    });

    almacenForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(almacenForm));
        data.activo = almacenForm.querySelector('[name="activo"]').checked;
        const submitBtn = almacenForm.querySelector('[type="submit"]');
        submitBtn.disabled = true;
        try {
            if (editingAlmacenId) {
                await api.put(`/api/inventory/almacenes/${editingAlmacenId}`, data);
                showToast('Almacén actualizado correctamente');
            } else {
                await api.post('/api/inventory/almacenes', data);
                showToast('Almacén creado correctamente');
            }
            await fetchAlmacenes();
            renderAlmacenesGrid();
            hideModal(almacenModal);
        } catch (err) {
            const errors = err.response?.data?.errors;
            const msg = errors ? Object.values(errors).flat().join(' ') : (err.response?.data?.message || 'Error al guardar el almacén');
            showToast(msg, 'error');
        } finally {
            submitBtn.disabled = false;
        }
    });

    // ── Modal de permisos de almacén ──
    const permisosModal      = document.getElementById('permisos-modal');
    const permisosNombre     = document.getElementById('permisos-almacen-nombre');
    const permisosLista      = document.getElementById('permisos-lista');
    const permisosAddSelect  = document.getElementById('permisos-add-select');
    const permisosAddBtn     = document.getElementById('permisos-add-btn');
    const closePermisosBtn   = document.getElementById('close-permisos-modal');
    let permisosAlmacenId = null;

    async function openPermisosModal(id, nombre) {
        permisosAlmacenId = id;
        permisosNombre.textContent = nombre;
        permisosLista.innerHTML = '<li class="text-sm text-slate-400 dark:text-zinc-500">Cargando…</li>';
        permisosAddSelect.innerHTML = '<option value="">Selecciona un usuario…</option>';
        showModal(permisosModal);
        await Promise.all([cargarPermisosLista(), cargarPermisosDisponibles()]);
    }

    async function cargarPermisosLista() {
        try {
            const { data } = await api.get(`/api/inventory/almacenes/${permisosAlmacenId}/usuarios`);
            if (!data.length) {
                permisosLista.innerHTML = '<li class="text-sm text-slate-400 dark:text-zinc-500">Sin usuarios.</li>';
                return;
            }
            permisosLista.innerHTML = data.map(u => `
                <li class="flex items-center justify-between gap-3 bg-slate-50 dark:bg-zinc-800/60 rounded-xl px-3 py-2">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-800 dark:text-zinc-100 truncate">${esc(u.name)}${u.es_creador ? ' <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400">DUEÑO</span>' : ''}</p>
                        <p class="text-xs text-slate-400 dark:text-zinc-500 truncate">${esc(u.email)}</p>
                    </div>
                    ${u.es_creador ? '' : `<button class="btn-quitar-permiso text-xs text-rose-600 dark:text-rose-400 hover:text-rose-700 dark:hover:text-rose-300 font-semibold flex-shrink-0" data-id="${u.id}">Quitar</button>`}
                </li>`).join('');

            permisosLista.querySelectorAll('.btn-quitar-permiso').forEach(btn =>
                btn.addEventListener('click', () => quitarPermiso(parseInt(btn.dataset.id)))
            );
        } catch (err) {
            permisosLista.innerHTML = '<li class="text-sm text-rose-500">Error al cargar.</li>';
        }
    }

    async function cargarPermisosDisponibles() {
        try {
            const { data } = await api.get(`/api/inventory/almacenes/${permisosAlmacenId}/usuarios-disponibles`);
            permisosAddSelect.innerHTML = '<option value="">Selecciona un usuario…</option>' +
                data.map(u => `<option value="${u.id}">${esc(u.name)} — ${esc(u.email)}</option>`).join('');
        } catch (err) {
            // silencioso
        }
    }

    permisosAddBtn?.addEventListener('click', async () => {
        const userId = permisosAddSelect.value;
        if (!userId) return;
        permisosAddBtn.disabled = true;
        try {
            await api.post(`/api/inventory/almacenes/${permisosAlmacenId}/usuarios`, { user_id: parseInt(userId) });
            showToast('Acceso concedido.');
            await Promise.all([cargarPermisosLista(), cargarPermisosDisponibles()]);
        } catch (err) {
            showToast(err.response?.data?.error || 'Error al conceder acceso', 'error');
        } finally {
            permisosAddBtn.disabled = false;
        }
    });

    async function quitarPermiso(userId) {
        const ok = await showConfirm('¿Revocar el acceso de este usuario al almacén?');
        if (!ok) return;
        try {
            await api.delete(`/api/inventory/almacenes/${permisosAlmacenId}/usuarios/${userId}`);
            showToast('Acceso revocado.');
            await Promise.all([cargarPermisosLista(), cargarPermisosDisponibles()]);
        } catch (err) {
            showToast(err.response?.data?.error || 'Error al revocar acceso', 'error');
        }
    }

    closePermisosBtn?.addEventListener('click', () => hideModal(permisosModal));
    permisosModal?.addEventListener('click', (e) => {
        if (e.target === permisosModal || e.target.classList.contains('absolute')) hideModal(permisosModal);
    });

    // ─── Transferencias ────────────────────────────────────────
    async function fetchAndRenderTransferencias() {
        try {
            const { data } = await api.get('/api/inventory/transferencias');
            const tbody = document.getElementById('tbody-transferencias');
            if (!tbody) return;
            const items = data.data ?? data;
            if (!items.length) {
                tbody.innerHTML = '<tr><td colspan="7" class="px-5 py-6 text-center text-slate-400 dark:text-zinc-500">No hay transferencias registradas.</td></tr>';
                return;
            }
            const estadoMap = {
                borrador:    { label: 'Borrador',    cls: 'bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-300' },
                en_transito: { label: 'En tránsito', cls: 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400' },
                recibida:    { label: 'Recibida',    cls: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' },
                cancelada:   { label: 'Cancelada',   cls: 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400' },
            };
            tbody.innerHTML = items.map(t => {
                const est = estadoMap[t.estado] ?? { label: t.estado, cls: 'bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-300' };
                const fechaFmt = t.fecha ? new Date(t.fecha).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
                return `<tr class="border-b border-slate-100 dark:border-zinc-800 hover:bg-slate-50 dark:hover:bg-zinc-800/40 transition-colors">
                    <td class="px-5 py-3.5 text-sm text-slate-600 dark:text-zinc-300">${esc(fechaFmt)}</td>
                    <td class="px-5 py-3.5 text-sm font-medium text-slate-800 dark:text-zinc-100">${esc(t.almacen_origen?.nombre ?? '—')}</td>
                    <td class="px-5 py-3.5 text-sm font-medium text-slate-800 dark:text-zinc-100">${esc(t.almacen_destino?.nombre ?? '—')}</td>
                    <td class="px-5 py-3.5 text-center text-sm text-slate-600 dark:text-zinc-300">${t.items_count ?? '—'}</td>
                    <td class="px-5 py-3.5"><span class="text-xs font-semibold px-2 py-0.5 rounded-full ${est.cls}">${est.label}</span></td>
                    <td class="px-5 py-3.5 text-sm text-slate-500 dark:text-zinc-400">${esc(t.user?.name ?? '—')}</td>
                    <td class="px-5 py-3.5 text-right whitespace-nowrap">
                        ${t.estado === 'borrador' ? `<button data-action="enviar" data-id="${t.id}" class="btn-transferencia-action text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1 rounded-lg transition-colors">Enviar</button>` : ''}
                        ${t.estado === 'en_transito' ? `<button data-action="recibir" data-id="${t.id}" class="btn-transferencia-action text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded-lg transition-colors">Recibir</button>` : ''}
                        ${t.estado === 'borrador' ? `<button data-action="cancelar" data-id="${t.id}" class="btn-transferencia-action ml-1 text-xs bg-rose-50 dark:bg-rose-900/20 text-rose-700 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900/40 px-3 py-1 rounded-lg transition-colors">Cancelar</button>` : ''}
                    </td>
                </tr>`;
            }).join('');

            tbody.querySelectorAll('.btn-transferencia-action').forEach(btn => {
                btn.addEventListener('click', () => handleTransferenciaAction(btn.dataset.action, parseInt(btn.dataset.id)));
            });
        } catch (err) {
            console.error(err);
        }
    }

    async function handleTransferenciaAction(action, id) {
        if (action === 'enviar') {
            const ok = await showConfirm('¿Confirmar envío?\nSe descontará el stock del almacén origen.');
            if (!ok) return;
            try {
                await api.post(`/api/inventory/transferencias/${id}/enviar`);
                showToast('Transferencia enviada. En tránsito.');
                await Promise.all([fetchAndRenderTransferencias(), fetchProducts()]);
                renderProducts();
            } catch (err) {
                showToast(err.response?.data?.error ?? 'Error al enviar', 'error');
            }
        } else if (action === 'recibir') {
            const ok = await showConfirm('¿Confirmar recepción?\nSe acreditará el stock en el almacén destino.');
            if (!ok) return;
            try {
                await api.post(`/api/inventory/transferencias/${id}/recibir`, { items: [] });
                showToast('Transferencia recibida.');
                await Promise.all([fetchAndRenderTransferencias(), fetchProducts()]);
                renderProducts();
            } catch (err) {
                showToast(err.response?.data?.error ?? 'Error al recibir', 'error');
            }
        } else if (action === 'cancelar') {
            const ok = await showConfirm('¿Cancelar esta transferencia en borrador?');
            if (!ok) return;
            try {
                await api.delete(`/api/inventory/transferencias/${id}`);
                showToast('Transferencia cancelada.');
                await fetchAndRenderTransferencias();
            } catch (err) {
                showToast(err.response?.data?.error ?? 'Error al cancelar', 'error');
            }
        }
    }

    // ── Modal de crear transferencia ──
    const transferenciaModal     = document.getElementById('transferencia-modal');
    const transferenciaForm      = document.getElementById('transferencia-form');
    const transfOrigen           = document.getElementById('transf-origen');
    const transfDestino          = document.getElementById('transf-destino');
    const transfFecha            = document.getElementById('transf-fecha');
    const transfItemsContainer   = document.getElementById('transf-items');
    const closeTransferenciaBtn  = document.getElementById('close-transferencia-modal');
    const cancelTransferenciaBtn = document.getElementById('cancel-transferencia-modal');
    const addTransfItemBtn       = document.getElementById('transf-add-item');

    // Stock de productos según el almacén de origen elegido en el modal (no siempre es el activo)
    let transfProducts = state.products;

    function transfProductOptions() {
        return transfProducts.map(p => `<option value="${p.id}">${esc(p.name)} (${esc(p.sku)}) — stock ${p.stock}</option>`).join('');
    }

    async function refreshTransfProducts() {
        const almacenId = transfOrigen.value;
        if (!almacenId || String(almacenId) === String(state.selectedAlmacen)) {
            transfProducts = state.products;
        } else {
            try {
                const { data } = await api.get(`/api/inventory/products?almacen_id=${almacenId}`);
                transfProducts = data;
            } catch (err) {
                transfProducts = [];
            }
        }
        // Refrescar el stock mostrado en las filas ya agregadas, conservando la selección
        transfItemsContainer.querySelectorAll('.transf-item-product').forEach(sel => {
            const current = sel.value;
            sel.innerHTML = transfProductOptions();
            if (current) sel.value = current;
        });
    }

    transfOrigen?.addEventListener('change', refreshTransfProducts);

    function renderTransfItemRow() {
        const row = document.createElement('div');
        row.className = 'flex gap-2 items-center transf-item-row';
        row.innerHTML = `
            <select class="transf-item-product flex-1 rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-800 dark:text-zinc-100 text-sm focus:border-emerald-500 focus:ring-emerald-500 py-2 px-2">
                ${transfProductOptions()}
            </select>
            <input type="number" min="1" class="transf-item-qty w-24 rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-800 dark:text-zinc-100 text-sm focus:border-emerald-500 focus:ring-emerald-500 py-2 px-2" placeholder="Cant." required value="1">
            <button type="button" class="transf-item-remove text-rose-500 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300 p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>`;
        row.querySelector('.transf-item-remove').addEventListener('click', () => row.remove());
        transfItemsContainer.appendChild(row);
    }

    async function openTransferenciaModal() {
        transferenciaForm.reset();
        // Llenar selects de almacenes (filtra solo activos)
        const opts = almacenes.filter(a => a.activo).map(a => `<option value="${a.id}">${esc(a.nombre)} (${esc(a.codigo)})</option>`).join('');
        transfOrigen.innerHTML  = opts;
        transfDestino.innerHTML = opts;
        // Origen por defecto: el almacén activo
        if (state.selectedAlmacen) transfOrigen.value = state.selectedAlmacen;
        // Destino: el primero distinto al origen
        const destino = almacenes.find(a => a.activo && String(a.id) !== String(transfOrigen.value));
        if (destino) transfDestino.value = destino.id;
        transfFecha.value = new Date().toISOString().slice(0, 10);
        transfItemsContainer.innerHTML = '';
        await refreshTransfProducts();
        renderTransfItemRow();
        showModal(transferenciaModal);
    }

    document.getElementById('btn-nueva-transferencia')?.addEventListener('click', () => {
        if (almacenes.length < 2) {
            showToast('Necesitas al menos 2 almacenes para crear una transferencia.', 'warning');
            return;
        }
        if (!state.products.length) {
            showToast('No hay productos registrados.', 'warning');
            return;
        }
        openTransferenciaModal();
    });

    addTransfItemBtn?.addEventListener('click', renderTransfItemRow);
    [closeTransferenciaBtn, cancelTransferenciaBtn].forEach(el => el?.addEventListener('click', () => hideModal(transferenciaModal)));
    transferenciaModal?.addEventListener('click', (e) => {
        if (e.target === transferenciaModal || e.target.classList.contains('absolute')) hideModal(transferenciaModal);
    });

    transferenciaForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (transfOrigen.value === transfDestino.value) {
            showToast('El origen y destino deben ser diferentes.', 'error');
            return;
        }
        const items = Array.from(transfItemsContainer.querySelectorAll('.transf-item-row')).map(row => ({
            product_id: parseInt(row.querySelector('.transf-item-product').value),
            cantidad:   parseInt(row.querySelector('.transf-item-qty').value),
        })).filter(i => i.product_id && i.cantidad > 0);

        if (!items.length) {
            showToast('Agrega al menos un producto.', 'error');
            return;
        }

        const payload = {
            almacen_origen_id:  parseInt(transfOrigen.value),
            almacen_destino_id: parseInt(transfDestino.value),
            fecha:              transfFecha.value,
            referencia:         transferenciaForm.querySelector('[name="referencia"]').value || null,
            notas:              transferenciaForm.querySelector('[name="notas"]').value || null,
            items,
        };

        const submitBtn = transferenciaForm.querySelector('[type="submit"]');
        submitBtn.disabled = true;
        try {
            await api.post('/api/inventory/transferencias', payload);
            showToast('Transferencia creada en borrador.');
            hideModal(transferenciaModal);
            await fetchAndRenderTransferencias();
        } catch (err) {
            const errors = err.response?.data?.errors;
            const msg = errors ? Object.values(errors).flat().join(' ') : (err.response?.data?.error || err.response?.data?.message || 'Error al crear');
            showToast(msg, 'error');
        } finally {
            submitBtn.disabled = false;
        }
    });

    // ─── Alertas de Stock ──────────────────────────────────────
    async function fetchAndRenderAlertas() {
        try {
            const params = state.selectedAlmacen ? `?almacen_id=${state.selectedAlmacen}` : '';
            const { data } = await api.get(`/api/inventory/alertas${params}`);
            const tbody  = document.getElementById('tbody-alertas');
            const count  = document.getElementById('alertas-count');
            const badge  = document.getElementById('alertas-badge');
            const items  = data.data ?? data;
            const total  = data.total ?? items.length;

            if (count) count.textContent = `${total} alerta${total !== 1 ? 's' : ''}`;
            if (badge) badge.classList.toggle('hidden', total === 0);

            if (!tbody) return;
            if (!items.length) {
                tbody.innerHTML = '<tr><td colspan="7" class="px-5 py-6 text-center text-emerald-600 dark:text-emerald-400">Sin alertas activas. Stock saludable.</td></tr>';
                return;
            }
            tbody.innerHTML = items.map(a => {
                const tipo = a.tipo === 'bajo_minimo' ? 'Bajo mínimo' : 'Punto reorden';
                return `<tr class="border-b border-slate-100 dark:border-zinc-800 hover:bg-slate-50 dark:hover:bg-zinc-800/40 transition-colors">
                    <td class="px-5 py-3 text-sm font-mono text-slate-500 dark:text-zinc-400">${esc(a.product?.sku ?? '—')}</td>
                    <td class="px-5 py-3 text-sm font-medium text-slate-800 dark:text-zinc-100">${esc(a.product?.name ?? '—')}</td>
                    <td class="px-5 py-3 text-sm text-slate-500 dark:text-zinc-400">${esc(a.almacen?.nombre ?? 'Global')}</td>
                    <td class="px-5 py-3"><span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400">${tipo}</span></td>
                    <td class="px-5 py-3 text-center font-mono text-rose-600 dark:text-rose-400 font-semibold">${a.stock_actual}</td>
                    <td class="px-5 py-3 text-center font-mono text-slate-500 dark:text-zinc-400">${a.stock_minimo}</td>
                    <td class="px-5 py-3 text-right">
                        <button data-id="${a.id}" class="btn-resolver-alerta text-xs text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 font-semibold">Resolver</button>
                    </td>
                </tr>`;
            }).join('');

            tbody.querySelectorAll('.btn-resolver-alerta').forEach(btn => {
                btn.addEventListener('click', async () => {
                    btn.disabled = true;
                    try {
                        await api.patch(`/api/inventory/alertas/${btn.dataset.id}/resolver`);
                        btn.closest('tr').remove();
                        showToast('Alerta resuelta.');
                    } catch {
                        btn.disabled = false;
                    }
                });
            });
        } catch (err) { console.error(err); }
    }

    // ─── Tabs (actualizado con nuevos tabs) ────────────────────
    const allSections = {
        products:      document.getElementById('section-products'),
        movements:     document.getElementById('section-movements'),
        almacenes:     document.getElementById('section-almacenes'),
        transferencias: document.getElementById('section-transferencias'),
        alertas:       document.getElementById('section-alertas'),
    };

    const allTabBtns = {
        products:      tabProductsBtn,
        movements:     tabMovementsBtn,
        almacenes:     document.getElementById('tab-almacenes'),
        transferencias: document.getElementById('tab-transferencias'),
        alertas:       document.getElementById('tab-alertas'),
    };

    function switchTab(tab) {
        state.currentTab = tab;

        Object.entries(allSections).forEach(([key, el]) => {
            if (!el) return;
            el.classList.toggle('hidden', key !== tab);
        });

        Object.entries(allTabBtns).forEach(([key, btn]) => {
            if (!btn) return;
            const isActive = key === tab;
            const isAlertas = key === 'alertas';

            // Reset all variant classes that might toggle
            btn.classList.remove(
                'border-emerald-500', 'text-emerald-600', 'dark:text-emerald-400',
                'border-rose-500', 'text-rose-600', 'dark:text-rose-400',
                'border-transparent', 'text-slate-500', 'dark:text-zinc-500',
                'text-rose-500',
            );

            if (isActive) {
                if (isAlertas) {
                    btn.classList.add('border-rose-500', 'text-rose-600', 'dark:text-rose-400');
                } else {
                    btn.classList.add('border-emerald-500', 'text-emerald-600', 'dark:text-emerald-400');
                }
            } else {
                btn.classList.add('border-transparent');
                if (isAlertas) {
                    btn.classList.add('text-rose-500', 'dark:text-rose-400');
                } else {
                    btn.classList.add('text-slate-500', 'dark:text-zinc-500');
                }
            }
        });

        if (tab === 'almacenes') renderAlmacenesGrid();
        if (tab === 'transferencias') fetchAndRenderTransferencias();
        if (tab === 'alertas') fetchAndRenderAlertas();
    }

    Object.entries(allTabBtns).forEach(([key, btn]) => {
        btn?.addEventListener('click', () => switchTab(key));
    });

    // ─── Search ────────────────────────────────────────────────
    searchInput?.addEventListener('input', (e) => {
        state.searchQuery = e.target.value.trim();
        renderProducts();
    });

    // ─── Modal helpers ─────────────────────────────────────────
    function showModal(modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function hideModal(modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Close modal on backdrop click
    [productModal, movementModal, categoryModal].forEach(modal => {
        modal?.addEventListener('click', (e) => {
            if (e.target === modal || e.target.classList.contains('absolute')) {
                hideModal(modal);
            }
        });
    });

    // ─── Loading ───────────────────────────────────────────────
    function showLoading() {
        loadingState?.classList.remove('hidden');
        productsTbody?.classList.add('hidden');
    }

    function hideLoading() {
        loadingState?.classList.add('hidden');
        productsTbody?.classList.remove('hidden');
    }

    // ─── Importar CSV ──────────────────────────────────────────
    const importBtn          = document.getElementById('import-products-btn');
    const importModal        = document.getElementById('import-modal');
    const closeImportModal   = document.getElementById('close-import-modal');
    const cancelImportModal  = document.getElementById('cancel-import-modal');
    const importForm         = document.getElementById('import-form');
    const importFileInput    = document.getElementById('import-file');
    const importResult       = document.getElementById('import-result');
    const importSubmitBtn    = document.getElementById('import-submit-btn');
    const downloadTemplateBtn = document.getElementById('download-template-btn');

    function openImportModal() {
        importForm?.reset();
        if (importResult) {
            importResult.classList.add('hidden');
            importResult.textContent = '';
        }
        importModal?.classList.remove('hidden');
    }
    function closeImport() {
        importModal?.classList.add('hidden');
    }

    importBtn?.addEventListener('click', openImportModal);
    closeImportModal?.addEventListener('click', closeImport);
    cancelImportModal?.addEventListener('click', closeImport);
    importModal?.addEventListener('click', (e) => { if (e.target === importModal.firstElementChild) closeImport(); });

    downloadTemplateBtn?.addEventListener('click', () => {
        window.location.href = '/api/inventory/products-template';
    });

    importForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const file = importFileInput?.files?.[0];
        if (!file) return;

        if (file.size > 5 * 1024 * 1024) {
            showToast('Archivo excede 5 MB', 'error');
            return;
        }

        const fd = new FormData();
        fd.append('file', file);

        importSubmitBtn.disabled = true;
        importSubmitBtn.textContent = 'Importando…';
        importResult.classList.add('hidden');

        try {
            const { data } = await api.post('/api/inventory/products-import', fd);

            const msg = `Creados: ${data.created} · Actualizados: ${data.updated}` +
                        (data.error_count ? ` · Errores: ${data.error_count}` : '');
            importResult.textContent = msg;
            importResult.className = 'text-sm rounded-xl p-3 ' +
                (data.error_count
                    ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-300 border border-amber-100 dark:border-amber-800/40'
                    : 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-300 border border-emerald-100 dark:border-emerald-800/40');
            importResult.classList.remove('hidden');

            if (data.errors && data.errors.length) {
                const details = document.createElement('details');
                details.className = 'mt-2 text-xs';
                details.innerHTML = `<summary class="cursor-pointer">Ver errores</summary><ul class="list-disc list-inside mt-1">${data.errors.map(e => `<li>${e.replace(/</g,'&lt;')}</li>`).join('')}</ul>`;
                importResult.appendChild(details);
            }

            showToast('Importación completada');
            await Promise.all([fetchCategories(), fetchProducts()]);
            renderStats();
            renderCategories();
            renderProducts();
            populateCategorySelects();
            populateProductSelects();
        } catch (err) {
            const msg = err.response?.data?.message || 'Error al importar';
            importResult.textContent = msg;
            importResult.className = 'text-sm rounded-xl p-3 bg-rose-50 dark:bg-rose-900/20 text-rose-800 dark:text-rose-300 border border-rose-100 dark:border-rose-800/40';
            importResult.classList.remove('hidden');
            showToast(msg, 'error');
        } finally {
            importSubmitBtn.disabled = false;
            importSubmitBtn.textContent = 'Importar';
        }
    });

    // ─── Asistente IA (chatbot) ────────────────────────────────
    const aiFab      = document.getElementById('ai-fab');
    const aiPanel    = document.getElementById('ai-panel');
    const aiDrawer   = document.getElementById('ai-drawer');
    const aiOverlay  = document.getElementById('ai-overlay');
    const aiCloseBtn = document.getElementById('ai-close');
    const aiForm     = document.getElementById('ai-form');
    const aiInput    = document.getElementById('ai-input');
    const aiSend     = document.getElementById('ai-send');
    const aiMessages = document.getElementById('ai-messages');
    let aiBusy = false;

    function openAiPanel() {
        aiPanel?.classList.remove('hidden');
        requestAnimationFrame(() => aiDrawer?.classList.remove('translate-x-full'));
        setTimeout(() => aiInput?.focus(), 200);
    }
    function closeAiPanel() {
        aiDrawer?.classList.add('translate-x-full');
        setTimeout(() => aiPanel?.classList.add('hidden'), 200);
    }

    aiFab?.addEventListener('click', openAiPanel);
    aiCloseBtn?.addEventListener('click', closeAiPanel);
    aiOverlay?.addEventListener('click', closeAiPanel);

    function appendAiMessage(text, who) {
        const wrap = document.createElement('div');
        wrap.className = who === 'user' ? 'flex justify-end' : 'flex justify-start';
        const bubble = document.createElement('div');
        bubble.className = who === 'user'
            ? 'max-w-[85%] bg-emerald-600 text-white rounded-2xl rounded-br-sm px-3.5 py-2 text-sm whitespace-pre-wrap'
            : 'max-w-[85%] bg-slate-100 dark:bg-zinc-800 text-slate-800 dark:text-zinc-100 rounded-2xl rounded-bl-sm px-3.5 py-2 text-sm whitespace-pre-wrap';
        bubble.textContent = text;
        wrap.appendChild(bubble);
        aiMessages?.appendChild(wrap);
        if (aiMessages) aiMessages.scrollTop = aiMessages.scrollHeight;
        return wrap;
    }

    aiForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const message = aiInput.value.trim();
        if (!message || aiBusy) return;

        aiBusy = true;
        aiSend.disabled = true;
        aiInput.value = '';
        appendAiMessage(message, 'user');

        const typing = appendAiMessage('Escribiendo…', 'bot');
        typing.querySelector('div').classList.add('opacity-60', 'italic');

        try {
            const { data } = await api.post('/api/inventory/ai/chat', { message });
            typing.remove();
            appendAiMessage(data.reply || 'Sin respuesta.', 'bot');
        } catch (err) {
            typing.remove();
            const msg = err.response?.data?.message || 'El asistente no está disponible en este momento.';
            appendAiMessage(msg, 'bot');
        } finally {
            aiBusy = false;
            aiSend.disabled = false;
            aiInput.focus();
        }
    });

    // ─── Start ─────────────────────────────────────────────────
    init();
});
