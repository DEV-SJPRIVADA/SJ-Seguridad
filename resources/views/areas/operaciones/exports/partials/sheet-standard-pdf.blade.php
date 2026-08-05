@php
    $objective = 'Medir el grado de cumplimiento del indicador.';
    $process = 'Gestion Operativa';
    $inputs = 'Base de datos del indicador';
    $colCount = 12;
    $pdfTwoPages = (bool) ($pdfSplitBeforeAnalysis ?? $pdfSplitAtCharts ?? false);
@endphp

<table class="sheet-table">
    <tr>
        <td colspan="2" rowspan="4" style="text-align:center;">
            @if (! empty($logoPath) && file_exists($logoPath))
                <img src="{{ $logoPath }}" alt="Logo" style="width:70px; height:auto;">
            @endif
        </td>
        <td colspan="7" rowspan="4" class="sheet-title">FICHA DEL INDICADOR DE GESTION</td>
        <td colspan="3">{{ $indicator->code }}</td>
    </tr>
    <tr>
        <td colspan="3">{{ ($months[$selectedMonth] ?? 'Mes').' de '.$selectedYear }}</td>
    </tr>
    <tr>
        <td colspan="3">Version 02</td>
    </tr>
    <tr>
        <td colspan="3">Pagina {{ $pdfTwoPages ? '1 de 2' : '1 de 1' }}</td>
    </tr>
    <tr><td colspan="{{ $colCount }}" class="sheet-head">NOMBRE DEL INDICADOR</td></tr>
    <tr><td colspan="{{ $colCount }}" class="sheet-title">{{ $indicator->name }}</td></tr>
    <tr>
        <td colspan="8" class="sheet-head">OBJETIVO</td>
        <td colspan="4" class="sheet-head">PROCESO</td>
    </tr>
    <tr>
        <td colspan="8">{{ $objective }}</td>
        <td colspan="4" style="text-align:center;">{{ $process }}</td>
    </tr>
    <tr>
        <td colspan="2" class="sheet-head">UNIDAD MEDIDA</td>
        <td class="sheet-head">META</td>
        <td colspan="3" class="sheet-head">FRECUENCIA DE MEDICION</td>
        <td colspan="2" class="sheet-head">TENDENCIA</td>
        <td colspan="4" class="sheet-head">INSUMOS PARA LA MEDICION</td>
    </tr>
    <tr style="text-align:center;">
        <td colspan="2">{{ ucfirst((string) ($indicator->unit ?? 'Porcentaje')) }}</td>
        <td>{{ number_format((float) $indicator->target_value, 0) }}%</td>
        <td colspan="3">{{ ucfirst($indicator->frequency ?? 'Mensual') }}</td>
        <td colspan="2">
            @php
                $tendency = match ($indicator->target_operator ?? '>=') {
                    '<=' => 'Decreciente',
                    '==' => 'Objetivo exacto',
                    default => 'Creciente',
                };
            @endphp
            {{ $tendency }}
        </td>
        <td colspan="4">{{ $inputs }}</td>
    </tr>
    <tr>
        <td colspan="2" class="sheet-head">CRITICO</td>
        <td>{{ number_format((float) ($indicator->critical_value ?? 0), 0) }}%</td>
        <td colspan="9"></td>
    </tr>
    <tr><td colspan="{{ $colCount }}" class="sheet-head">FORMULA</td></tr>
    <tr><td colspan="{{ $colCount }}" style="text-align:center;">({{ $indicator->formula_description ?? 'N/A' }})</td></tr>
    <tr><td colspan="{{ $colCount }}" class="sheet-head">RESPONSABILIDADES</td></tr>
    <tr>
        <td colspan="4" class="sheet-head">RESULTADOS Y MEDICION</td>
        <td colspan="4" class="sheet-head">RESULTADOS</td>
        <td colspan="4" class="sheet-head">MEDICION</td>
    </tr>
    <tr style="text-align:center;">
        <td colspan="4">Lider de Gestion Operativa</td>
        <td colspan="4">N.A.</td>
        <td colspan="4">N.A.</td>
    </tr>
    <tr><td colspan="{{ $colCount }}" class="sheet-head">RESULTADOS</td></tr>
    <tr class="sheet-blue">
        @foreach ($sheetRows as $row)
            <td>{{ $row['month'] }}</td>
        @endforeach
    </tr>
    <tr class="sheet-head"><td colspan="{{ $colCount }}">{{ $selectedYear }}</td></tr>
    <tr class="sheet-blue"><td colspan="{{ $colCount }}">{{ $sheetDenominatorLabel }}</td></tr>
    <tr style="text-align:center;">
        @foreach ($sheetRows as $row)
            <td>{{ rtrim(rtrim(number_format($row['denominator'], 2, '.', ''), '0'), '.') }}</td>
        @endforeach
    </tr>
    <tr class="sheet-blue"><td colspan="{{ $colCount }}">{{ $sheetNumeratorLabel }}</td></tr>
    <tr style="text-align:center;">
        @foreach ($sheetRows as $row)
            <td>{{ rtrim(rtrim(number_format($row['numerator'], 2, '.', ''), '0'), '.') }}</td>
        @endforeach
    </tr>
    <tr class="sheet-blue"><td colspan="{{ $colCount }}">NIVEL DE CUMPLIMIENTO {{ strtoupper($indicator->name) }}</td></tr>
    <tr style="text-align:center;">
        @foreach ($sheetRows as $row)
            <td class="{{ $row['complies'] ? 'sheet-green' : 'sheet-red' }}">{{ number_format($row['result_percentage'], 2) }}%</td>
        @endforeach
    </tr>
    <tr style="text-align:center;">
        @for ($i = 0; $i < 12; $i++)
            <td class="sheet-head">{{ $indicator->target_operator }} {{ number_format((float) $indicator->target_value, 0) }}%</td>
        @endfor
    </tr>
    <tr style="text-align:center;">
        @for ($i = 0; $i < 12; $i++)
            <td class="sheet-head">CRITICO {{ number_format((float) ($indicator->critical_value ?? 0), 0) }}%</td>
        @endfor
    </tr>
</table>

<div class="chart-box">
    <div class="chart-title">GRAFICOS</div>
    @if (! empty($chartImages['main']))
        <img src="{{ $chartImages['main'] }}" alt="Grafico indicador">
    @else
        <p style="padding: 20px;">Grafico no disponible</p>
    @endif
</div>

@if ($pdfTwoPages)
<div class="pdf-sheet-page-2">
    <p style="text-align:right; font-size:9px; color:#64748b; margin:0 0 6px 0;">Pagina 2 de 2 — {{ $indicator->code }}</p>
@endif

<table class="sheet-table" style="border-top:none;">
    <tr class="sheet-head">
        <td colspan="3">ANALISIS DE RESULTADOS</td>
        <td>CUMPLE</td>
        <td>MEJORA</td>
    </tr>
    @foreach ($sheetRows as $row)
        <tr>
            <td class="sheet-head" style="text-align:center;">{{ $selectedYear }}</td>
            <td class="sheet-head" style="text-align:center;">{{ $row['month'] }}</td>
            <td>{{ $row['analysis'] }}</td>
            <td style="text-align:center;">{{ $row['has_capture'] ? ($row['complies'] ? 'SI' : 'NO') : '' }}</td>
            <td style="text-align:center;">{{ $row['has_capture'] ? ($row['improvement'] ? 'SI' : 'NO') : '' }}</td>
        </tr>
    @endforeach
</table>

@if ($pdfTwoPages)
</div>
@endif
