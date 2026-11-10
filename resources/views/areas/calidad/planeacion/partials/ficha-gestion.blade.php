@php
    /** @var array<string, mixed> $ficha */
    $unitSuffix = $ficha['unit_suffix'] ?? '';
    $months = $ficha['months'] ?? [];
    $fields = $ficha['fields'] ?? [];
@endphp

{{-- Ficha de gestión tipo FT-OP-01: cabecera, filas ene–dic por campo, gráfico y análisis. --}}
<div class="panel indicadores-sheet-panel planeacion-ficha-sheet ftop01-sheet" style="margin:0;">
    <div class="panel__body">
        <table class="planeacion-ficha-sheet__table border-collapse table-fixed text-[13px] text-black" style="min-width: 888px;">
            <colgroup>
                @for ($c = 0; $c < 12; $c++)
                    <col style="width:74px;">
                @endfor
            </colgroup>

            <tr style="height:26px;">
                <td colspan="2" rowspan="4" class="border border-gray-600 planeacion-ficha-sheet__center align-middle bg-gray-100">
                    <x-application-logo class="mx-auto h-20 w-20 text-sky-800" />
                </td>
                <td colspan="7" rowspan="4" class="border border-gray-600 planeacion-ficha-sheet__center align-middle bg-gray-50 t-title">
                    FICHA DEL INDICADOR DE GESTIÓN
                </td>
                <td colspan="3" class="border border-gray-600 t-body">{{ $indicator->code }}</td>
            </tr>
            <tr style="height:26px;">
                <td colspan="3" class="border border-gray-600 px-2 t-body">{{ $ficha['period_label'] }}</td>
            </tr>
            <tr style="height:26px;">
                <td colspan="3" class="border border-gray-600 px-2 t-body">Versión {{ str_pad((string) $ficha['version_number'], 2, '0', STR_PAD_LEFT) }}</td>
            </tr>
            <tr style="height:26px;">
                <td colspan="3" class="border border-gray-600 px-2 t-body">Página 1 de 1</td>
            </tr>

            <tr style="height:26px;">
                <td colspan="12" class="border border-gray-600 t-head">NOMBRE DEL INDICADOR</td>
            </tr>
            <tr style="height:26px;">
                <td colspan="12" class="border border-gray-600 t-body">{{ $ficha['name'] }}</td>
            </tr>
            <tr style="height:26px;">
                <td colspan="8" class="border border-gray-600 t-head">OBJETIVO</td>
                <td colspan="4" class="border border-gray-600 t-head">PROCESO</td>
            </tr>
            <tr style="height:26px;">
                <td colspan="8" class="border border-gray-600 t-body">{{ $ficha['objective'] }}</td>
                <td colspan="4" class="border border-gray-600 t-body">{{ $ficha['process'] }}</td>
            </tr>
            <tr style="height:26px;">
                <td colspan="2" class="border border-gray-600 t-head">UNIDAD MEDIDA</td>
                <td class="border border-gray-600 t-head">META</td>
                <td colspan="3" class="border border-gray-600 t-head">FRECUENCIA DE MEDICIÓN</td>
                <td colspan="2" class="border border-gray-600 t-head">TENDENCIA</td>
                <td colspan="4" class="border border-gray-600 t-head">INSUMOS PARA LA MEDICIÓN</td>
            </tr>
            <tr style="height:26px;">
                <td colspan="2" class="border border-gray-600 t-body">{{ $ficha['unit_label'] }}</td>
                <td class="border border-gray-600 t-body">
                    {{ $ficha['target_operator'] }} {{ $ficha['target_value'] !== null ? number_format((float) $ficha['target_value'], 0) : '—' }}{{ $unitSuffix }}
                </td>
                <td colspan="3" class="border border-gray-600 t-body">{{ $ficha['frequency'] }}</td>
                <td colspan="2" class="border border-gray-600 t-body">{{ $ficha['tendency'] }}</td>
                <td colspan="4" class="border border-gray-600 t-body">{{ $ficha['insumos'] }}</td>
            </tr>
            <tr style="height:26px;">
                <td colspan="2" class="border border-gray-600 t-head">CRÍTICO</td>
                <td class="border border-gray-600 t-body">
                    {{ $ficha['critical_value'] !== null ? number_format((float) $ficha['critical_value'], 0).$unitSuffix : '—' }}
                </td>
                <td colspan="9" class="border border-gray-600 t-body"></td>
            </tr>
            <tr style="height:26px;">
                <td colspan="12" class="border border-gray-600 t-head">FÓRMULA</td>
            </tr>
            <tr style="height:26px;">
                <td colspan="12" class="border border-gray-600 t-body">({{ $ficha['formula_display'] ?: 'N.A.' }})</td>
            </tr>
            <tr style="height:26px;">
                <td colspan="12" class="border border-gray-600 t-head">RESPONSABILIDADES</td>
            </tr>
            <tr style="height:26px;">
                <td colspan="4" class="border border-gray-600 t-head">RESULTADOS Y MEDICIÓN</td>
                <td colspan="4" class="border border-gray-600 t-head">RESULTADOS</td>
                <td colspan="4" class="border border-gray-600 t-head">MEDICIÓN</td>
            </tr>
            <tr style="height:26px;">
                <td colspan="4" class="border border-gray-600 t-body">{{ $ficha['responsibilities']['measurement'] }}</td>
                <td colspan="4" class="border border-gray-600 t-body">{{ $ficha['responsibilities']['results'] }}</td>
                <td colspan="4" class="border border-gray-600 t-body">{{ $ficha['responsibilities']['measure'] }}</td>
            </tr>
            <tr style="height:26px;">
                <td colspan="12" class="border border-gray-600 t-head">RESULTADOS</td>
            </tr>
            <tr style="height:26px;">
                @foreach ($months as $row)
                    <td class="border border-gray-600 t-head planeacion-ficha-sheet__center">{{ $row['abbrev'] }}</td>
                @endforeach
            </tr>
            <tr style="height:26px;">
                <td colspan="12" class="border border-gray-600 t-head">{{ $ficha['year'] }}</td>
            </tr>

            @foreach ($fields as $field)
                <tr style="height:26px;">
                    <td colspan="12" class="border border-gray-600 t-head">{{ $field['label'] }}</td>
                </tr>
                <tr style="height:26px;">
                    @foreach ($months as $row)
                        <td class="border border-gray-600 t-body planeacion-ficha-sheet__center">{{ isset($row['field_values'][$field['key']]) && $row['field_values'][$field['key']] !== null ? rtrim(rtrim(number_format((float) $row['field_values'][$field['key']], 2, '.', ''), '0'), '.') : '' }}</td>
                    @endforeach
                </tr>
            @endforeach

            <tr style="height:26px;">
                <td colspan="12" class="border border-gray-600 t-head">NIVEL DE CUMPLIMIENTO {{ strtoupper((string) $ficha['name']) }}</td>
            </tr>
            <tr style="height:26px;">
                @foreach ($months as $row)
                    <td class="border border-gray-600 t-body planeacion-ficha-sheet__center {{ $row['has_data'] ? ($row['complies'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700') : '' }}">
                        {{ $row['has_data'] && $row['result'] !== null ? number_format((float) $row['result'], 2).$unitSuffix : '' }}
                    </td>
                @endforeach
            </tr>
            <tr style="height:26px;">
                @foreach ($months as $row)
                    <td class="border border-gray-600 t-body planeacion-ficha-sheet__center">{{ $row['target_display'] }}</td>
                @endforeach
            </tr>
            <tr style="height:26px;">
                @foreach ($months as $row)
                    <td class="border border-gray-600 t-body planeacion-ficha-sheet__center">{{ $row['critical_display'] }}</td>
                @endforeach
            </tr>
        </table>

        <div class="planeacion-ficha-sheet__chart-wrap">
            <div class="planeacion-ficha-sheet__chart-head t-head">GRÁFICOS</div>
            <div id="planeacion-ficha-chart" data-chart='@json($ficha['chart'])' class="planeacion-ficha-sheet__chart"></div>
        </div>

        <table class="planeacion-ficha-sheet__table planeacion-ficha-sheet__table--analysis border-collapse table-fixed text-[13px] text-black" style="min-width: 888px;">
            <colgroup>
                <col style="width:74px;">
                <col style="width:74px;">
                <col style="width:502px;">
                <col style="width:74px;">
                <col style="width:74px;">
            </colgroup>
            <tr style="height:53px;">
                <td colspan="3" class="border border-gray-600 t-head planeacion-ficha-sheet__banner">ANÁLISIS DE RESULTADOS</td>
                <td class="border border-gray-600 t-head planeacion-ficha-sheet__center planeacion-ficha-sheet__banner">CUMPLE</td>
                <td class="border border-gray-600 t-head planeacion-ficha-sheet__center planeacion-ficha-sheet__banner">MEJORA</td>
            </tr>
            @foreach ($months as $row)
                <tr style="height:53px;">
                    <td class="border border-gray-600 t-head planeacion-ficha-sheet__center [writing-mode:vertical-rl] rotate-180">{{ $ficha['year'] }}</td>
                    <td class="border border-gray-600 t-head planeacion-ficha-sheet__center">{{ $row['abbrev'] }}</td>
                    <td class="border border-gray-600 align-top t-body">{{ $row['has_data'] ? $row['analysis'] : '' }}</td>
                    <td class="border border-gray-600 t-body planeacion-ficha-sheet__center">{{ $row['has_data'] ? ($row['complies'] ? 'Sí' : 'No') : '' }}</td>
                    <td class="border border-gray-600 t-body planeacion-ficha-sheet__center">{{ $row['has_data'] ? ($row['improvement'] ? 'Sí' : 'No') : '' }}</td>
                </tr>
            @endforeach
        </table>
    </div>
</div>
