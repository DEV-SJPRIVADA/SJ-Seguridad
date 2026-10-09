<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.mt_st_04.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Dashboard: subpestañas Psicofísicos / Psicosensométricos (patrón Formación) --}}
    <div
        class="page-section mt-st-04-page mt-st-04-dashboard-page cursos-dashboard-page formacion-dashboard-page"
        x-data="mtSt04DashboardPage(@js([
            'metricsUrl' => $metricsUrl,
            'matrizUrl' => $matrizUrl,
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
                        <div class="form-field">
                            <label class="form-label" for="dash_ciudad">Ciudad</label>
                            <x-searchable-select
                                id="dash_ciudad"
                                name="ciudad"
                                :options="$filterCiudadOptions"
                                :value="$filters['ciudad']"
                                placeholder="Todas"
                                search-placeholder="Buscar ciudad…"
                                :allow-clear="false"
                                x-on:change="onSelectChange('ciudad', $event)"
                            />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="dash_cargo">Cargo</label>
                            <x-searchable-select
                                id="dash_cargo"
                                name="cargo"
                                :options="$filterCargoOptions"
                                :value="$filters['cargo']"
                                placeholder="Todos"
                                search-placeholder="Buscar cargo…"
                                :allow-clear="false"
                                x-on:change="onSelectChange('cargo', $event)"
                            />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="dash_puesto">Puesto</label>
                            <x-searchable-select
                                id="dash_puesto"
                                name="puesto"
                                :options="$filterPuestoOptions"
                                :value="$filters['puesto']"
                                placeholder="Todos"
                                search-placeholder="Buscar puesto…"
                                :allow-clear="false"
                                x-on:change="onSelectChange('puesto', $event)"
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
                            <span class="panel-text" x-show="!loading && error" x-text="error" x-cloak></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="formacion-dashboard-page__mode-tabs" role="tablist" aria-label="Tipo de examen">
                <button
                    type="button"
                    class="formacion-dashboard-page__mode-tab"
                    :class="{ 'is-active': mode === 'psicofisicos' }"
                    role="tab"
                    :aria-selected="(mode === 'psicofisicos').toString()"
                    x-on:click="setMode('psicofisicos')"
                >Psicofísicos</button>
                <button
                    type="button"
                    class="formacion-dashboard-page__mode-tab"
                    :class="{ 'is-active': mode === 'psicosensometricos' }"
                    role="tab"
                    :aria-selected="(mode === 'psicosensometricos').toString()"
                    x-on:click="setMode('psicosensometricos')"
                >Psicosensométricos</button>
            </div>

            {{-- Subpestaña psicofísicos (examen 1 / armas) --}}
            <div x-show="mode === 'psicofisicos'" x-cloak class="mt-st-04-dash-pane section-stack">
                <div class="cursos-dashboard-page__kpi-grid formacion-dashboard-page__kpi-grid">
                    <button
                        type="button"
                        class="card cursos-dashboard-page__kpi formacion-dashboard-page__kpi-link"
                        style="border-left-color:#0f172a;"
                        title="Ver en Matriz"
                        x-on:click="goToMatriz({})"
                    >
                        <p class="cursos-dashboard-page__kpi-label" x-text="labels.examen1_total">Total psicofísicos</p>
                        <p class="cursos-dashboard-page__kpi-value" x-text="kpis.examen1.total">0</p>
                    </button>
                    <button
                        type="button"
                        class="card cursos-dashboard-page__kpi formacion-dashboard-page__kpi-link"
                        style="border-left-color:#15803d;"
                        title="Ver vigentes en Matriz"
                        x-on:click="goToMatriz({ estado_1: 'VIGENTE' })"
                    >
                        <p class="cursos-dashboard-page__kpi-label" x-text="labels.examen1_vigente">Vigente</p>
                        <p class="cursos-dashboard-page__kpi-value" x-text="kpis.examen1.vigente">0</p>
                    </button>
                    <button
                        type="button"
                        class="card cursos-dashboard-page__kpi formacion-dashboard-page__kpi-link"
                        style="border-left-color:#ca8a04;"
                        title="Ver por vencer en Matriz"
                        x-on:click="goToMatriz({ estado_1: 'VENCERA' })"
                    >
                        <p class="cursos-dashboard-page__kpi-label" x-text="labels.examen1_vencera">Vencerá</p>
                        <p class="cursos-dashboard-page__kpi-value" x-text="kpis.examen1.vencera">0</p>
                    </button>
                    <button
                        type="button"
                        class="card cursos-dashboard-page__kpi formacion-dashboard-page__kpi-link"
                        style="border-left-color:#be123c;"
                        title="Ver vencidos en Matriz"
                        x-on:click="goToMatriz({ estado_1: 'VENCIDO' })"
                    >
                        <p class="cursos-dashboard-page__kpi-label" x-text="labels.examen1_vencido">Vencido</p>
                        <p class="cursos-dashboard-page__kpi-value" x-text="kpis.examen1.vencido">0</p>
                    </button>
                    <button
                        type="button"
                        class="card cursos-dashboard-page__kpi formacion-dashboard-page__kpi-link"
                        style="border-left-color:#15803d;"
                        title="Ver aptos en Matriz"
                        x-on:click="goToMatriz({ apto: 'SI' })"
                    >
                        <p class="cursos-dashboard-page__kpi-label" x-text="labels.apto_si">Aptos</p>
                        <p class="cursos-dashboard-page__kpi-value" x-text="kpis.apto.si">0</p>
                    </button>
                    <button
                        type="button"
                        class="card cursos-dashboard-page__kpi formacion-dashboard-page__kpi-link"
                        style="border-left-color:#be123c;"
                        title="Ver no aptos en Matriz"
                        x-on:click="goToMatriz({ apto: 'NO' })"
                    >
                        <p class="cursos-dashboard-page__kpi-label" x-text="labels.apto_no">No aptos</p>
                        <p class="cursos-dashboard-page__kpi-value" x-text="kpis.apto.no">0</p>
                    </button>
                </div>

                <div class="cursos-dashboard-page__charts mt-st-04-dash-charts">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Estados psicofísicos</h3>
                        </div>
                        <div class="panel__body">
                            <div id="mt-st-04-chart-examen1" class="cursos-dashboard-page__chart"></div>
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
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Tendencia de vencimientos (año)</h3>
                            <p class="panel-text">Cantidad por año de fecha de vencimiento psicofísico</p>
                        </div>
                        <div class="panel__body">
                            <div id="mt-st-04-chart-examen1-anio" class="cursos-dashboard-page__chart"></div>
                        </div>
                    </div>
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Cantidad por cargo</h3>
                        </div>
                        <div class="panel__body">
                            <div id="mt-st-04-chart-examen1-cargo" class="cursos-dashboard-page__chart mt-st-04-dash-chart--cargo"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Subpestaña psicosensométricos (examen 2 / vial) --}}
            <div x-show="mode === 'psicosensometricos'" x-cloak class="mt-st-04-dash-pane section-stack">
                <p class="panel-text mt-st-04-dash-pane__hint">
                    Total y estados excluyen filas NO APLICA (cargos GUARDA / OPERADOR).
                </p>
                <div class="cursos-dashboard-page__kpi-grid formacion-dashboard-page__kpi-grid">
                    <button
                        type="button"
                        class="card cursos-dashboard-page__kpi formacion-dashboard-page__kpi-link"
                        style="border-left-color:#0f172a;"
                        title="Ver en Matriz (sin NO APLICA)"
                        x-on:click="goToMatriz({ estado_2: '__sin_no_aplica__' })"
                    >
                        <p class="cursos-dashboard-page__kpi-label" x-text="labels.examen2_total">Total psicosensométricos</p>
                        <p class="cursos-dashboard-page__kpi-value" x-text="kpis.examen2.total">0</p>
                    </button>
                    <button
                        type="button"
                        class="card cursos-dashboard-page__kpi formacion-dashboard-page__kpi-link"
                        style="border-left-color:#15803d;"
                        title="Ver vigentes en Matriz"
                        x-on:click="goToMatriz({ estado_2: 'VIGENTE' })"
                    >
                        <p class="cursos-dashboard-page__kpi-label" x-text="labels.examen2_vigente">Vigente</p>
                        <p class="cursos-dashboard-page__kpi-value" x-text="kpis.examen2.vigente">0</p>
                    </button>
                    <button
                        type="button"
                        class="card cursos-dashboard-page__kpi formacion-dashboard-page__kpi-link"
                        style="border-left-color:#ca8a04;"
                        title="Ver por vencer en Matriz"
                        x-on:click="goToMatriz({ estado_2: 'VENCERA' })"
                    >
                        <p class="cursos-dashboard-page__kpi-label" x-text="labels.examen2_vencera">Vencerá</p>
                        <p class="cursos-dashboard-page__kpi-value" x-text="kpis.examen2.vencera">0</p>
                    </button>
                    <button
                        type="button"
                        class="card cursos-dashboard-page__kpi formacion-dashboard-page__kpi-link"
                        style="border-left-color:#be123c;"
                        title="Ver vencidos en Matriz"
                        x-on:click="goToMatriz({ estado_2: 'VENCIDO' })"
                    >
                        <p class="cursos-dashboard-page__kpi-label" x-text="labels.examen2_vencido">Vencido</p>
                        <p class="cursos-dashboard-page__kpi-value" x-text="kpis.examen2.vencido">0</p>
                    </button>
                    <button
                        type="button"
                        class="card cursos-dashboard-page__kpi formacion-dashboard-page__kpi-link"
                        style="border-left-color:#64748b;"
                        title="Ver NO APLICA en Matriz"
                        x-on:click="goToMatriz({ estado_2: 'NO APLICA' })"
                    >
                        <p class="cursos-dashboard-page__kpi-label" x-text="labels.examen2_no_aplica">NO APLICA</p>
                        <p class="cursos-dashboard-page__kpi-value" x-text="kpis.examen2.no_aplica">0</p>
                    </button>
                </div>

                <div class="cursos-dashboard-page__charts mt-st-04-dash-charts">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Estados psicosensométricos</h3>
                            <p class="panel-text">Sin NO APLICA</p>
                        </div>
                        <div class="panel__body">
                            <div id="mt-st-04-chart-examen2" class="cursos-dashboard-page__chart"></div>
                        </div>
                    </div>
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Tendencia de vencimientos (año)</h3>
                            <p class="panel-text">Cantidad por año de fecha de vencimiento psicosensométrico</p>
                        </div>
                        <div class="panel__body">
                            <div id="mt-st-04-chart-examen2-anio" class="cursos-dashboard-page__chart"></div>
                        </div>
                    </div>
                    <div class="panel mt-st-04-dash-chart-span">
                        <div class="panel__header">
                            <h3 class="panel-title">Cantidad por cargo</h3>
                            <p class="panel-text">Sin NO APLICA</p>
                        </div>
                        <div class="panel__body">
                            <div id="mt-st-04-chart-examen2-cargo" class="cursos-dashboard-page__chart mt-st-04-dash-chart--cargo"></div>
                        </div>
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
                    matrizUrl: config.matrizUrl,
                    mode: 'psicofisicos',
                    filters: { ...config.filters },
                    kpis: {
                        examen1: { ...(config.initial?.kpis?.examen1 || {}) },
                        examen2: { ...(config.initial?.kpis?.examen2 || {}) },
                        apto: { ...(config.initial?.kpis?.apto || {}) },
                    },
                    labels: { ...(config.initial?.labels || {}) },
                    _lastCharts: config.initial?.charts || null,
                    _chartsReadyTimer: null,
                    loading: false,
                    error: '',
                    refreshTimer: null,
                    init() {
                        // Vite carga el módulo de ApexCharts async: hay que esperar
                        // a window.renderMtSt04DashboardCharts (si no, el primer paint queda vacío).
                        this.waitForChartsReady(() => this.paintCharts());
                    },
                    setMode(mode) {
                        this.mode = mode === 'psicosensometricos' ? 'psicosensometricos' : 'psicofisicos';
                        this.paintCharts();
                    },
                    waitForChartsReady(onReady) {
                        const run = () => {
                            if (typeof window.renderMtSt04DashboardCharts !== 'function') {
                                return false;
                            }
                            onReady();
                            return true;
                        };

                        if (run()) {
                            return;
                        }

                        const onReadyEvent = () => run();
                        window.addEventListener('mt-st-04-dashboard-charts-ready', onReadyEvent, { once: true });
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
                        // Doble tick: Alpine quita x-cloak/x-show y el layout ya tiene ancho > 0.
                        this.$nextTick(() => {
                            this.renderChartsForMode(this._lastCharts);
                            requestAnimationFrame(() => {
                                this.renderChartsForMode(this._lastCharts);
                            });
                        });
                    },
                    renderChartsForMode(charts) {
                        this._lastCharts = charts || this._lastCharts || {};
                        if (typeof window.renderMtSt04DashboardCharts !== 'function') {
                            return;
                        }
                        window.renderMtSt04DashboardCharts(this._lastCharts, this.mode);
                    },
                    /** Abre Matriz con filtros del KPI + filtros activos del dashboard. */
                    goToMatriz(overrides = {}) {
                        const params = new URLSearchParams();
                        const shared = ['ficha_estado', 'ciudad', 'cargo', 'puesto'];
                        shared.forEach((key) => {
                            const value = String(overrides[key] ?? this.filters[key] ?? '');
                            if (value !== '' && value !== 'todos') {
                                params.set(key, value);
                            } else if (key === 'ficha_estado' && value === 'todos') {
                                params.set(key, 'todos');
                            }
                        });
                        // Default de matriz = activo si no viene nada.
                        if (! params.has('ficha_estado')) {
                            params.set('ficha_estado', String(this.filters.ficha_estado || 'activo'));
                        }

                        ['estado_1', 'estado_2', 'apto', 'arma'].forEach((key) => {
                            if (! Object.prototype.hasOwnProperty.call(overrides, key)) {
                                return;
                            }
                            const value = String(overrides[key] ?? '');
                            if (value !== '' && value !== 'todos') {
                                params.set(key, value);
                            }
                        });

                        const query = params.toString();
                        window.location.href = query
                            ? `${this.matrizUrl}?${query}`
                            : this.matrizUrl;
                    },
                    onSelectChange(key, event) {
                        let value = event?.detail?.value;
                        if (value === undefined || value === null) {
                            value = event?.target?.value ?? '';
                        }
                        this.filters[key] = value;
                        this.scheduleRefresh();
                    },
                    clearFilters() {
                        // Recarga limpia para sincronizar searchable-selects con defaults.
                        const url = new URL(window.location.href);
                        url.search = '';
                        window.location.href = url.toString();
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
                            this._lastCharts = payload.charts || this._lastCharts;
                            this.paintCharts();
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
