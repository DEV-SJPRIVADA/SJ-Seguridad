<h4 class="section-title">{{ $indicator->code }} — {{ $indicator->name }}</h4>

@if (! empty($formFieldRows))
    <table class="fields-table">
        <thead>
            <tr>
                <th style="width: 35%;">Campo</th>
                <th>Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($formFieldRows as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td>{{ $row['value'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<table class="metrics-grid">
    <tr>
        <td>
            <div class="caption">Resultado %</div>
            <div class="value">{{ number_format((float) $resultPercentage, 2) }}%</div>
        </td>
        <td>
            <div class="caption">Semáforo</div>
            <div class="value">{{ $semaforo }}</div>
        </td>
        <td>
            <div class="caption">Cumple</div>
            <div class="value">{{ $complies ? 'Sí' : 'No' }}</div>
        </td>
        <td>
            <div class="caption">Mejora</div>
            <div class="value">{{ ($improvementId ?? null) ? 'Sí' : 'No' }}</div>
        </td>
    </tr>
</table>

@if (($improvementId ?? null) || trim((string) ($improvementAnalysis ?? '')) !== '' || trim((string) ($analysisText ?? '')) !== '')
    <div class="improvement-box">
        <strong>Análisis y mejora</strong>
        @if (trim((string) ($analysisText ?? '')) !== '')
            <p><strong>Análisis captura:</strong> {{ $analysisText }}</p>
        @endif
        @if (trim((string) ($improvementAnalysis ?? '')) !== '')
            <p><strong>Análisis mejora:</strong> {{ $improvementAnalysis }}</p>
        @endif
        @if (trim((string) ($improvementActionTaken ?? '')) !== '')
            <p><strong>Acción tomada:</strong> {{ $improvementActionTaken }}</p>
        @endif
        @if (trim((string) ($improvementActionDefined ?? '')) !== '')
            <p><strong>Acción definida:</strong> {{ $improvementActionDefined }}</p>
        @endif
        @if (trim((string) ($improvementRequired ?? '')) !== '')
            <p><strong>Mejora requerida:</strong> {{ $improvementRequired }}</p>
        @endif
    </div>
@endif

@if ($indicator->code === 'FT-OP-03')
    @include('areas.operaciones.exports.partials.sheet-ft-op-03-pdf')
@else
    @include('areas.operaciones.exports.partials.sheet-standard-pdf')
@endif
