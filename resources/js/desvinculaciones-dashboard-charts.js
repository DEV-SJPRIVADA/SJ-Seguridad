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
    evolucionMensual: null,
    porCausal: null,
    porCiudad: null,
    porCliente: null,
    porCargo: null,
};

function destroyChart(key) {
    if (chartInstances[key]) {
        chartInstances[key].destroy();
        chartInstances[key] = null;
    }
}

function isMobileChartViewport() {
    return window.matchMedia('(max-width: 639px)').matches;
}

/** Etiquetas numéricas sin caja opaca. */
function numericDataLabels(enabled = true) {
    return {
        enabled,
        style: {
            fontSize: isMobileChartViewport() ? '9px' : '11px',
            fontWeight: 700,
            colors: ['#0f172a'],
        },
        background: { enabled: false },
        formatter(val) {
            const n = Number(val);
            if (!Number.isFinite(n)) {
                return '';
            }

            return n.toLocaleString('es-CO');
        },
    };
}

/** Línea: evolución mensual de desvinculaciones. */
function renderEvolucion(data) {
    const el = document.querySelector('#desv-chart-evolucion-mensual');
    if (!el) {
        return;
    }
    destroyChart('evolucionMensual');
    const labels = data?.labels?.length ? data.labels : ['Sin datos'];
    const series = data?.labels?.length ? data.data.map(Number) : [0];
    const hasValues = Boolean(data?.labels?.length);
    const mobile = isMobileChartViewport();

    chartInstances.evolucionMensual = new ApexCharts(el, {
        ...sharedChart,
        chart: {
            ...sharedChart.chart,
            type: 'line',
            height: '100%',
            parentHeightOffset: 0,
        },
        grid: {
            ...sharedChart.grid,
            padding: { top: 16, right: 8, left: 4, bottom: 0 },
        },
        series: [{ name: 'Bajas', data: series }],
        colors: [STATUS_RED],
        stroke: { curve: 'smooth', width: 3 },
        markers: { size: hasValues ? (mobile ? 3 : 4) : 0 },
        dataLabels: {
            ...numericDataLabels(hasValues),
            offsetY: -10,
        },
        xaxis: {
            categories: labels,
            labels: {
                style: { fontSize: mobile ? '9px' : '11px' },
                rotate: mobile ? -45 : 0,
                rotateAlways: mobile,
            },
        },
        yaxis: {
            labels: { style: { fontSize: mobile ? '9px' : '11px' } },
            min: 0,
            forceNiceScale: true,
        },
        legend: { show: false },
        tooltip: { enabled: hasValues },
    });
    chartInstances.evolucionMensual.render();
}

/** Barras horizontales: distribución por dimensión. */
function renderHorizontalBar(elSelector, key, data, color, seriesName) {
    const el = document.querySelector(elSelector);
    if (!el) {
        return;
    }
    destroyChart(key);
    const labels = data?.labels?.length ? data.labels : ['Sin datos'];
    const series = data?.labels?.length ? data.data.map(Number) : [0];
    const hasValues = Boolean(data?.labels?.length);
    const rowCount = Math.max(labels.length, 1);
    const chartHeight = Math.max(200, rowCount * 28 + 40);
    el.style.height = `${chartHeight}px`;

    chartInstances[key] = new ApexCharts(el, {
        ...sharedChart,
        chart: {
            ...sharedChart.chart,
            type: 'bar',
            height: chartHeight,
            parentHeightOffset: 0,
        },
        series: [{ name: seriesName, data: series }],
        plotOptions: {
            bar: {
                horizontal: true,
                borderRadius: 6,
                barHeight: '58%',
                dataLabels: { position: 'top' },
            },
        },
        dataLabels: {
            ...numericDataLabels(hasValues),
            offsetX: 4,
        },
        colors: [color],
        xaxis: {
            categories: labels,
            labels: { style: { fontSize: '11px' } },
            min: 0,
            forceNiceScale: true,
        },
        legend: { show: false },
        grid: {
            ...sharedChart.grid,
            padding: { top: 0, right: 18, left: 0, bottom: 0 },
        },
    });
    chartInstances[key].render();
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
    const mobile = isMobileChartViewport();

    chartInstances[key] = new ApexCharts(el, {
        ...sharedChart,
        chart: {
            ...sharedChart.chart,
            type: 'donut',
            height: '100%',
            parentHeightOffset: 0,
        },
        series: hasValues ? series : [1],
        labels: hasValues ? labels : ['Sin datos'],
        colors: hasValues ? colors : [STATUS_NEUTRAL],
        legend: {
            position: 'bottom',
            fontSize: mobile ? '10px' : '11px',
        },
        dataLabels: { enabled: hasValues },
        stroke: { width: 0 },
        plotOptions: {
            pie: {
                donut: {
                    size: '56%',
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
        tooltip: { enabled: hasValues },
    });
    chartInstances[key].render();
}

window.renderDesvinculacionesDashboardCharts = function renderDesvinculacionesDashboardCharts(charts) {
    renderEvolucion(charts?.evolucion_mensual || { labels: [], data: [] });
    renderDonut(
        '#desv-chart-por-causal',
        'porCausal',
        charts?.por_causal || { labels: [], data: [] },
        [STATUS_RED, STATUS_ORANGE, BRAND_BLUE, STATUS_GREEN, BRAND_NAVY, STATUS_NEUTRAL, '#0d9488', '#7c3aed'],
    );
    renderHorizontalBar(
        '#desv-chart-por-ciudad',
        'porCiudad',
        charts?.por_ciudad || { labels: [], data: [] },
        BRAND_BLUE,
        'Bajas',
    );
    renderHorizontalBar(
        '#desv-chart-por-cliente',
        'porCliente',
        charts?.por_cliente || { labels: [], data: [] },
        BRAND_NAVY,
        'Bajas',
    );
    renderHorizontalBar(
        '#desv-chart-por-cargo',
        'porCargo',
        charts?.por_cargo || { labels: [], data: [] },
        STATUS_ORANGE,
        'Bajas',
    );
};

window.dispatchEvent(new CustomEvent('desvinculaciones-dashboard-charts-ready'));
