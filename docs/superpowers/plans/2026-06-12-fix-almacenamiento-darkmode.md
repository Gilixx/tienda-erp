# Fix Sistema de Almacenamiento + Modo Oscuro — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Reparar bugs visuales del modo oscuro y completar piezas faltantes del módulo de almacenamiento (almacenes, transferencias, inventario físico, alertas) en el ERP CRM-AC.

**Architecture:** Frontend vanilla-JS en `resources/js/inventory.js` que renderiza tablas y modales dentro de la Blade `resources/views/modules/inventory.blade.php`. No hay framework SPA. El modo oscuro se basa en clases `dark:` de Tailwind aplicadas vía el toggle `localStorage('theme')`. Los endpoints API en `routes/api.php` bajo `/api/inventory/*` ya existen — el plan agrega UI faltante y dark-mode classes a fragmentos JS-rendered.

**Tech Stack:** Laravel 12, Blade, Tailwind CSS 4 (dark mode via `class`), Vite, vanilla JS, Axios via `api.js`. MySQL `CRMTEST`.

---

## Resumen de problemas detectados

### Modo oscuro (clases dark faltantes en fragmentos JS-rendered)
1. `showConfirm` modal — fondo blanco fijo, texto invisible en oscuro
2. `renderProducts` — filas, hover, badges Stock Bajo/Normal, texto SKU/precio/costo
3. `renderMovements` — filas, badges tipo (Entrada/Salida/Ajuste), texto
4. `renderAlmacenesGrid` — badge "Activo/Inactivo"
5. `fetchAndRenderTransferencias` — badges de estado + filas hover
6. `fetchAndRenderInvFisico` — badges de estado + filas hover
7. `fetchAndRenderAlertas` — badge tipo + texto
8. `importResult` (estados de resultado) — fondos sin dark
9. Empty states (SVGs/textos vacíos en tablas)
10. Tab "Alertas" pierde su color rose al activarse (queda emerald)

### Piezas funcionales faltantes
11. Botón **"Nueva transferencia"** sin handler — no se puede crear
12. **"Nuevo almacén"** usa `prompt()` nativo — sin descripción/dirección
13. Sin editar/eliminar almacén desde UI
14. Sin gestión de ubicaciones de almacén desde UI
15. Sin vista detalle de transferencia / recibir con cantidades parciales
16. Sin vista detalle de inventario físico / conteo manual de items
17. **"Nueva sesión inv físico"** usa `prompt()` nativo
18. Fecha de transferencia se muestra como ISO sin formatear
19. `openImportModal` modifica `importResult.textContent` sin guard de null

---

## File Structure

**Modificar:**
- `resources/js/inventory.js` — agregar handlers, modales nuevos, helpers dark mode, fix bugs
- `resources/views/modules/inventory.blade.php` — agregar markup de nuevos modales (almacén, transferencia, conteo físico)

**No se crean archivos nuevos.** Todo se mantiene en el módulo existente para preservar el patrón "un módulo = una Blade + un JS".

---

## Task 1: Helper dark-aware en inventory.js + Fix showConfirm

**Files:**
- Modify: `resources/js/inventory.js:115-132` (función `showConfirm`)

**Why:** El modal de confirmación se renderiza vía JS con clases fijas que no respetan dark mode (fondo blanco, texto slate-700). Lo reescribimos con variantes `dark:`.

- [ ] **Step 1: Reemplazar función `showConfirm`**

Buscar la función actual (líneas ~115-132) y reemplazarla por:

```javascript
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
```

Cambios clave: agrega `dark:bg-zinc-900`, `dark:text-zinc-200`, `dark:border-zinc-800` y todas las variantes para botones. Cambia botón "Eliminar" → "Confirmar" (más reutilizable). Añade `whitespace-pre-line` para que `\n` en mensajes funcione.

- [ ] **Step 2: Verificar visualmente**

Abrir `/inventory`, alternar dark mode, intentar eliminar un producto. El modal debe verse legible en ambos temas.

- [ ] **Step 3: Commit**

```bash
git add resources/js/inventory.js
git commit -m "fix(inventory): dark mode en modal showConfirm"
```

---

## Task 2: Dark mode en renderProducts

**Files:**
- Modify: `resources/js/inventory.js:262-303` (función `renderProducts`)

**Why:** Las filas de productos usan `text-slate-900`, `text-slate-500`, `border-slate-100`, `hover:bg-slate-50/60` y badges `bg-red-100 text-red-700` / `bg-emerald-100 text-emerald-700` sin variantes dark.

- [ ] **Step 1: Actualizar empty state y badges de stock**

Reemplazar la sección desde `if (list.length === 0)` hasta el cierre del `productsTbody.innerHTML = list.map(...)`:

```javascript
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
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/inventory.js
git commit -m "fix(inventory): dark mode en renderProducts (filas y badges)"
```

