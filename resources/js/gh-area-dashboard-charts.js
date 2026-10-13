import ApexCharts from 'apexcharts';
import {
    BRAND_BLUE,
    BRAND_NAVY,
    STATUS_GREEN,
    STATUS_NEUTRAL,
    STATUS_ORANGE,
    STATUS_RED,
    sharedChart,
} from './charts/apex-defaults';

const chartInstances = {
    evolucionPersonal: null,
    ingresosVsDesvinculaciones: null,
    evolucionFormacion: null,
    personalPorCargos: null,
    estadoPsicofisicos: null,
    estadoAcreditacion: null,
    consultasArchivo: null,
};

function destroyChart(key) {
    if (chartInstances[key]) {
        chartInstances[key].destroy();
        chartInstances[key] = null;
    }
}

/**
 * Etiquetas numéricas sin caja opaca (no tapan barras).
 * La legibilidad va por trazo blanco en CSS (.gh-area-dashboard-page).
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

/**
 * Rota etiquetas a vertical (−90°) y las sube para que queden sobre la barra/punto.
 * ApexCharts no expone rotate nativo en dataLabels.
 */
function rotateDataLabelsVertical(chartContext) {
    const root = chartContext?.w?.dom?.baseEl || chartContext?.el;
    if (!root) {
        return;
    }

    const stripLabelTransform = (node) => {
        const current = node.getAttribute('transform') || '';

        return current
            .replace(/\s*rotate\([^)]*\)/g, '')
            .replace(/\s*translate\([^)]*\)/g, '')
            .trim();
    };

    root.querySelectorAll('.apexcharts-datalabels text').forEach((text) => {
        let bbox;
        try {
            bbox = text.getBBox();
        } catch {
            return;
        }

        if (!bbox.width && !bbox.height) {
            return;
        }

        const cx = bbox.x + bbox.width / 2;
        const cy = bbox.y + bbox.height / 2;
        // Tras −90° alrededor del centro, subir solo media altura del texto + holgura mínima.
        const lift = bbox.width / 2 + 3;
        const transform = `${stripLabelTransform(text)} translate(0 ${-lift}) rotate(-90 ${cx} ${cy})`.trim();

        text.setAttribute('transform', transform);

        const rect = text.previousElementSibling;
        if (rect && rect.tagName.toLowerCase() === 'rect') {
            rect.setAttribute('transform', `${stripLabelTransform(rect)} translate(0 ${-lift}) rotate(-90 ${cx} ${cy})`.trim());
            rect.setAttribute('fill-opacity', '0');
        }
    });
}

/** Eventos chart para aplicar rotación vertical de etiquetas. */
function verticalLabelEvents() {
    const apply = (chartContext) => {
        requestAnimationFrame(() => rotateDataLabelsVertical(chartContext));
    };

    return {
        mounted: apply,
        updated: apply,
        animationEnd: apply,
    };
}

/** Celular: etiquetas horizontales (evita recorte); tablet/desktop: verticales. */
function isMobileChartViewport() {
    return window.matchMedia('(max-width: 639px)').matches;
}

/** Línea: evolución de activos EOM por mes. */
function renderLine(elSelector, key, data, color, seriesName) {
    const el = document.querySelector(elSelector);
    if (!el) {
        return;
    }
    destroyChart(key);
    const labels = data?.labels?.length ? data.labels : ['Sin datos'];
    const series = data?.labels?.length ? data.data.map(Number) : [0];
    const hasValues = data?.labels?.length > 0;
    const mobile = isMobileChartViewport();
    const vertical = !mobile;

    chartInstances[key] = new ApexCharts(el, {
        ...sharedChart,
        chart: {
            ...sharedChart.chart,
            type: 'line',
            height: '100%',
            parentHeightOffset: 0,
            events: vertical ? verticalLabelEvents() : {},
        },
        grid: {
            ...sharedChart.grid,
            padding: {
                top: vertical ? 18 : 14,
                right: mobile ? 4 : 6,
                left: mobile ? 0 : 4,
                bottom: 0,
            },
        },
        series: [{ name: seriesName, data: series }],
        colors: [color],
        stroke: { curve: 'smooth', width: 3 },
        markers: { size: hasValues ? (mobile ? 3 : 4) : 0 },
        dataLabels: {
            ...numericDataLabels(hasValues),
            offsetY: vertical ? -2 : -10,
            style: {
                fontSize: mobile ? '9px' : '11px',
                fontWeight: 700,
                colors: ['#0f172a'],
            },
        },
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
            labels: { style: { fontSize: mobile ? '9px' : '11px' } },
            min: 0,
            forceNiceScale: true,
        },
        legend: { show: false },
        tooltip: { enabled: hasValues },
    });
    chartInstances[key].render();
}

