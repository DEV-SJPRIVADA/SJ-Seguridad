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
    solicitud: null,
    cliente: null,
    responsable: null,
    trend: null,
};

/** Filas visibles en los rankings (cliente / responsable). */
const MAX_RANKING_ROWS = 8;

/** Paleta del donut de estado de solicitud (se repite si hay más estados). */
const SOLICITUD_PALETTE = [
    STATUS_GREEN,
    BRAND_BLUE,
    STATUS_ORANGE,
    STATUS_RED,
    BRAND_NAVY,
    '#0d9488',
    '#7c3aed',
    '#ca8a04',
    STATUS_NEUTRAL,
];

function destroyChart(key) {
    if (chartInstances[key]) {
        chartInstances[key].destroy();
        chartInstances[key] = null;
    }
}

/** Celular: etiquetas y leyendas más compactas. */
function isMobileChartViewport() {
    return window.matchMedia('(max-width: 639px)').matches;
}

/** Recorta nombres largos de cliente / responsable en el eje de categorías. */
function truncateLabel(value, max = 24) {
    const label = String(value ?? '').trim();

    if (label.length <= max) {
        return label;
    }

    return `${label.slice(0, max - 1)}…`;
}

/**
 * Etiquetas numéricas sin caja opaca (no tapan barras).
 * La legibilidad va por trazo blanco en CSS (.seleccion-dashboard-page).
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
 * Rota etiquetas a vertical (−90°) y las sube para que queden sobre el punto/barra.
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

/** Donut: distribución de exámenes por estado de solicitud. */
function renderSolicitud(data) {
    const el = document.querySelector('#seleccion-chart-solicitud');
    if (!el) {
        return;
    }
    destroyChart('solicitud');

    const hasLabels = Boolean(data?.labels?.length);
    const labels = hasLabels ? data.labels : ['Sin datos'];
    const series = hasLabels ? data.data.map(Number) : [1];
    const hasValues = hasLabels && series.some((value) => Number(value) > 0);
    const mobile = isMobileChartViewport();
    const total = series.reduce((acc, value) => acc + Number(value || 0), 0);
    const colors = hasValues
        ? labels.map((_, index) => SOLICITUD_PALETTE[index % SOLICITUD_PALETTE.length])
        : [STATUS_NEUTRAL];

    chartInstances.solicitud = new ApexCharts(el, {
        ...sharedChart,
        chart: {
            ...sharedChart.chart,
            type: 'donut',
            height: '100%',
            parentHeightOffset: 0,
        },
        series: hasValues ? series : [1],
        labels: hasValues ? labels : ['Sin datos'],
        colors,
        stroke: { width: 0 },
        legend: {
            position: 'bottom',
            fontSize: mobile ? '10px' : '11px',
            itemMargin: { horizontal: 6, vertical: 2 },
            markers: { size: 6 },
        },
        dataLabels: {
            // Con muchos estados las etiquetas se pisan: solo se muestran si son pocos.
            enabled: hasValues && labels.length <= 6,
            style: {
                fontSize: mobile ? '10px' : '11px',
                fontWeight: 600,
            },
            dropShadow: { enabled: false },
            formatter(val) {
                const n = Number(val);

                return total > 0 ? `${Math.round((n / total) * 100)}%` : '';
            },
        },
        plotOptions: {
            pie: {
                donut: {
                    size: mobile ? '55%' : '60%',
                    labels: {
                        show: true,
                        name: {
                            show: true,
                            fontSize: mobile ? '11px' : '12px',
                            color: '#64748b',
                        },
                        value: {
                            show: true,
                            fontSize: mobile ? '18px' : '22px',
                            fontWeight: 700,
                            color: '#0f172a',
                        },
                        total: {
                            show: true,
                            label: 'Total',
                            fontSize: mobile ? '11px' : '12px',
                            color: '#64748b',
                            formatter() {
                                return hasValues ? total.toLocaleString('es-CO') : '0';
                            },
                        },
                    },
                },
            },
        },
        tooltip: { enabled: hasValues },
    });
    chartInstances.solicitud.render();
}

