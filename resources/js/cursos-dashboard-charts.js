import ApexCharts from 'apexcharts';
import {
    BRAND_BLUE,
    BRAND_NAVY,
    DONUT_COLORS,
    STATUS_GREEN,
    STATUS_ORANGE,
    STATUS_RED,
    sharedChart,
} from './charts/apex-defaults';

const chartInstances = {
    tipo: null,
    vigencia: null,
    estado: null,
    trend: null,
};

function destroyChart(key) {
    if (chartInstances[key]) {
        chartInstances[key].destroy();
        chartInstances[key] = null;
    }
}

function renderTipo(data) {
    const el = document.querySelector('#cursos-chart-tipo');
    if (!el) {
        return;
    }
    destroyChart('tipo');
    chartInstances.tipo = new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'bar', height: '100%' },
        series: [{ name: 'Cursos', data: data.labels.length ? data.data : [0] }],
        plotOptions: {
            bar: { horizontal: true, borderRadius: 6, barHeight: '55%' },
        },
        colors: [BRAND_NAVY],
        xaxis: {
            categories: data.labels.length ? data.labels : ['Sin datos'],
            labels: { style: { fontSize: '11px' } },
            min: 0,
            forceNiceScale: true,
        },
        legend: { show: false },
    });
    chartInstances.tipo.render();
}

function renderVigencia(data) {
    const el = document.querySelector('#cursos-chart-vigencia');
    if (!el) {
        return;
    }
    destroyChart('vigencia');
    chartInstances.vigencia = new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'donut', height: '100%' },
        series: data.data.map(Number),
        labels: data.labels,
        colors: [STATUS_GREEN, STATUS_ORANGE, STATUS_RED],
        legend: { position: 'bottom', fontSize: '12px' },
        stroke: { width: 0 },
        plotOptions: {
            pie: { donut: { size: '55%' } },
        },
    });
    chartInstances.vigencia.render();
}

function renderEstado(data) {
    const el = document.querySelector('#cursos-chart-estado');
    if (!el) {
        return;
    }
    destroyChart('estado');
    chartInstances.estado = new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'bar', height: '100%' },
        series: [{ name: 'Cursos', data: data.labels.length ? data.data : [0] }],
        plotOptions: {
            bar: { horizontal: false, borderRadius: 6, columnWidth: '45%' },
        },
        colors: [BRAND_BLUE],
        xaxis: {
            categories: data.labels.length ? data.labels : ['Sin datos'],
            labels: { style: { fontSize: '11px' } },
        },
        yaxis: {
            labels: { style: { fontSize: '11px' } },
            min: 0,
            forceNiceScale: true,
        },
        legend: { show: false },
    });
    chartInstances.estado.render();
}

function renderTrend(data) {
    const el = document.querySelector('#cursos-chart-trend');
    if (!el) {
        return;
    }
    destroyChart('trend');
    chartInstances.trend = new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'bar', height: '100%', stacked: false },
        series: [
            { name: 'Nuevos', data: data.nuevos },
            { name: 'Actualizaciones', data: data.actualizados },
        ],
        plotOptions: {
            bar: { horizontal: false, borderRadius: 4, columnWidth: '55%' },
        },
        colors: [BRAND_BLUE, ...DONUT_COLORS.slice(2, 3)],
        xaxis: {
            categories: data.labels,
            labels: { style: { fontSize: '11px' } },
        },
        yaxis: {
            labels: { style: { fontSize: '11px' } },
            min: 0,
            forceNiceScale: true,
        },
        legend: { position: 'top', fontSize: '12px' },
    });
    chartInstances.trend.render();
}

export function renderCursosDashboardCharts(charts) {
    if (!charts) {
        return;
    }
    renderTipo(charts.by_tipo || { labels: [], data: [] });
    renderVigencia(charts.by_vigencia || { labels: ['VIGENTE', 'ACTUALIZAR', 'VENCIDO'], data: [0, 0, 0] });
    renderEstado(charts.by_estado || { labels: [], data: [] });
    renderTrend(charts.trend || { labels: [], nuevos: [], actualizados: [] });
}

window.renderCursosDashboardCharts = renderCursosDashboardCharts;
