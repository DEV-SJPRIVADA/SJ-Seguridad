<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.cursos.partials.subnav', ['subTabs' => $subTabs])
        <div class="app-container">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">Dashboard</h2>
                <p class="panel-text">Gestion humana — indicadores de cursos</p>
            </div>
        </div>
    </x-slot>

    <div
        class="page-section cursos-dashboard-page"
        x-data="cursosDashboardPage(@js([
            'metricsUrl' => $metricsUrl,
            'initial' => $initialPayload,
            'filters' => $filters,
        ]))"
        x-init="init()"
    >
        <div class="app-container section-stack">
            <div class="panel cursos-dashboard-page__filters-panel">
                <div class="panel__body panel__body--compact">
                    <div class="cursos-dashboard-page__filters">
                        <div class="form-field">
                            <label class="form-label" for="dash_fecha_desde">Fecha expedición desde</label>
                            <input
                                id="dash_fecha_desde"
                                type="date"
                                class="form-input"
                                x-model="filters.fecha_desde"
                                @change="scheduleRefresh()"
                            >
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="dash_fecha_hasta">Fecha expedición hasta</label>
                            <input
                                id="dash_fecha_hasta"
                                type="date"
                                class="form-input"
                                x-model="filters.fecha_hasta"
                                @change="scheduleRefresh()"
                            >
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="dash_curso_tipo_id">Tipo curso</label>
                            <x-searchable-select
                                id="dash_curso_tipo_id"
                                name="curso_tipo_id"
                                :options="$tipoOptions"
                                :value="$filters['curso_tipo_id']"
                                placeholder="Todos"
                                :allow-clear="true"
                                x-on:change="onSelectChange('curso_tipo_id', $event)"
                            />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="dash_vigencia">Vigencia</label>
                            <x-searchable-select
                                id="dash_vigencia"
                                name="vigencia"
                                :options="$filterVigenciaOptions"
                                :value="$filters['vigencia']"
                                placeholder="Todas"
                                :allow-clear="true"
                                x-on:change="onSelectChange('vigencia', $event)"
                            />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="dash_estado">Estado</label>
                            <x-searchable-select
                                id="dash_estado"
                                name="estado"
                                :options="$filterEstadoOptions"
                                :value="$filters['estado']"
                                placeholder="Todos"
                                :allow-clear="false"
                                x-on:change="onSelectChange('estado', $event)"
                            />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="dash_anio">Año tendencia</label>
                            <select
                                id="dash_anio"
                                class="form-input"
                                x-model.number="filters.anio"
                                @change="scheduleRefresh()"
                            >
                                @foreach ($yearOptions as $year)
                                    <option value="{{ $year }}" @selected((int) $filters['anio'] === (int) $year)>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-field cursos-dashboard-page__filter-meta">
                            <span class="panel-text" x-show="loading" x-cloak>Actualizando…</span>
                            <span class="panel-text" x-show="!loading && error" x-text="error" x-cloak></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="cursos-dashboard-page__kpi-grid">
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#0369a1;">
                    <p class="cursos-dashboard-page__kpi-label">Total cursos</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="kpis.total"></p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#15803d;">
                    <p class="cursos-dashboard-page__kpi-label">Vigentes</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="kpis.vigentes"></p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#ca8a04;">
                    <p class="cursos-dashboard-page__kpi-label">Por actualizar</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="kpis.actualizar"></p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#be123c;">
                    <p class="cursos-dashboard-page__kpi-label">Vencidos</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="kpis.vencidos"></p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#92400e;">
                    <p class="cursos-dashboard-page__kpi-label">Sin documento</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="kpis.sin_documento"></p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#ea580c;">
                    <p class="cursos-dashboard-page__kpi-label">Solicitados</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="kpis.solicitados"></p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#ca8a04;">
                    <p class="cursos-dashboard-page__kpi-label">Pendientes</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="kpis.pendientes"></p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#64748b;">
                    <p class="cursos-dashboard-page__kpi-label">Actualizados</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="kpis.actualizados"></p>
                </div>
            </div>

            <div class="cursos-dashboard-page__charts">
                <div class="panel">
                    <div class="panel__header">
                        <h3 class="panel-title">Cursos por tipo</h3>
                    </div>
                    <div class="panel__body">
                        <div id="cursos-chart-tipo" class="cursos-dashboard-page__chart"></div>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel__header">
                        <h3 class="panel-title">Vigencia</h3>
                    </div>
                    <div class="panel__body">
                        <div id="cursos-chart-vigencia" class="cursos-dashboard-page__chart"></div>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel__header">
                        <h3 class="panel-title">Por estado</h3>
                    </div>
                    <div class="panel__body">
                        <div id="cursos-chart-estado" class="cursos-dashboard-page__chart"></div>
                    </div>
                </div>
                <div class="panel cursos-dashboard-page__chart-wide">
                    <div class="panel__header">
                        <h3 class="panel-title">Tendencia <span x-text="filters.anio"></span> (nuevos vs actualizaciones)</h3>
                    </div>
                    <div class="panel__body">
                        <div id="cursos-chart-trend" class="cursos-dashboard-page__chart cursos-dashboard-page__chart--tall"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @vite(['resources/js/cursos-dashboard-charts.js'])

    @push('scripts')
        <script>
            function cursosDashboardPage(config) {
                return {
                    metricsUrl: config.metricsUrl,
                    filters: { ...config.filters },
                    kpis: { ...(config.initial?.kpis || {}) },
                    loading: false,
                    error: '',
                    refreshTimer: null,
                    init() {
                        if (typeof window.renderCursosDashboardCharts === 'function') {
                            window.renderCursosDashboardCharts(config.initial?.charts);
                        } else {
                            window.addEventListener('load', () => {
                                window.renderCursosDashboardCharts?.(config.initial?.charts);
                            }, { once: true });
                        }
                    },
                    onSelectChange(key, event) {
                        let value = event?.detail?.value;
                        if (value === undefined || value === null) {
                            value = event?.target?.value ?? '';
                        }
                        if (key === 'estado' && (value === '' || value === null)) {
                            value = 'todos';
                        }
                        this.filters[key] = value;
                        this.scheduleRefresh();
                    },
                    scheduleRefresh() {
                        clearTimeout(this.refreshTimer);
                        this.refreshTimer = setTimeout(() => this.refresh(), 300);
                    },
                    async refresh() {
                        this.loading = true;
                        this.error = '';
                        try {
                            const params = new URLSearchParams();
                            Object.entries(this.filters).forEach(([key, value]) => {
                                if (value === null || value === undefined || value === '') {
                                    return;
                                }
                                params.set(key, String(value));
                            });

                            const res = await fetch(`${this.metricsUrl}?${params.toString()}`, {
                                headers: { Accept: 'application/json' },
                                credentials: 'same-origin',
                            });
                            if (!res.ok) {
                                throw new Error('No se pudieron actualizar los indicadores.');
                            }
                            const payload = await res.json();
                            this.kpis = payload.kpis || this.kpis;
                            if (payload.filters) {
                                this.filters.anio = payload.filters.anio ?? this.filters.anio;
                            }
                            window.renderCursosDashboardCharts?.(payload.charts);
                        } catch (e) {
                            this.error = e?.message || 'Error al actualizar.';
                        } finally {
                            this.loading = false;
                        }
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
