<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.desvinculaciones.partials.subnav', ['subTabs' => $subTabs])
        <div class="app-container">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">Masivos</h2>
                <p class="panel-text">Gestion humana — desvinculaciones masivas</p>
            </div>
        </div>
    </x-slot>

    <div class="page-section desvinculaciones-masivos-page">
        <div class="app-container">
            <div
                class="panel desvinculaciones-masivos"
                x-data="desvinculacionesMasivos(@js([
                    'canMasivos' => (bool) $canMasivos,
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
                        <div class="data-table-wrap desvinculaciones-masivos__table-wrap">
                            <table class="data-table desvinculaciones-masivos__table">
                                <thead>
                                    <tr>
                                        <th class="desvinculaciones-masivos__col-cedula">CEDULA</th>
                                        <th>NOMBRE</th>
                                        <th>FECHA DESVINCULACION</th>
                                        <th>TIPO CARTA</th>
                                        <th>FIRMA</th>
                                        <th>Causal</th>
                                        <th>Recontratable</th>
                                        <th>Observaciones</th>
                                        <th></th>
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
                                            <td>
                                                <button
                                                    type="button"
                                                    class="btn btn--secondary btn--sm"
                                                    x-on:click="removeRow(index)"
                                                    x-bind:disabled="processing || rows.length <= 1"
                                                    title="Borrar fila"
                                                >
                                                    Borrar
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div class="desvinculaciones-masivos__toolbar">
                            <button type="button" class="btn btn--secondary" x-on:click="addRow()" x-bind:disabled="processing">
                                + fila
                            </button>
                            <button
                                type="button"
                                class="btn btn--primary"
                                x-on:click="processBatch()"
                                x-bind:disabled="processing || ! hasProcessableRows()"
                            >
                                <span x-show="! processing">Desvincular</span>
                                <span x-show="processing" x-cloak>Procesando…</span>
                            </button>
                            <button
                                type="button"
                                class="btn btn--secondary"
                                x-show="report"
                                x-cloak
                                x-on:click="clearGrid()"
                            >
                                Limpiar grilla
                            </button>
                            <a
                                class="btn btn--secondary"
                                x-show="downloadUrl"
                                x-cloak
                                x-bind:href="downloadUrl"
                                download
                            >
                                Descargar ZIP
                            </a>
                        </div>

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

                    return {
                        canMasivos: Boolean(config.canMasivos),
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
                        },

                        hasProcessableRows() {
                            return this.rows.some((row) => String(row.document_number || '').trim() !== '');
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
                                    row.lookup_error = data.message || (data.errors?.document_number?.[0]) || 'No procesable.';
                                    return;
                                }
                                row.document_number = data.document_number;
                                row.full_name = data.full_name;
                                row.ficha_entry_id = data.ficha_entry_id;
                            } catch (e) {
                                row.lookup_error = 'Error al consultar la cedula.';
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

                            const rows = this.buildPayloadRows();
                            if (rows.length === 0) {
                                this.errorMessage = 'Agregue al menos una fila con cedula.';
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
                                    body: JSON.stringify({ rows }),
                                });

                                const data = await response.json();

                                if (response.status === 422 && data.errors) {
                                    const first = Object.values(data.errors).flat()[0];
                                    this.errorMessage = first || 'Revise la validacion del lote.';
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
