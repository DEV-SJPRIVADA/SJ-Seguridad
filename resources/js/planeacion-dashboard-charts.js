import ApexCharts from 'apexcharts';
import { BRAND_BLUE, STATUS_ORANGE, sharedChart } from './charts/apex-defaults';

const chartInstances = {};

function destroyChart(key) {
    if (chartInstances[key]) {
        chartInstances[key].destroy();
        chartInstances[key] = null;
    }
}

function numericLabels(enabled, offsetY = -8) {
    return {
        enabled,
        offsetY,
        style: {
            fontSize: '11px',
            fontWeight: 700,
            colors: ['#0f172a'],
        },
        background: { enabled: false },
        formatter(value) {
            const n = Number(value);

            return Number.isFinite(n) ? n.toLocaleString('es-CO', { maximumFractionDigits: 2 }) : '';
        },
    };
}

function renderBars(key, elementId, payload, color, seriesName) {
    const el = document.querySelector(elementId);
    if (!el) {
        return;
    }

    destroyChart(key);
    const labels = payload?.labels?.length ? payload.labels : ['Sin datos'];
    const series = payload?.labels?.length ? payload.data.map(Number) : [0];
    const hasValues = series.some((value) => Number(value) !== 0);

    chartInstances[key] = new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'bar', height: '100%' },
        series: [{ name: seriesName, data: series }],
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
    const node = document.getElementById('planeacion-dashboard-data');
    if (!node) {
        return;
    }

    const data = JSON.parse(node.textContent || '{}');
    renderBars('cumplimiento', '#planeacion-dash-cumplimiento', data.cumplimiento, BRAND_BLUE, 'Resultado');
    renderBars('pendientes', '#planeacion-dash-pendientes', data.pendientes, STATUS_ORANGE, 'Pendiente');
}

document.addEventListener('DOMContentLoaded', boot);
