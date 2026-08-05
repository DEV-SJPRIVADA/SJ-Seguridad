@php
    $colCount = 14;
    $pdfTwoPages = (bool) ($pdfSplitBeforeAnalysis ?? $pdfSplitAtCharts ?? false);
@endphp

<table class="sheet-table">
    <tr>
        <td colspan="2" rowspan="4" style="text-align:center;">
            @if (! empty($logoPath) && file_exists($logoPath))
                <img src="{{ $logoPath }}" alt="Logo" style="width:70px; height:auto;">
            @endif
        </td>
        <td colspan="8" rowspan="4" class="sheet-title">FICHA DEL INDICADOR DE GESTION</td>
        <td colspan="4">{{ $indicator->code }}</td>
    </tr>
    <tr><td colspan="4">{{ ($months[$selectedMonth] ?? 'Mes').' de '.$selectedYear }}</td></tr>
    <tr><td colspan="4">Version 02</td></tr>
    <tr><td colspan="4">Pagina {{ $pdfTwoPages ? '1 de 2' : '1 de 1' }}</td></tr>
    <tr><td colspan="{{ $colCount }}" class="sheet-head">NOMBRE DEL INDICADOR</td></tr>
    <tr><td colspan="{{ $colCount }}" class="sheet-title">{{ strtoupper($indicator->name) }}</td></tr>
    <tr>
        <td colspan="9" class="sheet-head">OBJETIVO</td>
        <td colspan="5" class="sheet-head">PROCESO</td>
    </tr>
    <tr>
        <td colspan="9">Determinar el impacto de los siniestros o reclamos en la facturacion de la empresa.</td>
        <td colspan="5" style="text-align:center;">Operaciones y Gestion de Riesgos</td>
    </tr>
    <tr>
        <td colspan="3" class="sheet-head">UNIDAD MEDIDA</td>
        <td class="sheet-head">META</td>
        <td colspan="3" class="sheet-head">FRECUENCIA DE MEDICION</td>
        <td colspan="2" class="sheet-head">TENDENCIA</td>
        <td colspan="5" class="sheet-head">INSUMOS PARA LA MEDICION</td>
    </tr>
    <tr style="text-align:center;">
        <td colspan="3">{{ ucfirst((string) ($indicator->unit ?? 'Porcentaje')) }}</td>
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
        <td colspan="5">FO-GI-06 Control de No Conformidades / Reporte clientes</td>
    </tr>
    <tr>
        <td colspan="3" class="sheet-head">CRITICO</td>
        <td>{{ number_format((float) ($indicator->critical_value ?? 0), 0) }}%</td>
        <td colspan="10"></td>
    </tr>
    <tr><td colspan="{{ $colCount }}" class="sheet-head">FORMULA</td></tr>
    <tr><td colspan="{{ $colCount }}" style="text-align:center;">{{ $indicator->formula_description }}</td></tr>
    <tr><td colspan="{{ $colCount }}" class="sheet-head">RESPONSABILIDADES</td></tr>
    <tr>
        <td colspan="5" class="sheet-head">RESULTADOS Y MEDICION</td>
        <td colspan="5" class="sheet-head">RESULTADOS</td>
        <td colspan="4" class="sheet-head">MEDICION</td>
    </tr>
    <tr style="text-align:center;">
        <td colspan="5">Director de Operaciones / Director(a) Financiero</td>
        <td colspan="5">%</td>
        <td colspan="4">No. siniestros / No. de servicios</td>
    </tr>
</table>

<table class="sheet-table" style="border-top:none;">
    <tr><td colspan="{{ $colCount }}" class="sheet-head">RESULTADOS</td></tr>
    <tr class="sheet-blue">
        <td>CRITERIO</td>
        @foreach (['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'] as $m)
            <td>{{ $m }}</td>
        @endforeach
        <td>TOTAL</td>
    </tr>
    <tr style="text-align:center;">
        <td class="sheet-head">TOTAL FACTURACION MENSUAL</td>
        @for ($i = 1; $i <= 12; $i++)
            <td>$ {{ number_format($financeRows['facturacion'][$i] ?? 0, 0, ',', '.') }}</td>
        @endfor
        <td>$ {{ number_format($financeRows['totals']['facturacion'] ?? 0, 0, ',', '.') }}</td>
    </tr>
    <tr style="text-align:center;">
        <td class="sheet-head">VALOR PAGADO MENSUAL</td>
        @for ($i = 1; $i <= 12; $i++)
            <td>$ {{ number_format($financeRows['pagado'][$i] ?? 0, 0, ',', '.') }}</td>
        @endfor
        <td>$ {{ number_format($financeRows['totals']['pagado'] ?? 0, 0, ',', '.') }}</td>
    </tr>
    <tr style="text-align:center;" class="sheet-green">
        <td class="sheet-head">% CUMPLIMIENTO</td>
        @for ($i = 1; $i <= 12; $i++)
            <td>{{ number_format($financeRows['cumplimiento'][$i] ?? 0, 2) }}%</td>
        @endfor
        <td>{{ number_format($financeRows['totals']['cumplimiento'] ?? 0, 2) }}%</td>
    </tr>
    <tr style="text-align:center;">
        <td class="sheet-head">META</td>
        @for ($i = 0; $i < 13; $i++)
            <td>{{ number_format((float) $indicator->target_value, 0) }}%</td>
        @endfor
    </tr>
    <tr style="text-align:center;">
        <td class="sheet-head">CRITICO</td>
        @for ($i = 0; $i < 13; $i++)
            <td>{{ number_format((float) ($indicator->critical_value ?? 0), 0) }}%</td>
        @endfor
    </tr>
