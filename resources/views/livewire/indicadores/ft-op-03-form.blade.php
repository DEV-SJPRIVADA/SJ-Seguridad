<div class="indicadores-form ftop03-sheet">
    @include('livewire.indicadores.partials.capture-alerts')

    <div class="panel" style="margin:0;">
        <div class="panel__header">
            <h4 class="panel-title">{{ $indicator->code }} — {{ $indicator->name }}</h4>
        </div>
        <div class="panel__body form-stack">
            @include($fieldsView)
            @include('livewire.indicadores.partials.capture-metrics')
        </div>
    </div>

    <div class="panel indicadores-sheet-panel" style="margin:0;">
        <div class="panel__body">
        <table class="border-collapse table-fixed text-[13px] text-black" style="min-width: 1036px;">
            <colgroup>
                @for ($c = 0; $c < 14; $c++)
                    <col style="width:74px;">
                @endfor
            </colgroup>
            <tr style="height:26px;">
                <td colspan="2" rowspan="4" class="border border-gray-600 text-center align-middle bg-gray-100">
                    <x-application-logo class="mx-auto h-20 w-20 text-sky-800" />
                </td>
                <td colspan="8" rowspan="4" class="border border-gray-600 text-center align-middle bg-gray-50 t-title">
                    FICHA DEL INDICADOR DE GESTION
                </td>
                <td colspan="4" class="border border-gray-600 px-2 t-body">{{ $indicator->code }}</td>
            </tr>
            <tr style="height:26px;">
                <td colspan="4" class="border border-gray-600 px-2 t-body">{{ ($months[$selectedMonth] ?? 'Mes').' de '.$selectedYear }}</td>
            </tr>
            <tr style="height:26px;">
                <td colspan="4" class="border border-gray-600 px-2 t-body">Version 02</td>
            </tr>
            <tr style="height:26px;">
                <td colspan="4" class="border border-gray-600 px-2 t-body">Pagina 1 de 1</td>
            </tr>

            <tr style="height:26px;" class="bg-gray-100">
                <td colspan="14" class="border border-gray-600 text-center t-head">NOMBRE DEL INDICADOR</td>
            </tr>
            <tr style="height:26px;" class="bg-gray-50">
                <td colspan="14" class="border border-gray-600 text-center t-head">{{ strtoupper($indicator->name) }}</td>
            </tr>

            <tr style="height:26px;" class="bg-gray-100">
                <td colspan="9" class="border border-gray-600 text-center t-head">OBJETIVO</td>
                <td colspan="5" class="border border-gray-600 text-center t-head">PROCESO</td>
            </tr>
            <tr style="height:26px;">
                <td colspan="9" class="border border-gray-600 px-2 t-body">Determinar el impacto de los siniestros o reclamos en la facturacion de la empresa.</td>
                <td colspan="5" class="border border-gray-600 text-center t-body">Operaciones y Gestion de Riesgos</td>
            </tr>
            <tr style="height:26px;" class="bg-gray-100 text-center">
                <td colspan="3" class="border border-gray-600 t-head">UNIDAD MEDIDA</td>
                <td class="border border-gray-600 t-head">META</td>
                <td colspan="3" class="border border-gray-600 t-head">FRECUENCIA DE MEDICION</td>
                <td colspan="2" class="border border-gray-600 t-head">TENDENCIA</td>
                <td colspan="5" class="border border-gray-600 t-head">INSUMOS PARA LA MEDICION</td>
            </tr>
            <tr style="height:26px;" class="text-center">
                <td colspan="3" class="border border-gray-600 t-body">{{ ucfirst((string) ($indicator->unit ?? 'Porcentaje')) }}</td>
                <td class="border border-gray-600 t-body">{{ number_format((float) $indicator->target_value, 0) }}%</td>
                <td colspan="3" class="border border-gray-600 t-body">{{ ucfirst($indicator->frequency ?? 'Mensual') }}</td>
                <td colspan="2" class="border border-gray-600 t-body">{{ ($indicator->target_operator ?? '>=') === '<=' ? 'Decreciente' : 'Creciente' }}</td>
                <td colspan="5" class="border border-gray-600 t-body">FO-GI-06 Control de No Conformidades / Reporte clientes</td>
            </tr>
            <tr style="height:26px;" class="bg-gray-100">
                <td colspan="14" class="border border-gray-600 text-center t-head">FORMULA</td>
            </tr>
            <tr style="height:26px;">
                <td colspan="14" class="border border-gray-600 text-center t-body">{{ $indicator->formula_description }}</td>
            </tr>
            <tr style="height:26px;" class="bg-gray-100">
                <td colspan="14" class="border border-gray-600 text-center t-head">RESPONSABILIDADES</td>
            </tr>
            <tr style="height:26px;" class="bg-gray-100 text-center">
                <td colspan="5" class="border border-gray-600 t-head">RESULTADOS Y MEDICION</td>
                <td colspan="5" class="border border-gray-600 t-head">RESULTADOS</td>
                <td colspan="4" class="border border-gray-600 t-head">MEDICION</td>
            </tr>
            <tr style="height:26px;" class="text-center">
                <td colspan="5" class="border border-gray-600 t-body">Director de Operaciones / Director(a) Financiero</td>
                <td colspan="5" class="border border-gray-600 t-body">%</td>
                <td colspan="4" class="border border-gray-600 t-body">No. siniestros / No. de servicios</td>
            </tr>
        </table>

        <table class="border-collapse table-fixed text-[13px] text-black border border-gray-600 border-t-0" style="min-width: 1036px;">
            <colgroup>
                @for ($c = 0; $c < 14; $c++)
                    <col style="width:74px;">
                @endfor
            </colgroup>
            <tr style="height:26px;" class="bg-gray-100 text-center">
                <td colspan="14" class="border border-gray-600 t-head">RESULTADOS</td>
            </tr>
            <tr style="height:26px;" class="bg-blue-100 text-center">
                <td class="border border-gray-600 t-head">CRITERIO</td>
                @foreach (['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'] as $m)
                    <td class="border border-gray-600 t-head">{{ $m }}</td>
                @endforeach
                <td class="border border-gray-600 t-head">TOTAL</td>
            </tr>
            <tr style="height:26px;" class="text-center">
                <td class="border border-gray-600 t-head">TOTAL FACTURACION MENSUAL</td>
                @for ($i = 1; $i <= 12; $i++) <td class="border border-gray-600 t-body">$ {{ number_format($financeRows['facturacion'][$i] ?? 0, 0, ',', '.') }}</td> @endfor
                <td class="border border-gray-600 t-body">$ {{ number_format($financeRows['totals']['facturacion'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr style="height:26px;" class="text-center">
                <td class="border border-gray-600 t-head">VALOR PAGADO MENSUAL</td>
                @for ($i = 1; $i <= 12; $i++) <td class="border border-gray-600 t-body">$ {{ number_format($financeRows['pagado'][$i] ?? 0, 0, ',', '.') }}</td> @endfor
                <td class="border border-gray-600 t-body">$ {{ number_format($financeRows['totals']['pagado'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr style="height:26px;" class="text-center bg-green-100">
                <td class="border border-gray-600 t-head">% CUMPLIMIENTO</td>
                @for ($i = 1; $i <= 12; $i++) <td class="border border-gray-600 t-body">{{ number_format($financeRows['cumplimiento'][$i] ?? 0, 2) }}%</td> @endfor
                <td class="border border-gray-600 t-body">{{ number_format($financeRows['totals']['cumplimiento'] ?? 0, 2) }}%</td>
            </tr>
            <tr style="height:26px;" class="text-center">
                <td class="border border-gray-600 t-head">META</td>
                @for ($i = 0; $i < 13; $i++) <td class="border border-gray-600 t-body">{{ number_format((float)$indicator->target_value,0) }}%</td> @endfor
            </tr>
        </table>

        <div class="border border-gray-600 border-t-0 p-3" style="width:1036px; min-width:1036px;">
            <div wire:ignore id="ft-op-03-chart-finance" data-chart='@json($financeChartPayload)' class="w-full h-[360px]"></div>
        </div>

        <table class="border-collapse table-fixed text-[13px] text-black border border-gray-600 border-t-0" style="min-width: 1036px;">
            <colgroup>
                @for ($c = 0; $c < 14; $c++)
                    <col style="width:74px;">
                @endfor
            </colgroup>
            <tr style="height:26px;" class="bg-gray-100 text-center">
                <td colspan="14" class="border border-gray-600 t-head">RESULTADOS POR CANTIDAD DE CLIENTES</td>
            </tr>
            <tr style="height:26px;" class="bg-blue-100 text-center">
                <td class="border border-gray-600 t-head">CRITERIO</td>
                @foreach (['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'] as $m)
                    <td class="border border-gray-600 t-head">{{ $m }}</td>
                @endforeach
                <td class="border border-gray-600 t-head">TOTAL</td>
            </tr>
            <tr style="height:26px;" class="text-center">
                <td class="border border-gray-600 t-head">TOTAL DE CLIENTES MENSUAL</td>
                @for ($i = 1; $i <= 12; $i++) <td class="border border-gray-600 t-body">{{ number_format($incidentRows['clientes'][$i] ?? 0, 0, ',', '.') }}</td> @endfor
                <td class="border border-gray-600 t-body">{{ number_format($incidentRows['totals']['clientes'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr style="height:26px;" class="text-center">
                <td class="border border-gray-600 t-head">TOTAL SINIESTROS MENSUAL</td>
                @for ($i = 1; $i <= 12; $i++) <td class="border border-gray-600 t-body">{{ number_format($incidentRows['siniestros'][$i] ?? 0, 0, ',', '.') }}</td> @endfor
                <td class="border border-gray-600 t-body">{{ number_format($incidentRows['totals']['siniestros'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr style="height:26px;" class="text-center bg-green-100">
                <td class="border border-gray-600 t-head">% SINIESTROS</td>
                @for ($i = 1; $i <= 12; $i++) <td class="border border-gray-600 t-body">{{ number_format($incidentRows['porcentaje'][$i] ?? 0, 2) }}%</td> @endfor
                <td class="border border-gray-600 t-body">{{ number_format($incidentRows['totals']['porcentaje'] ?? 0, 2) }}%</td>
            </tr>
        </table>

        <div class="border border-gray-600 border-t-0 p-3" style="width:1036px; min-width:1036px;">
            <div wire:ignore id="ft-op-03-chart-clients" data-chart='@json($incidentChartPayload)' class="w-full h-[360px]"></div>
        </div>

        @for ($q = 1; $q <= 4; $q++)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-0 border border-gray-600 border-t-0" style="width:1036px; min-width:1036px;">
                <table class="border-collapse table-fixed text-[13px] text-black md:col-span-1">
                    <colgroup>
                        <col style="width:180px;"><col style="width:80px;"><col style="width:80px;"><col style="width:80px;">
                    </colgroup>
                    <tr class="bg-blue-100 text-center" style="height:26px;">
                        <td class="border border-gray-600 t-head">TIPO DE SINIESTRO</td>
                        <td class="border border-gray-600 t-head">CANTIDAD</td>
                        <td class="border border-gray-600 t-head">%</td>
                        <td class="border border-gray-600 t-head">PERIODO</td>
                    </tr>
                    @foreach (($quarterlyTables[$q]['rows'] ?? []) as $idx => $row)
                        <tr style="height:53px;" class="text-center">
                            <td class="border border-gray-600 t-body">{{ strtoupper($row['type']) }}</td>
                            <td class="border border-gray-600 t-body">{{ number_format($row['qty'], 0, ',', '.') }}</td>
                            <td class="border border-gray-600 t-body">{{ number_format($row['pct'], 2) }}%</td>
                            @if ($idx === 0)
                                <td rowspan="{{ count($quarterlyTables[$q]['rows'] ?? []) + 1 }}" class="border border-gray-600 t-head">
                                    {{ $q }}{{ ['ER', 'DO', 'ER', 'TO'][$q - 1] }} TRIMESTRE
                                </td>
                            @endif
                        </tr>
                    @endforeach
                    <tr style="height:26px;" class="text-center bg-gray-100">
                        <td class="border border-gray-600 t-head">TOTAL</td>
                        <td class="border border-gray-600 t-head">{{ number_format($quarterlyTables[$q]['total_qty'] ?? 0, 0, ',', '.') }}</td>
                        <td class="border border-gray-600 t-head">100%</td>
                    </tr>
                </table>
                <div class="md:col-span-2 p-3">
                    <div wire:ignore id="ft-op-03-quarter-{{ $q }}" data-chart='@json($quarterChartPayload[$q] ?? [])' class="w-full h-[320px]"></div>
                </div>
            </div>
        @endfor

        <table class="border-collapse table-fixed text-[13px] text-black border border-gray-600 border-t-0" style="min-width: 1036px;">
            <colgroup>
                <col style="width:60px;"><col style="width:74px;"><col style="width:560px;"><col style="width:74px;"><col style="width:74px;">
            </colgroup>
            <tr style="height:53px;" class="bg-gray-100 text-center">
                <td colspan="3" class="border border-gray-600 t-head">ANALISIS DE RESULTADOS</td>
                <td class="border border-gray-600 t-head">CUMPLE</td>
                <td class="border border-gray-600 t-head">MEJORA</td>
            </tr>
            @foreach ($sheetRows as $row)
                <tr style="height:53px;">
                    <td class="border border-gray-600 bg-gray-100 t-head text-center [writing-mode:vertical-rl] rotate-180">{{ $selectedYear }}</td>
                    <td class="border border-gray-600 bg-gray-100 t-head text-center">{{ $row['month'] }}</td>
                    <td class="border border-gray-600 px-2 align-top t-body">{{ $row['analysis'] }}</td>
                    <td class="border border-gray-600 text-center t-head">{{ $row['has_capture'] ? ($row['complies'] ? 'SI' : 'NO') : '' }}</td>
                    <td class="border border-gray-600 text-center t-head">{{ $row['has_capture'] ? ($row['improvement'] ? 'SI' : 'NO') : '' }}</td>
                </tr>
            @endforeach
        </table>
        </div>
    </div>

    @include('livewire.indicadores.partials.improvement-modal')

    @if ($showClassificationModal)
        <div
            class="indicadores-modal-backdrop"
            wire:key="classification-modal"
            role="dialog"
            aria-modal="true"
            wire:click.self="closeClassificationModal"
        >
            <div class="panel indicadores-modal" wire:click.stop>
                <div class="panel__header">
                    <div class="indicadores-modal__header">
                        <h4 class="panel-title">Clasificacion de siniestros</h4>
                        <button type="button" wire:click="closeClassificationModal" class="btn btn--secondary btn--sm">Cerrar</button>
                    </div>
                </div>
                <div class="panel__body form-stack">
                    <table class="supply-table">
                        <thead>
                            <tr>
                                <th style="text-align:left;">Tipo de siniestro</th>
                                <th>Cantidad</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($form['clasificacion_por_tipo'] as $index => $row)
                                <tr>
                                    <td style="text-align:left;">
                                        <select wire:model.live="form.clasificacion_por_tipo.{{ $index }}.tipo" wire:change="handleClassificationTypeChange({{ $index }})" class="supply-input supply-select">
                                            <option value="">Seleccione...</option>
                                            @foreach ($siniestroOptions as $option)
                                                <option value="{{ $option }}">{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" wire:model.live="form.clasificacion_por_tipo.{{ $index }}.cantidad" class="supply-input" />
                                    </td>
                                    <td>
                                        <button type="button" wire:click="removeTypeRow({{ $index }})" class="btn btn--secondary btn--sm">X</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="indicadores-actions indicadores-actions--end">
                        <button type="button" wire:click="closeClassificationModal" class="btn btn--secondary">Cancelar</button>
                        <button type="button" wire:click="saveClassification" class="btn btn--primary">Guardar clasificacion</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@assets
<script src="https://cdn.jsdelivr.net/npm/echarts@5/dist/echarts.min.js"></script>
@endassets

@script
<script>
    (function () {
        if (window.__ftop03ChartsInit) return;
        window.__ftop03ChartsInit = true;
        window.__ftop03ChartInstances = window.__ftop03ChartInstances || {};

        function parseData(id) {
            const el = document.getElementById(id);
            if (!el || !el.dataset?.chart) return {};
            try { return JSON.parse(el.dataset.chart); } catch (e) { return {}; }
        }

        function getChart(id) {
            const el = document.getElementById(id);
            if (!el || !window.echarts) return null;

            if (window.__ftop03ChartInstances[id] && !window.__ftop03ChartInstances[id].isDisposed()) {
                return window.__ftop03ChartInstances[id];
            }

            const current = echarts.getInstanceByDom(el);
            if (current) {
                window.__ftop03ChartInstances[id] = current;
                return current;
            }

            const chart = echarts.init(el);
            window.__ftop03ChartInstances[id] = chart;
            return chart;
        }

        function renderBar(id, payload, config) {
            const chart = getChart(id);
            if (!chart) return;
            chart.setOption({
                tooltip: { trigger: 'axis' },
                legend: { top: 0, data: [config.bar1Label, config.bar2Label, config.lineLabel] },
                grid: { left: 50, right: 20, top: 35, bottom: 30 },
                xAxis: { type: 'category', data: payload.months || [] },
                yAxis: [{ type: 'value' }, { type: 'value', min: 0, max: 100 }],
                series: [
                    {
                        type: 'bar',
                        name: config.bar1Label,
                        data: payload[config.bar1Key] || [],
                        barMaxWidth: 28,
                        barGap: '20%',
                    },
                    {
                        type: 'bar',
                        name: config.bar2Label,
                        data: payload[config.bar2Key] || [],
                        barMaxWidth: 28,
                        barGap: '20%',
                    },
                    {
                        type: 'line',
                        yAxisIndex: 1,
                        name: config.lineLabel,
                        data: payload[config.lineKey] || [],
                        smooth: true,
                    },
                ]
            }, true);
            chart.resize();
        }

        function renderPie(id, payload) {
            const chart = getChart(id);
            if (!chart) return;
            const data = payload.data || [];
            const total = data.reduce(function (sum, item) {
                return sum + Number(item.value || 0);
            }, 0);

            chart.setOption({
                title: { text: payload.title || '', top: 5, left: 'center', textStyle: { fontSize: 22, fontWeight: 'bold', fontFamily: 'serif' } },
                tooltip: total > 0 ? {
                    trigger: 'item',
                    formatter: function (params) {
                        return params.name + '<br/>Cantidad: ' + params.value + '<br/>Porcentaje: ' + params.percent + '%';
                    }
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
                    style: {
                        text: 'Sin datos para este trimestre',
                        fill: '#6b7280',
                        fontSize: 13,
                        fontWeight: 500,
                    }
                }] : [],
                series: [{
                    type: 'pie',
                    radius: total > 0 ? ['0%', '58%'] : ['0%', '52%'],
                    center: ['50%', '42%'],
                    avoidLabelOverlap: true,
                    label: { show: false },
                    labelLine: { show: false },
                    emphasis: {
                        scale: true,
                        label: { show: false },
                    },
                    data: total > 0 ? data : [{ name: 'Sin datos', value: 1, itemStyle: { color: '#e5e7eb' } }],
                }]
            }, true);
            chart.resize();
        }

        function renderAll(finance, clients, quarters) {
            renderBar('ft-op-03-chart-finance', finance, {
                bar1Key: 'facturacion',
                bar2Key: 'pagado',
                lineKey: 'cumplimiento',
                bar1Label: 'TOTAL FACTURACION MENSUAL',
                bar2Label: 'VALOR PAGADO MENSUAL',
                lineLabel: '% CUMPLIMIENTO',
            });
            renderBar('ft-op-03-chart-clients', clients, {
                bar1Key: 'clientes',
                bar2Key: 'siniestros',
                lineKey: 'porcentaje',
                bar1Label: 'TOTAL DE CLIENTES MENSUAL',
                bar2Label: 'TOTAL SINIESTROS MENSUAL',
                lineLabel: '% SINIESTROS',
            });

            [1, 2, 3, 4].forEach(function (q) {
                renderPie('ft-op-03-quarter-' + q, quarters[q] || parseData('ft-op-03-quarter-' + q));
            });
        }

        function boot() {
            renderAll(
                parseData('ft-op-03-chart-finance'),
                parseData('ft-op-03-chart-clients'),
                {
                    1: parseData('ft-op-03-quarter-1'),
                    2: parseData('ft-op-03-quarter-2'),
                    3: parseData('ft-op-03-quarter-3'),
                    4: parseData('ft-op-03-quarter-4'),
                }
            );
        }

        window.addEventListener('ft-op-03-charts-refresh', function (event) {
            const detail = event.detail || {};
            renderAll(detail.finance || {}, detail.clients || {}, detail.quarters || {});
        });

        window.addEventListener('resize', function () {
            Object.values(window.__ftop03ChartInstances).forEach(function (chart) {
                if (chart && !chart.isDisposed()) {
                    chart.resize();
                }
            });
        });

        document.addEventListener('livewire:initialized', boot);
        document.addEventListener('livewire:navigated', boot);
        window.setTimeout(boot, 80);
    })();
</script>
@endscript
