(function () {
    'use strict';

    function parseJsonAttr(el, name) {
        if (!el) return null;
        try {
            return JSON.parse(el.getAttribute(name) || 'null');
        } catch (e) {
            return null;
        }
    }

    function parseDataChart(el) {
        if (!el || !el.dataset.chart) return {};
        try {
            return JSON.parse(el.dataset.chart);
        } catch (e) {
            return {};
        }
    }

    function round2(n) {
        return Math.round(n * 100) / 100;
    }

    function computeMetrics(formula, values) {
        if (!formula || formula.type === 'none') {
            return { result: 0, complies: false };
        }

        if (formula.type === 'ft_op_03') {
            var totalServicios = Number(values.total_servicios || 0);
            var totalSiniestros = Number(values.total_siniestros || 0);
            var facturacion = Number(values.facturacion_mensual || 0);
            var valorPagado = Number(values.valor_pagado_siniestros || 0);
            var freq = totalServicios > 0 ? round2((totalSiniestros / totalServicios) * 100) : 0;
            var impacto = facturacion > 0 ? round2((valorPagado / facturacion) * 100) : 0;
            var freqThreshold = Number(formula.freqThreshold ?? 3);
            var impactThreshold = Number(formula.impactThreshold ?? 1);
            return {
                result: freq,
                complies: totalServicios > 0 && facturacion > 0 && freq <= freqThreshold && impacto <= impactThreshold,
            };
        }

        var den = Number(values[formula.den] || 0);
        var num = Number(values[formula.num] || 0);
        var result = den > 0 ? round2((num / den) * 100) : 0;
        var threshold = Number(formula.threshold || 0);
        var complies = false;

        if (formula.type === 'ratio_ge') {
            complies = den > 0 && result >= threshold;
        } else if (formula.type === 'ratio_le') {
            complies = den > 0 && result <= threshold;
        } else if (formula.type === 'ratio_eq_zero') {
            complies = den > 0 && round2(result) === round2(threshold);
        }

        return { result: result, complies: complies };
    }

    function readFormValues(root) {
        var values = {};
        root.querySelectorAll('.js-capture-field').forEach(function (input) {
            var key = input.getAttribute('data-field');
            if (key) {
                values[key] = input.value;
            }
        });
        return values;
    }

    function updateMetricsUi(root, metrics) {
        var resultEl = root.querySelector('[data-metric="result"]');
        var compliesEl = root.querySelector('[data-metric="complies"]');
        var semaforoEl = root.querySelector('[data-metric="semaforo"]');
        var pill = root.querySelector('[data-metric="semaforo-pill"]');
        var requiredWrap = root.querySelector('[data-improvement-required-wrap]');

        if (resultEl) resultEl.textContent = metrics.result.toFixed(2);
        if (compliesEl) compliesEl.textContent = metrics.complies ? 'SI' : 'NO';
        if (semaforoEl) semaforoEl.textContent = metrics.complies ? 'VERDE' : 'ROJO';
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
        if (!modal) return;
        modal.classList.remove('is-hidden');
        modal.removeAttribute('hidden');
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.classList.add('is-hidden');
        modal.setAttribute('hidden', 'hidden');
    }

    function reindexClassificationRows(tbody) {
        Array.prototype.forEach.call(tbody.querySelectorAll('[data-classification-row]'), function (row, index) {
            row.querySelectorAll('select, input').forEach(function (input) {
                var name = input.getAttribute('name') || '';
                input.setAttribute(
                    'name',
                    name.replace(/form\[clasificacion_por_tipo\]\[\d+\]/, 'form[clasificacion_por_tipo][' + index + ']')
                );
            });
        });
    }

    function addClassificationRow(root) {
        var tbody = root.querySelector('[data-classification-rows]');
        var template = root.querySelector('#classification-row-template');
        if (!tbody || !template) return;

        var index = tbody.querySelectorAll('[data-classification-row]').length;
        var html = template.innerHTML.replace(/__INDEX__/g, String(index));
        tbody.insertAdjacentHTML('beforeend', html);
    }

    function ensureEmptyClassificationRow(root) {
        var tbody = root.querySelector('[data-classification-rows]');
        if (!tbody) return;
        var hasEmpty = false;
        tbody.querySelectorAll('.js-classification-type').forEach(function (select) {
            if (!select.value) hasEmpty = true;
        });
        if (!hasEmpty) {
            addClassificationRow(root);
        }
    }

    function initModals(root) {
        var improvementModal = root.querySelector('#improvement-modal');
        var classificationModal = root.querySelector('#classification-modal');

        root.querySelectorAll('.js-open-improvement-modal').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openModal(improvementModal);
            });
        });
        root.querySelectorAll('.js-close-improvement-modal').forEach(function (btn) {
            btn.addEventListener('click', function () {
                closeModal(improvementModal);
            });
        });
        if (improvementModal) {
            improvementModal.addEventListener('click', function (event) {
                if (event.target === improvementModal) {
                    closeModal(improvementModal);
                }
            });
        }

        root.querySelectorAll('.js-open-classification-modal').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openModal(classificationModal);
            });
        });
        root.querySelectorAll('.js-close-classification-modal').forEach(function (btn) {
            btn.addEventListener('click', function () {
                closeModal(classificationModal);
            });
        });
        if (classificationModal) {
            classificationModal.addEventListener('click', function (event) {
                if (event.target === classificationModal) {
                    closeModal(classificationModal);
                }
            });
        }

        var addBtn = root.querySelector('.js-add-classification-row');
        if (addBtn) {
            addBtn.addEventListener('click', function () {
                addClassificationRow(root);
            });
        }

        root.addEventListener('click', function (event) {
            var removeBtn = event.target.closest('.js-remove-classification-row');
            if (!removeBtn) return;
            var row = removeBtn.closest('[data-classification-row]');
            var tbody = root.querySelector('[data-classification-rows]');
            if (!row || !tbody) return;
            if (tbody.querySelectorAll('[data-classification-row]').length <= 1) {
                row.querySelectorAll('select, input').forEach(function (input) {
                    input.value = '';
                });
                return;
            }
            row.remove();
            reindexClassificationRows(tbody);
        });

        root.addEventListener('change', function (event) {
            if (!event.target.classList.contains('js-classification-type')) return;
            if (event.target.value) {
                ensureEmptyClassificationRow(root);
            }
        });
    }

    function initMetrics(root) {
        var formula = parseJsonAttr(root, 'data-formula');
        function refresh() {
            updateMetricsUi(root, computeMetrics(formula, readFormValues(root)));
        }
        root.querySelectorAll('.js-capture-field').forEach(function (input) {
            input.addEventListener('input', refresh);
            input.addEventListener('change', refresh);
        });
        refresh();
    }

    function ensureEcharts(onReady) {
        if (window.echarts) {
            onReady();
            return;
        }
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/echarts@5/dist/echarts.min.js';
        script.onload = onReady;
        document.head.appendChild(script);
    }

    function cylinderBar(name, data, colors, borderColor) {
        return [
            {
                name: name,
                type: 'bar',
                barWidth: 16,
                data: data,
                itemStyle: {
                    color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                        { offset: 0, color: colors[0] },
                        { offset: 1, color: colors[1] },
                    ]),
                    borderColor: borderColor,
                    borderWidth: 1,
                    shadowBlur: 6,
                    shadowColor: 'rgba(0,0,0,0.22)',
                },
            },
            {
                name: name + ' cap',
                type: 'pictorialBar',
                symbolSize: [16, 7],
                symbolOffset: [0, -3],
                symbolPosition: 'end',
                z: 12,
                tooltip: { show: false },
                itemStyle: { color: colors[0], borderColor: borderColor, borderWidth: 1 },
                data: data,
            },
        ];
    }

    function initFtOp01Chart() {
        var el = document.getElementById('ft-op-01-chart');
        if (!el || !window.echarts) return;

        var payload = parseDataChart(el);
        var chart = echarts.init(el);
        var denominatorLabel = payload.denominator_label || 'Total base';
        var numeratorLabel = payload.numerator_label || 'Total cumplido';

        chart.setOption({
            title: {
                text: payload.title || '',
                left: 'center',
                top: 10,
                textStyle: { fontSize: 24, fontWeight: 'bold' },
            },
            tooltip: { trigger: 'axis' },
            grid: { left: 55, right: 30, top: 65, bottom: 35 },
            legend: {
                bottom: 0,
                data: [denominatorLabel, numeratorLabel, '% Cumplimiento', 'Meta'],
            },
            xAxis: [{ type: 'category', data: payload.months || [], axisLabel: { fontWeight: 'bold' } }],
            yAxis: [
                { type: 'value', name: 'Valor' },
                { type: 'value', name: '%', min: 0, max: 100, splitLine: { show: false } },
            ],
            series: []
                .concat(cylinderBar(denominatorLabel, payload.denominator || [], ['#90b8ff', '#2f6fd9'], '#2a4f86'))
                .concat(cylinderBar(numeratorLabel, payload.numerator || [], ['#d8f3a5', '#78b63f'], '#3e7f23'))
                .concat([
                    {
                        name: '% Cumplimiento',
                        type: 'line',
                        yAxisIndex: 1,
                        smooth: true,
                        symbol: 'circle',
                        symbolSize: 6,
                        lineStyle: { width: 3, color: '#d12f2f' },
                        itemStyle: { color: '#d12f2f' },
                        data: payload.result_percentage || [],
                    },
                    {
                        name: 'Meta',
                        type: 'line',
                        yAxisIndex: 1,
                        smooth: false,
                        symbol: 'none',
                        lineStyle: { type: 'dashed', width: 2, color: '#444' },
                        data: payload.meta || [],
                    },
                ]),
        }, true);

        window.addEventListener('resize', function () {
            chart.resize();
        });
    }

    function initFtOp03Charts() {
        if (!window.echarts) return;
        if (!document.getElementById('ft-op-03-chart-finance')) return;

        window.__ftop03ChartInstances = window.__ftop03ChartInstances || {};

        function getChart(id) {
            var el = document.getElementById(id);
            if (!el) return null;
            if (window.__ftop03ChartInstances[id] && !window.__ftop03ChartInstances[id].isDisposed()) {
                return window.__ftop03ChartInstances[id];
            }
            var chart = echarts.init(el);
            window.__ftop03ChartInstances[id] = chart;
            return chart;
        }

        function renderBar(id, payload, config) {
            var chart = getChart(id);
            if (!chart) return;
            chart.setOption({
                tooltip: { trigger: 'axis' },
                legend: { top: 0, data: [config.bar1Label, config.bar2Label, config.lineLabel] },
                grid: { left: 50, right: 20, top: 35, bottom: 30 },
                xAxis: { type: 'category', data: payload.months || [] },
                yAxis: [{ type: 'value' }, { type: 'value', min: 0, max: 100 }],
                series: [
                    { type: 'bar', name: config.bar1Label, data: payload[config.bar1Key] || [], barMaxWidth: 28, barGap: '20%' },
                    { type: 'bar', name: config.bar2Label, data: payload[config.bar2Key] || [], barMaxWidth: 28, barGap: '20%' },
                    { type: 'line', yAxisIndex: 1, name: config.lineLabel, data: payload[config.lineKey] || [], smooth: true },
                ],
            }, true);
            chart.resize();
        }

        function renderPie(id) {
            var el = document.getElementById(id);
            var chart = getChart(id);
            if (!el || !chart) return;
            var payload = parseDataChart(el);
            var data = payload.data || [];
            var total = data.reduce(function (sum, item) {
                return sum + Number(item.value || 0);
            }, 0);

            chart.setOption({
                title: { text: payload.title || '', top: 5, left: 'center', textStyle: { fontSize: 22, fontWeight: 'bold', fontFamily: 'serif' } },
                tooltip: total > 0 ? {
                    trigger: 'item',
                    formatter: function (params) {
                        return params.name + '<br/>Cantidad: ' + params.value + '<br/>Porcentaje: ' + params.percent + '%';
                    },
                } : { show: false },
                legend: {
                    show: total > 0,
                    bottom: 0,
                    left: 'center',
                    orient: 'horizontal',
                    itemWidth: 12,
                    itemHeight: 12,
                    textStyle: { fontSize: 11 },
                },
                graphic: total === 0 ? [{
                    type: 'text',
                    left: 'center',
                    top: '85%',
                    style: { text: 'Sin datos para este trimestre', fill: '#6b7280', fontSize: 13, fontWeight: 500 },
                }] : [],
                series: [{
                    type: 'pie',
                    radius: total > 0 ? ['0%', '58%'] : ['0%', '52%'],
                    center: ['50%', '42%'],
                    avoidLabelOverlap: true,
                    label: { show: false },
                    labelLine: { show: false },
                    data: total > 0 ? data : [{ name: 'Sin datos', value: 1, itemStyle: { color: '#e5e7eb' } }],
                }],
            }, true);
            chart.resize();
        }

        renderBar('ft-op-03-chart-finance', parseDataChart(document.getElementById('ft-op-03-chart-finance')), {
            bar1Key: 'facturacion',
            bar2Key: 'pagado',
            lineKey: 'cumplimiento',
            bar1Label: 'TOTAL FACTURACION MENSUAL',
            bar2Label: 'VALOR PAGADO MENSUAL',
            lineLabel: '% CUMPLIMIENTO',
        });
        renderBar('ft-op-03-chart-clients', parseDataChart(document.getElementById('ft-op-03-chart-clients')), {
            bar1Key: 'clientes',
            bar2Key: 'siniestros',
            lineKey: 'porcentaje',
            bar1Label: 'TOTAL DE CLIENTES MENSUAL',
            bar2Label: 'TOTAL SINIESTROS MENSUAL',
            lineLabel: '% SINIESTROS',
        });
        [1, 2, 3, 4].forEach(function (q) {
            renderPie('ft-op-03-quarter-' + q);
        });

        window.addEventListener('resize', function () {
            Object.keys(window.__ftop03ChartInstances).forEach(function (id) {
                var chart = window.__ftop03ChartInstances[id];
                if (chart && !chart.isDisposed()) chart.resize();
            });
        });
    }

    function boot() {
        var root = document.querySelector('[data-indicadores-capture]');
        if (!root) return;

        initModals(root);
        initMetrics(root);
        ensureEcharts(function () {
            initFtOp01Chart();
            initFtOp03Charts();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