</table>

<div class="chart-box">
    @if (! empty($chartImages['finance']))
        <img src="{{ $chartImages['finance'] }}" alt="Grafico facturacion">
    @endif
</div>

<table class="sheet-table" style="border-top:none;">
    <tr><td colspan="{{ $colCount }}" class="sheet-head">RESULTADOS POR CANTIDAD DE CLIENTES</td></tr>
    <tr class="sheet-blue">
        <td>CRITERIO</td>
        @foreach (['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'] as $m)
            <td>{{ $m }}</td>
        @endforeach
        <td>TOTAL</td>
    </tr>
    <tr style="text-align:center;">
        <td class="sheet-head">TOTAL DE CLIENTES MENSUAL</td>
        @for ($i = 1; $i <= 12; $i++)
            <td>{{ number_format($incidentRows['clientes'][$i] ?? 0, 0, ',', '.') }}</td>
        @endfor
        <td>{{ number_format($incidentRows['totals']['clientes'] ?? 0, 0, ',', '.') }}</td>
    </tr>
    <tr style="text-align:center;">
        <td class="sheet-head">TOTAL SINIESTROS MENSUAL</td>
        @for ($i = 1; $i <= 12; $i++)
            <td>{{ number_format($incidentRows['siniestros'][$i] ?? 0, 0, ',', '.') }}</td>
        @endfor
        <td>{{ number_format($incidentRows['totals']['siniestros'] ?? 0, 0, ',', '.') }}</td>
    </tr>
    <tr style="text-align:center;" class="sheet-green">
        <td class="sheet-head">% SINIESTROS</td>
        @for ($i = 1; $i <= 12; $i++)
            <td>{{ number_format($incidentRows['porcentaje'][$i] ?? 0, 2) }}%</td>
        @endfor
        <td>{{ number_format($incidentRows['totals']['porcentaje'] ?? 0, 2) }}%</td>
    </tr>
</table>

<div class="chart-box">
    @if (! empty($chartImages['incident']))
        <img src="{{ $chartImages['incident'] }}" alt="Grafico siniestros">
    @endif
</div>

@for ($q = 1; $q <= 4; $q++)
    <table class="sheet-table" style="border-top:none; margin-top:8px;">
        <tr class="sheet-blue">
            <td style="width:28%;">TIPO DE SINIESTRO</td>
            <td>CANTIDAD</td>
            <td>%</td>
            <td>PERIODO</td>
            <td style="width:45%;">GRAFICO</td>
        </tr>
        @foreach (($quarterlyTables[$q]['rows'] ?? []) as $idx => $row)
            <tr style="text-align:center;">
                <td>{{ strtoupper($row['type']) }}</td>
                <td>{{ number_format($row['qty'], 0, ',', '.') }}</td>
                <td>{{ number_format($row['pct'], 2) }}%</td>
                @if ($idx === 0)
                    <td rowspan="{{ count($quarterlyTables[$q]['rows'] ?? []) + 1 }}" class="sheet-head">
                        {{ $q }}{{ ['ER', 'DO', 'ER', 'TO'][$q - 1] }} TRIMESTRE
                    </td>
                @endif
                @if ($idx === 0)
                    <td rowspan="{{ count($quarterlyTables[$q]['rows'] ?? []) + 1 }}">
                        @if (! empty($chartImages['quarter_'.$q]))
                            <img src="{{ $chartImages['quarter_'.$q] }}" alt="Grafico trimestre {{ $q }}" style="max-width:280px;">
                        @endif
                    </td>
                @endif
            </tr>
        @endforeach
        <tr style="text-align:center;" class="sheet-head">
            <td>TOTAL</td>
            <td>{{ number_format($quarterlyTables[$q]['total_qty'] ?? 0, 0, ',', '.') }}</td>
            <td>100%</td>
        </tr>
    </table>
@endfor

@if ($pdfTwoPages)
<div class="pdf-sheet-page-2">
    <p style="text-align:right; font-size:9px; color:#64748b; margin:0 0 6px 0;">Pagina 2 de 2 — {{ $indicator->code }}</p>
@endif

<table class="sheet-table" style="border-top:none; margin-top:8px;">
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
