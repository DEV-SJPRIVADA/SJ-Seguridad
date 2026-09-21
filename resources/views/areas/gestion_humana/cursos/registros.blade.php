<x-app-layout>
    @php
        $showNuevoModal = $canEdit && $errors->any() && ! $errors->has('import_file');
        $showMasivosModal = $errors->has('import_file');
    @endphp

    <x-slot name="header">
        @include('areas.gestion_humana.cursos.partials.subnav', ['subTabs' => $subTabs])
        <div class="app-container">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">Cursos</h2>
                <p class="panel-text">Gestion humana — registros de cursos por persona</p>
            </div>
        </div>
    </x-slot>

    <div
        class="page-section cursos-registros-page req-manage-page"
        x-data="cursosRegistros({
            lookupUrl: @js($lookupUrl),
            canEdit: @js($canEdit),
            bulkMarkSolicitadoUrl: @js($bulkMarkSolicitadoUrl ?? null),
            bulkSelectableUrl: @js($bulkSelectableUrl ?? null),
            activeFilterQuery: @js($activeFilterQuery ?? []),
            colaMode: @js($colaMode ?? false),
        })"
        @open-cursos-nuevo-from-pending.window="openCreateFromPending($event.detail)"
        @cursos-open-edit.window="openEdit($event.detail)"
    >
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success cursos-registros-page__alert">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert--danger cursos-registros-page__alert">{{ session('error') }}</div>
            @endif

            @if (session('import_failures'))
                <div class="alert alert--danger cursos-registros-page__alert">
                    <p class="mb-2">Errores de importación (máx. 50 en pantalla):</p>
                    <ul class="mb-2">
                        @foreach (session('import_failures') as $failure)
                            <li>
                                Fila {{ $failure['row'] ?? '?' }}
                                @if (! empty($failure['identifier']))
                                    ({{ $failure['identifier'] }})
                                @endif
                                : {{ $failure['reason'] ?? '' }}
                            </li>
                        @endforeach
                    </ul>
                    @if (session('import_report_token'))
                        <a
                            class="btn btn--secondary btn--sm"
                            href="{{ route('gestion-humana.cursos.registros.import-report', session('import_report_token')) }}"
                        >Descargar reporte</a>
                    @endif
                </div>
            @endif

            <div class="panel cursos-registros-panel">
                <div class="panel__body panel__body--compact req-manage-shell">
                    <div class="req-manage-shell__filters">
                        @unless ($colaMode ?? false)
                        <form method="GET" action="{{ route('gestion-humana.cursos.registros') }}" class="req-manage-filters">
                            <div class="cursos-registros-page__filters">
                                <div class="form-field">
                                    <label class="form-label" for="filter_document_number">Cédula</label>
                                    <input id="filter_document_number" name="document_number" type="text" class="form-input" value="{{ $filters['document_number'] }}">
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_full_name">Nombre</label>
                                    <input id="filter_full_name" name="full_name" type="text" class="form-input" value="{{ $filters['full_name'] }}">
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_curso_tipo_id">Tipo curso</label>
                                    <x-searchable-select
                                        id="filter_curso_tipo_id"
                                        name="curso_tipo_id"
                                        :options="$filterTipoOptions"
                                        :value="$filters['curso_tipo_id']"
                                        placeholder="Todos"
                                        :allow-clear="true"
                                    />
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_vigencia">Vigencia</label>
                                    <x-searchable-select
                                        id="filter_vigencia"
                                        name="vigencia"
                                        :options="$filterVigenciaOptions"
                                        :value="$filters['vigencia']"
                                        placeholder="Todas"
                                        :allow-clear="true"
                                    />
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_estado">Estado</label>
                                    <x-searchable-select
                                        id="filter_estado"
                                        name="estado"
                                        :options="$filterEstadoOptions"
                                        :value="$filters['estado']"
                                        placeholder="Todos"
                                        :allow-clear="false"
                                    />
                                </div>
                                <div class="form-field cursos-registros-page__solo-actualizar">
                                    <label class="form-label" for="filter_solo_actualizar">Solo ACTUALIZAR</label>
                                    <label class="cursos-registros-page__checkbox-label">
                                        <input
                                            id="filter_solo_actualizar"
                                            name="solo_actualizar"
                                            type="checkbox"
                                            value="1"
                                            @checked($filters['solo_actualizar'])
                                        >
                                        Filtrar
                                    </label>
                                </div>
                                <div class="form-field cursos-registros-page__filter-actions">
                                    <button type="submit" class="btn btn--primary btn--sm">Filtrar</button>
                                    <a href="{{ route('gestion-humana.cursos.registros') }}" class="btn btn--secondary btn--sm">Limpiar</a>
                                    <a
                                        href="{{ $exportUrl }}"
                                        class="ficha-empleados-filters__bulk-icon cursos-registros-page__export-icon"
                                        title="Exportar a Excel (respeta filtros)"
                                        aria-label="Exportar a Excel"
                                    >
                                        <x-selfhst-microsoft-excel-2013 width="18" height="18" aria-hidden="true" />
                                    </a>
                                    <button
                                        type="button"
                                        class="ficha-empleados-filters__bulk-icon"
                                        title="Plantilla masivos — importar"
                                        aria-label="Plantilla masivos — importar"
                                        x-on:click.prevent="$dispatch('open-modal', 'cursos-masivos')"
                                    >
                                        <x-lucide-upload width="20" height="20" aria-hidden="true" />
                                    </button>
                                </div>
                            </div>
                        </form>
                        @endunless

                        <div class="cursos-registros-page__table-toolbar">
                            @if ($colaMode ?? false)
                                <p class="req-manage-filters__meta">
                                    {{ $pendingRows->count() }} nuevo(s) sin curso
                                    <a href="{{ $colaExitUrl }}" class="cursos-registros-page__cola-exit">Volver a registros</a>
                                </p>
                            @else
                                <p class="req-manage-filters__meta">
                                    <strong id="cursos-registros-count">…</strong>
                                    <span id="cursos-registros-count-label">registro(s)</span>
                                </p>
                            @endif

                            @if ($canEdit)
                                <div class="cursos-registros-page__table-actions">
                                    <a
                                        href="{{ ($colaMode ?? false) ? $colaExitUrl : $colaQueueUrl }}"
                                        class="ficha-empleados-filters__pending-link cursos-registros-page__nuevos-link {{ ($colaMode ?? false) ? 'is-active' : '' }}"
                                        title="Nuevos sin curso"
                                        aria-label="Nuevos sin curso: {{ number_format($pendingCount ?? 0) }}"
                                    >
                                        <x-ri-pass-pending-fill width="24" height="24" aria-hidden="true" />
                                        <span class="ficha-empleados-filters__pending-count">{{ number_format($pendingCount ?? 0) }}</span>
                                    </a>

                                    @unless ($colaMode ?? false)
                                        <button
                                            type="button"
                                            class="btn btn--primary btn--sm"
                                            x-show="selectedCount > 0"
                                            x-cloak
                                            x-on:click="openBulkConfirm()"
                                        >
                                            Marcar SOLICITADO
                                            (<span x-text="selectedCount"></span>)
                                        </button>
                                        <button
                                            type="button"
                                            class="ficha-empleados-filters__bulk-icon cursos-registros-page__add-btn"
                                            title="Nuevo registro"
                                            aria-label="Nuevo registro"
                                            x-on:click.prevent="openCreateBlank()"
                                        >
                                            <x-lucide-plus width="20" height="20" aria-hidden="true" />
                                        </button>
                                    @endunless
                                </div>
                            @endif
                        </div>
                    </div>

                    @if ($colaMode ?? false)
                        @include('areas.gestion_humana.cursos.partials.nuevos-sin-curso-table', [
                            'pendingRows' => $pendingRows,
                        ])
                    @else
                    <div class="data-table-wrap req-manage-shell__table cursos-registros-page__table-wrap data-table-wrap--booting">
                        @include('partials.data-table-loader')
                        <table
                            id="cursos-registros-datatable"
                            class="data-table js-cursos-registros-datatable"
                            data-dt-url="{{ $datatableUrl }}"
                            data-dt-responsive="false"
                            data-dt-compact="true"
                            data-dt-body-scroll="true"
                            data-dt-can-edit="{{ $canEdit ? '1' : '0' }}"
                            style="width:100%"
                        >
                            <thead>
                                <tr>
                                    @if ($canEdit)
                                        <th class="cursos-registros-page__select-col" data-orderable="false">
                                            <label class="cursos-registros-page__select-label" title="Seleccionar todos los elegibles del filtro actual">
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
                                    <th>TIPO CURSO</th>
                                    <th>ESCUELA</th>
                                    <th>CODIGO</th>
                                    <th>NIT</th>
                                    <th>FECHA EXPEDICION</th>
                                    <th>No.CURSO</th>
                                    <th>VIGENCIA</th>
                                    <th>ESTADO</th>
                                    <th>OBSERVACIONES</th>
                                    <th>DOCUMENTO</th>
                                    @if ($canEdit)
                                        <th>Acciones</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>

            @include('areas.gestion_humana.cursos.partials.masivos-modal', [
                'filters' => $filters,
                'canEdit' => $canEdit,
                'exportUrl' => $exportUrl,
                'importTemplateUrl' => $importTemplateUrl,
                'importUrl' => $importUrl,
                'show' => $showMasivosModal,
            ])

            @if ($canEdit)
                @include('areas.gestion_humana.cursos.partials.nuevo-modal', [
                    'tipoOptions' => $tipoOptions,
                    'escuelaOptions' => $escuelaOptions,
                    'estadoOptions' => $estadoOptions,
                    'lookupUrl' => $lookupUrl,
                    'show' => $showNuevoModal,
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
                                <div class="cursos-registros-page__form-grid">
                                    <div class="form-field">
                                        <label class="form-label" for="edit_document_number">CEDULA</label>
                                        <input
                                            id="edit_document_number"
                                            name="document_number"
                                            type="text"
                                            class="form-input"
                                            maxlength="50"
                                            required
                                            x-model="editForm.document_number"
                                            @blur="lookupName($event.target.value, 'edit')"
                                        >
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_full_name">NOMBRE COMPLETO</label>
                                        <input
                                            id="edit_full_name"
                                            name="full_name"
                                            type="text"
                                            class="form-input"
                                            maxlength="255"
                                            required
                                            x-model="editForm.full_name"
                                        >
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_curso_tipo_id">TIPO CURSO</label>
                                        <select id="edit_curso_tipo_id" name="curso_tipo_id" class="form-input" required x-model="editForm.curso_tipo_id">
                                            @foreach ($tipoOptions as $opt)
                                                <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_curso_escuela_id">ESCUELA</label>
                                        <select id="edit_curso_escuela_id" name="curso_escuela_id" class="form-input" required x-model="editForm.curso_escuela_id">
                                            <option value="">Seleccionar escuela</option>
                                            @foreach ($escuelaOptions as $opt)
                                                <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_fecha_expedicion">FECHA EXPEDICION</label>
                                        <input
                                            id="edit_fecha_expedicion"
                                            name="fecha_expedicion"
                                            type="date"
                                            class="form-input"
                                            required
                                            x-model="editForm.fecha_expedicion"
                                        >
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_numero_curso">No.CURSO</label>
                                        <input
                                            id="edit_numero_curso"
                                            name="numero_curso"
                                            type="text"
                                            class="form-input"
                                            maxlength="100"
                                            required
                                            x-model="editForm.numero_curso"
                                        >
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_estado">ESTADO</label>
                                        <select id="edit_estado" name="estado" class="form-input" x-model="editForm.estado" required>
                                            <option value="SOLICITADO">SOLICITADO</option>
                                            <option value="ACTUALIZADO">ACTUALIZADO</option>
                                            <option value="PENDIENTE">PENDIENTE</option>
                                        </select>
                                    </div>
                                    <div class="form-field cursos-registros-page__form-span">
                                        <label class="form-label" for="edit_observaciones">OBSERVACIONES</label>
                                        <textarea
                                            id="edit_observaciones"
                                            name="observaciones"
                                            class="form-input"
                                            rows="2"
                                            x-model="editForm.observaciones"
                                        ></textarea>
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
                    x-show="omitOpen"
                    x-cloak
                    @keydown.escape.window="closeOmit()"
                >
                    <div class="cursos-registros-page__modal-backdrop" @click="closeOmit()"></div>
                    <div
                        class="cursos-registros-page__modal-panel panel cursos-registros-page__omit-modal"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="cursos-omit-title"
                    >
                        <div class="panel__header panel-heading-row">
                            <h3 id="cursos-omit-title" class="panel-title">No aplica / omitir</h3>
                            <button type="button" class="btn btn--ghost btn--sm" @click="closeOmit()">Cerrar</button>
                        </div>
                        <div class="panel__body">
                            <p class="panel-text">
                                Se omitirá a
                                <strong x-text="omitForm.full_name || omitForm.document_number"></strong>
                                (cédula <span x-text="omitForm.document_number"></span>) de la cola «Nuevos sin curso».
                                Esta acción no se puede deshacer en V1.
                            </p>
                            <form
                                method="POST"
                                :action="omitForm.omit_url"
                                class="cursos-registros-page__form"
                                x-on:submit="submittingOmit = true"
                            >
                                @csrf
                                <div class="form-field">
                                    <label class="form-label" for="omit_reason">Motivo (opcional)</label>
                                    <textarea
                                        id="omit_reason"
                                        name="omit_reason"
                                        class="form-input"
                                        rows="3"
                                        maxlength="1000"
                                        x-model="omitForm.omit_reason"
                                        placeholder="Ej. No requiere curso por rol"
                                    ></textarea>
                                </div>
                                <div class="cursos-registros-page__form-actions">
                                    <button type="button" class="btn btn--secondary" @click="closeOmit()" :disabled="submittingOmit">
                                        Cancelar
                                    </button>
                                    <button type="submit" class="btn btn--primary" :disabled="submittingOmit">
                                        <span x-show="! submittingOmit">Confirmar omitir</span>
                                        <span x-show="submittingOmit" x-cloak>Omitiendo…</span>
                                    </button>
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
                    <div
                        class="cursos-registros-page__modal-panel panel cursos-registros-page__bulk-modal"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="cursos-bulk-solicitado-title"
                    >
                        <div class="panel__header panel-heading-row">
                            <h3 id="cursos-bulk-solicitado-title" class="panel-title">Confirmar marcado a SOLICITADO</h3>
                            <button type="button" class="btn btn--ghost btn--sm" @click="closeBulkConfirm()">Cerrar</button>
                        </div>
                        <div class="panel__body">
                            <div class="alert alert--danger cursos-registros-page__bulk-warning">
                                Esta acción <strong>no se puede revertir</strong> desde el marcado masivo.
                                El estado quedará en <strong>SOLICITADO</strong> y el proceso diario de sincronización lo conservará.
                                Solo podrá cambiarlo editando cada registro de forma individual.
                            </div>

                            <p class="panel-text cursos-registros-page__bulk-summary">
                                Se actualizarán <strong x-text="selectedCount"></strong> registro(s):
                            </p>

                            <div class="cursos-registros-page__bulk-list-wrap">
                                <table class="data-table cursos-registros-page__bulk-list">
                                    <thead>
                                        <tr>
                                            <th>Cédula</th>
                                            <th>Nombre</th>
                                            <th>Tipo</th>
                                            <th>No.CURSO</th>
                                            <th>Vigencia</th>
                                            <th>Estado actual</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="row in selectedRows" :key="row.id">
                                            <tr>
                                                <td x-text="row.document_number"></td>
                                                <td x-text="row.full_name"></td>
                                                <td x-text="row.tipo_curso"></td>
                                                <td x-text="row.numero_curso"></td>
                                                <td x-text="row.vigencia"></td>
                                                <td x-text="row.estado"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <form
                                method="POST"
                                :action="bulkMarkSolicitadoUrl"
                                class="cursos-registros-page__bulk-form"
                                x-on:submit="submittingBulk = true"
                            >
                                @csrf
                                <template x-for="id in selectedIds" :key="'bulk-id-' + id">
                                    <input type="hidden" name="ids[]" :value="id">
                                </template>
                                <template x-for="(value, key) in activeFilterQuery" :key="'filter-' + key">
                                    <input type="hidden" :name="key" :value="value">
                                </template>

                                <label class="cursos-registros-page__bulk-confirm-label">
                                    <input type="checkbox" name="confirmed" value="1" x-model="bulkConfirmAccepted">
                                    Confirmo que revisé el listado y estoy seguro de ejecutar el cambio a SOLICITADO.
                                </label>

                                <div class="cursos-registros-page__form-actions">
                                    <button type="button" class="btn btn--secondary" @click="closeBulkConfirm()" :disabled="submittingBulk">
                                        Cancelar
                                    </button>
                                    <button
                                        type="submit"
                                        class="btn btn--primary"
                                        :disabled="! bulkConfirmAccepted || selectedCount < 1 || submittingBulk"
                                    >
                                        <span x-show="! submittingBulk">Ejecutar cambio</span>
                                        <span x-show="submittingBulk" x-cloak>Ejecutando…</span>
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
            function cursosRegistros(config) {
                return {
                    lookupUrl: config.lookupUrl,
                    canEdit: config.canEdit,
                    bulkMarkSolicitadoUrl: config.bulkMarkSolicitadoUrl || '',
                    bulkSelectableUrl: config.bulkSelectableUrl || '',
                    activeFilterQuery: config.activeFilterQuery || {},
                    bulkSelectableRows: [],
                    bulkSelectableLoading: false,
                    colaMode: !! config.colaMode,
                    selectedMap: {},
                    bulkConfirmOpen: false,
                    bulkConfirmAccepted: false,
                    submittingBulk: false,
                    omitOpen: false,
                    submittingOmit: false,
                    omitForm: {
                        id: null,
                        document_number: '',
                        full_name: '',
                        omit_url: '',
                        omit_reason: '',
                    },
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
                        if (this.canEdit && this.bulkSelectableUrl && ! this.colaMode) {
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
                        document.querySelectorAll('.js-curso-row-select').forEach((input) => {
                            const id = Number(input.value);
                            input.checked = !! this.selectedMap[id];
                        });
                    },
                    openBulkConfirm() {
                        if (this.selectedCount < 1) {
                            return;
                        }
                        this.bulkConfirmAccepted = false;
                        this.submittingBulk = false;
                        this.bulkConfirmOpen = true;
                        this.editOpen = false;
                        this.omitOpen = false;
                    },
                    closeBulkConfirm() {
                        if (this.submittingBulk) {
                            return;
                        }
                        this.bulkConfirmOpen = false;
                        this.bulkConfirmAccepted = false;
                    },
                    openOmit(row) {
                        this.omitForm = {
                            id: row.id,
                            document_number: row.document_number || '',
                            full_name: row.full_name || '',
                            omit_url: row.omit_url || '',
                            omit_reason: '',
                        };
                        this.submittingOmit = false;
                        this.omitOpen = true;
                        this.editOpen = false;
                        this.bulkConfirmOpen = false;
                    },
                    closeOmit() {
                        if (this.submittingOmit) {
                            return;
                        }
                        this.omitOpen = false;
                    },
                    openCreateBlank() {
                        window.dispatchEvent(new CustomEvent('cursos-nuevo-reset'));
                        this.$dispatch('open-modal', 'cursos-nuevo');
                    },
                    openCreateFromPending(row) {
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
                        this.omitOpen = false;
                    },
                    async lookupName(cedula, target) {
                        const value = String(cedula || '').trim();
                        if (!value || !this.canEdit) {
                            return;
                        }

                        try {
                            const res = await fetch(this.lookupUrl + '?cedula=' + encodeURIComponent(value), {
                                headers: { 'Accept': 'application/json' },
                            });
                            if (!res.ok) {
                                return;
                            }
                            const data = await res.json();
                            if (!data.found || !data.full_name) {
                                return;
                            }
                            if (target === 'edit') {
                                this.editForm.full_name = data.full_name;
                            }
                        } catch (e) {
                            // ignore lookup errors
                        }
                    },
                };
            }

            document.addEventListener('DOMContentLoaded', () => {
                const form = document.querySelector('[data-cursos-import-form]');
                if (form) {
                    const fileInput = form.querySelector('[data-cursos-import-file]');
                    const fileName = form.querySelector('[data-cursos-import-name]');
                    const submitBtn = form.querySelector('[data-cursos-import-submit]');
                    const loading = document.querySelector('[data-cursos-import-loading]');

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
                            submitBtn.innerHTML = '<span class="ficha-empleados-masivos-modal__btn-spinner" aria-hidden="true"></span> Importando…';
                        }
                    });
                }

                const $table = window.jQuery ? window.jQuery('#cursos-registros-datatable') : null;
                if (! $table || ! $table.length || typeof window.jQuery.fn.DataTable === 'undefined') {
                    return;
                }

                function revealCursosTableWrap() {
                    $table.closest('.data-table-wrap').removeClass('data-table-wrap--booting');
                }

                function updateCursosEntriesMeta(recordsFiltered) {
                    const countEl = document.getElementById('cursos-registros-count');
                    const labelEl = document.getElementById('cursos-registros-count-label');
                    if (! countEl || ! labelEl) {
                        return;
                    }
                    const count = Number(recordsFiltered) || 0;
                    countEl.textContent = count.toLocaleString('es-CO');
                    labelEl.textContent = count === 1 ? 'registro' : 'registros';
                }

                function getAlpineRoot() {
                    const root = document.querySelector('.cursos-registros-page');
                    if (! root || ! window.Alpine) {
                        return null;
                    }
                    return window.Alpine.$data(root);
                }

                function bindRowInteractions() {
                    const alpine = getAlpineRoot();

                    $table.find('.js-curso-row-select').each(function () {
                        const id = Number(this.value);
                        if (alpine) {
                            this.checked = !! alpine.isSelected(id);
                        }
                    });

                    $table.find('[data-curso-upload]').each(function () {
                        const uploadForm = this;
                        const input = uploadForm.querySelector('input[type="file"]');
                        const nameEl = uploadForm.querySelector('[data-curso-upload-name]');
                        if (! input || ! nameEl) {
                            return;
                        }
                        input.addEventListener('change', () => {
                            nameEl.textContent = input.files?.[0]?.name || 'Sin archivo';
                        });
                    });
                }

                if (window.jQuery.fn.DataTable.isDataTable($table[0])) {
                    $table.DataTable().destroy();
                }

                $table.closest('.req-manage-shell__table, .data-table-wrap').addClass('data-table-wrap--dt-compact');

                const canEdit = $table.data('dt-can-edit') === 1 || $table.data('dt-can-edit') === '1';
                const columnDefs = [
                    { targets: canEdit ? [0, 12, 13] : [11], orderable: false, searchable: false },
                    { targets: canEdit ? [9] : [8], orderable: false },
                ];

                const api = $table.DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: $table.data('dt-url'),
                    },
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                        emptyTable: 'No hay registros para este filtro.',
                    },
                    dom: '<"req-manage-dt-top"lf><"req-manage-table-scroll"t><"req-manage-dt-bottom"ip>',
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    pageLength: 10,
                    responsive: false,
                    order: [[canEdit ? 7 : 6, 'desc']],
                    columnDefs: columnDefs,
                });

                api.on('xhr.dt', function (_event, _settings, json) {
                    revealCursosTableWrap();
                    if (json && typeof json.recordsFiltered !== 'undefined') {
                        updateCursosEntriesMeta(json.recordsFiltered);
                    }
                });

                api.on('draw.dt', function () {
                    bindRowInteractions();
                });

                $table.on('change', '.js-curso-row-select', function () {
                    const alpine = getAlpineRoot();
                    if (! alpine) {
                        return;
                    }
                    let rowMeta = null;
                    try {
                        rowMeta = JSON.parse(this.getAttribute('data-curso-row') || 'null');
                    } catch (e) {
                        rowMeta = null;
                    }
                    alpine.toggleRow(Number(this.value), this.checked, rowMeta);
                });

                $table.on('click', '.js-curso-edit', function () {
                    try {
                        const row = JSON.parse(this.getAttribute('data-curso-edit') || '{}');
                        window.dispatchEvent(new CustomEvent('cursos-open-edit', { detail: row }));
                    } catch (e) {
                        // ignore malformed payload
                    }
                });

                (function setupCursosTableScroll() {
                    const $shell = $table.closest('.req-manage-shell');
                    const $wrapper = $table.closest('.dataTables_wrapper');
                    const $scroll = $wrapper.find('.req-manage-table-scroll').first();
                    const $bottom = $wrapper.find('.req-manage-dt-bottom').first();

                    if (! $scroll.length) {
                        return;
                    }

                    const updateScrollArea = function () {
                        const bottomHeight = $bottom.outerHeight(true) || 0;
                        const rect = $scroll[0].getBoundingClientRect();
                        const maxHeight = window.innerHeight - rect.top - bottomHeight - 16;
                        $scroll.css('max-height', Math.max(220, maxHeight) + 'px');
                    };

                    const debounce = function (fn, wait) {
                        let timer = null;
                        return function () {
                            if (timer) {
                                clearTimeout(timer);
                            }
                            timer = setTimeout(fn, wait);
                        };
                    };

                    updateScrollArea();
                    setTimeout(updateScrollArea, 200);
                    api.on('draw.dt-table-scroll', updateScrollArea);
                    window.jQuery(window).on('resize orientationchange', debounce(updateScrollArea, 100));

                    const $filtersPanel = $shell.find('.req-manage-shell__filters').first();
                    if ($filtersPanel.length && typeof ResizeObserver !== 'undefined') {
                        new ResizeObserver(debounce(updateScrollArea, 50)).observe($filtersPanel[0]);
                    }
                })();
            });
        </script>
    @endpush
</x-app-layout>
