<p class="text-small indicadores-help-text">Campos obligatorios: datos del indicador y analisis en modal.</p>

<div class="dashboard-stat-grid indicadores-metrics-grid">
    <div class="card kpi-card">
        <p class="text-caption">Resultado %</p>
        <p class="kpi-value">{{ number_format($resultPercentage, 2) }}%</p>
    </div>
    <div class="card kpi-card">
        <p class="text-caption">Semaforo</p>
        <p class="kpi-value">
            <span class="status-pill {{ $complies ? 'status-pill--req-contratado' : 'status-pill--req-cancelada' }}">{{ $semaforo }}</span>
        </p>
    </div>
    <div class="card kpi-card">
        <p class="text-caption">Cumple</p>
        <p class="kpi-value">{{ $complies ? 'SI' : 'NO' }}</p>
    </div>
    <div class="card kpi-card">
        <p class="text-caption">Mejora</p>
        <p class="kpi-value">
            <button type="button" wire:click="openImprovementModal" class="btn btn--secondary btn--sm" @disabled($isPeriodClosed)>
                {{ $improvementId ? 'SI' : 'NO' }}
            </button>
        </p>
    </div>
</div>

<div class="indicadores-actions">
    @can('operations.export')
        <a href="{{ route('indicadores.export.leader.excel', ['indicator' => $indicator->code, 'year' => $selectedYear, 'month' => $selectedMonth, 'operations_leader_id' => $selectedOperationsLeaderId]) }}" class="btn btn--secondary">Exportar Excel</a>
        <a href="{{ route('indicadores.export.leader.pdf', ['indicator' => $indicator->code, 'year' => $selectedYear, 'month' => $selectedMonth, 'operations_leader_id' => $selectedOperationsLeaderId]) }}" class="btn btn--secondary">Exportar PDF</a>
    @endcan
</div>