---

## Task 3: Dark mode en renderMovements

**Files:**
- Modify: `resources/js/inventory.js:317-350` (función `renderMovements`)

- [ ] **Step 1: Reemplazar la función completa**

```javascript
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
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/inventory.js
git commit -m "fix(inventory): dark mode en renderMovements"
```

---

## Task 4: Dark mode en renderAlmacenesGrid + acciones editar/eliminar

**Files:**
- Modify: `resources/js/inventory.js:555-581` (función `renderAlmacenesGrid`)
- Modify: `resources/js/inventory.js:583-596` (reemplazar handler `btn-nuevo-almacen`)

**Why:** Las tarjetas de almacén son legibles en dark pero el badge "Activo/Inactivo" no tiene dark. Además, no hay botones para editar/eliminar/ver ubicaciones.

- [ ] **Step 1: Reemplazar `renderAlmacenesGrid`**

```javascript
    function renderAlmacenesGrid() {
        const grid = document.getElementById('almacenes-grid');
        if (!grid) return;

        if (!almacenes.length) {
            grid.innerHTML = '<p class="text-slate-400 dark:text-zinc-500 text-sm col-span-3">No hay almacenes registrados.</p>';
            return;
        }

        grid.innerHTML = almacenes.map(a => `
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
                <div class="flex gap-2 mt-3">
                    <button class="btn-editar-almacen flex-1 text-xs px-3 py-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 font-medium transition-colors" data-id="${a.id}">
                        Editar
                    </button>
                    ${!a.es_principal ? `<button class="btn-eliminar-almacen flex-1 text-xs px-3 py-1.5 rounded-lg bg-rose-50 dark:bg-rose-900/20 text-rose-700 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900/40 font-medium transition-colors" data-id="${a.id}" data-nombre="${esc(a.nombre)}">
                        Eliminar
                    </button>` : ''}
                </div>
            </div>`).join('');

        grid.querySelectorAll('.btn-editar-almacen').forEach(btn =>
            btn.addEventListener('click', () => openAlmacenModal(parseInt(btn.dataset.id)))
        );
        grid.querySelectorAll('.btn-eliminar-almacen').forEach(btn =>
            btn.addEventListener('click', () => eliminarAlmacen(parseInt(btn.dataset.id), btn.dataset.nombre))
        );
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
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/inventory.js
git commit -m "feat(inventory): tarjetas de almacén con dark mode + acciones editar/eliminar"
```

---

## Task 5: Modal de crear/editar almacén (reemplazo de prompt)

**Files:**
- Modify: `resources/views/modules/inventory.blade.php` (agregar markup del modal antes del cierre `</div>` de `#inventory-app`)
- Modify: `resources/js/inventory.js:583-596` (reemplazar handler `btn-nuevo-almacen`)

**Why:** El handler actual usa `prompt()` nativo dos veces. UX horrible, no funciona en móviles bien, no permite editar descripción/dirección.

- [ ] **Step 1: Agregar markup del modal en la Blade**

En `resources/views/modules/inventory.blade.php`, **antes del cierre `</div>` que cierra `#inventory-app`** (línea ~474), agregar:

```blade
    <!-- MODAL: ALMACÉN (crear/editar) -->
    <div id="almacen-modal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 flex">
        <div class="absolute inset-0 bg-slate-900/50 dark:bg-black/60 backdrop-blur-sm"></div>
        <div class="relative bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-100 dark:border-zinc-800">
            <div class="p-6 sm:p-8">
                <div class="flex justify-between items-center mb-5">
                    <h3 id="almacen-modal-title" class="text-xl font-bold text-slate-800 dark:text-zinc-50">Nuevo Almacén</h3>
                    <button type="button" id="close-almacen-modal" class="text-slate-400 dark:text-zinc-500 hover:text-slate-600 dark:hover:text-zinc-300 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                @php $inpA = 'w-full rounded-xl border border-slate-200 dark:border-zinc-700 bg-slate-50 dark:bg-zinc-800 text-slate-800 dark:text-zinc-100 focus:bg-white dark:focus:bg-zinc-700 focus:border-emerald-500 focus:ring-emerald-500 transition-colors text-sm placeholder-slate-400 dark:placeholder-zinc-500'; @endphp
                <form id="almacen-form" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1">Nombre <span class="text-rose-500">*</span></label>
                            <input type="text" name="nombre" required maxlength="100" class="{{ $inpA }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1">Código <span class="text-rose-500">*</span></label>
                            <input type="text" name="codigo" required maxlength="20" placeholder="Ej: BODEGA-A" class="{{ $inpA }}">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1">Descripción</label>
                            <textarea name="descripcion" rows="2" maxlength="500" class="{{ $inpA }}"></textarea>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1">Dirección</label>
                            <input type="text" name="direccion" maxlength="500" class="{{ $inpA }}">
                        </div>
                        <div class="col-span-2 flex items-center gap-2">
                            <input type="checkbox" name="activo" id="almacen-activo" checked class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500">
                            <label for="almacen-activo" class="text-sm text-slate-700 dark:text-zinc-300">Almacén activo</label>
                        </div>
                    </div>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" id="cancel-almacen-modal"
                            class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-400 hover:bg-slate-50 dark:hover:bg-zinc-800 font-medium text-sm transition-colors">Cancelar</button>
                        <button type="submit"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition-all">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
```

