import ApexCharts from 'apexcharts';
import {
    BRAND_BLUE,
    BRAND_NAVY,
    DONUT_COLORS,
    STATUS_BLUE,
    STATUS_GREEN,
    STATUS_NEUTRAL,
    STATUS_ORANGE,
    STATUS_RED,
    sharedChart,
} from './charts/apex-defaults';

const chartInstances = {
    tipo: null,
    vigencia: null,
    estado: null,
    pendientesRenovacion: null,
    trend: null,
};

function isMobileChartViewport() {
    return window.matchMedia('(max-width: 639px)').matches;
}

function hasNumericSeries(values) {
    return Array.isArray(values) && values.some((value) => Number(value) > 0);
}

/**
 * Etiquetas numéricas horizontales sin caja opaca.
 * Halo blanco vía CSS (.cursos-dashboard-page .apexcharts-datalabels).
 */
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

/** Colores por etiqueta de vigencia. */
function colorsForVigenciaLabels(labels) {
    return labels.map((label) => {
        const normalized = String(label || '').toLowerCase();

        if (normalized.includes('vencid')) {
            return STATUS_RED;
        }

        if (normalized.includes('actualiz')) {
            return STATUS_ORANGE;
        }

        if (normalized.includes('vigente')) {
            return STATUS_GREEN;
        }

        return STATUS_NEUTRAL;
    });
}

/** Colores por etiqueta de estado de trámite. */
function colorsForEstadoLabels(labels) {
    return labels.map((label) => {
        const normalized = String(label || '').toLowerCase();

        if (normalized.includes('solicit')) {
            return STATUS_ORANGE;
        }

        if (normalized.includes('pendient')) {
            return STATUS_BLUE;
        }

        if (normalized.includes('actualiz')) {
            return STATUS_GREEN;
        }

        return STATUS_NEUTRAL;
    });
}

function destroyChart(key) {
    if (chartInstances[key]) {
        chartInstances[key].destroy();
        chartInstances[key] = null;
    }
}

function renderTipo(data) {
    const el = document.querySelector('#cursos-chart-tipo');
    if (!el) {
        return;
    }
    destroyChart('tipo');

    const labels = data.labels?.length ? data.labels : ['Sin datos'];
    const series = data.labels?.length ? data.data.map(Number) : [0];
    const hasValues = hasNumericSeries(series);

    chartInstances.tipo = new ApexCharts(el, {
        ...sharedChart,
        chart: {
            ...sharedChart.chart,
            type: 'bar',
            height: '100%',
            parentHeightOffset: 0,
        },
        series: [{ name: 'Cursos', data: series }],
        plotOptions: {
            bar: {
                horizontal: true,
                borderRadius: 8,
                barHeight: '55%',
                dataLabels: { position: 'top' },
            },
        },
        dataLabels: {
            ...numericDataLabels(hasValues),
            offsetX: 6,
        },
        colors: [BRAND_NAVY],
        xaxis: {
            categories: labels,
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
            padding: { top: 0, right: 16, left: 0, bottom: 0 },
        },
        tooltip: { enabled: hasValues },
    });
    chartInstances.tipo.render();
}

