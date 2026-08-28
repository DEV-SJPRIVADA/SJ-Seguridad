import ApexCharts from 'apexcharts';
import {
    BRAND_BLUE,
    BRAND_NAVY,
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
        xaxis: {
            categories: data.trend.labels,
            labels: { style: { fontSize: '11px' } },
        },
        yaxis: {
            labels: { style: { fontSize: '11px' } },
            min: 0,
            forceNiceScale: true,
        },
        legend: { position: 'top', fontSize: '11px' },
    }).render();
}

function renderPurchaseStatus(data) {
    const el = document.querySelector('#purchaseStatusChart');
    if (!el) {
        return;
    }

    new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'donut', height: '100%' },
        series: data.purchaseStatus.data.map(Number),
        labels: data.purchaseStatus.labels,
        colors: colorsForWorkflowStatusLabels(data.purchaseStatus.labels),
        legend: { position: 'bottom', fontSize: '11px' },
        stroke: { width: 0 },
        plotOptions: {
            pie: {
                donut: { size: '55%' },
            },
        },
    }).render();
}

function renderBandejaStatus(data) {
    const el = document.querySelector('#bandejaStatusChart');
    if (!el) {
        return;
    }

    new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'donut', height: '100%' },
        series: data.bandejaStatus.data.map(Number),
        labels: data.bandejaStatus.labels,
        colors: colorsForWorkflowStatusLabels(data.bandejaStatus.labels),
        legend: { position: 'bottom', fontSize: '11px' },
        stroke: { width: 0 },
        plotOptions: {
            pie: {
                donut: { size: '55%' },
            },
        },
    }).render();
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
            },
        },
        colors: [BRAND_NAVY],
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
    }).render();
}

function renderTipoBandeja(data) {
    const el = document.querySelector('#tipoBandejaChart');
    if (!el) {
        return;
    }

    new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'donut', height: '100%' },
        series: data.tipoBandeja.data.map(Number),
        labels: data.tipoBandeja.labels,
        colors: [BRAND_BLUE, '#0f766e'],
        legend: { position: 'bottom', fontSize: '11px' },
        stroke: { width: 0 },
        plotOptions: {
            pie: {
                donut: { size: '55%' },
            },
        },
    }).render();
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
});
