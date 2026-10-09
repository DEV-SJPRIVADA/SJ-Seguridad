import ApexCharts from 'apexcharts';
import {
    STATUS_GREEN,
    STATUS_NEUTRAL,
    STATUS_ORANGE,
    STATUS_RED,
    sharedChart,
} from './charts/apex-defaults';

const chartInstances = {
    examen1: null,
    examen2: null,
    aptos: null,
};

const ESTADO_COLORS = [STATUS_GREEN, STATUS_ORANGE, STATUS_RED];
const APTO_COLORS = [STATUS_GREEN, STATUS_RED, STATUS_NEUTRAL];

function destroyChart(key) {
    if (chartInstances[key]) {
        chartInstances[key].destroy();
        chartInstances[key] = null;
    }
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

    chartInstances[key] = new ApexCharts(el, {
        ...sharedChart,
        chart: { ...sharedChart.chart, type: 'donut', height: '100%' },
        series: hasValues ? series : [1],
        labels: hasValues ? labels : ['Sin datos'],
        colors: hasValues ? colors : [STATUS_NEUTRAL],
        legend: {
            position: 'bottom',
            fontSize: '12px',
        },
        dataLabels: {
            enabled: hasValues,
        },
        stroke: { width: 0 },
        plotOptions: {
            pie: {
                donut: {
                    size: '58%',
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
    chartInstances[key].render();
}

export function renderMtSt04DashboardCharts(charts) {
    if (!charts) {
        return;
    }
    renderDonut('#mt-st-04-chart-examen1', 'examen1', charts.examen1_estados || { labels: [], data: [] }, ESTADO_COLORS);
    renderDonut('#mt-st-04-chart-examen2', 'examen2', charts.examen2_estados || { labels: [], data: [] }, ESTADO_COLORS);
    renderDonut('#mt-st-04-chart-aptos', 'aptos', charts.aptos || { labels: [], data: [] }, APTO_COLORS);
}

window.renderMtSt04DashboardCharts = renderMtSt04DashboardCharts;
