import ApexCharts from 'apexcharts';
import {
    BRAND_BLUE,
    BRAND_NAVY,
    DONUT_COLORS,
    STATUS_DONUT_COLORS,
    sharedChart,
} from './charts/apex-defaults';

const chartInstances = {
    solicitud: null,
    cliente: null,
    responsable: null,
    trend: null,
};

function destroyChart(key) {
    if (chartInstances[key]) {
        chartInstances[key].destroy();
        chartInstances[key] = null;
    }
}

function renderSolicitud(data) {
    const el = document.querySelector('#seleccion-chart-solicitud');
    if (!el) {
        return;
    }
    destroyChart('solicitud');
    const labels = data.labels?.length ? data.labels : ['Sin datos'];
    const series = data.labels?.length ? data.data.map(Number) : [0];
    chartInstances.solicitud = new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'bar', height: '100%' },
        series: [{ name: 'Exámenes', data: series }],
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
    chartInstances.solicitud.render();
}

function renderCliente(data) {
    const el = document.querySelector('#seleccion-chart-cliente');
    if (!el) {
        return;
    }
    destroyChart('cliente');
    const labels = data.labels?.length ? data.labels : ['Sin datos'];
    const series = data.labels?.length ? data.data.map(Number) : [0];
    chartInstances.cliente = new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'donut', height: '100%' },
        series,
        labels,
        colors: DONUT_COLORS.concat(STATUS_DONUT_COLORS),
        legend: { position: 'bottom', fontSize: '12px' },
        stroke: { width: 0 },
        plotOptions: {
            pie: { donut: { size: '55%' } },
        },
    });
    chartInstances.cliente.render();
}

function renderResponsable(data) {
    const el = document.querySelector('#seleccion-chart-responsable');
    if (!el) {
        return;
    }
    destroyChart('responsable');
    const labels = data.labels?.length ? data.labels : ['Sin datos'];
    const series = data.labels?.length ? data.data.map(Number) : [0];
    chartInstances.responsable = new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'bar', height: '100%' },
        series: [{ name: 'Registros', data: series }],
        plotOptions: {
            bar: { horizontal: false, borderRadius: 6, columnWidth: '45%' },
        },
        colors: [BRAND_BLUE],
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
    chartInstances.responsable.render();
}

function renderTrend(data) {
    const el = document.querySelector('#seleccion-chart-trend');
    if (!el) {
        return;
    }
    destroyChart('trend');
    const labels = data.labels?.length ? data.labels : ['Sin datos'];
    const series = data.labels?.length ? (data.data || []).map(Number) : [0];
    chartInstances.trend = new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'area', height: '100%' },
        series: [{ name: 'Ingresos', data: series }],
        colors: [BRAND_BLUE],
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
    chartInstances.trend.render();
}

export function renderSeleccionDashboardCharts(charts) {
    if (!charts) {
        return;
    }
    renderSolicitud(charts.by_solicitud_status || { labels: [], data: [] });
    renderCliente(charts.by_client || { labels: [], data: [] });
    renderResponsable(charts.by_responsable || { labels: [], data: [] });
    renderTrend(charts.ingresos_trend || { labels: [], data: [] });
}

window.renderSeleccionDashboardCharts = renderSeleccionDashboardCharts;
