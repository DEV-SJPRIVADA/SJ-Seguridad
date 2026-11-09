<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.seleccion.partials.subnav', ['subTabs' => $subTabs])
        <div class="app-container seleccion-dashboard-page__header">
            <h2 class="seleccion-dashboard-page__title">Dashboard de Selección</h2>
            <p class="seleccion-dashboard-page__subtitle">Ingresos y exámenes ocupacionales · seguimiento del proceso</p>
        </div>
    </x-slot>

    <div
        class="page-section seleccion-dashboard-page"
        x-data="seleccionDashboardPage(@js([
            'metricsUrl' => $metricsUrl,
            'ingresosUrl' => $ingresosUrl,
            'examenesUrl' => $examenesUrl,
            'solicitudEnProcesoCode' => $solicitudEnProcesoCode,
            'today' => $today,
            'initial' => $initialPayload,
            'filters' => $filters,
        ]))"
        x-init="init()"
    >
        <div class="app-container section-stack">
            {{-- Filtros: fechas + cliente + responsable, con rangos rápidos --}}
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
                                x-on:change="scheduleRefresh()"
                            >
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="dash_date_to">Fecha hasta</label>
                            <input
                                id="dash_date_to"
                                type="date"
                                class="form-input"
                                x-model="filters.date_to"
                                x-on:change="scheduleRefresh()"
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
                                search-placeholder="Buscar cliente…"
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
                                search-placeholder="Buscar responsable…"
                                :allow-clear="true"
                                x-on:change="onSelectChange('responsable_user_id', $event)"
                            />
                        </div>
                        <div class="form-field seleccion-dashboard-page__filter-actions">
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
                            <span
                                class="panel-text formacion-dashboard-page__error"
                                x-show="!loading && error"
                                x-text="error"
                                x-cloak
                            ></span>
                        </div>
                    </div>

                    {{-- Rangos rápidos: un clic ajusta las dos fechas --}}
                    <div class="seleccion-dashboard-page__presets" role="group" aria-label="Rangos de fecha rápidos">
                        <span class="seleccion-dashboard-page__presets-label">Rango rápido</span>
                        <button
                            type="button"
                            class="seleccion-dashboard-page__preset"
                            x-bind:class="{ 'is-active': isPresetActive('mes') }"
                            x-on:click="applyPreset('mes')"
                        >Mes actual</button>
                        <button
                            type="button"
                            class="seleccion-dashboard-page__preset"
                            x-bind:class="{ 'is-active': isPresetActive('30d') }"
                            x-on:click="applyPreset('30d')"
                        >Últimos 30 días</button>
                        <button
                            type="button"
                            class="seleccion-dashboard-page__preset"
                            x-bind:class="{ 'is-active': isPresetActive('anio') }"
                            x-on:click="applyPreset('anio')"
                        >Año actual</button>
                        <button
                            type="button"
                            class="seleccion-dashboard-page__preset"
                            x-bind:class="{ 'is-active': isPresetActive('todo') }"
                            x-on:click="applyPreset('todo')"
                        >Todo el histórico</button>
                        <span
                            class="seleccion-dashboard-page__updated"
                            x-show="!loading && updatedAt"
                            x-cloak
                        >Actualizado <span x-text="updatedAt"></span></span>
                    </div>
                </div>
            </div>

            {{-- KPIs: un clic abre el listado con el mismo contexto de filtros --}}
            <div class="seleccion-dashboard-page__kpi-grid">
                <button
                    type="button"
                    class="card seleccion-dashboard-page__kpi seleccion-dashboard-page__kpi--ingresos"
                    title="Ver ingresos con estos filtros"
                    x-on:click="goToIngresos()"
                >
                    <span class="seleccion-dashboard-page__kpi-icon" aria-hidden="true">
                        <x-lucide-user-plus width="20" height="20" />
                    </span>
                    <span class="seleccion-dashboard-page__kpi-body">
                        <span class="seleccion-dashboard-page__kpi-label">Total ingresos</span>
                        <span class="seleccion-dashboard-page__kpi-value" x-text="kpis.total_ingresos">0</span>
                        <span class="seleccion-dashboard-page__kpi-hint">En el rango filtrado</span>
                    </span>
                    <span class="seleccion-dashboard-page__kpi-go" aria-hidden="true">
                        <x-lucide-arrow-up-right width="16" height="16" />
                    </span>
                </button>

                <button
                    type="button"
                    class="card seleccion-dashboard-page__kpi seleccion-dashboard-page__kpi--examenes"
                    title="Ver exámenes ocupacionales con estos filtros"
                    x-on:click="goToExamenes()"
                >
                    <span class="seleccion-dashboard-page__kpi-icon" aria-hidden="true">
                        <x-lucide-clipboard-list width="20" height="20" />
                    </span>
                    <span class="seleccion-dashboard-page__kpi-body">
                        <span class="seleccion-dashboard-page__kpi-label">Total exámenes</span>
                        <span class="seleccion-dashboard-page__kpi-value" x-text="kpis.total_examenes">0</span>
                        <span class="seleccion-dashboard-page__kpi-hint">En el rango filtrado</span>
                    </span>
                    <span class="seleccion-dashboard-page__kpi-go" aria-hidden="true">
                        <x-lucide-arrow-up-right width="16" height="16" />
                    </span>
                </button>

                <button
                    type="button"
                    class="card seleccion-dashboard-page__kpi seleccion-dashboard-page__kpi--mes"
                    title="Ver ingresos del mes en curso"
                    x-on:click="goToIngresosMes()"
                >
                    <span class="seleccion-dashboard-page__kpi-icon" aria-hidden="true">
                        <x-lucide-calendar-days width="20" height="20" />
                    </span>
                    <span class="seleccion-dashboard-page__kpi-body">
                        <span class="seleccion-dashboard-page__kpi-label">Ingresos del mes</span>
                        <span class="seleccion-dashboard-page__kpi-value" x-text="kpis.ingresos_del_mes">0</span>
                        <span class="seleccion-dashboard-page__kpi-hint">Mes en curso</span>
                    </span>
                    <span class="seleccion-dashboard-page__kpi-go" aria-hidden="true">
                        <x-lucide-arrow-up-right width="16" height="16" />
                    </span>
                </button>

                <button
                    type="button"
                    class="card seleccion-dashboard-page__kpi seleccion-dashboard-page__kpi--proceso"
                    title="Ver exámenes con solicitud en proceso"
                    x-on:click="goToExamenesEnProceso()"
                >
                    <span class="seleccion-dashboard-page__kpi-icon" aria-hidden="true">
                        <x-lucide-hourglass width="20" height="20" />
                    </span>
                    <span class="seleccion-dashboard-page__kpi-body">
                        <span class="seleccion-dashboard-page__kpi-label">Exámenes en proceso</span>
                        <span class="seleccion-dashboard-page__kpi-value" x-text="kpis.examenes_en_proceso">0</span>
                        <span class="seleccion-dashboard-page__kpi-hint">Solicitud EN PROCESO</span>
                    </span>
                    <span class="seleccion-dashboard-page__kpi-go" aria-hidden="true">
                        <x-lucide-arrow-up-right width="16" height="16" />
                    </span>
                </button>
            </div>

            {{-- Fila 1: estado de solicitud (donut) + rankings; fila 2: tendencia --}}
            <div class="seleccion-dashboard-page__charts">
                <div class="seleccion-dashboard-page__charts-row seleccion-dashboard-page__charts-row--top">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Estado de la solicitud</h3>
                        </div>
                        <div class="panel__body">
                            <div id="seleccion-chart-solicitud" class="seleccion-dashboard-page__chart"></div>
                        </div>
                    </div>
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Top clientes por registros</h3>
                        </div>
                        <div class="panel__body">
                            <div id="seleccion-chart-cliente" class="seleccion-dashboard-page__chart seleccion-dashboard-page__chart--ranking"></div>
                        </div>
                    </div>
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Top responsables por registros</h3>
                        </div>
                        <div class="panel__body">
                            <div id="seleccion-chart-responsable" class="seleccion-dashboard-page__chart seleccion-dashboard-page__chart--ranking"></div>
                        </div>
                    </div>
                </div>
                <div class="seleccion-dashboard-page__charts-row seleccion-dashboard-page__charts-row--bottom">
                    <div class="panel">
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
    </div>

    @vite(['resources/js/seleccion-dashboard-charts.js'])

    @push('scripts')
        <script>
            function seleccionDashboardPage(config) {
                const emptyFilters = {
                    date_from: '',
                    date_to: '',
                    commercial_client_id: '',
                    responsable_user_id: '',
                };

                return {
                    metricsUrl: config.metricsUrl,
                    ingresosUrl: config.ingresosUrl,
                    examenesUrl: config.examenesUrl,
                    solicitudEnProcesoCode: config.solicitudEnProcesoCode || 'EN_PROCESO',
                    // Fecha de hoy según el servidor: mantiene el default «Mes actual» alineado.
                    todayIso: config.today || '',
                    filters: { ...emptyFilters, ...(config.filters || {}) },
                    kpis: { ...(config.initial?.kpis || {}) },
                    loading: false,
                    error: '',
                    updatedAt: '',
                    refreshTimer: null,
                    _lastCharts: config.initial?.charts || null,
                    _chartsReadyTimer: null,
                    init() {
                        this.updatedAt = this.nowLabel();
                        // Vite carga ApexCharts async: sin espera el primer paint queda vacío.
                        this.waitForChartsReady(() => this.paintCharts());
                    },
                    nowLabel() {
                        return new Date().toLocaleTimeString('es-CO', {
                            hour: '2-digit',
                            minute: '2-digit',
                        });
                    },
                    waitForChartsReady(onReady) {
                        const run = () => {
                            if (typeof window.renderSeleccionDashboardCharts !== 'function') {
                                return false;
                            }
                            onReady();
                            return true;
                        };

                        if (run()) {
                            return;
                        }

                        const onReadyEvent = () => run();
                        window.addEventListener('seleccion-dashboard-charts-ready', onReadyEvent, { once: true });
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
                        if (typeof window.renderSeleccionDashboardCharts !== 'function') {
                            return;
                        }
                        window.renderSeleccionDashboardCharts(this._lastCharts);
                    },
                    onSelectChange(key, event) {
                        let value = event?.detail?.value;
                        if (value === undefined || value === null) {
                            value = event?.target?.value ?? '';
                        }
                        this.filters[key] = value === null || value === undefined ? '' : String(value);
                        this.scheduleRefresh();
                    },
                    /** Hoy en fecha local; usa la del servidor para no desalinear presets. */
                    todayDate() {
                        const raw = String(this.todayIso || '');
                        if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
                            const [year, month, day] = raw.split('-').map(Number);

                            return new Date(year, month - 1, day);
                        }

                        return new Date();
                    },
                    /** Rango de fechas (ISO local) para cada preset del dashboard. */
                    presetRange(key) {
                        const iso = (date) => {
                            const year = date.getFullYear();
                            const month = String(date.getMonth() + 1).padStart(2, '0');
                            const day = String(date.getDate()).padStart(2, '0');

                            return `${year}-${month}-${day}`;
                        };
                        const today = this.todayDate();

                        if (key === 'mes') {
                            return {
                                from: iso(new Date(today.getFullYear(), today.getMonth(), 1)),
                                to: iso(today),
                            };
                        }

                        if (key === '30d') {
                            const start = new Date(today);
                            start.setDate(start.getDate() - 29);

                            return { from: iso(start), to: iso(today) };
                        }

                        if (key === 'anio') {
                            return {
                                from: iso(new Date(today.getFullYear(), 0, 1)),
                                to: iso(today),
                            };
                        }

                        return { from: '', to: '' };
                    },
                    isPresetActive(key) {
                        const range = this.presetRange(key);

                        return this.filters.date_from === range.from
                            && this.filters.date_to === range.to;
                    },
                    applyPreset(key) {
                        const range = this.presetRange(key);
                        this.filters.date_from = range.from;
                        this.filters.date_to = range.to;
                        this.scheduleRefresh();
                    },
                    clearFilters() {
                        this.filters = { ...emptyFilters };
                        this.setSelectValue('dash_commercial_client_id', '');
                        this.setSelectValue('dash_responsable_user_id', '');
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
                        const normalized = value === null || value === undefined ? '' : String(value);
                        const data = this.searchableSelectData(inputId);
                        if (data) {
                            data.value = normalized;
                            data.syncLabel?.();
                        }
                        const hidden = document.getElementById(inputId);
                        if (hidden) {
                            hidden.value = normalized;
                        }
                    },
                    /** Query string con los filtros vigentes (+ extras que los pisan). */
                    filterParams(extra = {}) {
                        const params = new URLSearchParams();
                        Object.entries({ ...this.filters, ...extra }).forEach(([key, value]) => {
                            if (value === null || value === undefined || value === '') {
                                return;
                            }
                            params.set(key, String(value));
                        });

                        return params;
                    },
                    goTo(url, extra = {}) {
                        if (!url) {
                            return;
                        }
                        const query = this.filterParams(extra).toString();
                        window.location.href = query ? `${url}?${query}` : url;
                    },
                    goToIngresos() {
                        this.goTo(this.ingresosUrl);
                    },
                    goToIngresosMes() {
                        const range = this.presetRange('mes');
                        this.goTo(this.ingresosUrl, {
                            date_from: range.from,
                            date_to: range.to,
                        });
                    },
                    goToExamenes(extra = {}) {
                        this.goTo(this.examenesUrl, extra);
                    },
                    goToExamenesEnProceso() {
                        this.goToExamenes({
                            solicitud_status_code: this.solicitudEnProcesoCode,
                        });
                    },
                    scheduleRefresh() {
                        clearTimeout(this.refreshTimer);
                        this.refreshTimer = setTimeout(() => this.refresh(), 300);
                    },
                    async refresh() {
                        this.loading = true;
                        this.error = '';
                        try {
                            const params = this.filterParams();
                            const res = await fetch(`${this.metricsUrl}?${params.toString()}`, {
                                headers: { Accept: 'application/json' },
                                credentials: 'same-origin',
                            });
                            if (!res.ok) {
                                throw new Error('No se pudieron actualizar los indicadores.');
                            }
                            const payload = await res.json();
                            this.kpis = payload.kpis || this.kpis;
                            this._lastCharts = payload.charts || {};
                            this.updatedAt = this.nowLabel();
                            this.paintCharts();
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
