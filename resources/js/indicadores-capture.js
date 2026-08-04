import ApexCharts from 'apexcharts';
import { BRAND_BLUE, BRAND_NAVY, sharedChart } from './charts/apex-defaults';

const BAR_DENOM = '#2f6fd9';
const BAR_NUM = '#78b63f';
const LINE_RESULT = '#d12f2f';
const LINE_META = '#444444';
const PIE_EMPTY = '#e5e7eb';

function parseJsonAttr(el, name) {
    if (!el) {
        return null;
    }

    try {
        return JSON.parse(el.getAttribute(name) || 'null');
    } catch {
        return null;
    }
}

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

function round2(n) {
    return Math.round(n * 100) / 100;
}

function compareByOperator(result, threshold, operator) {
    if (operator === '<=') {
        return result <= threshold;
    }

    if (operator === '==') {
        return round2(result) === round2(threshold);
    }

    return result >= threshold;
}

function computeMetrics(formula, values) {
    if (!formula || formula.type === 'none') {
        return { result: 0, complies: false };
    }

    if (formula.type === 'ft_op_03') {
        const totalServicios = Number(values.total_servicios || 0);
        const totalSiniestros = Number(values.total_siniestros || 0);
        const facturacion = Number(values.facturacion_mensual || 0);
        const valorPagado = Number(values.valor_pagado_siniestros || 0);
        const freq = totalServicios > 0 ? round2((totalSiniestros / totalServicios) * 100) : 0;
        const impacto = facturacion > 0 ? round2((valorPagado / facturacion) * 100) : 0;
        const freqThreshold = Number(formula.freqThreshold ?? 3);
        const impactThreshold = Number(formula.impactThreshold ?? 1);

        return {
            result: freq,
            complies:
                totalServicios > 0 &&
                facturacion > 0 &&
                freq <= freqThreshold &&
                impacto <= impactThreshold,
        };
    }

    const den = Number(values[formula.den] || 0);
    const num = Number(values[formula.num] || 0);
    const result = den > 0 ? round2((num / den) * 100) : 0;
    const threshold = Number(formula.threshold || 0);
    let complies = false;
    const operator = formula.operator || null;

    if (formula.type === 'ratio') {
        complies = den > 0 && compareByOperator(result, threshold, operator || '>=');
    } else if (formula.type === 'ratio_ge') {
        complies = den > 0 && result >= threshold;
    } else if (formula.type === 'ratio_le') {
        complies = den > 0 && result <= threshold;
    } else if (formula.type === 'ratio_eq_zero') {
        complies = den > 0 && round2(result) === round2(threshold);
    }

    return { result, complies };
}

function readFormValues(root) {
    const values = {};
    root.querySelectorAll('.js-capture-field').forEach((input) => {
        const key = input.getAttribute('data-field');
        if (key) {
            values[key] = input.value;
        }
    });

    return values;
}

function updateMetricsUi(root, metrics) {
    const resultEl = root.querySelector('[data-metric="result"]');
    const compliesEl = root.querySelector('[data-metric="complies"]');
    const semaforoEl = root.querySelector('[data-metric="semaforo"]');
    const pill = root.querySelector('[data-metric="semaforo-pill"]');
    const requiredWrap = root.querySelector('[data-improvement-required-wrap]');

    if (resultEl) {
        resultEl.textContent = metrics.result.toFixed(2);
    }
    if (compliesEl) {
        compliesEl.textContent = metrics.complies ? 'SI' : 'NO';
    }
    if (semaforoEl) {
        semaforoEl.textContent = metrics.complies ? 'VERDE' : 'ROJO';
    }
    if (pill) {
        pill.classList.toggle('status-pill--req-contratado', metrics.complies);
        pill.classList.toggle('status-pill--req-cancelada', !metrics.complies);
    }
    if (requiredWrap) {
        if (metrics.complies) {
            requiredWrap.setAttribute('hidden', 'hidden');
        } else {
            requiredWrap.removeAttribute('hidden');
        }
    }
    root.setAttribute('data-complies', metrics.complies ? '1' : '0');
}

function openModal(modal) {
    if (!modal) {
        return;
    }
    modal.classList.remove('is-hidden');
    modal.removeAttribute('hidden');
}

function closeModal(modal) {
    if (!modal) {
        return;
    }
    modal.classList.add('is-hidden');
    modal.setAttribute('hidden', 'hidden');
}

