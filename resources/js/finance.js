import api from './api';
import Chart from 'chart.js/auto';

document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('finance-app');
    if (!app) return;

    let chartFlow = null;
    let chartCats = null;
    let monedaBase = { codigo: 'MXN', simbolo: '$' };
    let cuentas = [];
    let categorias = [];
    let monedas = [];
    let impuestos = [];
    let proveedores = [];
    let productos = [];

    const fmt = (n, code = null) => {
        const cur = code || monedaBase.codigo || 'MXN';
        try { return new Intl.NumberFormat('es-MX', { style: 'currency', currency: cur }).format(n || 0); }
        catch { return `${monedaBase.simbolo}${(n || 0).toFixed(2)}`; }
    };
    const fmtDate = (s) => s ? new Date(s).toLocaleDateString('es-MX') : '—';
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    function toast(msg, type = 'success') {
        const colors = type === 'error' ? 'bg-rose-600' : (type === 'warn' ? 'bg-amber-600' : 'bg-emerald-600');
        const el = document.createElement('div');
        el.className = `${colors} text-white px-4 py-2.5 rounded-xl shadow-lg text-sm pointer-events-auto`;
        el.textContent = msg;
        document.getElementById('toast-container').appendChild(el);
        setTimeout(() => el.remove(), 4000);
    }

    function todayStr() {
        return new Date().toISOString().slice(0, 10);
    }

    // ──── Modales ──────────────────────────────────────────────
    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
    }
    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }
    document.querySelectorAll('.modal-close').forEach(el => {
        el.addEventListener('click', () => closeModal(el.dataset.modal));
    });

    // ──── Tabs ─────────────────────────────────────────────────
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => {
                b.classList.remove('border-orange-500', 'text-orange-600');
                b.classList.add('border-transparent', 'text-slate-500');
            });
            btn.classList.add('border-orange-500', 'text-orange-600');
            btn.classList.remove('border-transparent', 'text-slate-500');

            const tab = btn.dataset.tab;
            document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
            document.getElementById('tab-' + tab).classList.remove('hidden');

            if (tab === 'transacciones') loadTransacciones();
            if (tab === 'cuentas')       renderCuentas();
            if (tab === 'compras')       loadCompras();
            if (tab === 'proveedores')   loadProveedores();
            if (tab === 'cxc')           loadCxC();
            if (tab === 'cxp')           loadCxP();
            if (tab === 'presupuestos')  loadPresupuestos();
        });
    });

    // ──── Carga de catálogos ───────────────────────────────────
    async function loadCatalogos() {
        try {
            const [cu, ca, mo, im, pr, pd] = await Promise.all([
                api.get('/api/finance/cuentas'),
                api.get('/api/finance/categorias'),
                api.get('/api/finance/monedas'),
                api.get('/api/finance/impuestos'),
                api.get('/api/finance/proveedores'),
                api.get('/api/finance/products'),
            ]);
            cuentas     = cu.data;
            categorias  = ca.data;
            monedas     = mo.data;
            impuestos   = im.data.filter(i => i.activo);
            proveedores = pr.data;
            productos   = pd.data;

            const base = monedas.find(m => m.es_base);
            if (base) monedaBase = base;

            // Llenar selects del modal de transacción
            fillSelect('tx-cuenta',    cuentas.filter(c => c.activa).map(c => ({ value: c.id, label: `${c.nombre} (${c.moneda?.codigo || '—'})` })));
            fillSelect('tx-categoria', categorias.map(c => ({ value: c.id, label: `[${c.tipo}] ${c.nombre}` })), true);
            fillSelect('tx-moneda',    monedas.filter(m => m.activa).map(m => ({ value: m.id, label: `${m.codigo} — ${m.nombre}`, selected: m.es_base })));
            fillSelect('tx-impuesto',  impuestos.map(i => ({ value: i.id, label: `${i.nombre} (${parseFloat(i.tasa)}%)` })), true);

            // Transferencias
            const opts = cuentas.filter(c => c.activa).map(c => ({ value: c.id, label: `${c.nombre} — ${fmt(c.saldo_actual, c.moneda?.codigo)}` }));
            fillSelect('tr-origen', opts);
            fillSelect('tr-destino', opts);

            // Pagos
            fillSelect('pago-cuenta', opts);

            // Compras (modal)
            fillSelect('co-proveedor', proveedores.filter(p => p.activo !== false).map(p => ({ value: p.id, label: p.nombre })));
            fillSelect('co-moneda',    monedas.filter(m => m.activa).map(m => ({ value: m.id, label: m.codigo, selected: m.es_base })));
            fillSelect('recibir-cuenta', opts);

            // Proveedores (modal)
            fillSelect('prov-moneda', monedas.filter(m => m.activa).map(m => ({ value: m.id, label: `${m.codigo} — ${m.nombre}`, selected: m.es_base })), true);
        } catch (err) {
            console.error(err);
            toast('Error al cargar catálogos', 'error');
        }
    }

    function fillSelect(id, items, includeEmpty = false) {
        const sel = document.getElementById(id);
        if (!sel) return;
        let html = '';
        if (includeEmpty) html += '<option value="">— Seleccionar —</option>';
        for (const it of items) {
            html += `<option value="${esc(it.value)}"${it.selected ? ' selected' : ''}>${esc(it.label)}</option>`;
        }
        sel.innerHTML = html;
    }

    // ──── Stats / KPIs ─────────────────────────────────────────
    async function loadStats() {
        const days = document.getElementById('fin-period').value;
        try {
            const { data } = await api.get('/api/finance/stats', { days });

            if (data.moneda_base) monedaBase = data.moneda_base;

            const s = data.summary;
            document.getElementById('kpi-ingresos').textContent = fmt(s.ingresos);
            document.getElementById('kpi-egresos').textContent  = fmt(s.egresos);
            document.getElementById('kpi-utilidad').textContent = fmt(s.utilidad);
            document.getElementById('kpi-utilidad').className   = `text-2xl font-bold mt-1 ${s.utilidad >= 0 ? 'text-slate-800' : 'text-rose-600'}`;
            document.getElementById('kpi-margen').textContent   = `Margen ${s.margen}%`;

            const saldoTotal = (data.cuentas || []).reduce((acc, c) => acc + (parseFloat(c.saldo_actual) || 0), 0);
            document.getElementById('kpi-saldo').textContent = fmt(saldoTotal);
            document.getElementById('kpi-cuentas-info').textContent = `${(data.cuentas || []).length} cuentas activas`;

            document.getElementById('cxc-monto').textContent = fmt(data.cxc.pendiente);
            document.getElementById('cxc-info').textContent  = `${data.cxc.count} pendiente(s) · ${fmt(data.cxc.vencida)} vencido`;
            document.getElementById('cxp-monto').textContent = fmt(data.cxp.pendiente);
            document.getElementById('cxp-info').textContent  = `${data.cxp.count} pendiente(s) · ${fmt(data.cxp.vencida)} vencido`;

            renderFlow(data.daily_flow);
            renderCats(data.by_category);

            // guardar cuentas para tab Cuentas
            window.__finCuentasStats = data.cuentas || [];
        } catch (err) {
            console.error(err);
            toast('Error al cargar estadísticas', 'error');
        }
    }

    function renderFlow(series) {
        const ctx = document.getElementById('chart-flow');
        if (chartFlow) chartFlow.destroy();
        chartFlow = new Chart(ctx, {
            type: 'line',
            data: {
                labels: series.map(s => fmtDate(s.fecha)),
                datasets: [
                    { label: 'Ingresos', data: series.map(s => s.ingresos), borderColor: 'rgba(16, 185, 129, 1)', backgroundColor: 'rgba(16, 185, 129, 0.12)', fill: true, tension: 0.3, pointRadius: 2 },
                    { label: 'Egresos',  data: series.map(s => s.egresos),  borderColor: 'rgba(244, 63, 94, 1)',  backgroundColor: 'rgba(244, 63, 94, 0.12)',  fill: true, tension: 0.3, pointRadius: 2 },
                ],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true, ticks: { callback: v => fmt(v) } } }
            }
        });
    }

    function renderCats(rows) {
        const egresos = rows.filter(r => r.tipo === 'egreso');
        const ctx = document.getElementById('chart-cats');
        if (chartCats) chartCats.destroy();
        if (egresos.length === 0) {
            const c = ctx.getContext('2d');
            c.clearRect(0, 0, ctx.width, ctx.height);
            c.font = '13px Inter'; c.fillStyle = '#94a3b8'; c.textAlign = 'center';
            c.fillText('Sin egresos en el período', ctx.width / 2, ctx.height / 2);
            return;
        }
        chartCats = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: egresos.map(r => r.nombre),
                datasets: [{
                    data: egresos.map(r => r.monto),
                    backgroundColor: egresos.map(r => r.color || '#94a3b8'),
                    borderWidth: 1,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'right', labels: { font: { size: 11 } } } }
            }
        });
    }

    // ──── Transacciones ────────────────────────────────────────
    async function loadTransacciones() {
        try {
            const { data } = await api.get('/api/finance/transacciones', { per_page: 50 });
            const rows = data.data || [];
            const tbody = document.getElementById('tbody-transacciones');
            if (!rows.length) {
                tbody.innerHTML = `<tr><td colspan="7" class="px-5 py-6 text-center text-slate-400">Sin transacciones</td></tr>`;
                return;
            }
            tbody.innerHTML = rows.map(t => {
                const color = t.tipo === 'ingreso' ? 'text-emerald-600' : (t.tipo === 'egreso' ? 'text-rose-600' : 'text-indigo-600');
                const badge = {
                    ingreso:       'bg-emerald-100 text-emerald-700',
                    egreso:        'bg-rose-100 text-rose-700',
                    transferencia: 'bg-indigo-100 text-indigo-700',
                }[t.tipo] || 'bg-slate-100 text-slate-700';
                const sign = t.tipo === 'egreso' ? '-' : (t.tipo === 'ingreso' ? '+' : '');
                return `
                <tr class="border-b border-slate-100 hover:bg-slate-50">
                    <td class="px-5 py-3 text-slate-500">${fmtDate(t.fecha)}</td>
                    <td class="px-5 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium ${badge}">${t.tipo}</span></td>
                    <td class="px-5 py-3">${esc(t.categoria?.nombre || '—')}</td>
                    <td class="px-5 py-3">${esc(t.cuenta?.nombre || '—')}</td>
                    <td class="px-5 py-3 text-slate-600">${esc(t.descripcion || '')}</td>
                    <td class="px-5 py-3 text-right font-semibold ${color}">${sign}${fmt(t.total, t.moneda?.codigo)}</td>
                    <td class="px-5 py-3 text-right">
                        ${t.venta_id || t.compra_id ? '<span class="text-xs text-slate-400">vinculada</span>' :
                            `<button data-del-tx="${t.id}" class="text-slate-400 hover:text-rose-600 text-xs">Eliminar</button>`}
                    </td>
                </tr>`;
            }).join('');

            tbody.querySelectorAll('[data-del-tx]').forEach(btn => {
                btn.addEventListener('click', async () => {
                    if (!confirm('¿Eliminar esta transacción? Se revertirá el saldo.')) return;
                    try {
                        await api.delete(`/api/finance/transacciones/${btn.dataset.delTx}`);
                        toast('Transacción eliminada');
                        loadStats(); loadTransacciones();
                    } catch (err) {
                        toast(err.response?.data?.error || 'Error', 'error');
                    }
                });
            });
        } catch (err) {
            console.error(err);
            toast('Error al cargar transacciones', 'error');
        }
    }

    // ──── Cuentas tab ──────────────────────────────────────────
    function renderCuentas() {
        const cont = document.getElementById('tab-cuentas');
        const rows = window.__finCuentasStats || [];
        if (!rows.length) {
            cont.innerHTML = '<div class="text-slate-400 text-sm col-span-full text-center py-6">Sin cuentas activas</div>';
            return;
        }
        cont.innerHTML = rows.map(c => `
            <div class="bg-white border border-slate-200/60 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold">${esc(c.tipo)}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">${esc(c.moneda?.codigo || '—')}</span>
                </div>
                <h4 class="text-base font-bold text-slate-800">${esc(c.nombre)}</h4>
                <p class="text-2xl font-bold text-indigo-600 mt-2">${fmt(c.saldo_actual, c.moneda?.codigo)}</p>
            </div>
        `).join('');
    }

    // ──── CxC / CxP ────────────────────────────────────────────
    async function loadCxC() {
        try {
            const { data } = await api.get('/api/finance/cxc');
            const rows = data.data || [];
            const tbody = document.getElementById('tbody-cxc');
            if (!rows.length) {
                tbody.innerHTML = `<tr><td colspan="7" class="px-5 py-6 text-center text-slate-400">Sin cuentas por cobrar</td></tr>`;
                return;
            }
            tbody.innerHTML = rows.map(r => rowCxx(r, 'cxc')).join('');
            attachPagoButtons('cxc');
        } catch (err) { console.error(err); toast('Error al cargar CxC', 'error'); }
    }

    async function loadCxP() {
        try {
            const { data } = await api.get('/api/finance/cxp');
            const rows = data.data || [];
            const tbody = document.getElementById('tbody-cxp');
            if (!rows.length) {
                tbody.innerHTML = `<tr><td colspan="7" class="px-5 py-6 text-center text-slate-400">Sin cuentas por pagar</td></tr>`;
                return;
            }
            tbody.innerHTML = rows.map(r => rowCxx(r, 'cxp')).join('');
            attachPagoButtons('cxp');
        } catch (err) { console.error(err); toast('Error al cargar CxP', 'error'); }
    }

    function rowCxx(r, modo) {
        const hoy = new Date().toISOString().slice(0,10);
        const vencida = r.fecha_vencimiento < hoy && !['pagada','cancelada'].includes(r.estado);
        const badge = {
            pendiente: 'bg-amber-100 text-amber-700',
            parcial:   'bg-indigo-100 text-indigo-700',
            pagada:    'bg-emerald-100 text-emerald-700',
            vencida:   'bg-rose-100 text-rose-700',
            cancelada: 'bg-slate-100 text-slate-500',
        }[r.estado] || 'bg-slate-100 text-slate-700';
        const nombre = modo === 'cxc' ? (r.cliente || '—') : (r.proveedor?.nombre || '—');
        const accion = ['pagada','cancelada'].includes(r.estado) ? '' :
            `<button data-pago="${r.id}" data-modo="${modo}" data-saldo="${r.saldo}" data-moneda="${r.moneda?.codigo || ''}" data-nombre="${esc(nombre)}" class="text-xs text-emerald-600 hover:text-emerald-700 font-medium">Pagar</button>`;
        return `
        <tr class="border-b border-slate-100 hover:bg-slate-50 ${vencida ? 'bg-rose-50/40' : ''}">
            <td class="px-5 py-3 font-medium">${esc(nombre)}</td>
            <td class="px-5 py-3 text-slate-500">${fmtDate(r.fecha_emision)}</td>
            <td class="px-5 py-3 text-slate-500">${fmtDate(r.fecha_vencimiento)}${vencida ? ' <span class="text-xs text-rose-600">vencida</span>' : ''}</td>
            <td class="px-5 py-3 text-right">${fmt(r.monto_total, r.moneda?.codigo)}</td>
            <td class="px-5 py-3 text-right font-semibold">${fmt(r.saldo, r.moneda?.codigo)}</td>
            <td class="px-5 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium ${badge}">${r.estado}</span></td>
            <td class="px-5 py-3 text-right">${accion}</td>
        </tr>`;
    }

    function attachPagoButtons(modo) {
        document.querySelectorAll(`#tab-${modo} [data-pago]`).forEach(btn => {
            btn.addEventListener('click', () => abrirPago(btn.dataset.modo, btn.dataset.pago, parseFloat(btn.dataset.saldo), btn.dataset.moneda, btn.dataset.nombre));
        });
    }

    function abrirPago(modo, id, saldo, moneda, nombre) {
        document.getElementById('pago-modo').value = modo;
        document.getElementById('pago-id').value   = id;
        document.getElementById('pago-title').textContent = modo === 'cxc' ? 'Cobrar de cliente' : 'Pagar a proveedor';
        document.getElementById('pago-info').innerHTML = `<strong>${esc(nombre)}</strong> — Saldo pendiente: <span class="font-bold">${fmt(saldo, moneda)}</span>`;
        const form = document.getElementById('form-pago');
        form.querySelector('[name=monto]').value = saldo.toFixed(2);
        form.querySelector('[name=fecha]').value = todayStr();
        form.querySelector('[name=referencia]').value = '';
        openModal('modal-pago');
    }

    // ──── Submit: transacción ──────────────────────────────────
    document.getElementById('form-transaccion').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        const payload = Object.fromEntries(fd.entries());
        if (!payload.categoria_id) delete payload.categoria_id;
        const impId = payload.impuesto_id;
        delete payload.impuesto_id;
        if (impId) payload.impuestos = [{ impuesto_id: parseInt(impId) }];
        try {
            await api.post('/api/finance/transacciones', payload);
            toast('Transacción registrada');
            closeModal('modal-transaccion');
            e.target.reset();
            loadStats(); loadTransacciones();
        } catch (err) {
            toast(err.response?.data?.error || err.response?.data?.message || 'Error', 'error');
        }
    });

    document.getElementById('btn-nueva-transaccion').addEventListener('click', () => {
        document.querySelector('#form-transaccion [name=fecha]').value = todayStr();
        openModal('modal-transaccion');
    });

    // ──── Submit: transferencia ────────────────────────────────
    document.getElementById('form-transferir').addEventListener('submit', async (e) => {
        e.preventDefault();
        const payload = Object.fromEntries(new FormData(e.target).entries());
        try {
            await api.post('/api/finance/cuentas/transferir', payload);
            toast('Transferencia registrada');
            closeModal('modal-transferir');
            e.target.reset();
            loadStats(); loadTransacciones(); await loadCatalogos();
        } catch (err) {
            toast(err.response?.data?.error || 'Error', 'error');
        }
    });

    document.getElementById('btn-transferir').addEventListener('click', () => {
        document.querySelector('#form-transferir [name=fecha]').value = todayStr();
        openModal('modal-transferir');
    });

    // ──── Submit: pago ─────────────────────────────────────────
    document.getElementById('form-pago').addEventListener('submit', async (e) => {
        e.preventDefault();
        const modo = document.getElementById('pago-modo').value;
        const id   = document.getElementById('pago-id').value;
        const payload = Object.fromEntries(new FormData(e.target).entries());
        delete payload._modo; delete payload._id;
        try {
            await api.post(`/api/finance/${modo}/${id}/pagar`, payload);
            toast('Pago registrado');
            closeModal('modal-pago');
            loadStats();
            modo === 'cxc' ? loadCxC() : loadCxP();
            await loadCatalogos();
        } catch (err) {
            toast(err.response?.data?.error || 'Error', 'error');
        }
    });

    // ──── IA: Asesor financiero ────────────────────────────────
    let currentAiTipo = null;

    function renderMarkdown(md) {
        const escMd = (s) => s.replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));
        let html = escMd(md);
        html = html.replace(/^### (.+)$/gm, '<h3 class="text-base font-bold text-slate-800 mt-4 mb-2">$1</h3>');
        html = html.replace(/^## (.+)$/gm, '<h2 class="text-lg font-bold text-violet-700 mt-5 mb-2 border-b border-violet-100 pb-1">$1</h2>');
        html = html.replace(/^# (.+)$/gm, '<h1 class="text-xl font-bold text-slate-900 mt-5 mb-3">$1</h1>');
        html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/\*(.+?)\*/g, '<em>$1</em>');
        html = html.replace(/^[\-\*] (.+)$/gm, '<li class="ml-5 list-disc">$1</li>');
        html = html.replace(/^\d+\. (.+)$/gm, '<li class="ml-5 list-decimal">$1</li>');
        html = html.split(/\n{2,}/).map(p => {
            if (/^<(h\d|ul|ol|li|p)/.test(p.trim())) return p;
            return `<p class="my-2 leading-relaxed">${p.replace(/\n/g, '<br>')}</p>`;
        }).join('\n');
        return html;
    }

    async function runAi(tipo, refresh = false) {
        currentAiTipo = tipo;
        document.getElementById('ai-empty').classList.add('hidden');
        document.getElementById('ai-content').classList.add('hidden');
        document.getElementById('ai-loading').classList.remove('hidden');
        document.getElementById('ai-meta').classList.add('hidden');

        // Resaltar botón activo
        document.querySelectorAll('.ai-btn').forEach(b => {
            const active = b.dataset.ai === tipo;
            b.classList.toggle('bg-violet-600', active);
            b.classList.toggle('text-white', active);
            b.classList.toggle('hover:bg-violet-700', active);
            b.classList.toggle('bg-white', !active);
            b.classList.toggle('hover:bg-slate-50', !active);
            b.classList.toggle('border', !active);
            b.classList.toggle('border-slate-200', !active);
            b.classList.toggle('text-slate-700', !active);
            b.disabled = true;
        });

        try {
            const days = document.getElementById('fin-period').value;
            const params = { refresh: refresh ? 1 : 0 };
            if (tipo === 'informe')  params.days = days;
            if (tipo === 'forecast') params.dias = 30;

            const { data } = await api.get(`/api/finance/ai/${tipo}`, params);

            document.getElementById('ai-content').innerHTML = renderMarkdown(data.content);
            document.getElementById('ai-content').classList.remove('hidden');
            document.getElementById('ai-meta').textContent = `Generado ${new Date(data.generated_at).toLocaleString('es-MX')} · ${data.cached ? 'caché' : 'nuevo'}`;
            document.getElementById('ai-meta').classList.remove('hidden');
            document.getElementById('btn-ai-refresh').classList.remove('hidden');
        } catch (err) {
            document.getElementById('ai-empty').classList.remove('hidden');
            const msg = err.response?.data?.message || 'Error al generar análisis';
            const hint = err.response?.data?.hint ? ` (${err.response.data.hint})` : '';
            toast(msg + hint, 'error');
        } finally {
            document.getElementById('ai-loading').classList.add('hidden');
            document.querySelectorAll('.ai-btn').forEach(b => b.disabled = false);
        }
    }

    document.querySelectorAll('.ai-btn').forEach(btn => {
        btn.addEventListener('click', () => runAi(btn.dataset.ai, false));
    });
    document.getElementById('btn-ai-refresh').addEventListener('click', () => {
        if (currentAiTipo) runAi(currentAiTipo, true);
    });

    // ──── Compras ──────────────────────────────────────────────
    async function loadCompras() {
        try {
            const { data } = await api.get('/api/finance/compras', { per_page: 50 });
            const rows = data.data || [];
            const tbody = document.getElementById('tbody-compras');
            if (!rows.length) {
                tbody.innerHTML = `<tr><td colspan="7" class="px-5 py-6 text-center text-slate-400">Sin compras registradas</td></tr>`;
                return;
            }
            tbody.innerHTML = rows.map(c => {
                const badge = {
                    borrador:  'bg-slate-100 text-slate-700',
                    recibida:  'bg-indigo-100 text-indigo-700',
                    pagada:    'bg-emerald-100 text-emerald-700',
                    cancelada: 'bg-rose-100 text-rose-700',
                }[c.estado] || 'bg-slate-100 text-slate-700';

                let acciones = '';
                if (c.estado === 'borrador') {
                    acciones = `
                        <button data-recibir="${c.id}" data-prov="${esc(c.proveedor?.nombre || '')}" data-total="${c.total}" data-forma="${c.forma_pago}" data-moneda="${esc(c.moneda?.codigo || '')}"
                                class="text-xs text-emerald-600 hover:text-emerald-700 font-medium mr-3">Recibir</button>
                        <button data-del-compra="${c.id}" class="text-xs text-rose-500 hover:text-rose-600">Eliminar</button>`;
                }

                return `
                <tr class="border-b border-slate-100 hover:bg-slate-50">
                    <td class="px-5 py-3 font-mono text-xs">#${c.id}</td>
                    <td class="px-5 py-3 text-slate-500">${fmtDate(c.fecha)}</td>
                    <td class="px-5 py-3">${esc(c.proveedor?.nombre || '—')}</td>
                    <td class="px-5 py-3 capitalize text-slate-600">${esc(c.forma_pago)}</td>
                    <td class="px-5 py-3 text-right font-semibold">${fmt(c.total, c.moneda?.codigo)}</td>
                    <td class="px-5 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium ${badge}">${c.estado}</span></td>
                    <td class="px-5 py-3 text-right">${acciones}</td>
                </tr>`;
            }).join('');

            tbody.querySelectorAll('[data-recibir]').forEach(btn => {
                btn.addEventListener('click', () => abrirRecibir(btn));
            });
            tbody.querySelectorAll('[data-del-compra]').forEach(btn => {
                btn.addEventListener('click', async () => {
                    if (!confirm('¿Eliminar este borrador de compra?')) return;
                    try {
                        await api.delete(`/api/finance/compras/${btn.dataset.delCompra}`);
                        toast('Compra eliminada');
                        loadCompras();
                    } catch (err) {
                        toast(err.response?.data?.error || 'Error', 'error');
                    }
                });
            });
        } catch (err) {
            console.error(err);
            toast('Error al cargar compras', 'error');
        }
    }

    // ──── Modal: nueva compra (items dinámicos) ────────────────
    function addCompraItem() {
        const cont = document.getElementById('co-items');
        const idx = cont.children.length;
        const row = document.createElement('div');
        row.className = 'grid grid-cols-12 gap-2 items-end bg-slate-50 rounded-xl p-2';
        row.innerHTML = `
            <div class="col-span-5">
                <label class="text-xs text-slate-500">Producto</label>
                <select class="co-prod w-full rounded-lg border-slate-200 bg-white text-xs" required>
                    <option value="">— Seleccionar —</option>
                    ${productos.map(p => `<option value="${p.id}" data-cost="${p.cost}">${esc(p.sku)} · ${esc(p.name)}</option>`).join('')}
                </select>
            </div>
            <div class="col-span-2">
                <label class="text-xs text-slate-500">Cantidad</label>
                <input type="number" min="0.0001" step="0.0001" class="co-qty w-full rounded-lg border-slate-200 bg-white text-xs" required>
            </div>
            <div class="col-span-2">
                <label class="text-xs text-slate-500">Costo</label>
                <input type="number" min="0" step="0.01" class="co-cost w-full rounded-lg border-slate-200 bg-white text-xs" required>
            </div>
            <div class="col-span-2">
                <label class="text-xs text-slate-500">Impuesto</label>
                <select class="co-imp w-full rounded-lg border-slate-200 bg-white text-xs">
                    <option value="">—</option>
                    ${impuestos.filter(i => i.aplicacion === 'traslado').map(i => `<option value="${i.id}" data-tasa="${i.tasa}">${esc(i.codigo)}</option>`).join('')}
                </select>
            </div>
            <div class="col-span-1 text-right">
                <button type="button" class="co-rem text-rose-500 hover:text-rose-700 text-lg leading-none">×</button>
            </div>`;
        cont.appendChild(row);

        // Auto-fill costo cuando se selecciona producto
        row.querySelector('.co-prod').addEventListener('change', (e) => {
            const opt = e.target.selectedOptions[0];
            if (opt && opt.dataset.cost) {
                row.querySelector('.co-cost').value = parseFloat(opt.dataset.cost).toFixed(2);
                recalcTotal();
            }
        });
        row.querySelectorAll('.co-qty, .co-cost, .co-imp').forEach(el => el.addEventListener('input', recalcTotal));
        row.querySelector('.co-rem').addEventListener('click', () => { row.remove(); recalcTotal(); });
        recalcTotal();
    }

    function recalcTotal() {
        let total = 0;
        document.querySelectorAll('#co-items > div').forEach(row => {
            const qty  = parseFloat(row.querySelector('.co-qty')?.value || 0);
            const cost = parseFloat(row.querySelector('.co-cost')?.value || 0);
            const imp  = row.querySelector('.co-imp');
            const tasa = imp?.selectedOptions[0]?.dataset?.tasa || 0;
            const sub  = qty * cost;
            const impt = sub * (parseFloat(tasa) / 100);
            total += sub + impt;
        });
        document.getElementById('co-total').textContent = fmt(total);
    }

    document.getElementById('co-add-item').addEventListener('click', addCompraItem);

    document.getElementById('co-forma-pago').addEventListener('change', (e) => {
        document.getElementById('co-venc-wrap').classList.toggle('hidden', e.target.value !== 'credito');
    });

    document.getElementById('btn-nueva-compra').addEventListener('click', () => {
        document.getElementById('form-compra').reset();
        document.getElementById('co-items').innerHTML = '';
        document.querySelector('#form-compra [name=fecha]').value = todayStr();
        document.querySelector('#form-compra [name=tipo_cambio]').value = '1';
        document.getElementById('co-venc-wrap').classList.add('hidden');
        addCompraItem();
        openModal('modal-compra');
    });

    document.getElementById('form-compra').addEventListener('submit', async (e) => {
        e.preventDefault();
        const items = [];
        document.querySelectorAll('#co-items > div').forEach(row => {
            const product_id = row.querySelector('.co-prod').value;
            const cantidad   = parseFloat(row.querySelector('.co-qty').value);
            const costo_unit = parseFloat(row.querySelector('.co-cost').value);
            const impuesto_id = row.querySelector('.co-imp').value || null;
            if (product_id && cantidad > 0 && costo_unit >= 0) {
                items.push({ product_id: parseInt(product_id), cantidad, costo_unit, impuesto_id });
            }
        });
        if (!items.length) {
            toast('Agrega al menos un producto válido', 'warn');
            return;
        }
        const fd = new FormData(e.target);
        const payload = Object.fromEntries(fd.entries());
        payload.items = items;
        if (!payload.fecha_vencimiento) delete payload.fecha_vencimiento;

        try {
            await api.post('/api/finance/compras', payload);
            toast('Compra creada como borrador');
            closeModal('modal-compra');
            loadCompras();
        } catch (err) {
            toast(err.response?.data?.error || err.response?.data?.message || 'Error', 'error');
        }
    });

    // ──── Recibir compra ───────────────────────────────────────
    function abrirRecibir(btn) {
        const id     = btn.dataset.recibir;
        const prov   = btn.dataset.prov;
        const total  = parseFloat(btn.dataset.total);
        const forma  = btn.dataset.forma;
        const moneda = btn.dataset.moneda;
        document.getElementById('recibir-id').value = id;
        document.getElementById('recibir-info').innerHTML = `
            <strong>Compra #${id}</strong> a <strong>${esc(prov)}</strong><br>
            Total: <strong>${fmt(total, moneda)}</strong> · Forma de pago: <strong>${esc(forma)}</strong><br>
            ${forma === 'contado'
                ? 'Al confirmar se generará la transacción de egreso y entrará el stock.'
                : 'Al confirmar se generará la CxP y entrará el stock.'}`;
        document.getElementById('recibir-cuenta-wrap').classList.toggle('hidden', forma !== 'contado');
        document.getElementById('recibir-cuenta').required = (forma === 'contado');
        openModal('modal-recibir');
    }

    document.getElementById('form-recibir').addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('recibir-id').value;
        const cuentaId = document.getElementById('recibir-cuenta').value;
        const payload = cuentaId ? { cuenta_id: cuentaId } : {};
        try {
            await api.post(`/api/finance/compras/${id}/recibir`, payload);
            toast('Recepción confirmada');
            closeModal('modal-recibir');
            loadCompras(); loadStats(); await loadCatalogos();
        } catch (err) {
            toast(err.response?.data?.error || 'Error', 'error');
        }
    });

    // ──── Proveedores ──────────────────────────────────────────
    async function loadProveedores() {
        try {
            const { data } = await api.get('/api/finance/proveedores');
            proveedores = data;
            const tbody = document.getElementById('tbody-proveedores');
            if (!data.length) {
                tbody.innerHTML = `<tr><td colspan="7" class="px-5 py-6 text-center text-slate-400">Sin proveedores</td></tr>`;
                return;
            }
            tbody.innerHTML = data.map(p => `
                <tr class="border-b border-slate-100 hover:bg-slate-50">
                    <td class="px-5 py-3 font-medium">${esc(p.nombre)}</td>
                    <td class="px-5 py-3 font-mono text-xs">${esc(p.rfc || '—')}</td>
                    <td class="px-5 py-3">${esc(p.contacto || '—')}</td>
                    <td class="px-5 py-3">${esc(p.telefono || '—')}</td>
                    <td class="px-5 py-3 text-slate-600">${esc(p.email || '—')}</td>
                    <td class="px-5 py-3 text-center">${p.dias_credito || 0}</td>
                    <td class="px-5 py-3 text-right">
                        <button data-edit-prov="${p.id}" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium mr-3">Editar</button>
                        <button data-del-prov="${p.id}" class="text-xs text-rose-500 hover:text-rose-600">Eliminar</button>
                    </td>
                </tr>`).join('');

            tbody.querySelectorAll('[data-edit-prov]').forEach(btn => {
                btn.addEventListener('click', () => abrirProveedor(proveedores.find(p => p.id == btn.dataset.editProv)));
            });
            tbody.querySelectorAll('[data-del-prov]').forEach(btn => {
                btn.addEventListener('click', async () => {
                    if (!confirm('¿Eliminar este proveedor?')) return;
                    try {
                        await api.delete(`/api/finance/proveedores/${btn.dataset.delProv}`);
                        toast('Proveedor eliminado');
                        loadProveedores(); await loadCatalogos();
                    } catch (err) {
                        toast(err.response?.data?.error || 'Error', 'error');
                    }
                });
            });
        } catch (err) {
            console.error(err);
            toast('Error al cargar proveedores', 'error');
        }
    }

    function abrirProveedor(p = null) {
        const form = document.getElementById('form-proveedor');
        form.reset();
        document.getElementById('prov-id').value = p?.id || '';
        document.getElementById('prov-title').textContent = p ? 'Editar proveedor' : 'Nuevo proveedor';
        if (p) {
            form.querySelector('[name=nombre]').value      = p.nombre || '';
            form.querySelector('[name=rfc]').value         = p.rfc || '';
            form.querySelector('[name=dias_credito]').value = p.dias_credito || 0;
            form.querySelector('[name=contacto]').value    = p.contacto || '';
            form.querySelector('[name=telefono]').value    = p.telefono || '';
            form.querySelector('[name=email]').value       = p.email || '';
            form.querySelector('[name=direccion]').value   = p.direccion || '';
            form.querySelector('[name=notas]').value       = p.notas || '';
            form.querySelector('[name=moneda_id]').value   = p.moneda_id || '';
        }
        openModal('modal-proveedor');
    }

    document.getElementById('btn-nuevo-proveedor').addEventListener('click', () => abrirProveedor(null));

    document.getElementById('form-proveedor').addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('prov-id').value;
        const payload = Object.fromEntries(new FormData(e.target).entries());
        delete payload._id;
        if (!payload.moneda_id) delete payload.moneda_id;
        try {
            if (id) {
                await api.put(`/api/finance/proveedores/${id}`, payload);
                toast('Proveedor actualizado');
            } else {
                await api.post('/api/finance/proveedores', payload);
                toast('Proveedor creado');
            }
            closeModal('modal-proveedor');
            loadProveedores(); await loadCatalogos();
        } catch (err) {
            toast(err.response?.data?.message || err.response?.data?.error || 'Error', 'error');
        }
    });

    // ──── Presupuestos ─────────────────────────────────────────
    function initPresupuestoFilters() {
        const sel = document.getElementById('pre-anio');
        const now = new Date();
        const year = now.getFullYear();
        sel.innerHTML = '';
        for (let y = year - 2; y <= year + 1; y++) {
            sel.innerHTML += `<option value="${y}"${y === year ? ' selected' : ''}>${y}</option>`;
        }
        document.getElementById('pre-mes').value = String(now.getMonth() + 1);
    }

    async function loadPresupuestos() {
        const anio = document.getElementById('pre-anio').value;
        const mes  = document.getElementById('pre-mes').value;
        const grid = document.getElementById('presupuestos-grid');
        grid.innerHTML = '<div class="text-slate-400 text-sm col-span-full text-center py-6">Cargando…</div>';
        try {
            const { data } = await api.get('/api/finance/presupuestos', { anio, mes });
            if (!data.length) {
                grid.innerHTML = `<div class="text-slate-400 text-sm col-span-full text-center py-10">Sin presupuestos para este periodo. Crea uno para empezar a monitorear.</div>`;
                return;
            }
            grid.innerHTML = data.map(p => {
                const pct = Math.min(100, Math.max(0, p.porcentaje));
                let barColor = 'bg-emerald-500';
                let textColor = 'text-emerald-700';
                let bg = 'bg-emerald-50 border-emerald-100';
                if (p.excedido) {
                    barColor = 'bg-rose-500'; textColor = 'text-rose-700'; bg = 'bg-rose-50 border-rose-100';
                } else if (p.en_alerta) {
                    barColor = 'bg-amber-500'; textColor = 'text-amber-700'; bg = 'bg-amber-50 border-amber-100';
                }
                const monedaCod = p.moneda?.codigo;
                return `
                <div class="bg-white border ${bg} rounded-2xl p-5 shadow-sm">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-3 h-3 rounded-full flex-shrink-0" style="background:${esc(p.categoria?.color || '#94a3b8')}"></span>
                            <h4 class="text-base font-bold text-slate-800 truncate">${esc(p.categoria?.nombre || '—')}</h4>
                        </div>
                        <button data-del-pp="${p.id}" class="text-slate-300 hover:text-rose-500 text-xs flex-shrink-0">×</button>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Usado</span>
                            <span class="font-semibold ${textColor}">${fmt(p.monto_usado, monedaCod)}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Límite</span>
                            <span class="font-medium text-slate-700">${fmt(p.monto_limite, monedaCod)}</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div class="${barColor} h-2 transition-all" style="width:${pct}%"></div>
                        </div>
                        <div class="flex justify-between items-center text-xs">
                            <span class="${textColor} font-bold">${p.porcentaje}%</span>
                            <span class="text-slate-400">${p.excedido ? `Excedido por ${fmt(Math.abs(p.saldo), monedaCod)}` : `Restan ${fmt(Math.max(0, p.saldo), monedaCod)}`}</span>
                        </div>
                    </div>
                </div>`;
            }).join('');

            grid.querySelectorAll('[data-del-pp]').forEach(btn => {
                btn.addEventListener('click', async () => {
                    if (!confirm('¿Eliminar este presupuesto?')) return;
                    try {
                        await api.delete(`/api/finance/presupuestos/${btn.dataset.delPp}`);
                        toast('Presupuesto eliminado');
                        loadPresupuestos();
                    } catch (err) {
                        toast(err.response?.data?.error || 'Error', 'error');
                    }
                });
            });
        } catch (err) {
            console.error(err);
            toast('Error al cargar presupuestos', 'error');
        }
    }

    document.getElementById('btn-nuevo-presupuesto').addEventListener('click', () => {
        const form = document.getElementById('form-presupuesto');
        form.reset();
        const now = new Date();
        form.querySelector('[name=anio]').value = now.getFullYear();
        form.querySelector('[name=mes]').value  = now.getMonth() + 1;
        form.querySelector('[name=alerta_pct]').value = 80;

        // Solo egresos
        fillSelect('pp-categoria', categorias.filter(c => c.tipo === 'egreso' && c.activa).map(c => ({ value: c.id, label: c.nombre })));
        fillSelect('pp-moneda',    monedas.filter(m => m.activa).map(m => ({ value: m.id, label: m.codigo, selected: m.es_base })));
        openModal('modal-presupuesto');
    });

    document.getElementById('form-presupuesto').addEventListener('submit', async (e) => {
        e.preventDefault();
        const payload = Object.fromEntries(new FormData(e.target).entries());

        // Validaciones cliente extra (defensa en profundidad — el server las repite)
        const anio = parseInt(payload.anio);
        const mes  = parseInt(payload.mes);
        const monto = parseFloat(payload.monto_limite);
        const pct  = parseInt(payload.alerta_pct || 80);
        if (!Number.isInteger(anio) || anio < 2020 || anio > 2100) return toast('Año inválido', 'warn');
        if (!Number.isInteger(mes) || mes < 1 || mes > 12)        return toast('Mes inválido', 'warn');
        if (!(monto > 0))                                          return toast('Monto inválido', 'warn');
        if (pct < 1 || pct > 100)                                  return toast('% alerta inválido', 'warn');

        try {
            await api.post('/api/finance/presupuestos', payload);
            toast('Presupuesto guardado');
            closeModal('modal-presupuesto');
            // Sincronizar filtros visibles con lo recién creado
            document.getElementById('pre-anio').value = payload.anio;
            document.getElementById('pre-mes').value  = payload.mes;
            loadPresupuestos();
        } catch (err) {
            toast(err.response?.data?.message || err.response?.data?.error || 'Error', 'error');
        }
    });

    document.getElementById('pre-anio').addEventListener('change', loadPresupuestos);
    document.getElementById('pre-mes').addEventListener('change', loadPresupuestos);

    // ──── Devoluciones ─────────────────────────────────────────
    async function loadDevCompras() {
        const tbody = document.getElementById('tbody-dev-compras');
        if (!tbody) return;
        try {
            const { data } = await api.get('/api/finance/devoluciones-compra');
            const rows = data.data ?? data;
            if (!rows.length) { tbody.innerHTML = '<tr><td colspan="4" class="px-5 py-5 text-center text-slate-400">Sin devoluciones.</td></tr>'; return; }
            tbody.innerHTML = rows.map(d => {
                const badge = d.estado === 'aplicada' ? 'bg-emerald-100 text-emerald-700' : (d.estado === 'cancelada' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700');
                return `<tr class="hover:bg-slate-50 dark:hover:bg-zinc-800/50">
                    <td class="px-4 py-3 text-sm font-medium">${esc(d.proveedor?.nombre ?? '—')}</td>
                    <td class="px-4 py-3 text-sm text-slate-500">${fmtDate(d.fecha)}</td>
                    <td class="px-4 py-3 text-right font-mono text-sm font-semibold">${fmt(d.total)}</td>
                    <td class="px-4 py-3"><span class="text-xs font-semibold px-2 py-0.5 rounded-full ${badge}">${d.estado}</span></td>
                </tr>`;
            }).join('');
        } catch { tbody.innerHTML = '<tr><td colspan="4" class="px-5 py-5 text-center text-rose-400">Error al cargar.</td></tr>'; }
    }

    async function loadDevVentas() {
        const tbody = document.getElementById('tbody-dev-ventas');
        if (!tbody) return;
        try {
            const { data } = await api.get('/api/finance/devoluciones-venta');
            const rows = data.data ?? data;
            if (!rows.length) { tbody.innerHTML = '<tr><td colspan="4" class="px-5 py-5 text-center text-slate-400">Sin devoluciones.</td></tr>'; return; }
            tbody.innerHTML = rows.map(d => {
                const badge = d.estado === 'aplicada' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700';
                return `<tr class="hover:bg-slate-50 dark:hover:bg-zinc-800/50">
                    <td class="px-4 py-3 text-sm text-slate-500">#${d.venta_id}</td>
                    <td class="px-4 py-3 text-sm text-slate-500">${fmtDate(d.fecha)}</td>
                    <td class="px-4 py-3 text-right font-mono text-sm font-semibold">${fmt(d.total)}</td>
                    <td class="px-4 py-3"><span class="text-xs font-semibold px-2 py-0.5 rounded-full ${badge}">${d.estado}</span></td>
                </tr>`;
            }).join('');
        } catch { tbody.innerHTML = '<tr><td colspan="4" class="px-5 py-5 text-center text-rose-400">Error al cargar.</td></tr>'; }
    }

    // ──── Conciliación ─────────────────────────────────────────
    async function loadConciliaciones() {
        const tbody = document.getElementById('tbody-conciliaciones');
        if (!tbody) return;
        try {
            const { data } = await api.get('/api/finance/conciliaciones');
            const rows = data.data ?? data;
            if (!rows.length) { tbody.innerHTML = '<tr><td colspan="6" class="px-5 py-5 text-center text-slate-400">Sin conciliaciones.</td></tr>'; return; }
            tbody.innerHTML = rows.map(c => {
                const badge = c.estado === 'cerrada' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700';
                const difColor = Math.abs(c.diferencia) < 0.01 ? 'text-emerald-600' : 'text-rose-600';
                return `<tr class="hover:bg-slate-50 dark:hover:bg-zinc-800/50">
                    <td class="px-5 py-3 text-sm font-medium">${esc(c.cuenta?.nombre ?? '—')}</td>
                    <td class="px-5 py-3 text-sm text-slate-500">${fmtDate(c.fecha_inicio)} – ${fmtDate(c.fecha_fin)}</td>
                    <td class="px-5 py-3 text-right font-mono text-sm">${fmt(c.saldo_banco_statement)}</td>
                    <td class="px-5 py-3 text-right font-mono text-sm">${fmt(c.saldo_sistema)}</td>
                    <td class="px-5 py-3 text-right font-mono text-sm font-semibold ${difColor}">${fmt(c.diferencia)}</td>
                    <td class="px-5 py-3"><span class="text-xs font-semibold px-2 py-0.5 rounded-full ${badge}">${c.estado}</span></td>
                </tr>`;
            }).join('');
        } catch { tbody.innerHTML = '<tr><td colspan="6" class="px-5 py-5 text-center text-rose-400">Error al cargar.</td></tr>'; }
    }

    document.getElementById('btn-nueva-conciliacion')?.addEventListener('click', async () => {
        const cuentaOpts = cuentas.map(c => `${c.id}: ${c.nombre}`).join('\n');
        const cuentaId = parseInt(prompt(`Selecciona cuenta (ID):\n${cuentaOpts}`));
        if (!cuentaId) return;
        const desde = prompt('Fecha inicio (YYYY-MM-DD):');
        const hasta = prompt('Fecha fin (YYYY-MM-DD):');
        const saldo = parseFloat(prompt('Saldo del estado de cuenta bancario:'));
        if (!desde || !hasta || isNaN(saldo)) { toast('Datos incompletos', 'warn'); return; }
        try {
            await api.post('/api/finance/conciliaciones', { cuenta_id: cuentaId, fecha_inicio: desde, fecha_fin: hasta, saldo_banco_statement: saldo });
            toast('Conciliación creada');
            loadConciliaciones();
        } catch (err) {
            toast(err.response?.data?.message || 'Error', 'error');
        }
    });

    // ──── Reportes P&G ─────────────────────────────────────────
    document.getElementById('btn-rep-pyg')?.addEventListener('click', async () => {
        const desde = document.getElementById('rep-desde')?.value;
        const hasta = document.getElementById('rep-hasta')?.value;
        if (!desde || !hasta) { toast('Selecciona el período', 'warn'); return; }
        try {
            const { data } = await api.get(`/api/finance/reportes/pyg?desde=${desde}&hasta=${hasta}`);
            document.getElementById('rep-pyg-ingresos').textContent = fmt(data.total_ingresos);
            document.getElementById('rep-pyg-egresos').textContent  = fmt(data.total_egresos);
            document.getElementById('rep-pyg-utilidad').textContent = fmt(data.utilidad_bruta);
            document.getElementById('rep-pyg-margen').textContent   = `Margen ${data.margen_pct}%`;
            document.getElementById('rep-pyg-container').classList.remove('hidden');
        } catch (err) { toast('Error al cargar P&G', 'error'); }
    });

    document.getElementById('btn-rep-aging-cxc')?.addEventListener('click', async () => {
        const al = new Date().toISOString().split('T')[0];
        try {
            const { data } = await api.get(`/api/finance/reportes/aging-cxc?al=${al}`);
            const container = document.getElementById('rep-aging-cxc');
            if (!container) return;
            container.innerHTML = `<p class="text-xs text-slate-400 mb-3">Total: <strong class="text-slate-700 dark:text-zinc-200">${fmt(data.total_saldo)}</strong></p>` +
                data.buckets.map(b => `
                    <div class="flex justify-between items-center py-1.5 border-b border-slate-100 dark:border-zinc-800 last:border-0">
                        <span class="text-sm text-slate-600 dark:text-zinc-300">${esc(b.label)}</span>
                        <div class="text-right">
                            <span class="font-mono text-sm font-semibold">${fmt(b.total)}</span>
                            <span class="text-xs text-slate-400 ml-1">(${b.items?.length ?? 0})</span>
                        </div>
                    </div>`).join('');
        } catch { toast('Error al cargar aging CxC', 'error'); }
    });

    document.getElementById('btn-rep-aging-cxp')?.addEventListener('click', async () => {
        const al = new Date().toISOString().split('T')[0];
        try {
            const { data } = await api.get(`/api/finance/reportes/aging-cxp?al=${al}`);
            const container = document.getElementById('rep-aging-cxp');
            if (!container) return;
            container.innerHTML = `<p class="text-xs text-slate-400 mb-3">Total: <strong class="text-slate-700 dark:text-zinc-200">${fmt(data.total_saldo)}</strong></p>` +
                data.buckets.map(b => `
                    <div class="flex justify-between items-center py-1.5 border-b border-slate-100 dark:border-zinc-800 last:border-0">
                        <span class="text-sm text-slate-600 dark:text-zinc-300">${esc(b.label)}</span>
                        <div class="text-right">
                            <span class="font-mono text-sm font-semibold">${fmt(b.total)}</span>
                            <span class="text-xs text-slate-400 ml-1">(${b.items?.length ?? 0})</span>
                        </div>
                    </div>`).join('');
        } catch { toast('Error al cargar aging CxP', 'error'); }
    });

    // ──── Activos Fijos ────────────────────────────────────────
    async function loadActivosFijos() {
        const tbody = document.getElementById('tbody-activos');
        if (!tbody) return;
        try {
            const { data } = await api.get('/api/finance/activos-fijos');
            const rows = data.data ?? data;
            if (!rows.length) { tbody.innerHTML = '<tr><td colspan="6" class="px-5 py-5 text-center text-slate-400">Sin activos fijos.</td></tr>'; return; }
            tbody.innerHTML = rows.map(a => {
                const estadoBadge = { activo: 'bg-emerald-100 text-emerald-700', vendido: 'bg-slate-100 text-slate-600', dado_de_baja: 'bg-rose-100 text-rose-700' }[a.estado] ?? '';
                const valorLibro = a.costo_adquisicion - (a.depreciaciones?.reduce((s, d) => s + Number(d.depreciacion_mensual), 0) ?? 0);
                return `<tr class="hover:bg-slate-50 dark:hover:bg-zinc-800/50">
                    <td class="px-5 py-3 text-sm font-medium">${esc(a.nombre)}</td>
                    <td class="px-5 py-3 text-sm text-slate-500">${esc(a.categoria ?? '—')}</td>
                    <td class="px-5 py-3 text-right font-mono text-sm">${fmt(a.costo_adquisicion)}</td>
                    <td class="px-5 py-3 text-right font-mono text-sm font-semibold">${fmt(valorLibro)}</td>
                    <td class="px-5 py-3 text-sm text-slate-500">${a.metodo_depreciacion}</td>
                    <td class="px-5 py-3"><span class="text-xs font-semibold px-2 py-0.5 rounded-full ${estadoBadge}">${a.estado}</span></td>
                </tr>`;
            }).join('');
        } catch { tbody.innerHTML = '<tr><td colspan="6" class="px-5 py-5 text-center text-rose-400">Error al cargar.</td></tr>'; }
    }

    // ──── Tabs: registrar nuevos ───────────────────────────────
    // Inyectar en el sistema de tabs existente
    const extraTabHandlers = {
        devoluciones: () => { loadDevCompras(); loadDevVentas(); },
        conciliacion: loadConciliaciones,
        reportes: () => {
            const hoy = new Date().toISOString().split('T')[0];
            const hace30 = new Date(Date.now() - 30 * 86400000).toISOString().split('T')[0];
            const desde = document.getElementById('rep-desde');
            const hasta = document.getElementById('rep-hasta');
            if (desde && !desde.value) desde.value = hace30;
            if (hasta && !hasta.value) hasta.value = hoy;
        },
        activos: loadActivosFijos,
    };

    // Parchear el listener de tabs existente para los tabs nuevos
    document.querySelectorAll('.tab-btn[data-tab="devoluciones"], .tab-btn[data-tab="conciliacion"], .tab-btn[data-tab="reportes"], .tab-btn[data-tab="activos"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const tab = btn.dataset.tab;
            if (extraTabHandlers[tab]) extraTabHandlers[tab]();
        });
    });

    // ──── Eventos globales ─────────────────────────────────────
    document.getElementById('fin-period').addEventListener('change', loadStats);
    initPresupuestoFilters();

    // Init
    (async () => {
        await loadCatalogos();
        await loadStats();
        await loadTransacciones();
    })();
});
