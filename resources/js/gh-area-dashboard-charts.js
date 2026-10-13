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
    evolucionPersonal: null,
    ingresosVsDesvinculaciones: null,
    evolucionFormacion: null,
    personalPorCargos: null,
    estadoPsicofisicos: null,
    estadoAcreditacion: null,
    consultasArchivo: null,
};

function destroyChart(key) {
    if (chartInstances[key]) {
        chartInstances[key].destroy();
        chartInstances[key] = null;
    }
}

/** Línea: evolución de activos EOM por mes. */
function renderLine(elSelector, key, data, color, seriesName) {
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
        tooltip: { enabled: hasValues },
    });
    chartInstances[key].render();
}

/** Barras agrupadas: ingresos vs desvinculaciones. */
function renderIngresosVsDesvinculaciones(data) {
    const el = document.querySelector('#gh-area-chart-ingresos-vs-desvinculaciones');
    if (!el) {
        return;
    }
    destroyChart('ingresosVsDesvinculaciones');
    const labels = data?.labels?.length ? data.labels : ['Sin datos'];
    const ingresos = data?.labels?.length ? (data.ingresos || []).map(Number) : [0];
    const desvinculaciones = data?.labels?.length ? (data.desvinculaciones || []).map(Number) : [0];

    chartInstances.ingresosVsDesvinculaciones = new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'bar', height: '100%' },
        series: [
            { name: 'Ingresos', data: ingresos },
            { name: 'Desvinculaciones', data: desvinculaciones },
        ],
        plotOptions: {
            bar: { horizontal: false, borderRadius: 4, columnWidth: '55%' },
        },
        colors: [STATUS_GREEN, STATUS_RED],
        xaxis: {
            categories: labels,
            labels: { style: { fontSize: '11px' } },
        },
        yaxis: {
            labels: { style: { fontSize: '11px' } },
            min: 0,
            forceNiceScale: true,
        },
        legend: { position: 'bottom', fontSize: '12px' },
    });
    chartInstances.ingresosVsDesvinculaciones.render();
}

/** Barras horizontales: personal por concepto / área. */
function renderPorCargos(data) {
    const el = document.querySelector('#gh-area-chart-personal-por-concepto');
    if (!el) {
        return;
    }
    destroyChart('personalPorCargos');
    const labels = data?.labels?.length ? data.labels : ['Sin datos'];
    const series = data?.labels?.length ? data.data.map(Number) : [0];
    const rowCount = Math.max(labels.length, 1);
    const chartHeight = Math.max(220, rowCount * 28 + 48);

    chartInstances.personalPorCargos = new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'bar', height: chartHeight },
        series: [{ name: 'Activos', data: series }],
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
    chartInstances.personalPorCargos.render();
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

window.renderGhAreaDashboardCharts = function renderGhAreaDashboardCharts(charts) {
    renderLine(
        '#gh-area-chart-evolucion-personal',
        'evolucionPersonal',
        charts?.evolucion_personal || { labels: [], data: [] },
        BRAND_BLUE,
        'Personal activo',
    );
    renderIngresosVsDesvinculaciones(charts?.ingresos_vs_desvinculaciones || {
        labels: [],
        ingresos: [],
        desvinculaciones: [],
    });
    renderLine(
        '#gh-area-chart-evolucion-formacion',
        'evolucionFormacion',
        charts?.evolucion_formacion || { labels: [], data: [] },
        BRAND_NAVY,
        'Formación',
    );
    renderPorCargos(charts?.personal_por_concepto || { labels: [], data: [] });
    renderDonut(
        '#gh-area-chart-estado-psicofisicos',
        'estadoPsicofisicos',
        charts?.estado_psicofisicos || { labels: [], data: [] },
        [STATUS_GREEN, STATUS_ORANGE, STATUS_RED],
    );
    renderDonut(
        '#gh-area-chart-estado-acreditacion',
        'estadoAcreditacion',
        charts?.estado_acreditacion || { labels: [], data: [] },
        [STATUS_GREEN, STATUS_ORANGE, BRAND_BLUE],
    );
    renderDonut(
        '#gh-area-chart-consultas-archivo',
        'consultasArchivo',
        charts?.consultas_archivo || { labels: [], data: [] },
        [BRAND_BLUE, BRAND_NAVY, STATUS_GREEN, STATUS_ORANGE, STATUS_RED, STATUS_NEUTRAL, '#0d9488', '#7c3aed'],
    );
};

// Avisa a Alpine que el módulo Vite ya expuso el renderer (evita primer paint vacío).
window.dispatchEvent(new CustomEvent('gh-area-dashboard-charts-ready'));
