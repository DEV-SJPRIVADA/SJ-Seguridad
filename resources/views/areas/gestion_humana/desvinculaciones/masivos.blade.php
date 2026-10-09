<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.desvinculaciones.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section desvinculaciones-masivos-page">
        <div class="app-container">
            <div
                class="panel desvinculaciones-masivos"
                x-data="desvinculacionesMasivos(@js([
                    'canMasivos' => (bool) $canMasivos,
                    'canForceNovedadesConflict' => (bool) ($canForceNovedadesConflict ?? false),
                    'lookupUrl' => route('gestion-humana.desvinculaciones.masivos.lookup'),
                    'processUrl' => route('gestion-humana.desvinculaciones.masivos.process'),
                    'csrf' => csrf_token(),
                    'templateOptions' => $templateOptions,
                    'signatoryOptions' => $signatoryOptions,
                    'causeOptions' => $causeOptions,
                    'rehireOptions' => $rehireOptions,
                ]))"
            >
                <div class="panel__body">
                    @unless ($canMasivos)
                        <p class="panel-text">
                            Tiene acceso de lectura al tablero. Para ejecutar el lote necesita el permiso de Masivos.
                        </p>
                    @else
                        <div class="desvinculaciones-masivos__table-top">
                            <div class="cursos-registros-page__table-actions">
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
                                <button
                                    type="button"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Limpiar vista"
                                    aria-label="Limpiar vista"
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
                                    x-bind:disabled="processing || bulkApplying"
                                >
                                    <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                </button>
                            </div>
                        </div>

                        <div class="data-table-wrap desvinculaciones-masivos__table-wrap">
                            <table class="data-table desvinculaciones-masivos__table">
                                <thead>
                                    <tr>
                                        <th class="desvinculaciones-masivos__col-cedula">CÉDULA</th>
                                        <th>NOMBRE</th>
                                        <th>FECHA DESVINCULACIÓN</th>
                                        <th>TIPO CARTA</th>
                                        <th>FIRMA</th>
                                        <th>Causal</th>
                                        <th>Recontratable</th>
                                        <th>Observaciones</th>
                                        <th class="desvinculaciones-masivos__col-actions"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row, index) in rows" :key="row.key">
                                        <tr>
                                            <td class="desvinculaciones-masivos__col-cedula">
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
                                                <p class="form-hint text-danger" x-show="row.lookup_error" x-text="row.lookup_error" x-cloak></p>
                                            </td>
                                            <td>
                                                <span class="desvinculaciones-masivos__name" x-text="row.full_name || '—'"></span>
                                            </td>
                                            <td>
                                                <input
                                                    type="date"
                                                    class="form-input"
                                                    x-model="row.termination_date"
                                                    x-bind:disabled="processing"
                                                    required
                                                >
                                            </td>
                                            <td>
                                                <div
                                                    x-data="searchableSelect({
                                                        options: templateOptions,
                                                        value: row.template_id,
                                                        placeholder: 'Tipo carta…',
                                                        searchPlaceholder: 'Buscar plantilla…',
                                                        required: true,
                                                        allowClear: false,
                                                    })"
                                                    x-effect="row.template_id = value"
                                                >
                                                    @include('areas.gestion_humana.desvinculaciones.partials.alpine-searchable-select')
                                                </div>
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
                                            <td>
                                                <div
                                                    x-data="searchableSelect({
                                                        options: causeOptions,
                                                        value: row.termination_cause_code,
                                                        placeholder: 'Opcional…',
                                                        searchPlaceholder: 'Buscar causal…',
                                                        required: false,
                                                        allowClear: true,
                                                    })"
                                                    x-effect="row.termination_cause_code = value"
                                                >
                                                    @include('areas.gestion_humana.desvinculaciones.partials.alpine-searchable-select')
                                                </div>
                                            </td>
                                            <td>
                                                <div
                                                    x-data="searchableSelect({
                                                        options: rehireOptions,
                                                        value: row.is_rehireable,
                                                        placeholder: 'Opcional…',
                                                        searchPlaceholder: 'Buscar…',
                                                        required: false,
                                                        allowClear: true,
                                                    })"
                                                    x-effect="row.is_rehireable = value"
                                                >
                                                    @include('areas.gestion_humana.desvinculaciones.partials.alpine-searchable-select')
                                                </div>
                                            </td>
                                            <td>
                                                <input
                                                    type="text"
                                                    class="form-input"
                                                    x-model="row.termination_notes"
                                                    placeholder="Opcional"
                                                    maxlength="1000"
                                                    x-bind:disabled="processing"
                                                >
                                            </td>
                                            <td class="table-actions">
                                                <button
                                                    type="button"
                                                    class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger"
                                                    x-on:click="removeRow(index)"
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
                                <template x-if="canForceNovedadesConflict">
                                    <label class="form-check">
                                        <input type="checkbox" class="form-check" x-model="forceNovedadesConflict">
                                        <span>Forzar pese a cruce con novedades (solo super-admin)</span>
                                    </label>
                                </template>
                            </div>
                            <div class="desvinculaciones-masivos__toolbar-end">
                                <button
                                    type="button"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                    x-on:click="processBatch()"
                                    x-bind:disabled="processing || bulkApplying || ! hasProcessableRows()"
                                    x-bind:title="processing ? 'Procesando…' : 'Desvincular'"
                                    x-bind:aria-label="processing ? 'Procesando…' : 'Desvincular'"
                                >
                                    <x-lucide-user-round-x width="18" height="18" aria-hidden="true" />
                                </button>
                                <button
                                    type="button"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    x-show="report"
                                    x-cloak
                                    x-on:click="clearGrid()"
                                    x-bind:disabled="processing || bulkApplying"
                                    title="Limpiar grilla"
                                    aria-label="Limpiar grilla"
                                >
                                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                                </button>
                                <a
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    x-show="downloadUrl"
                                    x-cloak
                                    x-bind:href="downloadUrl"
                                    download
                                    title="Descargar ZIP"
                                    aria-label="Descargar ZIP"
                                >
                                    <x-lucide-download width="18" height="18" aria-hidden="true" />
                                </a>
                            </div>
                        </div>

                        <template x-if="bulkImportSummary">
                            <div
                                class="desvinculaciones-masivos__bulk-summary"
                                x-cloak
                                x-bind:class="{
                                    'desvinculaciones-masivos__bulk-summary--warn': bulkImportSummary.not_found > 0 || bulkImportSummary.truncated
                                }"
                            >
                                <div class="desvinculaciones-masivos__bulk-summary-head">
                                    <div class="desvinculaciones-masivos__bulk-summary-title-wrap">
                                        <span class="desvinculaciones-masivos__bulk-summary-icon" aria-hidden="true">
                                            <x-lucide-clipboard-list width="18" height="18" />
                                        </span>
                                        <div>
                                            <p class="desvinculaciones-masivos__bulk-summary-title">Resumen de cédulas</p>
                                            <p class="desvinculaciones-masivos__bulk-summary-lead">
                                                Resultado del pegado en la grilla
                                                <span x-show="bulkImportSummary.truncated"> · Se limitó a 500</span>
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
                                        x-bind:class="bulkImportSummary.not_found > 0
                                            ? 'desvinculaciones-masivos__bulk-stat--fail'
                                            : 'desvinculaciones-masivos__bulk-stat--muted'"
                                    >
                                        <span class="desvinculaciones-masivos__bulk-stat-value" x-text="bulkImportSummary.not_found"></span>
                                        <span class="desvinculaciones-masivos__bulk-stat-label">No procesables</span>
                                    </div>
                                </div>

                                <template x-if="bulkImportSummary.not_found_items.length">
                                    <div class="desvinculaciones-masivos__bulk-summary-details">
                                        <p class="desvinculaciones-masivos__bulk-summary-details-title">Detalle no procesable</p>
                                        <ul class="desvinculaciones-masivos__bulk-summary-list">
                                            <template x-for="item in bulkImportSummary.not_found_items" :key="'nf-' + item.document_number">
                                                <li>
                                                    <span class="desvinculaciones-masivos__bulk-summary-cedula" x-text="item.document_number"></span>
                                                    <span x-text="item.message"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <template x-if="report">
                            <div class="desvinculaciones-masivos__report" x-cloak>
                                <h3 class="panel-title">Resultado del lote</h3>
                                <p class="panel-text">
                                    Total: <span x-text="report.summary.total"></span>
                                    · Exitosos: <span x-text="report.summary.ok"></span>
                                    · Fallidos / avisos: <span x-text="report.summary.failed"></span>
                                    · Cartas: <span x-text="report.summary.letters"></span>
                                </p>

                                <template x-if="report.ok.length">
                                    <div class="desvinculaciones-masivos__report-block">
                                        <h4 class="form-label">Exitosos</h4>
                                        <ul class="desvinculaciones-masivos__report-list">
                                            <template x-for="item in report.ok" :key="'ok-' + item.row">
                                                <li>
                                                    Fila <span x-text="item.row"></span>:
                                                    <span x-text="item.document_number"></span>
                                                    — <span x-text="item.full_name"></span>
                                                    <span x-show="item.letter_generated"> (carta OK)</span>
                                                    <span x-show="! item.letter_generated"> (sin carta)</span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>

                                <template x-if="report.failed.length">
                                    <div class="desvinculaciones-masivos__report-block">
                                        <h4 class="form-label">Fallos / avisos</h4>
                                        <ul class="desvinculaciones-masivos__report-list desvinculaciones-masivos__report-list--fail">
                                            <template x-for="item in report.failed" :key="'fail-' + item.row + '-' + item.type">
                                                <li>
                                                    Fila <span x-text="item.row"></span>
                                                    (<span x-text="item.document_number || '—'"></span>)
                                                    [<span x-text="item.type"></span>]:
                                                    <span x-text="item.message"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <template x-if="errorMessage">
                            <div class="alert alert--danger" x-text="errorMessage" x-cloak></div>
                        </template>
                    @endunless
                </div>

                @if ($canMasivos)
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
                        <div class="modal-panel modal-panel--lg" role="dialog" aria-modal="true" aria-labelledby="desvinculaciones-bulk-cedulas-title">
                            <div class="modal-card ficha-empleados-masivos-modal multi-cedula-modal">
                                <div class="ficha-empleados-masivos-modal__header">
                                    <div class="ficha-empleados-masivos-modal__heading">
                                        <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                                            <x-lucide-clipboard-list width="18" height="18" aria-hidden="true" />
                                        </span>
                                        <div class="multi-cedula-modal__heading-copy">
                                            <h3 id="desvinculaciones-bulk-cedulas-title" class="ficha-empleados-masivos-modal__title">Agregar varias cédulas</h3>
                                            <p class="ficha-empleados-masivos-modal__lead multi-cedula-modal__lead">
                                                Pegue o escriba cédulas: una por línea, o separadas por coma o punto y coma. Máximo 500. Se omite duplicadas en la grilla y las que no estén activas / no procesables.
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
                                    <label class="form-label" for="desvinculaciones-bulk-cedulas-textarea">Cédulas</label>
                                    <textarea
                                        id="desvinculaciones-bulk-cedulas-textarea"
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
                @endif
            </div>
        </div>
    </div>

    @if ($canMasivos)
        @push('scripts')
            <script>
                function desvinculacionesMasivos(config) {
                    const emptyRow = () => ({
                        key: 'r-' + Math.random().toString(36).slice(2, 10),
                        document_number: '',
                        full_name: '',
                        ficha_entry_id: null,
                        termination_date: '',
                        template_id: '',
                        signatory_id: '',
                        termination_cause_code: '',
                        is_rehireable: '',
                        termination_notes: '',
                        lookup_error: '',
                        looking_up: false,
                    });

                    const MAX_BULK_CEDULAS = 500;

                    return {
                        canMasivos: Boolean(config.canMasivos),
                        canForceNovedadesConflict: Boolean(config.canForceNovedadesConflict),
                        forceNovedadesConflict: false,
                        lookupUrl: config.lookupUrl,
                        processUrl: config.processUrl,
                        csrf: config.csrf,
                        templateOptions: config.templateOptions || [],
                        signatoryOptions: config.signatoryOptions || [],
                        causeOptions: config.causeOptions || [],
                        rehireOptions: config.rehireOptions || [],
                        rows: Array.from({ length: 2 }, () => emptyRow()),
                        processing: false,
                        report: null,
                        downloadUrl: null,
                        errorMessage: '',
                        bulkPasteText: '',
                        bulkPasteError: '',
                        bulkApplying: false,
                        bulkImportSummary: null,
                        bulkModalOpen: false,

                        addRow() {
                            this.rows.push(emptyRow());
                        },

                        removeRow(index) {
                            if (this.rows.length <= 1) {
                                return;
                            }
                            this.rows.splice(index, 1);
                        },

                        clearGrid() {
                            this.rows = Array.from({ length: 2 }, () => emptyRow());
                            this.report = null;
                            this.downloadUrl = null;
                            this.errorMessage = '';
                            this.bulkImportSummary = null;
                        },

                        hasProcessableRows() {
                            return this.rows.some((row) => String(row.document_number || '').trim() !== '');
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
                                truncated: unique.length > MAX_BULK_CEDULAS,
                                list: unique.slice(0, MAX_BULK_CEDULAS),
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
                                text += ' Se usarán solo las primeras ' + MAX_BULK_CEDULAS + '.';
                            }
                            return text;
                        },

                        async lookupDocument(documentNumber) {
                            try {
                                const response = await fetch(this.lookupUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': this.csrf,
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    body: JSON.stringify({ document_number: documentNumber }),
                                });
                                const data = await response.json();
                                if (! response.ok || ! data.ok) {
                                    return {
                                        ok: false,
                                        document_number: documentNumber,
                                        message: data.message || (data.errors?.document_number?.[0]) || 'No procesable.',
                                    };
                                }

                                return {
                                    ok: true,
                                    document_number: data.document_number,
                                    full_name: data.full_name,
                                    ficha_entry_id: data.ficha_entry_id,
                                };
                            } catch (e) {
                                return {
                                    ok: false,
                                    document_number: documentNumber,
                                    message: 'Error al consultar la cédula.',
                                };
                            }
                        },

                        fillOrPushRow(payload) {
                            const emptyIndex = this.rows.findIndex(
                                (row) => String(row.document_number || '').trim() === ''
                            );

                            if (emptyIndex >= 0) {
                                this.rows[emptyIndex].document_number = payload.document_number;
                                this.rows[emptyIndex].full_name = payload.full_name;
                                this.rows[emptyIndex].ficha_entry_id = payload.ficha_entry_id;
                                this.rows[emptyIndex].lookup_error = '';
                                return;
                            }

                            const row = emptyRow();
                            row.document_number = payload.document_number;
                            row.full_name = payload.full_name;
                            row.ficha_entry_id = payload.ficha_entry_id;
                            this.rows.push(row);
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
                                    .map((row) => String(row.document_number || '').trim())
                                    .filter(Boolean)
                            );

                            const candidates = [];
                            let skippedDuplicate = 0;
                            for (const documentNumber of parsed.list) {
                                if (existing.has(documentNumber)) {
                                    skippedDuplicate += 1;
                                    continue;
                                }
                                existing.add(documentNumber);
                                candidates.push(documentNumber);
                            }

                            this.bulkApplying = true;
                            const notFoundItems = [];
                            let added = 0;

                            try {
                                for (const documentNumber of candidates) {
                                    const result = await this.lookupDocument(documentNumber);
                                    if (! result.ok) {
                                        notFoundItems.push({
                                            document_number: result.document_number,
                                            message: result.message,
                                        });
                                        continue;
                                    }

                                    this.fillOrPushRow({
                                        document_number: result.document_number,
                                        full_name: result.full_name,
                                        ficha_entry_id: result.ficha_entry_id,
                                    });
                                    added += 1;
                                }

                                this.bulkImportSummary = {
                                    added,
                                    skipped_duplicate: skippedDuplicate,
                                    not_found: notFoundItems.length,
                                    not_found_items: notFoundItems.slice(0, 50),
                                    truncated: parsed.truncated,
                                };
                                this.bulkPasteText = '';
                                this.closeBulkCedulasModal();
                            } finally {
                                this.bulkApplying = false;
                            }
                        },

                        async lookupRow(row) {
                            const documentNumber = String(row.document_number || '').trim();
                            row.lookup_error = '';
                            row.full_name = '';
                            row.ficha_entry_id = null;

                            if (documentNumber === '') {
                                return;
                            }

                            row.looking_up = true;
                            try {
                                const result = await this.lookupDocument(documentNumber);
                                if (! result.ok) {
                                    row.lookup_error = result.message;
                                    return;
                                }
                                row.document_number = result.document_number;
                                row.full_name = result.full_name;
                                row.ficha_entry_id = result.ficha_entry_id;
                            } finally {
                                row.looking_up = false;
                            }
                        },

                        buildPayloadRows() {
                            return this.rows
                                .filter((row) => String(row.document_number || '').trim() !== '')
                                .map((row) => ({
                                    document_number: String(row.document_number).trim(),
                                    termination_date: row.termination_date || null,
                                    template_id: row.template_id ? Number(row.template_id) : null,
                                    signatory_id: row.signatory_id ? Number(row.signatory_id) : null,
                                    termination_cause_code: row.termination_cause_code || null,
                                    is_rehireable: row.is_rehireable === '' || row.is_rehireable === null
                                        ? null
                                        : row.is_rehireable,
                                    termination_notes: row.termination_notes || null,
                                }));
                        },

                        async processBatch() {
                            this.errorMessage = '';
                            this.report = null;
                            this.downloadUrl = null;
                            this.bulkImportSummary = null;

                            const rows = this.buildPayloadRows();
                            if (rows.length === 0) {
                                this.errorMessage = 'Agregue al menos una fila con cédula.';
                                return;
                            }

                            this.processing = true;
                            try {
                                const response = await fetch(this.processUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': this.csrf,
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    body: JSON.stringify({
                                        rows,
                                        force_novedades_conflict: this.canForceNovedadesConflict && this.forceNovedadesConflict ? 1 : 0,
                                    }),
                                });

                                const data = await response.json();

                                if (response.status === 422 && data.errors) {
                                    const first = Object.values(data.errors).flat()[0];
                                    this.errorMessage = first || 'Revise la validación del lote.';
                                    return;
                                }

                                if (! response.ok) {
                                    this.errorMessage = data.message || 'No se pudo procesar el lote.';
                                    return;
                                }

                                this.report = data;
                                this.downloadUrl = data.download_url || null;
                                this.rows = Array.from({ length: 2 }, () => emptyRow());

                                if (this.downloadUrl) {
                                    window.location.href = this.downloadUrl;
                                }
                            } catch (e) {
                                this.errorMessage = 'Error de red al procesar el lote.';
                            } finally {
                                this.processing = false;
                            }
                        },
                    };
                }
            </script>
        @endpush
    @endif
</x-app-layout>
