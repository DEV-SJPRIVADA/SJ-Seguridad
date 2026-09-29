<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.acreditaciones.partials.subnav', ['subTabs' => $subTabs])
        <div class="app-container">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">Dashboard</h2>
                <p class="panel-text">Gestion humana — indicadores de acreditaciones</p>
            </div>
        </div>
    </x-slot>

    <div
        class="page-section cursos-dashboard-page"
        x-data="acreditacionesDashboardPage(@js([
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
                            <label class="form-label" for="dash_fecha_desde">Fecha solicitud desde</label>
                            <input
                                id="dash_fecha_desde"
                                type="date"
                                class="form-input"
                                x-model="filters.fecha_desde"
                                @change="scheduleRefresh()"
                            >
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="dash_fecha_hasta">Fecha solicitud hasta</label>
                            <input
                                id="dash_fecha_hasta"
                                type="date"
                                class="form-input"
                                x-model="filters.fecha_hasta"
                                @change="scheduleRefresh()"
                            >
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="dash_cargo_apo">Cargo APO</label>
                            <x-searchable-select
                                id="dash_cargo_apo"
                                name="cargo_apo"
                                :options="$filterCargoApoOptions"
                                :value="$filters['cargo_apo']"
                                placeholder="Todos"
                                :allow-clear="true"
                                x-on:change="onSelectChange('cargo_apo', $event)"
                            />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="dash_ficha_estado">Estado ficha</label>
                            <x-searchable-select
                                id="dash_ficha_estado"
                                name="ficha_estado"
                                :options="$filterFichaEstadoOptions"
                                :value="$filters['ficha_estado']"
                                placeholder="Activos en ficha"
                                :allow-clear="false"
                                x-on:change="onSelectChange('ficha_estado', $event)"
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
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#0f172a;">
                    <p class="cursos-dashboard-page__kpi-label" x-text="labels.total">Total</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="kpis.total">0</p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#0369a1;">
                    <p class="cursos-dashboard-page__kpi-label" x-text="labels.en_proceso">EN PROCESO</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="kpis.en_proceso">0</p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#15803d;">
                    <p class="cursos-dashboard-page__kpi-label" x-text="labels.acreditado">ACREDITADO</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="kpis.acreditado">0</p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#ca8a04;">
                    <p class="cursos-dashboard-page__kpi-label" x-text="labels.por_vencer">POR VENCER</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="kpis.por_vencer">0</p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#be123c;">
                    <p class="cursos-dashboard-page__kpi-label" x-text="labels.desacreditado">DESACREDITADO</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="kpis.desacreditado">0</p>
                </div>
            </div>

            <div class="cursos-dashboard-page__charts">
                <div class="panel">
                    <div class="panel__header">
                        <h3 class="panel-title">Por estado</h3>
                    </div>
                    <div class="panel__body">
                        <div id="acreditaciones-chart-estado" class="cursos-dashboard-page__chart"></div>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel__header">
                        <h3 class="panel-title">Por cargo APO</h3>
                    </div>
                    <div class="panel__body">
                        <div id="acreditaciones-chart-cargo" class="cursos-dashboard-page__chart"></div>
                    </div>
                </div>
                <div class="panel cursos-dashboard-page__chart-wide">
                    <div class="panel__header">
                        <h3 class="panel-title">Tendencia solicitudes <span x-text="filters.anio"></span></h3>
                    </div>
                    <div class="panel__body">
                        <div id="acreditaciones-chart-trend" class="cursos-dashboard-page__chart cursos-dashboard-page__chart--tall"></div>
                    </div>
                </div>
                <div class="panel cursos-dashboard-page__chart-wide">
                    <div class="panel__header">
                        <h3 class="panel-title">Tendencia vencimientos <span x-text="filters.anio"></span></h3>
                        <p class="panel-text">Acreditaciones con VIGEN.ACR en cada mes</p>
                    </div>
                    <div class="panel__body">
                        <div id="acreditaciones-chart-trend-vencimientos" class="cursos-dashboard-page__chart cursos-dashboard-page__chart--tall"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @vite(['resources/js/acreditaciones-dashboard-charts.js'])

    @push('scripts')
        <script>
            function acreditacionesDashboardPage(config) {
                return {
                    metricsUrl: config.metricsUrl,
                    filters: { ...config.filters },
                    kpis: { ...(config.initial?.kpis || {}) },
                    labels: { ...(config.initial?.labels || {}) },
                    loading: false,
                    error: '',
                    refreshTimer: null,
                    init() {
                        if (typeof window.renderAcreditacionesDashboardCharts === 'function') {
                            window.renderAcreditacionesDashboardCharts(config.initial?.charts);
                        } else {
                            window.addEventListener('load', () => {
                                window.renderAcreditacionesDashboardCharts?.(config.initial?.charts);
                            }, { once: true });
                        }
                    },
                    onSelectChange(key, event) {
                        let value = event?.detail?.value;
                        if (value === undefined || value === null) {
                            value = event?.target?.value ?? '';
                        }
                        this.filters[key] = value;
                        this.scheduleRefresh();
                    },
                    scheduleRefresh() {
                        clearTimeout(this.refreshTimer);
                        this.refreshTimer = setTimeout(() => this.refresh(), 300);
                    },
                    async refresh() {
                        if (!this.metricsUrl) {
                            return;
                        }

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

                            const response = await fetch(`${this.metricsUrl}?${params.toString()}`, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                            });

                            if (!response.ok) {
                                throw new Error('No se pudieron actualizar los indicadores.');
                            }

                            const payload = await response.json();
                            this.kpis = { ...(payload.kpis || {}) };
                            this.labels = { ...(payload.labels || this.labels) };
                            if (payload.filters) {
                                this.filters = { ...this.filters, ...payload.filters };
                            }
                            window.renderAcreditacionesDashboardCharts?.(payload.charts);
                        } catch (e) {
                            this.error = e?.message || 'Error al actualizar el dashboard.';
                        } finally {
                            this.loading = false;
                        }
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
