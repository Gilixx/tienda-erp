/**
 * CRM-AC — Panel de Administración
 *
 * Vanilla JS + helper `api` (mismo patrón que inventory.js).
 * Gestiona usuarios, acceso a servicios/almacenes, catálogo, métricas y auditoría.
 */

import api from './api';

const root = document.getElementById('admin-app');
if (root) {
    const CURRENT_USER_ID = parseInt(root.dataset.currentUserId, 10);

    const state = {
        tab: 'usuarios',
        users: { data: [], meta: null, page: 1, search: '', role: '', status: '' },
        services: [],
        audit: { data: [], meta: null, page: 1 },
        loaded: { servicios: false, metricas: false, auditoria: false },
    };

    // ── Helpers ─────────────────────────────────────────────────
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
    ));

    const modalRoot = document.getElementById('admin-modal-root');
    const toastRoot = document.getElementById('toast-container');

    function toast(message, type = 'success') {
        const colors = {
            success: 'bg-emerald-600',
            error: 'bg-red-600',
            info: 'bg-slate-800',
        };
        const el = document.createElement('div');
        el.className = `${colors[type] || colors.info} text-white text-sm px-4 py-3 rounded-xl shadow-lg max-w-xs animate-[fadeIn_.15s_ease]`;
        el.textContent = message;
        toastRoot.appendChild(el);
        setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .3s'; }, 3000);
        setTimeout(() => el.remove(), 3400);
    }

    function apiError(err, fallback = 'Ocurrió un error.') {
        const res = err?.response?.data;
        if (res?.errors) {
            const first = Object.values(res.errors)[0];
            return Array.isArray(first) ? first[0] : String(first);
        }
        return res?.message || fallback;
    }

    function openModal(html, { maxWidth = 'max-w-lg' } = {}) {
        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 z-[250] bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4';
        overlay.innerHTML = `<div class="w-full ${maxWidth} bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl shadow-2xl max-h-[90vh] overflow-y-auto">${html}</div>`;
        overlay.addEventListener('mousedown', (e) => { if (e.target === overlay) close(); });
        modalRoot.appendChild(overlay);
        function close() { overlay.remove(); }
        return { overlay, close, el: overlay.firstElementChild };
    }

    function confirmDialog(message, { confirmText = 'Confirmar', danger = true } = {}) {
        return new Promise((resolve) => {
            const { close, el } = openModal(`
                <div class="p-6">
                    <p class="text-sm text-slate-700 dark:text-zinc-200 mb-6">${esc(message)}</p>
                    <div class="flex justify-end gap-2">
                        <button data-x="c" class="px-4 py-2 text-sm font-medium rounded-lg border border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-300 hover:bg-slate-50 dark:hover:bg-zinc-800">Cancelar</button>
                        <button data-x="k" class="px-4 py-2 text-sm font-semibold rounded-lg text-white ${danger ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700'}">${esc(confirmText)}</button>
                    </div>
                </div>`, { maxWidth: 'max-w-sm' });
            el.querySelector('[data-x="c"]').onclick = () => { close(); resolve(false); };
            el.querySelector('[data-x="k"]').onclick = () => { close(); resolve(true); };
        });
    }

    const inputCls = 'w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm text-slate-800 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-emerald-500';
    const labelCls = 'block text-xs font-medium text-slate-600 dark:text-zinc-400 mb-1';

    // ── Tabs ────────────────────────────────────────────────────
    const activeTabCls = ['border-emerald-600', 'text-emerald-700', 'dark:text-emerald-400'];
    const idleTabCls = ['border-transparent', 'text-slate-500', 'dark:text-zinc-400', 'hover:text-slate-700', 'dark:hover:text-zinc-200'];

    function switchTab(tab) {
        state.tab = tab;
        document.querySelectorAll('.admin-tab').forEach((b) => {
            const on = b.dataset.tab === tab;
            b.classList.remove(...activeTabCls, ...idleTabCls);
            b.classList.add(...(on ? activeTabCls : idleTabCls));
        });
        document.querySelectorAll('[data-panel]').forEach((p) => {
            p.classList.toggle('hidden', p.dataset.panel !== tab);
        });

        if (tab === 'servicios' && !state.loaded.servicios) loadServices();
        if (tab === 'metricas' && !state.loaded.metricas) loadStats();
        if (tab === 'auditoria' && !state.loaded.auditoria) loadAudit();
    }

    // ── Usuarios ────────────────────────────────────────────────
    async function loadUsers() {
        const params = { page: state.users.page, per_page: 20 };
        if (state.users.search) params.search = state.users.search;
        if (state.users.role) params.role = state.users.role;
        if (state.users.status === 'trashed') params.trashed = 1;
        else if (state.users.status !== '') params.is_active = state.users.status;

        try {
            const { data } = await api.get('/api/admin/users', params);
            state.users.data = data.data;
            state.users.meta = data;
            renderUsers();
        } catch (err) {
            toast(apiError(err, 'No se pudieron cargar los usuarios.'), 'error');
        }
    }

    function roleBadge(role) {
        return role === 'admin'
            ? '<span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">Admin</span>'
            : '<span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400">Usuario</span>';
    }

    function statusBadge(u) {
        if (u.deleted_at) return '<span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400">Eliminado</span>';
        return u.is_active
            ? '<span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">Activo</span>'
            : '<span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400">Inactivo</span>';
    }

    function renderUsers() {
        const tbody = document.getElementById('users-tbody');
        const empty = document.getElementById('users-empty');
        const rows = state.users.data;

        empty.classList.toggle('hidden', rows.length > 0);
        tbody.innerHTML = rows.map((u) => {
            const services = (u.services || []).map((s) =>
                `<span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 mr-1">${esc(s.name)}</span>`
            ).join('') || '<span class="text-xs text-slate-400">—</span>';

            const acciones = u.deleted_at
                ? `<button data-act="restore" data-id="${u.id}" class="text-xs font-medium text-emerald-600 hover:text-emerald-700">Restaurar</button>`
                : `
                    <button data-act="access" data-id="${u.id}" class="text-xs font-medium text-emerald-600 hover:text-emerald-700">Acceso</button>
                    <button data-act="edit" data-id="${u.id}" class="text-xs font-medium text-slate-500 hover:text-slate-700 dark:text-zinc-400 dark:hover:text-zinc-200">Editar</button>
                    <button data-act="toggle" data-id="${u.id}" class="text-xs font-medium text-amber-600 hover:text-amber-700">${u.is_active ? 'Desactivar' : 'Activar'}</button>
                    <button data-act="reset" data-id="${u.id}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">Reset</button>
                    <button data-act="delete" data-id="${u.id}" class="text-xs font-medium text-red-600 hover:text-red-700">Eliminar</button>
                `;

            return `<tr class="hover:bg-slate-50 dark:hover:bg-zinc-800/40">
                <td class="px-4 py-3">
                    <p class="font-medium text-slate-800 dark:text-zinc-100">${esc(u.name)}</p>
                    <p class="text-xs text-slate-400 dark:text-zinc-500">${esc(u.email)}</p>
                </td>
                <td class="px-4 py-3">${roleBadge(u.role)}</td>
                <td class="px-4 py-3">${statusBadge(u)}</td>
                <td class="px-4 py-3">${services}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap space-x-2">${acciones}</td>
            </tr>`;
        }).join('');

        // Paginación
        const m = state.users.meta;
        const pag = document.getElementById('users-pagination');
        if (m) {
            pag.innerHTML = `
                <span>${m.total} usuario(s) — página ${m.current_page} de ${m.last_page}</span>
                <span class="flex gap-2">
                    <button data-pg="prev" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-zinc-700 disabled:opacity-40" ${m.current_page <= 1 ? 'disabled' : ''}>Anterior</button>
                    <button data-pg="next" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-zinc-700 disabled:opacity-40" ${m.current_page >= m.last_page ? 'disabled' : ''}>Siguiente</button>
                </span>`;
        }
    }

    function userForm(u = null) {
        const isEdit = !!u;
        const { close, el } = openModal(`
            <div class="p-6">
                <h3 class="text-base font-semibold text-slate-900 dark:text-zinc-50 mb-5">${isEdit ? 'Editar usuario' : 'Nuevo usuario'}</h3>
                <form id="user-form" class="space-y-4">
                    <div><label class="${labelCls}">Nombre</label><input name="name" class="${inputCls}" value="${esc(u?.name || '')}" required></div>
                    <div><label class="${labelCls}">Email</label><input name="email" type="email" class="${inputCls}" value="${esc(u?.email || '')}" required></div>
                    <div><label class="${labelCls}">Teléfono</label><input name="phone" class="${inputCls}" value="${esc(u?.phone || '')}"></div>
                    <div><label class="${labelCls}">Rol</label>
                        <select name="role" class="${inputCls}">
                            <option value="user" ${u?.role === 'user' ? 'selected' : ''}>Usuario</option>
                            <option value="admin" ${u?.role === 'admin' ? 'selected' : ''}>Administrador</option>
                        </select>
                    </div>
                    ${isEdit ? '' : `<div><label class="${labelCls}">Contraseña</label><input name="password" type="password" class="${inputCls}" minlength="8" required></div>`}
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" data-x="c" class="px-4 py-2 text-sm font-medium rounded-lg border border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-300 hover:bg-slate-50 dark:hover:bg-zinc-800">Cancelar</button>
                        <button type="submit" class="px-4 py-2 text-sm font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white">${isEdit ? 'Guardar' : 'Crear'}</button>
                    </div>
                </form>
            </div>`);

        el.querySelector('[data-x="c"]').onclick = close;
        el.querySelector('#user-form').onsubmit = async (e) => {
            e.preventDefault();
            const fd = Object.fromEntries(new FormData(e.target));
            try {
                if (isEdit) {
                    await api.put(`/api/admin/users/${u.id}`, fd);
                    toast('Usuario actualizado.');
                } else {
                    await api.post('/api/admin/users', fd);
                    toast('Usuario creado.');
                }
                close();
                loadUsers();
            } catch (err) {
                toast(apiError(err), 'error');
            }
        };
    }

    async function accessModal(userId) {
        let payload;
        try {
            const { data } = await api.get(`/api/admin/users/${userId}/access`);
            payload = data;
        } catch (err) {
            toast(apiError(err, 'No se pudo cargar el acceso.'), 'error');
            return;
        }

        const deps = payload.dependencias || {};          // { pos: 'inventory' }
        const actualKeys = new Set(payload.acceso_actual.services.map((s) => s.key));
        const expiraciones = {};
        payload.acceso_actual.services.forEach((s) => { expiraciones[s.key] = s.expires_at || ''; });
        const almacenSel = new Set(payload.acceso_actual.almacen_ids.map(Number));

        const tomorrow = new Date(Date.now() + 86400000).toISOString().slice(0, 10);

        const serviciosHtml = payload.services_disponibles.map((s) => {
            const checked = actualKeys.has(s.key) ? 'checked' : '';
            const exp = expiraciones[s.key] || '';
            return `<div class="border border-slate-200 dark:border-zinc-700 rounded-lg p-3" data-service="${esc(s.key)}">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" class="svc-check w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500" data-key="${esc(s.key)}" ${checked}>
                    <span class="text-sm font-medium text-slate-800 dark:text-zinc-100">${esc(s.name)}</span>
                    <span class="text-[10px] font-mono text-slate-400">${esc(s.key)}</span>
                </label>
                <div class="svc-exp mt-2 ${checked ? '' : 'hidden'}">
                    <label class="${labelCls}">Expira (opcional)</label>
                    <input type="date" class="svc-exp-input ${inputCls}" min="${tomorrow}" value="${esc(exp)}">
                </div>
            </div>`;
        }).join('');

        const almacenesHtml = payload.almacenes_disponibles.map((a) => `
            <label class="flex items-center gap-2 cursor-pointer text-sm text-slate-700 dark:text-zinc-200">
                <input type="checkbox" class="alm-check w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500" value="${a.id}" ${almacenSel.has(a.id) ? 'checked' : ''}>
                ${esc(a.nombre)} <span class="text-[10px] font-mono text-slate-400">${esc(a.codigo)}</span>
            </label>`).join('') || '<p class="text-xs text-slate-400">No hay almacenes disponibles.</p>';

        const { close, el } = openModal(`
            <div class="p-6">
                <h3 class="text-base font-semibold text-slate-900 dark:text-zinc-50 mb-1">Gestionar acceso</h3>
                <p class="text-xs text-slate-400 dark:text-zinc-500 mb-5">Ventas (POS) requiere Inventario y al menos un almacén.</p>

                <div class="space-y-2 mb-5">${serviciosHtml}</div>

                <div id="almacenes-block" class="mb-5 hidden">
                    <label class="${labelCls}">Almacenes accesibles</label>
                    <div class="border border-slate-200 dark:border-zinc-700 rounded-lg p-3 space-y-2 max-h-40 overflow-y-auto">${almacenesHtml}</div>
                </div>

                <div class="flex justify-end gap-2">
                    <button data-x="c" class="px-4 py-2 text-sm font-medium rounded-lg border border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-300 hover:bg-slate-50 dark:hover:bg-zinc-800">Cancelar</button>
                    <button data-x="s" class="px-4 py-2 text-sm font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white">Guardar acceso</button>
                </div>
            </div>`, { maxWidth: 'max-w-lg' });

        const almBlock = el.querySelector('#almacenes-block');
        const checks = () => Array.from(el.querySelectorAll('.svc-check'));
        const checkedKeys = () => new Set(checks().filter((c) => c.checked).map((c) => c.dataset.key));

        // Aplica reglas de dependencia sobre la UI
        function applyRules() {
            const active = checkedKeys();
            checks().forEach((c) => {
                const req = deps[c.dataset.key];      // requisito de este servicio
                if (req) {
                    const ok = active.has(req);
                    if (!ok) { c.checked = false; }
                    c.disabled = !ok;
                    c.closest('[data-service]').classList.toggle('opacity-50', !ok);
                }
                // Mostrar/ocultar fecha de expiración según el estado real
                c.closest('[data-service]').querySelector('.svc-exp').classList.toggle('hidden', !c.checked);
            });
            // Almacenes visibles si POS activo
            almBlock.classList.toggle('hidden', !checkedKeys().has('pos'));
        }

        checks().forEach((c) => c.addEventListener('change', applyRules));
        applyRules();

        el.querySelector('[data-x="c"]').onclick = close;
        el.querySelector('[data-x="s"]').onclick = async () => {
            const services = checks().filter((c) => c.checked).map((c) => {
                const box = c.closest('[data-service]');
                const exp = box.querySelector('.svc-exp-input').value || null;
                return { key: c.dataset.key, expires_at: exp };
            });
            const almacen_ids = Array.from(el.querySelectorAll('.alm-check'))
                .filter((c) => c.checked).map((c) => parseInt(c.value, 10));

            try {
                await api.put(`/api/admin/users/${userId}/access`, { services, almacen_ids });
                toast('Acceso actualizado.');
                close();
                loadUsers();
            } catch (err) {
                toast(apiError(err), 'error');
            }
        };
    }

    async function resetPasswordModal(userId) {
        const { close, el } = openModal(`
            <div class="p-6">
                <h3 class="text-base font-semibold text-slate-900 dark:text-zinc-50 mb-2">Restablecer contraseña</h3>
                <p class="text-xs text-slate-400 dark:text-zinc-500 mb-4">El usuario deberá cambiarla en su próximo ingreso.</p>
                <form id="rp-form" class="space-y-4">
                    <div><label class="${labelCls}">Nueva contraseña temporal</label><input name="password" type="text" class="${inputCls}" minlength="8" required></div>
                    <div class="flex justify-end gap-2">
                        <button type="button" data-x="c" class="px-4 py-2 text-sm font-medium rounded-lg border border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-300 hover:bg-slate-50 dark:hover:bg-zinc-800">Cancelar</button>
                        <button type="submit" class="px-4 py-2 text-sm font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white">Restablecer</button>
                    </div>
                </form>
            </div>`, { maxWidth: 'max-w-sm' });
        el.querySelector('[data-x="c"]').onclick = close;
        el.querySelector('#rp-form').onsubmit = async (e) => {
            e.preventDefault();
            const password = new FormData(e.target).get('password');
            try {
                await api.post(`/api/admin/users/${userId}/reset-password`, { password });
                toast('Contraseña restablecida.');
                close();
            } catch (err) {
                toast(apiError(err), 'error');
            }
        };
    }

    // ── Servicios (catálogo) ────────────────────────────────────
    async function loadServices() {
        try {
            const { data } = await api.get('/api/admin/services');
            state.services = data;
            state.loaded.servicios = true;
            renderServices();
        } catch (err) {
            toast(apiError(err, 'No se pudieron cargar los servicios.'), 'error');
        }
    }

    function renderServices() {
        const grid = document.getElementById('services-grid');
        grid.innerHTML = state.services.map((s) => `
            <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl p-5">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900 dark:text-zinc-50">${esc(s.name)}</h4>
                        <span class="text-[10px] font-mono text-slate-400">${esc(s.key)}</span>
                    </div>
                    <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold ${s.is_active ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : 'bg-slate-100 dark:bg-zinc-800 text-slate-500'}">${s.is_active ? 'Activo' : 'Oculto'}</span>
                </div>
                <p class="text-xs text-slate-500 dark:text-zinc-400 mb-3 min-h-[2rem]">${esc(s.description || 'Sin descripción')}</p>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-slate-400">${s.users_count ?? 0} usuario(s)</span>
                    <button data-act="edit-service" data-id="${s.id}" class="text-xs font-medium text-emerald-600 hover:text-emerald-700">Editar</button>
                </div>
            </div>`).join('');
    }

    function serviceForm(s) {
        const { close, el } = openModal(`
            <div class="p-6">
                <h3 class="text-base font-semibold text-slate-900 dark:text-zinc-50 mb-5">Editar servicio</h3>
                <form id="svc-form" class="space-y-4">
                    <div><label class="${labelCls}">Clave</label><input class="${inputCls} opacity-60" value="${esc(s.key)}" disabled></div>
                    <div><label class="${labelCls}">Nombre</label><input name="name" class="${inputCls}" value="${esc(s.name)}" required></div>
                    <div><label class="${labelCls}">Descripción</label><input name="description" class="${inputCls}" value="${esc(s.description || '')}"></div>
                    <div><label class="${labelCls}">Ícono</label><input name="icon" class="${inputCls}" value="${esc(s.icon || '')}"></div>
                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-zinc-200">
                        <input type="checkbox" name="is_active" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500" ${s.is_active ? 'checked' : ''}>
                        Visible / activo
                    </label>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" data-x="c" class="px-4 py-2 text-sm font-medium rounded-lg border border-slate-200 dark:border-zinc-700 text-slate-600 dark:text-zinc-300 hover:bg-slate-50 dark:hover:bg-zinc-800">Cancelar</button>
                        <button type="submit" class="px-4 py-2 text-sm font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white">Guardar</button>
                    </div>
                </form>
            </div>`);
        el.querySelector('[data-x="c"]').onclick = close;
        el.querySelector('#svc-form').onsubmit = async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            const body = {
                name: fd.get('name'),
                description: fd.get('description'),
                icon: fd.get('icon'),
                is_active: fd.get('is_active') === 'on',
            };
            try {
                await api.put(`/api/admin/services/${s.id}`, body);
                toast('Servicio actualizado.');
                close();
                loadServices();
            } catch (err) {
                toast(apiError(err), 'error');
            }
        };
    }

    // ── Métricas ────────────────────────────────────────────────
    async function loadStats() {
        try {
            const { data } = await api.get('/api/admin/stats');
            state.loaded.metricas = true;
            renderStats(data);
        } catch (err) {
            toast(apiError(err, 'No se pudieron cargar las métricas.'), 'error');
        }
    }

    function renderStats(s) {
        const card = (label, value, color) => `
            <div class="bg-white dark:bg-zinc-900 border border-slate-200/70 dark:border-zinc-800 rounded-2xl p-4">
                <p class="text-2xl font-bold ${color}">${value}</p>
                <p class="text-xs text-slate-500 dark:text-zinc-400 mt-1">${label}</p>
            </div>`;
        document.getElementById('stats-cards').innerHTML = [
            card('Usuarios', s.total_usuarios, 'text-slate-900 dark:text-zinc-50'),
            card('Activos', s.activos, 'text-emerald-600'),
            card('Inactivos', s.inactivos, 'text-amber-600'),
            card('Administradores', s.admins, 'text-indigo-600'),
            card('Eliminados', s.eliminados, 'text-red-600'),
        ].join('');

        const max = Math.max(1, ...s.por_servicio.map((x) => x.total));
        document.getElementById('stats-por-servicio').innerHTML = s.por_servicio.map((x) => `
            <div>
                <div class="flex justify-between text-xs mb-1">
                    <span class="font-medium text-slate-700 dark:text-zinc-300">${esc(x.name)}</span>
                    <span class="text-slate-400">${x.total}</span>
                </div>
                <div class="h-2 rounded-full bg-slate-100 dark:bg-zinc-800 overflow-hidden">
                    <div class="h-full bg-emerald-500 rounded-full" style="width:${(x.total / max) * 100}%"></div>
                </div>
            </div>`).join('') || '<p class="text-xs text-slate-400">Sin datos.</p>';
    }

    // ── Auditoría ───────────────────────────────────────────────
    async function loadAudit() {
        try {
            const { data } = await api.get('/api/admin/audit-logs', { page: state.audit.page, per_page: 30 });
            state.audit.data = data.data;
            state.audit.meta = data;
            state.loaded.auditoria = true;
            renderAudit();
        } catch (err) {
            toast(apiError(err, 'No se pudo cargar la auditoría.'), 'error');
        }
    }

    function renderAudit() {
        const tbody = document.getElementById('audit-tbody');
        const empty = document.getElementById('audit-empty');
        const rows = state.audit.data;
        empty.classList.toggle('hidden', rows.length > 0);
        tbody.innerHTML = rows.map((r) => `
            <tr>
                <td class="px-4 py-3 text-xs text-slate-500 dark:text-zinc-400 whitespace-nowrap">${esc(new Date(r.created_at).toLocaleString('es-MX'))}</td>
                <td class="px-4 py-3 text-slate-700 dark:text-zinc-200">${esc(r.user?.name || 'Sistema')}</td>
                <td class="px-4 py-3"><span class="text-[11px] font-mono px-1.5 py-0.5 rounded bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400">${esc(r.action)}</span></td>
                <td class="px-4 py-3 text-slate-600 dark:text-zinc-300">${esc(r.description)}</td>
            </tr>`).join('');

        const m = state.audit.meta;
        const pag = document.getElementById('audit-pagination');
        if (m) {
            pag.innerHTML = `
                <span>${m.total} registro(s) — página ${m.current_page} de ${m.last_page}</span>
                <span class="flex gap-2">
                    <button data-apg="prev" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-zinc-700 disabled:opacity-40" ${m.current_page <= 1 ? 'disabled' : ''}>Anterior</button>
                    <button data-apg="next" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-zinc-700 disabled:opacity-40" ${m.current_page >= m.last_page ? 'disabled' : ''}>Siguiente</button>
                </span>`;
        }
    }

    // ── Event delegation ────────────────────────────────────────
    document.querySelectorAll('.admin-tab').forEach((b) => b.onclick = () => switchTab(b.dataset.tab));

    let searchTimer;
    document.getElementById('user-search').addEventListener('input', (e) => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => { state.users.search = e.target.value.trim(); state.users.page = 1; loadUsers(); }, 300);
    });
    document.getElementById('filter-role').onchange = (e) => { state.users.role = e.target.value; state.users.page = 1; loadUsers(); };
    document.getElementById('filter-status').onchange = (e) => { state.users.status = e.target.value; state.users.page = 1; loadUsers(); };
    document.getElementById('btn-nuevo-usuario').onclick = () => userForm();
    document.getElementById('btn-exportar').onclick = () => { window.location.href = '/api/admin/export/users'; };

    // Acciones de la tabla de usuarios
    document.getElementById('users-tbody').addEventListener('click', async (e) => {
        const btn = e.target.closest('button[data-act]');
        if (!btn) return;
        const id = parseInt(btn.dataset.id, 10);
        const user = state.users.data.find((u) => u.id === id);

        switch (btn.dataset.act) {
            case 'access': accessModal(id); break;
            case 'edit': userForm(user); break;
            case 'reset': resetPasswordModal(id); break;
            case 'toggle': {
                const verb = user.is_active ? 'desactivar' : 'activar';
                if (await confirmDialog(`¿Seguro que deseas ${verb} a ${user.name}?`, { confirmText: 'Confirmar', danger: user.is_active })) {
                    try { await api.patch(`/api/admin/users/${id}/toggle-active`); toast('Estado actualizado.'); loadUsers(); }
                    catch (err) { toast(apiError(err), 'error'); }
                }
                break;
            }
            case 'delete': {
                if (await confirmDialog(`¿Eliminar a ${user.name}? Podrás restaurarlo después.`, { confirmText: 'Eliminar' })) {
                    try { await api.delete(`/api/admin/users/${id}`); toast('Usuario eliminado.'); loadUsers(); }
                    catch (err) { toast(apiError(err), 'error'); }
                }
                break;
            }
            case 'restore': {
                try { await api.post(`/api/admin/users/${id}/restore`); toast('Usuario restaurado.'); loadUsers(); }
                catch (err) { toast(apiError(err), 'error'); }
                break;
            }
        }
    });

    // Paginación usuarios
    document.getElementById('users-pagination').addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-pg]');
        if (!btn) return;
        state.users.page += btn.dataset.pg === 'next' ? 1 : -1;
        loadUsers();
    });

    // Paginación auditoría
    document.getElementById('audit-pagination').addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-apg]');
        if (!btn) return;
        state.audit.page += btn.dataset.apg === 'next' ? 1 : -1;
        loadAudit();
    });

    // Editar servicio
    document.getElementById('services-grid').addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-act="edit-service"]');
        if (!btn) return;
        const s = state.services.find((x) => x.id === parseInt(btn.dataset.id, 10));
        if (s) serviceForm(s);
    });

    // ── Init ────────────────────────────────────────────────────
    switchTab('usuarios');
    loadUsers();
}
