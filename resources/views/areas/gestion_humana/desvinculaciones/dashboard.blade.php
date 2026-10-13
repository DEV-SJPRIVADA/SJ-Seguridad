<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.desvinculaciones.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Dashboard Desvinculaciones: filtros + KPIs + ApexCharts (chrome alineado al dashboard área GH) --}}
    <div
        class="page-section cursos-dashboard-page formacion-dashboard-page gh-area-dashboard-page desvinculaciones-dashboard-page"
        x-data="desvinculacionesDashboardPage(@js([
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
                            <label class="form-label" for="desv_dash_anio">Año</label>
                            <x-searchable-select
                                id="desv_dash_anio"
                                name="anio"
                                :options="$yearOptions"
                                :value="$filters['anio']"
                                placeholder="Seleccionar año"
                                :allow-clear="false"
                                x-on:change="onSelectChange('anio', $event)"
                            />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="desv_dash_mes">Mes</label>
                            <x-searchable-select
                                id="desv_dash_mes"
                                name="mes"
                                :options="$monthOptions"
                                :value="$filters['mes']"
                                placeholder="Seleccionar mes"
                                :allow-clear="false"
                                x-on:change="onSelectChange('mes', $event)"
                            />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="desv_dash_ciudad">Ciudad</label>
                            <x-searchable-select
                                id="desv_dash_ciudad"
                                name="ciudad"
                                :options="$cityOptions"
                                :value="$filters['ciudad']"
                                placeholder="Todas"
                                search-placeholder="Buscar ciudad…"
                                :allow-clear="true"
                                x-on:change="onSelectChange('ciudad', $event)"
                            />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="desv_dash_cargo">Cargo</label>
                            <x-searchable-select
                                id="desv_dash_cargo"
                                name="cargo"
                                :options="$positionOptions"
                                :value="$filters['cargo']"
                                placeholder="Todos"
                                search-placeholder="Buscar cargo…"
                                :allow-clear="true"
                                x-on:change="onSelectChange('cargo', $event)"
                            />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="desv_dash_cliente">Cliente</label>
                            <x-searchable-select
                                id="desv_dash_cliente"
                                name="cliente"
                                :options="$clientOptions"
                                :value="$filters['cliente']"
                                placeholder="Todos"
                                search-placeholder="Buscar cliente…"
                                :allow-clear="true"
                                x-on:change="onSelectChange('cliente', $event)"
                            />
                        </div>
                        <div class="form-field cursos-dashboard-page__filter-actions">
                            <button
                                type="button"
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                title="Limpiar filtros"
                                aria-label="Limpiar filtros"
                                x-on:click="clearFilters()"
                            >
                                <x-lucide-x width="18" height="18" aria-hidden="true" />
                            </button>
                            <span class="panel-text" x-show="loading" x-cloak>Actualizando…</span>
                            <span class="panel-text formacion-dashboard-page__error" x-show="!loading && error" x-text="error" x-cloak></span>
                        </div>
                    </div>
                    <p class="panel-text" style="margin:0.35rem 0 0; font-size:0.8rem;" x-text="meta.filters_nota"></p>
                </div>
            </div>

            {{-- KPIs del mes/año --}}
            <div class="cursos-dashboard-page__kpi-grid desvinculaciones-dashboard-page__kpi-grid">
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#be123c;">
                    <p class="cursos-dashboard-page__kpi-label">Desvinculaciones del mes</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="kpis.bajas_mes">0</p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#0f172a;">
                    <p class="cursos-dashboard-page__kpi-label">Desvinculaciones del año</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="kpis.bajas_anio">0</p>
                    <p class="formacion-dashboard-page__kpi-hint">Ene → mes seleccionado</p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#0369a1;">
                    <p class="cursos-dashboard-page__kpi-label">Top causal</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="kpis.top_causal_mes">0</p>
                    <p class="formacion-dashboard-page__kpi-hint" x-text="kpis.top_causal_label">—</p>
                </div>
            </div>

            <div class="gh-area-dashboard-page__charts desvinculaciones-dashboard-page__charts">
                <div class="gh-area-dashboard-page__charts-row desvinculaciones-dashboard-page__charts-row--top">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Evolución mensual</h3>
                        </div>
                        <div class="panel__body">
                            <div id="desv-chart-evolucion-mensual" class="cursos-dashboard-page__chart"></div>
                        </div>
                    </div>
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Por causal</h3>
                        </div>
                        <div class="panel__body">
                            <div id="desv-chart-por-causal" class="cursos-dashboard-page__chart"></div>
                        </div>
                    </div>
                </div>
                <div class="gh-area-dashboard-page__charts-row desvinculaciones-dashboard-page__charts-row--bottom">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Por ciudad</h3>
                        </div>
                        <div class="panel__body">
                            <div id="desv-chart-por-ciudad" class="cursos-dashboard-page__chart desvinculaciones-dashboard-page__chart--bars"></div>
                        </div>
                    </div>
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Por cliente</h3>
                        </div>
                        <div class="panel__body">
                            <div id="desv-chart-por-cliente" class="cursos-dashboard-page__chart desvinculaciones-dashboard-page__chart--bars"></div>
                        </div>
                    </div>
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Por cargo</h3>
                        </div>
                        <div class="panel__body">
                            <div id="desv-chart-por-cargo" class="cursos-dashboard-page__chart desvinculaciones-dashboard-page__chart--bars"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @vite(['resources/js/desvinculaciones-dashboard-charts.js'])

    @push('scripts')
        <script>
            function desvinculacionesDashboardPage(config) {
                const defaultKpis = {
                    bajas_mes: 0,
                    bajas_anio: 0,
                    top_causal_mes: 0,
                    top_causal_label: '—',
                };
                const defaultMeta = {
                    filters_nota: 'Las desvinculaciones usan fecha de desvinculación del periodo. Ciudad/cliente vienen de la ficha.',
                };

                return {
                    metricsUrl: config.metricsUrl,
                    filters: {
                        anio: String(config.filters?.anio ?? ''),
                        mes: String(config.filters?.mes ?? ''),
                        ciudad: String(config.filters?.ciudad ?? ''),
                        cargo: String(config.filters?.cargo ?? ''),
                        cliente: String(config.filters?.cliente ?? ''),
                    },
                    kpis: { ...defaultKpis, ...(config.initial?.kpis || {}) },
                    meta: { ...defaultMeta, ...(config.initial?.meta || {}) },
                    _lastCharts: config.initial?.charts || null,
                    _chartsReadyTimer: null,
                    loading: false,
                    error: '',
                    refreshTimer: null,
                    init() {
                        this.waitForChartsReady(() => this.paintCharts());
                    },
                    waitForChartsReady(onReady) {
                        const run = () => {
                            if (typeof window.renderDesvinculacionesDashboardCharts !== 'function') {
                                return false;
                            }
                            onReady();
                            return true;
                        };

                        if (run()) {
                            return;
                        }

                        const onReadyEvent = () => run();
                        window.addEventListener('desvinculaciones-dashboard-charts-ready', onReadyEvent, { once: true });
                        window.addEventListener('load', onReadyEvent, { once: true });

                        let attempts = 0;
                        clearInterval(this._chartsReadyTimer);
                        this._chartsReadyTimer = setInterval(() => {
                            attempts += 1;
                            if (run() || attempts > 60) {
                                clearInterval(this._chartsReadyTimer);
                                this._chartsReadyTimer = null;
                            }
                        }, 50);
                    },
                    paintCharts() {
                        this.$nextTick(() => {
                            this.renderCharts(this._lastCharts);
                            requestAnimationFrame(() => {
                                this.renderCharts(this._lastCharts);
                            });
                        });
                    },
                    renderCharts(charts) {
                        this._lastCharts = charts || this._lastCharts || {};
                        if (typeof window.renderDesvinculacionesDashboardCharts !== 'function') {
                            return;
                        }
                        window.renderDesvinculacionesDashboardCharts(this._lastCharts);
                    },
                    onSelectChange(key, event) {
                        const value = event?.detail?.value ?? event?.target?.value ?? '';
                        this.filters[key] = String(value ?? '');
                        this.scheduleRefresh();
                    },
                    clearFilters() {
                        const now = new Date();
                        this.filters.ciudad = '';
                        this.filters.cargo = '';
                        this.filters.cliente = '';
                        this.filters.anio = String(now.getFullYear());
                        this.filters.mes = String(now.getMonth() + 1);
                        this.scheduleRefresh(true);
                    },
                    scheduleRefresh(immediate = false) {
                        clearTimeout(this.refreshTimer);
                        this.refreshTimer = setTimeout(() => this.refresh(), immediate ? 0 : 180);
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
                                if (value !== '' && value !== null && value !== undefined) {
                                    params.set(key, value);
                                }
                            });
                            const response = await fetch(`${this.metricsUrl}?${params.toString()}`, {
                                headers: {
                                    Accept: 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                            });
                            if (!response.ok) {
                                throw new Error('No se pudieron cargar las métricas.');
                            }
                            const payload = await response.json();
                            this.kpis = { ...defaultKpis, ...(payload.kpis || {}) };
                            this.meta = { ...defaultMeta, ...(payload.meta || {}) };
                            if (payload.filters) {
                                this.filters = {
                                    anio: String(payload.filters.anio ?? this.filters.anio),
                                    mes: String(payload.filters.mes ?? this.filters.mes),
                                    ciudad: String(payload.filters.ciudad ?? ''),
                                    cargo: String(payload.filters.cargo ?? ''),
                                    cliente: String(payload.filters.cliente ?? ''),
                                };
                            }
                            this.renderCharts(payload.charts || {});
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
