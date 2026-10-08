<x-app-layout>
    {{-- Cartas Notificación: grilla en memoria + Excel + generación Word (docx/zip) --}}
    <div class="page-section desvinculaciones-masivos-page cartas-vacaciones-page">
        <div class="app-container">
            <div
                class="panel desvinculaciones-masivos"
                x-data="cartasNotificacionGrid(@js([
                    'lookupUrl' => $lookupUrl,
                    'generateUrl' => $generateUrl,
                    'importTemplateUrl' => $importTemplateUrl,
                    'importPreviewUrl' => $importPreviewUrl,
                    'csrf' => csrf_token(),
                    'signatoryOptions' => $signatoryOptions,
                    'duracionContratoOptions' => $duracionContratoOptions,
                    'maxRows' => (int) $maxRows,
                ]))"
            >
                <div class="panel__body">
                    {{-- Franja título + filtro memoria + acciones icon-only --}}
                    <div class="desvinculaciones-masivos__table-top cartas-vacaciones__toolbar">
                        <div class="cartas-vacaciones__toolbar-meta">
                            <h2 class="cartas-vacaciones__toolbar-title">Cartas Notificación</h2>
                            <p class="cartas-vacaciones__toolbar-hint">
                                Escriba filas a mano (+) · pegar cédulas · Excel · 1 → .docx · N → .zip · máx. {{ (int) $maxRows }}
                                · una plantilla activa en Plantillas Word
                            </p>
                        </div>
                        {{-- Bloque derecho: filtro/Excel/pegar, y al extremo limpiar + agregar fila --}}
                        <div class="cartas-notificacion__toolbar-right">
                            <div class="cartas-notificacion__toolbar-actions">
                                <input
                                    type="search"
                                    class="form-input cartas-notificacion__filter-input"
                                    placeholder="Filtrar cédula o nombre…"
                                    title="Filtrar en memoria"
                                    aria-label="Filtrar en memoria por cédula o nombre"
                                    x-model="filterText"
                                    x-bind:disabled="processing || bulkApplying || importing"
                                >
                                <a
                                    x-bind:href="importTemplateUrl"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Descargar plantilla Excel"
                                    aria-label="Descargar plantilla Excel"
                                >
                                    <x-selfhst-microsoft-excel-2013 width="18" height="18" aria-hidden="true" />
                                </a>
                                <button
                                    type="button"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Importar Excel"
                                    aria-label="Importar Excel"
                                    x-on:click.prevent="$refs.excelFileInput.click()"
                                    x-bind:disabled="processing || bulkApplying || importing"
                                >
                                    <x-lucide-upload width="18" height="18" aria-hidden="true" />
                                </button>
                                <input
                                    type="file"
                                    class="sr-only"
                                    accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
                                    x-ref="excelFileInput"
                                    x-on:change="importExcel($event)"
                                >
                                <button
                                    type="button"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Agregar varias cédulas"
                                    aria-label="Agregar varias cédulas"
                                    x-on:click.prevent="openBulkCedulasModal()"
                                    x-bind:disabled="processing || bulkApplying || importing"
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
                                    x-bind:disabled="processing || bulkApplying || importing"
                                >
                                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                                </button>
                                <button
                                    type="button"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                    title="Agregar fila"
                                    aria-label="Agregar fila"
                                    x-on:click="addRow()"
                                    x-bind:disabled="processing || bulkApplying || importing || rows.length >= maxRows"
                                >
                                    <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="data-table-wrap desvinculaciones-masivos__table-wrap">
                        <table class="data-table desvinculaciones-masivos__table">
                            <thead>
                                <tr>
                                    <th class="desvinculaciones-masivos__col-cedula">Cédula</th>
                                    <th>Nombre completo</th>
                                    <th>Duración contrato</th>
                                    <th>Fecha terminación</th>
                                    <th>Firma</th>
                                    <th class="desvinculaciones-masivos__col-actions"></th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Filas editables a mano (mismo patrón Cartas Vacaciones); botón + en toolbar agrega más --}}
                                <template x-for="(row, index) in visibleRows()" :key="row.key">
                                    <tr>
                                        <td class="desvinculaciones-masivos__col-cedula">
                                            <input
                                                type="text"
                                                class="form-input desvinculaciones-masivos__cedula-input"
                                                x-model="row.cedula"
                                                x-on:blur="lookupRow(row)"
                                                x-on:keydown.enter.prevent="lookupRow(row)"
                                                placeholder="Cédula"
                                                autocomplete="off"
                                                x-bind:disabled="processing"
                                            >
                                            <p class="form-hint text-danger" x-show="row.warning" x-text="row.warning" x-cloak></p>
                                        </td>
                                        <td>
                                            <input
                                                type="text"
                                                class="form-input"
                                                x-model="row.nombre_completo"
                                                placeholder="Nombre completo"
                                                autocomplete="off"
                                                x-bind:disabled="processing"
                                            >
                                        </td>
                                        <td>
                                            {{-- Duración contrato: solo 6 o 12 meses (variable ${DURACION_CONTRATO}) --}}
                                            <div
                                                x-data="searchableSelect({
                                                    options: duracionContratoOptions,
                                                    value: row.duracion_contrato,
                                                    placeholder: 'Duración…',
                                                    searchPlaceholder: 'Buscar…',
                                                    required: true,
                                                    allowClear: false,
                                                })"
                                                x-effect="row.duracion_contrato = value"
                                            >
                                                @include('areas.gestion_humana.desvinculaciones.partials.alpine-searchable-select')
                                            </div>
                                        </td>
                                        <td>
                                            <input
                                                type="date"
                                                class="form-input"
                                                x-model="row.fecha_terminacion"
                                                x-bind:disabled="processing"
                                                required
                                                title="Fecha de terminación"
                                                aria-label="Fecha de terminación"
                                            >
                                        </td>
                                        <td>
                                            <div
                                                x-data="searchableSelect({
                                                    options: signatoryOptions,
                                                    value: row.signatory_id,
                                                    placeholder: 'Firma…',
                                                    searchPlaceholder: 'Buscar firma…',
                                                    required: true,
                                                    allowClear: false,
                                                })"
                                                x-effect="row.signatory_id = value"
                                            >
                                                @include('areas.gestion_humana.desvinculaciones.partials.alpine-searchable-select')
                                            </div>
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

                    <div class="desvinculaciones-masivos__toolbar">
                        <div class="desvinculaciones-masivos__toolbar-start">
                            <p class="panel-text" x-text="rows.length + ' / ' + maxRows + ' filas'"></p>
                        </div>
                        <div class="desvinculaciones-masivos__toolbar-end">
                            <button
                                type="button"
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                x-on:click="generateBatch()"
                                x-bind:disabled="processing || bulkApplying || importing || ! hasProcessableRows()"
                                x-bind:title="processing ? 'Generando…' : 'Generar cartas'"
                                x-bind:aria-label="processing ? 'Generando…' : 'Generar cartas'"
                            >
                                <x-lucide-download width="18" height="18" aria-hidden="true" />
                            </button>
                        </div>
                    </div>

                    <template x-if="bulkImportSummary">
                        <div
                            class="desvinculaciones-masivos__bulk-summary"
                            x-cloak
                            x-bind:class="{
                                'desvinculaciones-masivos__bulk-summary--warn': bulkImportSummary.with_warning > 0 || bulkImportSummary.truncated
                            }"
                        >
                            <div class="desvinculaciones-masivos__bulk-summary-head">
                                <div class="desvinculaciones-masivos__bulk-summary-title-wrap">
                                    <span class="desvinculaciones-masivos__bulk-summary-icon" aria-hidden="true">
                                        <x-lucide-clipboard-list width="18" height="18" />
                                    </span>
                                    <div>
                                        <p class="desvinculaciones-masivos__bulk-summary-title" x-text="bulkImportSummary.title || 'Resumen de carga'"></p>
                                        <p class="desvinculaciones-masivos__bulk-summary-lead">
                                            Resultado en la grilla (memoria)
                                            <span x-show="bulkImportSummary.truncated"> · Se limitó a {{ (int) $maxRows }}</span>
                                        </p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Cerrar resumen"
                                    aria-label="Cerrar resumen"
                                    x-on:click="bulkImportSummary = null"
                                >
                                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                                </button>
                            </div>

                            <div class="desvinculaciones-masivos__bulk-summary-stats" role="list">
                                <div class="desvinculaciones-masivos__bulk-stat desvinculaciones-masivos__bulk-stat--ok" role="listitem">
                                    <span class="desvinculaciones-masivos__bulk-stat-value" x-text="bulkImportSummary.added"></span>
                                    <span class="desvinculaciones-masivos__bulk-stat-label">Agregadas</span>
                                </div>
                                <div class="desvinculaciones-masivos__bulk-stat desvinculaciones-masivos__bulk-stat--dup" role="listitem">
                                    <span class="desvinculaciones-masivos__bulk-stat-value" x-text="bulkImportSummary.skipped_duplicate"></span>
                                    <span class="desvinculaciones-masivos__bulk-stat-label">Duplicadas</span>
                                </div>
                                <div
                                    class="desvinculaciones-masivos__bulk-stat"
                                    role="listitem"
                                    x-bind:class="bulkImportSummary.with_warning > 0
                                        ? 'desvinculaciones-masivos__bulk-stat--fail'
                                        : 'desvinculaciones-masivos__bulk-stat--muted'"
                                >
                                    <span class="desvinculaciones-masivos__bulk-stat-value" x-text="bulkImportSummary.with_warning"></span>
                                    <span class="desvinculaciones-masivos__bulk-stat-label">Con aviso</span>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="errorMessage">
                        <div class="alert alert--danger" x-text="errorMessage" x-cloak></div>
                    </template>

                    <template x-if="successMessage">
                        <div class="alert alert--success" x-text="successMessage" x-cloak></div>
                    </template>
                </div>

                {{-- Modal pegar cédulas --}}
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
                    <div class="modal-panel modal-panel--lg" role="dialog" aria-modal="true" aria-labelledby="cartas-notificacion-bulk-cedulas-title">
                        <div class="modal-card ficha-empleados-masivos-modal multi-cedula-modal">
                            <div class="ficha-empleados-masivos-modal__header">
                                <div class="ficha-empleados-masivos-modal__heading">
                                    <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                                        <x-lucide-clipboard-list width="18" height="18" aria-hidden="true" />
                                    </span>
                                    <div class="multi-cedula-modal__heading-copy">
                                        <h3 id="cartas-notificacion-bulk-cedulas-title" class="ficha-empleados-masivos-modal__title">Agregar varias cédulas</h3>
                                        <p class="ficha-empleados-masivos-modal__lead multi-cedula-modal__lead">
                                            Pegue o escriba cédulas: una por línea, o separadas por coma o punto y coma.
                                            Máximo {{ (int) $maxRows }}. Se omiten duplicadas en la grilla.
                                            Sin ficha o inactivas se agregan con aviso; el nombre se puede editar.
                                        </p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    class="ficha-empleados-masivos-modal__close"
                                    aria-label="Cerrar"
                                    x-on:click="closeBulkCedulasModal()"
                                    x-bind:disabled="bulkApplying"
                                >
                                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                                </button>
                            </div>

                            <div class="multi-cedula-modal__body">
                                <label class="form-label" for="cartas-notificacion-bulk-cedulas-textarea">Cédulas</label>
                                <textarea
                                    id="cartas-notificacion-bulk-cedulas-textarea"
                                    class="form-input multi-cedula-modal__textarea"
                                    rows="12"
                                    placeholder="1234567890&#10;9876543210"
                                    x-model="bulkPasteText"
                                    x-bind:disabled="bulkApplying"
                                ></textarea>
                                <p class="multi-cedula-modal__hint" x-text="bulkPasteHint()"></p>
                                <p class="multi-cedula-modal__error" x-show="bulkPasteError" x-text="bulkPasteError" x-cloak></p>
                            </div>

                            <div class="multi-cedula-modal__actions">
                                <button
                                    type="button"
                                    class="btn btn--secondary btn--sm"
                                    x-on:click="bulkPasteText = ''; bulkPasteError = ''"
                                    x-bind:disabled="bulkApplying"
                                >
                                    Limpiar
                                </button>
                                <button
                                    type="button"
                                    class="btn btn--primary btn--sm"
                                    x-on:click="applyBulkCedulas()"
                                    x-bind:disabled="bulkApplying || ! bulkPasteHasValues()"
                                >
                                    <span x-show="! bulkApplying">Agregar a la grilla</span>
                                    <span x-show="bulkApplying" x-cloak>Consultando…</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function cartasNotificacionGrid(config) {
                const emptyRow = () => ({
                    key: 'r-' + Math.random().toString(36).slice(2, 10),
                    cedula: '',
                    nombre_completo: '',
                    duracion_contrato: '',
                    fecha_terminacion: '',
                    signatory_id: '',
                    warning: '',
                    looking_up: false,
                });

                const maxRows = Number(config.maxRows) || 500;

                return {
                    lookupUrl: config.lookupUrl,
                    generateUrl: config.generateUrl,
                    importTemplateUrl: config.importTemplateUrl,
                    importPreviewUrl: config.importPreviewUrl,
                    csrf: config.csrf,
                    signatoryOptions: config.signatoryOptions || [],
                    duracionContratoOptions: config.duracionContratoOptions || [
                        { value: '6', label: '6' },
                        { value: '12', label: '12' },
                    ],
                    maxRows,
                    // Dos filas vacías al cargar (igual Cartas Vacaciones) para captura manual inmediata.
                    rows: Array.from({ length: 2 }, () => emptyRow()),
                    filterText: '',
                    processing: false,
                    importing: false,
                    errorMessage: '',
                    successMessage: '',
                    bulkPasteText: '',
                    bulkPasteError: '',
                    bulkApplying: false,
                    bulkImportSummary: null,
                    bulkModalOpen: false,

                    visibleRows() {
                        const q = String(this.filterText || '').trim().toLowerCase();
                        if (! q) {
                            return this.rows;
                        }
                        return this.rows.filter((row) => {
                            const cedula = String(row.cedula || '').toLowerCase();
                            const nombre = String(row.nombre_completo || '').toLowerCase();
                            return cedula.includes(q) || nombre.includes(q);
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

                    /** Limpiar = reiniciar a 2 filas vacías (captura manual) y resetear filtro. */
                    clearGrid() {
                        this.rows = Array.from({ length: 2 }, () => emptyRow());
                        this.filterText = '';
                        this.errorMessage = '';
                        this.successMessage = '';
                        this.bulkImportSummary = null;
                    },

                    hasProcessableRows() {
                        return this.rows.some((row) => String(row.cedula || '').trim() !== '');
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
                        const count = parsed.list.length;
                        let text = count + ' cédula(s) reconocida(s).';
                        if (parsed.truncated) {
                            text += ' Se usarán solo las primeras ' + this.maxRows + '.';
                        }
                        return text;
                    },

                    async lookupDocuments(documentNumbers) {
                        const response = await fetch(this.lookupUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ document_numbers: documentNumbers }),
                        });
                        const data = await response.json();
                        if (! response.ok || ! data.ok) {
                            throw new Error(
                                data.message
                                || (data.errors && Object.values(data.errors).flat()[0])
                                || 'No se pudo consultar las cédulas.'
                            );
                        }
                        return data.results || [];
                    },

                    fillOrPushRow(payload) {
                        const emptyIndex = this.rows.findIndex(
                            (row) => String(row.cedula || '').trim() === ''
                        );

                        const apply = (row) => {
                            row.cedula = payload.cedula;
                            row.nombre_completo = payload.nombre_completo || row.nombre_completo || '';
                            row.warning = payload.warning || '';
                            if (payload.duracion_contrato !== null && payload.duracion_contrato !== undefined && payload.duracion_contrato !== '') {
                                row.duracion_contrato = String(payload.duracion_contrato);
                            }
                            if (payload.fecha_terminacion) {
                                row.fecha_terminacion = payload.fecha_terminacion;
                            }
                            if (payload.signatory_id) {
                                row.signatory_id = String(payload.signatory_id);
                            }
                        };

                        if (emptyIndex >= 0) {
                            apply(this.rows[emptyIndex]);
                            return true;
                        }

                        if (this.rows.length >= this.maxRows) {
                            return false;
                        }

                        const row = emptyRow();
                        apply(row);
                        this.rows.push(row);
                        return true;
                    },

                    async applyBulkCedulas() {
                        this.bulkPasteError = '';
                        this.bulkImportSummary = null;

                        const parsed = this.parseBulkCedulas(this.bulkPasteText);
                        if (parsed.list.length === 0) {
                            this.bulkPasteError = 'Ingrese al menos una cédula.';
                            return;
                        }

                        const existing = new Set(
                            this.rows
                                .map((row) => String(row.cedula || '').trim())
                                .filter(Boolean)
                        );

                        const candidates = [];
                        let skippedDuplicate = 0;
                        for (const cedula of parsed.list) {
                            if (existing.has(cedula)) {
                                skippedDuplicate += 1;
                                continue;
                            }
                            existing.add(cedula);
                            candidates.push(cedula);
                        }

                        this.bulkApplying = true;
                        let added = 0;
                        let withWarning = 0;

                        try {
                            if (candidates.length === 0) {
                                this.bulkImportSummary = {
                                    title: 'Resumen de cédulas',
                                    added: 0,
                                    skipped_duplicate: skippedDuplicate,
                                    with_warning: 0,
                                    truncated: parsed.truncated,
                                };
                                this.bulkPasteText = '';
                                this.closeBulkCedulasModal();
                                return;
                            }

                            const results = await this.lookupDocuments(candidates);
                            for (const item of results) {
                                if (this.fillOrPushRow(item)) {
                                    added += 1;
                                    if (item.warning) {
                                        withWarning += 1;
                                    }
                                }
                            }

                            this.bulkImportSummary = {
                                title: 'Resumen de cédulas',
                                added,
                                skipped_duplicate: skippedDuplicate,
                                with_warning: withWarning,
                                truncated: parsed.truncated,
                            };
                            this.bulkPasteText = '';
                            this.closeBulkCedulasModal();
                        } catch (e) {
                            this.bulkPasteError = e.message || 'Error al consultar las cédulas.';
                        } finally {
                            this.bulkApplying = false;
                        }
                    },

                    async lookupRow(row) {
                        const cedula = String(row.cedula || '').trim();
                        row.warning = '';

                        if (cedula === '') {
                            return;
                        }

                        row.looking_up = true;
                        try {
                            const results = await this.lookupDocuments([cedula]);
                            const item = results[0];
                            if (! item) {
                                row.warning = 'No se pudo consultar la cédula.';
                                return;
                            }
                            row.cedula = item.cedula;
                            if (item.nombre_completo) {
                                row.nombre_completo = item.nombre_completo;
                            }
                            row.warning = item.warning || '';
                        } catch (e) {
                            row.warning = e.message || 'Error al consultar la cédula.';
                        } finally {
                            row.looking_up = false;
                        }
                    },

                    async importExcel(event) {
                        const input = event.target;
                        const file = input.files && input.files[0];
                        input.value = '';

                        if (! file) {
                            return;
                        }

                        this.errorMessage = '';
                        this.successMessage = '';
                        this.bulkImportSummary = null;
                        this.importing = true;

                        try {
                            const formData = new FormData();
                            formData.append('file', file);

                            const response = await fetch(this.importPreviewUrl, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': this.csrf,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: formData,
                            });

                            const data = await response.json();
                            if (! response.ok || ! data.ok) {
                                this.errorMessage = data.message
                                    || (data.errors && Object.values(data.errors).flat()[0])
                                    || 'No se pudo leer el Excel.';
                                return;
                            }

                            const existing = new Set(
                                this.rows
                                    .map((row) => String(row.cedula || '').trim().toLowerCase())
                                    .filter(Boolean)
                            );

                            let added = 0;
                            let skippedDuplicate = 0;
                            let withWarning = 0;
                            let truncatedMerge = false;

                            for (const item of (data.rows || [])) {
                                const key = String(item.cedula || '').trim().toLowerCase();
                                if (! key) {
                                    continue;
                                }
                                if (existing.has(key)) {
                                    skippedDuplicate += 1;
                                    continue;
                                }
                                if (this.rows.length >= this.maxRows) {
                                    truncatedMerge = true;
                                    break;
                                }
                                existing.add(key);
                                if (this.fillOrPushRow(item)) {
                                    added += 1;
                                    if (item.warning) {
                                        withWarning += 1;
                                    }
                                }
                            }

                            this.bulkImportSummary = {
                                title: 'Resumen de Excel',
                                added,
                                skipped_duplicate: skippedDuplicate,
                                with_warning: withWarning,
                                truncated: Boolean(data.summary && data.summary.truncated) || truncatedMerge,
                            };
                        } catch (e) {
                            this.errorMessage = 'Error de red al importar el Excel.';
                        } finally {
                            this.importing = false;
                        }
                    },

                    buildPayloadRows() {
                        return this.rows
                            .filter((row) => String(row.cedula || '').trim() !== '')
                            .map((row) => ({
                                cedula: String(row.cedula).trim(),
                                nombre_completo: String(row.nombre_completo || '').trim(),
                                duracion_contrato: row.duracion_contrato !== '' && row.duracion_contrato != null
                                    ? Number(row.duracion_contrato)
                                    : null,
                                fecha_terminacion: row.fecha_terminacion || null,
                                signatory_id: row.signatory_id ? Number(row.signatory_id) : null,
                            }));
                    },

                    filenameFromDisposition(header) {
                        if (! header) {
                            return 'cartas_notificacion.docx';
                        }
                        const utfMatch = header.match(/filename\*=UTF-8''([^;]+)/i);
                        if (utfMatch) {
                            try {
                                return decodeURIComponent(utfMatch[1]);
                            } catch (e) {
                                return utfMatch[1];
                            }
                        }
                        const plain = header.match(/filename="?([^";]+)"?/i);
                        return plain ? plain[1] : 'cartas_notificacion.docx';
                    },

                    async generateBatch() {
                        this.errorMessage = '';
                        this.successMessage = '';
                        this.bulkImportSummary = null;

                        const rows = this.buildPayloadRows();
                        if (rows.length === 0) {
                            this.errorMessage = 'Agregue al menos una fila con cédula.';
                            return;
                        }

                        this.processing = true;
                        try {
                            const response = await fetch(this.generateUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json, application/octet-stream, */*',
                                    'X-CSRF-TOKEN': this.csrf,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: JSON.stringify({ rows }),
                            });

                            const contentType = response.headers.get('Content-Type') || '';

                            if (! response.ok) {
                                let message = 'No se pudo generar las cartas.';
                                if (contentType.includes('application/json')) {
                                    const data = await response.json();
                                    message = data.message
                                        || (data.errors && Object.values(data.errors).flat()[0])
                                        || message;
                                }
                                this.errorMessage = message;
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

                            this.successMessage = rows.length === 1
                                ? 'Carta generada (.docx).'
                                : ('Lote generado: ' + rows.length + ' cartas (.zip).');
                        } catch (e) {
                            this.errorMessage = 'Error de red al generar las cartas.';
                        } finally {
                            this.processing = false;
                        }
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