function renderVigencia(data) {
    const el = document.querySelector('#cursos-chart-vigencia');
    if (!el) {
        return;
    }
    destroyChart('vigencia');

    const labels = data.labels?.length ? data.labels : ['VIGENTE', 'ACTUALIZAR', 'VENCIDO'];
    const series = data.labels?.length ? data.data.map(Number) : [0, 0, 0];
    const hasValues = hasNumericSeries(series);
    const mobile = isMobileChartViewport();

    chartInstances.vigencia = new ApexCharts(el, {
        ...sharedChart,
        chart: {
            ...sharedChart.chart,
            type: 'donut',
            height: '100%',
            parentHeightOffset: 0,
        },
        series: hasValues ? series : [1],
        labels: hasValues ? labels : ['Sin datos'],
        colors: hasValues ? colorsForVigenciaLabels(labels) : [STATUS_NEUTRAL],
        legend: {
            position: 'bottom',
            horizontalAlign: 'center',
            fontSize: mobile ? '10px' : '11px',
            itemMargin: { horizontal: 6, vertical: 2 },
        },
        dataLabels: {
            enabled: hasValues,
            style: {
                fontSize: mobile ? '10px' : '12px',
            },
        },
        stroke: { width: 0 },
        plotOptions: {
            pie: {
                donut: {
                    size: mobile ? '55%' : '58%',
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
    });
    chartInstances.vigencia.render();
}

function renderEstado(data) {
    const el = document.querySelector('#cursos-chart-estado');
    if (!el) {
        return;
    }
    destroyChart('estado');

    const labels = data.labels?.length ? data.labels : ['Sin datos'];
    const series = data.labels?.length ? data.data.map(Number) : [0];
    const hasValues = hasNumericSeries(series);
    const mobile = isMobileChartViewport();

    chartInstances.estado = new ApexCharts(el, {
        ...sharedChart,
        chart: {
            ...sharedChart.chart,
            type: 'bar',
            height: '100%',
            parentHeightOffset: 0,
        },
        grid: {
            ...sharedChart.grid,
            padding: {
                top: 18,
                right: mobile ? 4 : 6,
                left: mobile ? 0 : 4,
                bottom: 0,
            },
        },
        series: [{ name: 'Cursos', data: series }],
        plotOptions: {
            bar: {
                horizontal: false,
                borderRadius: 6,
                columnWidth: '45%',
                distributed: true,
                dataLabels: { position: 'top' },
            },
        },
        dataLabels: {
            ...numericDataLabels(hasValues),
            offsetY: -12,
            style: {
                fontSize: mobile ? '10px' : '11px',
                fontWeight: 700,
                colors: ['#0f172a'],
            },
        },
        colors: colorsForEstadoLabels(labels),
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
        tooltip: { enabled: hasValues },
    });
    chartInstances.estado.render();
}

function renderPendientesRenovacion(data) {
    const el = document.querySelector('#cursos-chart-pendientes-renovacion');
    if (!el) {
        return;
    }
    destroyChart('pendientesRenovacion');

    const labels = data.labels?.length ? data.labels : ['Sin datos'];
    const actualizar = data.labels?.length ? (data.actualizar || []).map(Number) : [0];
    const vencidos = data.labels?.length ? (data.vencidos || []).map(Number) : [0];
    const hasValues = hasNumericSeries(actualizar) || hasNumericSeries(vencidos);

    chartInstances.pendientesRenovacion = new ApexCharts(el, {
        ...sharedChart,
        chart: {
            ...sharedChart.chart,
            type: 'bar',
            height: '100%',
            stacked: true,
            parentHeightOffset: 0,
        },
        series: [
            { name: 'ACTUALIZAR', data: actualizar },
            { name: 'VENCIDO', data: vencidos },
        ],
        plotOptions: {
            bar: {
                horizontal: true,
                borderRadius: 4,
                barHeight: '55%',
                dataLabels: { position: 'top' },
            },
        },
        dataLabels: {
            ...numericDataLabels(hasValues),
            offsetX: 6,
        },
        colors: [STATUS_ORANGE, STATUS_RED],
        xaxis: {
            categories: labels,
            labels: { style: { fontSize: '11px' } },
            min: 0,
            forceNiceScale: true,
        },
        legend: { position: 'bottom', fontSize: '12px' },
        grid: {
            ...sharedChart.grid,
            padding: { top: 0, right: 16, left: 0, bottom: 0 },
        },
        tooltip: { enabled: hasValues },
    });
    chartInstances.pendientesRenovacion.render();
}

function renderTrend(data) {
    const el = document.querySelector('#cursos-chart-trend');
    if (!el) {
        return;
    }
    destroyChart('trend');

    const labels = data.labels?.length ? data.labels : [];
    const nuevos = (data.nuevos || []).map(Number);
    const actualizados = (data.actualizados || []).map(Number);
    const hasValues = hasNumericSeries(nuevos) || hasNumericSeries(actualizados);
    const mobile = isMobileChartViewport();

    chartInstances.trend = new ApexCharts(el, {
        ...sharedChart,
        chart: {
            ...sharedChart.chart,
            type: 'bar',
            height: '100%',
            stacked: false,
            parentHeightOffset: 0,
        },
        grid: {
            ...sharedChart.grid,
            padding: {
                top: 18,
                right: mobile ? 4 : 6,
                left: mobile ? 0 : 4,
                bottom: 0,
            },
        },
        series: [
            { name: 'Nuevos', data: nuevos },
            { name: 'Actualizaciones', data: actualizados },
        ],
        plotOptions: {
            bar: {
                horizontal: false,
                borderRadius: 4,
                columnWidth: '55%',
                dataLabels: { position: 'top' },
            },
        },
        dataLabels: {
            ...numericDataLabels(hasValues),
            offsetY: -12,
            style: {
                fontSize: mobile ? '9px' : '11px',
                fontWeight: 700,
                colors: ['#0f172a'],
            },
        },
        colors: [BRAND_BLUE, ...DONUT_COLORS.slice(2, 3)],
        xaxis: {
            categories: labels,
            labels: {
                style: { fontSize: mobile ? '9px' : '11px' },
                rotate: mobile ? -45 : 0,
                rotateAlways: mobile,
                hideOverlappingLabels: true,
            },
        },
        yaxis: {
            labels: { style: { fontSize: '11px' } },
            min: 0,
            forceNiceScale: true,
        },
        legend: { position: 'top', fontSize: '12px' },
        tooltip: { enabled: hasValues },
    });
    chartInstances.trend.render();
}

export function renderCursosDashboardCharts(charts) {
    if (!charts) {
        return;
    }
    renderTipo(charts.by_tipo || { labels: [], data: [] });
    renderVigencia(charts.by_vigencia || { labels: ['VIGENTE', 'ACTUALIZAR', 'VENCIDO'], data: [0, 0, 0] });
    renderEstado(charts.by_estado || { labels: [], data: [] });
    renderPendientesRenovacion(charts.pendientes_renovacion_by_tipo || {
        labels: [],
        actualizar: [],
        vencidos: [],
    });
    renderTrend(charts.trend || { labels: [], nuevos: [], actualizados: [] });
}

window.renderCursosDashboardCharts = renderCursosDashboardCharts;
