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
            'canEditExportApo' => $canEditExportApo,
            'exportApoUrl' => route('gestion-humana.acreditaciones.export-apo'),
        ]))"
        x-init="init()"
    >
        <div class="app-container section-stack">
            <div class="cursos-dashboard-page__kpi-grid">
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
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#7c3aed;">
                    <p class="cursos-dashboard-page__kpi-label" x-text="labels.candidatos">Candidatos exportables</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="kpis.candidatos">0</p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#ea580c;">
                    <p class="cursos-dashboard-page__kpi-label" x-text="labels.con_novedad_blanda">Con novedad blanda</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="novedadDisplay()">0</p>
                </div>
            </div>

            <div class="panel" x-show="loading" x-cloak>
                <div class="panel__body panel__body--compact">
                    <p class="panel-text">Actualizando indicadores…</p>
                </div>
            </div>

            <div class="panel" x-show="!loading && error" x-cloak>
                <div class="panel__body panel__body--compact">
                    <p class="panel-text" x-text="error"></p>
                </div>
            </div>

            <div class="panel">
                <div class="panel__header panel-heading-row">
                    <div>
                        <h3 class="panel-title">Últimas corridas Export Apo</h3>
                        <p class="panel-text">Historial reciente de archivos SuperVigilancia generados</p>
                    </div>
                    <template x-if="canEditExportApo">
                        <a
                            :href="exportApoUrl"
                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                            title="Ir a Export Apo"
                            aria-label="Ir a Export Apo"
                        >
                            <x-lucide-download width="18" height="18" aria-hidden="true" />
                        </a>
                    </template>
                </div>
                <div class="panel__body req-manage-shell">
                    <div class="req-manage-table-scroll">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Seq</th>
                                    <th>Archivo</th>
                                    <th>Usuario</th>
                                    <th>Filas exportadas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="recentRuns.length === 0">
                                    <tr>
                                        <td colspan="5" class="panel-text">Aún no hay corridas registradas.</td>
                                    </tr>
                                </template>
                                <template x-for="run in recentRuns" :key="run.id">
                                    <tr>
                                        <td x-text="run.export_date"></td>
                                        <td x-text="String(run.seq).padStart(3, '0')"></td>
                                        <td x-text="run.file_name"></td>
                                        <td x-text="run.user_name"></td>
                                        <td x-text="run.rows_exported"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function acreditacionesDashboardPage(config) {
                return {
                    metricsUrl: config.metricsUrl,
                    canEditExportApo: !!config.canEditExportApo,
                    exportApoUrl: config.exportApoUrl || '',
                    kpis: { ...(config.initial?.kpis || {}) },
                    labels: { ...(config.initial?.labels || {}) },
                    recentRuns: Array.isArray(config.initial?.recent_runs) ? config.initial.recent_runs : [],
                    loading: false,
                    error: '',
                    init() {
                        // Payload inicial ya viene del servidor; metrics JSON disponible para refresh futuro.
                    },
                    novedadDisplay() {
                        const value = this.kpis.con_novedad_blanda;
                        return value === null || typeof value === 'undefined' ? '—' : value;
                    },
                    async refresh() {
                        if (!this.metricsUrl) {
                            return;
                        }

                        this.loading = true;
                        this.error = '';

                        try {
                            const response = await fetch(this.metricsUrl, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                            });

                            if (!response.ok) {
                                throw new Error('No se pudieron cargar los indicadores.');
                            }

                            const payload = await response.json();
                            this.kpis = { ...(payload.kpis || {}) };
                            this.labels = { ...(payload.labels || this.labels) };
                            this.recentRuns = Array.isArray(payload.recent_runs) ? payload.recent_runs : [];
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
