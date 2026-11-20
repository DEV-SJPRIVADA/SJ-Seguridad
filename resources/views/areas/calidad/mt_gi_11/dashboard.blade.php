{{--
    Dashboard MT-GI-11: filtros compactos, KPIs por estado y dos gráficos.

    Las métricas respetan el mismo alcance del listado (propias o todas).
--}}
@php
    $hasActiveFilters = collect($filters)->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty();
@endphp

<x-app-layout>
    <x-slot name="header">
        @include('areas.calidad.mt_gi_11.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section mt-gi-11-page mt-gi-11-dashboard-page cursos-dashboard-page gh-area-dashboard-page">
        <div class="app-container section-stack">
            <div class="panel cursos-dashboard-page__filters-panel">
                <div class="panel__body panel__body--compact">
                    <form method="GET" action="{{ route('calidad.mt-gi-11.dashboard') }}" class="cursos-dashboard-page__filters">
                        <div class="form-field">
                            <label class="form-label" for="dash_fecha_desde">Fecha solicitud desde</label>
                            <input id="dash_fecha_desde" name="fecha_desde" type="date" class="form-input" value="{{ $filters['fecha_desde'] }}">
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="dash_fecha_hasta">Fecha solicitud hasta</label>
                            <input id="dash_fecha_hasta" name="fecha_hasta" type="date" class="form-input" value="{{ $filters['fecha_hasta'] }}">
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="dash_proceso_id">Proceso</label>
                            <x-searchable-select id="dash_proceso_id" name="proceso_id" :options="$options['procesos']" :value="$filters['proceso_id']" placeholder="Todos" search-placeholder="Buscar proceso…" :allow-clear="true" />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="dash_tipo_documento_id">Tipo de documento</label>
                            <x-searchable-select id="dash_tipo_documento_id" name="tipo_documento_id" :options="$options['tipos_documento']" :value="$filters['tipo_documento_id']" placeholder="Todos" :allow-clear="true" />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="dash_estado_id">Estado</label>
                            <x-searchable-select id="dash_estado_id" name="estado_id" :options="$options['estados']" :value="$filters['estado_id']" placeholder="Todos" :allow-clear="true" />
                        </div>
                        <div class="form-field cursos-dashboard-page__filter-actions">
                            <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Filtrar" aria-label="Filtrar">
                                <x-lucide-search width="18" height="18" aria-hidden="true" />
                            </button>
                            <a href="{{ route('calidad.mt-gi-11.dashboard') }}" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost" title="Limpiar filtros" aria-label="Limpiar filtros">
                                <x-lucide-x width="18" height="18" aria-hidden="true" />
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- KPIs: total + un card por estado; cada card enlaza al listado filtrado. --}}
            <div class="cursos-dashboard-page__kpi-grid gh-area-dashboard-page__kpi-grid mt-gi-11-dashboard-page__kpi-grid">
                <a href="{{ $kpiLinks['total'] }}" class="card cursos-dashboard-page__kpi" style="border-left-color:#0f172a;" title="Ver todas las solicitudes en la matriz">
                    <p class="cursos-dashboard-page__kpi-label">Total</p>
                    <p class="cursos-dashboard-page__kpi-value">{{ $metrics['kpis']['total'] }}</p>
                    <p class="formacion-dashboard-page__kpi-hint">Ver listado</p>
                </a>

                @foreach ($metrics['estados'] as $estado)
                    @php
                        $borderColor = match ($estado['color']) {
                            'success' => '#15803d',
                            'warning' => '#ca8a04',
                            'danger' => '#be123c',
                            'info' => '#0369a1',
                            default => '#64748b',
                        };
                    @endphp
                    <a href="{{ $kpiLinks['estados'][$estado['id']] ?? $kpiLinks['total'] }}" class="card cursos-dashboard-page__kpi" style="border-left-color:{{ $borderColor }};" title="Ver solicitudes en estado {{ $estado['label'] }}">
                        <p class="cursos-dashboard-page__kpi-label">{{ $estado['label'] }}</p>
                        <p class="cursos-dashboard-page__kpi-value">{{ $estado['total'] }}</p>
                        <p class="formacion-dashboard-page__kpi-hint">Ver listado</p>
                    </a>
                @endforeach
            </div>

            <div class="gh-area-dashboard-page__charts">
                <div class="gh-area-dashboard-page__charts-row mt-gi-11-dashboard-page__charts-row--two">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Solicitudes por proceso</h3>
                        </div>
                        <div class="panel__body">
                            <div id="mt-gi-11-chart-proceso" class="cursos-dashboard-page__chart"></div>
                        </div>
                    </div>
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Solicitudes por tipo de documento</h3>
                        </div>
                        <div class="panel__body">
                            <div id="mt-gi-11-chart-tipo-documento" class="cursos-dashboard-page__chart"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="application/json" id="mt-gi-11-dashboard-data">@json($metrics)</script>
    @vite(['resources/js/mt-gi-11-dashboard-charts.js'])
</x-app-layout>
