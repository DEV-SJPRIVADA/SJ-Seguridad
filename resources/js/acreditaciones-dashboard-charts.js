import ApexCharts from 'apexcharts';
import {
    BRAND_BLUE,
    BRAND_NAVY,
    STATUS_BLUE,
    STATUS_GREEN,
    STATUS_NEUTRAL,
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

/** Colores por etiqueta de estado (orden del backend puede variar). */
function colorsForEstadoLabels(labels) {
    return labels.map((label) => {
        const normalized = String(label || '').toLowerCase();

        if (normalized.includes('desacredit')) {
            return STATUS_RED;
        }

        if (normalized.includes('acredit')) {
            return STATUS_GREEN;
        }

        if (normalized.includes('vencer')) {
            return STATUS_ORANGE;
        }

        if (normalized.includes('proceso')) {
            return STATUS_BLUE;
        }

        return STATUS_NEUTRAL;
    });
}

function isMobileChartViewport() {
    return window.matchMedia('(max-width: 639px)').matches;
}

function hasNumericSeries(values) {
    return Array.isArray(values) && values.some((value) => Number(value) > 0);
}

/**
 * Etiquetas numéricas horizontales sin caja opaca.
 * Halo blanco vía CSS (.acreditaciones-dashboard-page .apexcharts-datalabels).
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
    const hasValues = hasNumericSeries(series);
    const mobile = isMobileChartViewport();

    chartInstances.estado = new ApexCharts(el, {
        ...sharedChart,
        chart: {
            ...sharedChart.chart,
            type: 'donut',
            height: '100%',
            parentHeightOffset: 0,
        },
        series: hasValues ? series : [1],
        labels: hasValues ? labels : ['Sin datos'],
        colors: hasValues ? colorsForEstadoLabels(labels) : [STATUS_NEUTRAL],
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
    const hasValues = hasNumericSeries(series);

    chartInstances.cargo = new ApexCharts(el, {
        ...sharedChart,
        chart: {
            ...sharedChart.chart,
            type: 'bar',
            height: '100%',
            parentHeightOffset: 0,
        },
        series: [{ name: 'Acreditados', data: series }],
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
    const hasValues = hasNumericSeries(series);
    const mobile = isMobileChartViewport();

    chartInstances[instanceKey] = new ApexCharts(el, {
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
        series: [{ name: seriesName, data: series }],
        colors: [color],
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
