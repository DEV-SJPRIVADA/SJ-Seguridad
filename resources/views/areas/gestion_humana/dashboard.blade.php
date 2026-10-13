<x-app-layout>
    <x-slot name="header">
        <div class="app-container gh-area-dashboard-page__header">
            <h2 class="gh-area-dashboard-page__title">Dashboard de Gestión Humana – Informe Mensual</h2>
        </div>
    </x-slot>

    {{-- Informe mensual GH: filtros searchable-select + KPIs + ApexCharts vía Alpine --}}
    <div
        class="page-section cursos-dashboard-page formacion-dashboard-page gh-area-dashboard-page"
        x-data="ghAreaDashboardPage(@js([
            'metricsUrl' => $metricsUrl,
            'conceptosUrl' => $conceptosUrl,
            'conceptosStoreUrl' => $conceptosStoreUrl,
            'initial' => $initialPayload,
            'filters' => $filters,
        ]))"
        x-init="init()"
        x-on:gh-area-conceptos-changed.window="scheduleRefresh()"
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
                                placeholder="Seleccionar mes"
                                :allow-clear="false"
                                x-on:change="onSelectChange('mes', $event)"
                            />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="dash_ciudad">Ciudad</label>
                            <x-searchable-select
                                id="dash_ciudad"
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
                            <label class="form-label" for="dash_cargo">Cargo</label>
                            <x-searchable-select
                                id="dash_cargo"
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
                            <label class="form-label" for="dash_cliente">Cliente</label>
                            <x-searchable-select
                                id="dash_cliente"
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
                            <button
                                type="button"
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                title="Conceptos / áreas"
                                aria-label="Conceptos / áreas"
                                x-on:click="openConceptosModal()"
                            >
                                <x-lucide-layers width="18" height="18" aria-hidden="true" />
                            </button>
                            <span class="panel-text" x-show="loading" x-cloak>Actualizando…</span>
                            <span class="panel-text formacion-dashboard-page__error" x-show="!loading && error" x-text="error" x-cloak></span>
                        </div>
                    </div>
                    <p class="panel-text" style="margin:0.35rem 0 0; font-size:0.8rem;" x-text="meta.filters_parciales_nota"></p>
                </div>
            </div>

            {{-- 10 KPIs en una sola fila (portátil / monitor) --}}
            <div class="cursos-dashboard-page__kpi-grid gh-area-dashboard-page__kpi-grid">
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#0f172a;">
                    <p class="cursos-dashboard-page__kpi-label">Personal Activo</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="kpis.personal_activo">0</p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#15803d;">
                    <p class="cursos-dashboard-page__kpi-label">Ingresos del Mes</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="kpis.ingresos_mes">0</p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#be123c;">
                    <p class="cursos-dashboard-page__kpi-label">Desvinculaciones</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="kpis.desvinculaciones_mes">0</p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#ca8a04;">
                    <p class="cursos-dashboard-page__kpi-label">Rotación Mensual %</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="kpis.rotacion_display">—</p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#0369a1;">
                    <p class="cursos-dashboard-page__kpi-label">Personal Operativo</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="kpis.personal_operativo">0</p>
                    <p class="formacion-dashboard-page__kpi-hint">
                        <span x-text="kpis.personal_operativo_pct"></span>% del total
                    </p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#15803d;">
                    <p class="cursos-dashboard-page__kpi-label">Acreditaciones</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="kpis.acreditaciones">0</p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#0ea5e9;">
                    <p class="cursos-dashboard-page__kpi-label">Psicofísicos</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="kpis.psicofisicos">0</p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#7c3aed;">
                    <p class="cursos-dashboard-page__kpi-label">Cliente Interno</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="kpis.cliente_interno">0</p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#0d9488;">
                    <p class="cursos-dashboard-page__kpi-label">Formación</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="kpis.formacion">0</p>
                </div>
                <div class="card cursos-dashboard-page__kpi" style="border-left-color:#64748b;">
                    <p class="cursos-dashboard-page__kpi-label">Archivo</p>
                    <p class="cursos-dashboard-page__kpi-value" x-text="kpis.archivo">0</p>
                </div>
            </div>

            {{-- Fila 1: 3 series temporales · Fila 2: 4 estados/distribuciones --}}
            <div class="gh-area-dashboard-page__charts">
                <div class="gh-area-dashboard-page__charts-row gh-area-dashboard-page__charts-row--top">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Evolución de Personal</h3>
                        </div>
                        <div class="panel__body">
                            <div id="gh-area-chart-evolucion-personal" class="cursos-dashboard-page__chart"></div>
                        </div>
                    </div>
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Ingreso Vs Desvinculaciones</h3>
                        </div>
                        <div class="panel__body">
                            <div id="gh-area-chart-ingresos-vs-desvinculaciones" class="cursos-dashboard-page__chart"></div>
                        </div>
                    </div>
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Evolución Formación</h3>
                        </div>
                        <div class="panel__body">
                            <div id="gh-area-chart-evolucion-formacion" class="cursos-dashboard-page__chart"></div>
                        </div>
                    </div>
                </div>
                <div class="gh-area-dashboard-page__charts-row gh-area-dashboard-page__charts-row--bottom">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Personal por concepto / área</h3>
                        </div>
                        <div class="panel__body">
                            <div id="gh-area-chart-personal-por-concepto" class="cursos-dashboard-page__chart gh-area-dashboard-page__chart--concepto"></div>
                        </div>
                    </div>
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Estado Psicofísicos</h3>
                        </div>
                        <div class="panel__body">
                            <div id="gh-area-chart-estado-psicofisicos" class="cursos-dashboard-page__chart"></div>
                        </div>
                    </div>
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Estado Acreditación</h3>
                        </div>
                        <div class="panel__body">
                            <div id="gh-area-chart-estado-acreditacion" class="cursos-dashboard-page__chart"></div>
                        </div>
                    </div>
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Consultas Archivo</h3>
                        </div>
                        <div class="panel__body">
                            <div id="gh-area-chart-consultas-archivo" class="cursos-dashboard-page__chart"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal: conceptos/áreas y asignación de cargos (chrome alineado a Generar cartas) --}}
        <x-modal name="gh-area-conceptos" maxWidth="3xl" focusable>
            <div
                class="modal-card gh-area-conceptos-modal"
                x-data="ghAreaConceptosModal(@js([
                    'catalogUrl' => $conceptosUrl,
                    'storeUrl' => $conceptosStoreUrl,
                ]))"
                x-on:open-modal.window="if ($event.detail === 'gh-area-conceptos') loadCatalog()"
            >
                <div class="gh-area-conceptos-modal__header">
                    <div class="gh-area-conceptos-modal__heading">
                        <span class="gh-area-conceptos-modal__heading-icon" aria-hidden="true">
                            <x-lucide-layers width="18" height="18" />
                        </span>
                        <div class="gh-area-conceptos-modal__heading-copy">
                            <div class="gh-area-conceptos-modal__title-row">
                                <h3 class="gh-area-conceptos-modal__title">Conceptos / áreas del personal</h3>
                                <span
                                    class="gh-area-conceptos-modal__badge"
                                    x-show="conceptos.length > 0"
                                    x-cloak
                                    x-text="conceptos.length"
                                    title="Conceptos definidos"
                                ></span>
                            </div>
                            <p class="gh-area-conceptos-modal__lead">
                                Agrupa cargos de Ficha para el gráfico «Personal por concepto / área».
                                Sin concepto cuentan en <strong>OTROS</strong>.
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="gh-area-conceptos-modal__close"
                        title="Cerrar"
                        aria-label="Cerrar"
                        x-on:click="$dispatch('close-modal', 'gh-area-conceptos')"
                    >
                        <x-lucide-x width="18" height="18" aria-hidden="true" />
                    </button>
                </div>

                <div class="gh-area-conceptos-modal__scroll" role="region" aria-label="Catálogo de conceptos y cargos">
                    {{-- Paso 1: crear --}}
                    <section class="gh-area-conceptos-modal__section">
                        <div class="gh-area-conceptos-modal__section-head">
                            <span class="gh-area-conceptos-modal__step" aria-hidden="true">1</span>
                            <div>
                                <h4 class="gh-area-conceptos-modal__section-title">Nuevo concepto</h4>
                                <p class="gh-area-conceptos-modal__section-hint">Nombre corto del área o agrupación (ej. ADMINISTRACIÓN).</p>
                            </div>
                        </div>
                        <div class="gh-area-conceptos-modal__toolbar">
                            <div class="form-field gh-area-conceptos-modal__field-grow">
                                <label class="form-label" for="gh_concepto_nuevo">Nombre</label>
                                <input
                                    id="gh_concepto_nuevo"
                                    type="text"
                                    class="form-input"
                                    maxlength="120"
                                    placeholder="Ej. ADMINISTRACIÓN"
                                    x-model="newConceptoName"
                                    x-on:keydown.enter.prevent="createConcepto()"
                                />
                            </div>
                            <div class="form-field gh-area-conceptos-modal__field-action">
                                <button
                                    type="button"
                                    class="btn btn--primary btn--sm"
                                    x-on:click="createConcepto()"
                                    x-bind:disabled="saving || !String(newConceptoName || '').trim()"
                                >
                                    <x-lucide-plus width="16" height="16" aria-hidden="true" />
                                    Crear
                                </button>
                            </div>
                        </div>
                    </section>

                    {{-- Paso 2: asignar --}}
                    <section class="gh-area-conceptos-modal__section">
                        <div class="gh-area-conceptos-modal__section-head">
                            <span class="gh-area-conceptos-modal__step" aria-hidden="true">2</span>
                            <div class="gh-area-conceptos-modal__section-copy">
                                <h4 class="gh-area-conceptos-modal__section-title">Asignar cargo</h4>
                                <p class="gh-area-conceptos-modal__section-hint">
                                    Elija un cargo sin asignar o escriba el nombre exacto como aparece en Ficha.
                                </p>
                            </div>
                        </div>
                        <div class="gh-area-conceptos-modal__toolbar gh-area-conceptos-modal__toolbar--assign">
                            <div class="form-field gh-area-conceptos-modal__field-concepto">
                                <label class="form-label" for="gh_assign_concepto">Concepto</label>
                                <x-searchable-select
                                    id="gh_assign_concepto"
                                    name="assign_concepto"
                                    :options="[]"
                                    value=""
                                    placeholder="Seleccionar concepto…"
                                    search-placeholder="Buscar concepto…"
                                    :allow-clear="false"
                                    x-on:change="onAssignConceptoChange($event)"
                                />
                            </div>
                            <div class="form-field gh-area-conceptos-modal__field-cargo">
                                <label class="form-label" for="gh_assign_cargo">Cargo sin asignar</label>
                                <x-searchable-select
                                    id="gh_assign_cargo"
                                    name="assign_cargo"
                                    :options="[]"
                                    value=""
                                    placeholder="Seleccionar cargo…"
                                    search-placeholder="Buscar cargo…"
                                    :allow-clear="true"
                                    x-on:change="onAssignCargoSelectChange($event)"
                                />
                                <input
                                    type="text"
                                    class="form-input gh-area-conceptos-modal__cargo-manual"
                                    placeholder="O escriba un cargo nuevo (nombre exacto)…"
                                    x-model="assignCargoName"
                                    autocomplete="off"
                                    aria-label="Cargo manual"
                                />
                            </div>
                            <div class="form-field gh-area-conceptos-modal__field-action">
                                <button
                                    type="button"
                                    class="btn btn--secondary btn--sm"
                                    x-on:click="assignCargo()"
                                    x-bind:disabled="saving"
                                >
                                    <x-lucide-link width="16" height="16" aria-hidden="true" />
                                    Asignar
                                </button>
                            </div>
                        </div>
                    </section>

                    <template x-if="modalError">
                        <div class="alert alert--danger gh-area-conceptos-modal__alert" x-text="modalError"></div>
                    </template>

                    <p class="gh-area-conceptos-modal__state gh-area-conceptos-modal__state--loading" x-show="loadingCatalog" x-cloak>
                        <x-lucide-loader-2 width="16" height="16" class="animate-spin" aria-hidden="true" />
                        <span>Cargando catálogo…</span>
                    </p>

                    {{-- Paso 3: listado --}}
                    <section class="gh-area-conceptos-modal__section" x-show="!loadingCatalog" x-cloak>
                        <div class="gh-area-conceptos-modal__section-head">
                            <span class="gh-area-conceptos-modal__step" aria-hidden="true">3</span>
                            <div class="gh-area-conceptos-modal__section-copy">
                                <h4 class="gh-area-conceptos-modal__section-title">Catálogo</h4>
                                <p class="gh-area-conceptos-modal__section-hint">
                                    Renombre, quite cargos o elimine conceptos. Los cambios se guardan al salir del campo.
                                </p>
                            </div>
                            <span
                                class="gh-area-conceptos-modal__section-meta"
                                x-show="cargosSinAsignar.length"
                                x-cloak
                                x-text="cargosSinAsignar.length + ' sin asignar'"
                            ></span>
                        </div>

                        <p class="gh-area-conceptos-modal__state" x-show="!conceptos.length" x-cloak>
                            Aún no hay conceptos. Cree el primero en el paso 1.
                        </p>

                        <div class="gh-area-conceptos-modal__list" x-show="conceptos.length" x-cloak>
                            <template x-for="c in conceptos" :key="c.id">
                                <article class="gh-area-conceptos-modal__card">
                                    <div class="gh-area-conceptos-modal__card-head">
                                        <div class="gh-area-conceptos-modal__card-title-wrap">
                                            <span class="gh-area-conceptos-modal__card-icon" aria-hidden="true">
                                                <x-lucide-folder width="14" height="14" />
                                            </span>
                                            <input
                                                type="text"
                                                class="form-input gh-area-conceptos-modal__card-name"
                                                x-model="c.name"
                                                x-on:change="renameConcepto(c)"
                                                maxlength="120"
                                                aria-label="Nombre del concepto"
                                            />
                                            <span
                                                class="gh-area-conceptos-modal__chip-count"
                                                x-text="(c.cargos?.length || 0) + ' cargo' + ((c.cargos?.length || 0) === 1 ? '' : 's')"
                                            ></span>
                                        </div>
                                        <button
                                            type="button"
                                            class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger"
                                            title="Eliminar concepto"
                                            aria-label="Eliminar concepto"
                                            x-on:click="deleteConcepto(c)"
                                        >
                                            <x-lucide-trash-2 width="16" height="16" aria-hidden="true" />
                                        </button>
                                    </div>
                                    <ul class="gh-area-conceptos-modal__cargos" x-show="c.cargos.length">
                                        <template x-for="cargo in c.cargos" :key="cargo.id">
                                            <li>
                                                <span class="gh-area-conceptos-modal__cargo-label" x-text="cargo.position_label"></span>
                                                <button
                                                    type="button"
                                                    class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger"
                                                    title="Quitar cargo"
                                                    aria-label="Quitar cargo"
                                                    x-on:click="unassignCargo(cargo)"
                                                >
                                                    <x-lucide-x width="14" height="14" aria-hidden="true" />
                                                </button>
                                            </li>
                                        </template>
                                    </ul>
                                    <p class="gh-area-conceptos-modal__empty-cargos" x-show="!c.cargos.length">
                                        Sin cargos asignados.
                                    </p>
                                </article>
                            </template>
                        </div>

                        <div class="gh-area-conceptos-modal__unassigned" x-show="cargosSinAsignar.length" x-cloak>
                            <div class="gh-area-conceptos-modal__unassigned-head">
                                <span class="gh-area-conceptos-modal__unassigned-icon" aria-hidden="true">
                                    <x-lucide-circle-alert width="16" height="16" />
                                </span>
                                <div>
                                    <p class="gh-area-conceptos-modal__unassigned-title">Cargos en Ficha sin concepto</p>
                                    <p class="gh-area-conceptos-modal__unassigned-hint">Aparecerán en OTROS hasta asignarlos.</p>
                                </div>
                            </div>
                            <div class="gh-area-conceptos-modal__unassigned-chips" role="list">
                                <template x-for="opt in cargosSinAsignar" :key="opt.value">
                                    <span class="gh-area-conceptos-modal__unassigned-chip" role="listitem" x-text="opt.label"></span>
                                </template>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="gh-area-conceptos-modal__actions">
                    <p class="gh-area-conceptos-modal__actions-hint gh-area-conceptos-modal__actions-hint--muted">
                        Al cerrar se refresca el gráfico del informe.
                    </p>
                    <div class="gh-area-conceptos-modal__actions-btns">
                        <button
                            type="button"
                            class="btn btn--primary btn--sm"
                            x-on:click="$dispatch('close-modal', 'gh-area-conceptos'); $dispatch('gh-area-conceptos-changed')"
                        >
                            Cerrar y actualizar gráfico
                        </button>
                    </div>
                </div>
            </div>
        </x-modal>
    </div>

    @vite(['resources/js/gh-area-dashboard-charts.js'])

    @push('scripts')
        <script>
            function ghAreaDashboardPage(config) {
                const defaultKpis = {
                    personal_activo: 0,
                    ingresos_mes: 0,
                    desvinculaciones_mes: 0,
                    rotacion_mensual_pct: 0,
                    rotacion_display: '—',
                    personal_operativo: 0,
                    personal_operativo_pct: 0,
                    acreditaciones: 0,
                    psicofisicos: 0,
                    cliente_interno: 0,
                    formacion: 0,
                    archivo: 0,
                };
                const defaultMeta = {
                    activos_mes_actual: 0,
                    activos_mes_anterior: 0,
                    eom_date: '',
                    filters_parciales_nota: 'Cliente interno, Formación y Archivo solo filtran por año/mes.',
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
                        // Vite carga ApexCharts async: sin wait el primer paint queda vacío (KPIs sí, charts no).
                        this.waitForChartsReady(() => this.paintCharts());
                    },
                    openConceptosModal() {
                        this.$dispatch('open-modal', 'gh-area-conceptos');
                    },
                    waitForChartsReady(onReady) {
                        const run = () => {
                            if (typeof window.renderGhAreaDashboardCharts !== 'function') {
                                return false;
                            }
                            onReady();
                            return true;
                        };

                        if (run()) {
                            return;
                        }

                        const onReadyEvent = () => run();
                        window.addEventListener('gh-area-dashboard-charts-ready', onReadyEvent, { once: true });
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
                        if (typeof window.renderGhAreaDashboardCharts !== 'function') {
                            return;
                        }
                        window.renderGhAreaDashboardCharts(this._lastCharts);
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
                        const anio = String(this.filters.anio || '');
                        const mes = String(this.filters.mes || '');
                        this.filters = {
                            anio: anio,
                            mes: mes,
                            ciudad: '',
                            cargo: '',
                            cliente: '',
                        };
                        this.setSelectValue('dash_ciudad', '');
                        this.setSelectValue('dash_cargo', '');
                        this.setSelectValue('dash_cliente', '');
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
                    scheduleRefresh() {
                        clearTimeout(this.refreshTimer);
                        this.refreshTimer = setTimeout(() => this.refresh(), 300);
                    },
                    async refresh() {
                        this.loading = true;
                        this.error = '';
                        try {
                            const params = new URLSearchParams();
                            ['anio', 'mes', 'ciudad', 'cargo', 'cliente'].forEach((key) => {
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
                            this.kpis = { ...defaultKpis, ...(payload.kpis || {}) };
                            this.meta = { ...defaultMeta, ...(payload.meta || {}) };
                            this.filters.anio = String(payload.filters?.anio ?? this.filters.anio);
                            this.filters.mes = String(payload.filters?.mes ?? this.filters.mes);
                            this.filters.ciudad = String(payload.filters?.ciudad ?? '');
                            this.filters.cargo = String(payload.filters?.cargo ?? '');
                            this.filters.cliente = String(payload.filters?.cliente ?? '');
                            this._lastCharts = payload.charts || {};
                            this.paintCharts();
                        } catch (e) {
                            this.error = e?.message || 'Error al actualizar.';
                        } finally {
                            this.loading = false;
                        }
                    },
                };
            }

            function ghAreaConceptosModal(config) {
                const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                return {
                    catalogUrl: config.catalogUrl,
                    storeUrl: config.storeUrl,
                    conceptos: [],
                    cargosSinAsignar: [],
                    newConceptoName: '',
                    assignConceptoId: '',
                    assignCargoName: '',
                    loadingCatalog: false,
                    saving: false,
                    modalError: '',
                    async loadCatalog() {
                        this.loadingCatalog = true;
                        this.modalError = '';
                        try {
                            const res = await fetch(this.catalogUrl, {
                                headers: { Accept: 'application/json' },
                                credentials: 'same-origin',
                            });
                            if (!res.ok) {
                                throw new Error('No se pudo cargar el catálogo de conceptos.');
                            }
                            const payload = await res.json();
                            this.applyCatalog(payload);
                        } catch (e) {
                            this.modalError = e?.message || 'Error al cargar.';
                        } finally {
                            this.loadingCatalog = false;
                        }
                    },
                    applyCatalog(payload) {
                        this.conceptos = Array.isArray(payload?.conceptos) ? payload.conceptos : [];
                        this.cargosSinAsignar = Array.isArray(payload?.cargos_sin_asignar) ? payload.cargos_sin_asignar : [];
                        this.$nextTick(() => this.syncAssignSelects());
                    },
                    searchableSelectData(inputId) {
                        const hidden = document.getElementById(inputId);
                        const wrap = hidden?.closest('.searchable-select-wrap');
                        if (! wrap || ! window.Alpine || typeof window.Alpine.$data !== 'function') {
                            return null;
                        }
                        return window.Alpine.$data(wrap);
                    },
                    syncAssignSelects() {
                        const conceptoData = this.searchableSelectData('gh_assign_concepto');
                        if (conceptoData) {
                            conceptoData.options = [
                                { value: '', label: 'Seleccionar concepto…' },
                                ...this.conceptos.map((c) => ({ value: String(c.id), label: c.name })),
                            ];
                            if (! this.conceptos.some((c) => String(c.id) === String(this.assignConceptoId))) {
                                this.assignConceptoId = '';
                                conceptoData.value = '';
                                conceptoData.syncLabel?.();
                            }
                        }

                        const cargoData = this.searchableSelectData('gh_assign_cargo');
                        if (cargoData) {
                            cargoData.options = [
                                { value: '', label: 'Seleccionar cargo sin asignar…' },
                                ...this.cargosSinAsignar,
                            ];
                            cargoData.value = '';
                            cargoData.syncLabel?.();
                        }
                    },
                    onAssignConceptoChange(event) {
                        let value = event?.detail?.value;
                        if (value === undefined || value === null) {
                            value = event?.target?.value ?? '';
                        }
                        this.assignConceptoId = value === null || value === undefined ? '' : String(value);
                    },
                    onAssignCargoSelectChange(event) {
                        let value = event?.detail?.value;
                        if (value === undefined || value === null) {
                            value = event?.target?.value ?? '';
                        }
                        if (value) {
                            this.assignCargoName = String(value);
                        }
                    },
                    async api(url, method, body) {
                        const res = await fetch(url, {
                            method,
                            headers: {
                                Accept: 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf(),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            body: body ? JSON.stringify(body) : undefined,
                        });
                        const payload = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            const msg = payload?.message
                                || payload?.errors?.name?.[0]
                                || payload?.errors?.position_name?.[0]
                                || 'No se pudo completar la acción.';
                            throw new Error(msg);
                        }
                        return payload;
                    },
                    async createConcepto() {
                        this.saving = true;
                        this.modalError = '';
                        try {
                            const payload = await this.api(this.storeUrl, 'POST', { name: this.newConceptoName });
                            this.newConceptoName = '';
                            this.applyCatalog(payload.catalog);
                        } catch (e) {
                            this.modalError = e?.message || 'Error al crear.';
                        } finally {
                            this.saving = false;
                        }
                    },
                    async renameConcepto(concepto) {
                        this.saving = true;
                        this.modalError = '';
                        try {
                            const url = `${this.storeUrl}/${concepto.id}`;
                            const payload = await this.api(url, 'PATCH', { name: concepto.name });
                            this.applyCatalog(payload.catalog);
                        } catch (e) {
                            this.modalError = e?.message || 'Error al renombrar.';
                            await this.loadCatalog();
                        } finally {
                            this.saving = false;
                        }
                    },
                    async deleteConcepto(concepto) {
                        if (! window.confirm(`¿Eliminar el concepto «${concepto.name}»? Sus cargos pasarán a OTROS.`)) {
                            return;
                        }
                        this.saving = true;
                        this.modalError = '';
                        try {
                            const payload = await this.api(`${this.storeUrl}/${concepto.id}`, 'DELETE');
                            this.applyCatalog(payload.catalog);
                        } catch (e) {
                            this.modalError = e?.message || 'Error al eliminar.';
                        } finally {
                            this.saving = false;
                        }
                    },
                    async assignCargo() {
                        const conceptoId = String(this.assignConceptoId || '');
                        const cargoName = String(this.assignCargoName || '').trim();
                        if (! conceptoId || ! cargoName) {
                            this.modalError = 'Seleccione concepto y cargo.';
                            return;
                        }
                        this.saving = true;
                        this.modalError = '';
                        try {
                            const payload = await this.api(`${this.storeUrl}/${conceptoId}/cargos`, 'POST', {
                                position_name: cargoName,
                            });
                            this.assignCargoName = '';
                            this.applyCatalog(payload.catalog);
                        } catch (e) {
                            this.modalError = e?.message || 'Error al asignar.';
                        } finally {
                            this.saving = false;
                        }
                    },
                    async unassignCargo(cargo) {
                        this.saving = true;
                        this.modalError = '';
                        try {
                            const base = this.storeUrl.replace(/\/conceptos$/, '/concepto-cargos');
                            const payload = await this.api(`${base}/${cargo.id}`, 'DELETE');
                            this.applyCatalog(payload.catalog);
                        } catch (e) {
                            this.modalError = e?.message || 'Error al quitar.';
                        } finally {
                            this.saving = false;
                        }
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
