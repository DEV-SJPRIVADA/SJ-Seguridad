import ApexCharts from 'apexcharts';
import {
    BRAND_BLUE,
    BRAND_NAVY,
    DONUT_COLORS,
    sharedChart,
} from './charts/apex-defaults';

function readChartData() {
    const el = document.getElementById('comercial-chart-data');
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
        series: [{ name: 'Servicios', data: data.trend.data }],
        labels: data.trend.labels,
        colors: [BRAND_BLUE],
        fill: {
            type: 'solid',
            opacity: 0.15,
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
        legend: { show: false },
        tooltip: { y: { formatter: (v) => String(v) } },
    }).render();
}

function renderPortfolio(data) {
    const el = document.querySelector('#portfolioChart');
    if (!el) {
        return;
    }

    new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'donut', height: '100%' },
        series: data.portfolio.data.map(Number),
        labels: data.portfolio.labels,
        colors: DONUT_COLORS,
        legend: { position: 'bottom', fontSize: '12px' },
        stroke: { width: 0 },
        plotOptions: {
            pie: {
                donut: { size: '55%' },
            },
        },
    }).render();
}

function renderCities(data) {
    const el = document.querySelector('#cityChart');
    if (!el) {
        return;
    }

    new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'bar', height: '100%' },
        series: [{ name: 'Servicios', data: data.cities.data }],
        plotOptions: {
            bar: {
                horizontal: true,
                borderRadius: 8,
                barHeight: '60%',
            },
        },
        colors: [BRAND_NAVY],
        xaxis: {
            categories: data.cities.labels,
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

function renderServiceTypes(data) {
    const el = document.querySelector('#serviceTypeChart');
    if (!el) {
        return;
    }

    new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'bar', height: '100%' },
        series: [{ name: 'Servicios', data: data.serviceTypes.data }],
        plotOptions: {
            bar: {
                horizontal: false,
                borderRadius: 8,
                columnWidth: '55%',
            },
        },
        colors: [BRAND_BLUE],
        xaxis: {
            categories: data.serviceTypes.labels,
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
    renderPortfolio(data);
    renderCities(data);
    renderServiceTypes(data);
});
