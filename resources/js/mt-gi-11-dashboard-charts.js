import ApexCharts from 'apexcharts';
import { BRAND_BLUE, sharedChart } from './charts/apex-defaults';

/**
 * Gráficos del dashboard MT-GI-11 (Calidad).
 *
 * Dos barras verticales: solicitudes por proceso y por tipo de documento.
 * Etiquetas numéricas encima de cada columna, siguiendo dashboard-ui.mdc.
 */
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

function renderBars(key, selector, payload, color) {
    const el = document.querySelector(selector);
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
        series: [{ name: 'Solicitudes', data: series }],
        plotOptions: {
            bar: {
                horizontal: false,
                borderRadius: 6,
                columnWidth: '52%',
                dataLabels: { position: 'top' },
            },
        },
        colors: [color],
        dataLabels: numericLabels(hasValues),
        grid: { ...sharedChart.grid, padding: { top: 18, right: 8, left: 4, bottom: 0 } },
        xaxis: {
            categories: labels,
            labels: { rotate: -25, trim: true, style: { fontSize: '11px' } },
        },
        yaxis: { min: 0, forceNiceScale: true, labels: { style: { fontSize: '11px' } } },
        legend: { show: false },
    });

    chartInstances[key].render();
}

function boot() {
    const node = document.getElementById('mt-gi-11-dashboard-data');
    if (!node) {
        return;
    }

    const data = JSON.parse(node.textContent || '{}');

    renderBars('proceso', '#mt-gi-11-chart-proceso', data.proceso, BRAND_BLUE);
    renderBars('tipo_documento', '#mt-gi-11-chart-tipo-documento', data.tipo_documento, '#0d9488');
}

document.addEventListener('DOMContentLoaded', boot);
