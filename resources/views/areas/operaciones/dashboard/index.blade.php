@php
    $semaforoClass = fn (?string $semaforo) => match ($semaforo) {
        'VERDE' => 'status-pill--req-contratado',
        'AMARILLO', 'ATENCION' => 'status-pill--req-en_gestion',
        default => 'status-pill--req-cancelada',
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        @include('areas.operaciones.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container indicadores-board">
            <div class="panel">
                <div class="panel__header">
                    <div class="indicadores-panel-header">
                        <div>
                            <h3 class="panel-title">Dashboard General de Operaciones</h3>
                            <p class="panel-text">Consolidado mensual de indicadores FT-OP por usuario.</p>
                        </div>
                        @can('operations.export')
                            <div class="indicadores-filter-bar" style="margin:0;">
                                <a href="{{ route('indicadores.export.dashboard.pdf', ['year' => $year, 'month' => $month]) }}" class="btn btn--secondary btn--sm">
                                    Exportar PDF
                                </a>
                                <a href="{{ route('indicadores.export.management.pptx', ['year' => $year, 'month' => $month]) }}" class="btn btn--secondary btn--sm">
                                    Informe PPTX
                                </a>
                            </div>
                        @endcan
                    </div>
                </div>

                <div class="panel__body">
                    <form method="GET" class="indicadores-inline-form">
                        <div class="indicadores-filter-bar">
                            <div class="indicadores-field indicadores-field--xs">
                                <label class="form-label">Ano</label>
                                <select name="year" class="supply-input supply-select">
                                    @foreach ($years as $yearOption)
                                        <option value="{{ $yearOption }}" @selected($year === (int) $yearOption)>{{ $yearOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="indicadores-field indicadores-field--sm">
                                <label class="form-label">Mes</label>
                                <select name="month" class="supply-input supply-select">
                                    @foreach ($months as $monthNumber => $monthName)
                                        <option value="{{ $monthNumber }}" @selected($month === (int) $monthNumber)>{{ $monthName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="indicadores-field indicadores-field--action">
                                <button type="submit" class="btn btn--primary btn--sm">Aplicar</button>
                            </div>
                        </div>
                    </form>

                    <div class="dashboard-stat-grid">
                        <div class="card kpi-card">
                            <p class="text-caption">Score global ponderado</p>
                            <p class="kpi-value">{{ number_format($dashboard['global_score'], 2) }}%</p>
                        </div>
                        <div class="card kpi-card">
                            <p class="text-caption">Estado general</p>
                            <p class="kpi-value">
                                <span class="status-pill {{ $semaforoClass($dashboard['global_state'] === 'ESTABLE' ? 'VERDE' : ($dashboard['global_state'] === 'ATENCION' ? 'AMARILLO' : 'ROJO')) }}">
                                    {{ $dashboard['global_state'] }}
                                </span>
                            </p>
                        </div>
                        <div class="card kpi-card">
                            <p class="text-caption">Regla</p>
                            <p class="text-small">>=90 ESTABLE | 75-89 ATENCION | &lt;75 CRITICO</p>
                        </div>
                    </div>

                    <h4 class="indicadores-subpanel__title" style="margin-bottom:0.75rem;">KPIs del mes</h4>
                    <div class="indicadores-table-wrap">
                    <table class="supply-table indicadores-table indicadores-kpi-table">
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Indicador</th>
                                <th>Mes anterior ({{ $dashboard['previous_period']['label'] ?? '' }})</th>
                                <th>Resultado</th>
                                <th>Meta</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dashboard['kpis'] as $kpi)
                                <tr>
                                    <td class="indicadores-kpi-code">
                                        <a href="{{ $kpi['consolidado_url'] }}" class="btn btn--secondary btn--sm indicadores-code-link">{{ $kpi['indicator']->code }}</a>
                                    </td>
                                    <td class="indicadores-kpi-name">{{ $kpi['indicator']->name }}</td>
                                    <td>{{ $kpi['previous_result'] !== null ? number_format((float) $kpi['previous_result'], 2).'%' : '-' }}</td>
                                    <td>{{ $kpi['result'] !== null ? number_format((float) $kpi['result'], 2).'%' : '-' }}</td>
                                    <td>{{ $kpi['meta'] }}</td>
                                    <td>
                                        <span class="status-pill {{ $semaforoClass($kpi['semaforo']) }}">{{ $kpi['semaforo'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>

                    <div class="indicadores-split-grid">
                        <div class="panel indicadores-nested-panel">
                            <div class="panel__header"><h4 class="panel-title">Ranking de usuarios</h4></div>
                            <div class="panel__body">
                                <div class="indicadores-table-wrap">
                                <table class="supply-table indicadores-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Usuario</th>
                                            <th>Score</th>
                                            <th>En rojo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($dashboard['zone_ranking'] as $index => $row)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $row['user']->name }}</td>
                                                <td>{{ number_format($row['score'], 2) }}%</td>
                                                <td>{{ $row['red_count'] }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4">No hay usuarios de captura registrados.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                                </div>
                            </div>
                        </div>

                        <div class="panel indicadores-nested-panel">
                            <div class="panel__header"><h4 class="panel-title">Indicadores criticos</h4></div>
                            <div class="panel__body">
                                <div class="indicadores-table-wrap">
                                <table class="supply-table indicadores-table">
                                    <thead>
                                        <tr>
                                            <th>Usuario</th>
                                            <th>Indicador</th>
                                            <th>Valor critico</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($dashboard['critical_indicators'] as $row)
                                            <tr>
                                                <td>{{ $row['user']->name }}</td>
                                                <td>{{ $row['indicator']->code }} — {{ $row['indicator']->name }}</td>
                                                <td>{{ number_format((float) $row['critical_value'], 2) }}%</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3">No hay indicadores en estado critico para este periodo.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    @can('operations.manage')
                        <div class="panel indicadores-nested-panel" style="margin-top:1.5rem;">
                            <div class="panel__header"><h4 class="panel-title">Resumen ejecutivo</h4></div>
                            <div class="panel__body">
                                <form method="POST" action="{{ route('indicadores.admin.dashboard.summary') }}" class="indicadores-form-compact">
                                    @csrf
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <input type="hidden" name="month" value="{{ $month }}">
                                    <textarea name="summary_text" class="supply-textarea" rows="4" placeholder="Resumen del periodo...">{{ old('summary_text', $summary?->summary_text) }}</textarea>
                                    <button type="submit" class="btn btn--primary btn--sm">Guardar resumen</button>
                                </form>
                            </div>
                        </div>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
