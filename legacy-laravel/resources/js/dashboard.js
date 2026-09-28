import Chart from 'chart.js/auto';

const dailyCanvas = document.getElementById('daily-sales-chart');
const topCanvas = document.getElementById('top-products-chart');

function parseJson(el) {
    if (!el) {
        return null;
    }
    try {
        return JSON.parse(el.textContent);
    } catch (e) {
        return null;
    }
}

function rupiah(value) {
    return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
}

function compactRupiah(value) {
    const n = Number(value || 0);
    if (n >= 1000000000) {
        return 'Rp ' + (n / 1000000000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' M';
    }
    if (n >= 1000000) {
        return 'Rp ' + (n / 1000000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt';
    }
    if (n >= 1000) {
        return 'Rp ' + (n / 1000).toLocaleString('id-ID', { maximumFractionDigits: 0 }) + ' rb';
    }
    return 'Rp ' + n.toLocaleString('id-ID');
}

function barGradient(ctx, chartArea) {
    if (!chartArea) {
        return 'rgba(99, 102, 241, 0.8)';
    }
    const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
    gradient.addColorStop(0, 'rgba(99, 102, 241, 0.45)');
    gradient.addColorStop(0.6, 'rgba(99, 102, 241, 0.75)');
    gradient.addColorStop(1, 'rgba(168, 85, 247, 0.9)');
    return gradient;
}

let salesChart = null;
let salesSeriesMap = null;
let salesChartState = {
    period: '7d',
    values: [],
    counts: [],
    labels: [],
};

function renderSalesChart() {
    if (!dailyCanvas) {
        return;
    }

    const state = salesChartState;

    if (salesChart) {
        salesChart.destroy();
    }

    salesChart = new Chart(dailyCanvas, {
        type: 'bar',
        data: {
            labels: state.labels,
            datasets: [{
                label: 'Penjualan',
                data: state.values,
                backgroundColor: (ctx) => {
                    const { chart } = ctx;
                    return barGradient(chart.ctx, chart.chartArea);
                },
                borderRadius: 8,
                maxBarThickness: state.period === '12m' ? 56 : 34,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    titleColor: '#cbd5e1',
                    bodyColor: '#f8fafc',
                    padding: 12,
                    cornerRadius: 10,
                    callbacks: {
                        label: (ctx) => {
                            const count = state.counts[ctx.dataIndex] || 0;
                            return [rupiah(ctx.parsed.y), count + ' transaksi'];
                        },
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(148, 163, 184, 0.15)' },
                    ticks: {
                        color: '#64748b',
                        callback: (value) => compactRupiah(value),
                    },
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#64748b',
                        maxRotation: 0,
                        autoSkip: true,
                        maxTicksLimit: state.period === '12m' ? 12 : 10,
                    },
                },
            },
        },
    });
}

function switchSalesPeriod(period) {
    const series = salesSeriesMap[period];
    if (!series) {
        return;
    }

    salesChartState = {
        period,
        labels: series.labels,
        values: series.values,
        counts: series.counts,
    };

    const unit = period === '12m' ? 'bulan' : 'hari';
    document.getElementById('sales-chart-total').textContent = rupiah(series.total);
    document.getElementById('sales-chart-average').textContent = rupiah(Math.round(series.average)) + '/' + unit;

    document.querySelectorAll('.sales-tab').forEach((tab) => {
        const active = tab.dataset.period === period;
        tab.classList.toggle('bg-white', active);
        tab.classList.toggle('text-indigo-600', active);
        tab.classList.toggle('shadow-sm', active);
        tab.classList.toggle('text-slate-500', !active);
    });

    renderSalesChart();
}

function initSalesChart() {
    const data = parseJson(document.getElementById('daily-sales-data'));
    if (!data || !data['7d']) {
        return;
    }

    salesSeriesMap = Object.fromEntries(
        Object.entries(data).map(([key, series]) => [
            key,
            {
                labels: series.labels,
                values: series.data.map(Number),
                counts: (series.counts || []).map(Number),
                total: Number(series.total || 0),
                average: Number(series.average || 0),
            },
        ])
    );

    switchSalesPeriod('7d');

    document.querySelectorAll('.sales-tab').forEach((tab) => {
        tab.addEventListener('click', () => switchSalesPeriod(tab.dataset.period));
    });
}

function renderTopProductsChart() {
    if (!topCanvas) {
        return;
    }

    const items = parseJson(document.getElementById('top-products-data'));

    new Chart(topCanvas, {
        type: 'doughnut',
        data: {
            labels: items.map((item) => item.name),
            datasets: [{
                data: items.map((item) => Number(item.total_qty)),
                backgroundColor: ['#6366f1', '#a855f7', '#f59e0b', '#10b981', '#0ea5e9'],
                borderWidth: 2,
                borderColor: '#ffffff',
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '62%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    titleColor: '#cbd5e1',
                    bodyColor: '#f8fafc',
                    padding: 12,
                    cornerRadius: 10,
                    callbacks: {
                        label: (ctx) => {
                            const item = items[ctx.dataIndex];
                            return [item.name + ': ' + item.total_qty + ' terjual', rupiah(item.total_sales)];
                        },
                    },
                },
            },
        },
    });
}

function initDashboard() {
    initSalesChart();
    renderTopProductsChart();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDashboard);
} else {
    initDashboard();
}