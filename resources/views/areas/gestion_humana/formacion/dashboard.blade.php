<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.formacion.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div
        class="page-section cursos-dashboard-page formacion-dashboard-page"
        x-data="formacionDashboardPage(@js([
            'metricsUrl' => $metricsUrl,
            'formacionesUrl' => $formacionesUrl,
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
                        <div class="form-field" x-show="mode === 'individual'" x-cloak>
                            <label class="form-label" for="dash_estado">Estado</label>
                            <x-searchable-select
                                id="dash_estado"
                                name="estado"
                                :options="$estadoOptions"
                                :value="$filters['estado']"
                                placeholder="Todos"
                                :allow-clear="true"
                                x-on:change="onSelectChange('estado', $event)"
                            />
                        </div>
                        <div class="form-field" x-show="mode === 'individual'" x-cloak>
                            <label class="form-label" for="dash_nombre_curso">Curso</label>
                            <x-searchable-select
                                id="dash_nombre_curso"
                                name="nombre_curso"
                                :options="$cursoOptions"
                                :value="$filters['nombre_curso']"
                                placeholder="Todos"
                                :allow-clear="true"
                                x-on:change="onSelectChange('nombre_curso', $event)"
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

            <div class="formacion-dashboard-page__mode-tabs" role="tablist" aria-label="Modo de indicadores">
                <button
                    type="button"
                    class="formacion-dashboard-page__mode-tab"
                    :class="{ 'is-active': mode === 'individual' }"
                    role="tab"
                    :aria-selected="(mode === 'individual').toString()"
                    x-on:click="setMode('individual')"
                >Por curso</button>
                <button
                    type="button"
                    class="formacion-dashboard-page__mode-tab"
                    :class="{ 'is-active': mode === 'persona' }"
                    role="tab"
                    :aria-selected="(mode === 'persona').toString()"
                    x-on:click="setMode('persona')"
                >Por persona (ciclo)</button>
            </div>

            <div class="cursos-dashboard-page__kpi-grid formacion-dashboard-page__kpi-grid" x-show="mode === 'individual'" x-cloak>
                <button
                    type="button"
                    class="card cursos-dashboard-page__kpi formacion-dashboard-page__kpi-link"
                    style="border-left-color:#0369a1;"
                    title="Ver registros en Formaciones"
                    x-on:click="goToFormaciones({})"
                >
                    <p class="cursos-dashboard-page__kpi-label">Total registros</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="total"></p>
                    <p class="formacion-dashboard-page__kpi-hint">Año <span x-text="anio"></span></p>
                </button>
                <button
                    type="button"
                    class="card cursos-dashboard-page__kpi formacion-dashboard-page__kpi-link"
                    style="border-left-color:#15803d;"
                    title="Ver aprobados en Formaciones"
                    x-on:click="goToFormaciones({ estado: 'aprobado' })"
                >
                    <p class="cursos-dashboard-page__kpi-label">Aprobados</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="porEstado.aprobado"></p>
                </button>
                <button
                    type="button"
                    class="card cursos-dashboard-page__kpi formacion-dashboard-page__kpi-link"
                    style="border-left-color:#be123c;"
                    title="Ver reprobados en Formaciones"
                    x-on:click="goToFormaciones({ estado: 'reprobado' })"
                >
                    <p class="cursos-dashboard-page__kpi-label">Reprobados</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="porEstado.reprobado"></p>
                </button>
                <button
                    type="button"
                    class="card cursos-dashboard-page__kpi formacion-dashboard-page__kpi-link"
                    style="border-left-color:#64748b;"
                    title="Ver no realizadas en Formaciones"
                    x-on:click="goToFormaciones({ estado: 'no_realizada' })"
                >
                    <p class="cursos-dashboard-page__kpi-label">No realizadas</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="porEstado.no_realizada"></p>
                </button>
            </div>

            <div class="cursos-dashboard-page__kpi-grid formacion-dashboard-page__kpi-grid" x-show="mode === 'persona'" x-cloak>
                <button
                    type="button"
                    class="card cursos-dashboard-page__kpi formacion-dashboard-page__kpi-link"
                    style="border-left-color:#0369a1;"
                    title="Ver personas del ciclo en Formaciones"
                    x-on:click="goToFormaciones({ ciclo: '' })"
                >
                    <p class="cursos-dashboard-page__kpi-label">Personas</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="ciclo.personas"></p>
                    <p class="formacion-dashboard-page__kpi-hint">
                        <span x-text="ciclo.cursos_ciclo"></span> curso(s) del ciclo
                    </p>
                </button>
                <button
                    type="button"
                    class="card cursos-dashboard-page__kpi formacion-dashboard-page__kpi-link"
                    style="border-left-color:#15803d;"
                    title="Ver personas que aprobaron todos"
                    x-on:click="goToFormaciones({ ciclo: 'aprobado' })"
                >
                    <p class="cursos-dashboard-page__kpi-label">Aprobaron todos</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="ciclo.aprobado"></p>
                </button>
                <button
                    type="button"
                    class="card cursos-dashboard-page__kpi formacion-dashboard-page__kpi-link"
                    style="border-left-color:#be123c;"
                    title="Ver personas reprobadas en ciclo"
                    x-on:click="goToFormaciones({ ciclo: 'reprobado' })"
                >
                    <p class="cursos-dashboard-page__kpi-label">Reprobados</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="ciclo.reprobado"></p>
                </button>
                <button
                    type="button"
                    class="card cursos-dashboard-page__kpi formacion-dashboard-page__kpi-link"
                    style="border-left-color:#ca8a04;"
                    title="Ver personas incompletas en ciclo"
                    x-on:click="goToFormaciones({ ciclo: 'incompleto' })"
                >
                    <p class="cursos-dashboard-page__kpi-label">Incompletos</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="ciclo.incompleto"></p>
                </button>
                <button
                    type="button"
                    class="card cursos-dashboard-page__kpi formacion-dashboard-page__kpi-link"
                    style="border-left-color:#64748b;"
                    title="Ver personas no realizadas en ciclo"
                    x-on:click="goToFormaciones({ ciclo: 'no_realizado' })"
                >
                    <p class="cursos-dashboard-page__kpi-label">No realizados</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="ciclo.no_realizado"></p>
                </button>
            </div>

            <div class="cursos-dashboard-page__charts formacion-dashboard-page__charts" x-show="mode === 'individual'" x-cloak>
                <div class="panel cursos-dashboard-page__chart-wide">
                    <div class="panel__header">
                        <h3 class="panel-title">Distribución por mes (<span x-text="anio"></span>)</h3>
                    </div>
                    <div class="panel__body">
                        <div id="formacion-chart-mes" class="cursos-dashboard-page__chart cursos-dashboard-page__chart--tall"></div>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel__header">
                        <h3 class="panel-title">Por estado (registro)</h3>
                    </div>
                    <div class="panel__body">
                        <div id="formacion-chart-estado" class="cursos-dashboard-page__chart"></div>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel__header">
                        <h3 class="panel-title">Top categorías</h3>
                    </div>
                    <div class="panel__body">
                        <div id="formacion-chart-categoria" class="cursos-dashboard-page__chart cursos-dashboard-page__chart--tall"></div>
                    </div>
                </div>
            </div>

            <div class="cursos-dashboard-page__charts formacion-dashboard-page__charts" x-show="mode === 'persona'" x-cloak>
                <div class="panel cursos-dashboard-page__chart-wide">
                    <div class="panel__header">
                        <h3 class="panel-title">Resultado por persona (ciclo)</h3>
                    </div>
                    <div class="panel__body">
                        <p class="formacion-dashboard-page__ciclo-hint panel-text">
                            Set = todos los cursos distintos del
                            <span x-text="filters.mes ? 'mes seleccionado' : 'año'"></span>.
                            Se toma la mejor nota por curso; si reprueba cualquiera → reprobado;
                            si falta alguno → incompleto; si no hizo ninguno → no realizado.
                        </p>
                        <div id="formacion-chart-ciclo" class="cursos-dashboard-page__chart cursos-dashboard-page__chart--tall"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @vite(['resources/js/formacion-dashboard-charts.js'])

    @push('scripts')
        <script>
            function formacionDashboardPage(config) {
                const defaultEstado = { aprobado: 0, reprobado: 0, no_realizada: 0 };
                const defaultCiclo = {
                    cursos_ciclo: 0,
                    personas: 0,
                    aprobado: 0,
                    reprobado: 0,
                    incompleto: 0,
                    no_realizado: 0,
                    charts: { labels: [], data: [] },
                };

                return {
                    metricsUrl: config.metricsUrl,
                    formacionesUrl: config.formacionesUrl,
                    mode: 'individual',
                    filters: {
                        anio: String(config.filters?.anio ?? ''),
                        mes: String(config.filters?.mes ?? ''),
                        estado: String(config.filters?.estado ?? ''),
                        nombre_curso: String(config.filters?.nombre_curso ?? ''),
                    },
                    total: config.initial?.total ?? 0,
                    anio: config.initial?.anio ?? '',
                    porEstado: { ...defaultEstado, ...(config.initial?.por_estado || {}) },
                    ciclo: { ...defaultCiclo, ...(config.initial?.ciclo || {}) },
                    loading: false,
                    error: '',
                    refreshTimer: null,
                    init() {
                        this.$nextTick(() => this.renderChartsForMode(config.initial));
                    },
                    goToFormaciones(overrides = {}) {
                        const params = new URLSearchParams();
                        const anio = String(overrides.anio ?? this.filters.anio ?? this.anio ?? '');
                        const mes = String(overrides.mes ?? this.filters.mes ?? '');
                        if (anio) {
                            params.set('anio', anio);
                        }
                        if (mes) {
                            params.set('mes', mes);
                        }

                        if (Object.prototype.hasOwnProperty.call(overrides, 'ciclo')) {
                            const ciclo = String(overrides.ciclo || '');
                            if (ciclo) {
                                params.set('ciclo', ciclo);
                            }
                        } else {
                            const curso = String(overrides.nombre_curso ?? this.filters.nombre_curso ?? '');
                            if (curso) {
                                params.set('nombre_curso', curso);
                            }
                            if (Object.prototype.hasOwnProperty.call(overrides, 'estado')) {
                                const estado = String(overrides.estado || '');
                                if (estado) {
                                    params.set('estado', estado);
                                }
                            }
                        }

                        const query = params.toString();
                        window.location.href = query
                            ? `${this.formacionesUrl}?${query}`
                            : this.formacionesUrl;
                    },
                    setMode(mode) {
                        this.mode = mode === 'persona' ? 'persona' : 'individual';
                        this.$nextTick(() => this.renderChartsForMode({
                            charts: {
                                por_mes: this._lastCharts?.por_mes,
                                por_categoria: this._lastCharts?.por_categoria,
                                por_estado: this._lastCharts?.por_estado,
                            },
                            ciclo: this.ciclo,
                        }));
                    },
                    renderChartsForMode(payload) {
                        this._lastCharts = payload?.charts || this._lastCharts || {};
                        if (this.mode === 'persona') {
                            window.renderFormacionCicloChart?.(payload?.ciclo?.charts || this.ciclo?.charts);
                            return;
                        }
                        window.renderFormacionDashboardCharts?.(payload?.charts || this._lastCharts);
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
                        this.filters = {
                            anio: anio,
                            mes: '',
                            estado: '',
                            nombre_curso: '',
                        };
                        this.setSelectValue('dash_mes', '');
                        this.setSelectValue('dash_estado', '');
                        this.setSelectValue('dash_nombre_curso', '');
                        this.scheduleRefresh();
                    },
                    searchableSelectData(inputId) {
                        const hidden = document.getElementById(inputId);
                        const wrap = hidden?.closest('.searchable-select-wrap');
                        if (! wrap || ! window.Alpine || typeof window.Alpine.$data !== 'function') {
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
                    updateCursoOptions(cursos) {
                        const data = this.searchableSelectData('dash_nombre_curso');
                        if (! data) {
                            return;
                        }
                        const list = Array.isArray(cursos) ? cursos : [];
                        const placeholder = data.placeholder || 'Todos';
                        const next = [
                            { value: '', label: placeholder },
                            ...list.map((opt) => ({
                                value: String(opt.value ?? ''),
                                label: String(opt.label ?? opt.value ?? ''),
                            })),
                        ];
                        data.options = next;
                        const current = String(this.filters.nombre_curso || '');
                        const stillValid = current === '' || list.some((opt) => String(opt.value) === current);
                        if (! stillValid) {
                            this.filters.nombre_curso = '';
                            data.value = '';
                            data.syncLabel?.();
                        } else {
                            data.value = current;
                            data.syncLabel?.();
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
                            const keys = this.mode === 'persona'
                                ? ['anio', 'mes']
                                : ['anio', 'mes', 'estado', 'nombre_curso'];
                            keys.forEach((key) => {
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
                            if (this.mode === 'individual') {
                                this.filters.estado = String(payload.filters?.estado ?? this.filters.estado ?? '');
                                this.filters.nombre_curso = String(payload.filters?.nombre_curso ?? '');
                                this.updateCursoOptions(payload.options?.cursos || []);
                                this.setSelectValue('dash_nombre_curso', this.filters.nombre_curso);
                            }
                            this.porEstado = { ...defaultEstado, ...(payload.por_estado || {}) };
                            this.ciclo = { ...defaultCiclo, ...(payload.ciclo || {}) };
                            this.$nextTick(() => this.renderChartsForMode(payload));
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
