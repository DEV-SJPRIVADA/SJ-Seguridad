<x-app-layout>
    @php
        $showNuevoModal = $canEdit && $errors->any() && ! $errors->has('import_file');
        $showMasivosModal = $canEdit && $errors->has('import_file');
    @endphp

    <x-slot name="header">
        @include('areas.gestion_humana.acreditaciones.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div
        class="page-section cursos-registros-page req-manage-page acreditaciones-acreditados-page"
        x-data="acreditacionesAcreditados({
            lookupUrl: @js($lookupUrl),
            canEdit: @js($canEdit),
            bulkSelectableUrl: @js($bulkSelectableUrl ?? null),
            bulkUpdateUrl: @js($bulkUpdateUrl ?? null),
            activeFilterQuery: @js($activeFilterQuery ?? []),
        })"
        @acreditaciones-open-edit.window="openEdit($event.detail)"
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

            <x-import-result-modal download-route="gestion-humana.acreditaciones.acreditados.import-report" />

            <div class="panel cursos-registros-panel">
                <div class="panel__body panel__body--compact req-manage-shell">
                    <div class="req-manage-shell__filters">
                        <form method="GET" action="{{ route('gestion-humana.acreditaciones.acreditados') }}" class="req-manage-filters">
                            <div class="cursos-registros-page__filters">
                                <div class="form-field">
                                    <label class="form-label" for="filter_document_number">Cédula</label>
                                    <input id="filter_document_number" name="document_number" type="text" class="form-input" value="{{ $filters['document_number'] }}">
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_cargo">Cargo</label>
                                    <input id="filter_cargo" name="cargo" type="text" class="form-input" value="{{ $filters['cargo'] }}">
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_cargo_apo">CARGO APO</label>
                                    <x-searchable-select
                                        id="filter_cargo_apo"
                                        name="cargo_apo"
                                        :options="$filterCargoApoOptions"
                                        :value="$filters['cargo_apo']"
                                        placeholder="Todos"
                                        :allow-clear="true"
                                    />
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_estado">Estado acreditación</label>
                                    <x-searchable-select
                                        id="filter_estado"
                                        name="estado"
                                        :options="$filterEstadoOptions"
                                        :value="$filters['estado']"
                                        placeholder="Todos"
                                        :allow-clear="false"
                                    />
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_ficha_estado">Estado en ficha</label>
                                    <x-searchable-select
                                        id="filter_ficha_estado"
                                        name="ficha_estado"
                                        :options="$filterFichaEstadoOptions"
                                        :value="$filters['ficha_estado']"
                                        placeholder="Activos en ficha"
                                        :allow-clear="false"
                                    />
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_vigencia_desde">VIGEN.ACR desde</label>
                                    <input id="filter_vigencia_desde" name="vigencia_desde" type="date" class="form-input" value="{{ $filters['vigencia_desde'] }}">
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_vigencia_hasta">VIGEN.ACR hasta</label>
                                    <input id="filter_vigencia_hasta" name="vigencia_hasta" type="date" class="form-input" value="{{ $filters['vigencia_hasta'] }}">
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
                                        href="{{ route('gestion-humana.acreditaciones.acreditados') }}"
                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                        title="Limpiar filtros"
                                        aria-label="Limpiar filtros"
                                    >
                                        <x-lucide-x width="18" height="18" aria-hidden="true" />
                                    </a>
                                    <a
                                        href="{{ $exportUrl }}"
                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                        title="Exportar a Excel"
                                        aria-label="Exportar a Excel"
                                    >
                                        <x-selfhst-microsoft-excel-2013 width="18" height="18" aria-hidden="true" />
                                    </a>
                                </div>
                            </div>
                            <p class="panel-text" style="margin-top:0.35rem;font-size:0.85rem;">
                                Por defecto solo se listan empleados <strong>activos en ficha</strong>.
                                Use «Estado en ficha» para ver desvinculados o todos.
                                El rango de fechas filtra por <strong>VIGEN.ACR</strong> (vencimiento).
                            </p>
                        </form>

                        <div class="cursos-registros-page__table-toolbar">
                            <p class="req-manage-filters__meta">
                                <strong id="acreditados-count">…</strong>
                                <span id="acreditados-count-label">registro(s)</span>
                            </p>

                            @if ($canEdit)
                                <div class="cursos-registros-page__table-actions">
                                    <button
                                        type="button"
                                        class="btn btn--primary btn--sm acreditaciones-bulk-trigger"
                                        x-show="selectedCount > 0"
                                        x-cloak
                                        x-on:click="openBulkUpdate()"
                                        title="Actualizar seleccionados"
                                    >
                                        <x-lucide-list-checks width="16" height="16" aria-hidden="true" />
                                        <span>Actualizar</span>
                                        <span class="acreditaciones-bulk-trigger__count" x-text="selectedCount"></span>
                                    </button>
                                    <button
                                        type="button"
                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                        title="Plantilla masivos — importar"
                                        aria-label="Plantilla masivos — importar"
                                        x-on:click.prevent="$dispatch('open-modal', 'acreditaciones-masivos')"
                                    >
                                        <x-lucide-upload width="18" height="18" aria-hidden="true" />
                                    </button>
                                    <button
                                        type="button"
                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                        title="Nuevo acreditado"
                                        aria-label="Nuevo acreditado"
                                        x-on:click.prevent="$dispatch('open-modal', 'acreditaciones-nuevo')"
                                    >
                                        <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="data-table-wrap req-manage-shell__table cursos-registros-page__table-wrap data-table-wrap--booting">
                        @include('partials.data-table-loader')
                        <table
                            id="acreditados-datatable"
                            class="data-table js-acreditados-datatable"
                            data-dt-url="{{ $datatableUrl }}"
                            data-dt-can-edit="{{ $canEdit ? '1' : '0' }}"
                            style="width:100%"
                        >
                            <thead>
                                <tr>
                                    @if ($canEdit)
                                        <th class="cursos-registros-page__select-col" data-orderable="false">
                                            <label class="cursos-registros-page__select-label" title="Seleccionar todos los del filtro actual">
                                                <input
                                                    type="checkbox"
                                                    class="cursos-registros-page__select-checkbox"
                                                    x-bind:checked="allEligibleSelected"
                                                    x-bind:disabled="bulkSelectableRows.length === 0 || bulkSelectableLoading"
                                                    x-on:change="toggleSelectAll($event.target.checked)"
                                                    aria-label="Seleccionar todos"
                                                >
                                            </label>
                                        </th>
                                    @endif
                                    <th>CEDULA</th>
                                    <th>NOMBRE COMPLETO</th>
                                    <th>CARGO</th>
                                    <th>CARGO APO</th>
                                    <th>VIGEN.ACR</th>
                                    <th>ESTADO</th>
                                    <th>OBSERVACIONES</th>
                                    <th>FECHA SOLICITUD</th>
                                    @if ($canEdit)
                                        <th>Acciones</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if ($canEdit)
                @include('areas.gestion_humana.acreditaciones.partials.bulk-update-modal')

                @include('areas.gestion_humana.acreditaciones.partials.nuevo-modal', [
                    'cargoApoOptions' => $cargoApoOptions,
                    'lookupUrl' => $lookupUrl,
                    'show' => $showNuevoModal,
                ])

                @include('areas.gestion_humana.acreditaciones.partials.masivos-modal', [
                    'canEdit' => $canEdit,
                    'exportUrl' => $exportUrl,
                    'importTemplateUrl' => $importTemplateUrl,
                    'importUrl' => $importUrl,
                    'show' => $showMasivosModal,
                ])

                @include('areas.gestion_humana.acreditaciones.partials.edit-modal', [
                    'cargoApoOptions' => $cargoApoOptions,
                ])
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            function acreditacionesAcreditados(config) {
                return {
                    lookupUrl: config.lookupUrl,
                    canEdit: !!config.canEdit,
                    bulkSelectableUrl: config.bulkSelectableUrl || '',
                    bulkUpdateUrl: config.bulkUpdateUrl || '',
                    activeFilterQuery: config.activeFilterQuery || {},
                    bulkSelectableRows: [],
                    bulkSelectableLoading: false,
                    selectedMap: {},
                    bulkUpdateOpen: false,
                    submittingBulk: false,
                    bulkForm: {
                        observaciones: '',
                        fecha_solicitud: '',
                    },
                    editOpen: false,
                    editIdentityLocked: true,
                    editForm: {
                        document_number: '',
                        full_name: '',
                        cargo: '',
                        cargo_apo: '',
                        vigencia_acr: '',
                        fecha_solicitud: '',
                        estado: '',
                        estado_label: '',
                        observaciones: '',
                        update_url: '',
                    },
                    init() {
                        if (this.canEdit && this.bulkSelectableUrl) {
                            this.loadBulkSelectable();
                        }
                    },
                    async loadBulkSelectable() {
                        this.bulkSelectableLoading = true;
                        try {
                            const res = await fetch(this.bulkSelectableUrl, {
                                headers: { 'Accept': 'application/json' },
                            });
                            if (! res.ok) {
                                return;
                            }
                            const payload = await res.json();
                            this.bulkSelectableRows = Array.isArray(payload.data) ? payload.data : [];
                        } catch (e) {
                            this.bulkSelectableRows = [];
                        } finally {
                            this.bulkSelectableLoading = false;
                        }
                    },
                    get selectedIds() {
                        return Object.keys(this.selectedMap)
                            .filter((id) => this.selectedMap[id])
                            .map((id) => Number(id));
                    },
                    get selectedCount() {
                        return this.selectedIds.length;
                    },
                    get selectedRows() {
                        const selected = new Set(this.selectedIds);
                        return this.bulkSelectableRows.filter((row) => selected.has(Number(row.id)));
                    },
                    get allEligibleSelected() {
                        if (this.bulkSelectableRows.length === 0) {
                            return false;
                        }
                        return this.bulkSelectableRows.every((row) => this.selectedMap[row.id]);
                    },
                    get bulkHasPayload() {
                        return String(this.bulkForm.observaciones || '').trim() !== ''
                            || String(this.bulkForm.fecha_solicitud || '').trim() !== '';
                    },
                    isSelected(id) {
                        return !! this.selectedMap[id];
                    },
                    toggleRow(id, checked, rowMeta) {
                        this.selectedMap = {
                            ...this.selectedMap,
                            [id]: !! checked,
                        };

                        if (checked && rowMeta && ! this.bulkSelectableRows.some((row) => Number(row.id) === Number(id))) {
                            this.bulkSelectableRows = [...this.bulkSelectableRows, rowMeta];
                        }
                    },
                    toggleSelectAll(checked) {
                        const next = {};
                        if (checked) {
                            this.bulkSelectableRows.forEach((row) => {
                                next[row.id] = true;
                            });
                        }
                        this.selectedMap = next;
                        this.syncPageCheckboxes();
                    },
                    syncPageCheckboxes() {
                        document.querySelectorAll('.js-acreditado-row-select').forEach((input) => {
                            const id = Number(input.value);
                            input.checked = !! this.selectedMap[id];
                        });
                    },
                    openBulkUpdate() {
                        if (this.selectedCount < 1) {
                            return;
                        }
                        this.bulkForm = { observaciones: '', fecha_solicitud: '' };
                        this.bulkUpdateOpen = true;
                    },
                    closeBulkUpdate() {
                        this.bulkUpdateOpen = false;
                        this.submittingBulk = false;
                    },
                    closeEdit() {
                        this.editOpen = false;
                    },
                    unlockEditIdentity() {
                        this.editIdentityLocked = false;
                        this.editForm.document_number = '';
                        this.editForm.full_name = '';
                    },
                    syncEditCargoApo(value) {
                        this.$nextTick(() => {
                            const wrap = document.querySelector('.js-edit-cargo-apo-select');
                            if (! wrap || ! window.Alpine || typeof window.Alpine.$data !== 'function') {
                                return;
                            }
                            try {
                                const data = window.Alpine.$data(wrap);
                                if (data && 'value' in data) {
                                    data.value = String(value || '');
                                }
                            } catch (e) {}
                        });
                    },
                    openEdit(detail) {
                        this.editForm = {
                            document_number: detail?.document_number || '',
                            full_name: detail?.full_name || '',
                            cargo: detail?.cargo || '',
                            cargo_apo: detail?.cargo_apo || '',
                            vigencia_acr: detail?.vigencia_acr || '',
                            fecha_solicitud: detail?.fecha_solicitud || '',
                            estado: detail?.estado || '',
                            estado_label: detail?.estado_label || detail?.estado || '',
                            observaciones: detail?.observaciones || '',
                            update_url: detail?.update_url || '',
                        };
                        this.editIdentityLocked = Boolean(this.editForm.document_number);
                        this.editOpen = true;
                        this.syncEditCargoApo(this.editForm.cargo_apo);
                    },
                    async lookupName(cedula, mode) {
                        const value = String(cedula || '').trim();
                        if (! value || ! this.lookupUrl) {
                            return;
                        }
                        if (mode === 'edit' && this.editIdentityLocked) {
                            return;
                        }
                        try {
                            const res = await fetch(this.lookupUrl + '?cedula=' + encodeURIComponent(value), {
                                headers: { 'Accept': 'application/json' },
                            });
                            if (! res.ok) {
                                return;
                            }
                            const data = await res.json();
                            if (data.found && data.full_name) {
                                if (mode === 'edit') {
                                    this.editForm.document_number = data.document_number || value;
                                    this.editForm.full_name = data.full_name;
                                    this.editIdentityLocked = true;
                                }
                            }
                        } catch (e) {}
                    },
                };
            }

            document.addEventListener('DOMContentLoaded', function () {
                const form = document.querySelector('[data-acreditaciones-import-form]');
                if (form) {
                    const fileInput = form.querySelector('[data-acreditaciones-import-file]');
                    const fileName = form.querySelector('[data-acreditaciones-import-name]');
                    const submitBtn = form.querySelector('[data-acreditaciones-import-submit]');
                    const loading = document.querySelector('[data-acreditaciones-import-loading]');

                    fileInput?.addEventListener('change', () => {
                        const name = fileInput.files?.[0]?.name || 'Sin archivo seleccionado';
                        if (fileName) {
                            fileName.textContent = name;
                        }
                        if (submitBtn) {
                            submitBtn.disabled = !fileInput.files?.length;
                        }
                    });

                    form.addEventListener('submit', () => {
                        if (loading) {
                            loading.hidden = false;
                        }
                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.setAttribute('aria-busy', 'true');
                            submitBtn.title = 'Importando…';
                            submitBtn.setAttribute('aria-label', 'Importando…');
                        }
                    });
                }

                if (!window.jQuery || !window.jQuery.fn.DataTable) {
                    return;
                }

                const $table = window.jQuery('.js-acreditados-datatable');
                if (!$table.length) {
                    return;
                }

                const wrap = $table.closest('.data-table-wrap');
                const reveal = function () {
                    wrap.removeClass('data-table-wrap--booting');
                };
                const updateMeta = function (count) {
                    const el = document.getElementById('acreditados-count');
                    if (el) {
                        el.textContent = String(count);
                    }
                };

                function getAlpineRoot() {
                    const root = document.querySelector('.acreditaciones-acreditados-page');
                    if (! root || ! window.Alpine) {
                        return null;
                    }
                    return window.Alpine.$data(root);
                }

                function bindRowInteractions() {
                    const alpine = getAlpineRoot();
                    $table.find('.js-acreditado-row-select').each(function () {
                        const id = Number(this.value);
                        if (alpine) {
                            this.checked = !! alpine.isSelected(id);
                        }
                    });
                }

                const canEdit = $table.data('dt-can-edit') === 1 || $table.data('dt-can-edit') === '1';

                const api = $table.DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: { url: $table.data('dt-url') },
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                        emptyTable: 'No hay acreditados para este filtro.',
                    },
                    dom: '<"req-manage-dt-top"lf><"req-manage-table-scroll"t><"req-manage-dt-bottom"ip>',
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    pageLength: 10,
                    responsive: false,
                    order: [[canEdit ? 1 : 0, 'asc']],
                    columnDefs: canEdit
                        ? [{ targets: [0, 9], orderable: false, searchable: false }]
                        : [],
                });

                api.on('xhr.dt', function (_event, _settings, json) {
                    reveal();
                    if (json && typeof json.recordsFiltered !== 'undefined') {
                        updateMeta(json.recordsFiltered);
                    }
                });

                api.on('draw.dt', function () {
                    bindRowInteractions();
                });

                $table.on('change', '.js-acreditado-row-select', function () {
                    const alpine = getAlpineRoot();
                    if (! alpine) {
                        return;
                    }
                    let rowMeta = null;
                    try {
                        rowMeta = JSON.parse(this.getAttribute('data-acreditado-row') || 'null');
                    } catch (e) {
                        rowMeta = null;
                    }
                    alpine.toggleRow(Number(this.value), this.checked, rowMeta);
                });

                $table.on('click', '.js-acreditado-edit', function () {
                    try {
                        const row = JSON.parse(this.getAttribute('data-acreditado-edit') || '{}');
                        window.dispatchEvent(new CustomEvent('acreditaciones-open-edit', { detail: row }));
                    } catch (e) {}
                });
            });
        </script>
    @endpush
</x-app-layout>
