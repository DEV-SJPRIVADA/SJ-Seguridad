import ApexCharts from 'apexcharts';
import {
    BRAND_BLUE,
    BRAND_NAVY,
    DONUT_COLORS,
    STATUS_DONUT_COLORS,
    STATUS_NEUTRAL,
    sharedChart,
} from './charts/apex-defaults';

const chartInstances = {
    tendencia: null,
    estado: null,
    dias: null,
};

function destroyChart(key) {
    if (chartInstances[key]) {
        chartInstances[key].destroy();
        chartInstances[key] = null;
    }
}

function renderTendencia(data) {
    const el = document.querySelector('#cliente-interno-chart-tendencia');
    if (!el) {
        return;
    }
    destroyChart('tendencia');
    const labels = data?.labels?.length ? data.labels : ['Sin datos'];
    const series = data?.labels?.length ? data.data.map(Number) : [0];
    chartInstances.tendencia = new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'bar', height: '100%' },
        series: [{ name: 'Solicitudes', data: series }],
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
    chartInstances.tendencia.render();
}

function renderEstado(data) {
    const el = document.querySelector('#cliente-interno-chart-estado');
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
        colors: hasValues ? DONUT_COLORS.concat(STATUS_DONUT_COLORS) : [STATUS_NEUTRAL],
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

function renderDias(data) {
    const el = document.querySelector('#cliente-interno-chart-dias');
    if (!el) {
        return;
    }
    destroyChart('dias');
    const labels = data?.labels?.length ? data.labels : ['Sin datos'];
    const series = data?.labels?.length ? data.data.map(Number) : [0];
    chartInstances.dias = new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'bar', height: '100%' },
        series: [{ name: 'Solicitudes', data: series }],
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
    chartInstances.dias.render();
}

window.renderClienteInternoDashboardCharts = function renderClienteInternoDashboardCharts(charts) {
    renderTendencia(charts?.tendencia_mensual || { labels: [], data: [] });
    renderEstado(charts?.por_estado || { labels: [], data: [] });
    renderDias(charts?.dias_respuesta || { labels: [], data: [] });
};