function reindexClassificationRows(tbody) {
    Array.prototype.forEach.call(tbody.querySelectorAll('[data-classification-row]'), (row, index) => {
        row.querySelectorAll('select, input').forEach((input) => {
            const name = input.getAttribute('name') || '';
            input.setAttribute(
                'name',
                name.replace(/form\[clasificacion_por_tipo\]\[\d+\]/, `form[clasificacion_por_tipo][${index}]`)
            );
        });
    });
}

function addClassificationRow(root) {
    const tbody = root.querySelector('[data-classification-rows]');
    const template = root.querySelector('#classification-row-template');
    if (!tbody || !template) {
        return;
    }

    const index = tbody.querySelectorAll('[data-classification-row]').length;
    const html = template.innerHTML.replace(/__INDEX__/g, String(index));
    tbody.insertAdjacentHTML('beforeend', html);
}

function ensureEmptyClassificationRow(root) {
    const tbody = root.querySelector('[data-classification-rows]');
    if (!tbody) {
        return;
    }

    let hasEmpty = false;
    tbody.querySelectorAll('.js-classification-type').forEach((select) => {
        if (!select.value) {
            hasEmpty = true;
        }
    });

    if (!hasEmpty) {
        addClassificationRow(root);
    }
}

function initModals(root) {
    const improvementModal = root.querySelector('#improvement-modal');
    const classificationModal = root.querySelector('#classification-modal');

    root.querySelectorAll('.js-open-improvement-modal').forEach((btn) => {
        btn.addEventListener('click', () => {
            openModal(improvementModal);
        });
    });
    root.querySelectorAll('.js-close-improvement-modal').forEach((btn) => {
        btn.addEventListener('click', () => {
            closeModal(improvementModal);
        });
    });
    if (improvementModal) {
        improvementModal.addEventListener('click', (event) => {
            if (event.target === improvementModal) {
                closeModal(improvementModal);
            }
        });
    }

    root.querySelectorAll('.js-open-classification-modal').forEach((btn) => {
        btn.addEventListener('click', () => {
            openModal(classificationModal);
        });
    });
    root.querySelectorAll('.js-close-classification-modal').forEach((btn) => {
        btn.addEventListener('click', () => {
            closeModal(classificationModal);
        });
    });
    if (classificationModal) {
        classificationModal.addEventListener('click', (event) => {
            if (event.target === classificationModal) {
                closeModal(classificationModal);
            }
        });
    }

    const addBtn = root.querySelector('.js-add-classification-row');
    if (addBtn) {
        addBtn.addEventListener('click', () => {
            addClassificationRow(root);
        });
    }

    root.addEventListener('click', (event) => {
        const removeBtn = event.target.closest('.js-remove-classification-row');
        if (!removeBtn) {
            return;
        }

        const row = removeBtn.closest('[data-classification-row]');
        const tbody = root.querySelector('[data-classification-rows]');
        if (!row || !tbody) {
            return;
        }

        if (tbody.querySelectorAll('[data-classification-row]').length <= 1) {
            row.querySelectorAll('select, input').forEach((input) => {
                input.value = '';
            });
            return;
        }

        row.remove();
        reindexClassificationRows(tbody);
    });

    root.addEventListener('change', (event) => {
        if (!event.target.classList.contains('js-classification-type')) {
            return;
        }
        if (event.target.value) {
            ensureEmptyClassificationRow(root);
        }
    });
}

function initMetrics(root) {
    const formula = parseJsonAttr(root, 'data-formula');

    function refresh() {
        updateMetricsUi(root, computeMetrics(formula, readFormValues(root)));
    }

    root.querySelectorAll('.js-capture-field').forEach((input) => {
        input.addEventListener('input', refresh);
        input.addEventListener('change', refresh);
    });
    refresh();
}

