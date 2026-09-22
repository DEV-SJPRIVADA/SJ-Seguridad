<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.seleccion.partials.subnav', ['subTabs' => $subTabs])
        <div class="app-container">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">Dashboard</h2>
                <p class="panel-text">Gestion humana — indicadores de selección</p>
            </div>
        </div>
    </x-slot>

    <div
        class="page-section seleccion-dashboard-page"
        x-data="seleccionDashboardPage(@js([
            'metricsUrl' => $metricsUrl,
            'initial' => $initialPayload,
            'filters' => $filters,
        ]))"
        x-init="init()"
    >
        <div class="app-container section-stack">
            <div class="panel seleccion-dashboard-page__filters-panel">
                <div class="panel__body panel__body--compact">
                    <div class="seleccion-dashboard-page__filters">
                        <div class="form-field">
                            <label class="form-label" for="dash_date_from">Fecha desde</label>
                            <input
                                id="dash_date_from"
                                type="date"
                                class="form-input"
                                x-model="filters.date_from"
                                @change="scheduleRefresh()"
                            >
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="dash_date_to">Fecha hasta</label>
                            <input
                                id="dash_date_to"
                                type="date"
                                class="form-input"
                                x-model="filters.date_to"
                                @change="scheduleRefresh()"
                            >
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="dash_commercial_client_id">Cliente</label>
                            <x-searchable-select
                                id="dash_commercial_client_id"
                                name="commercial_client_id"
                                :options="$clientOptions"
                                :value="$filters['commercial_client_id']"
                                placeholder="Todos"
                                :allow-clear="true"
                                x-on:change="onSelectChange('commercial_client_id', $event)"
                            />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="dash_responsable_user_id">Responsable</label>
                            <x-searchable-select
                                id="dash_responsable_user_id"
                                name="responsable_user_id"
                                :options="$responsableOptions"
                                :value="$filters['responsable_user_id']"
                                placeholder="Todos"
                                :allow-clear="true"
                                x-on:change="onSelectChange('responsable_user_id', $event)"
                            />
                        </div>
                        <div class="form-field seleccion-dashboard-page__filter-meta">
                            <span class="panel-text" x-show="loading" x-cloak>Actualizando…</span>
                            <span class="panel-text" x-show="!loading && error" x-text="error" x-cloak></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="seleccion-dashboard-page__kpi-grid">
                <div class="card seleccion-dashboard-page__kpi" style="border-left-color:#0369a1;">
                    <p class="seleccion-dashboard-page__kpi-label">Total ingresos</p>
                    <p class="seleccion-dashboard-page__kpi-value" x-text="kpis.total_ingresos"></p>
                </div>
                <div class="card seleccion-dashboard-page__kpi" style="border-left-color:#15803d;">
                    <p class="seleccion-dashboard-page__kpi-label">Total exámenes</p>
                    <p class="seleccion-dashboard-page__kpi-value" x-text="kpis.total_examenes"></p>
                </div>
                <div class="card seleccion-dashboard-page__kpi" style="border-left-color:#ca8a04;">
                    <p class="seleccion-dashboard-page__kpi-label">Ingresos del mes</p>
                    <p class="seleccion-dashboard-page__kpi-value" x-text="kpis.ingresos_del_mes"></p>
                </div>
                <div class="card seleccion-dashboard-page__kpi" style="border-left-color:#ea580c;">
                    <p class="seleccion-dashboard-page__kpi-label">Exámenes en proceso</p>
                    <p class="seleccion-dashboard-page__kpi-value" x-text="kpis.examenes_en_proceso"></p>
                </div>
            </div>

            <div class="seleccion-dashboard-page__charts">
                <div class="panel">
                    <div class="panel__header">
                        <h3 class="panel-title">Estado SOLICITUD</h3>
                    </div>
                    <div class="panel__body">
                        <div id="seleccion-chart-solicitud" class="seleccion-dashboard-page__chart"></div>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel__header">
                        <h3 class="panel-title">Por cliente</h3>
                    </div>
                    <div class="panel__body">
                        <div id="seleccion-chart-cliente" class="seleccion-dashboard-page__chart"></div>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel__header">
                        <h3 class="panel-title">Por responsable</h3>
                    </div>
                    <div class="panel__body">
                        <div id="seleccion-chart-responsable" class="seleccion-dashboard-page__chart"></div>
                    </div>
                </div>
                <div class="panel seleccion-dashboard-page__chart-wide">
                    <div class="panel__header">
                        <h3 class="panel-title">Tendencia mensual de ingresos</h3>
                    </div>
                    <div class="panel__body">
                        <div id="seleccion-chart-trend" class="seleccion-dashboard-page__chart seleccion-dashboard-page__chart--tall"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @vite(['resources/js/seleccion-dashboard-charts.js'])

    @push('scripts')
        <script>
            function seleccionDashboardPage(config) {
                return {
                    metricsUrl: config.metricsUrl,
                    filters: { ...config.filters },
                    kpis: { ...(config.initial?.kpis || {}) },
                    loading: false,
                    error: '',
                    refreshTimer: null,
                    init() {
                        if (typeof window.renderSeleccionDashboardCharts === 'function') {
                            window.renderSeleccionDashboardCharts(config.initial?.charts);
                        } else {
                            window.addEventListener('load', () => {
                                window.renderSeleccionDashboardCharts?.(config.initial?.charts);
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
                            window.renderSeleccionDashboardCharts?.(payload.charts);
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
