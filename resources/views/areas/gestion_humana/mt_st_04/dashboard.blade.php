<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.mt_st_04.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Dashboard KPIs + ApexCharts (patrón Formación / Acreditaciones) --}}
    <div
        class="page-section mt-st-04-page cursos-dashboard-page"
        x-data="mtSt04DashboardPage(@js([
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
                        <div class="form-field cursos-dashboard-page__filter-meta">
                            <span class="panel-text" x-show="loading" x-cloak>Actualizando…</span>
                            <span class="panel-text" x-show="!loading && error" x-text="error" x-cloak></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KPI examen psicofísico (armas) --}}
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Examen psicofísico (armas)</h3>
                </div>
                <div class="panel__body panel__body--compact">
                    <div class="cursos-dashboard-page__kpi-grid">
                        <div class="card cursos-dashboard-page__kpi" style="border-left-color:#0f172a;">
                            <p class="cursos-dashboard-page__kpi-label" x-text="labels.examen1_total">Total examen 1</p>
                            <p class="cursos-dashboard-page__kpi-value" x-text="kpis.examen1.total">0</p>
                        </div>
                        <div class="card cursos-dashboard-page__kpi" style="border-left-color:#15803d;">
                            <p class="cursos-dashboard-page__kpi-label" x-text="labels.examen1_vigente">Vigente</p>
                            <p class="cursos-dashboard-page__kpi-value" x-text="kpis.examen1.vigente">0</p>
                        </div>
                        <div class="card cursos-dashboard-page__kpi" style="border-left-color:#ca8a04;">
                            <p class="cursos-dashboard-page__kpi-label" x-text="labels.examen1_vencera">Vencerá</p>
                            <p class="cursos-dashboard-page__kpi-value" x-text="kpis.examen1.vencera">0</p>
                        </div>
                        <div class="card cursos-dashboard-page__kpi" style="border-left-color:#be123c;">
                            <p class="cursos-dashboard-page__kpi-label" x-text="labels.examen1_vencido">Vencido</p>
                            <p class="cursos-dashboard-page__kpi-value" x-text="kpis.examen1.vencido">0</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KPI examen psicosensométrico (vial); Total2 excluye NO APLICA --}}
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Examen psicosensométrico (vial)</h3>
                    <p class="panel-text">Total y estados excluyen filas NO APLICA (cargos GUARDA / OPERADOR)</p>
                </div>
                <div class="panel__body panel__body--compact">
                    <div class="cursos-dashboard-page__kpi-grid">
                        <div class="card cursos-dashboard-page__kpi" style="border-left-color:#0f172a;">
                            <p class="cursos-dashboard-page__kpi-label" x-text="labels.examen2_total">Total examen 2</p>
                            <p class="cursos-dashboard-page__kpi-value" x-text="kpis.examen2.total">0</p>
                        </div>
                        <div class="card cursos-dashboard-page__kpi" style="border-left-color:#15803d;">
                            <p class="cursos-dashboard-page__kpi-label" x-text="labels.examen2_vigente">Vigente</p>
                            <p class="cursos-dashboard-page__kpi-value" x-text="kpis.examen2.vigente">0</p>
                        </div>
                        <div class="card cursos-dashboard-page__kpi" style="border-left-color:#ca8a04;">
                            <p class="cursos-dashboard-page__kpi-label" x-text="labels.examen2_vencera">Vencerá</p>
                            <p class="cursos-dashboard-page__kpi-value" x-text="kpis.examen2.vencera">0</p>
                        </div>
                        <div class="card cursos-dashboard-page__kpi" style="border-left-color:#be123c;">
                            <p class="cursos-dashboard-page__kpi-label" x-text="labels.examen2_vencido">Vencido</p>
                            <p class="cursos-dashboard-page__kpi-value" x-text="kpis.examen2.vencido">0</p>
                        </div>
                        <div class="card cursos-dashboard-page__kpi" style="border-left-color:#64748b;">
                            <p class="cursos-dashboard-page__kpi-label" x-text="labels.examen2_no_aplica">NO APLICA</p>
                            <p class="cursos-dashboard-page__kpi-value" x-text="kpis.examen2.no_aplica">0</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Aptos vs no aptos --}}
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Aptitud (examen 1)</h3>
                </div>
                <div class="panel__body panel__body--compact">
                    <div class="cursos-dashboard-page__kpi-grid">
                        <div class="card cursos-dashboard-page__kpi" style="border-left-color:#15803d;">
                            <p class="cursos-dashboard-page__kpi-label" x-text="labels.apto_si">Aptos</p>
                            <p class="cursos-dashboard-page__kpi-value" x-text="kpis.apto.si">0</p>
                        </div>
                        <div class="card cursos-dashboard-page__kpi" style="border-left-color:#be123c;">
                            <p class="cursos-dashboard-page__kpi-label" x-text="labels.apto_no">No aptos</p>
                            <p class="cursos-dashboard-page__kpi-value" x-text="kpis.apto.no">0</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="cursos-dashboard-page__charts">
                <div class="panel">
                    <div class="panel__header">
                        <h3 class="panel-title">Estados examen 1</h3>
                    </div>
                    <div class="panel__body">
                        <div id="mt-st-04-chart-examen1" class="cursos-dashboard-page__chart"></div>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel__header">
                        <h3 class="panel-title">Estados examen 2</h3>
                        <p class="panel-text">Sin NO APLICA</p>
                    </div>
                    <div class="panel__body">
                        <div id="mt-st-04-chart-examen2" class="cursos-dashboard-page__chart"></div>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel__header">
                        <h3 class="panel-title">Aptos vs no aptos</h3>
                    </div>
                    <div class="panel__body">
                        <div id="mt-st-04-chart-aptos" class="cursos-dashboard-page__chart"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @vite(['resources/js/mt-st-04-dashboard-charts.js'])

    @push('scripts')
        <script>
            function mtSt04DashboardPage(config) {
                return {
                    metricsUrl: config.metricsUrl,
                    filters: { ...config.filters },
                    kpis: {
                        examen1: { ...(config.initial?.kpis?.examen1 || {}) },
                        examen2: { ...(config.initial?.kpis?.examen2 || {}) },
                        apto: { ...(config.initial?.kpis?.apto || {}) },
                    },
                    labels: { ...(config.initial?.labels || {}) },
                    loading: false,
                    error: '',
                    refreshTimer: null,
                    init() {
                        if (typeof window.renderMtSt04DashboardCharts === 'function') {
                            window.renderMtSt04DashboardCharts(config.initial?.charts);
                        } else {
                            window.addEventListener('load', () => {
                                window.renderMtSt04DashboardCharts?.(config.initial?.charts);
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
                            this.kpis = {
                                examen1: { ...(payload.kpis?.examen1 || {}) },
                                examen2: { ...(payload.kpis?.examen2 || {}) },
                                apto: { ...(payload.kpis?.apto || {}) },
                            };
                            this.labels = { ...(payload.labels || this.labels) };
                            if (payload.filters) {
                                this.filters = { ...this.filters, ...payload.filters };
                            }
                            window.renderMtSt04DashboardCharts?.(payload.charts);
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
