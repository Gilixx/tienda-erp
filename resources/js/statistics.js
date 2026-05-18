import api from './api';
import Chart from 'chart.js/auto';

document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('stats-app');
    if (!app) return;

    const periodSelect      = document.getElementById('period-select');
    const generateBtn       = document.getElementById('generate-report-btn');
    const generateBtnText   = document.getElementById('generate-btn-text');
    const refreshBtn        = document.getElementById('refresh-report-btn');
    const reportLoading     = document.getElementById('report-loading');
    const reportContent     = document.getElementById('report-content');
    const reportEmpty       = document.getElementById('report-empty');
    const reportMeta        = document.getElementById('report-meta');
    const staleTbody        = document.getElementById('stale-tbody');

    let chartTop = null;
    let chartDaily = null;

    const fmtMoney = (n) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(n || 0);
    const fmtNum   = (n) => new Intl.NumberFormat('es-MX').format(n || 0);

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function showToast(msg, type = 'success') {
        const colors = type === 'error' ? 'bg-rose-600' : 'bg-emerald-600';
        const el = document.createElement('div');
        el.className = `${colors} text-white px-4 py-2.5 rounded-xl shadow-lg text-sm pointer-events-auto animate-fade-in`;
        el.textContent = msg;
        document.getElementById('toast-container').appendChild(el);
        setTimeout(() => el.remove(), 4000);
    }

    // Markdown ultra-simple (sólo lo necesario: headers, listas, bold, párrafos)
    function renderMarkdown(md) {
        const esc = (s) => s.replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));
        let html = esc(md);
        html = html.replace(/^### (.+)$/gm, '<h3 class="text-base font-bold text-slate-800 mt-5 mb-2">$1</h3>');
        html = html.replace(/^## (.+)$/gm, '<h2 class="text-lg font-bold text-violet-700 mt-6 mb-3 border-b border-violet-100 pb-1">$1</h2>');
        html = html.replace(/^# (.+)$/gm, '<h1 class="text-xl font-bold text-slate-900 mt-6 mb-3">$1</h1>');
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

    async function loadStats() {
        try {
            const days = periodSelect.value;
            const { data } = await api.get('/api/inventory/stats', { days });

            // KPIs
            document.getElementById('kpi-ventas').textContent = fmtNum(data.summary.total_ventas);
            document.getElementById('kpi-ingresos').textContent = fmtMoney(data.summary.ingresos_totales);
            document.getElementById('kpi-ticket').textContent = fmtMoney(data.summary.ticket_promedio);
            document.getElementById('kpi-unidades').textContent = fmtNum(data.summary.unidades_vendidas);

            renderTopChart(data.top_products);
            renderDailyChart(data.daily_sales);
            renderStaleTable(data.stale_products);
        } catch (err) {
            showToast('Error al cargar estadísticas', 'error');
            console.error(err);
        }
    }

    function renderTopChart(items) {
        const ctx = document.getElementById('chart-top');
        if (chartTop) chartTop.destroy();
        chartTop = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: items.map(i => i.name.length > 20 ? i.name.slice(0, 20) + '…' : i.name),
                datasets: [{
                    label: 'Unidades vendidas',
                    data: items.map(i => i.unidades),
                    backgroundColor: 'rgba(139, 92, 246, 0.7)',
                    borderColor: 'rgba(139, 92, 246, 1)',
                    borderWidth: 1,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true } }
            }
        });
    }

    function renderDailyChart(series) {
        const ctx = document.getElementById('chart-daily');
        if (chartDaily) chartDaily.destroy();
        chartDaily = new Chart(ctx, {
            type: 'line',
            data: {
                labels: series.map(s => s.dia),
                datasets: [{
                    label: 'Ingresos diarios',
                    data: series.map(s => s.ingresos),
                    borderColor: 'rgba(16, 185, 129, 1)',
                    backgroundColor: 'rgba(16, 185, 129, 0.15)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: v => fmtMoney(v) } }
                }
            }
        });
    }

    function renderStaleTable(items) {
        if (!items.length) {
            staleTbody.innerHTML = `<tr><td colspan="5" class="px-3 py-4 text-center text-emerald-600">¡Todos los productos tienen movimiento!</td></tr>`;
            return;
        }
        staleTbody.innerHTML = items.map(it => `
            <tr class="border-b border-slate-100 hover:bg-slate-50">
                <td class="px-3 py-2 font-mono text-xs">${escapeHtml(it.sku)}</td>
                <td class="px-3 py-2">${escapeHtml(it.name)}</td>
                <td class="px-3 py-2">${fmtNum(it.stock)}</td>
                <td class="px-3 py-2 text-slate-500">${it.ultima_venta ? new Date(it.ultima_venta).toLocaleDateString('es-MX') : 'Nunca'}</td>
                <td class="px-3 py-2"><span class="px-2 py-0.5 rounded-full text-xs ${it.dias_sin_venta && it.dias_sin_venta > 60 ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700'}">${it.dias_sin_venta ?? '∞'} días</span></td>
            </tr>
        `).join('');
    }

    async function generateReport(refresh = false) {
        const days = periodSelect.value;

        reportEmpty.classList.add('hidden');
        reportContent.classList.add('hidden');
        reportLoading.classList.remove('hidden');
        generateBtn.disabled = true;
        generateBtnText.textContent = 'Generando…';

        try {
            const { data } = await api.get('/api/inventory/stats/report', { days, refresh: refresh ? 1 : 0 });
            reportContent.innerHTML = renderMarkdown(data.content);
            reportContent.classList.remove('hidden');
            reportMeta.textContent = `Generado ${new Date(data.generated_at).toLocaleString('es-MX')} · ${data.cached ? 'caché' : 'nuevo'}`;
            refreshBtn.classList.remove('hidden');
            showToast('Informe generado');
        } catch (err) {
            reportEmpty.classList.remove('hidden');
            const msg = err.response?.data?.message || 'Error al generar informe';
            const hint = err.response?.data?.hint ? ` (${err.response.data.hint})` : '';
            showToast(msg + hint, 'error');
        } finally {
            reportLoading.classList.add('hidden');
            generateBtn.disabled = false;
            generateBtnText.textContent = 'Generar informe IA';
        }
    }

    periodSelect.addEventListener('change', loadStats);
    generateBtn.addEventListener('click', () => generateReport(false));
    refreshBtn.addEventListener('click', () => generateReport(true));

    loadStats();
});
