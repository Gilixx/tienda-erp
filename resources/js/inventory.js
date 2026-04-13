import api from './api';

document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('inventory-app');
    if (!app) return;

    // ─── State ────────────────────────────────────────────────
    const state = {
        products: [],
        categories: [],
        movements: [],
        selectedCategory: null,
        searchQuery: '',
        currentTab: 'products',
        editingProductId: null,
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
                <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 border border-slate-100">
                    <p class="text-slate-700 font-medium mb-6 text-center text-sm leading-relaxed">${esc(message)}</p>
                    <div class="flex gap-3 justify-center">
                        <button id="confirm-cancel" class="px-5 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-medium text-sm transition-colors">Cancelar</button>
                        <button id="confirm-ok" class="px-5 py-2 rounded-xl bg-rose-600 text-white hover:bg-rose-700 font-medium text-sm transition-colors">Eliminar</button>
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
            await Promise.all([fetchCategories(), fetchProducts(), fetchMovements()]);
            renderStats();
            renderCategories();
            renderProducts();
            renderMovements();
            populateCategorySelects();
            populateProductSelects();
        } catch (error) {
            console.error('Error inicializando inventario', error);
            showToast('Error al cargar los datos del inventario', 'error');
        } finally {
            hideLoading();
        }
    }

    // ─── Fetches ───────────────────────────────────────────────
    async function fetchProducts() {
        const { data } = await api.get('/api/inventory/products');
        state.products = data;
    }

    async function fetchCategories() {
        const { data } = await api.get('/api/inventory/categories');
        state.categories = data;
    }

    async function fetchMovements() {
        const { data } = await api.get('/api/inventory/movements');
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
                    <svg class="w-10 h-10 mx-auto mb-3 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    <p class="text-slate-400 text-sm">${state.searchQuery ? 'Sin resultados para tu búsqueda.' : 'No hay productos registrados.'}</p>
                </td></tr>`;
            return;
        }

        productsTbody.innerHTML = list.map(p => {
            const isLow     = p.stock <= p.min_stock;
            const badgeCls  = isLow ? 'bg-red-100 text-red-700 border-red-200' : 'bg-emerald-100 text-emerald-700 border-emerald-200';
            const badgeTxt  = isLow ? 'Stock Bajo' : 'Normal';
            const stockCls  = isLow ? 'text-red-600 font-bold' : 'text-slate-700 font-semibold';

            return `
            <tr class="border-b border-slate-100 hover:bg-slate-50/60 transition-colors">
                <td class="px-5 py-3 whitespace-nowrap text-xs font-mono text-slate-500">${esc(p.sku)}</td>
                <td class="px-5 py-3 whitespace-nowrap text-sm font-medium text-slate-900">${esc(p.name)}</td>
                <td class="px-5 py-3 whitespace-nowrap text-sm text-slate-500">${esc(p.category?.name || '—')}</td>
                <td class="px-5 py-3 whitespace-nowrap text-sm font-semibold text-slate-800">$${fmt(p.price)}</td>
                <td class="px-5 py-3 whitespace-nowrap text-sm text-slate-500">$${fmt(p.cost)}</td>
                <td class="px-5 py-3 whitespace-nowrap text-sm ${stockCls}">${p.stock}</td>
                <td class="px-5 py-3 whitespace-nowrap">
                    <span class="px-2.5 py-1 inline-flex text-xs font-semibold rounded-full border ${badgeCls}">${badgeTxt}</span>
                </td>
                <td class="px-5 py-3 whitespace-nowrap text-right">
                    <button class="btn-edit p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors" data-id="${p.id}" title="Editar producto">
                        <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button class="btn-move p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors" data-id="${p.id}" title="Mover stock">
                        <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                    </button>
                    <button class="btn-delete p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" data-id="${p.id}" title="Eliminar producto">
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
            movementsTbody.innerHTML = `<tr><td colspan="7" class="px-6 py-10 text-center text-slate-400 text-sm">No hay movimientos registrados.</td></tr>`;
            return;
        }

        const typeMap = {
            in:         { label: 'Entrada',  cls: 'bg-emerald-100 text-emerald-700 border-emerald-200' },
            out:        { label: 'Salida',   cls: 'bg-rose-100 text-rose-700 border-rose-200' },
            adjustment: { label: 'Ajuste',   cls: 'bg-amber-100 text-amber-700 border-amber-200' },
        };

        movementsTbody.innerHTML = state.movements.map(m => {
            const t       = typeMap[m.type] || { label: m.type, cls: '' };
            const qty     = m.quantity > 0 ? `+${m.quantity}` : m.quantity;
            const qtyCls  = m.quantity >= 0 ? 'text-emerald-600 font-bold' : 'text-rose-600 font-bold';
            const nota    = m.notes || m.reference || '—';

            return `
            <tr class="border-b border-slate-100 hover:bg-slate-50/60 transition-colors">
                <td class="px-5 py-3 whitespace-nowrap text-xs text-slate-400">${formatDate(m.created_at)}</td>
                <td class="px-5 py-3 whitespace-nowrap text-sm font-medium text-slate-800">${esc(m.product?.name || '—')}</td>
                <td class="px-5 py-3 whitespace-nowrap text-xs font-mono text-slate-500">${esc(m.product?.sku || '—')}</td>
                <td class="px-5 py-3 whitespace-nowrap">
                    <span class="px-2.5 py-1 inline-flex text-xs font-semibold rounded-full border ${t.cls}">${t.label}</span>
                </td>
                <td class="px-5 py-3 whitespace-nowrap text-sm ${qtyCls}">${qty}</td>
                <td class="px-5 py-3 text-sm text-slate-500 max-w-[200px] truncate">${esc(nota)}</td>
                <td class="px-5 py-3 whitespace-nowrap text-sm text-slate-500">${esc(m.user?.name || '—')}</td>
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
        syncMovementStockDisplay();
        syncMovementTypeUI();
        showModal(movementModal);
    }

    [closeMovementModal, cancelMovementModal].forEach(el => el?.addEventListener('click', () => hideModal(movementModal)));

    function syncMovementStockDisplay() {
        if (!movementProductSelect || !movementCurrentStock) return;
        const product = state.products.find(p => p.id == movementProductSelect.value);
        movementCurrentStock.textContent = product != null ? product.stock : '—';
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

    movementForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(movementForm));
        const submitBtn = movementForm.querySelector('[type="submit"]');
        submitBtn.disabled = true;

        try {
            const res = await api.post('/api/inventory/movements', data);
            // Sync local stock from server response
            const updatedProduct = res.data.product;
            if (updatedProduct) {
                const idx = state.products.findIndex(p => p.id == updatedProduct.id);
                if (idx !== -1) state.products[idx] = { ...state.products[idx], stock: updatedProduct.stock };
            }
            state.movements.unshift(res.data);
            renderStats();
            renderProducts();
            renderMovements();
            hideModal(movementModal);
            showToast('Movimiento registrado correctamente');
        } catch (err) {
            const msg = err.response?.data?.error || err.response?.data?.message || 'Error al registrar el movimiento';
            showToast(msg, 'error');
        } finally {
            submitBtn.disabled = false;
        }
    });

    // ─── Tabs ──────────────────────────────────────────────────
    function switchTab(tab) {
        state.currentTab = tab;
        const isProducts = tab === 'products';
        productsSection.classList.toggle('hidden', !isProducts);
        movementsSection.classList.toggle('hidden', isProducts);

        tabProductsBtn.classList.toggle('border-emerald-500', isProducts);
        tabProductsBtn.classList.toggle('text-emerald-600', isProducts);
        tabProductsBtn.classList.toggle('border-transparent', !isProducts);
        tabProductsBtn.classList.toggle('text-slate-500', !isProducts);

        tabMovementsBtn.classList.toggle('border-emerald-500', !isProducts);
        tabMovementsBtn.classList.toggle('text-emerald-600', !isProducts);
        tabMovementsBtn.classList.toggle('border-transparent', isProducts);
        tabMovementsBtn.classList.toggle('text-slate-500', isProducts);
    }

    tabProductsBtn?.addEventListener('click', () => switchTab('products'));
    tabMovementsBtn?.addEventListener('click', () => switchTab('movements'));

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

    // ─── Start ─────────────────────────────────────────────────
    init();
});
