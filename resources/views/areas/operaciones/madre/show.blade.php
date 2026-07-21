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
                            <h3 class="panel-title">Consolidado — {{ $indicator->code }}</h3>
                            <p class="panel-text">{{ $indicator->name }}</p>
                        </div>
                        <div class="indicadores-filter-bar">
                            @can('operations.export')
                                <a href="{{ route('indicadores.export.mother.excel', ['indicator' => $indicator->code, 'year' => $year, 'month' => $month]) }}" class="btn btn--secondary btn--sm">Excel</a>
                                <a href="{{ route('indicadores.export.mother.pdf', ['indicator' => $indicator->code, 'year' => $year, 'month' => $month]) }}" class="btn btn--secondary btn--sm">PDF</a>
                            @endcan
                            <form method="GET" class="indicadores-inline-form" style="margin:0;">
                                <div class="indicadores-field indicadores-field--xs">
                                    <label class="form-label">Ano</label>
                                    <select name="year" onchange="this.form.submit()" class="supply-input supply-select">
                                        @foreach ($years as $yearOption)
                                            <option value="{{ $yearOption }}" @selected($year === (int) $yearOption)>{{ $yearOption }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="indicadores-field indicadores-field--sm">
                                    <label class="form-label">Mes</label>
                                    <select name="month" onchange="this.form.submit()" class="supply-input supply-select">
                                        @foreach ($months as $monthNumber => $monthName)
                                            <option value="{{ $monthNumber }}" @selected($month === (int) $monthNumber)>{{ $monthName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="panel__body">
                    <div class="dashboard-stat-grid" style="margin-bottom:1rem;">
                        <div class="card kpi-card">
                            <p class="text-caption">Consolidado</p>
                            <p class="kpi-value">
                                {{ ($monthly['consolidated']['result_percentage'] ?? null) !== null ? number_format((float) $monthly['consolidated']['result_percentage'], 2).'%' : '-' }}
                            </p>
                        </div>
                        <div class="card kpi-card">
                            <p class="text-caption">Meta</p>
                            <p class="kpi-value">{{ $indicator->target_operator }} {{ number_format((float) $indicator->target_value, 2) }}%</p>
                        </div>
                        <div class="card kpi-card">
                            <p class="text-caption">Estado</p>
                            <p class="kpi-value">{{ $monthly['consolidated']['semaforo'] ?? 'ROJO' }}</p>
                        </div>
                    </div>

                    <div class="indicadores-table-wrap">
                    <table class="supply-table indicadores-table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Resultado</th>
                                <th>Cumple</th>
                                <th>Mejora</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($monthly['rows'] as $row)
                                <tr>
                                    <td>{{ $row['user']->name }}</td>
                                    <td>{{ $row['result_percentage'] !== null ? number_format((float) $row['result_percentage'], 2).'%' : 'Sin registro' }}</td>
                                    <td>{{ $row['semaforo'] ?? '-' }}</td>
                                    <td>{{ ($row['has_improvement'] ?? false) ? 'Si' : 'No' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
