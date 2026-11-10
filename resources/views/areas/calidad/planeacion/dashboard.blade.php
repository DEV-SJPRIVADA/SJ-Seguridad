<x-app-layout>
    <x-slot name="header">
        @include('areas.calidad.planeacion.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Dashboard del mes consolidado: filtros, KPI y gráficos Apex al chrome de GH. --}}
    <div class="page-section cursos-dashboard-page gh-area-dashboard-page planeacion-dashboard-page">
        <div class="app-container section-stack">
            <div class="panel cursos-dashboard-page__filters-panel">
                <div class="panel__body panel__body--compact">
                    <form method="GET" action="{{ route('calidad.planeacion.dashboard') }}" class="cursos-dashboard-page__filters">
                        <div class="form-field">
                            <label class="form-label" for="dash_year">Año</label>
                            <x-searchable-select
                                id="dash_year"
                                name="year"
                                :options="$yearOptions"
                                :value="(string) $year"
                                placeholder="Seleccionar año"
                                :allow-clear="false"
                            />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="dash_month">Mes</label>
                            <x-searchable-select
                                id="dash_month"
                                name="month"
                                :options="$monthOptions"
                                :value="(string) $month"
                                placeholder="Seleccionar mes"
                                :allow-clear="false"
                            />
                        </div>
                        <div class="form-field cursos-dashboard-page__filter-actions">
                            <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Filtrar" aria-label="Filtrar">
                                <x-lucide-search width="18" height="18" aria-hidden="true" />
                            </button>
                            <a href="{{ route('calidad.planeacion.dashboard') }}" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost" title="Limpiar filtros" aria-label="Limpiar filtros">
                                <x-lucide-x width="18" height="18" aria-hidden="true" />
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="cursos-dashboard-page__kpi-grid gh-area-dashboard-page__kpi-grid planeacion-dashboard-page__kpi-grid">
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#0f172a;">
                    <p class="cursos-dashboard-page__kpi-label">Indicadores</p>
                    <p class="cursos-dashboard-page__kpi-value">{{ $metrics['kpis']['indicadores'] }}</p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#0369a1;">
                    <p class="cursos-dashboard-page__kpi-label">% Capturados</p>
                    <p class="cursos-dashboard-page__kpi-value">{{ number_format((float) $metrics['kpis']['pct_capturados'], 1, ',', '.') }}%</p>
                    <p class="formacion-dashboard-page__kpi-hint">{{ $metrics['kpis']['capturados'] }} / {{ $metrics['kpis']['asignados'] }} asignados</p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#15803d;">
                    <p class="cursos-dashboard-page__kpi-label">En verde</p>
                    <p class="cursos-dashboard-page__kpi-value">{{ $metrics['kpis']['verde'] }}</p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#be123c;">
                    <p class="cursos-dashboard-page__kpi-label">En rojo / crítico</p>
                    <p class="cursos-dashboard-page__kpi-value">{{ $metrics['kpis']['rojo'] }}</p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#ca8a04;">
                    <p class="cursos-dashboard-page__kpi-label">Pendientes</p>
                    <p class="cursos-dashboard-page__kpi-value">{{ $metrics['kpis']['pendientes'] }}</p>
                </div>
            </div>

            <div class="gh-area-dashboard-page__charts">
                <div class="gh-area-dashboard-page__charts-row planeacion-dashboard-page__charts-row--two">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Cumplimiento por indicador</h3>
                        </div>
                        <div class="panel__body">
                            <div id="planeacion-dash-cumplimiento" class="cursos-dashboard-page__chart"></div>
                        </div>
                    </div>
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Pendientes del mes</h3>
                        </div>
                        <div class="panel__body">
                            <div id="planeacion-dash-pendientes" class="cursos-dashboard-page__chart"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Indicadores · {{ $metrics['month_label'] }} {{ $metrics['year'] }}</h3>
                </div>
                <div class="panel__body panel__body--compact">
                    <div class="data-table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Resultado</th>
                                    <th>Meta</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($metrics['rows'] as $row)
                                    <tr>
                                        <td>
                                            <a href="{{ $row['url'] }}">{{ $row['code'] }}</a>
                                        </td>
                                        <td>{{ $row['name'] }}</td>
                                        <td>{{ $row['result_display'] }}</td>
                                        <td>{{ $row['target_display'] }}</td>
                                        <td>
                                            <span class="status-pill {{ $row['status'] === 'verde' ? 'status-pill--req-contratado' : ($row['status'] === 'rojo' ? 'status-pill--req-cancelada' : '') }}">
                                                {{ $row['status_label'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">No hay indicadores publicados en su alcance para este mes.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="application/json" id="planeacion-dashboard-data">@json($metrics['charts'])</script>
    @vite(['resources/js/planeacion-dashboard-charts.js'])
</x-app-layout>
