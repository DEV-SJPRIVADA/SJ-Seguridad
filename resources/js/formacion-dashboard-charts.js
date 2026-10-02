import ApexCharts from 'apexcharts';
import {
    BRAND_BLUE,
    BRAND_NAVY,
    STATUS_GREEN,
    STATUS_NEUTRAL,
    STATUS_RED,
    sharedChart,
} from './charts/apex-defaults';

const chartInstances = {
    mes: null,
    categoria: null,
    estado: null,
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

function renderEstado(data) {
    const el = document.querySelector('#formacion-chart-estado');
    if (!el) {
        return;
    }
    destroyChart('estado');
    const labels = data?.labels?.length ? data.labels : ['Sin datos'];
    const series = data?.labels?.length ? data.data.map(Number) : [0];
    const hasValues = series.some((value) => Number(value) > 0);

    chartInstances.estado = new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'donut', height: '100%' },
        series: hasValues ? series : [1],
        labels: hasValues ? labels : ['Sin datos'],
        colors: hasValues ? [STATUS_GREEN, STATUS_RED, STATUS_NEUTRAL] : [STATUS_NEUTRAL],
        legend: {
            position: 'bottom',
            fontSize: '12px',
        },
        dataLabels: {
            enabled: hasValues,
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '62%',
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
    chartInstances.estado.render();
}

/**
 * @param {{
 *   por_mes?: { labels: string[], data: number[] },
 *   por_categoria?: { labels: string[], data: number[] },
 *   por_estado?: { labels: string[], data: number[] }
 * } | null | undefined} charts
 */
window.renderFormacionDashboardCharts = function renderFormacionDashboardCharts(charts) {
    renderMes(charts?.por_mes || { labels: [], data: [] });
    renderEstado(charts?.por_estado || { labels: [], data: [] });
    renderCategoria(charts?.por_categoria || { labels: [], data: [] });
};
