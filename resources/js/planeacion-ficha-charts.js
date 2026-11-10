import ApexCharts from 'apexcharts';
import { sharedChart } from './charts/apex-defaults';

const BAR_COLORS = ['#2f6fd9', '#78b63f', '#0d9488', '#ca8a04'];
const LINE_RESULT = '#d12f2f';
const LINE_META = '#444444';

function parseDataChart(el) {
    if (!el || !el.dataset.chart) {
        return {};
    }

    try {
        return JSON.parse(el.dataset.chart);
    } catch {
        return {};
    }
}

function numericLabel(value) {
    const n = Number(value);

    if (!Number.isFinite(n)) {
        return '';
    }

    return n.toLocaleString('es-CO', { maximumFractionDigits: 2 });
}

function xyPoints(categories, values) {
    return (categories || []).map((label, index) => {
        const raw = Array.isArray(values) ? values[index] : null;
        const n = raw === null || raw === undefined || raw === '' ? null : Number(raw);

        return {
            x: String(label),
            y: Number.isFinite(n) ? n : null,
        };
    });
}

function boot() {
    const el = document.getElementById('planeacion-ficha-chart');
    if (!el) {
        return;
    }

    const payload = parseDataChart(el);
    const categories = Array.isArray(payload.months) ? payload.months.map((label) => String(label)) : [];
    const fields = Array.isArray(payload.fields) ? payload.fields : [];
    const barSeries = fields.map((field, index) => ({
        name: field.label || field.key || `Campo ${index + 1}`,
        type: 'column',
        data: xyPoints(categories, field.data || []),
        color: BAR_COLORS[index % BAR_COLORS.length],
    }));
    const lineSeries = [
        {
            name: 'Resultado',
            type: 'line',
            data: xyPoints(categories, payload.result || []),
            color: LINE_RESULT,
            strokeWidth: 3,
        },
        {
            name: 'Meta',
            type: 'line',
            data: xyPoints(categories, payload.meta || []),
            color: LINE_META,
            strokeWidth: 2,
            dashArray: 6,
        },
    ];

    const series = [
        ...barSeries.map(({ name, type, data }) => ({ name, type, data })),
        ...lineSeries.map(({ name, type, data }) => ({ name, type, data })),
    ];

    const yaxis = [];
    barSeries.forEach((item, index) => {
        yaxis.push({
            seriesName: item.name,
            show: index === 0,
            title:
                index === 0
                    ? {
                          text: 'Valor',
                          rotate: -90,
                          offsetX: -8,
                          offsetY: 0,
                          style: { fontSize: '12px', fontWeight: 600, color: '#475569' },
                      }
                    : undefined,
            labels: {
                style: { fontSize: '11px' },
                offsetX: 0,
                minWidth: 40,
                align: 'right',
                padding: 8,
            },
            min: 0,
            forceNiceScale: true,
        });
    });
    lineSeries.forEach((item, index) => {
        yaxis.push({
            seriesName: item.name,
            opposite: true,
            show: index === 0,
            title:
                index === 0
                    ? {
                          text: '%',
                          rotate: 90,
                          offsetX: 8,
                          offsetY: 0,
                          style: { fontSize: '12px', fontWeight: 600, color: '#475569' },
                      }
                    : undefined,
            min: 0,
            max: Number(payload.y_right_max || 100),
            labels: {
                style: { fontSize: '11px' },
                offsetX: 0,
                minWidth: 32,
                align: 'left',
                padding: 8,
            },
        });
    });

    const chart = new ApexCharts(el, {
        ...sharedChart,
        chart: {
            ...sharedChart.chart,
            type: 'line',
            width: '100%',
            height: 380,
            stacked: false,
            parentHeightOffset: 0,
        },
        title: payload.title
            ? {
                  text: payload.title,
                  align: 'center',
                  margin: 14,
                  style: { fontSize: '16px', fontWeight: 700 },
              }
            : undefined,
        series,
        stroke: {
            width: [...barSeries.map(() => 0), ...lineSeries.map((item) => item.strokeWidth ?? 3)],
            curve: 'straight',
            dashArray: [...barSeries.map(() => 0), ...lineSeries.map((item) => item.dashArray ?? 0)],
        },
        markers: {
            size: [...barSeries.map(() => 0), ...lineSeries.map(() => 4)],
            strokeWidth: 0,
        },
        plotOptions: {
            bar: {
                columnWidth: '45%',
                borderRadius: 4,
                dataLabels: { position: 'top' },
            },
        },
        colors: [...barSeries.map((item) => item.color), ...lineSeries.map((item) => item.color)],
        dataLabels: {
            enabled: true,
            enabledOnSeries: series.map((_, index) => index),
            offsetY: -8,
            background: { enabled: false },
            style: {
                fontSize: '11px',
                fontWeight: 700,
                colors: ['#0f172a'],
            },
            formatter(value) {
                if (value === null || value === undefined || value === '') {
                    return '';
                }

                const n = Number(value);
                if (!Number.isFinite(n) || n === 0) {
                    return '';
                }

                return numericLabel(value);
            },
        },
        grid: {
            ...sharedChart.grid,
            padding: { top: 36, right: 36, left: 36, bottom: 28 },
        },
        xaxis: {
            type: 'category',
            categories,
            tickAmount: categories.length || 12,
            labels: { style: { fontSize: '11px', fontWeight: 600 } },
        },
        yaxis,
        legend: {
            position: 'bottom',
            fontSize: '11px',
            offsetY: 6,
            itemMargin: { horizontal: 10, vertical: 4 },
        },
        tooltip: {
            shared: true,
            intersect: false,
        },
    });

    chart.render().then(() => {
        chart.resize();
    });

    window.addEventListener('resize', () => {
        chart.resize();
    });
}

document.addEventListener('DOMContentLoaded', boot);
