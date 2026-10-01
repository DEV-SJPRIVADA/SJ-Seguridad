<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.formacion.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div
        class="page-section cursos-dashboard-page"
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
                        <div class="form-field cursos-dashboard-page__filter-meta">
                            <span class="panel-text" x-show="loading" x-cloak>Actualizando…</span>
                            <span class="panel-text" x-show="!loading && error" x-text="error" x-cloak></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="cursos-dashboard-page__kpi-grid">
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#0369a1;">
                    <p class="cursos-dashboard-page__kpi-label">Total formaciones</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="total"></p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#15803d;">
                    <p class="cursos-dashboard-page__kpi-label">Año</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="anio"></p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#92400e;">
                    <p class="cursos-dashboard-page__kpi-label">Categorías (top)</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="categoriaCount"></p>
                </div>
            </div>

            <div class="cursos-dashboard-page__charts">
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
                        <h3 class="panel-title">Distribución por categoría</h3>
                    </div>
                    <div class="panel__body">
                        <div id="formacion-chart-categoria" class="cursos-dashboard-page__chart"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @vite(['resources/js/formacion-dashboard-charts.js'])

    @push('scripts')
        <script>
            function formacionDashboardPage(config) {
                return {
                    metricsUrl: config.metricsUrl,
                    filters: { ...config.filters },
                    total: config.initial?.total ?? 0,
                    anio: config.initial?.anio ?? '',
                    categoriaCount: (config.initial?.por_categoria || []).length,
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
                            this.total = payload.total ?? 0;
                            this.anio = payload.anio ?? this.anio;
                            this.filters.anio = String(payload.anio ?? this.filters.anio);
                            this.categoriaCount = (payload.por_categoria || []).length;
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
