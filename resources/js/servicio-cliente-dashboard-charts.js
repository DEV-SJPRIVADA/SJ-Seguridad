import ApexCharts from 'apexcharts';
import { BRAND_BLUE, DONUT_COLORS, sharedChart } from './charts/apex-defaults';

const chartInstances = {};

function destroyChart(key) {
    if (chartInstances[key]) {
        chartInstances[key].destroy();
        chartInstances[key] = null;
    }
}

function numericLabels(enabled) {
    return {
        enabled,
        offsetY: -8,
        style: {
            fontSize: '11px',
            fontWeight: 700,
            colors: ['#0f172a'],
        },
        background: { enabled: false },
        formatter(value) {
            const n = Number(value);

            return Number.isFinite(n) ? n.toLocaleString('es-CO') : '';
        },
    };
}

function renderBars(key, elementId, payload, color) {
    const el = document.querySelector(elementId);
    if (!el) {
        return;
    }

    destroyChart(key);
    const labels = payload?.labels?.length ? payload.labels : ['Sin datos'];
    const series = payload?.labels?.length ? payload.data.map(Number) : [0];
    const hasValues = series.some((value) => Number(value) > 0);

    chartInstances[key] = new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'bar', height: '100%' },
        series: [{ name: 'Casos', data: series }],
        plotOptions: {
            bar: {
                horizontal: false,
                borderRadius: 6,
                columnWidth: '48%',
                dataLabels: { position: 'top' },
            },
        },
        colors: [color],
        dataLabels: numericLabels(hasValues),
        grid: { ...sharedChart.grid, padding: { top: 18, right: 8, left: 4, bottom: 0 } },
        xaxis: { categories: labels, labels: { style: { fontSize: '11px' } } },
        yaxis: { min: 0, forceNiceScale: true, labels: { style: { fontSize: '11px' } } },
        legend: { show: false },
    });
    chartInstances[key].render();
}

function boot() {
    const node = document.getElementById('servicio-cliente-dashboard-data');
    if (!node) {
        return;
    }

    const data = JSON.parse(node.textContent || '{}');
    renderBars('tipologia', '#servicio-cliente-chart-tipologia', data.tipologia, BRAND_BLUE);
    renderBars('categoria', '#servicio-cliente-chart-categoria', data.categoria, '#0d9488');
    renderBars('eficacia', '#servicio-cliente-chart-eficacia', data.eficacia, '#ca8a04');
    renderBars('fuente', '#servicio-cliente-chart-fuente', data.fuente, DONUT_COLORS[0]);
    renderBars('recurrencia', '#servicio-cliente-chart-recurrencia', data.recurrencia, '#be123c');
}

document.addEventListener('DOMContentLoaded', boot);
