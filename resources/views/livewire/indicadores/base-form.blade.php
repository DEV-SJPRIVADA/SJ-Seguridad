<div class="indicadores-form space-y-4">
    <div class="panel" style="margin:0;">
        <div class="panel__body">
            <div class="filter-grid" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:0.75rem; align-items:end;">
                <div>
                    <label class="form-label">Ano</label>
                    <select wire:model.live="selectedYear" class="supply-input supply-select">
                        @foreach ($years as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Mes</label>
                    <select wire:model.live="selectedMonth" class="supply-input supply-select">
                        @foreach ($months as $monthNumber => $monthName)
                            <option value="{{ $monthNumber }}">{{ $monthName }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Jefe de operaciones</label>
                    <select wire:model.live="selectedOperationsLeaderId" class="supply-input supply-select">
                        @foreach ($operationsLeaders as $operationsLeader)
                            <option value="{{ $operationsLeader['id'] }}">{{ $operationsLeader['code'] }} - {{ $operationsLeader['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <span class="status-pill {{ $isPeriodClosed ? 'status-pill--req-cancelada' : 'status-pill--req-contratado' }}">
                        {{ $isPeriodClosed ? 'Periodo cerrado' : 'Periodo abierto' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="panel" style="border-color:#f43f5e; margin:0;">
            <div class="panel__body text-small">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
    @endif

    @if (session('status'))
        <div class="panel" style="border-color:#10b981; margin:0;">
            <div class="panel__body text-small">{{ session('status') }}</div>
        </div>
    @endif

    <div class="panel" style="margin:0;">
        <div class="panel__header">
            <h4 class="panel-title">{{ $indicator->code }} - {{ $indicator->name }}</h4>
        </div>
        <div class="panel__body form-stack">
            @include($fieldsView)

            <p class="text-small" style="color:#64748b;">
                Campos obligatorios: datos del indicador y analisis de resultados.
            </p>

            <div>
                <label class="form-label">Analisis de resultados</label>
                <textarea wire:model.live.debounce.300ms="analysisText" rows="5" class="supply-textarea" @disabled($isPeriodClosed)></textarea>
            </div>

            <div class="dashboard-stat-grid" style="margin:0;">
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
                        @if (! $complies)
                            <button type="button" wire:click="openImprovementModal" class="btn btn--secondary btn--sm">Registrar</button>
                        @else
                            NO
                        @endif
                    </p>
                </div>
            </div>

            <div style="display:flex; flex-wrap:wrap; gap:0.75rem;">
                <button type="button" wire:click="save" class="btn btn--primary" @disabled($isPeriodClosed)>Guardar mes</button>
                @can('operations.export')
                    <a href="{{ route('indicadores.export.leader.excel', ['indicator' => $indicator->code, 'year' => $selectedYear, 'month' => $selectedMonth, 'operations_leader_id' => $selectedOperationsLeaderId]) }}"
                       class="btn btn--secondary">Exportar Excel</a>
                    <a href="{{ route('indicadores.export.leader.pdf', ['indicator' => $indicator->code, 'year' => $selectedYear, 'month' => $selectedMonth, 'operations_leader_id' => $selectedOperationsLeaderId]) }}"
                       class="btn btn--secondary">Exportar PDF</a>
                @endcan
            </div>
        </div>
    </div>

    <div class="panel" style="margin:0;">
        <div class="panel__header"><h4 class="panel-title">Tendencia ultimos 3 meses</h4></div>
        <div class="panel__body">
            <table class="supply-table">
                <thead>
                    <tr>
                        <th>Periodo</th>
                        <th>Resultado</th>
                        <th>Semaforo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($trendRows as $row)
                        <tr>
                            <td>{{ $row['year'] }}-{{ str_pad((string) $row['month'], 2, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $row['result'] !== null ? number_format((float) $row['result'], 2).'%' : '-' }}</td>
                            <td>{{ $row['semaforo'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if ($showImprovementModal)
        <div class="indicadores-modal-backdrop">
            <div class="panel indicadores-modal">
                <div class="panel__header">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <h4 class="panel-title">Mejora obligatoria</h4>
                        <button type="button" wire:click="closeImprovementModal" class="btn btn--secondary btn--sm">Cerrar</button>
                    </div>
                </div>
                <div class="panel__body form-stack">
                    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:1rem;">
                        <div>
                            <label class="form-label">Analisis</label>
                            <textarea wire:model.defer="improvementAnalysis" rows="5" class="supply-textarea"></textarea>
                        </div>
                        <div>
                            <label class="form-label">Accion tomada</label>
                            <textarea wire:model.defer="improvementActionTaken" rows="5" class="supply-textarea"></textarea>
                        </div>
                        <div>
                            <label class="form-label">Accion definida</label>
                            <textarea wire:model.defer="improvementActionDefined" rows="5" class="supply-textarea"></textarea>
                        </div>
                    </div>
                    <div style="display:flex; justify-content:flex-end; gap:0.75rem;">
                        <button type="button" wire:click="closeImprovementModal" class="btn btn--secondary">Cancelar</button>
                        <button type="button" wire:click="saveImprovement" class="btn btn--primary">Guardar mejora</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
