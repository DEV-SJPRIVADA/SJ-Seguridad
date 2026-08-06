<p class="text-small indicadores-help-text">Campos obligatorios: datos del indicador y analisis en modal.</p>

<div class="dashboard-stat-grid indicadores-metrics-grid">
    <div class="card kpi-card">
        <p class="text-caption">Resultado %</p>
        <p class="kpi-value"><span data-metric="result">{{ number_format($resultPercentage, 2) }}</span>%</p>
    </div>
    <div class="card kpi-card">
        <p class="text-caption">Semaforo</p>
        <p class="kpi-value">
            <span class="status-pill {{ $complies ? 'status-pill--req-contratado' : 'status-pill--req-cancelada' }}" data-metric="semaforo-pill">
                <span data-metric="semaforo">{{ $semaforo }}</span>
            </span>
        </p>
    </div>
    <div class="card kpi-card">
        <p class="text-caption">Cumple</p>
        <p class="kpi-value"><span data-metric="complies">{{ $complies ? 'SI' : 'NO' }}</span></p>
    </div>
    <div class="card kpi-card">
        <p class="text-caption">Mejora</p>
        <p class="kpi-value">
            @if ($readOnly ?? false)
                {{ ($improvementId ?? null) ? 'SI' : 'NO' }}
            @else
                <button type="button" class="btn btn--secondary btn--sm js-open-improvement-modal" @disabled($isPeriodClosed)>
                    {{ $improvementId ? 'SI' : 'NO' }}
                </button>
            @endif
        </p>
    </div>
</div>

@if ($isConsolidadoView ?? false)
    <div class="indicadores-actions">
        @can('operations.export')
            @if (empty($exportUserId))
                <a href="{{ route('indicadores.export.consolidado.excel', ['indicator' => $indicator->code, 'year' => $selectedYear, 'month' => $selectedMonth]) }}" class="btn btn--secondary btn--sm">Exportar Excel</a>
                <a href="{{ route('indicadores.export.consolidado.pdf', ['indicator' => $indicator->code, 'year' => $selectedYear, 'month' => $selectedMonth]) }}" class="btn btn--secondary btn--sm">Exportar PDF</a>
            @else
                <a href="{{ route('indicadores.export.leader.excel', ['indicator' => $indicator->code, 'year' => $selectedYear, 'month' => $selectedMonth, 'user_id' => $exportUserId]) }}" class="btn btn--secondary btn--sm">Exportar Excel</a>
                <a href="{{ route('indicadores.export.leader.pdf', ['indicator' => $indicator->code, 'year' => $selectedYear, 'month' => $selectedMonth, 'user_id' => $exportUserId]) }}" class="btn btn--secondary btn--sm">Exportar PDF</a>
            @endif
        @endcan
    </div>
@endif
