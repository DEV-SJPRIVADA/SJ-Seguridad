<x-app-layout>
    @php
        // Abrir modal Nuevo solo por errores de CRUD; import usa showImportModal del controller.
        $showNuevoModal = $canEdit && $errors->any() && ! $errors->has('import_file');
        $hasActiveFilters = ($filters['document_number'] ?? '') !== ''
            || ($filters['full_name'] ?? '') !== ''
            || ($filters['q'] ?? '') !== ''
            || (($filters['estado_1'] ?? 'todos') !== '' && ($filters['estado_1'] ?? 'todos') !== 'todos')
            || (($filters['estado_2'] ?? 'todos') !== '' && ($filters['estado_2'] ?? 'todos') !== 'todos')
            || (($filters['arma'] ?? 'todos') !== '' && ($filters['arma'] ?? 'todos') !== 'todos')
            || (($filters['apto'] ?? 'todos') !== '' && ($filters['apto'] ?? 'todos') !== 'todos')
            || (($filters['ciudad'] ?? 'todos') !== '' && ($filters['ciudad'] ?? 'todos') !== 'todos')
            || (($filters['ficha_estado'] ?? 'activo') !== '' && ($filters['ficha_estado'] ?? 'activo') !== 'activo');
    @endphp

    <x-slot name="header">
        @include('areas.gestion_humana.mt_st_04.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Matriz operativa: filtros + DataTables server-side + CRUD modal --}}
    <div
        class="page-section mt-st-04-page mt-st-04-matriz-page req-manage-page cursos-registros-page"
        x-data="mtSt04Matriz({
            lookupUrl: @js($lookupUrl),
            canEdit: @js($canEdit),
        })"
        @mt-st-04-open-edit.window="openEdit($event.detail)"
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
                    <details class="req-manage-shell__filters req-manage-filters req-manage-filters__panel" @if ($hasActiveFilters) open @endif>
                        <summary class="req-manage-filters__panel-toggle">
                            <span>Filtros</span>
                            @if ($hasActiveFilters)
                                <span class="req-manage-filters__panel-badge">Activos</span>
                            @endif
                        </summary>
                        <div class="req-manage-filters__panel-body">
                            <form method="GET" action="{{ route('gestion-humana.mt-st-04.matriz') }}" class="req-manage-filters">
                                <div class="cursos-registros-page__filters">
                                    <div class="form-field">
                                        <label class="form-label" for="filter_q">Buscar (cédula / nombre)</label>
                                        <input id="filter_q" name="q" type="text" class="form-input" value="{{ $filters['q'] }}" placeholder="Cédula o nombre">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_estado_1">Estado psicofísico</label>
                                        <x-searchable-select
                                            id="filter_estado_1"
                                            name="estado_1"
                                            :options="$filterEstado1Options"
                                            :value="$filters['estado_1']"
                                            placeholder="Todos"
                                            :allow-clear="false"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_estado_2">Estado psicosensométrico</label>
                                        <x-searchable-select
                                            id="filter_estado_2"
                                            name="estado_2"
                                            :options="$filterEstado2Options"
                                            :value="$filters['estado_2']"
                                            placeholder="Todos"
                                            :allow-clear="false"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_arma">Arma</label>
                                        <x-searchable-select
                                            id="filter_arma"
                                            name="arma"
                                            :options="$filterArmaOptions"
                                            :value="$filters['arma']"
                                            placeholder="Todos"
                                            :allow-clear="false"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_apto">Apto</label>
                                        <x-searchable-select
                                            id="filter_apto"
                                            name="apto"
                                            :options="$filterAptoOptions"
                                            :value="$filters['apto']"
                                            placeholder="Todos"
                                            :allow-clear="false"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_ciudad">Ciudad</label>
                                        <x-searchable-select
                                            id="filter_ciudad"
                                            name="ciudad"
                                            :options="$filterCiudadOptions"
                                            :value="$filters['ciudad']"
                                            placeholder="Todas"
                                            search-placeholder="Buscar ciudad…"
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
                                            href="{{ route('gestion-humana.mt-st-04.matriz') }}"
                                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                            title="Limpiar filtros"
                                            aria-label="Limpiar filtros"
                                        >
                                            <x-lucide-x width="18" height="18" aria-hidden="true" />
                                        </a>
                                    </div>
                                </div>
                                <p class="panel-text" style="margin-top:0.35rem;font-size:0.85rem;">
                                    Por defecto solo se listan empleados <strong>activos en ficha</strong>.
                                    Use «Estado en ficha» para ver desvinculados o todos.
                                </p>
                            </form>
                        </div>
                    </details>

                    <div class="cursos-registros-page__table-toolbar">
                        <p class="req-manage-filters__meta">
                            <strong id="mt-st-04-count">…</strong>
                            <span>registro(s)</span>
                        </p>

                        <div class="cursos-registros-page__table-actions">
                            <x-export-excel
                                route="{{ $exportUrl }}"
                                label=""
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                title="Exportar a Excel (respeta filtros)"
                                aria-label="Exportar a Excel"
                            />
                            @if ($canEdit)
                                <button
                                    type="button"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Plantilla e importar"
                                    aria-label="Plantilla e importar"
                                    x-on:click.prevent="$dispatch('open-modal', 'mt-st-04-import')"
                                >
                                    <x-lucide-upload width="18" height="18" aria-hidden="true" />
                                </button>
                                <button
                                    type="button"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                    title="Nuevo registro"
                                    aria-label="Nuevo registro"
                                    x-on:click.prevent="$dispatch('open-modal', 'mt-st-04-nuevo')"
                                >
                                    <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="data-table-wrap req-manage-shell__table cursos-registros-page__table-wrap data-table-wrap--booting">
                        @include('partials.data-table-loader')
                        <table
                            id="mt-st-04-datatable"
                            class="data-table js-mt-st-04-datatable"
                            data-dt-url="{{ $datatableUrl }}"
                            data-dt-can-edit="{{ $canEdit ? '1' : '0' }}"
                            style="width:100%"
                            aria-label="Matriz MT-ST-04"
                        >
                            {{-- Cabecera en dos filas: identidad + grupos psicofísico / psicosensométrico --}}
                            <thead>
                                <tr>
                                    <th rowspan="2">Cédula</th>
                                    <th rowspan="2">Nombre</th>
                                    <th rowspan="2">Cargo</th>
                                    <th rowspan="2">Ciudad</th>
                                    <th rowspan="2">Puesto</th>
                                    <th colspan="6" class="mt-st-04-th-group mt-st-04-th-group--psico">Psicofísico (armas)</th>
                                    <th colspan="3" class="mt-st-04-th-group mt-st-04-th-group--senso">Psicosensométrico (vial)</th>
                                    @if ($canEdit)
                                        <th rowspan="2">Acciones</th>
                                    @endif
                                </tr>
                                <tr>
                                    <th class="mt-st-04-th-sub mt-st-04-th-sub--psico">Arma</th>
                                    <th class="mt-st-04-th-sub mt-st-04-th-sub--psico">Fecha examen</th>
                                    <th class="mt-st-04-th-sub mt-st-04-th-sub--psico">Vencimiento</th>
                                    <th class="mt-st-04-th-sub mt-st-04-th-sub--psico">Apto</th>
                                    <th class="mt-st-04-th-sub mt-st-04-th-sub--psico">Estado</th>
                                    <th class="mt-st-04-th-sub mt-st-04-th-sub--psico">Observaciones</th>
                                    <th class="mt-st-04-th-sub mt-st-04-th-sub--senso">Fecha examen</th>
                                    <th class="mt-st-04-th-sub mt-st-04-th-sub--senso">Vencimiento</th>
                                    <th class="mt-st-04-th-sub mt-st-04-th-sub--senso">Estado</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if ($canEdit)
                @include('areas.gestion_humana.mt_st_04.partials.nuevo-modal', [
                    'lookupUrl' => $lookupUrl,
                    'siNoOptions' => $siNoOptions,
                    'show' => $showNuevoModal,
                ])
                @include('areas.gestion_humana.mt_st_04.partials.editar-modal', [
                    'siNoOptions' => $siNoOptions,
                ])
                @include('areas.gestion_humana.mt_st_04.partials.import-modal', [
                    'importTemplateUrl' => $importTemplateUrl,
                    'importUrl' => $importUrl,
                    'show' => $showImportModal ?? false,
                ])
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            function mtSt04Matriz(config) {
                return {
                    lookupUrl: config.lookupUrl,
                    canEdit: !!config.canEdit,
                    editOpen: false,
                    editIdentityLocked: true,
                    editForm: {
                        document_number: '',
                        full_name: '',
                        cargo: '',
                        ciudad: '',
                        puesto: '',
                        arma: '',
                        fecha_examen_1: '',
                        fecha_vencimiento_1: '',
                        apto: '',
                        observaciones_1: '',
                        estado_1: '',
                        fecha_examen_2: '',
                        fecha_vencimiento_2: '',
                        observaciones_2: '',
                        estado_2: '',
                        update_url: '',
                    },
                    editLookupHint: '',
                    async lookupFicha(cedula, mode) {
                        const value = String(cedula || '').trim();
                        if (!value) return;
                        if (mode === 'edit' && this.editIdentityLocked) return;
                        try {
                            const res = await fetch(this.lookupUrl + '?cedula=' + encodeURIComponent(value), {
                                headers: { 'Accept': 'application/json' },
                            });
                            if (!res.ok) return;
                            const data = await res.json();
                            if (mode === 'edit') {
                                if (data.found) {
                                    this.editForm.document_number = data.document_number || value;
                                    this.editForm.full_name = data.full_name || '';
                                    this.editForm.cargo = data.cargo || '';
                                    this.editForm.ciudad = data.ciudad || '';
                                    this.editForm.puesto = data.puesto || '';
                                    this.editLookupHint = '';
                                } else {
                                    this.editForm.document_number = value;
                                    this.editForm.full_name = '';
                                    this.editForm.cargo = '';
                                    this.editForm.ciudad = '';
                                    this.editForm.puesto = '';
                                    this.editLookupHint = 'Sin Ficha empleados: se puede guardar igual; aparecerá marcado en la matriz.';
                                }
                                this.editIdentityLocked = true;
                            }
                        } catch (e) {}
                    },
                    syncEditSelect(selector, value) {
                        this.$nextTick(() => {
                            const wrap = document.querySelector(selector);
                            if (!wrap || !window.Alpine || typeof window.Alpine.$data !== 'function') {
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
                    syncEditSelects() {
                        this.syncEditSelect('.js-edit-arma-select', this.editForm.arma);
                        this.syncEditSelect('.js-edit-apto-select', this.editForm.apto);
                    },
                    openEdit(row) {
                        this.editForm = {
                            document_number: row.document_number || '',
                            full_name: row.full_name || '',
                            cargo: row.cargo || '',
                            ciudad: row.ciudad || '',
                            puesto: row.puesto || '',
                            arma: row.arma || '',
                            fecha_examen_1: row.fecha_examen_1 || '',
                            fecha_vencimiento_1: row.fecha_vencimiento_1 || '',
                            apto: row.apto || '',
                            observaciones_1: row.observaciones_1 || '',
                            estado_1: row.estado_1 || '',
                            fecha_examen_2: row.fecha_examen_2 || '',
                            fecha_vencimiento_2: row.fecha_vencimiento_2 || '',
                            observaciones_2: row.observaciones_2 || '',
                            estado_2: row.estado_2 || '',
                            update_url: row.update_url || '',
                        };
                        this.editIdentityLocked = Boolean(this.editForm.document_number);
                        this.editLookupHint = row.sin_ficha
                            ? 'Sin Ficha empleados: se puede guardar igual; aparecerá marcado en la matriz.'
                            : '';
                        this.editOpen = true;
                        this.syncEditSelects();
                    },
                    closeEdit() {
                        this.editOpen = false;
                        this.editLookupHint = '';
                    },
                    unlockEditIdentity() {
                        this.editIdentityLocked = false;
                        this.editLookupHint = '';
                        this.editForm.document_number = '';
                        this.editForm.full_name = '';
                        this.editForm.cargo = '';
                        this.editForm.ciudad = '';
                        this.editForm.puesto = '';
                    },
                };
            }

            document.addEventListener('DOMContentLoaded', function () {
                if (!window.jQuery || !window.jQuery.fn.DataTable) {
                    return;
                }

                const $table = window.jQuery('.js-mt-st-04-datatable');
                if (!$table.length) {
                    return;
                }

                const wrap = $table.closest('.data-table-wrap');
                const reveal = function () {
                    wrap.removeClass('data-table-wrap--booting');
                };
                const updateMeta = function (count) {
                    const el = document.getElementById('mt-st-04-count');
                    if (el) {
                        el.textContent = String(count);
                    }
                };

                const canEdit = $table.data('dt-can-edit') === 1 || $table.data('dt-can-edit') === '1';
                // Orden de datos: 0–4 identidad, 5–10 psicofísico (obs incluida), 11–13 psicosensométrico, 14 acciones.
                const columnDefs = canEdit
                    ? [{ targets: [14], orderable: false, searchable: false }]
                    : [];

                const tableEl = $table.get(0);
                // Mide altura de la fila de grupos para anclar la 2ª fila sticky debajo.
                const syncStickyHeaderOffset = function () {
                    const scrollWrap = tableEl?.closest('.req-manage-table-scroll');
                    const groupCell = tableEl?.querySelector('thead .mt-st-04-th-group');
                    if (!scrollWrap || !groupCell) {
                        return;
                    }
                    const height = Math.ceil(groupCell.getBoundingClientRect().height);
                    if (height > 0) {
                        scrollWrap.style.setProperty('--mt-st-04-sticky-sub-top', height + 'px');
                    }
                };

                const api = $table.DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: { url: $table.data('dt-url') },
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                        emptyTable: 'No hay registros para este filtro.',
                    },
                    dom: '<"req-manage-dt-top"lf><"req-manage-table-scroll"t><"req-manage-dt-bottom"ip>',
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    pageLength: 10,
                    responsive: false,
                    orderCellsTop: true,
                    order: [[0, 'asc']],
                    columnDefs: columnDefs,
                    // Tinte de celdas por grupo de examen (psicofísico / psicosensométrico).
                    createdRow: function (row) {
                        const cells = row.querySelectorAll('td');
                        [5, 6, 7, 8, 9, 10].forEach(function (i) {
                            if (cells[i]) {
                                cells[i].classList.add('mt-st-04-td--psico');
                            }
                        });
                        [11, 12, 13].forEach(function (i) {
                            if (cells[i]) {
                                cells[i].classList.add('mt-st-04-td--senso');
                            }
                        });
                    },
                });

                api.on('xhr.dt', function (_event, _settings, json) {
                    reveal();
                    if (json && typeof json.recordsFiltered !== 'undefined') {
                        updateMeta(json.recordsFiltered);
                    }
                    syncStickyHeaderOffset();
                });

                api.on('draw.dt', syncStickyHeaderOffset);
                window.addEventListener('resize', syncStickyHeaderOffset);
                syncStickyHeaderOffset();

                $table.on('click', '.js-mt-st-04-edit', function () {
                    try {
                        const row = JSON.parse(this.getAttribute('data-mt-st-04-edit') || '{}');
                        window.dispatchEvent(new CustomEvent('mt-st-04-open-edit', { detail: row }));
                    } catch (e) {}
                });

                // Habilitar submit del modal de import cuando hay archivo seleccionado.
                const importForm = document.querySelector('[data-mt-st-04-import-form]');
                if (importForm) {
                    const fileInput = importForm.querySelector('[data-mt-st-04-import-file]');
                    const fileName = importForm.querySelector('[data-mt-st-04-import-name]');
                    const submitBtn = importForm.querySelector('[data-mt-st-04-import-submit]');
                    const loading = document.querySelector('[data-mt-st-04-import-loading]');

                    fileInput?.addEventListener('change', () => {
                        const name = fileInput.files?.[0]?.name || 'Sin archivo seleccionado';
                        if (fileName) {
                            fileName.textContent = name;
                        }
                        if (submitBtn) {
                            submitBtn.disabled = !fileInput.files?.length;
                        }
                    });

                    importForm.addEventListener('submit', () => {
                        if (loading) {
                            loading.hidden = false;
                        }
                        if (submitBtn) {
                            submitBtn.disabled = true;
                        }
                    });
                }
            });
        </script>
    @endpush
</x-app-layout>
