<div
    class="indicadores-form ftop03-sheet"
    data-indicadores-capture
    data-formula='@json($clientFormula)'
    data-complies="{{ $complies ? '1' : '0' }}"
>
    @include('areas.operaciones.indicadores.partials.capture-alerts')

    <div class="panel" style="margin:0;">
        <div class="panel__header">
            <h4 class="panel-title">{{ $indicator->code }} — {{ $indicator->name }}</h4>
        </div>
        <div class="panel__body form-stack">
            @include($fieldsView)
            @include('areas.operaciones.indicadores.partials.capture-metrics')
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
            <tr style="height:26px;" class="bg-gray-100 text-center">
                <td colspan="3" class="border border-gray-600 t-head">CRITICO</td>
                <td class="border border-gray-600 t-body">{{ number_format((float) ($indicator->critical_value ?? 0), 0) }}%</td>
                <td colspan="10" class="border border-gray-600 t-body"></td>
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
            <tr style="height:26px;" class="text-center">
                <td class="border border-gray-600 t-head">CRITICO</td>
                @for ($i = 0; $i < 13; $i++) <td class="border border-gray-600 t-body">{{ number_format((float)($indicator->critical_value ?? 0),0) }}%</td> @endfor
            </tr>
        </table>

        <div class="border border-gray-600 border-t-0 p-3" style="width:1036px; min-width:1036px;">
            <div id="ft-op-03-chart-finance" data-chart='@json($financeChartPayload)' class="w-full h-[360px]"></div>
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
            <div id="ft-op-03-chart-clients" data-chart='@json($incidentChartPayload)' class="w-full h-[360px]"></div>
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
                    <div id="ft-op-03-quarter-{{ $q }}" data-chart='@json($quarterChartPayload[$q] ?? [])' class="w-full h-[320px]"></div>
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

    @include('areas.operaciones.indicadores.partials.improvement-modal')
    @include('areas.operaciones.indicadores.partials.classification-modal')
</div>
