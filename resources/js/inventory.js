import api from './api';

document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('inventory-app');
    if (!app) return;

    // State
    const state = {
        products: [],
        categories: [],
        selectedCategory: null
    };

    // DOM Elements
    const productsTbody = document.getElementById('products-tbody');
    const loadingState = document.getElementById('loading-state');
    const categoriesContainer = document.getElementById('categories-container');
    const addProductBtn = document.getElementById('add-product-btn');
    const addCategoryBtn = document.getElementById('add-category-btn');
    
    const productModal = document.getElementById('product-modal');
    const closeProductModalBtn = document.getElementById('close-product-modal');
    const productForm = document.getElementById('product-form');

    const categoryModal = document.getElementById('category-modal');
    const closeCategoryModalBtn = document.getElementById('close-category-modal');
    const categoryForm = document.getElementById('category-form');

    const movementModal = document.getElementById('movement-modal');
    const closeMovementModalBtn = document.getElementById('close-movement-modal');
    const movementForm = document.getElementById('movement-form');
    
    // Selects
    const categorySelect = document.getElementById('product-category');
    const movementProductSelect = document.getElementById('movement-product');

    // Init
    async function init() {
        showLoading();
        try {
            await Promise.all([fetchCategories(), fetchProducts()]);
            renderCategories();
            renderProducts();
            populateCategorySelects();
            populateProductSelects();
        } catch (error) {
            console.error('Error inicializando inventario', error);
            showToast('Error al cargar datos', 'error');
        } finally {
            hideLoading();
        }
    }

    // Fetches
    async function fetchProducts() {
        const { data } = await api.get('/api/inventory/products');
        state.products = data;
    }

    async function fetchCategories() {
        const { data } = await api.get('/api/inventory/categories');
        state.categories = data;
    }

    // Renders
    function renderCategories() {
        if (!categoriesContainer) return;
        
        const countAll = state.products.length;
        
        let html = `
            <button class="category-btn flex-shrink-0 min-w-[120px] p-4 text-left rounded-2xl border-2 transition-all ${state.selectedCategory === null ? 'border-emerald-500 bg-emerald-50' : 'border-slate-100 bg-white hover:border-emerald-200'}" data-id="null">
                <h3 class="font-bold text-slate-800">Todas</h3>
                <p class="text-xs text-slate-500 mt-1">${countAll} productos</p>
            </button>
        `;
        
        html += state.categories.map(c => `
            <button class="category-btn flex-shrink-0 min-w-[120px] p-4 text-left rounded-2xl border-2 transition-all ${state.selectedCategory == c.id ? 'border-indigo-500 bg-indigo-50' : 'border-slate-100 bg-white hover:border-indigo-200'}" data-id="${c.id}">
                <h3 class="font-bold text-slate-800">${c.name}</h3>
                <p class="text-xs text-slate-500 mt-1">${c.products_count || 0} productos</p>
            </button>
        `).join('');
        
        categoriesContainer.innerHTML = html;

        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = e.currentTarget.dataset.id;
                state.selectedCategory = id === 'null' ? null : parseInt(id);
                renderCategories();
                renderProducts();
            });
        });
    }

    function renderProducts() {
        let displayedProducts = state.products;
        if (state.selectedCategory !== null) {
            displayedProducts = displayedProducts.filter(p => p.category_id === state.selectedCategory);
        }

        if (displayedProducts.length === 0) {
            productsTbody.innerHTML = `<tr><td colspan="7" class="px-6 py-8 text-center text-slate-500">No hay productos registrados.</td></tr>`;
            return;
        }

        productsTbody.innerHTML = displayedProducts.map(product => {
            const statusColor = product.stock <= product.min_stock ? 'bg-red-100 text-red-700 border-red-200' : 'bg-emerald-100 text-emerald-700 border-emerald-200';
            const statusText = product.stock <= product.min_stock ? 'Stock Bajo' : 'Normal';
            
            return `
            <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">${product.sku}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">${product.name}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">${product.category?.name || '-'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-900">$${Number(product.price).toFixed(2)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 font-bold">${product.stock}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full border ${statusColor}">
                        ${statusText}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button class="text-emerald-600 hover:text-emerald-900 mx-1 btn-move" data-id="${product.id}">Mover Stock</button>
                    <button class="text-rose-600 hover:text-rose-900 mx-1 btn-delete" data-id="${product.id}">Elminar</button>
                </td>
            </tr>
        `}).join('');

        // Attach events
        document.querySelectorAll('.btn-move').forEach(btn => {
            btn.addEventListener('click', (e) => openMovementModal(e.target.dataset.id));
        });
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', (e) => deleteProduct(e.target.dataset.id));
        });
    }

    function populateCategorySelects() {
        categorySelect.innerHTML = '<option value="">Sin categoría</option>' + 
            state.categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
    }

    function populateProductSelects() {
        movementProductSelect.innerHTML = state.products.map(p => `<option value="${p.id}">${p.name} (${p.sku})</option>`).join('');
    }

    // Actions
    async function deleteProduct(id) {
        if (!confirm('¿Seguro que deseas eliminar este producto?')) return;
        try {
            await api.delete(`/api/inventory/products/${id}`);
            state.products = state.products.filter(p => p.id != id);
            renderProducts();
            populateProductSelects();
            showToast('Producto eliminado');
        } catch (error) {
            showToast('Error al eliminar', 'error');
        }
    }

    // Modals
    addCategoryBtn.addEventListener('click', () => {
        categoryForm.reset();
        categoryModal.classList.remove('hidden');
    });

    closeCategoryModalBtn.addEventListener('click', () => {
        categoryModal.classList.add('hidden');
    });

    addProductBtn.addEventListener('click', () => {
        productForm.reset();
        productModal.classList.remove('hidden');
    });

    closeProductModalBtn.addEventListener('click', () => {
        productModal.classList.add('hidden');
    });

    function openMovementModal(productId) {
        movementForm.reset();
        movementProductSelect.value = productId;
        movementModal.classList.remove('hidden');
    }

    closeMovementModalBtn.addEventListener('click', () => {
        movementModal.classList.add('hidden');
    });

    // Form Submits
    categoryForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(categoryForm);
        const data = Object.fromEntries(formData.entries());
        
        try {
            const res = await api.post('/api/inventory/categories', data);
            state.categories.push(res.data);
            renderCategories();
            populateCategorySelects();
            categoryModal.classList.add('hidden');
            showToast('Categoría guardada con éxito');
        } catch(error) {
            alert(error.response?.data?.message || 'Error guardando categoría');
        }
    });

    productForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(productForm);
        const data = Object.fromEntries(formData.entries());
        // Handle empty category
        if(!data.category_id) delete data.category_id;
        
        try {
            const res = await api.post('/api/inventory/products', data);
            state.products.unshift(res.data);
            
            // Recalculate category count for the product's category if it exists
            if (data.category_id) {
                const cat = state.categories.find(c => c.id == data.category_id);
                if (cat) cat.products_count = (cat.products_count || 0) + 1;
            }
            
            renderCategories();
            renderProducts();
            populateProductSelects();
            productModal.classList.add('hidden');
            showToast('Producto guardado con éxito');
        } catch(error) {
            alert(error.response?.data?.message || 'Error guardando producto');
        }
    });

    movementForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(movementForm);
        const data = Object.fromEntries(formData.entries());
        
        try {
            const res = await api.post('/api/inventory/movements', data);
            // Update local stock
            const product = state.products.find(p => p.id == res.data.product_id);
            if(product) {
                if(data.type === 'in') product.stock += parseInt(data.quantity);
                if(data.type === 'out') product.stock -= parseInt(data.quantity);
            }
            renderProducts();
            movementModal.classList.add('hidden');
            showToast('Movimiento registrado');
        } catch(error) {
            alert(error.response?.data?.error || error.response?.data?.message || 'Error al registrar movimiento');
        }
    });

    // Helpers
    function showLoading() {
        productsTbody.classList.add('hidden');
        loadingState.classList.remove('hidden');
    }
    function hideLoading() {
        productsTbody.classList.remove('hidden');
        loadingState.classList.add('hidden');
    }

    function showToast(msg, type='success') {
        // Simple alert for now, could be improved with sweetalert or custom toast
        alert(msg);
    }

    // Start
    init();
});