function renderMixedBarLine(el, options) {
    if (!el) {
        return null;
    }

    const {
        title,
        categories,
        barSeries,
        lineSeries,
        height = 360,
        yRightMax = 100,
    } = options;

    const series = [
        ...barSeries.map(({ name, type, data }) => ({ name, type, data })),
        ...lineSeries.map(({ name, type, data }) => ({ name, type, data })),
    ];

    const yaxis = [];
    barSeries.forEach((s, index) => {
        yaxis.push({
            seriesName: s.name,
            show: index === 0,
            title: index === 0 ? { text: 'Valor' } : undefined,
            labels: { style: { fontSize: '11px' } },
            min: 0,
            forceNiceScale: true,
        });
    });
    lineSeries.forEach((s, index) => {
        yaxis.push({
            seriesName: s.name,
            opposite: true,
            show: index === 0,
            title: index === 0 ? { text: '%' } : undefined,
            min: 0,
            max: yRightMax,
            labels: { style: { fontSize: '11px' } },
        });
    });

    const chart = new ApexCharts(el, {
        ...sharedChart,
        chart: {
            ...sharedChart.chart,
            type: 'line',
            height,
            stacked: false,
        },
        title: title
            ? {
                  text: title,
                  align: 'center',
                  style: { fontSize: '18px', fontWeight: 700 },
              }
            : undefined,
        series,
        stroke: {
            width: [...barSeries.map(() => 0), ...lineSeries.map((s) => s.strokeWidth ?? 3)],
            curve: 'smooth',
            dashArray: [...barSeries.map(() => 0), ...lineSeries.map((s) => s.dashArray ?? 0)],
        },
        plotOptions: {
            bar: {
                columnWidth: '45%',
                borderRadius: 4,
            },
        },
        colors: [...barSeries.map((s) => s.color), ...lineSeries.map((s) => s.color)],
        xaxis: {
            categories: categories || [],
            labels: { style: { fontSize: '11px', fontWeight: 600 } },
        },
        yaxis,
        legend: {
            position: 'bottom',
            fontSize: '11px',
        },
        tooltip: {
            shared: true,
            intersect: false,
        },
    });

    chart.render();

    return chart;
}

function initFtOp01Chart() {
    const el = document.getElementById('ft-op-01-chart');
    if (!el) {
        return;
    }

    const payload = parseDataChart(el);
    const denominatorLabel = payload.denominator_label || 'Total base';
    const numeratorLabel = payload.numerator_label || 'Total cumplido';

    renderMixedBarLine(el, {
        title: payload.title || '',
        categories: payload.months || [],
        height: 380,
        barSeries: [
            {
                name: denominatorLabel,
                type: 'column',
                data: payload.denominator || [],
                color: BAR_DENOM,
            },
            {
                name: numeratorLabel,
                type: 'column',
                data: payload.numerator || [],
                color: BAR_NUM,
            },
        ],
        lineSeries: [
            {
                name: '% Cumplimiento',
                type: 'line',
                data: payload.result_percentage || [],
                color: LINE_RESULT,
                strokeWidth: 3,
            },
            {
                name: 'Meta',
                type: 'line',
                data: payload.meta || [],
                color: LINE_META,
                strokeWidth: 2,
                dashArray: 6,
            },
        ],
    });
}

function destroyMountedChart(el) {
    if (!el) {
        return;
    }

    if (el._apexChart) {
        el._apexChart.destroy();
        el._apexChart = null;
    }
}

function mountChart(el, options) {
    if (!el) {
        return null;
    }

    destroyMountedChart(el);

    const chart = new ApexCharts(el, options);
    chart.render();
    el._apexChart = chart;

    return chart;
}

function formatFtOp03Amount(value) {
    return `$ ${Number(value || 0).toLocaleString('es-CO', { maximumFractionDigits: 0 })}`;
}

function formatFtOp03Count(value) {
    return Number(value || 0).toLocaleString('es-CO', { maximumFractionDigits: 0 });
}