- [ ] **Step 2: Reemplazar handler de `btn-nuevo-almacen` en `inventory.js`**

Buscar el bloque actual:
```javascript
    document.getElementById('btn-nuevo-almacen')?.addEventListener('click', async () => {
        const nombre = prompt('Nombre del almacén:');
        ...
    });
```

Reemplazarlo con:

```javascript
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
            const a = almacenes.find(x => x.id === id);
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
```

- [ ] **Step 3: Commit**

```bash
git add resources/views/modules/inventory.blade.php resources/js/inventory.js
git commit -m "feat(inventory): modal de crear/editar almacén (reemplaza prompt nativo)"
```

---

## Task 6: Dark mode en fetchAndRenderTransferencias + fix fecha

**Files:**
- Modify: `resources/js/inventory.js:599-633` (función `fetchAndRenderTransferencias`)

**Why:** Badges sin dark, hover sin dark, y la fecha se imprime como string ISO crudo (`2026-06-12`) sin formatear.

- [ ] **Step 1: Reemplazar la función**

```javascript
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
```

Y **eliminar** los handlers globales `window._enviarTransferencia` y `window._recibirTransferencia` (líneas ~635-659) — ya no son necesarios.

- [ ] **Step 2: Commit**

```bash
git add resources/js/inventory.js
git commit -m "fix(inventory): dark mode + formato fecha + handlers internos en transferencias"
```

---

## Task 7: Modal de creación de transferencia

**Files:**
- Modify: `resources/views/modules/inventory.blade.php` (agregar markup del modal)
- Modify: `resources/js/inventory.js` (agregar handler `btn-nueva-transferencia` y submit)

**Why:** El botón "Nueva transferencia" existe en HTML pero **no tiene handler**. Sin esto, el usuario no puede crear transferencias desde la UI.

- [ ] **Step 1: Agregar markup del modal en la Blade**

Antes del cierre `</div>` de `#inventory-app`, agregar:

```blade
    <!-- MODAL: TRANSFERENCIA (crear) -->
    <div id="transferencia-modal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 flex">
        <div class="absolute inset-0 bg-slate-900/50 dark:bg-black/60 backdrop-blur-sm"></div>
        <div class="relative bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden border border-slate-100 dark:border-zinc-800 flex flex-col">
            <div class="p-6 sm:p-8 overflow-y-auto">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xl font-bold text-slate-800 dark:text-zinc-50">Nueva Transferencia</h3>
                    <button type="button" id="close-transferencia-modal" class="text-slate-400 dark:text-zinc-500 hover:text-slate-600 dark:hover:text-zinc-300 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                @php $inpT = 'w-full rounded-xl border border-slate-200 dark:border-zinc-700 bg-slate-50 dark:bg-zinc-800 text-slate-800 dark:text-zinc-100 focus:bg-white dark:focus:bg-zinc-700 focus:border-emerald-500 focus:ring-emerald-500 transition-colors text-sm'; @endphp
                <form id="transferencia-form" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1">Almacén origen <span class="text-rose-500">*</span></label>
                            <select name="almacen_origen_id" id="transf-origen" required class="{{ $inpT }}"></select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1">Almacén destino <span class="text-rose-500">*</span></label>
                            <select name="almacen_destino_id" id="transf-destino" required class="{{ $inpT }}"></select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1">Fecha <span class="text-rose-500">*</span></label>
                            <input type="date" name="fecha" id="transf-fecha" required class="{{ $inpT }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1">Referencia</label>
                            <input type="text" name="referencia" maxlength="100" placeholder="Opcional" class="{{ $inpT }}">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-2">Productos a transferir</label>
                        <div id="transf-items" class="space-y-2 border border-slate-200 dark:border-zinc-700 rounded-xl p-3 max-h-60 overflow-y-auto bg-slate-50/50 dark:bg-zinc-800/50">
                            <!-- JS rendered -->
                        </div>
                        <button type="button" id="transf-add-item" class="mt-2 text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300">+ Agregar producto</button>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1">Notas</label>
                        <textarea name="notas" rows="2" maxlength="1000" class="{{ $inpT }}"></textarea>
                    </div>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" id="cancel-transferencia-modal"
                            class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-400 hover:bg-slate-50 dark:hover:bg-zinc-800 font-medium text-sm transition-colors">Cancelar</button>
                        <button type="submit"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition-all">Crear transferencia</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
```

