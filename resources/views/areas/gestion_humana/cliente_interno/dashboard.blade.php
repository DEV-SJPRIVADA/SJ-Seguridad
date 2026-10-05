<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.cliente_interno.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Dashboard KPIs Cliente interno: filtros año/mes + ApexCharts vía metrics JSON --}}
    <div
        class="page-section cursos-dashboard-page formacion-dashboard-page"
        x-data="clienteInternoDashboardPage(@js([
            'metricsUrl' => $metricsUrl,
            'solicitudesUrl' => $solicitudesUrl,
            'canViewSolicitudes' => $canViewSolicitudes,
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
                            <label class="form-label" for="dash_anio">Año</label>
                            <x-searchable-select
                                id="dash_anio"
                                name="anio"
                                :options="$yearOptions"
                                :value="$filters['anio']"
                                placeholder="Seleccionar año"
                                :allow-clear="false"
                                x-on:change="onSelectChange('anio', $event)"
                            />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="dash_mes">Mes</label>
                            <x-searchable-select
                                id="dash_mes"
                                name="mes"
                                :options="$monthOptions"
                                :value="$filters['mes']"
                                placeholder="Todos"
                                :allow-clear="true"
                                x-on:change="onSelectChange('mes', $event)"
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
                </div>
            </div>

            <div class="cursos-dashboard-page__kpi-grid formacion-dashboard-page__kpi-grid">
                <button
                    type="button"
                    class="card cursos-dashboard-page__kpi formacion-dashboard-page__kpi-link"
                    style="border-left-color:#0369a1;"
                    title="Ver solicitudes del periodo"
                    :disabled="!canViewSolicitudes"
                    x-on:click="goToSolicitudes({})"
                >
                    <p class="cursos-dashboard-page__kpi-label">Total solicitudes</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="total"></p>
                    <p class="formacion-dashboard-page__kpi-hint">Año <span x-text="anio"></span></p>
                </button>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#15803d;">
                    <p class="cursos-dashboard-page__kpi-label">Promedio días respuesta</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="promedioDiasLabel"></p>
                    <p class="formacion-dashboard-page__kpi-hint">
                        <span x-text="diasConDato"></span> con dato
                    </p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#92400e;">
                    <p class="cursos-dashboard-page__kpi-label">Estados distintos</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="estadosConDatos"></p>
                    <p class="formacion-dashboard-page__kpi-hint">Incluye «Sin estado»</p>
                </div>
            </div>

            <div class="cursos-dashboard-page__charts formacion-dashboard-page__charts">
                <div class="panel cursos-dashboard-page__chart-wide">
                    <div class="panel__header">
                        <h3 class="panel-title">Tendencia mensual (<span x-text="anio"></span>)</h3>
                    </div>
                    <div class="panel__body">
                        <div id="cliente-interno-chart-tendencia" class="cursos-dashboard-page__chart cursos-dashboard-page__chart--tall"></div>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel__header">
                        <h3 class="panel-title">Por estado</h3>
                    </div>
                    <div class="panel__body">
                        <div id="cliente-interno-chart-estado" class="cursos-dashboard-page__chart"></div>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel__header">
                        <h3 class="panel-title">Distribución días de respuesta</h3>
                    </div>
                    <div class="panel__body">
                        <div id="cliente-interno-chart-dias" class="cursos-dashboard-page__chart cursos-dashboard-page__chart--tall"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @vite(['resources/js/cliente-interno-dashboard-charts.js'])

    @push('scripts')
        <script>
            function clienteInternoDashboardPage(config) {
                const defaultDias = {
                    promedio: null,
                    con_dato: 0,
                    distribucion: [],
                };

                return {
                    metricsUrl: config.metricsUrl,
                    solicitudesUrl: config.solicitudesUrl,
                    canViewSolicitudes: Boolean(config.canViewSolicitudes),
                    filters: {
                        anio: String(config.filters?.anio ?? ''),
                        mes: String(config.filters?.mes ?? ''),
                    },
                    total: config.initial?.total ?? 0,
                    anio: config.initial?.anio ?? '',
                    porEstado: Array.isArray(config.initial?.por_estado) ? config.initial.por_estado : [],
                    diasRespuesta: { ...defaultDias, ...(config.initial?.dias_respuesta || {}) },
                    loading: false,
                    error: '',
                    refreshTimer: null,
                    _lastCharts: config.initial?.charts || {},
                    get promedioDiasLabel() {
                        const value = this.diasRespuesta?.promedio;
                        if (value === null || value === undefined || value === '') {
                            return '—';
                        }
                        return Number(value).toLocaleString('es-CO', {
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 1,
                        });
                    },
                    get diasConDato() {
                        return Number(this.diasRespuesta?.con_dato ?? 0);
                    },
                    get estadosConDatos() {
                        return (this.porEstado || []).filter((row) => Number(row.total) > 0).length;
                    },
                    init() {
                        this.$nextTick(() => this.renderCharts(config.initial));
                    },
                    goToSolicitudes(overrides = {}) {
                        if (!this.canViewSolicitudes) {
                            return;
                        }
                        const params = new URLSearchParams();
                        const anio = String(overrides.anio ?? this.filters.anio ?? this.anio ?? '');
                        const mes = String(overrides.mes ?? this.filters.mes ?? '');
                        if (anio) {
                            params.set('anio', anio);
                        }
                        if (mes) {
                            params.set('mes', mes);
                        }
                        const query = params.toString();
                        window.location.href = query
                            ? `${this.solicitudesUrl}?${query}`
                            : this.solicitudesUrl;
                    },
                    renderCharts(payload) {
                        this._lastCharts = payload?.charts || this._lastCharts || {};
                        window.renderClienteInternoDashboardCharts?.(this._lastCharts);
                    },
                    onSelectChange(key, event) {
                        let value = event?.detail?.value;
                        if (value === undefined || value === null) {
                            value = event?.target?.value ?? '';
                        }
                        this.filters[key] = value === null || value === undefined ? '' : String(value);
                        this.scheduleRefresh();
                    },
                    clearFilters() {
                        const anio = String(this.filters.anio || this.anio || '');
                        this.filters = { anio, mes: '' };
                        this.setSelectValue('dash_mes', '');
                        this.scheduleRefresh();
                    },
                    searchableSelectData(inputId) {
                        const hidden = document.getElementById(inputId);
                        const wrap = hidden?.closest('.searchable-select-wrap');
                        if (!wrap || !window.Alpine || typeof window.Alpine.$data !== 'function') {
                            return null;
                        }
                        return window.Alpine.$data(wrap);
                    },
                    setSelectValue(inputId, value) {
                        const data = this.searchableSelectData(inputId);
                        if (data) {
                            data.value = value === null || value === undefined ? '' : String(value);
                            data.syncLabel?.();
                        }
                        const hidden = document.getElementById(inputId);
                        if (hidden) {
                            hidden.value = value === null || value === undefined ? '' : String(value);
                        }
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
                            ['anio', 'mes'].forEach((key) => {
                                const value = this.filters[key];
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
                            this.total = payload.total ?? 0;
                            this.anio = payload.anio ?? this.anio;
                            this.filters.anio = String(payload.filters?.anio ?? payload.anio ?? this.filters.anio);
                            this.filters.mes = String(payload.filters?.mes ?? this.filters.mes ?? '');
                            this.porEstado = Array.isArray(payload.por_estado) ? payload.por_estado : [];
                            this.diasRespuesta = { ...defaultDias, ...(payload.dias_respuesta || {}) };
                            this.$nextTick(() => this.renderCharts(payload));
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