/** Barras agrupadas: ingresos vs desvinculaciones. */
function renderIngresosVsDesvinculaciones(data) {
    const el = document.querySelector('#gh-area-chart-ingresos-vs-desvinculaciones');
    if (!el) {
        return;
    }
    destroyChart('ingresosVsDesvinculaciones');
    const labels = data?.labels?.length ? data.labels : ['Sin datos'];
    const ingresos = data?.labels?.length ? (data.ingresos || []).map(Number) : [0];
    const desvinculaciones = data?.labels?.length ? (data.desvinculaciones || []).map(Number) : [0];
    const hasValues = Boolean(data?.labels?.length);
    const mobile = isMobileChartViewport();
    const vertical = !mobile;

    chartInstances.ingresosVsDesvinculaciones = new ApexCharts(el, {
        ...sharedChart,
        chart: {
            ...sharedChart.chart,
            type: 'bar',
            height: '100%',
            parentHeightOffset: 0,
            events: vertical ? verticalLabelEvents() : {},
        },
        grid: {
            ...sharedChart.grid,
            padding: {
                top: vertical ? 20 : 16,
                right: mobile ? 4 : 6,
                left: mobile ? 0 : 4,
                bottom: 0,
            },
        },
        series: [
            { name: 'Ingresos', data: ingresos },
            { name: 'Desvinculaciones', data: desvinculaciones },
        ],
        plotOptions: {
            bar: {
                horizontal: false,
                borderRadius: 4,
                columnWidth: mobile ? '70%' : '55%',
                dataLabels: { position: 'top' },
            },
        },
        dataLabels: {
            ...numericDataLabels(hasValues),
            offsetY: vertical ? 0 : -12,
            style: {
                fontSize: mobile ? '8px' : '11px',
                fontWeight: 700,
                colors: ['#0f172a'],
            },
        },
        colors: [STATUS_GREEN, STATUS_RED],
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
            labels: { style: { fontSize: mobile ? '9px' : '11px' } },
            min: 0,
            forceNiceScale: true,
        },
        legend: {
            position: 'bottom',
            fontSize: mobile ? '11px' : '12px',
            itemMargin: { horizontal: 8, vertical: 0 },
        },
    });
    chartInstances.ingresosVsDesvinculaciones.render();
}

/** Barras horizontales: personal por concepto / área. */
function renderPorCargos(data) {
    const el = document.querySelector('#gh-area-chart-personal-por-concepto');
    if (!el) {
        return;
    }
    destroyChart('personalPorCargos');
    const labels = data?.labels?.length ? data.labels : ['Sin datos'];
    const series = data?.labels?.length ? data.data.map(Number) : [0];
    const hasValues = Boolean(data?.labels?.length);
    const rowCount = Math.max(labels.length, 1);
    const mobile = isMobileChartViewport();
    // Altura justa al número de filas; en celular un poco más por labels del eje.
    const chartHeight = Math.max(mobile ? 240 : 160, rowCount * (mobile ? 30 : 26) + (mobile ? 48 : 36));
    el.style.height = `${chartHeight}px`;

    chartInstances.personalPorCargos = new ApexCharts(el, {
        ...sharedChart,
        chart: {
            ...sharedChart.chart,
            type: 'bar',
            height: chartHeight,
            parentHeightOffset: 0,
        },
        series: [{ name: 'Activos', data: series }],
        plotOptions: {
            bar: {
                horizontal: true,
                borderRadius: 6,
                barHeight: '60%',
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
        legend: { show: false },
        grid: {
            ...sharedChart.grid,
            padding: { top: 0, right: 16, left: 0, bottom: 0 },
        },
    });
    chartInstances.personalPorCargos.render();
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
        chart: {
            ...sharedChart.chart,
            type: 'donut',
            height: '100%',
            parentHeightOffset: 0,
        },
        series: hasValues ? series : [1],
        labels: hasValues ? labels : ['Sin datos'],
        colors: hasValues ? colors : [STATUS_NEUTRAL],
        legend: {
            position: 'bottom',
            fontSize: isMobileChartViewport() ? '10px' : '11px',
            offsetY: 0,
            itemMargin: { horizontal: 6, vertical: 2 },
            formatter(seriesName) {
                return seriesName;
            },
        },
        dataLabels: {
            enabled: hasValues,
            style: {
                fontSize: isMobileChartViewport() ? '10px' : '12px',
            },
        },
        stroke: { width: 0 },
        plotOptions: {
            pie: {
                donut: {
                    size: isMobileChartViewport() ? '55%' : '58%',
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

window.renderGhAreaDashboardCharts = function renderGhAreaDashboardCharts(charts) {
    renderLine(
        '#gh-area-chart-evolucion-personal',
        'evolucionPersonal',
        charts?.evolucion_personal || { labels: [], data: [] },
        BRAND_BLUE,
        'Personal activo',
    );
    renderIngresosVsDesvinculaciones(charts?.ingresos_vs_desvinculaciones || {
        labels: [],
        ingresos: [],
        desvinculaciones: [],
    });
    renderLine(
        '#gh-area-chart-evolucion-formacion',
        'evolucionFormacion',
        charts?.evolucion_formacion || { labels: [], data: [] },
        BRAND_NAVY,
        'Personas',
    );
    renderPorCargos(charts?.personal_por_concepto || { labels: [], data: [] });
    renderDonut(
        '#gh-area-chart-estado-psicofisicos',
        'estadoPsicofisicos',
        charts?.estado_psicofisicos || { labels: [], data: [] },
        [STATUS_GREEN, STATUS_ORANGE, STATUS_RED],
    );
    renderDonut(
        '#gh-area-chart-estado-acreditacion',
        'estadoAcreditacion',
        charts?.estado_acreditacion || { labels: [], data: [] },
        [STATUS_GREEN, STATUS_ORANGE, BRAND_BLUE],
    );
    renderDonut(
        '#gh-area-chart-consultas-archivo',
        'consultasArchivo',
        charts?.consultas_archivo || { labels: [], data: [] },
        [BRAND_BLUE, BRAND_NAVY, STATUS_GREEN, STATUS_ORANGE, STATUS_RED, STATUS_NEUTRAL, '#0d9488', '#7c3aed'],
    );
};

// Avisa a Alpine que el módulo Vite ya expuso el renderer (evita primer paint vacío).
window.dispatchEvent(new CustomEvent('gh-area-dashboard-charts-ready'));