function renderFtOp03ComboChart(el, payload, config) {
    const categories = payload.months || [];
    const bar1Label = config.bar1Label;
    const bar2Label = config.bar2Label;
    const lineLabel = config.lineLabel;
    const metaLabel = config.metaLabel || 'META';
    const criticoLabel = config.criticoLabel || 'CRITICO';
    const valueFormatter = config.valueFormatter || ((value) => String(value ?? 0));
    const lineSeries = [
        {
            name: lineLabel,
            type: 'line',
            data: payload[config.lineKey] || [],
        },
        {
            name: metaLabel,
            type: 'line',
            data: payload.meta || [],
        },
        {
            name: criticoLabel,
            type: 'line',
            data: payload.critico || [],
        },
    ];

    return mountChart(el, {
        ...sharedChart,
        chart: {
            ...sharedChart.chart,
            type: 'line',
            height: 360,
            stacked: false,
        },
        series: [
            {
                name: bar1Label,
                type: 'column',
                data: payload[config.bar1Key] || [],
            },
            {
                name: bar2Label,
                type: 'column',
                data: payload[config.bar2Key] || [],
            },
            ...lineSeries,
        ],
        colors: [BRAND_BLUE, BRAND_NAVY, LINE_RESULT, LINE_META, '#9333ea'],
        stroke: {
            width: [0, 0, 3, 2, 2],
            curve: 'smooth',
            dashArray: [0, 0, 0, 6, 4],
        },
        plotOptions: {
            bar: {
                columnWidth: '42%',
                borderRadius: 4,
            },
        },
        xaxis: {
            categories,
            labels: {
                style: { fontSize: '11px', fontWeight: 600 },
            },
        },
        yaxis: [
            {
                seriesName: bar1Label,
                title: { text: config.leftAxisTitle || 'Valor' },
                labels: {
                    style: { fontSize: '11px' },
                    formatter: (value) => valueFormatter(value),
                },
                min: 0,
                forceNiceScale: true,
            },
            {
                seriesName: bar2Label,
                show: false,
                min: 0,
                forceNiceScale: true,
            },
            {
                seriesName: lineLabel,
                opposite: true,
                min: 0,
                max: 100,
                title: { text: '%' },
                labels: {
                    style: { fontSize: '11px' },
                    formatter: (value) => `${round2(value)}%`,
                },
            },
            {
                seriesName: metaLabel,
                opposite: true,
                show: false,
                min: 0,
                max: 100,
            },
            {
                seriesName: criticoLabel,
                opposite: true,
                show: false,
                min: 0,
                max: 100,
            },
        ],
        legend: {
            position: 'top',
            fontSize: '11px',
            horizontalAlign: 'center',
        },
        grid: {
            ...sharedChart.grid,
            padding: { top: 8, right: 12, bottom: 0, left: 12 },
        },
        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: (value, { seriesIndex }) => {
                    if (seriesIndex <= 1) {
                        return valueFormatter(value);
                    }

                    return `${round2(value)}%`;
                },
            },
        },
    });
}

function renderFtOp03PieChart(el) {
    const payload = parseDataChart(el);
    const data = payload.data || [];
    const total = data.reduce((sum, item) => sum + Number(item.value || 0), 0);

    return mountChart(el, {
        ...sharedChart,
        chart: {
            ...sharedChart.chart,
            type: 'pie',
            height: 320,
        },
        title: {
            text: payload.title || '',
            align: 'center',
            style: { fontSize: '16px', fontWeight: 700 },
        },
        series: total > 0 ? data.map((item) => Number(item.value || 0)) : [1],
        labels: total > 0 ? data.map((item) => item.name) : ['Sin datos'],
        colors: total > 0 ? undefined : [PIE_EMPTY],
        legend: {
            show: total > 0,
            position: 'bottom',
            fontSize: '11px',
        },
        dataLabels: { enabled: false },
        tooltip: {
            enabled: total > 0,
            y: {
                formatter: (value) => String(value),
            },
        },
        plotOptions: {
            pie: {
                dataLabels: { offset: -4 },
            },
        },
        noData: {
            text: 'Sin datos para este trimestre',
        },
    });
}

function initFtOp03Charts() {
    const financeEl = document.getElementById('ft-op-03-chart-finance');
    if (!financeEl) {
        return;
    }

    requestAnimationFrame(() => {
        renderFtOp03ComboChart(financeEl, parseDataChart(financeEl), {
            bar1Key: 'facturacion',
            bar2Key: 'pagado',
            lineKey: 'cumplimiento',
            bar1Label: 'TOTAL FACTURACION MENSUAL',
            bar2Label: 'VALOR PAGADO MENSUAL',
            lineLabel: '% CUMPLIMIENTO',
            leftAxisTitle: 'Valor ($)',
            valueFormatter: formatFtOp03Amount,
        });

        const clientsEl = document.getElementById('ft-op-03-chart-clients');
        if (clientsEl) {
            renderFtOp03ComboChart(clientsEl, parseDataChart(clientsEl), {
                bar1Key: 'clientes',
                bar2Key: 'siniestros',
                lineKey: 'porcentaje',
                bar1Label: 'TOTAL DE CLIENTES MENSUAL',
                bar2Label: 'TOTAL SINIESTROS MENSUAL',
                lineLabel: '% SINIESTROS',
                leftAxisTitle: 'Cantidad',
                valueFormatter: formatFtOp03Count,
            });
        }

        [1, 2, 3, 4].forEach((quarter) => {
            const quarterEl = document.getElementById(`ft-op-03-quarter-${quarter}`);
            if (quarterEl) {
                renderFtOp03PieChart(quarterEl);
            }
        });
    });
}

function boot() {
    const root = document.querySelector('[data-indicadores-capture]');
    if (!root) {
        return;
    }

    initModals(root);
    initMetrics(root);
    initFtOp01Chart();
    initFtOp03Charts();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
