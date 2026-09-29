import ApexCharts from 'apexcharts';
import {
    BRAND_BLUE,
    BRAND_NAVY,
    STATUS_BLUE,
    STATUS_GREEN,
    STATUS_ORANGE,
    STATUS_RED,
    sharedChart,
} from './charts/apex-defaults';

const chartInstances = {
    estado: null,
    cargo: null,
    trend: null,
    trendVencimientos: null,
};

const ESTADO_COLORS = [STATUS_BLUE, STATUS_GREEN, STATUS_ORANGE, STATUS_RED];

function destroyChart(key) {
    if (chartInstances[key]) {
        chartInstances[key].destroy();
        chartInstances[key] = null;
    }
}

function renderEstado(data) {
    const el = document.querySelector('#acreditaciones-chart-estado');
    if (!el) {
        return;
    }
    destroyChart('estado');
    const labels = data.labels?.length ? data.labels : ['Sin datos'];
    const series = data.labels?.length ? data.data.map(Number) : [0];
    chartInstances.estado = new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'donut', height: '100%' },
        series,
        labels,
        colors: ESTADO_COLORS,
        legend: { position: 'bottom', fontSize: '12px' },
        stroke: { width: 0 },
        plotOptions: {
            pie: { donut: { size: '55%' } },
        },
    });
    chartInstances.estado.render();
}

function renderCargo(data) {
    const el = document.querySelector('#acreditaciones-chart-cargo');
    if (!el) {
        return;
    }
    destroyChart('cargo');
    const labels = data.labels?.length ? data.labels : ['Sin datos'];
    const series = data.labels?.length ? data.data.map(Number) : [0];
    chartInstances.cargo = new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'bar', height: '100%' },
        series: [{ name: 'Acreditados', data: series }],
        plotOptions: {
            bar: { horizontal: true, borderRadius: 6, barHeight: '55%' },
        },
        colors: [BRAND_NAVY],
        xaxis: {
            categories: labels,
            labels: { style: { fontSize: '11px' } },
            min: 0,
            forceNiceScale: true,
        },
        legend: { show: false },
    });
    chartInstances.cargo.render();
}

function renderAreaTrend(instanceKey, selector, seriesName, color, data) {
    const el = document.querySelector(selector);
    if (!el) {
        return;
    }
    destroyChart(instanceKey);
    const labels = data.labels?.length ? data.labels : ['Sin datos'];
    const series = data.labels?.length ? (data.data || []).map(Number) : [0];
    chartInstances[instanceKey] = new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'area', height: '100%' },
        series: [{ name: seriesName, data: series }],
        colors: [color],
        fill: {
            type: 'solid',
            opacity: 0.12,
        },
        stroke: { curve: 'smooth', width: 2 },
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
    });
    chartInstances[instanceKey].render();
}

export function renderAcreditacionesDashboardCharts(charts) {
    if (!charts) {
        return;
    }
    renderEstado(charts.by_estado || { labels: [], data: [] });
    renderCargo(charts.by_cargo_apo || { labels: [], data: [] });
    renderAreaTrend('trend', '#acreditaciones-chart-trend', 'Solicitudes', BRAND_BLUE, charts.trend || { labels: [], data: [] });
    renderAreaTrend(
        'trendVencimientos',
        '#acreditaciones-chart-trend-vencimientos',
        'Vencimientos',
        STATUS_ORANGE,
        charts.trend_vencimientos || { labels: [], data: [] },
    );
}

window.renderAcreditacionesDashboardCharts = renderAcreditacionesDashboardCharts;
