import ApexCharts from 'apexcharts';
import { BRAND_BLUE, BRAND_NAVY, sharedChart } from './charts/apex-defaults';

const BAR_DENOM = '#2f6fd9';
const BAR_NUM = '#78b63f';
const LINE_RESULT = '#d12f2f';
const LINE_META = '#444444';

const MONTH_LABELS = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];

function readChartPayload() {
    const el = document.getElementById('management-report-chart-data');
    if (!el) {
        return null;
    }

    try {
        return JSON.parse(el.textContent || '{}');
    } catch {
        return null;
    }
}

function chartElementId(code) {
    return `mgmt-chart-${code.toLowerCase().replace(/[^a-z0-9]+/g, '-')}`;
}

function normalizePercentages(values, scale) {
    return (values || []).map((value) => {
        if (value === null || value === undefined) {
            return 0;
        }

        return Number(value);
    });
}

function metaLine(meta, scale) {
    const value = Number(meta || 0);

    return Array(12).fill(value);
}

function renderIndicatorChart(container, series, year) {
    const isCount = series.percentage_scale === 'count';
    const resultLabel = isCount ? 'Resultado' : '% Cumplimiento';
    const rightAxisTitle = isCount ? 'Cantidad' : '%';
    const rightMax = isCount ? undefined : 100;
    const percentages = normalizePercentages(series.percentages, series.percentage_scale);

    const chart = new ApexCharts(container, {
        ...sharedChart,
        chart: {
            ...sharedChart.chart,
            type: 'line',
            height: 280,
            stacked: false,
        },
        title: {
            text: `Serie mensual ${year}`,
            align: 'center',
            style: { fontSize: '13px', fontWeight: 600 },
        },
        series: [
            {
                name: 'Denominador',
                type: 'column',
                data: series.denominators || [],
            },
            {
                name: 'Numerador',
                type: 'column',
                data: series.numerators || [],
            },
            {
                name: resultLabel,
                type: 'line',
                data: percentages,
            },
            {
                name: 'Meta',
                type: 'line',
                data: metaLine(series.meta, series.percentage_scale),
            },
        ],
        colors: [BAR_DENOM, BAR_NUM, LINE_RESULT, LINE_META],
        stroke: {
            width: [0, 0, 3, 2],
            curve: 'smooth',
            dashArray: [0, 0, 0, 6],
        },
        plotOptions: {
            bar: {
                columnWidth: '42%',
                borderRadius: 4,
            },
        },
        xaxis: {
            categories: MONTH_LABELS,
            labels: { style: { fontSize: '11px', fontWeight: 600 } },
        },
        yaxis: [
            {
                seriesName: 'Denominador',
                title: { text: 'Valor' },
                labels: { style: { fontSize: '11px' } },
                min: 0,
                forceNiceScale: true,
            },
            {
                seriesName: 'Numerador',
                show: false,
                min: 0,
                forceNiceScale: true,
            },
            {
                seriesName: resultLabel,
                opposite: true,
                title: { text: rightAxisTitle },
                labels: {
                    style: { fontSize: '11px' },
                    formatter: (value) => (isCount ? String(Math.round(value)) : `${Math.round(value * 100) / 100}%`),
                },
                min: 0,
                max: rightMax,
                forceNiceScale: isCount,
            },
            {
                seriesName: 'Meta',
                opposite: true,
                show: false,
                min: 0,
                max: rightMax,
                forceNiceScale: isCount,
            },
        ],
        legend: {
            position: 'bottom',
            fontSize: '11px',
        },
        grid: {
            ...sharedChart.grid,
            padding: { top: 4, right: 8, bottom: 0, left: 8 },
        },
        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: (value, { seriesIndex }) => {
                    if (seriesIndex <= 1) {
                        return Number(value || 0).toLocaleString('es-CO', { maximumFractionDigits: 0 });
                    }

                    if (isCount) {
                        return String(Math.round(value));
                    }

                    return `${Math.round(value * 100) / 100}%`;
                },
            },
        },
    });

    chart.render();
}

function boot() {
    const payload = readChartPayload();
    if (!payload?.indicators) {
        return;
    }

    const year = payload.year || new Date().getFullYear();

    Object.entries(payload.indicators).forEach(([code, series]) => {
        const container = document.getElementById(chartElementId(code));
        if (!container || !series) {
            return;
        }

        renderIndicatorChart(container, series, year);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
