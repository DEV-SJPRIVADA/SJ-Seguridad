import ApexCharts from 'apexcharts';
import {
    BRAND_BLUE,
    BRAND_NAVY,
    sharedChart,
} from './charts/apex-defaults';

const chartInstances = {
    mes: null,
    categoria: null,
};

function destroyChart(key) {
    if (chartInstances[key]) {
        chartInstances[key].destroy();
        chartInstances[key] = null;
    }
}

function renderMes(data) {
    const el = document.querySelector('#formacion-chart-mes');
    if (!el) {
        return;
    }
    destroyChart('mes');
    const labels = data?.labels?.length ? data.labels : ['Sin datos'];
    const series = data?.labels?.length ? data.data.map(Number) : [0];
    chartInstances.mes = new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'bar', height: '100%' },
        series: [{ name: 'Formaciones', data: series }],
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
    chartInstances.mes.render();
}

function renderCategoria(data) {
    const el = document.querySelector('#formacion-chart-categoria');
    if (!el) {
        return;
    }
    destroyChart('categoria');
    const labels = data?.labels?.length ? data.labels : ['Sin datos'];
    const series = data?.labels?.length ? data.data.map(Number) : [0];
    chartInstances.categoria = new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'bar', height: '100%' },
        series: [{ name: 'Formaciones', data: series }],
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
    chartInstances.categoria.render();
}

/**
 * @param {{ por_mes?: { labels: string[], data: number[] }, por_categoria?: { labels: string[], data: number[] } } | null | undefined} charts
 */
window.renderFormacionDashboardCharts = function renderFormacionDashboardCharts(charts) {
    renderMes(charts?.por_mes || { labels: [], data: [] });
    renderCategoria(charts?.por_categoria || { labels: [], data: [] });
};
