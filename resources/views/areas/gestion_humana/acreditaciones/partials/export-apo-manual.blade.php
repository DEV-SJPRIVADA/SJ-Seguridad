{{-- Export Apo Manual: grilla editable (fechas date + selects searchable); sin Valida/universo --}}
<div
    class="acreditaciones-export-apo-manual desvinculaciones-masivos-page cartas-vacaciones-page"
    x-data="acreditacionesExportApoManual(@js([
        'prefillUrl' => $manualPrefillUrl,
        'generateUrl' => $manualGenerateUrl,
        'csrfToken' => csrf_token(),
        'maxRows' => $manualMaxRows ?? 500,
        'defaultFechaVinculacion' => $defaultFechaVinculacion,
        'initialDocuments' => $initialManualDocuments ?? [],
        'generoOptions' => $manualGeneroOptions ?? [],
        'cargoOptions' => $manualCargoOptions ?? [],
        'codigoCursoOptions' => $manualCodigoCursoOptions ?? [],
        'nitEscuelaOptions' => $manualNitEscuelaOptions ?? [],
        'settings' => [
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
        ],
    ]))"
>
    <div class="panel cursos-registros-panel acreditaciones-export-apo-manual__panel">
        <div class="panel__body panel__body--compact req-manage-shell">
            {{-- Franja compacta: título + chips empresa | filtro + acciones icon-only --}}
            <div class="desvinculaciones-masivos__table-top cartas-vacaciones__toolbar acreditaciones-export-apo-manual__toolbar">
                <div class="cartas-vacaciones__toolbar-meta">
                    <h2 class="cartas-vacaciones__toolbar-title">Export Apo Manual</h2>
                    <p class="cartas-vacaciones__toolbar-hint">
                        Filas a mano o por cédula (prefill ficha/acreditado). Datos de empresa: Catálogo (solo lectura).
                    </p>
                    <div class="acreditaciones-export-apo-manual__settings" aria-label="Parámetros fijos de empresa">
                        <span class="acreditaciones-export-apo-manual__chip" title="NIT">
                            <strong>NIT</strong> <span x-text="settings.nit || '—'"></span>
                        </span>
                        <span class="acreditaciones-export-apo-manual__chip" title="Razón social">
                            <strong>Razón</strong> <span x-text="settings.razon_social || '—'"></span>
                        </span>
                        <span class="acreditaciones-export-apo-manual__chip" title="Tipo documento">
                            <strong>Doc.</strong> <span x-text="settings.tipo_documento || '—'"></span>
                        </span>
                        <span class="acreditaciones-export-apo-manual__chip" title="Ciudad">
                            <strong>Ciudad</strong> <span x-text="settings.ciudad || '—'"></span>
                        </span>
                    </div>
                </div>
                <div class="cartas-notificacion__toolbar-right">
                    <div class="cartas-notificacion__toolbar-actions">
                        <input
                            type="search"
                            class="form-input cartas-notificacion__filter-input"
                            placeholder="Filtrar cédula o nombre…"
                            x-model="filterText"
                            autocomplete="off"
                            x-bind:disabled="processing || bulkApplying"
                            title="Filtrar filas visibles"
                            aria-label="Filtrar filas visibles"
                        >
                        <button
                            type="button"
                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                            title="Agregar varias cédulas"
                            aria-label="Agregar varias cédulas"
                            x-on:click.prevent="openBulkCedulasModal()"
                            x-bind:disabled="processing || bulkApplying"
                        >
                            <x-lucide-clipboard-list width="18" height="18" aria-hidden="true" />
                        </button>
                    </div>
                    <div class="cartas-notificacion__toolbar-end" role="group" aria-label="Filas de la grilla">
                        <button
                            type="button"
                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                            title="Limpiar grilla"
                            aria-label="Limpiar grilla"
                            x-on:click="clearGrid()"
                            x-bind:disabled="processing || bulkApplying"
                        >
                            <x-lucide-x width="18" height="18" aria-hidden="true" />
                        </button>
                        <button
                            type="button"
                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                            title="Agregar fila"
                            aria-label="Agregar fila"
                            x-on:click="addRow()"
                            x-bind:disabled="processing || bulkApplying || rows.length >= maxRows"
                        >
                            <x-lucide-plus width="18" height="18" aria-hidden="true" />
                        </button>
                    </div>
                </div>
            </div>

            <div class="alert alert--danger acreditaciones-export-apo-manual__alert" x-show="errorMessage" x-text="errorMessage" x-cloak></div>
            <div class="alert alert--success acreditaciones-export-apo-manual__alert" x-show="successMessage" x-text="successMessage" x-cloak></div>

            <div class="data-table-wrap desvinculaciones-masivos__table-wrap req-manage-shell__table acreditaciones-export-apo-manual__table-wrap">
                <div class="req-manage-table-scroll">
                    <table class="data-table desvinculaciones-masivos__table acreditaciones-export-apo-manual__table">
                        <thead>
                            <tr>
                                <th class="acreditaciones-export-apo-manual__col-cedula">Cédula</th>
                                <th class="acreditaciones-export-apo-manual__col-name">Nombre1</th>
                                <th class="acreditaciones-export-apo-manual__col-name">Nombre2</th>
                                <th class="acreditaciones-export-apo-manual__col-name">Apellido1</th>
                                <th class="acreditaciones-export-apo-manual__col-name">Apellido2</th>
                                <th class="acreditaciones-export-apo-manual__col-date">Fecha nac.</th>
                                <th class="acreditaciones-export-apo-manual__col-select">Género</th>
                                <th class="acreditaciones-export-apo-manual__col-select-lg">Cargo</th>
                                <th class="acreditaciones-export-apo-manual__col-date">Fecha vinc.</th>
                                <th class="acreditaciones-export-apo-manual__col-select-lg">Código curso</th>
                                <th class="acreditaciones-export-apo-manual__col-select-lg">NIT escuela</th>
                                <th class="acreditaciones-export-apo-manual__col-nro">Nro</th>
                                <th class="desvinculaciones-masivos__col-actions"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="row in visibleRows()" :key="row.key">
                                <tr>
                                    <td class="acreditaciones-export-apo-manual__col-cedula">
                                        <input
                                            type="text"
                                            class="form-input desvinculaciones-masivos__cedula-input"
                                            x-model="row.document_number"
                                            x-on:blur="lookupRow(row)"
                                            x-on:keydown.enter.prevent="lookupRow(row)"
                                            placeholder="Cédula"
                                            autocomplete="off"
                                            x-bind:disabled="processing"
                                        >
                                        <p class="form-hint acreditaciones-export-apo-manual__hint" x-show="row.sourceHint" x-text="row.sourceHint" x-cloak></p>
                                        <p
                                            class="form-hint acreditaciones-export-apo-manual__hint acreditaciones-export-apo-manual__hint--warn"
                                            x-show="row.avisos"
                                            x-text="row.avisos"
                                            x-bind:title="row.avisos"
                                            x-cloak
                                        ></p>
                                    </td>
                                    <td>
                                        <input type="text" class="form-input" x-model="row.nombre1" placeholder="Nombre1" autocomplete="off" x-bind:disabled="processing">
                                    </td>
                                    <td>
                                        <input type="text" class="form-input" x-model="row.nombre2" placeholder="Nombre2" autocomplete="off" x-bind:disabled="processing">
                                    </td>
                                    <td>
                                        <input type="text" class="form-input" x-model="row.apellido1" placeholder="Apellido1" autocomplete="off" x-bind:disabled="processing">
                                    </td>
                                    <td>
                                        <input type="text" class="form-input" x-model="row.apellido2" placeholder="Apellido2" autocomplete="off" x-bind:disabled="processing">
                                    </td>
                                    <td>
                                        <input
                                            type="date"
                                            class="form-input"
                                            x-model="row.fecha_nacimiento"
                                            x-bind:disabled="processing"
                                            title="Fecha de nacimiento"
                                            aria-label="Fecha de nacimiento"
                                        >
                                    </td>
                                    <td>
                                        <div
                                            class="acreditaciones-export-apo-manual__select"
                                            x-data="searchableSelect({
                                                options: generoOptionsFor(row),
                                                value: row.genero,
                                                placeholder: 'Género…',
                                                searchPlaceholder: 'Buscar…',
                                                required: false,
                                                allowClear: true,
                                                disabled: processing,
                                            })"
                                            x-init="
                                                $watch('value', (v) => { row.genero = v });
                                                $watch(() => row.genero, (v) => {
                                                    const next = v == null ? '' : String(v);
                                                    if (String(value) !== next) { value = next; }
                                                    options = generoOptionsFor(row);
                                                });
                                            "
                                        >
                                            @include('areas.gestion_humana.desvinculaciones.partials.alpine-searchable-select')
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="acreditaciones-export-apo-manual__select"
                                            x-data="searchableSelect({
                                                options: cargoOptionsFor(row),
                                                value: row.cargo,
                                                placeholder: 'Cargo…',
                                                searchPlaceholder: 'Buscar cargo…',
                                                required: false,
                                                allowClear: true,
                                                disabled: processing,
                                            })"
                                            x-init="
                                                $watch('value', (v) => { row.cargo = v });
                                                $watch(() => row.cargo, (v) => {
                                                    const next = v == null ? '' : String(v);
                                                    if (String(value) !== next) { value = next; }
                                                    options = cargoOptionsFor(row);
                                                });
                                            "
                                        >
                                            @include('areas.gestion_humana.desvinculaciones.partials.alpine-searchable-select')
                                        </div>
                                    </td>
                                    <td>
                                        <input
                                            type="date"
                                            class="form-input"
                                            x-model="row.fecha_vinculacion"
                                            x-bind:disabled="processing"
                                            title="Fecha de vinculación"
                                            aria-label="Fecha de vinculación"
                                        >
                                    </td>
                                    <td>
                                        <div
                                            class="acreditaciones-export-apo-manual__select"
                                            x-data="searchableSelect({
                                                options: codigoCursoOptionsFor(row),
                                                value: row.codigo_curso,
                                                placeholder: 'Código…',
                                                searchPlaceholder: 'Buscar código…',
                                                required: false,
                                                allowClear: true,
                                                disabled: processing,
                                            })"
                                            x-init="
                                                $watch('value', (v) => { row.codigo_curso = v });
                                                $watch(() => row.codigo_curso, (v) => {
                                                    const next = v == null ? '' : String(v);
                                                    if (String(value) !== next) { value = next; }
                                                    options = codigoCursoOptionsFor(row);
                                                });
                                            "
                                        >
                                            @include('areas.gestion_humana.desvinculaciones.partials.alpine-searchable-select')
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="acreditaciones-export-apo-manual__select"
                                            x-data="searchableSelect({
                                                options: nitEscuelaOptionsFor(row),
                                                value: row.nit_escuela,
                                                placeholder: 'NIT…',
                                                searchPlaceholder: 'Buscar escuela…',
                                                required: false,
                                                allowClear: true,
                                                disabled: processing,
                                            })"
                                            x-init="
                                                $watch('value', (v) => { row.nit_escuela = v });
                                                $watch(() => row.nit_escuela, (v) => {
                                                    const next = v == null ? '' : String(v);
                                                    if (String(value) !== next) { value = next; }
                                                    options = nitEscuelaOptionsFor(row);
                                                });
                                            "
                                        >
                                            @include('areas.gestion_humana.desvinculaciones.partials.alpine-searchable-select')
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-input" x-model="row.nro" placeholder="Nro curso" autocomplete="off" x-bind:disabled="processing">
                                    </td>
                                    <td class="table-actions">
                                        <button
                                            type="button"
                                            class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger"
                                            x-on:click="removeRowByKey(row.key)"
                                            x-bind:disabled="processing || rows.length <= 1"
                                            title="Borrar fila"
                                            aria-label="Borrar fila"
                                        >
                                            <x-lucide-trash-2 width="16" height="16" aria-hidden="true" />
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="cursos-registros-page__table-toolbar acreditaciones-export-apo-manual__footer">
                <p class="req-manage-filters__meta">
                    <strong x-text="rowsWithCedulaCount()"></strong>
                    <span>fila(s) con cédula</span>
                    <span>· máx. <span x-text="maxRows"></span></span>
                </p>
                <div class="acreditaciones-export-apo-manual__actions">
                    <button
                        type="button"
                        class="btn btn--primary btn--sm"
                        x-bind:disabled="!hasProcessableRows() || processing || bulkApplying"
                        x-on:click="submitGenerate('acreditar')"
                        title="Acreditar: llena hoja ApoDatos"
                    >
                        <x-lucide-download width="16" height="16" aria-hidden="true" />
                        <span x-text="processing && pendingExportMode === 'acreditar' ? 'Generando…' : 'Acreditar .xls'"></span>
                    </button>
                    <button
                        type="button"
                        class="btn btn--secondary btn--sm"
                        x-bind:disabled="!hasProcessableRows() || processing || bulkApplying"
                        x-on:click="submitGenerate('retirar')"
                        title="Retirar: llena hoja Retiros (solo cédula)"
                    >
                        <x-lucide-user-minus width="16" height="16" aria-hidden="true" />
                        <span x-text="processing && pendingExportMode === 'retirar' ? 'Generando…' : 'Retirar'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal varias cédulas (mismo patrón Cartas) --}}
    <div
        class="modal-shell"
        x-show="bulkModalOpen"
        x-cloak
        style="display: none;"
        x-on:keydown.escape.window="! bulkApplying && closeBulkCedulasModal()"
    >
        <div
            class="modal-backdrop"
            x-on:click="! bulkApplying && closeBulkCedulasModal()"
        ></div>
        <div class="modal-panel modal-panel--lg" role="dialog" aria-modal="true" aria-labelledby="export-apo-manual-bulk-cedulas-title">
            <div class="modal-card ficha-empleados-masivos-modal multi-cedula-modal">
                <div class="ficha-empleados-masivos-modal__header">
                    <div class="ficha-empleados-masivos-modal__heading">
                        <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                            <x-lucide-clipboard-list width="18" height="18" aria-hidden="true" />
                        </span>
                        <div class="multi-cedula-modal__heading-copy">
                            <h3 id="export-apo-manual-bulk-cedulas-title" class="ficha-empleados-masivos-modal__title">Agregar varias cédulas</h3>
                            <p class="ficha-empleados-masivos-modal__lead multi-cedula-modal__lead">
                                Pegue cédulas separadas por salto de línea, coma o punto y coma.
                                Se precarga desde ficha/acreditado si existe; si no, complete a mano.
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="ficha-empleados-masivos-modal__close"
                        title="Cerrar"
                        aria-label="Cerrar"
                        x-on:click="closeBulkCedulasModal()"
                        x-bind:disabled="bulkApplying"
                    >
                        <x-lucide-x width="18" height="18" aria-hidden="true" />
                    </button>
                </div>
                <div class="ficha-empleados-masivos-modal__body">
                    <label class="form-label" for="export-apo-manual-bulk-cedulas-textarea">Cédulas</label>
                    <textarea
                        id="export-apo-manual-bulk-cedulas-textarea"
                        class="form-input"
                        rows="8"
                        x-model="bulkPasteText"
                        placeholder="Ej.&#10;123456789&#10;987654321"
                        x-bind:disabled="bulkApplying"
                    ></textarea>
                    <p class="form-hint" x-show="bulkPasteHasValues()" x-text="bulkPasteHint()" x-cloak></p>
                    <p class="form-hint text-danger" x-show="bulkPasteError" x-text="bulkPasteError" x-cloak></p>
                </div>
                <div class="ficha-empleados-masivos-modal__footer">
                    <button
                        type="button"
                        class="btn btn--secondary btn--sm"
                        x-on:click="closeBulkCedulasModal()"
                        x-bind:disabled="bulkApplying"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        class="btn btn--primary btn--sm"
                        x-on:click="applyBulkCedulas()"
                        x-bind:disabled="bulkApplying || !bulkPasteHasValues()"
                    >
                        <span x-text="bulkApplying ? 'Aplicando…' : 'Agregar a la grilla'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('acreditacionesExportApoManual', (config) => {
                    const defaultFechaVinculacion = String(config.defaultFechaVinculacion || '').trim();

                    const emptyFields = () => ({
                        document_number: '',
                        nombre1: '',
                        nombre2: '',
                        apellido1: '',
                        apellido2: '',
                        fecha_nacimiento: '',
                        genero: '',
                        cargo: '',
                        // Fecha vinc.: ayer (America/Bogota) por defecto.
                        fecha_vinculacion: defaultFechaVinculacion,
                        codigo_curso: '',
                        nit_escuela: '',
                        nro: '',
                    });

                    const emptyRow = () => ({
                        key: 'r-' + Math.random().toString(36).slice(2, 10),
                        ...emptyFields(),
                        sourceHint: '',
                        avisos: '',
                        looking_up: false,
                    });

                    const maxRows = Number(config.maxRows) || 500;

                    const withExtraOption = (baseOptions, value) => {
                        const list = Array.isArray(baseOptions) ? baseOptions.slice() : [];
                        const v = String(value || '').trim();
                        if (v === '') {
                            return list;
                        }
                        if (list.some((opt) => String(opt.value) === v)) {
                            return list;
                        }
                        return [{ value: v, label: v }, ...list];
                    };

                    return {
                        prefillUrl: config.prefillUrl,
                        generateUrl: config.generateUrl,
                        csrfToken: config.csrfToken || '',
                        defaultFechaVinculacion,
                        settings: config.settings || {},
                        generoOptions: Array.isArray(config.generoOptions) ? config.generoOptions : [],
                        cargoOptions: Array.isArray(config.cargoOptions) ? config.cargoOptions : [],
                        codigoCursoOptions: Array.isArray(config.codigoCursoOptions) ? config.codigoCursoOptions : [],
                        nitEscuelaOptions: Array.isArray(config.nitEscuelaOptions) ? config.nitEscuelaOptions : [],
                        initialDocuments: Array.isArray(config.initialDocuments) ? config.initialDocuments : [],
                        maxRows,
                        rows: Array.from({ length: 2 }, () => emptyRow()),
                        filterText: '',
                        processing: false,
                        pendingExportMode: 'acreditar',
                        errorMessage: '',
                        successMessage: '',
                        bulkPasteText: '',
                        bulkPasteError: '',
                        bulkApplying: false,
                        bulkModalOpen: false,

                        async init() {
                            // Precarga desde Validaciones (cola sin acreditación → Manual).
                            if (this.initialDocuments.length < 1) {
                                return;
                            }
                            this.bulkApplying = true;
                            try {
                                const results = await this.prefillDocuments(this.initialDocuments);
                                this.rows = [];
                                results.forEach((item) => {
                                    this.fillOrPushRow(item);
                                });
                                if (this.rows.length < 1) {
                                    this.rows = Array.from({ length: 2 }, () => emptyRow());
                                }
                                this.successMessage = 'Se precargaron ' + this.rows.filter((r) => String(r.document_number || '').trim() !== '').length + ' cédula(s) desde Validaciones.';
                            } catch (e) {
                                this.errorMessage = e?.message || 'No se pudo precargar las cédulas desde Validaciones.';
                                this.rows = Array.from({ length: 2 }, () => emptyRow());
                            } finally {
                                this.bulkApplying = false;
                            }
                        },

                        generoOptionsFor(row) {
                            return withExtraOption(this.generoOptions, row?.genero);
                        },

                        cargoOptionsFor(row) {
                            return withExtraOption(this.cargoOptions, row?.cargo);
                        },

                        codigoCursoOptionsFor(row) {
                            return withExtraOption(this.codigoCursoOptions, row?.codigo_curso);
                        },

                        nitEscuelaOptionsFor(row) {
                            return withExtraOption(this.nitEscuelaOptions, row?.nit_escuela);
                        },

                        /** dd/mm/yyyy o YYYY-MM-DD → YYYY-MM-DD para input date */
                        toIsoDate(value) {
                            const raw = String(value || '').trim();
                            if (! raw) {
                                return '';
                            }
                            const dmy = raw.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
                            if (dmy) {
                                return dmy[3] + '-' + dmy[2] + '-' + dmy[1];
                            }
                            if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
                                return raw;
                            }
                            return '';
                        },

                        visibleRows() {
                            const q = String(this.filterText || '').trim().toLowerCase();
                            if (! q) {
                                return this.rows;
                            }
                            return this.rows.filter((row) => {
                                const hay = [
                                    row.document_number,
                                    row.nombre1,
                                    row.nombre2,
                                    row.apellido1,
                                    row.apellido2,
                                    row.cargo,
                                ].join(' ').toLowerCase();
                                return hay.includes(q);
                            });
                        },

                        addRow() {
                            if (this.rows.length >= this.maxRows) {
                                return;
                            }
                            this.rows.push(emptyRow());
                        },

                        removeRowByKey(key) {
                            if (this.rows.length <= 1) {
                                return;
                            }
                            const index = this.rows.findIndex((row) => row.key === key);
                            if (index < 0) {
                                return;
                            }
                            this.rows.splice(index, 1);
                        },

                        clearGrid() {
                            this.rows = Array.from({ length: 2 }, () => emptyRow());
                            this.filterText = '';
                            this.errorMessage = '';
                            this.successMessage = '';
                        },

                        hasProcessableRows() {
                            return this.rows.some((row) => String(row.document_number || '').trim() !== '');
                        },

                        rowsWithCedulaCount() {
                            return this.rows.filter((row) => String(row.document_number || '').trim() !== '').length;
                        },

                        openBulkCedulasModal() {
                            this.bulkPasteError = '';
                            this.bulkModalOpen = true;
                            document.body.classList.add('overflow-y-hidden');
                        },

                        closeBulkCedulasModal() {
                            if (this.bulkApplying) {
                                return;
                            }
                            this.bulkModalOpen = false;
                            document.body.classList.remove('overflow-y-hidden');
                        },

                        parseBulkCedulas(raw) {
                            const parts = String(raw || '')
                                .split(/[\r\n,;]+/)
                                .map((part) => part.trim().replace(/\s+/g, ''))
                                .filter(Boolean);

                            const unique = [];
                            const seen = new Set();
                            for (const part of parts) {
                                if (seen.has(part)) {
                                    continue;
                                }
                                seen.add(part);
                                unique.push(part);
                            }

                            return {
                                unique,
                                truncated: unique.length > this.maxRows,
                                list: unique.slice(0, this.maxRows),
                            };
                        },

                        bulkPasteHasValues() {
                            return this.parseBulkCedulas(this.bulkPasteText).list.length > 0;
                        },

                        bulkPasteHint() {
                            const parsed = this.parseBulkCedulas(this.bulkPasteText);
                            let text = parsed.list.length + ' cédula(s) reconocida(s).';
                            if (parsed.truncated) {
                                text += ' Se usarán solo las primeras ' + this.maxRows + '.';
                            }
                            return text;
                        },

                        applyPrefillToRow(row, payload) {
                            const data = payload?.row || payload || {};
                            row.document_number = data.document_number || row.document_number || '';
                            row.nombre1 = data.nombre1 || '';
                            row.nombre2 = data.nombre2 || '';
                            row.apellido1 = data.apellido1 || '';
                            row.apellido2 = data.apellido2 || '';
                            row.fecha_nacimiento = this.toIsoDate(data.fecha_nacimiento);
                            row.genero = data.genero || '';
                            row.cargo = data.cargo || '';
                            row.fecha_vinculacion = this.toIsoDate(data.fecha_vinculacion)
                                || this.defaultFechaVinculacion
                                || '';
                            row.codigo_curso = data.codigo_curso || '';
                            row.nit_escuela = data.nit_escuela || '';
                            row.nro = data.nro || '';
                            if (payload?.found && payload?.source === 'acreditado') {
                                row.sourceHint = 'Prefill: acreditado';
                            } else if (payload?.found && payload?.source === 'ficha') {
                                row.sourceHint = 'Prefill: ficha';
                            } else {
                                row.sourceHint = payload?.found === false ? 'Sin ficha/acreditado — complete a mano' : '';
                            }
                            // Avisos informativos del cruce de cargos / curso (no bloquean la generación).
                            row.avisos = String(payload?.avisos || '');
                        },

                        fillOrPushRow(payload) {
                            const emptyIndex = this.rows.findIndex(
                                (row) => String(row.document_number || '').trim() === ''
                            );

                            if (emptyIndex >= 0) {
                                this.applyPrefillToRow(this.rows[emptyIndex], payload);
                                return true;
                            }

                            if (this.rows.length >= this.maxRows) {
                                return false;
                            }

                            const row = emptyRow();
                            this.applyPrefillToRow(row, payload);
                            this.rows.push(row);
                            return true;
                        },

                        async prefillDocuments(documentNumbers) {
                            const token = this.csrfToken
                                || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                                || '';
                            const response = await fetch(this.prefillUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': token,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: JSON.stringify({
                                    document_numbers: documentNumbers,
                                }),
                            });
                            const data = await response.json().catch(() => ({}));
                            if (! response.ok || data.ok === false) {
                                throw new Error(
                                    data.message
                                    || (data.errors && Object.values(data.errors).flat()[0])
                                    || 'No se pudo precargar las cédulas.'
                                );
                            }
                            return Array.isArray(data.results) ? data.results : [];
                        },

                        async lookupRow(row) {
                            const doc = String(row.document_number || '').trim();
                            if (! doc || row.looking_up) {
                                return;
                            }
                            row.looking_up = true;
                            try {
                                const results = await this.prefillDocuments([doc]);
                                if (results[0]) {
                                    this.applyPrefillToRow(row, results[0]);
                                }
                            } catch (e) {
                                row.sourceHint = '';
                                row.avisos = '';
                            } finally {
                                row.looking_up = false;
                            }
                        },

                        async applyBulkCedulas() {
                            this.bulkPasteError = '';
                            const parsed = this.parseBulkCedulas(this.bulkPasteText);
                            if (parsed.list.length === 0) {
                                this.bulkPasteError = 'Ingrese al menos una cédula.';
                                return;
                            }

                            this.bulkApplying = true;
                            try {
                                const results = await this.prefillDocuments(parsed.list);
                                let added = 0;
                                let skipped = 0;
                                results.forEach((item) => {
                                    if (this.fillOrPushRow(item)) {
                                        added++;
                                    } else {
                                        skipped++;
                                    }
                                });
                                this.bulkPasteText = '';
                                this.closeBulkCedulasModal();
                                this.successMessage = 'Se agregaron ' + added + ' cédula(s).'
                                    + (skipped > 0 ? ' ' + skipped + ' omitida(s) por tope.' : '');
                                this.errorMessage = '';
                            } catch (e) {
                                this.bulkPasteError = e?.message || 'No se pudo aplicar el lote.';
                            } finally {
                                this.bulkApplying = false;
                            }
                        },

                        buildPayloadRows() {
                            return this.rows
                                .filter((row) => String(row.document_number || '').trim() !== '')
                                .map((row) => ({
                                    document_number: String(row.document_number || '').trim(),
                                    nombre1: String(row.nombre1 || '').trim(),
                                    nombre2: String(row.nombre2 || '').trim(),
                                    apellido1: String(row.apellido1 || '').trim(),
                                    apellido2: String(row.apellido2 || '').trim(),
                                    fecha_nacimiento: String(row.fecha_nacimiento || '').trim(),
                                    genero: String(row.genero || '').trim(),
                                    cargo: String(row.cargo || '').trim(),
                                    fecha_vinculacion: String(row.fecha_vinculacion || '').trim(),
                                    codigo_curso: String(row.codigo_curso || '').trim(),
                                    nit_escuela: String(row.nit_escuela || '').trim(),
                                    nro: String(row.nro || '').trim(),
                                }));
                        },

                        filenameFromDisposition(header) {
                            if (! header) {
                                return 'APO_export.xls';
                            }
                            const utf = header.match(/filename\*=UTF-8''([^;]+)/i);
                            if (utf && utf[1]) {
                                try {
                                    return decodeURIComponent(utf[1]);
                                } catch (e) {
                                    return utf[1];
                                }
                            }
                            const plain = header.match(/filename="?([^";]+)"?/i);
                            return plain ? plain[1] : 'APO_export.xls';
                        },

                        async submitGenerate(mode) {
                            const exportMode = mode === 'retirar' ? 'retirar' : 'acreditar';
                            this.pendingExportMode = exportMode;
                            const rows = this.buildPayloadRows();
                            if (rows.length === 0) {
                                this.errorMessage = 'Agregue al menos una fila con cédula.';
                                return;
                            }

                            this.errorMessage = '';
                            this.successMessage = '';
                            this.processing = true;

                            const token = this.csrfToken
                                || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                                || '';

                            try {
                                const response = await fetch(this.generateUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json, application/vnd.ms-excel, application/octet-stream, */*',
                                        'X-CSRF-TOKEN': token,
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    body: JSON.stringify({
                                        rows,
                                        export_mode: exportMode,
                                    }),
                                });

                                const contentType = response.headers.get('Content-Type') || '';

                                if (! response.ok) {
                                    let message = 'No se pudo generar el archivo Export Apo Manual.';
                                    if (contentType.includes('application/json')) {
                                        const data = await response.json().catch(() => ({}));
                                        message = data.message
                                            || (data.errors && Object.values(data.errors).flat()[0])
                                            || message;
                                    }
                                    this.errorMessage = message;
                                    if (typeof window.showToast === 'function') {
                                        window.showToast(message, 'error');
                                    }
                                    return;
                                }

                                const blob = await response.blob();
                                const filename = this.filenameFromDisposition(
                                    response.headers.get('Content-Disposition')
                                );
                                const url = window.URL.createObjectURL(blob);
                                const link = document.createElement('a');
                                link.href = url;
                                link.download = filename;
                                document.body.appendChild(link);
                                link.click();
                                link.remove();
                                window.URL.revokeObjectURL(url);

                                const okMsg = exportMode === 'retirar'
                                    ? 'Documento de retiro (Manual) generado correctamente.'
                                    : 'Documento de acreditación (Manual) generado correctamente.';
                                this.successMessage = okMsg;
                                if (typeof window.showToast === 'function') {
                                    window.showToast(okMsg, 'success');
                                }
                            } catch (e) {
                                const message = 'Error de red al generar el archivo Export Apo Manual.';
                                this.errorMessage = message;
                                if (typeof window.showToast === 'function') {
                                    window.showToast(message, 'error');
                                }
                            } finally {
                                this.processing = false;
                            }
                        },
                    };
                });
            });
        </script>
    @endpush
@endonce
