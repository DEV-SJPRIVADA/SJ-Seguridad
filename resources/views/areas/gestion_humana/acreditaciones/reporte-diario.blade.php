<x-app-layout>
    @php
        $hasImportErrors = $errors->has('file_proceso')
            || $errors->has('file_acreditado')
            || $errors->has('fecha_reporte')
            || $errors->has('confirm_replace');
        $needsReplaceConfirm = $canEdit && $errors->has('confirm_replace');
        $showCargaModal = $canEdit && $hasImportErrors;
    @endphp

    <x-slot name="header">
        @include('areas.gestion_humana.acreditaciones.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div
        class="page-section cursos-registros-page req-manage-page acreditaciones-reporte-diario-page"
        x-data="acreditacionesReporteDiario({
            canEdit: @js($canEdit),
            cargasUrl: @js($cargasUrl),
            importUrl: @js($importUrl),
            today: @js($today),
            defaultFecha: @js($filters['fecha_reporte']),
            needsReplaceConfirm: @js($needsReplaceConfirm),
            oldFecha: @js(old('fecha_reporte', $filters['fecha_reporte'])),
        })"
    >
        <div class="app-container">
            @php
                $hasImportFlash = session()->has('import_done')
                    || session()->has('import_result')
                    || session()->has('import_failures')
                    || session()->has('import_report_token');
            @endphp

            @if (session('status') && ! $hasImportFlash)
                <div class="alert alert--success cursos-registros-page__alert">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert--danger cursos-registros-page__alert">{{ session('error') }}</div>
            @endif
            @if ($hasImportErrors)
                @php
                    $isReplaceConfirmOnly = $needsReplaceConfirm
                        && ! $errors->has('file_proceso')
                        && ! $errors->has('file_acreditado')
                        && ! $errors->has('fecha_reporte');
                @endphp
                <div
                    class="alert {{ $isReplaceConfirmOnly ? 'alert--warning' : 'alert--danger' }} cursos-registros-page__alert"
                    role="alert"
                >
                    @if ($isReplaceConfirmOnly)
                        <strong style="display:block;margin-bottom:0.35rem;">Confirmación de reemplazo requerida</strong>
                    @else
                        <strong style="display:block;margin-bottom:0.35rem;">No se pudo cargar el reporte diario:</strong>
                    @endif
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                    @if ($canEdit)
                        <button
                            type="button"
                            class="btn btn--secondary btn--sm"
                            style="margin-top:0.6rem;"
                            x-on:click="openCargaModal()"
                        >
                            Reabrir modal de carga
                        </button>
                    @endif
                </div>
            @endif

            <x-import-result-modal download-route="gestion-humana.acreditaciones.reporte-diario.import-report" />

            <div class="panel cursos-registros-panel">
                <div class="panel__body panel__body--compact req-manage-shell">
                    <div class="req-manage-shell__filters">
                        <form method="GET" action="{{ route('gestion-humana.acreditaciones.reporte-diario') }}" class="req-manage-filters">
                            <div class="cursos-registros-page__filters">
                                <div class="form-field">
                                    <label class="form-label" for="filter_fecha_reporte">Fecha reporte</label>
                                    <input
                                        id="filter_fecha_reporte"
                                        name="fecha_reporte"
                                        type="date"
                                        class="form-input"
                                        max="{{ $today }}"
                                        value="{{ $filters['fecha_reporte'] }}"
                                        required
                                    >
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_origen">Origen</label>
                                    <x-searchable-select
                                        id="filter_origen"
                                        name="origen"
                                        :options="$filterOrigenOptions"
                                        :value="$filters['origen']"
                                        placeholder="Todos"
                                        :allow-clear="false"
                                    />
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_q">Buscar</label>
                                    <input
                                        id="filter_q"
                                        name="q"
                                        type="text"
                                        class="form-input"
                                        value="{{ $filters['q'] }}"
                                        placeholder="Cédula, nombre o cargo"
                                    >
                                </div>
                                <div class="form-field cursos-registros-page__filter-actions">
                                    <button
                                        type="submit"
                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                        title="Filtrar"
                                        aria-label="Filtrar"
                                    >
                                        <x-lucide-search width="18" height="18" aria-hidden="true" />
                                    </button>
                                    <a
                                        href="{{ route('gestion-humana.acreditaciones.reporte-diario') }}"
                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                        title="Limpiar filtros"
                                        aria-label="Limpiar filtros"
                                    >
                                        <x-lucide-x width="18" height="18" aria-hidden="true" />
                                    </a>
                                    <x-export-excel
                                        route="{{ $exportUrl }}"
                                        label=""
                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    />
                                </div>
                            </div>
                        </form>

                        <div class="cursos-registros-page__table-toolbar">
                            <p class="req-manage-filters__meta">
                                <strong id="reporte-diario-count">…</strong>
                                <span>registro(s)</span>
                                <span class="panel-text" style="margin-left:0.5rem;">
                                    · fecha {{ $filters['fecha_reporte'] }}
                                </span>
                            </p>

                            <div class="cursos-registros-page__table-actions">
                                <button
                                    type="button"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Ver cargas"
                                    aria-label="Ver cargas"
                                    x-on:click="openCargas()"
                                >
                                    <x-lucide-history width="18" height="18" aria-hidden="true" />
                                </button>
                                @if ($canEdit)
                                    <button
                                        type="button"
                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                        title="Cargar reporte APO"
                                        aria-label="Cargar reporte APO"
                                        x-on:click="openCargaModal()"
                                    >
                                        <x-lucide-upload width="18" height="18" aria-hidden="true" />
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="data-table-wrap req-manage-shell__table cursos-registros-page__table-wrap data-table-wrap--booting">
                        @include('partials.data-table-loader')
                        <table
                            id="reporte-diario-datatable"
                            class="data-table js-reporte-diario-datatable"
                            data-dt-url="{{ $datatableUrl }}"
                            style="width:100%"
                        >
                            <thead>
                                <tr>
                                    <th>Origen</th>
                                    <th>Apellido1</th>
                                    <th>Apellido2</th>
                                    <th>Nombre1</th>
                                    <th>Nombre2</th>
                                    <th>Nombre completo</th>
                                    <th>IdNum</th>
                                    <th>Cargo</th>
                                    <th>Estado (APO)</th>
                                    <th>Vigen.Acr</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if ($canEdit)
                <x-modal name="acreditaciones-reporte-diario-carga" maxWidth="lg" :show="$showCargaModal" focusable>
                    <div class="modal-card ficha-empleados-masivos-modal" style="position:relative;">
                        <div class="ficha-empleados-masivos-modal__header">
                            <div class="ficha-empleados-masivos-modal__heading">
                                <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                                    <x-lucide-upload width="18" height="18" aria-hidden="true" />
                                </span>
                                <div>
                                    <h3 class="ficha-empleados-masivos-modal__title">Cargar reporte diario APO</h3>
                                    <p class="ficha-empleados-masivos-modal__lead">
                                        Suba 1 o 2 Excel (En proceso / Acreditados APO). El origen se toma del campo, no del nombre del archivo.
                                    </p>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="ficha-empleados-masivos-modal__close"
                                aria-label="Cerrar"
                                x-bind:disabled="importing"
                                x-on:click="$dispatch('close-modal', 'acreditaciones-reporte-diario-carga')"
                            >
                                <x-lucide-x width="18" height="18" aria-hidden="true" />
                            </button>
                        </div>

                        @if ($errors->any())
                            <div class="alert alert--danger ficha-empleados-masivos-modal__alert">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        <form
                            method="POST"
                            action="{{ $importUrl }}"
                            enctype="multipart/form-data"
                            class="ficha-empleados-masivos-modal__import-form"
                            data-reporte-diario-import-form
                            x-ref="importForm"
                            x-on:submit="onImportSubmit($event)"
                        >
                            @csrf
                            <input type="hidden" name="confirm_replace" :value="confirmReplace ? '1' : '0'">

                            <div class="form-field" style="margin-bottom:0.75rem;">
                                <label class="form-label" for="carga_fecha_reporte">Fecha de reporte</label>
                                <input
                                    id="carga_fecha_reporte"
                                    name="fecha_reporte"
                                    type="date"
                                    class="form-input"
                                    max="{{ $today }}"
                                    x-model="cargaFecha"
                                    x-on:change="refreshReplaceHint()"
                                    required
                                >
                            </div>

                            <div class="form-field" style="margin-bottom:0.75rem;">
                                <label class="form-label" for="file_proceso">En proceso (opcional)</label>
                                <input
                                    id="file_proceso"
                                    name="file_proceso"
                                    type="file"
                                    accept=".xlsx,.xls,.csv"
                                    class="ficha-empleados-masivos-modal__file-input"
                                    hidden
                                    data-file-proceso
                                    data-file-name-target="proceso"
                                    x-on:change="onFilePicked($event, 'proceso')"
                                >
                                <span class="ficha-empleados-masivos-modal__file-name" data-file-name="proceso" x-text="fileNameProceso">Sin archivo seleccionado</span>
                                <div class="acreditaciones-reporte-diario-file-actions">
                                    <label
                                        for="file_proceso"
                                        class="btn btn--secondary btn--sm ficha-empleados-masivos-modal__action"
                                        title="Elegir archivo En proceso"
                                    >
                                        <x-lucide-file-up width="15" height="15" aria-hidden="true" />
                                        Elegir archivo
                                    </label>
                                </div>
                            </div>

                            <div class="form-field" style="margin-bottom:0.75rem;">
                                <label class="form-label" for="file_acreditado">Acreditado APO (opcional)</label>
                                <input
                                    id="file_acreditado"
                                    name="file_acreditado"
                                    type="file"
                                    accept=".xlsx,.xls,.csv"
                                    class="ficha-empleados-masivos-modal__file-input"
                                    hidden
                                    data-file-acreditado
                                    data-file-name-target="acreditado"
                                    x-on:change="onFilePicked($event, 'acreditado')"
                                >
                                <span class="ficha-empleados-masivos-modal__file-name" data-file-name="acreditado" x-text="fileNameAcreditado">Sin archivo seleccionado</span>
                                <div class="acreditaciones-reporte-diario-file-actions">
                                    <label
                                        for="file_acreditado"
                                        class="btn btn--secondary btn--sm ficha-empleados-masivos-modal__action"
                                        title="Elegir archivo Acreditado APO"
                                    >
                                        <x-lucide-file-up width="15" height="15" aria-hidden="true" />
                                        Elegir archivo
                                    </label>
                                </div>
                            </div>

                            <div
                                class="acreditaciones-rd-replace"
                                role="status"
                                x-show="showReplaceWarning"
                                x-cloak
                            >
                                <div class="acreditaciones-rd-replace__icon" aria-hidden="true">
                                    <x-lucide-replace width="20" height="20" />
                                </div>
                                <div class="acreditaciones-rd-replace__body">
                                    <p class="acreditaciones-rd-replace__title">Carga existente para esta fecha</p>
                                    <p class="acreditaciones-rd-replace__text">
                                        Al confirmar se reemplazan solo los orígenes que suba; el otro se conserva.
                                    </p>
                                    <div class="acreditaciones-rd-replace__origins" x-show="dateHasProceso || dateHasAcreditado">
                                        <span
                                            class="acreditaciones-rd-replace__chip"
                                            x-show="dateHasProceso"
                                        >En proceso</span>
                                        <span
                                            class="acreditaciones-rd-replace__chip"
                                            x-show="dateHasAcreditado"
                                        >Acreditado APO</span>
                                    </div>
                                    <p
                                        class="acreditaciones-rd-replace__hint"
                                        x-show="clientReplaceBlocked"
                                        x-cloak
                                    >
                                        Marque confirmar y pulse <strong>Cargar</strong> de nuevo — no hace falta volver a elegir los archivos.
                                    </p>
                                    <label class="acreditaciones-rd-replace__confirm">
                                        <input type="checkbox" x-model="confirmReplace" class="acreditaciones-rd-replace__checkbox">
                                        <span class="acreditaciones-rd-replace__confirm-text">
                                            Confirmar reemplazo
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <div class="ficha-empleados-masivos-modal__import-actions acreditaciones-reporte-diario-import-actions">
                                <button
                                    type="button"
                                    class="btn btn--secondary btn--sm ficha-empleados-masivos-modal__action"
                                    x-bind:disabled="importing"
                                    x-on:click="$dispatch('close-modal', 'acreditaciones-reporte-diario-carga')"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    class="btn btn--primary btn--sm ficha-empleados-masivos-modal__action ficha-empleados-masivos-modal__action--primary"
                                    data-reporte-diario-import-submit
                                    x-bind:disabled="importing"
                                    x-bind:aria-busy="importing"
                                >
                                    <template x-if="!importing">
                                        <span class="acreditaciones-rd-import-btn-inner">
                                            <x-lucide-upload width="15" height="15" aria-hidden="true" />
                                            Cargar
                                        </span>
                                    </template>
                                    <template x-if="importing">
                                        <span class="acreditaciones-rd-import-btn-inner">
                                            <span class="ficha-empleados-masivos-modal__btn-spinner" aria-hidden="true"></span>
                                            Importando…
                                        </span>
                                    </template>
                                </button>
                            </div>
                        </form>

                        <div
                            class="ficha-empleados-masivos-modal__loading"
                            data-reporte-diario-import-loading
                            x-ref="importLoading"
                            hidden
                            aria-live="polite"
                            aria-busy="true"
                        >
                            <div class="ficha-empleados-masivos-modal__loading-card">
                                <span class="ficha-empleados-masivos-modal__spinner" aria-hidden="true"></span>
                                <p class="ficha-empleados-masivos-modal__loading-title">Importando reporte diario</p>
                                <p class="ficha-empleados-masivos-modal__loading-text">
                                    Procesando Excel (puede tardar con archivos grandes). No cierre esta ventana.
                                </p>
                            </div>
                        </div>
                    </div>
                </x-modal>
            @endif

            <x-modal name="acreditaciones-reporte-diario-cargas" maxWidth="2xl" focusable>
                <div class="modal-card ficha-empleados-masivos-modal">
                    <div class="ficha-empleados-masivos-modal__header">
                        <div class="ficha-empleados-masivos-modal__heading">
                            <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                                <x-lucide-history width="18" height="18" aria-hidden="true" />
                            </span>
                            <div>
                                <h3 class="ficha-empleados-masivos-modal__title">Historial de cargas</h3>
                                <p class="ficha-empleados-masivos-modal__lead">
                                    Cabeceras por fecha de reporte. «Ver» aplica el filtro al listado principal.
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="ficha-empleados-masivos-modal__close"
                            aria-label="Cerrar"
                            x-on:click="$dispatch('close-modal', 'acreditaciones-reporte-diario-cargas')"
                        >
                            <x-lucide-x width="18" height="18" aria-hidden="true" />
                        </button>
                    </div>

                    <div style="max-height:28rem;overflow:auto;">
                        <p x-show="cargasLoading" class="panel-text">Cargando…</p>
                        <p x-show="!cargasLoading && cargas.length === 0" class="panel-text">No hay cargas registradas.</p>
                        <table class="data-table" x-show="!cargasLoading && cargas.length > 0" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>En proceso</th>
                                    <th>Acreditado APO</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="row in cargas" :key="row.id">
                                    <tr>
                                        <td x-text="row.fecha_reporte"></td>
                                        <td>
                                            <div x-text="(row.proceso_rows_ok ?? 0) + ' ok / ' + (row.proceso_rows_fail ?? 0) + ' err'"></div>
                                            <div class="panel-text" style="font-size:0.8rem;" x-text="(row.proceso_loaded_by || '—') + (row.proceso_loaded_at ? ' · ' + row.proceso_loaded_at : '')"></div>
                                            <div class="panel-text" style="font-size:0.75rem;" x-text="row.proceso_file_name || ''"></div>
                                        </td>
                                        <td>
                                            <div x-text="(row.acreditado_rows_ok ?? 0) + ' ok / ' + (row.acreditado_rows_fail ?? 0) + ' err'"></div>
                                            <div class="panel-text" style="font-size:0.8rem;" x-text="(row.acreditado_loaded_by || '—') + (row.acreditado_loaded_at ? ' · ' + row.acreditado_loaded_at : '')"></div>
                                            <div class="panel-text" style="font-size:0.75rem;" x-text="row.acreditado_file_name || ''"></div>
                                        </td>
                                        <td>
                                            <a
                                                :href="row.view_url"
                                                class="btn btn--ghost btn--sm"
                                                title="Ver listado de esta fecha"
                                            >Ver</a>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </x-modal>
        </div>
    </div>

    @push('scripts')
        <script>
            function acreditacionesReporteDiario(config) {
                return {
                    canEdit: !!config.canEdit,
                    cargasUrl: config.cargasUrl || '',
                    importUrl: config.importUrl || '',
                    today: config.today,
                    cargaFecha: config.oldFecha || config.defaultFecha || config.today,
                    confirmReplace: !!config.needsReplaceConfirm,
                    needsReplaceConfirm: !!config.needsReplaceConfirm,
                    importing: false,
                    dateHasProceso: false,
                    dateHasAcreditado: false,
                    clientReplaceBlocked: false,
                    fileNameProceso: 'Sin archivo seleccionado',
                    fileNameAcreditado: 'Sin archivo seleccionado',
                    hasFileProceso: false,
                    hasFileAcreditado: false,
                    cargas: [],
                    cargasLoading: false,
                    cargasCache: [],
                    get showReplaceWarning() {
                        return this.needsReplaceConfirm
                            || this.confirmReplace
                            || this.clientReplaceBlocked
                            || this.dateHasProceso
                            || this.dateHasAcreditado;
                    },
                    init() {
                        if (this.needsReplaceConfirm || this.canEdit) {
                            this.refreshReplaceHint();
                        }
                    },
                    openCargaModal() {
                        this.cargaFecha = this.cargaFecha || this.today;
                        this.clientReplaceBlocked = false;
                        window.dispatchEvent(new CustomEvent('open-modal', {
                            detail: 'acreditaciones-reporte-diario-carga',
                        }));
                        this.refreshReplaceHint();
                    },
                    onFilePicked(event, key) {
                        const input = event.target;
                        const name = input.files?.[0]?.name || 'Sin archivo seleccionado';
                        const has = (input.files?.length || 0) > 0;
                        if (key === 'proceso') {
                            this.fileNameProceso = name;
                            this.hasFileProceso = has;
                        } else {
                            this.fileNameAcreditado = name;
                            this.hasFileAcreditado = has;
                        }
                        this.clientReplaceBlocked = false;
                    },
                    async refreshReplaceHint() {
                        const fecha = this.cargaFecha;
                        if (! fecha || ! this.cargasUrl) {
                            this.dateHasProceso = false;
                            this.dateHasAcreditado = false;
                            return;
                        }

                        try {
                            if (this.cargasCache.length === 0) {
                                const res = await fetch(this.cargasUrl, {
                                    headers: { 'Accept': 'application/json' },
                                });
                                if (! res.ok) {
                                    return;
                                }
                                const payload = await res.json();
                                this.cargasCache = Array.isArray(payload.data) ? payload.data : [];
                            }

                            const row = this.cargasCache.find((item) => item.fecha_reporte === fecha);
                            this.dateHasProceso = !!(
                                row
                                && (row.proceso_loaded_at || Number(row.proceso_rows_ok || 0) > 0)
                            );
                            this.dateHasAcreditado = !!(
                                row
                                && (row.acreditado_loaded_at || Number(row.acreditado_rows_ok || 0) > 0)
                            );
                        } catch (e) {
                            this.dateHasProceso = false;
                            this.dateHasAcreditado = false;
                        }
                    },
                    onImportSubmit(event) {
                        if (this.importing) {
                            event.preventDefault();
                            return;
                        }

                        const form = event.target;
                        const proceso = form.querySelector('[data-file-proceso]');
                        const acreditado = form.querySelector('[data-file-acreditado]');
                        const hasProceso = (proceso?.files?.length || 0) > 0;
                        const hasAcreditado = (acreditado?.files?.length || 0) > 0;

                        if (! hasProceso && ! hasAcreditado) {
                            event.preventDefault();
                            alert('Seleccione al menos un archivo (En proceso o Acreditado APO).');
                            return;
                        }

                        const needsReplace = (hasProceso && this.dateHasProceso)
                            || (hasAcreditado && this.dateHasAcreditado);

                        if (needsReplace && ! this.confirmReplace) {
                            event.preventDefault();
                            this.needsReplaceConfirm = true;
                            this.clientReplaceBlocked = true;
                            return;
                        }

                        // Overlay inmediato (antes del tick de Alpine) para que se vea durante el POST.
                        const loading = this.$refs.importLoading
                            || document.querySelector('[data-reporte-diario-import-loading]');
                        if (loading) {
                            loading.hidden = false;
                        }
                        this.importing = true;
                    },
                    async openCargas() {
                        window.dispatchEvent(new CustomEvent('open-modal', {
                            detail: 'acreditaciones-reporte-diario-cargas',
                        }));
                        this.cargasLoading = true;
                        try {
                            const res = await fetch(this.cargasUrl, {
                                headers: { 'Accept': 'application/json' },
                            });
                            if (! res.ok) {
                                this.cargas = [];
                                return;
                            }
                            const payload = await res.json();
                            this.cargas = Array.isArray(payload.data) ? payload.data : [];
                            this.cargasCache = this.cargas;
                        } catch (e) {
                            this.cargas = [];
                        } finally {
                            this.cargasLoading = false;
                        }
                    },
                };
            }

            document.addEventListener('DOMContentLoaded', function () {
                if (!window.jQuery || !window.jQuery.fn.DataTable) {
                    return;
                }

                const $table = window.jQuery('.js-reporte-diario-datatable');
                if (!$table.length) {
                    return;
                }

                const wrap = $table.closest('.data-table-wrap');
                const reveal = function () {
                    wrap.removeClass('data-table-wrap--booting');
                };
                const updateMeta = function (count) {
                    const el = document.getElementById('reporte-diario-count');
                    if (el) {
                        el.textContent = String(count);
                    }
                };

                const api = $table.DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: { url: $table.data('dt-url') },
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                        emptyTable: 'No hay filas para esta fecha / filtro. Si tiene permiso de edición, cargue el Excel APO.',
                    },
                    dom: '<"req-manage-dt-top"lf><"req-manage-table-scroll"t><"req-manage-dt-bottom"ip>',
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    pageLength: 25,
                    responsive: false,
                    order: [[0, 'asc'], [5, 'asc']],
                });

                api.on('xhr.dt', function (_event, _settings, json) {
                    reveal();
                    if (json && typeof json.recordsFiltered !== 'undefined') {
                        updateMeta(json.recordsFiltered);
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
