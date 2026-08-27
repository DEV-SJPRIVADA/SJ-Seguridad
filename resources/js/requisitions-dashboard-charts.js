import ApexCharts from 'apexcharts';
import {
    BRAND_BLUE,
    BRAND_NAVY,
    STATUS_DONUT_COLORS,
    sharedChart,
} from './charts/apex-defaults';

/** @type {ApexCharts[]} */
const chartInstances = [];

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

function mountChart(el, options) {
    const chart = new ApexCharts(el, options);
    chart.render();
    chartInstances.push(chart);

    return chart;
}

function renderTrend(data) {
    const el = document.querySelector('#trendChart');
    if (!el) {
        return null;
    }

    return mountChart(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'area', height: '100%' },
        series: [{ name: 'Solicitudes', data: data.trend.data }],
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
    });
}

function renderStatus(data) {
    const el = document.querySelector('#statusChart');
    if (!el) {
        return null;
    }

    return mountChart(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'donut', height: '100%' },
        series: data.status.data.map(Number),
        labels: data.status.labels,
        colors: STATUS_DONUT_COLORS,
        legend: {
            position: 'bottom',
            horizontalAlign: 'center',
            fontSize: '10px',
            itemMargin: { horizontal: 6, vertical: 2 },
            height: 52,
            offsetY: 0,
        },
        stroke: { width: 0 },
        plotOptions: {
            pie: {
                donut: {
                    size: '62%',
                },
            },
        },
    });
}

function renderCities(data) {
    const el = document.querySelector('#cityChart');
    if (!el) {
        return null;
    }

    return mountChart(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'bar', height: '100%' },
        series: [{ name: 'Solicitudes', data: data.cities.data }],
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
    });
}

function renderClients(data) {
    const el = document.querySelector('#clientChart');
    if (!el) {
        return null;
    }

    return mountChart(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'bar', height: '100%' },
        series: [{ name: 'Solicitudes', data: data.clients.data }],
        plotOptions: {
            bar: {
                horizontal: false,
                borderRadius: 8,
                columnWidth: '55%',
            },
        },
        colors: [BRAND_BLUE],
        xaxis: {
            categories: data.clients.labels,
            labels: {
                style: { fontSize: '11px' },
                trim: true,
                maxHeight: 48,
            },
        },
        yaxis: {
            labels: { style: { fontSize: '11px' } },
            min: 0,
            forceNiceScale: true,
        },
        legend: { show: false },
    });
}

function bindAutoSubmitFilters() {
    const filterForm = document.querySelector('.dashboard-filters');
    if (!filterForm) {
        return;
    }

    filterForm.addEventListener('change', () => {
        filterForm.submit();
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
