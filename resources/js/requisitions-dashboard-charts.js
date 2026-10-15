import ApexCharts from 'apexcharts';
import {
    BRAND_BLUE,
    BRAND_NAVY,
    STATUS_DONUT_COLORS,
    STATUS_NEUTRAL,
    sharedChart,
} from './charts/apex-defaults';

/** @type {ApexCharts[]} */
const chartInstances = [];

/** Colores KPI del dashboard (fallback por clave de estado). */
const STATUS_KPI_COLORS = {
    solicitada: '#0284c7',
    en_gestion: '#b45309',
    contratado: '#15803d',
    cancelada: '#be123c',
    aprobada: '#0f766e',
    pendiente_autorizacion_gerencia: '#7e22ce',
};

function readChartData() {
    const el = document.getElementById('requisitions-chart-data');
    if (!el) {
        return null;
    }

    try {
        return JSON.parse(el.textContent);
    } catch {
        return null;
    }
}

/**
 * @param {{ colors?: string[], keys?: string[] }} status
 * @returns {string[]}
 */
function statusChartColors(status) {
    if (Array.isArray(status.colors) && status.colors.length > 0) {
        return status.colors;
    }

    if (Array.isArray(status.keys) && status.keys.length > 0) {
        return status.keys.map((key) => STATUS_KPI_COLORS[key] || '#64748b');
    }

    return STATUS_DONUT_COLORS;
}

/** Celular: tipografía/ejes más compactos. */
function isMobileChartViewport() {
    return window.matchMedia('(max-width: 639px)').matches;
}

/**
 * Etiquetas numéricas horizontales sin caja opaca.
 * La legibilidad va por trazo blanco en CSS (.req-dashboard-charts).
 */
function numericDataLabels(enabled = true) {
    return {
        enabled,
        style: {
            fontSize: '11px',
            fontWeight: 700,
            colors: ['#0f172a'],
        },
        background: {
            enabled: false,
        },
        formatter(val) {
            const n = Number(val);

            if (!Number.isFinite(n)) {
                return '';
            }

            return n.toLocaleString('es-CO');
        },
    };
}

function mountChart(el, options) {
    const chart = new ApexCharts(el, options);
    chart.render();
    chartInstances.push(chart);

    return chart;
}

function hasNumericSeries(values) {
    return Array.isArray(values) && values.some((value) => Number(value) > 0);
}

function renderTrend(data) {
    const el = document.querySelector('#trendChart');
    if (!el) {
        return null;
    }

    const labels = data?.trend?.labels?.length ? data.trend.labels : ['Sin datos'];
    const series = data?.trend?.labels?.length ? data.trend.data.map(Number) : [0];
    const hasValues = hasNumericSeries(series);
    const mobile = isMobileChartViewport();

    return mountChart(el, {
        ...sharedChart,
        chart: {
            ...sharedChart.chart,
            type: 'area',
            height: '100%',
            parentHeightOffset: 0,
        },
        grid: {
            ...sharedChart.grid,
            padding: {
                top: 18,
                right: mobile ? 4 : 6,
                left: mobile ? 0 : 4,
                bottom: 0,
            },
        },
        series: [{ name: 'Solicitudes', data: series }],
        colors: [BRAND_BLUE],
        fill: {
            type: 'solid',
            opacity: 0.15,
        },
        stroke: { curve: 'smooth', width: 2 },
        markers: { size: hasValues ? (mobile ? 3 : 4) : 0 },
        dataLabels: {
            ...numericDataLabels(hasValues),
            // Encima del punto, horizontal (valores pequeños).
            offsetY: -12,
            style: {
                fontSize: mobile ? '10px' : '11px',
                fontWeight: 700,
                colors: ['#0f172a'],
            },
        },
        xaxis: {
            categories: labels,
            labels: {
                style: { fontSize: mobile ? '9px' : '11px' },
                rotate: mobile ? -45 : 0,
                rotateAlways: mobile,
                hideOverlappingLabels: true,
            },
        },
        yaxis: {
            labels: { style: { fontSize: mobile ? '9px' : '11px' } },
            min: 0,
            forceNiceScale: true,
        },
        legend: { show: false },
        tooltip: {
            enabled: hasValues,
            y: { formatter: (v) => String(v) },
        },
    });
}

