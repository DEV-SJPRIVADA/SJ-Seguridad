import ApexCharts from 'apexcharts';
import {
    BRAND_BLUE,
    BRAND_NAVY,
    STATUS_GREEN,
    STATUS_NEUTRAL,
    STATUS_ORANGE,
    colorsForWorkflowStatusLabels,
    sharedChart,
} from './charts/apex-defaults';

function readChartData() {
    const el = document.getElementById('compras-chart-data');
    if (!el) {
        return null;
    }

    try {
        return JSON.parse(el.textContent);
    } catch {
        return null;
    }
}

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

function renderTrend(data) {
    const el = document.querySelector('#trendChart');
    if (!el) {
        return;
    }

    new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'area', height: '100%' },
        series: [
            { name: 'Solicitudes compra', data: data.trend.purchase },
            { name: 'Suministros', data: data.trend.supply },
        ],
        colors: [BRAND_BLUE, BRAND_NAVY],
        fill: {
            type: 'solid',
            opacity: 0.12,
        },
        stroke: { curve: 'smooth', width: 2 },
        markers: { size: 3 },
        dataLabels: {
            ...numericDataLabels(true),
            offsetY: -10,
        },
        grid: {
            ...sharedChart.grid,
            padding: { top: 16, right: 8, left: 4, bottom: 0 },
        },
        xaxis: {
            categories: data.trend.labels,
            labels: { style: { fontSize: '11px' } },
        },
        yaxis: {
            labels: { style: { fontSize: '11px' } },
            min: 0,
            forceNiceScale: true,
        },
        legend: { position: 'bottom', fontSize: '11px' },
    }).render();
}

function donutColors(slice, fallbackLabels) {
    if (Array.isArray(slice?.colors) && slice.colors.length) {
        return slice.colors;
    }

    return colorsForWorkflowStatusLabels(fallbackLabels || slice?.labels || []);
}

function renderPurchaseStatus(data) {
    const el = document.querySelector('#purchaseStatusChart');
    if (!el) {
        return;
    }

    renderDonut(el, data.purchaseStatus.data, data.purchaseStatus.labels, donutColors(data.purchaseStatus));
}

function renderBandejaStatus(data) {
    const el = document.querySelector('#bandejaStatusChart');
    if (!el) {
        return;
    }

    renderDonut(el, data.bandejaStatus.data, data.bandejaStatus.labels, donutColors(data.bandejaStatus));
}

function renderAreas(data) {
    const el = document.querySelector('#areaChart');
    if (!el) {
        return;
    }

    new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'bar', height: '100%' },
        series: [{ name: 'Solicitudes', data: data.areas.data }],
        plotOptions: {
            bar: {
                horizontal: true,
                borderRadius: 8,
                barHeight: '60%',
                dataLabels: { position: 'top' },
            },
        },
        colors: [BRAND_NAVY],
        dataLabels: {
            ...numericDataLabels(true),
            offsetX: 6,
        },
        xaxis: {
            categories: data.areas.labels,
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
            padding: { top: 8, right: 18, left: 0, bottom: 0 },
        },
    }).render();
}

function renderTipoBandeja(data) {
    const el = document.querySelector('#tipoBandejaChart');
    if (!el) {
        return;
    }

    renderDonut(el, data.tipoBandeja.data, data.tipoBandeja.labels, [BRAND_BLUE, '#0f766e']);
}

function renderOrdersTrend(data) {
    const el = document.querySelector('#ordersTrendChart');
    if (!el || !data.ordersTrend) {
        return;
    }

    new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'bar', height: '100%' },
        series: [{ name: 'Órdenes', data: data.ordersTrend.data }],
        plotOptions: {
            bar: {
                horizontal: false,
                borderRadius: 4,
                columnWidth: '55%',
                dataLabels: { position: 'top' },
            },
        },
        colors: [BRAND_BLUE],
        dataLabels: {
            ...numericDataLabels(true),
            offsetY: -12,
        },
        grid: {
            ...sharedChart.grid,
            padding: { top: 16, right: 8, left: 4, bottom: 0 },
        },
        xaxis: {
            categories: data.ordersTrend.labels,
            labels: { style: { fontSize: '11px' } },
        },
        yaxis: {
            labels: { style: { fontSize: '11px' } },
            min: 0,
            forceNiceScale: true,
        },
        legend: { show: false },
    }).render();
}

