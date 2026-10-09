import ApexCharts from 'apexcharts';
import {
    BRAND_BLUE,
    BRAND_NAVY,
    STATUS_GREEN,
    STATUS_NEUTRAL,
    STATUS_ORANGE,
    STATUS_RED,
    sharedChart,
} from './charts/apex-defaults';

const chartInstances = {
    examen1: null,
    examen2: null,
    aptos: null,
    examen1Anio: null,
    examen2Anio: null,
    examen1Cargo: null,
    examen2Cargo: null,
};

const ESTADO_COLORS = [STATUS_GREEN, STATUS_ORANGE, STATUS_RED];
const APTO_COLORS = [STATUS_GREEN, STATUS_RED, STATUS_NEUTRAL];

function destroyChart(key) {
    if (chartInstances[key]) {
        chartInstances[key].destroy();
        chartInstances[key] = null;
    }
}

function renderDonut(elSelector, key, data, colors) {
    const el = document.querySelector(elSelector);
    if (!el) {
        return;
    }
    destroyChart(key);
    const labels = data?.labels?.length ? data.labels : ['Sin datos'];
    const series = data?.labels?.length ? data.data.map(Number) : [0];
    const hasValues = series.some((value) => Number(value) > 0);

    chartInstances[key] = new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'donut', height: '100%' },
        series: hasValues ? series : [1],
        labels: hasValues ? labels : ['Sin datos'],
        colors: hasValues ? colors : [STATUS_NEUTRAL],
        legend: {
            position: 'bottom',
            fontSize: '12px',
        },
        dataLabels: {
            enabled: hasValues,
        },
        stroke: { width: 0 },
        plotOptions: {
            pie: {
                donut: {
                    size: '58%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total',
                            formatter(w) {
                                if (!hasValues) {
                                    return '0';
                                }

                                return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                            },
                        },
                    },
                },
            },
        },
        tooltip: {
            enabled: hasValues,
        },
    });
    chartInstances[key].render();
}

/** Tendencia de vencimientos por año (línea). */
function renderTendenciaAnio(elSelector, key, data, color, seriesName) {
    const el = document.querySelector(elSelector);
    if (!el) {
        return;
    }
    destroyChart(key);
    const labels = data?.labels?.length ? data.labels : ['Sin datos'];
    const series = data?.labels?.length ? data.data.map(Number) : [0];
    const hasValues = data?.labels?.length > 0;

    chartInstances[key] = new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'line', height: '100%' },
        series: [{ name: seriesName, data: series }],
        colors: [color],
        stroke: { curve: 'smooth', width: 3 },
        markers: { size: hasValues ? 4 : 0 },
        xaxis: {
            categories: labels,
            labels: { style: { fontSize: '11px' } },
        },
        yaxis: {
            labels: { style: { fontSize: '11px' } },
            min: 0,
            forceNiceScale: true,
        },
        legend: { show: false },
        tooltip: {
            enabled: hasValues,
            y: {
                formatter(value) {
                    return String(value);
                },
            },
        },
    });
    chartInstances[key].render();
}

/** Barras horizontales: cantidad por cargo. */
function renderPorCargo(elSelector, key, data, color) {
    const el = document.querySelector(elSelector);
    if (!el) {
        return;
    }
    destroyChart(key);
    const labels = data?.labels?.length ? data.labels : ['Sin datos'];
    const series = data?.labels?.length ? data.data.map(Number) : [0];
    const rowCount = Math.max(labels.length, 1);
    const chartHeight = Math.max(220, rowCount * 28 + 48);

    chartInstances[key] = new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'bar', height: chartHeight },
        series: [{ name: 'Cantidad', data: series }],
        plotOptions: {
            bar: { horizontal: true, borderRadius: 6, barHeight: '55%' },
        },
        colors: [color],
        xaxis: {
            categories: labels,
            labels: { style: { fontSize: '11px' } },
            min: 0,
            forceNiceScale: true,
        },
        yaxis: {
            labels: {
                style: { fontSize: '11px' },
                maxWidth: 160,
            },
        },
        legend: { show: false },
        dataLabels: {
            enabled: true,
            style: { fontSize: '11px' },
        },
    });
    chartInstances[key].render();
}

/**
 * @param {object} charts
 * @param {'psicofisicos'|'psicosensometricos'|string} [mode]
 */
export function renderMtSt04DashboardCharts(charts, mode = 'psicofisicos') {
    if (!charts) {
        return;
    }

    if (mode === 'psicosensometricos') {
        renderDonut('#mt-st-04-chart-examen2', 'examen2', charts.examen2_estados || { labels: [], data: [] }, ESTADO_COLORS);
        renderTendenciaAnio(
            '#mt-st-04-chart-examen2-anio',
            'examen2Anio',
            charts.examen2_vencimientos_anio || { labels: [], data: [] },
            '#0f766e',
            'Vencimientos',
        );
        renderPorCargo(
            '#mt-st-04-chart-examen2-cargo',
            'examen2Cargo',
            charts.examen2_por_cargo || { labels: [], data: [] },
            '#0f766e',
        );
        return;
    }

    renderDonut('#mt-st-04-chart-examen1', 'examen1', charts.examen1_estados || { labels: [], data: [] }, ESTADO_COLORS);
    renderDonut('#mt-st-04-chart-aptos', 'aptos', charts.aptos || { labels: [], data: [] }, APTO_COLORS);
    renderTendenciaAnio(
        '#mt-st-04-chart-examen1-anio',
        'examen1Anio',
        charts.examen1_vencimientos_anio || { labels: [], data: [] },
        BRAND_BLUE,
        'Vencimientos',
    );
    renderPorCargo(
        '#mt-st-04-chart-examen1-cargo',
        'examen1Cargo',
        charts.examen1_por_cargo || { labels: [], data: [] },
        BRAND_NAVY,
    );
}

window.renderMtSt04DashboardCharts = renderMtSt04DashboardCharts;
window.dispatchEvent(new CustomEvent('mt-st-04-dashboard-charts-ready'));
