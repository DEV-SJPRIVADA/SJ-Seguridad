<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.cursos.partials.subnav', ['subTabs' => $subTabs])
        <div class="app-container">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">Validaciones</h2>
                <p class="panel-text">Activos sin curso y cursos por actualizar o vencidos</p>
            </div>
        </div>
    </x-slot>

    <div
        class="page-section cursos-registros-page req-manage-page acreditaciones-validaciones-page cursos-validaciones-page"
        x-data="cursosValidaciones({
            canEdit: @js($canEdit),
            defaultCola: @js($defaultCola),
            datatableUrl: @js($datatableUrl),
            bulkSelectableUrl: @js($bulkSelectableUrl ?? null),
            bulkMarkSolicitadoUrl: @js($bulkMarkSolicitadoUrl ?? null),
            lookupUrl: @js($lookupUrl),
            exportUrls: @js($exportUrls),
            counts: @js($counts),
        })"
        @cursos-open-edit.window="openEdit($event.detail)"
    >
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success cursos-registros-page__alert">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert--danger cursos-registros-page__alert">{{ session('error') }}</div>
            @endif

            <div class="panel cursos-registros-panel">
                <div class="panel__body panel__body--compact req-manage-shell">
                    <div class="av-results">
                    <div class="module-subnav av-colas-nav" aria-label="Colas de validación">
                        <div class="module-subnav__inner module-tabs" role="tablist">
                            @foreach ($colaLabels as $colaKey => $colaLabel)
                                <button
                                    type="button"
                                    class="module-tab av-cola-tab"
                                    role="tab"
                                    :class="{ 'module-tab--active': activeCola === @js($colaKey) }"
                                    :aria-selected="(activeCola === @js($colaKey)).toString()"
                                    @click="setCola(@js($colaKey))"
                                >
                                    <span class="av-cola-tab__icon" aria-hidden="true">
                                        @if ($colaKey === 'sin_curso')
                                            <x-lucide-user-round-x width="15" height="15" />
                                        @else
                                            <x-lucide-calendar-clock width="15" height="15" />
                                        @endif
                                    </span>
                                    <span class="av-cola-tab__label">{{ $colaLabel }}</span>
                                    <span
                                        class="av-cola-tab__count"
                                        id="cursos-validaciones-count-badge-{{ $colaKey }}"
                                        :class="{ 'av-cola-tab__count--hot': (counts[@js($colaKey)] || 0) > 0 }"
                                        x-text="counts[@js($colaKey)] ?? 0"
                                    ></span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    @foreach ($colaDefs as $colaKey => $colaDef)
                        <div
                            class="av-cola-panel"
                            x-show="activeCola === @js($colaKey)"
                            x-cloak
                            role="tabpanel"
                        >
                            <div class="av-cola-panel__toolbar">
                                <form
                                    class="req-manage-filters js-cursos-validaciones-filters"
                                    data-cola="{{ $colaKey }}"
                                    autocomplete="off"
                                    @submit.prevent="applyFilters(@js($colaKey))"
                                >
                                    <div class="cursos-registros-page__filters av-cola-filters">
                                        <div class="form-field">
                                            <label class="sr-only" for="cv_{{ $colaKey }}_document_number">Cédula</label>
                                            <input
                                                id="cv_{{ $colaKey }}_document_number"
                                                name="document_number"
                                                type="text"
                                                class="form-input"
                                                placeholder="Cédula"
                                            >
                                        </div>
                                        <div class="form-field">
                                            <label class="sr-only" for="cv_{{ $colaKey }}_full_name">Nombre</label>
                                            <input
                                                id="cv_{{ $colaKey }}_full_name"
                                                name="full_name"
                                                type="text"
                                                class="form-input"
                                                placeholder="Nombre"
                                            >
                                        </div>

                                        @if ($colaKey === 'por_actualizar_vencidos')
                                            <div class="form-field">
                                                <label class="sr-only" for="cv_{{ $colaKey }}_curso_tipo_id">Tipo curso</label>
                                                <x-searchable-select
                                                    id="cv_{{ $colaKey }}_curso_tipo_id"
                                                    name="curso_tipo_id"
                                                    :options="$filterTipoOptions"
                                                    value=""
                                                    placeholder="Tipo curso"
                                                    :allow-clear="true"
                                                />
                                            </div>
                                            <div class="form-field">
                                                <label class="sr-only" for="cv_{{ $colaKey }}_vigencia">Vigencia</label>
                                                <x-searchable-select
                                                    id="cv_{{ $colaKey }}_vigencia"
                                                    name="vigencia"
                                                    :options="$filterVigenciaOptions"
                                                    value=""
                                                    placeholder="Vigencia"
                                                    :allow-clear="true"
                                                />
                                            </div>
                                            <div class="form-field">
                                                <label class="sr-only" for="cv_{{ $colaKey }}_estado">Estado</label>
                                                <x-searchable-select
                                                    id="cv_{{ $colaKey }}_estado"
                                                    name="estado"
                                                    :options="$filterEstadoOptions"
                                                    value="todos"
                                                    placeholder="Estado"
                                                    :allow-clear="true"
                                                />
                                            </div>
                                        @endif

                                        <div class="form-field cursos-registros-page__filter-actions">
                                            <span class="av-cola-filters__count" aria-live="polite">
                                                <strong id="cursos-validaciones-count-{{ $colaKey }}">{{ (int) ($counts[$colaKey] ?? 0) }}</strong>
                                            </span>
                                            <button
                                                type="submit"
                                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                                title="Filtrar"
                                                aria-label="Filtrar"
                                            >
                                                <x-lucide-search width="18" height="18" aria-hidden="true" />
                                            </button>
                                            <button
                                                type="button"
                                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                                title="Limpiar filtros"
                                                aria-label="Limpiar filtros"
                                                @click="clearFilters(@js($colaKey))"
                                            >
                                                <x-lucide-x width="18" height="18" aria-hidden="true" />
                                            </button>
                                            @if (! empty($exportUrls[$colaKey]))
                                                <a
                                                    href="{{ $exportUrls[$colaKey] }}"
                                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost js-cursos-validaciones-export"
                                                    title="Exportar Excel de esta cola"
                                                    aria-label="Exportar Excel de esta cola"
                                                    data-export-base="{{ $exportUrls[$colaKey] }}"
                                                    data-cola="{{ $colaKey }}"
                                                    @click.prevent="exportCola(@js($colaKey), $event.currentTarget)"
                                                >
                                                    <x-lucide-file-spreadsheet width="18" height="18" aria-hidden="true" />
                                                </a>
                                            @endif
                                            @if ($canEdit && $colaKey === 'por_actualizar_vencidos')
                                                <button
                                                    type="button"
                                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                                    title="Marcar SOLICITADO"
                                                    aria-label="Marcar SOLICITADO"
                                                    x-show="selectedCount > 0"
                                                    x-cloak
                                                    @click="openBulkConfirm()"
                                                >
                                                    <x-lucide-check-circle width="18" height="18" aria-hidden="true" />
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <div class="data-table-wrap data-table-wrap--booting req-manage-shell__table av-cola-panel__table">
                                <table
                                    id="cursos-validaciones-datatable-{{ $colaKey }}"
                                    class="data-table js-cursos-validaciones-datatable"
                                    data-dt-cola="{{ $colaKey }}"
                                    data-dt-url="{{ $datatableUrl }}"
                                    data-dt-columns='@json($colaDef['columns'])'
                                    style="width:100%"
                                >
                                    <thead>
                                        <tr>
                                            @foreach ($colaDef['columns'] as $column)
                                                @if (($column['title'] ?? '') === '' && $canEdit && $colaKey === 'por_actualizar_vencidos')
                                                    <th class="cursos-registros-page__select-col" data-orderable="false">
                                                        <label class="cursos-registros-page__select-label" title="Seleccionar todos los elegibles del filtro">
                                                            <input
                                                                type="checkbox"
                                                                class="cursos-registros-page__select-checkbox"
                                                                :checked="allEligibleSelected"
                                                                :disabled="bulkSelectableRows.length === 0"
                                                                @change="toggleSelectAll($event.target.checked)"
                                                                aria-label="Seleccionar todos"
                                                            >
                                                        </label>
                                                    </th>
                                                @else
                                                    <th>{{ $column['title'] ?? '' }}</th>
                                                @endif
                                            @endforeach
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    @endforeach
                    </div>
                </div>
            </div>

            @if ($canEdit)
                @include('areas.gestion_humana.cursos.partials.nuevo-modal', [
                    'tipoOptions' => $tipoOptions,
                    'escuelaOptions' => $escuelaOptions,
                    'estadoOptions' => $estadoOptions,
                    'lookupUrl' => $lookupUrl,
                    'show' => $showNuevoModal,
                    'returnContext' => 'validaciones',
                    'returnCola' => 'sin_curso',
                ])

                <div
                    class="cursos-registros-page__modal"
                    x-show="editOpen"
                    x-cloak
                    @keydown.escape.window="editOpen = false"
                >
                    <div class="cursos-registros-page__modal-backdrop" @click="editOpen = false"></div>
                    <div class="cursos-registros-page__modal-panel panel" role="dialog" aria-modal="true">
                        <div class="panel__header panel-heading-row">
                            <h3 class="panel-title">Editar registro</h3>
                            <button type="button" class="btn btn--ghost btn--sm" @click="editOpen = false">Cerrar</button>
                        </div>
                        <div class="panel__body">
                            <form method="POST" :action="editForm.update_url" class="cursos-registros-page__form">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="_return_context" value="validaciones">
                                <input type="hidden" name="cola" value="por_actualizar_vencidos">
                                <div class="cursos-registros-page__form-grid">
                                    <div class="form-field">
                                        <label class="form-label" for="cv_edit_document_number">CEDULA</label>
                                        <input id="cv_edit_document_number" name="document_number" type="text" class="form-input" maxlength="50" required x-model="editForm.document_number">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="cv_edit_full_name">NOMBRE COMPLETO</label>
                                        <input id="cv_edit_full_name" name="full_name" type="text" class="form-input" maxlength="255" required x-model="editForm.full_name">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="cv_edit_curso_tipo_id">TIPO CURSO</label>
                                        <select id="cv_edit_curso_tipo_id" name="curso_tipo_id" class="form-input" required x-model="editForm.curso_tipo_id">
                                            @foreach ($tipoOptions as $opt)
                                                <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="cv_edit_curso_escuela_id">ESCUELA</label>
                                        <select id="cv_edit_curso_escuela_id" name="curso_escuela_id" class="form-input" required x-model="editForm.curso_escuela_id">
                                            <option value="">Seleccionar escuela</option>
                                            @foreach ($escuelaOptions as $opt)
                                                <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="cv_edit_fecha_expedicion">FECHA EXPEDICION</label>
                                        <input id="cv_edit_fecha_expedicion" name="fecha_expedicion" type="date" class="form-input" required x-model="editForm.fecha_expedicion">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="cv_edit_numero_curso">No.CURSO</label>
                                        <input id="cv_edit_numero_curso" name="numero_curso" type="text" class="form-input" maxlength="100" required x-model="editForm.numero_curso">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="cv_edit_estado">ESTADO</label>
                                        <select id="cv_edit_estado" name="estado" class="form-input" x-model="editForm.estado" required>
                                            <option value="SOLICITADO">SOLICITADO</option>
                                            <option value="ACTUALIZADO">ACTUALIZADO</option>
                                            <option value="PENDIENTE">PENDIENTE</option>
                                        </select>
                                    </div>
                                    <div class="form-field cursos-registros-page__form-span">
                                        <label class="form-label" for="cv_edit_observaciones">OBSERVACIONES</label>
                                        <textarea id="cv_edit_observaciones" name="observaciones" class="form-input" rows="2" x-model="editForm.observaciones"></textarea>
                                    </div>
                                </div>
                                <div class="cursos-registros-page__form-actions">
                                    <button type="submit" class="btn btn--primary">Actualizar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div
                    class="cursos-registros-page__modal"
                    x-show="bulkConfirmOpen"
                    x-cloak
                    @keydown.escape.window="closeBulkConfirm()"
                >
                    <div class="cursos-registros-page__modal-backdrop" @click="closeBulkConfirm()"></div>
                    <div class="cursos-registros-page__modal-panel panel cursos-registros-page__bulk-modal" role="dialog" aria-modal="true">
                        <div class="panel__header panel-heading-row">
                            <h3 class="panel-title">Confirmar marcado a SOLICITADO</h3>
                            <button type="button" class="btn btn--ghost btn--sm" @click="closeBulkConfirm()">Cerrar</button>
                        </div>
                        <div class="panel__body">
                            <div class="alert alert--danger cursos-registros-page__bulk-warning">
                                Esta acción <strong>no se puede revertir</strong> desde el marcado masivo.
                            </div>
                            <p class="panel-text">Se actualizarán <strong x-text="selectedCount"></strong> registro(s).</p>
                            <form method="POST" :action="bulkMarkSolicitadoUrl" class="cursos-registros-page__bulk-form" x-on:submit="submittingBulk = true">
                                @csrf
                                <input type="hidden" name="_return_context" value="validaciones">
                                <input type="hidden" name="cola" value="por_actualizar_vencidos">
                                <template x-for="id in selectedIds" :key="'bulk-id-' + id">
                                    <input type="hidden" name="ids[]" :value="id">
                                </template>
                                <label class="cursos-registros-page__bulk-confirm-label">
                                    <input type="checkbox" name="confirmed" value="1" x-model="bulkConfirmAccepted">
                                    Confirmo el cambio a SOLICITADO.
                                </label>
                                <div class="cursos-registros-page__form-actions">
                                    <button type="button" class="btn btn--secondary" @click="closeBulkConfirm()" :disabled="submittingBulk">Cancelar</button>
                                    <button type="submit" class="btn btn--primary" :disabled="! bulkConfirmAccepted || selectedCount < 1 || submittingBulk">
                                        Ejecutar cambio
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            function cursosValidaciones(config) {
                return {
                    canEdit: !!config.canEdit,
                    activeCola: config.defaultCola || 'sin_curso',
                    datatableUrl: config.datatableUrl,
                    bulkSelectableUrl: config.bulkSelectableUrl || '',
                    bulkMarkSolicitadoUrl: config.bulkMarkSolicitadoUrl || '',
                    lookupUrl: config.lookupUrl || '',
                    exportUrls: config.exportUrls || {},
                    counts: config.counts || {},
                    tables: {},
                    selectedMap: {},
                    bulkSelectableRows: [],
                    bulkConfirmOpen: false,
                    bulkConfirmAccepted: false,
                    submittingBulk: false,
                    editOpen: false,
                    editForm: {
                        id: null,
                        document_number: '',
                        full_name: '',
                        curso_tipo_id: '',
                        curso_escuela_id: '',
                        fecha_expedicion: '',
                        numero_curso: '',
                        estado: 'ACTUALIZADO',
                        observaciones: '',
                        update_url: '',
                    },
                    init() {
                        const self = this;
                        this.$nextTick(() => {
                            this.initAllTables();
                            this.reloadCola(this.activeCola);
                        });

                        document.addEventListener('click', (e) => {
                            const nuevoBtn = e.target.closest('.js-cursos-validaciones-nuevo');
                            if (nuevoBtn) {
                                let payload = {};
                                try { payload = JSON.parse(nuevoBtn.getAttribute('data-cursos-nuevo') || '{}'); } catch (err) {}
                                this.openCreate(payload);
                                return;
                            }
                            const editBtn = e.target.closest('.js-curso-edit');
                            if (editBtn) {
                                let payload = {};
                                try { payload = JSON.parse(editBtn.getAttribute('data-curso-edit') || '{}'); } catch (err) {}
                                this.openEdit(payload);
                            }
                        });

                        document.addEventListener('change', (e) => {
                            const select = e.target.closest('.js-curso-row-select');
                            if (!select || !select.closest('.js-cursos-validaciones-datatable')) {
                                return;
                            }
                            let meta = null;
                            try { meta = JSON.parse(select.getAttribute('data-curso-row') || 'null'); } catch (err) {}
                            self.toggleRow(Number(select.value), select.checked, meta);
                        });
                    },
                    get selectedIds() {
                        return Object.keys(this.selectedMap).filter((id) => this.selectedMap[id]).map(Number);
                    },
                    get selectedCount() {
                        return this.selectedIds.length;
                    },
                    get allEligibleSelected() {
                        return this.bulkSelectableRows.length > 0
                            && this.bulkSelectableRows.every((row) => this.selectedMap[row.id]);
                    },
                    filterForm(cola) {
                        return document.querySelector('.js-cursos-validaciones-filters[data-cola="' + cola + '"]');
                    },
                    collectFilters(cola) {
                        const form = this.filterForm(cola);
                        if (!form) {
                            return {};
                        }
                        const data = {};
                        const fd = new FormData(form);
                        fd.forEach((value, key) => {
                            const v = String(value || '').trim();
                            if (v !== '' && !(key === 'estado' && v === 'todos')) {
                                data[key] = v;
                            }
                        });
                        return data;
                    },
                    setCola(cola) {
                        if (this.activeCola === cola) {
                            return;
                        }
                        this.activeCola = cola;
                        this.selectedMap = {};
                        this.bulkSelectableRows = [];
                        this.$nextTick(() => {
                            this.reloadCola(cola);
                            const api = this.tables[cola];
                            if (api) {
                                api.columns.adjust();
                            }
                        });
                    },
                    applyFilters(cola) {
                        this.selectedMap = {};
                        this.reloadCola(cola || this.activeCola);
                    },
                    clearFilters(cola) {
                        const form = this.filterForm(cola);
                        if (!form) {
                            return;
                        }
                        form.querySelectorAll('input[type="text"], input[type="hidden"]').forEach((input) => {
                            if (input.name === 'estado') {
                                input.value = 'todos';
                            } else {
                                input.value = '';
                            }
                        });
                        form.querySelectorAll('.searchable-select-wrap').forEach((wrap) => {
                            try {
                                if (window.Alpine && Alpine.$data) {
                                    const data = Alpine.$data(wrap);
                                    if (data && typeof data.clear === 'function') {
                                        data.clear();
                                    } else if (data) {
                                        data.value = wrap.querySelector('input[name="estado"]') ? 'todos' : '';
                                        data.selectedLabel = '';
                                        data.query = '';
                                    }
                                }
                            } catch (e) {}
                        });
                        this.applyFilters(cola);
                    },
                    exportCola(cola, anchor) {
                        const base = (anchor && anchor.getAttribute('data-export-base')) || this.exportUrls[cola];
                        if (!base) {
                            return;
                        }
                        const url = new URL(base, window.location.origin);
                        Object.entries(this.collectFilters(cola)).forEach(([key, value]) => {
                            url.searchParams.set(key, value);
                        });
                        window.location.href = url.toString();
                    },
                    openCreate(row) {
                        window.dispatchEvent(new CustomEvent('cursos-nuevo-prefill', {
                            detail: {
                                document_number: row.document_number || '',
                                full_name: row.full_name || '',
                            },
                        }));
                        this.$dispatch('open-modal', 'cursos-nuevo');
                    },
                    openEdit(row) {
                        this.editForm = { ...row };
                        this.editOpen = true;
                        this.bulkConfirmOpen = false;
                    },
                    openBulkConfirm() {
                        if (this.selectedCount < 1) return;
                        this.bulkConfirmAccepted = false;
                        this.submittingBulk = false;
                        this.bulkConfirmOpen = true;
                        this.editOpen = false;
                    },
                    closeBulkConfirm() {
                        if (this.submittingBulk) return;
                        this.bulkConfirmOpen = false;
                        this.bulkConfirmAccepted = false;
                    },
                    toggleRow(id, checked, rowMeta) {
                        this.selectedMap = { ...this.selectedMap, [id]: !!checked };
                        if (checked && rowMeta && !this.bulkSelectableRows.some((r) => Number(r.id) === Number(id))) {
                            this.bulkSelectableRows = [...this.bulkSelectableRows, rowMeta];
                        }
                    },
                    toggleSelectAll(checked) {
                        const next = {};
                        if (checked) {
                            this.bulkSelectableRows.forEach((row) => { next[row.id] = true; });
                        }
                        this.selectedMap = next;
                        document.querySelectorAll('#cursos-validaciones-datatable-por_actualizar_vencidos .js-curso-row-select').forEach((input) => {
                            input.checked = !!this.selectedMap[Number(input.value)];
                        });
                    },
                    async loadBulkSelectable(cola) {
                        if (!this.bulkSelectableUrl || cola !== 'por_actualizar_vencidos' || !this.canEdit) {
                            return;
                        }
                        try {
                            const url = new URL(this.bulkSelectableUrl, window.location.origin);
                            Object.entries(this.collectFilters(cola)).forEach(([key, value]) => {
                                url.searchParams.set(key, value);
                            });
                            const res = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
                            if (!res.ok) return;
                            const payload = await res.json();
                            this.bulkSelectableRows = Array.isArray(payload.data) ? payload.data : [];
                        } catch (e) {
                            this.bulkSelectableRows = [];
                        }
                    },
                    initAllTables() {
                        const $ = window.jQuery;
                        if (!$ || !$.fn.DataTable) {
                            return;
                        }
                        const self = this;
                        document.querySelectorAll('.js-cursos-validaciones-datatable').forEach((el) => {
                            const $table = $(el);
                            const cola = String($table.data('dt-cola') || '');
                            if (!cola || self.tables[cola]) {
                                return;
                            }

                            const wrap = $table.closest('.data-table-wrap');
                            wrap.addClass('data-table-wrap--dt-compact');
                            const columns = $table.data('dt-columns') || [];

                            const api = $table.DataTable({
                                processing: true,
                                serverSide: true,
                                deferLoading: 0,
                                ajax: {
                                    url: $table.data('dt-url') || self.datatableUrl,
                                    data: function (d) {
                                        d.cola = cola;
                                        Object.entries(self.collectFilters(cola)).forEach(([key, value]) => {
                                            d[key] = value;
                                        });
                                    },
                                },
                                columns: columns,
                                language: {
                                    url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                                    emptyTable: 'No hay hallazgos en esta cola.',
                                },
                                dom: '<"req-manage-table-scroll"t><"req-manage-dt-bottom"lip>',
                                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                                pageLength: 25,
                                responsive: false,
                                scrollX: false,
                                autoWidth: false,
                                order: [],
                                columnDefs: columns
                                    .map((col, idx) => {
                                        if (col.orderable === false || col.searchable === false) {
                                            return {
                                                targets: idx,
                                                orderable: col.orderable !== false ? undefined : false,
                                                searchable: col.searchable !== false ? undefined : false,
                                            };
                                        }
                                        return null;
                                    })
                                    .filter(Boolean),
                            });

                            api.on('xhr.dt', function (_e, _s, json) {
                                wrap.removeClass('data-table-wrap--booting');
                                if (json && typeof json.recordsFiltered !== 'undefined') {
                                    const count = Number(json.recordsFiltered) || 0;
                                    self.counts = { ...self.counts, [cola]: count };
                                    const meta = document.getElementById('cursos-validaciones-count-' + cola);
                                    if (meta) {
                                        meta.textContent = String(count);
                                    }
                                    const badge = document.getElementById('cursos-validaciones-count-badge-' + cola);
                                    if (badge) {
                                        badge.textContent = String(count);
                                        badge.classList.toggle('av-cola-tab__count--hot', count > 0);
                                    }
                                }
                            });

                            api.on('draw.dt', function () {
                                $table.find('.js-curso-row-select').each(function () {
                                    this.checked = !!self.selectedMap[Number(this.value)];
                                });
                            });

                            self.tables[cola] = api;
                        });
                    },
                    reloadCola(cola) {
                        const api = this.tables[cola];
                        if (!api) {
                            return;
                        }
                        api.ajax.reload(null, true);
                        this.loadBulkSelectable(cola);
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