- [ ] **Step 2: Agregar lógica en `inventory.js`**

Dentro del `DOMContentLoaded`, **antes de la línea `// ─── Inventario Físico ─`**, agregar:

```javascript
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

    function renderTransfItemRow() {
        const row = document.createElement('div');
        row.className = 'flex gap-2 items-center transf-item-row';
        row.innerHTML = `
            <select class="transf-item-product flex-1 rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-800 dark:text-zinc-100 text-sm focus:border-emerald-500 focus:ring-emerald-500 py-2 px-2">
                ${state.products.map(p => `<option value="${p.id}">${esc(p.name)} (${esc(p.sku)}) — stock ${p.stock}</option>`).join('')}
            </select>
            <input type="number" min="1" class="transf-item-qty w-24 rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-800 dark:text-zinc-100 text-sm focus:border-emerald-500 focus:ring-emerald-500 py-2 px-2" placeholder="Cant." required value="1">
            <button type="button" class="transf-item-remove text-rose-500 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300 p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>`;
        row.querySelector('.transf-item-remove').addEventListener('click', () => row.remove());
        transfItemsContainer.appendChild(row);
    }

    function openTransferenciaModal() {
        transferenciaForm.reset();
        // Llenar selects de almacenes (filtra solo activos)
        const opts = almacenes.filter(a => a.activo).map(a => `<option value="${a.id}">${esc(a.nombre)} (${esc(a.codigo)})</option>`).join('');
        transfOrigen.innerHTML  = opts;
        transfDestino.innerHTML = opts;
        if (almacenes.length >= 2) transfDestino.value = almacenes[1].id;
        transfFecha.value = new Date().toISOString().slice(0, 10);
        transfItemsContainer.innerHTML = '';
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
```

- [ ] **Step 3: Commit**

```bash
git add resources/views/modules/inventory.blade.php resources/js/inventory.js
git commit -m "feat(inventory): modal de creación de transferencias"
```

---

## Task 8: Dark mode y conteo manual en inventario físico

**Files:**
- Modify: `resources/views/modules/inventory.blade.php` (agregar modal de sesión + modal de detalle)
- Modify: `resources/js/inventory.js:662-715` (mejorar `fetchAndRenderInvFisico` y handler de nueva sesión)

**Why:** El handler de "Nueva sesión" usa `prompt()`. Y aplicar sin conteos manuales no ajusta nada (todo queda igual al teórico). Necesitamos modal de selección y vista de detalle para registrar conteos por item.

- [ ] **Step 1: Agregar markup de modal de nueva sesión + modal de detalle/conteo**

Antes del cierre `</div>` de `#inventory-app`, agregar:

```blade
    <!-- MODAL: NUEVA SESIÓN INVENTARIO FÍSICO -->
    <div id="invfisico-modal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 flex">
        <div class="absolute inset-0 bg-slate-900/50 dark:bg-black/60 backdrop-blur-sm"></div>
        <div class="relative bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100 dark:border-zinc-800">
            <div class="p-6 sm:p-8">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xl font-bold text-slate-800 dark:text-zinc-50">Nueva Sesión de Inventario</h3>
                    <button type="button" id="close-invfisico-modal" class="text-slate-400 dark:text-zinc-500 hover:text-slate-600 dark:hover:text-zinc-300 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                @php $inpI = 'w-full rounded-xl border border-slate-200 dark:border-zinc-700 bg-slate-50 dark:bg-zinc-800 text-slate-800 dark:text-zinc-100 focus:bg-white dark:focus:bg-zinc-700 focus:border-emerald-500 focus:ring-emerald-500 transition-colors text-sm'; @endphp
                <form id="invfisico-form" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1">Almacén <span class="text-rose-500">*</span></label>
                        <select name="almacen_id" id="invfisico-almacen" required class="{{ $inpI }}"></select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-zinc-300 mb-1">Notas</label>
                        <textarea name="notas" rows="2" maxlength="1000" class="{{ $inpI }}"></textarea>
                    </div>
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800/40 rounded-xl p-3 text-xs text-amber-800 dark:text-amber-300">
                        Al abrir la sesión se tomará un snapshot del stock actual. Luego registrarás el conteo físico de cada producto.
                    </div>
                    <div class="pt-2 flex justify-end gap-3">
                        <button type="button" id="cancel-invfisico-modal"
                            class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-400 hover:bg-slate-50 dark:hover:bg-zinc-800 font-medium text-sm transition-colors">Cancelar</button>
                        <button type="submit"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition-all">Abrir sesión</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: DETALLE INVENTARIO FÍSICO (conteo) -->
    <div id="invfisico-detalle-modal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 flex">
        <div class="absolute inset-0 bg-slate-900/50 dark:bg-black/60 backdrop-blur-sm"></div>
        <div class="relative bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden border border-slate-100 dark:border-zinc-800 flex flex-col">
            <div class="p-6 sm:p-8 overflow-y-auto">
                <div class="flex justify-between items-center mb-5">
                    <div>
                        <h3 class="text-xl font-bold text-slate-800 dark:text-zinc-50">Conteo Físico</h3>
                        <p id="invfisico-detalle-info" class="text-xs text-slate-500 dark:text-zinc-400 mt-1"></p>
                    </div>
                    <button type="button" id="close-invfisico-detalle" class="text-slate-400 dark:text-zinc-500 hover:text-slate-600 dark:hover:text-zinc-300 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="overflow-x-auto border border-slate-200 dark:border-zinc-800 rounded-xl">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 dark:bg-zinc-800/60">
                            <tr class="text-[11px] font-semibold text-slate-400 dark:text-zinc-500 uppercase tracking-widest">
                                <th class="px-4 py-2 text-left">SKU</th>
                                <th class="px-4 py-2 text-left">Producto</th>
                                <th class="px-4 py-2 text-center">Teórico</th>
                                <th class="px-4 py-2 text-center">Contado</th>
                                <th class="px-4 py-2 text-center">Diferencia</th>
                            </tr>
                        </thead>
                        <tbody id="invfisico-detalle-tbody" class="divide-y divide-slate-100 dark:divide-zinc-800"></tbody>
                    </table>
                </div>
                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" id="cancel-invfisico-detalle"
                        class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-400 hover:bg-slate-50 dark:hover:bg-zinc-800 font-medium text-sm transition-colors">Cerrar</button>
                    <button type="button" id="btn-aplicar-invfisico"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition-all">Aplicar ajustes</button>
                </div>
            </div>
        </div>
    </div>
```

- [ ] **Step 2: Reescribir lógica de inventario físico en `inventory.js`**

Reemplazar el bloque desde `// ─── Inventario Físico ─` hasta antes de `// ─── Alertas de Stock ─` con:

```javascript
    // ─── Inventario Físico ─────────────────────────────────────
    async function fetchAndRenderInvFisico() {
        try {
            const { data } = await api.get('/api/inventory/inventario-fisico');
            const tbody = document.getElementById('tbody-inv-fisico');
            if (!tbody) return;
            const items = data.data ?? data;
            if (!items.length) {
                tbody.innerHTML = '<tr><td colspan="7" class="px-5 py-6 text-center text-slate-400 dark:text-zinc-500">No hay sesiones de inventario.</td></tr>';
                return;
            }
            const estadoMap = {
                abierto:  { label: 'Abierto',  cls: 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400' },
                cerrado:  { label: 'Cerrado',  cls: 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400' },
                aplicado: { label: 'Aplicado', cls: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' },
            };
            tbody.innerHTML = items.map(s => {
                const est = estadoMap[s.estado] ?? { label: s.estado, cls: 'bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-300' };
                return `<tr class="border-b border-slate-100 dark:border-zinc-800 hover:bg-slate-50 dark:hover:bg-zinc-800/40 transition-colors">
                    <td class="px-5 py-3.5 text-sm font-medium text-slate-800 dark:text-zinc-100">${esc(s.almacen?.nombre ?? '—')}</td>
                    <td class="px-5 py-3.5 text-sm text-slate-500 dark:text-zinc-400">${formatDate(s.fecha_apertura)}</td>
                    <td class="px-5 py-3.5 text-sm text-slate-500 dark:text-zinc-400">${s.fecha_cierre ? formatDate(s.fecha_cierre) : '—'}</td>
                    <td class="px-5 py-3.5 text-right font-mono text-sm text-slate-700 dark:text-zinc-200">${s.diferencia_total_valor != null ? '$' + fmt(s.diferencia_total_valor) : '—'}</td>
                    <td class="px-5 py-3.5"><span class="text-xs font-semibold px-2 py-0.5 rounded-full ${est.cls}">${est.label}</span></td>
                    <td class="px-5 py-3.5 text-sm text-slate-500 dark:text-zinc-400">${esc(s.user?.name ?? '—')}</td>
                    <td class="px-5 py-3.5 text-right whitespace-nowrap">
                        ${s.estado === 'abierto' ? `<button data-action="contar" data-id="${s.id}" class="btn-invfisico-action text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded-lg transition-colors">Contar</button>` : ''}
                        ${s.estado !== 'aplicado' ? `<button data-action="eliminar" data-id="${s.id}" class="btn-invfisico-action ml-1 text-xs bg-rose-50 dark:bg-rose-900/20 text-rose-700 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900/40 px-3 py-1 rounded-lg transition-colors">Eliminar</button>` : ''}
                    </td>
                </tr>`;
            }).join('');
            tbody.querySelectorAll('.btn-invfisico-action').forEach(btn => {
                btn.addEventListener('click', () => handleInvFisicoAction(btn.dataset.action, parseInt(btn.dataset.id)));
            });
        } catch (err) { console.error(err); }
    }

    async function handleInvFisicoAction(action, id) {
        if (action === 'contar') {
            openInvFisicoDetalle(id);
        } else if (action === 'eliminar') {
            const ok = await showConfirm('¿Eliminar esta sesión de inventario?');
            if (!ok) return;
            try {
                await api.delete(`/api/inventory/inventario-fisico/${id}`);
                showToast('Sesión eliminada.');
                await fetchAndRenderInvFisico();
            } catch (err) {
                showToast(err.response?.data?.error ?? 'Error al eliminar', 'error');
            }
        }
    }

    // ── Modal de nueva sesión inv físico ──
    const invFisicoModal       = document.getElementById('invfisico-modal');
    const invFisicoForm        = document.getElementById('invfisico-form');
    const invFisicoAlmacen     = document.getElementById('invfisico-almacen');
    const closeInvFisicoBtn    = document.getElementById('close-invfisico-modal');
    const cancelInvFisicoBtn   = document.getElementById('cancel-invfisico-modal');

    document.getElementById('btn-nueva-sesion-inv')?.addEventListener('click', () => {
        if (!almacenes.length) { showToast('No hay almacenes disponibles', 'warning'); return; }
        invFisicoForm.reset();
        invFisicoAlmacen.innerHTML = almacenes.filter(a => a.activo)
            .map(a => `<option value="${a.id}">${esc(a.nombre)} (${esc(a.codigo)})</option>`).join('');
        showModal(invFisicoModal);
    });

    [closeInvFisicoBtn, cancelInvFisicoBtn].forEach(el => el?.addEventListener('click', () => hideModal(invFisicoModal)));
    invFisicoModal?.addEventListener('click', (e) => {
        if (e.target === invFisicoModal || e.target.classList.contains('absolute')) hideModal(invFisicoModal);
    });

    invFisicoForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(invFisicoForm));
        const submitBtn = invFisicoForm.querySelector('[type="submit"]');
        submitBtn.disabled = true;
        try {
            await api.post('/api/inventory/inventario-fisico', data);
            showToast('Sesión abierta. Snapshot tomado.');
            hideModal(invFisicoModal);
            await fetchAndRenderInvFisico();
        } catch (err) {
            showToast(err.response?.data?.error ?? 'Error al abrir sesión', 'error');
        } finally {
            submitBtn.disabled = false;
        }
    });

    // ── Modal de detalle / conteo ──
    const invFisicoDetalleModal  = document.getElementById('invfisico-detalle-modal');
    const invFisicoDetalleTbody  = document.getElementById('invfisico-detalle-tbody');
    const invFisicoDetalleInfo   = document.getElementById('invfisico-detalle-info');
    const closeInvFisicoDetalleBtn  = document.getElementById('close-invfisico-detalle');
    const cancelInvFisicoDetalleBtn = document.getElementById('cancel-invfisico-detalle');
    const btnAplicarInvFisico       = document.getElementById('btn-aplicar-invfisico');
    let invFisicoDetalleId = null;

    async function openInvFisicoDetalle(id) {
        invFisicoDetalleId = id;
        invFisicoDetalleTbody.innerHTML = '<tr><td colspan="5" class="px-4 py-6 text-center text-slate-400 dark:text-zinc-500">Cargando…</td></tr>';
        showModal(invFisicoDetalleModal);
        try {
            const { data } = await api.get(`/api/inventory/inventario-fisico/${id}`);
            invFisicoDetalleInfo.textContent = `${data.almacen?.nombre ?? ''} · Apertura: ${formatDate(data.fecha_apertura)} · ${data.items?.length ?? 0} productos`;
            if (!data.items?.length) {
                invFisicoDetalleTbody.innerHTML = '<tr><td colspan="5" class="px-4 py-6 text-center text-slate-400 dark:text-zinc-500">Sin items.</td></tr>';
                return;
            }
            invFisicoDetalleTbody.innerHTML = data.items.map(it => {
                const contado = it.cantidad_contada;
                const dif = contado != null ? (contado - it.cantidad_teorica) : null;
                const difCls = dif == null ? 'text-slate-400 dark:text-zinc-500' :
                                (dif === 0 ? 'text-slate-600 dark:text-zinc-300' :
                                (dif > 0 ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-rose-600 dark:text-rose-400 font-semibold'));
                const difTxt = dif == null ? '—' : (dif > 0 ? `+${dif}` : dif);
                return `<tr>
                    <td class="px-4 py-2 text-xs font-mono text-slate-500 dark:text-zinc-400">${esc(it.product?.sku ?? '—')}</td>
                    <td class="px-4 py-2 text-sm text-slate-800 dark:text-zinc-100">${esc(it.product?.name ?? '—')}</td>
                    <td class="px-4 py-2 text-center text-sm text-slate-600 dark:text-zinc-300 font-mono">${it.cantidad_teorica}</td>
                    <td class="px-4 py-2 text-center">
                        <input type="number" min="0" value="${contado ?? ''}" data-item-id="${it.id}" class="invfisico-conteo w-20 text-center rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-800 dark:text-zinc-100 text-sm focus:border-emerald-500 focus:ring-emerald-500 py-1 px-2">
                    </td>
                    <td class="px-4 py-2 text-center text-sm ${difCls}">${difTxt}</td>
                </tr>`;
            }).join('');

            // Auto-save on blur
            invFisicoDetalleTbody.querySelectorAll('.invfisico-conteo').forEach(input => {
                input.addEventListener('blur', async () => {
                    const val = input.value === '' ? null : parseInt(input.value);
                    if (val === null) return;
                    if (val < 0 || isNaN(val)) { input.value = ''; return; }
                    try {
                        await api.patch(`/api/inventory/inventario-fisico/${invFisicoDetalleId}/items/${input.dataset.itemId}`, {
                            cantidad_contada: val,
                        });
                        // Refrescar fila para mostrar diferencia
                        const tr = input.closest('tr');
                        const teorico = parseInt(tr.children[2].textContent.trim());
                        const dif = val - teorico;
                        const cell = tr.children[4];
                        cell.className = 'px-4 py-2 text-center text-sm ' +
                            (dif === 0 ? 'text-slate-600 dark:text-zinc-300' :
                            (dif > 0 ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-rose-600 dark:text-rose-400 font-semibold'));
                        cell.textContent = dif > 0 ? `+${dif}` : dif;
                    } catch (err) {
                        showToast('Error al guardar conteo', 'error');
                    }
                });
            });
        } catch (err) {
            invFisicoDetalleTbody.innerHTML = '<tr><td colspan="5" class="px-4 py-6 text-center text-rose-500">Error al cargar.</td></tr>';
        }
    }

    [closeInvFisicoDetalleBtn, cancelInvFisicoDetalleBtn].forEach(el =>
        el?.addEventListener('click', () => hideModal(invFisicoDetalleModal))
    );
    invFisicoDetalleModal?.addEventListener('click', (e) => {
        if (e.target === invFisicoDetalleModal || e.target.classList.contains('absolute')) hideModal(invFisicoDetalleModal);
    });

    btnAplicarInvFisico?.addEventListener('click', async () => {
        if (!invFisicoDetalleId) return;
        const ok = await showConfirm('¿Aplicar ajustes de inventario?\nEsta acción generará movimientos automáticos y no se puede revertir.');
        if (!ok) return;
        try {
            await api.post(`/api/inventory/inventario-fisico/${invFisicoDetalleId}/aplicar`);
            showToast('Inventario aplicado. Stock ajustado.');
            hideModal(invFisicoDetalleModal);
            await Promise.all([fetchAndRenderInvFisico(), fetchProducts()]);
            renderProducts();
        } catch (err) {
            showToast(err.response?.data?.error ?? 'Error al aplicar', 'error');
        }
    });

    // (Quitar el viejo window._aplicarInvFisico — ya no se usa)
```

Y **eliminar** el `window._aplicarInvFisico` y el viejo handler de `btn-nueva-sesion-inv` (línea ~702).

- [ ] **Step 3: Commit**

```bash
git add resources/views/modules/inventory.blade.php resources/js/inventory.js
git commit -m "feat(inventory): conteo manual de inventario físico + dark mode + modal de sesión"
```

---

## Task 9: Dark mode en alertas + fix de tab activo

**Files:**
- Modify: `resources/js/inventory.js:718-749` (función `fetchAndRenderAlertas`)
- Modify: `resources/js/inventory.js:780-803` (función `switchTab`)

**Why:** Alertas no tienen dark mode. Y el tab "Alertas" pierde su color rose al estar activo porque `switchTab` lo pinta de emerald universalmente.

- [ ] **Step 1: Actualizar `fetchAndRenderAlertas`**

```javascript
    async function fetchAndRenderAlertas() {
        try {
            const { data } = await api.get('/api/inventory/alertas');
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
```

Y **eliminar** el `window._resolverAlerta` (línea ~752).

- [ ] **Step 2: Arreglar `switchTab` para que respete el color del tab Alertas**

Reemplazar la función `switchTab`:

```javascript
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
        if (tab === 'inv-fisico') fetchAndRenderInvFisico();
        if (tab === 'alertas') fetchAndRenderAlertas();
    }
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/inventory.js
git commit -m "fix(inventory): dark mode en alertas + tab Alertas conserva color rose al activarse"
```

---

## Task 10: Fix de bugs lógicos menores

**Files:**
- Modify: `resources/js/inventory.js:857-865` (función `openImportModal`)
- Modify: `resources/js/inventory.js:899-902` (clases de estado de `importResult`)

**Why:**
1. `openImportModal` hace `importResult.textContent = ''` sin guard — si `importResult` es null crashea.
2. Las clases CSS para los estados de éxito/error/warning en `importResult` no tienen dark mode.

- [ ] **Step 1: Guards y dark mode en import**

Reemplazar:

```javascript
    function openImportModal() {
        importForm?.reset();
        if (importResult) {
            importResult.classList.add('hidden');
            importResult.textContent = '';
        }
        importModal?.classList.remove('hidden');
    }
```

Y dentro del `importForm.addEventListener('submit', ...)`, cuando se asigna `importResult.className`, reemplazar las clases:

```javascript
            importResult.className = 'text-sm rounded-xl p-3 ' +
                (data.error_count
                    ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-300 border border-amber-100 dark:border-amber-800/40'
                    : 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-300 border border-emerald-100 dark:border-emerald-800/40');
```

Y el catch:

```javascript
            importResult.className = 'text-sm rounded-xl p-3 bg-rose-50 dark:bg-rose-900/20 text-rose-800 dark:text-rose-300 border border-rose-100 dark:border-rose-800/40';
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/inventory.js
git commit -m "fix(inventory): guards en openImportModal + dark mode en resultado de importación"
```

---

## Task 11: Build + verificación visual final

**Files:** (sin cambios, solo verificación)

- [ ] **Step 1: Construir assets**

```bash
npm run build
```

Expected: build success, sin errores de Vite.

- [ ] **Step 2: Levantar dev server (si no está corriendo)**

```bash
php artisan serve
```

- [ ] **Step 3: Checklist visual manual en `/inventory`**

Abrir el módulo Inventario en navegador y comprobar en **light mode** y **dark mode**:

- [ ] Tabla de productos: filas, badges Stock Bajo/Normal, hover, botones de acción
- [ ] Tabla de movimientos: badges Entrada/Salida/Ajuste, hover
- [ ] Modal de confirmación al eliminar producto: legible en ambos temas
- [ ] Tab Almacenes:
  - [ ] Tarjetas con badge Activo/Inactivo
  - [ ] Botón "Nuevo almacén" abre modal (no prompt)
  - [ ] Botón Editar abre modal pre-llenado
  - [ ] Botón Eliminar pide confirmación
- [ ] Tab Transferencias:
  - [ ] Botón "Nueva transferencia" abre modal
  - [ ] Crear transferencia con varios productos
  - [ ] Botones Enviar/Recibir/Cancelar funcionan
  - [ ] Fecha se muestra formateada (ej: "12 jun 2026")
- [ ] Tab Inventario Físico:
  - [ ] Botón "Nueva sesión" abre modal (no prompt)
  - [ ] Botón "Contar" abre modal de detalle con tabla editable
  - [ ] Conteos se guardan al perder foco del input
  - [ ] Diferencia se actualiza en tiempo real
  - [ ] Botón "Aplicar ajustes" funciona
- [ ] Tab Alertas:
  - [ ] Tab activo mantiene color rose (no emerald)
  - [ ] Filas y badges legibles en dark mode
  - [ ] Botón "Resolver" funciona
- [ ] Importación CSV: estados de éxito/error/warning legibles en dark

- [ ] **Step 4: Commit final (si hubo ajustes)**

```bash
git add -A
git commit -m "chore(inventory): verificación visual final post-refactor"
```

---

## Self-review

**Cobertura de problemas detectados:**
- ✅ Dark mode en `showConfirm` → Task 1
- ✅ Dark mode en `renderProducts` → Task 2
- ✅ Dark mode en `renderMovements` → Task 3
- ✅ Dark mode en `renderAlmacenesGrid` + acciones editar/eliminar → Task 4
- ✅ Modal de crear/editar almacén → Task 5
- ✅ Dark mode + fecha en transferencias → Task 6
- ✅ Modal de crear transferencia (handler faltante) → Task 7
- ✅ Conteo manual de inventario físico → Task 8
- ✅ Dark mode en alertas + fix tab activo → Task 9
- ✅ Bugs menores de import → Task 10
- ✅ Verificación visual → Task 11

**Notas para el ejecutor:**
- No tocar los endpoints API — ya están completos y funcionando.
- Mantener el patrón vanilla-JS existente; no introducir librerías.
- Las clases Tailwind ya usadas en el proyecto siguen el patrón `bg-X-100 dark:bg-X-900/30 text-X-700 dark:text-X-400` — respétalo para coherencia.
- Después de cada task, **hacer build con `npm run build`** si modificaste JS, ya que Vite necesita re-bundlear.
- Trabajar directamente en `main` (preferencia del usuario, ver memoria `feedback_work_on_main`).
- Hacer un commit por task — no batch.
