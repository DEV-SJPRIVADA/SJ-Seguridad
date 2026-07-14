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
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;">
                        <div>
                            <h3 class="panel-title">Dashboard General de Operaciones</h3>
                            <p class="panel-text">Consolidado mensual de indicadores FT-OP por usuario.</p>
                        </div>
                        @can('operations.export')
                            <a href="{{ route('indicadores.export.dashboard.pdf', ['year' => $year, 'month' => $month]) }}" class="btn btn--secondary btn--sm">
                                Exportar PDF
                            </a>
                        @endcan
                    </div>
                </div>

                <div class="panel__body">
                    <form method="GET" class="dashboard-filters form-stack" style="margin-bottom:1.5rem;">
                        <div class="filter-grid" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:0.75rem; align-items:end;">
                            <div>
                                <label class="form-label">Ano</label>
                                <select name="year" class="supply-input supply-select">
                                    @foreach ($years as $yearOption)
                                        <option value="{{ $yearOption }}" @selected($year === (int) $yearOption)>{{ $yearOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Mes</label>
                                <select name="month" class="supply-input supply-select">
                                    @foreach ($months as $monthNumber => $monthName)
                                        <option value="{{ $monthNumber }}" @selected($month === (int) $monthNumber)>{{ $monthName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
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

                    <h4 class="panel-title" style="margin-bottom:1rem;">KPIs del mes</h4>
                    <table class="supply-table indicadores-kpi-table">
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Indicador</th>
                                <th>Resultado</th>
                                <th>Meta</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dashboard['kpis'] as $kpi)
                                <tr>
                                    <td>
                                        <a href="{{ $kpi['mother_url'] }}" class="btn btn--secondary btn--sm">{{ $kpi['indicator']->code }}</a>
                                    </td>
                                    <td style="text-align:left;">{{ $kpi['indicator']->name }}</td>
                                    <td>{{ $kpi['result'] !== null ? number_format((float) $kpi['result'], 2).'%' : '-' }}</td>
                                    <td>{{ $kpi['meta'] }}</td>
                                    <td>
                                        <span class="status-pill {{ $semaforoClass($kpi['semaforo']) }}">{{ $kpi['semaforo'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:1.5rem; margin-top:2rem;">
                        <div class="panel" style="margin:0;">
                            <div class="panel__header"><h4 class="panel-title">Ranking de usuarios</h4></div>
                            <div class="panel__body">
                                <table class="supply-table">
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

                        <div class="panel" style="margin:0;">
                            <div class="panel__header"><h4 class="panel-title">Indicadores criticos</h4></div>
                            <div class="panel__body">
                                <table class="supply-table">
                                    <thead>
                                        <tr>
                                            <th>Indicador</th>
                                            <th>Resultado</th>
                                            <th>Usuarios rojo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach (array_slice($dashboard['critical_ranking'], 0, 8) as $row)
                                            <tr>
                                                <td>{{ $row['indicator']->code }}</td>
                                                <td>{{ $row['result'] !== null ? number_format((float) $row['result'], 2).'%' : '-' }}</td>
                                                <td>{{ $row['zones_red'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    @can('operations.manage')
                        <div class="panel" style="margin-top:2rem;">
                            <div class="panel__header"><h4 class="panel-title">Resumen ejecutivo</h4></div>
                            <div class="panel__body">
                                <form method="POST" action="{{ route('indicadores.admin.dashboard.summary') }}" class="form-stack">
                                    @csrf
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <input type="hidden" name="month" value="{{ $month }}">
                                    <textarea name="summary_text" class="supply-textarea" rows="5" placeholder="Resumen del periodo...">{{ old('summary_text', $summary?->summary_text) }}</textarea>
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