function renderOrdersCycle(data) {
    const el = document.querySelector('#ordersCycleChart');
    if (!el || !data.ordersCycle) {
        return;
    }

    new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'line', height: '100%' },
        series: [
            { name: 'Desde autorización', data: data.ordersCycle.desde_aprobacion },
            { name: 'Desde solicitud', data: data.ordersCycle.desde_solicitud },
        ],
        colors: [STATUS_ORANGE, BRAND_NAVY],
        stroke: { curve: 'smooth', width: 3 },
        markers: { size: 4 },
        dataLabels: {
            ...numericDataLabels(true),
            offsetY: -10,
        },
        grid: {
            ...sharedChart.grid,
            padding: { top: 16, right: 8, left: 4, bottom: 0 },
        },
        xaxis: {
            categories: data.ordersCycle.labels,
            labels: { style: { fontSize: '11px' } },
        },
        yaxis: {
            labels: { style: { fontSize: '11px' } },
            min: 0,
            forceNiceScale: true,
        },
        legend: { position: 'bottom', fontSize: '11px' },
    }).render();
}

function renderOrdersEvaluation(data) {
    const el = document.querySelector('#ordersEvaluationChart');
    if (!el || !data.ordersEvaluation) {
        return;
    }

    new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'bar', height: '100%' },
        series: [{ name: 'Evaluación %', data: data.ordersEvaluation.data }],
        plotOptions: {
            bar: {
                horizontal: false,
                borderRadius: 4,
                columnWidth: '55%',
                dataLabels: { position: 'top' },
            },
        },
        colors: [STATUS_GREEN],
        dataLabels: {
            ...numericDataLabels(true),
            offsetY: -12,
        },
        grid: {
            ...sharedChart.grid,
            padding: { top: 16, right: 8, left: 4, bottom: 0 },
        },
        xaxis: {
            categories: data.ordersEvaluation.labels,
            labels: { style: { fontSize: '11px' } },
        },
        yaxis: {
            labels: { style: { fontSize: '11px' } },
            min: 0,
            max: 100,
            forceNiceScale: true,
        },
        legend: { show: false },
    }).render();
}

function renderOrdersCoverage(data) {
    const el = document.querySelector('#ordersCoverageChart');
    if (!el || !data.ordersCoverage) {
        return;
    }

    renderDonut(el, data.ordersCoverage.data, data.ordersCoverage.labels, [STATUS_GREEN, STATUS_NEUTRAL]);
}

function renderDonut(el, rawSeries, rawLabels, colors) {
    const series = (rawSeries || []).map(Number);
    const labels = rawLabels || [];
    const hasValues = series.some((value) => Number(value) > 0);

    new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'donut', height: '100%' },
        series: hasValues ? series : [1],
        labels: hasValues ? labels : ['Sin datos'],
        colors: hasValues ? colors : [STATUS_NEUTRAL],
        legend: { position: 'bottom', fontSize: '11px' },
        stroke: { width: 0 },
        dataLabels: {
            enabled: hasValues,
            style: { fontSize: '12px' },
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '55%',
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
    }).render();
}

function bindAutoSubmitFilters() {
    const filterForm = document.querySelector('.compras-dashboard-page__filter-form');
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

document.addEventListener('DOMContentLoaded', () => {
    bindAutoSubmitFilters();

    const data = readChartData();
    if (!data) {
        return;
    }

    renderTrend(data);
    renderPurchaseStatus(data);
    renderBandejaStatus(data);
    renderAreas(data);
    renderTipoBandeja(data);
    renderOrdersTrend(data);
    renderOrdersCycle(data);
    renderOrdersEvaluation(data);
    renderOrdersCoverage(data);
});
