<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.formacion.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div
        class="page-section cursos-dashboard-page formacion-dashboard-page"
        x-data="formacionDashboardPage(@js([
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
                        <div class="form-field">
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
                        <div class="form-field">
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

            <div class="cursos-dashboard-page__kpi-grid formacion-dashboard-page__kpi-grid">
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#0369a1;">
                    <p class="cursos-dashboard-page__kpi-label">Total</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="total"></p>
                    <p class="formacion-dashboard-page__kpi-hint">Año <span x-text="anio"></span></p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#15803d;">
                    <p class="cursos-dashboard-page__kpi-label">Aprobados</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="porEstado.aprobado"></p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#be123c;">
                    <p class="cursos-dashboard-page__kpi-label">Reprobados</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="porEstado.reprobado"></p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#64748b;">
                    <p class="cursos-dashboard-page__kpi-label">No realizadas</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="porEstado.no_realizada"></p>
                </div>
            </div>

            <div class="cursos-dashboard-page__charts formacion-dashboard-page__charts">
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
                        <h3 class="panel-title">Por estado</h3>
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
        </div>
    </div>

    @vite(['resources/js/formacion-dashboard-charts.js'])

    @push('scripts')
        <script>
            function formacionDashboardPage(config) {
                const defaultEstado = { aprobado: 0, reprobado: 0, no_realizada: 0 };

                return {
                    metricsUrl: config.metricsUrl,
                    filters: {
                        anio: String(config.filters?.anio ?? ''),
                        mes: String(config.filters?.mes ?? ''),
                        estado: String(config.filters?.estado ?? ''),
                        nombre_curso: String(config.filters?.nombre_curso ?? ''),
                    },
                    total: config.initial?.total ?? 0,
                    anio: config.initial?.anio ?? '',
                    porEstado: { ...defaultEstado, ...(config.initial?.por_estado || {}) },
                    loading: false,
                    error: '',
                    refreshTimer: null,
                    init() {
                        if (typeof window.renderFormacionDashboardCharts === 'function') {
                            window.renderFormacionDashboardCharts(config.initial?.charts);
                        } else {
                            window.addEventListener('load', () => {
                                window.renderFormacionDashboardCharts?.(config.initial?.charts);
                            }, { once: true });
                        }
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
                            this.total = payload.total ?? 0;
                            this.anio = payload.anio ?? this.anio;
                            this.filters.anio = String(payload.filters?.anio ?? payload.anio ?? this.filters.anio);
                            this.filters.mes = String(payload.filters?.mes ?? this.filters.mes ?? '');
                            this.filters.estado = String(payload.filters?.estado ?? this.filters.estado ?? '');
                            this.filters.nombre_curso = String(payload.filters?.nombre_curso ?? '');
                            this.porEstado = { ...defaultEstado, ...(payload.por_estado || {}) };
                            this.updateCursoOptions(payload.options?.cursos || []);
                            this.setSelectValue('dash_nombre_curso', this.filters.nombre_curso);
                            window.renderFormacionDashboardCharts?.(payload.charts);
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
