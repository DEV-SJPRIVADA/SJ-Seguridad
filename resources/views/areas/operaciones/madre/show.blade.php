<x-app-layout>
    <x-slot name="header">
        @include('areas.operaciones.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:1rem; flex-wrap:wrap;">
                        <div>
                            <h3 class="panel-title">Consolidado — {{ $indicator->code }}</h3>
                            <p class="panel-text">{{ $indicator->name }}</p>
                        </div>
                        @can('operations.export')
                            <a href="{{ route('indicadores.export.mother.excel', ['indicator' => $indicator->code, 'year' => $year, 'month' => $month]) }}" class="btn btn--secondary btn--sm">Excel MADRE</a>
                            <a href="{{ route('indicadores.export.mother.pdf', ['indicator' => $indicator->code, 'year' => $year, 'month' => $month]) }}" class="btn btn--secondary btn--sm">PDF MADRE</a>
                        @endcan
                        <form method="GET" style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:flex-end;">
                            <div>
                                <label class="form-label">Año</label>
                                <select name="year" onchange="this.form.submit()" class="supply-input supply-select">
                                    @foreach ($years as $yearOption)
                                        <option value="{{ $yearOption }}" @selected($year === (int) $yearOption)>{{ $yearOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
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
                <div class="panel__body">
                    <div class="dashboard-stat-grid" style="margin-bottom:1.5rem;">
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

                    <table class="supply-table">
                        <thead>
                            <tr>
                                <th>Jefe</th>
                                <th>Resultado</th>
                                <th>Cumple</th>
                                <th>Mejora</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($monthly['rows'] as $row)
                                <tr>
                                    <td>{{ $row['operations_leader']->code }} — {{ $row['operations_leader']->name }}</td>
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
</x-app-layout>
