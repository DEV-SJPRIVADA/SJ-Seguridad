<x-app-layout>
    <x-slot name="header">
        @include('areas.calidad.servicio_cliente.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Dashboard FO-GI-22: panel de filtros y KPIs al estilo del informe de Gestión Humana. --}}
    <div class="page-section cursos-dashboard-page gh-area-dashboard-page servicio-cliente-dashboard-page">
        <div class="app-container section-stack">
            <div class="panel cursos-dashboard-page__filters-panel">
                <div class="panel__body panel__body--compact">
                    <form method="GET" action="{{ route('calidad.servicio-cliente.dashboard') }}" class="cursos-dashboard-page__filters">
                        <div class="form-field">
                            <label class="form-label" for="dash_tipo_cliente">Tipo de cliente</label>
                            <x-searchable-select id="dash_tipo_cliente" name="tipo_cliente" :options="$options['tipos']" :value="$filters['tipo_cliente']" placeholder="Todos" :allow-clear="true" />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="dash_tipologia_id">Tipología</label>
                            <x-searchable-select id="dash_tipologia_id" name="tipologia_id" :options="$options['tipologias']" :value="$filters['tipologia_id']" placeholder="Todas" :allow-clear="true" />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="dash_fuente_id">Fuente</label>
                            <x-searchable-select id="dash_fuente_id" name="fuente_id" :options="$options['fuentes']" :value="$filters['fuente_id']" placeholder="Todas" :allow-clear="true" />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="dash_responsable_id">Responsable</label>
                            <x-searchable-select id="dash_responsable_id" name="responsable_id" :options="$options['responsables']" :value="$filters['responsable_id']" placeholder="Todos" :allow-clear="true" />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="dash_eficacia">Eficacia</label>
                            <x-searchable-select id="dash_eficacia" name="eficacia" :options="$options['eficacia']" :value="$filters['eficacia']" placeholder="Todas" :allow-clear="true" />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="dash_recurrencia">Recurrencia</label>
                            <x-searchable-select id="dash_recurrencia" name="recurrencia" :options="$options['recurrencia']" :value="$filters['recurrencia']" :allow-clear="false" />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="dash_recepcion_desde">Recepción desde</label>
                            <input id="dash_recepcion_desde" name="recepcion_desde" type="date" class="form-input" value="{{ $filters['recepcion_desde'] }}">
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="dash_recepcion_hasta">Recepción hasta</label>
                            <input id="dash_recepcion_hasta" name="recepcion_hasta" type="date" class="form-input" value="{{ $filters['recepcion_hasta'] }}">
                        </div>
                        <div class="form-field cursos-dashboard-page__filter-actions">
                            <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Filtrar" aria-label="Filtrar">
                                <x-lucide-search width="18" height="18" aria-hidden="true" />
                            </button>
                            <a href="{{ route('calidad.servicio-cliente.dashboard') }}" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost" title="Limpiar filtros" aria-label="Limpiar filtros">
                                <x-lucide-x width="18" height="18" aria-hidden="true" />
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="cursos-dashboard-page__kpi-grid gh-area-dashboard-page__kpi-grid servicio-cliente-dashboard-page__kpi-grid">
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#0f172a;">
                    <p class="cursos-dashboard-page__kpi-label">Total</p>
                    <p class="cursos-dashboard-page__kpi-value">{{ $metrics['kpis']['total'] }}</p>
                </div>
                <a href="{{ $kpiLinks['abiertos'] }}" class="card cursos-dashboard-page__kpi" style="border-left-color:#0369a1;" title="Ver casos abiertos en FO-GI-22">
                    <p class="cursos-dashboard-page__kpi-label">Abiertos</p>
                    <p class="cursos-dashboard-page__kpi-value">{{ $metrics['kpis']['abiertos'] }}</p>
                    <p class="formacion-dashboard-page__kpi-hint">Ver listado</p>
                </a>
                <a href="{{ $kpiLinks['en_proceso'] }}" class="card cursos-dashboard-page__kpi" style="border-left-color:#ca8a04;" title="Ver casos en proceso en FO-GI-22">
                    <p class="cursos-dashboard-page__kpi-label">En proceso</p>
                    <p class="cursos-dashboard-page__kpi-value">{{ $metrics['kpis']['en_proceso'] }}</p>
                    <p class="formacion-dashboard-page__kpi-hint">Ver listado</p>
                </a>
                <a href="{{ $kpiLinks['cerrados'] }}" class="card cursos-dashboard-page__kpi" style="border-left-color:#15803d;" title="Ver casos cerrados en FO-GI-22">
                    <p class="cursos-dashboard-page__kpi-label">Cerrados</p>
                    <p class="cursos-dashboard-page__kpi-value">{{ $metrics['kpis']['cerrados'] }}</p>
                    <p class="formacion-dashboard-page__kpi-hint">Ver listado</p>
                </a>
                <a href="{{ $kpiLinks['vencidos'] }}" class="card cursos-dashboard-page__kpi" style="border-left-color:#be123c;" title="Ver casos vencidos en FO-GI-22">
                    <p class="cursos-dashboard-page__kpi-label">Vencidos</p>
                    <p class="cursos-dashboard-page__kpi-value">{{ $metrics['kpis']['vencidos'] }}</p>
                    <p class="formacion-dashboard-page__kpi-hint">Ver listado</p>
                </a>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#0d9488;">
                    <p class="cursos-dashboard-page__kpi-label">Promedio días</p>
                    <p class="cursos-dashboard-page__kpi-value">{{ $metrics['kpis']['promedio_dias'] === null ? '—' : $metrics['kpis']['promedio_dias'] }}</p>
                </div>
            </div>

            <div class="gh-area-dashboard-page__charts">
                <div class="gh-area-dashboard-page__charts-row gh-area-dashboard-page__charts-row--top">
                    @foreach ([
                        'tipologia' => 'Cantidad por tipo de solicitud',
                        'categoria' => 'Cantidad por categoría',
                        'eficacia' => 'Cantidad por estado de proceso',
                    ] as $key => $title)
                        <div class="panel">
                            <div class="panel__header">
                                <h3 class="panel-title">{{ $title }}</h3>
                            </div>
                            <div class="panel__body">
                                <div id="servicio-cliente-chart-{{ $key }}" class="cursos-dashboard-page__chart"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="gh-area-dashboard-page__charts-row servicio-cliente-dashboard-page__charts-row--two">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Cantidad por fuente</h3>
                        </div>
                        <div class="panel__body">
                            <div id="servicio-cliente-chart-fuente" class="cursos-dashboard-page__chart"></div>
                        </div>
                    </div>
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Recurrencia</h3>
                        </div>
                        <div class="panel__body">
                            <div id="servicio-cliente-chart-recurrencia" class="cursos-dashboard-page__chart"></div>
                            @if ($metrics['recurrencia']['clientes'] !== [])
                                <table class="servicio-cliente-dashboard-page__recurrencia">
                                    <thead>
                                        <tr>
                                            <th>Cliente</th>
                                            <th>Casos</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($metrics['recurrencia']['clientes'] as $cliente)
                                            <tr>
                                                <td>{{ $cliente['nombre'] }}</td>
                                                <td>{{ $cliente['total'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="application/json" id="servicio-cliente-dashboard-data">@json($metrics)</script>
    @vite(['resources/js/servicio-cliente-dashboard-charts.js'])
</x-app-layout>
