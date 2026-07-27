<div
    class="indicadores-form ftop01-sheet"
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
            <div class="indicadores-capture-toolbar">
                <div>@include($fieldsView)</div>
                <div class="indicadores-capture-toolbar__action">
                    <button type="button" class="btn btn--secondary btn--sm js-open-improvement-modal" @disabled($isPeriodClosed)>
                        Abrir modal de analisis
                    </button>
                </div>
            </div>

            @include('areas.operaciones.indicadores.partials.capture-metrics')
        </div>
    </div>

    <div class="panel indicadores-sheet-panel" style="margin:0;">
        <div class="panel__body">
        <table class="border-collapse table-fixed text-[13px] text-black" style="min-width: 888px;">
            <colgroup>
                @for ($c = 0; $c < 12; $c++)
                    <col style="width:74px;">
                @endfor
            </colgroup>

            <tr style="height:26px;">
                <td colspan="2" rowspan="4" class="border border-gray-600 text-center align-middle bg-gray-100">
                    <x-application-logo class="mx-auto h-20 w-20 text-sky-800" />
                </td>
                <td colspan="7" rowspan="4" class="border border-gray-600 text-center align-middle bg-gray-50 t-title">
                    FICHA DEL INDICADOR DE GESTION
                </td>
                <td colspan="3" class="border border-gray-600 px-2 t-body">{{ $indicator->code }}</td>
            </tr>
            <tr style="height:26px;">
                <td colspan="3" class="border border-gray-600 px-2 t-body">{{ ($months[$selectedMonth] ?? 'Mes').' de '.$selectedYear }}</td>
            </tr>
            <tr style="height:26px;">
                <td colspan="3" class="border border-gray-600 px-2 t-body">Version 02</td>
            </tr>
            <tr style="height:26px;">
                <td colspan="3" class="border border-gray-600 px-2 t-body">Pagina 1 de 1</td>
            </tr>

            <tr style="height:26px;" class="bg-gray-100">
                <td colspan="12" class="border border-gray-600 text-center t-head">NOMBRE DEL INDICADOR</td>
            </tr>
            <tr style="height:26px;" class="bg-gray-50">
                <td colspan="12" class="border border-gray-600 text-center t-head">{{ $indicator->name }}</td>
            </tr>
            <tr style="height:26px;" class="bg-gray-100">
                <td colspan="8" class="border border-gray-600 text-center t-head">OBJETIVO</td>
                <td colspan="4" class="border border-gray-600 text-center t-head">PROCESO</td>
            </tr>
            <tr style="height:26px;">
                <td colspan="8" class="border border-gray-600 px-2 t-body">Medir el grado de cumplimiento del indicador.</td>
                <td colspan="4" class="border border-gray-600 text-center t-body">Gestion Operativa</td>
            </tr>
            <tr style="height:26px;" class="bg-gray-100 text-center">
                <td colspan="2" class="border border-gray-600 t-head">UNIDAD MEDIDA</td>
                <td class="border border-gray-600 t-head">META</td>
                <td colspan="3" class="border border-gray-600 t-head">FRECUENCIA DE MEDICION</td>
                <td colspan="2" class="border border-gray-600 t-head">TENDENCIA</td>
                <td colspan="4" class="border border-gray-600 t-head">INSUMOS PARA LA MEDICION</td>
            </tr>
            <tr style="height:26px;" class="text-center">
                <td colspan="2" class="border border-gray-600 t-body">{{ ucfirst((string) ($indicator->unit ?? 'Porcentaje')) }}</td>
                <td class="border border-gray-600 t-body">{{ number_format((float) $indicator->target_value, 0) }}%</td>
                <td colspan="3" class="border border-gray-600 t-body">{{ ucfirst($indicator->frequency ?? 'Mensual') }}</td>
                <td colspan="2" class="border border-gray-600 t-body">
                    @php
                        $tendency = match ($indicator->target_operator ?? '>=') {
                            '<=' => 'Decreciente',
                            '==' => 'Objetivo exacto',
                            default => 'Creciente',
                        };
                    @endphp
                    {{ $tendency }}
                </td>
                <td colspan="4" class="border border-gray-600 t-body">Base de datos del indicador</td>
            </tr>
            <tr style="height:26px;" class="bg-gray-100 text-center">
                <td colspan="2" class="border border-gray-600 t-head">CRITICO</td>
                <td class="border border-gray-600 t-body">{{ number_format((float) ($indicator->critical_value ?? 0), 0) }}%</td>
                <td colspan="9" class="border border-gray-600 t-body"></td>
            </tr>
            <tr style="height:26px;" class="bg-gray-100">
                <td colspan="12" class="border border-gray-600 text-center t-head">FORMULA</td>
            </tr>
            <tr style="height:26px;">
                <td colspan="12" class="border border-gray-600 text-center t-body">({{ $indicator->formula_description ?? 'N/A' }})</td>
            </tr>
            <tr style="height:26px;" class="bg-gray-100">
                <td colspan="12" class="border border-gray-600 text-center t-head">RESPONSABILIDADES</td>
            </tr>
            <tr style="height:26px;" class="bg-gray-100 text-center">
                <td colspan="4" class="border border-gray-600 t-head">RESULTADOS Y MEDICION</td>
                <td colspan="4" class="border border-gray-600 t-head">RESULTADOS</td>
                <td colspan="4" class="border border-gray-600 t-head">MEDICION</td>
            </tr>
            <tr style="height:26px;" class="text-center">
                <td colspan="4" class="border border-gray-600 t-body">Lider de Gestion Operativa</td>
                <td colspan="4" class="border border-gray-600 t-body">N.A.</td>
                <td colspan="4" class="border border-gray-600 t-body">N.A.</td>
            </tr>
            <tr style="height:26px;" class="bg-gray-100">
                <td colspan="12" class="border border-gray-600 text-center t-head">RESULTADOS</td>
            </tr>
            <tr style="height:26px;" class="bg-blue-100 text-center">
                @foreach ($sheetRows as $row)
                    <td class="border border-gray-600 t-head">{{ $row['month'] }}</td>
                @endforeach
            </tr>
            <tr style="height:26px;" class="bg-gray-50 text-center">
                <td colspan="12" class="border border-gray-600 t-head">{{ $selectedYear }}</td>
            </tr>
            <tr style="height:26px;" class="bg-blue-100 text-center">
                <td colspan="12" class="border border-gray-600 t-head">{{ $sheetDenominatorLabel }}</td>
            </tr>
            <tr style="height:26px;" class="text-center">
                @foreach ($sheetRows as $row)
                    <td class="border border-gray-600 t-body">{{ rtrim(rtrim(number_format($row['denominator'], 2, '.', ''), '0'), '.') }}</td>
                @endforeach
            </tr>
            <tr style="height:26px;" class="bg-blue-100 text-center">
                <td colspan="12" class="border border-gray-600 t-head">{{ $sheetNumeratorLabel }}</td>
            </tr>
            <tr style="height:26px;" class="text-center">
                @foreach ($sheetRows as $row)
                    <td class="border border-gray-600 t-body">{{ rtrim(rtrim(number_format($row['numerator'], 2, '.', ''), '0'), '.') }}</td>
                @endforeach
            </tr>
            <tr style="height:26px;" class="bg-blue-100 text-center">
                <td colspan="12" class="border border-gray-600 t-head">NIVEL DE CUMPLIMIENTO {{ strtoupper($indicator->name) }}</td>
            </tr>
            <tr style="height:26px;" class="text-center">
                @foreach ($sheetRows as $row)
                    <td class="border border-gray-600 t-head {{ $row['complies'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ number_format($row['result_percentage'], 2) }}%
                    </td>
                @endforeach
            </tr>
            <tr style="height:26px;" class="text-center">
                @for ($i = 0; $i < 12; $i++)
                    <td class="border border-gray-600 t-head">{{ $indicator->target_operator }} {{ number_format((float) $indicator->target_value, 0) }}%</td>
                @endfor
            </tr>
            <tr style="height:26px;" class="text-center">
                @for ($i = 0; $i < 12; $i++)
                    <td class="border border-gray-600 t-head">CRITICO {{ number_format((float) ($indicator->critical_value ?? 0), 0) }}%</td>
                @endfor
            </tr>
        </table>

        <div class="border border-gray-600 border-t-0" style="height:418px; width:888px; min-width:888px;">
            <div class="h-[38px] border-b border-gray-600 flex items-center justify-center t-head bg-gray-100">GRAFICOS</div>
            <div id="ft-op-01-chart" data-chart='@json($chartPayload)' class="w-full h-[380px]"></div>
        </div>

        <table class="border-collapse table-fixed text-[13px] text-black border border-gray-600 border-t-0" style="min-width: 888px;">
            <colgroup>
                <col style="width:74px;">
                <col style="width:74px;">
                <col style="width:502px;">
                <col style="width:74px;">
                <col style="width:74px;">
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
</div>