/** Barras horizontales: ranking de clientes / responsables. */
function renderRanking(selector, key, data, color, seriesName) {
    const el = document.querySelector(selector);
    if (!el) {
        return;
    }
    destroyChart(key);

    const hasLabels = Boolean(data?.labels?.length);
    const mobile = isMobileChartViewport();
    const labels = (hasLabels ? data.labels : ['Sin datos'])
        .slice(0, MAX_RANKING_ROWS)
        .map((label) => truncateLabel(label, mobile ? 16 : 24));
    const series = (hasLabels ? data.data.map(Number) : [0]).slice(0, MAX_RANKING_ROWS);
    const hasValues = hasLabels && series.some((value) => Number(value) > 0);
    const rowCount = Math.max(labels.length, 1);
    // Altura justa al número de filas (sin espacio muerto dentro del panel).
    const chartHeight = Math.max(mobile ? 230 : 250, rowCount * (mobile ? 28 : 26) + (mobile ? 46 : 36));
    el.style.height = `${chartHeight}px`;

    chartInstances[key] = new ApexCharts(el, {
        ...sharedChart,
        chart: {
            ...sharedChart.chart,
            type: 'bar',
            height: chartHeight,
            parentHeightOffset: 0,
        },
        series: [{ name: seriesName, data: series }],
        plotOptions: {
            bar: {
                horizontal: true,
                borderRadius: 6,
                barHeight: '58%',
                dataLabels: { position: 'top' },
            },
        },
        dataLabels: {
            ...numericDataLabels(hasValues),
            offsetX: 6,
            style: {
                fontSize: mobile ? '10px' : '11px',
                fontWeight: 700,
                colors: ['#0f172a'],
            },
        },
        colors: [color],
        xaxis: {
            categories: labels,
            labels: { style: { fontSize: mobile ? '9px' : '11px' } },
            min: 0,
            forceNiceScale: true,
        },
        yaxis: {
            labels: { style: { fontSize: mobile ? '9px' : '11px' } },
        },
        grid: {
            ...sharedChart.grid,
            padding: { top: 0, right: 22, left: 0, bottom: 0 },
        },
        legend: { show: false },
        tooltip: { enabled: hasValues },
    });
    chartInstances[key].render();
}

/** Área: tendencia mensual de ingresos. */
function renderTrend(data) {
    const el = document.querySelector('#seleccion-chart-trend');
    if (!el) {
        return;
    }
    destroyChart('trend');

    const hasLabels = Boolean(data?.labels?.length);
    const labels = hasLabels ? data.labels : ['Sin datos'];
    const series = hasLabels ? (data.data || []).map(Number) : [0];
    const hasValues = hasLabels && series.some((value) => Number(value) > 0);
    const mobile = isMobileChartViewport();
    const vertical = !mobile && labels.length > 6;
    // Muchos meses saturan el gráfico: las etiquetas solo aparecen en rangos cortos.
    const showDataLabels = hasValues && labels.length <= 12;

    chartInstances.trend = new ApexCharts(el, {
        ...sharedChart,
        chart: {
            ...sharedChart.chart,
            type: 'area',
            height: '100%',
            parentHeightOffset: 0,
            events: vertical && showDataLabels ? verticalLabelEvents() : {},
        },
        grid: {
            ...sharedChart.grid,
            padding: {
                top: showDataLabels ? (vertical ? 22 : 18) : 10,
                right: mobile ? 4 : 8,
                left: mobile ? 0 : 4,
                bottom: 0,
            },
        },
        series: [{ name: 'Ingresos', data: series }],
        colors: [BRAND_BLUE],
        stroke: { curve: 'smooth', width: 3 },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 0.2,
                opacityFrom: 0.42,
                opacityTo: 0.04,
                stops: [0, 95],
            },
        },
        markers: { size: hasValues ? (mobile ? 3 : 4) : 0 },
        dataLabels: {
            ...numericDataLabels(showDataLabels),
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
    chartInstances.trend.render();
}

export function renderSeleccionDashboardCharts(charts) {
    if (!charts) {
        return;
    }
    renderSolicitud(charts.by_solicitud_status || { labels: [], data: [] });
    renderRanking(
        '#seleccion-chart-cliente',
        'cliente',
        charts.by_client || { labels: [], data: [] },
        BRAND_NAVY,
        'Registros',
    );
    renderRanking(
        '#seleccion-chart-responsable',
        'responsable',
        charts.by_responsable || { labels: [], data: [] },
        BRAND_BLUE,
        'Registros',
    );
    renderTrend(charts.ingresos_trend || { labels: [], data: [] });
}

window.renderSeleccionDashboardCharts = renderSeleccionDashboardCharts;

// Avisa a Alpine que el módulo Vite ya expuso el renderer (evita primer paint vacío).
window.dispatchEvent(new CustomEvent('seleccion-dashboard-charts-ready'));