function renderStatus(data) {
    const el = document.querySelector('#statusChart');
    if (!el) {
        return null;
    }

    const labels = data?.status?.labels?.length ? data.status.labels : ['Sin datos'];
    const series = data?.status?.labels?.length ? data.status.data.map(Number) : [0];
    const hasValues = hasNumericSeries(series);
    const mobile = isMobileChartViewport();

    return mountChart(el, {
        ...sharedChart,
        chart: {
            ...sharedChart.chart,
            type: 'donut',
            height: '100%',
            parentHeightOffset: 0,
        },
        series: hasValues ? series : [1],
        labels: hasValues ? labels : ['Sin datos'],
        colors: hasValues ? statusChartColors(data.status) : [STATUS_NEUTRAL],
        legend: {
            position: 'bottom',
            horizontalAlign: 'center',
            fontSize: mobile ? '10px' : '11px',
            itemMargin: { horizontal: 6, vertical: 2 },
            height: 52,
            offsetY: 0,
        },
        dataLabels: {
            enabled: hasValues,
            style: {
                fontSize: mobile ? '10px' : '12px',
            },
        },
        stroke: { width: 0 },
        plotOptions: {
            pie: {
                donut: {
                    size: mobile ? '55%' : '58%',
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
}

function renderCities(data) {
    const el = document.querySelector('#cityChart');
    if (!el) {
        return null;
    }

    const labels = data?.cities?.labels?.length ? data.cities.labels : ['Sin datos'];
    const series = data?.cities?.labels?.length ? data.cities.data.map(Number) : [0];
    const hasValues = hasNumericSeries(series);

    return mountChart(el, {
        ...sharedChart,
        chart: {
            ...sharedChart.chart,
            type: 'bar',
            height: '100%',
            parentHeightOffset: 0,
        },
        series: [{ name: 'Solicitudes', data: series }],
        plotOptions: {
            bar: {
                horizontal: true,
                borderRadius: 8,
                barHeight: '60%',
                dataLabels: { position: 'top' },
            },
        },
        dataLabels: {
            ...numericDataLabels(hasValues),
            offsetX: 6,
        },
        colors: [BRAND_NAVY],
        xaxis: {
            categories: labels,
            labels: { style: { fontSize: '11px' } },
            min: 0,
            forceNiceScale: true,
        },
        yaxis: {
            labels: { style: { fontSize: '11px' } },
        },
        legend: { show: false },
        grid: {
            ...sharedChart.grid,
            padding: { top: 0, right: 16, left: 0, bottom: 0 },
        },
        tooltip: { enabled: hasValues },
    });
}

function renderClients(data) {
    const el = document.querySelector('#clientChart');
    if (!el) {
        return null;
    }

    const labels = data?.clients?.labels?.length ? data.clients.labels : ['Sin datos'];
    const series = data?.clients?.labels?.length ? data.clients.data.map(Number) : [0];
    const hasValues = hasNumericSeries(series);
    const mobile = isMobileChartViewport();

    return mountChart(el, {
        ...sharedChart,
        chart: {
            ...sharedChart.chart,
            type: 'bar',
            height: '100%',
            parentHeightOffset: 0,
        },
        grid: {
            ...sharedChart.grid,
            // Espacio superior para que el número quede encima de la barra, no en el borde.
            padding: {
                top: 28,
                right: mobile ? 4 : 8,
                left: mobile ? 0 : 4,
                bottom: 0,
            },
        },
        series: [{ name: 'Solicitudes', data: series }],
        plotOptions: {
            bar: {
                horizontal: false,
                borderRadius: 8,
                columnWidth: mobile ? '70%' : '55%',
                dataLabels: { position: 'top' },
            },
        },
        dataLabels: {
            ...numericDataLabels(hasValues),
            // Horizontal y claramente encima de la columna.
            offsetY: -16,
            style: {
                fontSize: mobile ? '10px' : '11px',
                fontWeight: 700,
                colors: ['#0f172a'],
            },
        },
        colors: [BRAND_BLUE],
        xaxis: {
            categories: labels,
            labels: {
                style: { fontSize: mobile ? '9px' : '11px' },
                trim: true,
                maxHeight: 48,
                rotate: mobile ? -45 : 0,
                rotateAlways: mobile,
                hideOverlappingLabels: true,
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
}

function bindAutoSubmitFilters() {
    const filterForm = document.querySelector('.dashboard-filters');
    if (!filterForm) {
        return;
    }

    let submitTimer = null;
    filterForm.addEventListener('change', () => {
        if (submitTimer) {
            clearTimeout(submitTimer);
        }
        submitTimer = setTimeout(() => {
            filterForm.submit();
        }, 20);
    });
}

function bindChartResize() {
    let resizeTimer;

    window.addEventListener('resize', () => {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(() => {
            chartInstances.forEach((chart) => {
                chart.resize();
            });
        }, 120);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    bindAutoSubmitFilters();
    bindChartResize();

    const data = readChartData();
    if (!data) {
        return;
    }

    renderTrend(data);
    renderStatus(data);
    renderCities(data);
    renderClients(data);
});
