<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 landscape; margin: 5mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 7px; color: #1e293b; margin: 0; padding: 0; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; page-break-inside: avoid; }
        td { border: 1px solid #4b5563; padding: 1px 3px; vertical-align: middle; line-height: 1.15; }
        .sheet-head { background: #f3f4f6; font-weight: bold; text-align: center; }
        .sheet-title { background: #f9fafb; font-weight: bold; text-align: center; font-size: 10px; }
        .sheet-navy { background: #1e2743; color: #fff; font-weight: bold; text-align: center; }
        .sheet-center { text-align: center; }
        .sheet-green { background: #dcfce7; color: #15803d; font-weight: bold; text-align: center; }
        .sheet-red { background: #fee2e2; color: #b91c1c; font-weight: bold; text-align: center; }
        .logo { width: 42px; height: auto; }
        .chart-cell { text-align: center; padding: 1px; height: 126px; }
        .chart-img { width: 100%; height: 120px; }
        .analysis-text { font-size: 6.5px; white-space: pre-line; }
    </style>
</head>
<body>
    @php
        $unitSuffix = $ficha['unit_suffix'] ?? '';
        $months = $ficha['months'] ?? [];
        $fields = $ficha['fields'] ?? [];
        $responsibilities = $ficha['responsibilities'] ?? [];
        $chartImage = $chartImage ?? null;
        $target = ($ficha['target_value'] ?? null) !== null
            ? ($ficha['target_operator'] ?? '>=').' '.number_format((float) $ficha['target_value'], 0).$unitSuffix
            : '—';
        $critical = ($ficha['critical_value'] ?? null) !== null
            ? number_format((float) $ficha['critical_value'], 0).$unitSuffix
            : '—';
    @endphp

    {{-- Una sola tabla A4 horizontal para que la ficha, el gráfico y el análisis quepan en una hoja. --}}
    <table>
        <tr>
            <td colspan="2" rowspan="4" class="sheet-center">
                @if (! empty($logoPath))
                    <img src="{{ $logoPath }}" alt="Logo" class="logo">
                @endif
            </td>
            <td colspan="7" rowspan="4" class="sheet-title">FICHA DEL INDICADOR DE GESTIÓN</td>
            <td colspan="3">{{ $indicator->code }}</td>
        </tr>
        <tr>
            <td colspan="3">{{ $ficha['period_label'] }}</td>
        </tr>
        <tr>
            <td colspan="3">Versión {{ str_pad((string) $ficha['version_number'], 2, '0', STR_PAD_LEFT) }}</td>
        </tr>
        <tr>
            <td colspan="3">Página 1 de 1</td>
        </tr>
        <tr><td colspan="12" class="sheet-head">NOMBRE DEL INDICADOR</td></tr>
        <tr><td colspan="12" class="sheet-center">{{ $ficha['name'] }}</td></tr>
        <tr>
            <td colspan="8" class="sheet-head">OBJETIVO</td>
            <td colspan="4" class="sheet-head">PROCESO</td>
        </tr>
        <tr>
            <td colspan="8">{{ $ficha['objective'] }}</td>
            <td colspan="4" class="sheet-center">{{ $ficha['process'] }}</td>
        </tr>
        <tr>
            <td colspan="2" class="sheet-head">UNIDAD MEDIDA</td>
            <td class="sheet-head">META</td>
            <td colspan="3" class="sheet-head">FRECUENCIA DE MEDICIÓN</td>
            <td colspan="2" class="sheet-head">TENDENCIA</td>
            <td colspan="4" class="sheet-head">INSUMOS PARA LA MEDICIÓN</td>
        </tr>
        <tr class="sheet-center">
            <td colspan="2">{{ $ficha['unit_label'] }}</td>
            <td>{{ $target }}</td>
            <td colspan="3">{{ $ficha['frequency'] }}</td>
            <td colspan="2">{{ $ficha['tendency'] }}</td>
            <td colspan="4">{{ $ficha['insumos'] }}</td>
        </tr>
        <tr>
            <td colspan="2" class="sheet-head">CRÍTICO</td>
            <td class="sheet-center">{{ $critical }}</td>
            <td colspan="9"></td>
        </tr>
        <tr><td colspan="12" class="sheet-head">FÓRMULA</td></tr>
        <tr><td colspan="12" class="sheet-center">({{ $ficha['formula_display'] ?: 'N.A.' }})</td></tr>
        <tr><td colspan="12" class="sheet-head">RESPONSABILIDADES</td></tr>
        <tr>
            <td colspan="4" class="sheet-head">RESULTADOS Y MEDICIÓN</td>
            <td colspan="4" class="sheet-head">RESULTADOS</td>
            <td colspan="4" class="sheet-head">MEDICIÓN</td>
        </tr>
        <tr class="sheet-center">
            <td colspan="4">{{ $responsibilities['measurement'] ?? '—' }}</td>
            <td colspan="4">{{ $responsibilities['results'] ?? '—' }}</td>
            <td colspan="4">{{ $responsibilities['measure'] ?? '—' }}</td>
        </tr>
        <tr><td colspan="12" class="sheet-head">RESULTADOS</td></tr>
        <tr>
            @foreach ($months as $row)
                <td class="sheet-head">{{ $row['abbrev'] }}</td>
            @endforeach
        </tr>
        <tr><td colspan="12" class="sheet-head">{{ $ficha['year'] }}</td></tr>

        @foreach ($fields as $field)
            <tr><td colspan="12" class="sheet-head">{{ $field['label'] }}</td></tr>
            <tr>
                @foreach ($months as $row)
                    <td class="sheet-center">
                        {{ isset($row['field_values'][$field['key']]) && $row['field_values'][$field['key']] !== null ? rtrim(rtrim(number_format((float) $row['field_values'][$field['key']], 2, '.', ''), '0'), '.') : '' }}
                    </td>
                @endforeach
            </tr>
        @endforeach

        <tr><td colspan="12" class="sheet-head">NIVEL DE CUMPLIMIENTO {{ strtoupper((string) $ficha['name']) }}</td></tr>
        <tr>
            @foreach ($months as $row)
                <td class="{{ $row['has_data'] ? ($row['complies'] ? 'sheet-green' : 'sheet-red') : 'sheet-center' }}">
                    {{ $row['has_data'] && $row['result'] !== null ? number_format((float) $row['result'], 2).$unitSuffix : '' }}
                </td>
            @endforeach
        </tr>
        <tr>
            @foreach ($months as $row)
                <td class="sheet-center">{{ $row['target_display'] }}</td>
            @endforeach
        </tr>
        <tr>
            @foreach ($months as $row)
                <td class="sheet-center">{{ $row['critical_display'] }}</td>
            @endforeach
        </tr>
        <tr><td colspan="12" class="sheet-navy">GRÁFICOS</td></tr>
        <tr>
            <td colspan="12" class="chart-cell">
                @if (! empty($chartImage))
                    <img src="{{ $chartImage }}" alt="Gráfico" class="chart-img">
                @else
                    Gráfico no disponible.
                @endif
            </td>
        </tr>
        <tr>
            <td colspan="8" class="sheet-navy">ANÁLISIS DE RESULTADOS</td>
            <td colspan="2" class="sheet-navy">CUMPLE</td>
            <td colspan="2" class="sheet-navy">MEJORA</td>
        </tr>
        @foreach ($months as $row)
            <tr>
                <td class="sheet-head sheet-center">{{ $ficha['year'] }}</td>
                <td class="sheet-head sheet-center">{{ $row['abbrev'] }}</td>
                <td colspan="6" class="analysis-text">{{ $row['has_data'] ? $row['analysis'] : '' }}</td>
                <td colspan="2" class="sheet-center">{{ $row['has_data'] ? ($row['complies'] ? 'Sí' : 'No') : '' }}</td>
                <td colspan="2" class="sheet-center">{{ $row['has_data'] ? ($row['improvement'] ? 'Sí' : 'No') : '' }}</td>
            </tr>
        @endforeach
    </table>
</body>
</html>
