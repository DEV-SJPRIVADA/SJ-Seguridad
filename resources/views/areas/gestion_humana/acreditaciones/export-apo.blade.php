<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.acreditaciones.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div
        class="page-section cursos-registros-page req-manage-page acreditaciones-export-apo-page"
        x-data="acreditacionesExportApo({
            previewUrl: @js($previewUrl),
            generateUrl: @js($generateUrl),
            defaultVigenciaPolicy: @js($defaultVigenciaPolicy),
            csrfToken: @js(csrf_token()),
            autoValidateIds: @js($autoValidateIds ?? []),
            lookupUrl: @js($lookupUrl ?? null),
            bulkUpdateUrl: @js($bulkUpdateUrl ?? null),
            settings: @js([
                'nit' => $exportApoSettings->nit,
                'razon_social' => $exportApoSettings->razon_social,
                'tipo_documento' => $exportApoSettings->tipo_documento,
                'tipo_establecimiento' => $exportApoSettings->tipo_establecimiento,
                'telefono_r' => $exportApoSettings->telefono_r,
                'direccion_r' => $exportApoSettings->direccion_r,
                'direccion_p' => $exportApoSettings->direccion_p,
                'departamento' => $exportApoSettings->departamento,
                'ciudad' => $exportApoSettings->ciudad,
                'educacion_bm' => $exportApoSettings->educacion_bm,
                'educacion_s' => $exportApoSettings->educacion_s,
                'discapacidad' => $exportApoSettings->discapacidad,
            ]),
        })"
        x-on:export-apo-generate.window="submitGenerate($event.detail.include)"
        @acreditaciones-open-edit.window="openEdit($event.detail)"
    >
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success cursos-registros-page__alert">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert--danger cursos-registros-page__alert">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert--danger cursos-registros-page__alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="panel cursos-registros-panel">
                <div class="panel__body panel__body--compact req-manage-shell">
                    <div class="req-manage-shell__filters">
                        <div class="req-manage-filters">
                            <div class="cursos-registros-page__filters">
                                <div class="form-field">
                                    <label class="form-label" for="vigencia_policy">Política vigencia curso</label>
                                    <x-searchable-select
                                        id="vigencia_policy"
                                        name="vigencia_policy"
                                        :options="$vigenciaPolicyOptions"
                                        :value="$defaultVigenciaPolicy"
                                        placeholder="Seleccionar"
                                        :allow-clear="false"
                                        :required="true"
                                    />
                                </div>
                                <div class="form-field cursos-registros-page__filter-actions">
                                    <button
                                        type="button"
                                        class="btn btn--primary btn--sm"
                                        x-bind:disabled="previewLoading || generateLoading"
                                        x-on:click="runValidate()"
                                        title="Validar candidatos del universo Export Apo"
                                    >
                                        <x-lucide-eye width="16" height="16" aria-hidden="true" />
                                        <span x-text="previewLoading ? 'Validando…' : 'Validar'"></span>
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn--primary btn--sm"
                                        x-show="hasValidated"
                                        x-cloak
                                        x-bind:disabled="selectedCount === 0 || previewLoading || generateLoading"
                                        x-on:click="openGenerateModal()"
                                        title="Generar archivo APO .xls"
                                    >
                                        <x-lucide-download width="16" height="16" aria-hidden="true" />
                                        <span x-text="generateLoading ? 'Generando…' : 'Generar .xls'"></span>
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn--primary btn--sm"
                                        x-show="hasValidated && selectedCount > 0"
                                        x-cloak
                                        x-bind:disabled="previewLoading || generateLoading || submittingBulk"
                                        x-on:click="openBulkUpdate()"
                                        title="Actualizar seleccionados"
                                    >
                                        <x-lucide-list-checks width="16" height="16" aria-hidden="true" />
                                        <span>Actualizar</span>
                                        <span x-text="'(' + selectedCount + ')'"></span>
                                    </button>
                                    <button
                                        type="button"
                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                        title="Limpiar resultados"
                                        aria-label="Limpiar"
                                        x-on:click="clearAll()"
                                    >
                                        <x-lucide-x width="18" height="18" aria-hidden="true" />
                                    </button>
                                </div>
                            </div>
                            <p class="panel-text" style="margin-top:0.35rem;font-size:0.85rem;">
                                Pulse <strong>Validar</strong> para cargar candidatos (<strong>EN PROCESO</strong>, <strong>POR VENCER</strong>, <strong>DESACREDITADO</strong> con ficha activa)
                                en columnas SuperVigilancia. Marque filas y genere el <strong>.xls</strong>.
                            </p>
                            <div class="alert alert--danger" x-show="previewError" x-text="previewError" x-cloak style="margin-top:0.5rem;"></div>
                        </div>

                        <div class="cursos-registros-page__table-toolbar" x-show="hasValidated" x-cloak>
                            <p class="req-manage-filters__meta">
                                <strong x-text="previewRows.length"></strong>
                                <span>fila(s)</span>
                                <span x-show="previewSummary" x-cloak>
                                    · Válidas: <strong x-text="previewSummary?.validas ?? 0"></strong>
                                    · Blandas: <strong x-text="previewSummary?.novedades_blandas ?? 0"></strong>
                                    · Bloqueadas: <strong x-text="previewSummary?.bloqueadas ?? 0"></strong>
                                </span>
                                <span x-show="selectedCount > 0" x-cloak>
                                    · <strong x-text="selectedCount"></strong> seleccionado(s)
                                </span>
                            </p>
                        </div>
                    </div>

                    <div
                        class="panel-text"
                        x-show="!hasValidated && !previewLoading"
                        x-cloak
                        style="padding:1.25rem 0.25rem;color:var(--text-muted, #64748b);"
                    >
                        No hay datos cargados. Elija la política de vigencia y pulse <strong>Validar</strong>.
                    </div>

                    <p class="panel-text" x-show="previewLoading" x-cloak style="padding:1rem 0.25rem;">Validando candidatos…</p>

                    <div class="data-table-wrap req-manage-shell__table" x-show="hasValidated && previewRows.length > 0" x-cloak>
                        <div class="req-manage-table-scroll">
                            <table class="data-table" style="width:100%;min-width:96rem;">
                                <thead>
                                    <tr>
                                        <th class="cursos-registros-page__select-col">
                                            <label class="cursos-registros-page__select-label" title="Seleccionar todos">
                                                <input
                                                    type="checkbox"
                                                    class="cursos-registros-page__select-checkbox"
                                                    x-bind:checked="allSelected"
                                                    x-on:change="toggleAll($event.target.checked)"
                                                    aria-label="Seleccionar todos"
                                                >
                                            </label>
                                        </th>
                                        <th>Nit</th>
                                        <th>RazonSocial</th>
                                        <th>TipoDocumento</th>
                                        <th>NoDocumento</th>
                                        <th>Nombre1</th>
                                        <th>Nombre2</th>
                                        <th>Apellido1</th>
                                        <th>Apellido2</th>
                                        <th>FechaNacimiento</th>
                                        <th>Genero</th>
                                        <th>Cargo</th>
                                        <th>Fechavinculacion</th>
                                        <th>CodigoCurso</th>
                                        <th>NitEscuela</th>
                                        <th>Nro</th>
                                        <th>TipoEstablecimiento</th>
                                        <th>TelefonoR</th>
                                        <th>DireccionR</th>
                                        <th>DireccionP</th>
                                        <th>Departamento</th>
                                        <th>Ciudad</th>
                                        <th>EducacionBM</th>
                                        <th>EducacionS</th>
                                        <th>Discapacidad</th>
                                        <th>Estado curso</th>
                                        <th>Valida</th>
                                        <th>Motivo</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="row in previewRows" :key="row.acreditado_id">
                                        <tr>
                                            <td>
                                                <label class="cursos-registros-page__select-label">
                                                    <input
                                                        type="checkbox"
                                                        class="cursos-registros-page__select-checkbox"
                                                        x-bind:value="row.acreditado_id"
                                                        x-bind:checked="isSelected(row.acreditado_id)"
                                                        x-on:change="toggleRow(row.acreditado_id, $event.target.checked)"
                                                        aria-label="Seleccionar fila"
                                                    >
                                                </label>
                                            </td>
                                            <td x-text="settings.nit"></td>
                                            <td x-text="settings.razon_social"></td>
                                            <td x-text="settings.tipo_documento"></td>
                                            <td x-text="row.document_number"></td>
                                            <td x-text="row.nombre1"></td>
                                            <td x-text="row.nombre2 || ''"></td>
                                            <td x-text="row.apellido1"></td>
                                            <td x-text="row.apellido2 || ''"></td>
                                            <td x-text="row.fecha_nacimiento"></td>
                                            <td x-text="row.genero"></td>
                                            <td x-text="row.cargo"></td>
                                            <td x-text="row.fecha_vinculacion"></td>
                                            <td x-text="row.codigo_curso || ''"></td>
                                            <td x-text="row.nit_escuela || ''"></td>
                                            <td x-text="row.nro || ''"></td>
                                            <td x-text="settings.tipo_establecimiento"></td>
                                            <td x-text="settings.telefono_r"></td>
                                            <td x-text="settings.direccion_r"></td>
                                            <td x-text="settings.direccion_p"></td>
                                            <td x-text="settings.departamento"></td>
                                            <td x-text="settings.ciudad"></td>
                                            <td x-text="settings.educacion_bm"></td>
                                            <td x-text="settings.educacion_s"></td>
                                            <td x-text="settings.discapacidad"></td>
                                            <td>
                                                <template x-if="row.estado_curso">
                                                    <span
                                                        class="status-pill"
                                                        :class="{
                                                            'status-pill--success': row.estado_curso === 'VIGENTE',
                                                            'status-pill--warning': row.estado_curso === 'ACTUALIZAR',
                                                            'status-pill--danger': row.estado_curso === 'VENCIDO',
                                                            'status-pill--muted': !['VIGENTE','ACTUALIZAR','VENCIDO'].includes(row.estado_curso),
                                                        }"
                                                        x-text="row.estado_curso"
                                                    ></span>
                                                </template>
                                                <span x-show="!row.estado_curso">—</span>
                                            </td>
                                            <td>
                                                <span
                                                    class="status-pill"
                                                    :class="row.valida ? 'status-pill--success' : (row.hard_block ? 'status-pill--danger' : 'status-pill--warning')"
                                                    x-text="row.valida ? 'Sí' : 'No'"
                                                ></span>
                                            </td>
                                            <td x-text="row.motivo || '—'"></td>
                                            <td>
                                                <div class="cursos-registros-page__row-actions table-actions">
                                                    <button
                                                        type="button"
                                                        class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--edit"
                                                        title="Editar acreditado"
                                                        aria-label="Editar acreditado"
                                                        x-show="row.editable"
                                                        x-cloak
                                                        x-on:click="openEditFromRow(row)"
                                                    >
                                                        <x-lucide-square-pen width="16" height="16" aria-hidden="true" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger"
                                                        title="Quitar de esta validación"
                                                        aria-label="Quitar de esta validación"
                                                        x-on:click="removeRow(row.acreditado_id)"
                                                    >
                                                        <x-lucide-trash-2 width="16" height="16" aria-hidden="true" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div
                        class="panel-text"
                        x-show="hasValidated && !previewLoading && previewRows.length === 0 && !previewError"
                        x-cloak
                        style="padding:1rem 0.25rem;"
                    >
                        No hay candidatos en el universo Export Apo.
                    </div>
                </div>
            </div>
        </div>

        <x-modal name="export-apo-novedades" maxWidth="lg" focusable>
            <div class="modal-card ficha-empleados-masivos-modal">
                <div class="ficha-empleados-masivos-modal__header">
                    <div class="ficha-empleados-masivos-modal__heading">
                        <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                            <x-lucide-triangle-alert width="18" height="18" aria-hidden="true" />
                        </span>
                        <div>
                            <h3 class="ficha-empleados-masivos-modal__title" id="export-apo-novedades-title">
                                Incluir novedades blandas
                            </h3>
                            <p class="ficha-empleados-masivos-modal__lead">
                                Hay filas seleccionadas con <strong>Valida = No</strong>. Decida si las novedades leves
                                (curso, escuela o código) deben ir en el archivo <strong>.xls</strong>.
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="ficha-empleados-masivos-modal__close"
                        title="Cerrar"
                        aria-label="Cerrar"
                        x-on:click="$dispatch('close-modal', 'export-apo-novedades')"
                    >
                        <x-lucide-x width="18" height="18" aria-hidden="true" />
                    </button>
                </div>

                <div class="ficha-empleados-masivos-modal__cards">
                    <section class="ficha-empleados-masivos-modal__card">
                        <div class="ficha-empleados-masivos-modal__card-head">
                            <span class="ficha-empleados-masivos-modal__card-icon ficha-empleados-masivos-modal__card-icon--export" aria-hidden="true">
                                <x-lucide-check-circle width="18" height="18" aria-hidden="true" />
                            </span>
                            <div>
                                <h4 class="ficha-empleados-masivos-modal__card-title">Resumen de la selección</h4>
                                <p class="ficha-empleados-masivos-modal__card-note">
                                    <strong x-text="selectedCount"></strong> fila(s) marcada(s)
                                    · Válidas: <strong x-text="selectedValidCount"></strong>
                                    · Novedades: <strong x-text="selectedSoftCount"></strong>
                                    · Bloqueo duro: <strong x-text="selectedHardCount"></strong>
                                </p>
                            </div>
                        </div>
                        <p class="ficha-empleados-masivos-modal__export-note" style="margin:0;">
                            Las filas con <strong>bloqueo duro</strong> (ficha incompleta o fuera del universo) <strong>nunca</strong> se exportan, elija Sí o No.
                        </p>
                    </section>
                </div>

                <div class="cursos-registros-page__form-actions acreditaciones-bulk-modal__actions" style="margin-top:1rem;">
                    <button
                        type="button"
                        class="btn btn--secondary"
                        x-on:click="$dispatch('close-modal', 'export-apo-novedades')"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        class="btn btn--secondary"
                        title="Exportar solo filas con Valida = Sí"
                        x-on:click="$dispatch('export-apo-generate', { include: false })"
                    >
                        Solo válidas
                    </button>
                    <button
                        type="button"
                        class="btn btn--primary"
                        title="Incluir novedades blandas en el .xls"
                        x-bind:disabled="selectedSoftCount === 0"
                        x-on:click="$dispatch('export-apo-generate', { include: true })"
                    >
                        Incluir novedades
                    </button>
                </div>
            </div>
        </x-modal>

        @include('areas.gestion_humana.acreditaciones.partials.bulk-update-modal')
        @include('areas.gestion_humana.acreditaciones.partials.edit-modal', [
            'cargoApoOptions' => $cargoApoOptions,
            'renovacionOptions' => $renovacionOptions,
            'exportApoReturn' => true,
        ])
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('acreditacionesExportApo', (config) => ({
                    previewUrl: config.previewUrl,
                    generateUrl: config.generateUrl,
                    defaultVigenciaPolicy: config.defaultVigenciaPolicy,
                    csrfToken: config.csrfToken || '',
                    settings: config.settings || {},
                    autoValidateIds: Array.isArray(config.autoValidateIds) ? config.autoValidateIds : [],
                    lookupUrl: config.lookupUrl || '',
                    bulkUpdateUrl: config.bulkUpdateUrl || '',
                    selected: {},
                    previewLoading: false,
                    generateLoading: false,
                    hasValidated: false,
                    previewError: '',
                    previewRows: [],
                    previewSummary: null,
                    bulkUpdateOpen: false,
                    submittingBulk: false,
                    bulkForm: {
                        observaciones: '',
                        fecha_solicitud: '',
                        renovacion: '',
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
                        renovacion: '',
                        observaciones: '',
                        update_url: '',
                    },

                    init() {
                        if (this.autoValidateIds.length > 0) {
                            this.$nextTick(() => {
                                this.runValidate(this.autoValidateIds);
                            });
                        }
                    },

                    get selectedCount() {
                        return Object.keys(this.selected).length;
                    },

                    get selectedPreviewRows() {
                        const ids = new Set(this.selectedIds());
                        return this.previewRows.filter((row) => ids.has(Number(row.acreditado_id)));
                    },

                    get selectedRows() {
                        return this.selectedPreviewRows
                            .filter((row) => row.editable !== false)
                            .map((row) => ({
                                id: Number(row.acreditado_id),
                                document_number: row.document_number || '',
                                full_name: row.full_name
                                    || [row.nombre1, row.nombre2, row.apellido1, row.apellido2]
                                        .filter(Boolean)
                                        .join(' ')
                                    || '—',
                                cargo_apo: row.cargo_apo || '',
                                vigencia_acr: row.vigencia_acr || '—',
                                estado: row.estado_label || row.estado || '—',
                            }));
                    },

                    get returnPreviewIds() {
                        return this.previewRows
                            .map((row) => Number(row.acreditado_id))
                            .filter((id) => id > 0);
                    },

                    get selectedValidCount() {
                        return this.selectedPreviewRows.filter((row) => row.valida === true).length;
                    },

                    get selectedSoftCount() {
                        return this.selectedPreviewRows.filter(
                            (row) => row.valida !== true && row.hard_block !== true,
                        ).length;
                    },

                    get selectedHardCount() {
                        return this.selectedPreviewRows.filter((row) => row.hard_block === true).length;
                    },

                    get selectedHasValidaNo() {
                        return this.selectedPreviewRows.some((row) => row.valida !== true);
                    },

                    get bulkHasPayload() {
                        return String(this.bulkForm.observaciones || '').trim() !== ''
                            || String(this.bulkForm.fecha_solicitud || '').trim() !== ''
                            || String(this.bulkForm.renovacion || '').trim() !== '';
                    },

                    get allSelected() {
                        return this.previewRows.length > 0
                            && this.previewRows.every((row) => this.isSelected(row.acreditado_id));
                    },

                    isSelected(id) {
                        return !! this.selected[id];
                    },

                    toggleRow(id, checked) {
                        if (checked) {
                            this.selected[id] = true;
                        } else {
                            delete this.selected[id];
                        }
                    },

                    toggleAll(checked) {
                        if (! checked) {
                            this.selected = {};
                            return;
                        }

                        const next = {};
                        this.previewRows.forEach((row) => {
                            next[row.acreditado_id] = true;
                        });
                        this.selected = next;
                    },

                    selectedIds() {
                        return Object.keys(this.selected).map((id) => Number(id));
                    },

                    removeRow(id) {
                        const target = Number(id);
                        this.previewRows = this.previewRows.filter((row) => Number(row.acreditado_id) !== target);
                        delete this.selected[target];
                        this.recomputeSummary();
                    },

                    recomputeSummary() {
                        let validas = 0;
                        let blandas = 0;
                        let bloqueadas = 0;
                        this.previewRows.forEach((row) => {
                            if (row.hard_block === true) {
                                bloqueadas++;
                            } else if (row.soft_novedad === true) {
                                blandas++;
                            } else {
                                validas++;
                            }
                        });
                        this.previewSummary = {
                            ...(this.previewSummary || {}),
                            selected: this.previewRows.length,
                            found: this.previewRows.length,
                            validas,
                            novedades_blandas: blandas,
                            bloqueadas,
                            missing_ids: [],
                        };
                    },

                    openBulkUpdate() {
                        if (this.selectedRows.length < 1) {
                            this.previewError = 'Seleccione al menos un acreditado editable.';
                            return;
                        }
                        this.previewError = '';
                        this.bulkForm = { observaciones: '', fecha_solicitud: '', renovacion: '' };
                        this.bulkUpdateOpen = true;
                        this.submittingBulk = false;
                        this.syncBulkRenovacion('');
                    },

                    closeBulkUpdate() {
                        this.bulkUpdateOpen = false;
                        this.submittingBulk = false;
                    },

                    submitBulkUpdate() {
                        if (this.submittingBulk || this.selectedRows.length < 1 || ! this.bulkHasPayload) {
                            return;
                        }

                        this.submittingBulk = true;

                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = this.bulkUpdateUrl;
                        form.style.display = 'none';

                        const append = (name, value) => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = name;
                            input.value = value == null ? '' : String(value);
                            form.appendChild(input);
                        };

                        append('_token', this.csrfToken
                            || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                            || '');
                        append('_return_context', 'export_apo');

                        this.selectedRows.forEach((row) => append('ids[]', row.id));
                        this.returnPreviewIds.forEach((id) => append('_return_ids[]', id));

                        const observaciones = String(this.bulkForm.observaciones || '').trim();
                        const fecha = String(this.bulkForm.fecha_solicitud || '').trim();
                        const renovacion = String(this.bulkForm.renovacion || '').trim();

                        if (observaciones !== '') {
                            append('observaciones', observaciones);
                        }
                        if (fecha !== '') {
                            append('fecha_solicitud', fecha);
                        }
                        if (renovacion !== '') {
                            append('renovacion', renovacion);
                        }

                        document.body.appendChild(form);
                        form.submit();
                    },

                    syncBulkRenovacion(value) {
                        this.bulkForm.renovacion = String(value || '');
                        this.$nextTick(() => {
                            const wrap = document.querySelector('.js-bulk-renovacion-select');
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

                    closeEdit() {
                        this.editOpen = false;
                    },

                    unlockEditIdentity() {
                        this.editIdentityLocked = false;
                        this.editForm.document_number = '';
                        this.editForm.full_name = '';
                        this.editForm.cargo = '';
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

                    syncEditRenovacion(value) {
                        this.$nextTick(() => {
                            const wrap = document.querySelector('.js-edit-renovacion-select');
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

                    openEditFromRow(row) {
                        if (! row || row.editable === false || ! row.update_url) {
                            return;
                        }
                        this.openEdit({
                            document_number: row.document_number || '',
                            full_name: row.full_name || '',
                            cargo: row.ficha_cargo || '',
                            cargo_apo: row.cargo_apo || '',
                            vigencia_acr: row.vigencia_acr || '',
                            fecha_solicitud: row.fecha_solicitud || '',
                            estado: row.estado || '',
                            estado_label: row.estado_label || row.estado || '',
                            renovacion: row.renovacion || '',
                            observaciones: row.observaciones || '',
                            update_url: row.update_url || '',
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
                            renovacion: detail?.renovacion || '',
                            observaciones: detail?.observaciones || '',
                            update_url: detail?.update_url || '',
                        };
                        this.editIdentityLocked = Boolean(this.editForm.document_number);
                        this.editOpen = true;
                        this.syncEditCargoApo(this.editForm.cargo_apo);
                        this.syncEditRenovacion(this.editForm.renovacion);
                        if (this.editForm.document_number) {
                            this.lookupName(this.editForm.document_number, 'edit');
                        }
                    },

                    async lookupName(cedula, mode) {
                        const value = String(cedula || '').trim();
                        if (! value || ! this.lookupUrl) {
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
                            if (data.found && data.full_name && mode === 'edit') {
                                this.editForm.document_number = data.document_number || value;
                                this.editForm.full_name = data.full_name;
                                this.editForm.cargo = data.cargo || '';
                                this.editIdentityLocked = true;
                            }
                        } catch (e) {}
                    },

                    currentVigenciaPolicy() {
                        const input = document.querySelector('input[name="vigencia_policy"]');
                        const value = input ? String(input.value || '').trim() : '';
                        return value || this.defaultVigenciaPolicy;
                    },

                    async runValidate(ids) {
                        this.previewError = '';
                        this.selected = {};
                        this.hasValidated = true;
                        this.previewLoading = true;
                        this.previewRows = [];
                        this.previewSummary = null;

                        const payloadBody = {
                            vigencia_policy: this.currentVigenciaPolicy(),
                        };
                        if (Array.isArray(ids) && ids.length > 0) {
                            payloadBody.ids = ids.map((id) => Number(id)).filter((id) => id > 0);
                        }

                        try {
                            const token = this.csrfToken
                                || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                                || '';
                            const response = await fetch(this.previewUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': token,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: JSON.stringify(payloadBody),
                            });

                            const payload = await response.json().catch(() => ({}));
                            if (! response.ok) {
                                const msg = payload.message
                                    || payload.errors?.vigencia_policy?.[0]
                                    || payload.errors?.ids?.[0]
                                    || 'No se pudo validar.';
                                throw new Error(msg);
                            }

                            this.previewRows = Array.isArray(payload.rows) ? payload.rows : [];
                            this.previewSummary = payload.summary || null;
                        } catch (error) {
                            this.previewRows = [];
                            this.previewSummary = null;
                            this.previewError = error?.message || 'Error al validar.';
                        } finally {
                            this.previewLoading = false;
                        }
                    },

                    openGenerateModal() {
                        this.previewError = '';
                        if (this.selectedCount === 0) {
                            this.previewError = 'Seleccione al menos un candidato.';
                            return;
                        }

                        if (this.selectedHasValidaNo) {
                            window.dispatchEvent(new CustomEvent('open-modal', {
                                detail: 'export-apo-novedades',
                            }));
                            return;
                        }

                        this.submitGenerate(false);
                    },

                    submitGenerate(includeNovedades) {
                        window.dispatchEvent(new CustomEvent('close-modal', {
                            detail: 'export-apo-novedades',
                        }));

                        const ids = this.selectedIds();
                        if (ids.length === 0) {
                            this.previewError = 'Seleccione al menos un candidato.';
                            return;
                        }

                        this.generateLoading = true;

                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = this.generateUrl;
                        form.style.display = 'none';

                        const csrf = document.createElement('input');
                        csrf.type = 'hidden';
                        csrf.name = '_token';
                        csrf.value = this.csrfToken
                            || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                            || '';
                        form.appendChild(csrf);

                        ids.forEach((id) => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'ids[]';
                            input.value = String(id);
                            form.appendChild(input);
                        });

                        const policy = document.createElement('input');
                        policy.type = 'hidden';
                        policy.name = 'vigencia_policy';
                        policy.value = this.currentVigenciaPolicy();
                        form.appendChild(policy);

                        const novedades = document.createElement('input');
                        novedades.type = 'hidden';
                        novedades.name = 'include_novedades';
                        novedades.value = includeNovedades ? '1' : '0';
                        form.appendChild(novedades);

                        document.body.appendChild(form);
                        form.submit();
                    },

                    clearAll() {
                        this.selected = {};
                        this.hasValidated = false;
                        this.previewError = '';
                        this.previewRows = [];
                        this.previewSummary = null;
                        this.previewLoading = false;
                        this.generateLoading = false;
                        this.autoValidateIds = [];
                    },
                }));
            });
        </script>
    @endpush
</x-app-layout>
