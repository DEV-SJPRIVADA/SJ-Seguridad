<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.desvinculaciones.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Seguimientos: DataTables server-side + filtros Alpine; checks/fecha/revert con eventos delegados --}}
    <div class="page-section desvinculaciones-seguimientos-page req-manage-page">
        <div class="app-container">
            <div
                class="panel desvinculaciones-seguimientos"
                x-data="desvinculacionesSeguimientos(@js([
                    'canEdit' => (bool) $canEditSeguimientos,
                    'datatableUrl' => $datatableUrl,
                    'exportUrl' => $exportUrl,
                    'importHistoricoUrl' => $importHistoricoUrl ?? '',
                    'csrf' => csrf_token(),
                    'checkFields' => $checkFields,
                    'checkLabels' => $checkLabels,
                    'initialQ' => $filters['q'] ?? '',
                    'initialStatus' => $filters['status'] ?? 'incompletos',
                    'initialRehireable' => $filters['rehireable'] ?? '',
                    'initialFechaCampo' => $filters['fecha_campo'] ?? \App\Models\EmployeeTerminationFollowup::DEFAULT_DATE_FILTER_FIELD,
                    'initialFechaDesde' => $filters['fecha_desde'] ?? '',
                    'initialFechaHasta' => $filters['fecha_hasta'] ?? '',
                    'defaultFechaCampo' => \App\Models\EmployeeTerminationFollowup::DEFAULT_DATE_FILTER_FIELD,
                ]))"
            >
                <div class="panel__body panel__body--compact req-manage-shell">
                    @php
                        $hasActiveFilters = ($filters['q'] ?? '') !== ''
                            || ($filters['fecha_desde'] ?? '') !== ''
                            || ($filters['fecha_hasta'] ?? '') !== ''
                            || (($filters['status'] ?? 'incompletos') !== 'incompletos')
                            || (($filters['rehireable'] ?? '') !== '');
                    @endphp

                    @unless ($canEditSeguimientos)
                        <p class="panel-text desvinculaciones-seguimientos__readonly-notice">
                            Vista de solo lectura. Para editar checks o fecha de nómina necesita el permiso de edición de Seguimientos.
                        </p>
                    @endunless

                    <details class="req-manage-filters req-manage-filters__panel desvinculaciones-seguimientos__filters" @if ($hasActiveFilters) open @endif>
                        <summary class="req-manage-filters__panel-toggle">
                            <span>Filtros</span>
                            <span class="req-manage-filters__panel-badge" x-show="hasActiveFilters" x-cloak>Activos</span>
                        </summary>
                        <div class="req-manage-filters__panel-body">
                            <div class="req-manage-filters__toolbar desvinculaciones-seguimientos__toolbar">
                                <div class="desvinculaciones-seguimientos__filters-main">
                                    <div class="req-manage-filters__search-col desvinculaciones-seguimientos__search">
                                        <label class="req-manage-filters__label" for="seguimientos-search-q">Buscar</label>
                                        <div class="req-manage-filters__search-group">
                                            <input
                                                id="seguimientos-search-q"
                                                type="search"
                                                class="form-input"
                                                placeholder="Cédula o nombre"
                                                x-model="q"
                                                x-on:keydown.enter.prevent="applyFilters()"
                                                autocomplete="off"
                                            >
                                            <button
                                                type="button"
                                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                                title="Filtrar"
                                                aria-label="Filtrar"
                                                x-on:click="applyFilters()"
                                            >
                                                <x-lucide-search width="18" height="18" aria-hidden="true" />
                                            </button>
                                        </div>
                                    </div>

                                    <div class="form-field desvinculaciones-seguimientos__fecha-campo">
                                        <label class="req-manage-filters__label" for="seguimientos-fecha-campo">Campo fecha</label>
                                        <x-searchable-select
                                            id="seguimientos-fecha-campo"
                                            name="fecha_campo"
                                            :options="$fechaCampoOptions"
                                            :value="$filters['fecha_campo'] ?? \App\Models\EmployeeTerminationFollowup::DEFAULT_DATE_FILTER_FIELD"
                                            placeholder="Campo fecha…"
                                            :allow-clear="false"
                                            :required="false"
                                            x-on:change="onFechaCampoChange($event)"
                                        />
                                    </div>

                                    <div class="form-field desvinculaciones-seguimientos__rehireable">
                                        <label class="req-manage-filters__label" for="seguimientos-rehireable">Recontratable</label>
                                        <x-searchable-select
                                            id="seguimientos-rehireable"
                                            name="rehireable"
                                            :options="$rehireableOptions"
                                            :value="$filters['rehireable'] ?? ''"
                                            placeholder="Todos"
                                            :allow-clear="true"
                                            :required="false"
                                            x-on:change="onRehireableChange($event)"
                                        />
                                    </div>

                                    <div class="desvinculaciones-seguimientos__date-range" role="group" aria-label="Rango de fechas">
                                        <div class="desvinculaciones-seguimientos__date-fields">
                                            <div class="desvinculaciones-seguimientos__date-field">
                                                <label class="req-manage-filters__label" for="seguimientos-fecha-desde">Desde</label>
                                                <input
                                                    id="seguimientos-fecha-desde"
                                                    type="date"
                                                    class="form-input desvinculaciones-seguimientos__date-input"
                                                    x-model="fechaDesde"
                                                    x-on:change="onDateRangeChange()"
                                                >
                                            </div>
                                            <div class="desvinculaciones-seguimientos__date-field">
                                                <label class="req-manage-filters__label" for="seguimientos-fecha-hasta">Hasta</label>
                                                <input
                                                    id="seguimientos-fecha-hasta"
                                                    type="date"
                                                    class="form-input desvinculaciones-seguimientos__date-input"
                                                    x-model="fechaHasta"
                                                    x-on:change="onDateRangeChange()"
                                                >
                                            </div>
                                        </div>
                                    </div>

                                    <div class="desvinculaciones-seguimientos__filter-actions">
                                        <button
                                            type="button"
                                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                            title="Limpiar filtros"
                                            aria-label="Limpiar filtros"
                                            x-on:click="clearFilters()"
                                        >
                                            <x-lucide-x width="18" height="18" aria-hidden="true" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="desvinculaciones-seguimientos__status-chips" role="group" aria-label="Filtro de estado">
                                <template x-for="opt in statusOptions" :key="opt.value">
                                    <button
                                        type="button"
                                        class="module-tab"
                                        x-bind:class="{ 'module-tab--active': status === opt.value }"
                                        x-on:click="setStatus(opt.value)"
                                        x-text="opt.label"
                                    ></button>
                                </template>
                            </div>
                        </div>
                    </details>

                    <div class="cursos-registros-page__table-toolbar desvinculaciones-seguimientos__table-toolbar">
                        <p class="req-manage-filters__meta">
                            <strong id="desvinculaciones-seguimientos-count" x-text="totalFormatted">…</strong>
                            <span x-text="total === 1 ? 'seguimiento' : 'seguimientos'"></span>
                            <span x-show="savingCount > 0" x-cloak> · Guardando…</span>
                            <span x-show="saveError" class="text-danger" x-cloak x-text="saveError"></span>
                        </p>

                        <div class="cursos-registros-page__table-actions desvinculaciones-seguimientos__export">
                            @if ($canEditSeguimientos)
                                <button
                                    type="button"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                    title="Importar histórico (Excel NOVEDADES)"
                                    aria-label="Importar histórico"
                                    x-on:click="openImportModal()"
                                >
                                    <x-lucide-upload width="18" height="18" aria-hidden="true" />
                                </button>
                            @endif
                            <a
                                x-bind:href="exportHref"
                                class="btn btn--secondary btn--sm desvinculaciones-seguimientos__export-btn"
                                title="Exportar a Excel"
                                aria-label="Exportar a Excel"
                            >
                                <x-selfhst-microsoft-excel-2013 width="16" height="16" aria-hidden="true" />
                            </a>
                        </div>
                    </div>

                    <div class="data-table-wrap req-manage-shell__table desvinculaciones-seguimientos__table-wrap data-table-wrap--booting">
                        @include('partials.data-table-loader')
                        <table
                            id="desvinculaciones-seguimientos-datatable"
                            class="data-table desvinculaciones-seguimientos__table js-desvinculaciones-seguimientos-datatable"
                            data-dt-url="{{ $datatableUrl }}"
                            style="width:100%"
                        >
                            <thead>
                                <tr>
                                    <th class="desvinculaciones-seguimientos__col-id">No</th>
                                    <th class="desvinculaciones-seguimientos__col-doc">CÉDULA</th>
                                    <th class="desvinculaciones-seguimientos__col-name">NOMBRE Y APELLIDOS</th>
                                    <th class="desvinculaciones-seguimientos__col-wide">CARGO</th>
                                    <th class="desvinculaciones-seguimientos__col-wide">TIPO DESVINCULACIÓN</th>
                                    <th class="desvinculaciones-seguimientos__col-wide">FECHA DE REGISTRO</th>
                                    <th class="desvinculaciones-seguimientos__col-wide">FECHA DESVINCULACIÓN</th>
                                    @foreach ($checkLabels as $field => $label)
                                        <th class="desvinculaciones-seguimientos__check-th" title="{{ $label }}">{{ $label }}</th>
                                    @endforeach
                                    <th class="desvinculaciones-seguimientos__col-wide">OK TODO</th>
                                    <th class="desvinculaciones-seguimientos__col-wide">FECHA ENTREGADO NOMINA</th>
                                    <th class="desvinculaciones-seguimientos__col-wide">Tiene carta generada</th>
                                    <th class="desvinculaciones-seguimientos__col-wide">Causal</th>
                                    <th>Recontratable</th>
                                    <th>Observaciones</th>
                                    @if ($canEditSeguimientos)
                                        <th class="desvinculaciones-seguimientos__actions-th">Acciones</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                @if ($canEditSeguimientos)
                    {{-- Modal import histórico Excel NOVEDADES --}}
                    <div
                        class="desvinculaciones-seguimientos__revert-overlay"
                        x-show="importModal.open"
                        x-cloak
                        x-on:keydown.escape.window="closeImportModal()"
                    >
                        <div
                            class="modal-card desvinculaciones-seguimientos__import-modal"
                            role="dialog"
                            aria-modal="true"
                            aria-labelledby="seguimientos-import-title"
                        >
                            <div class="ficha-empleados-terminate-modal__header" style="padding: 1rem 1rem 0;">
                                <h3 id="seguimientos-import-title" class="panel-title">Importar histórico</h3>
                                <p class="panel-text">
                                    Hoja <strong>NOVEDADES</strong> (desde mayo 2025). No genera cartas.
                                    Si el ingreso actual es posterior al retiro del Excel, el empleado sigue activo.
                                </p>
                            </div>

                            <div class="ficha-empleados-terminate-modal__body" style="padding: 1rem; display:grid; gap:0.75rem;">
                                <div class="form-field">
                                    <label class="form-label" for="seguimientos-import-file">Archivo Excel</label>
                                    <input
                                        id="seguimientos-import-file"
                                        type="file"
                                        class="form-input"
                                        accept=".xlsx,.xls,.xlsm"
                                        x-ref="importFileInput"
                                        x-on:change="onImportFileChange($event)"
                                        x-bind:disabled="importModal.busy"
                                    >
                                    <p class="form-hint" x-text="importModal.fileName || 'Seleccione .xlsx, .xls o .xlsm (máx. 50 MB)'"></p>
                                </div>

                                <label class="form-field" style="display:flex; gap:0.5rem; align-items:flex-start;">
                                    <input
                                        type="checkbox"
                                        x-model="importModal.confirm"
                                        x-bind:disabled="importModal.busy"
                                        style="margin-top:0.2rem;"
                                    >
                                    <span class="form-hint" style="margin:0;">
                                        Confirmo la <strong>carga real</strong> (escribe en Seguimientos y Retiros). Use primero «Simular».
                                    </span>
                                </label>

                                <p class="form-hint text-danger" x-show="importModal.error" x-text="importModal.error" x-cloak></p>

                                <div
                                    class="desvinculaciones-seguimientos__import-stats"
                                    x-show="importModal.stats"
                                    x-cloak
                                    style="border:1px solid var(--border-color, #d1d5db); border-radius:0.5rem; padding:0.75rem;"
                                >
                                    <p class="panel-text" style="margin:0 0 0.5rem;" x-text="importModal.resultMessage"></p>
                                    <ul class="panel-text" style="margin:0; padding-left:1.1rem;">
                                        <li>Escaneadas: <span x-text="importModal.stats?.scanned ?? 0"></span></li>
                                        <li>Creadas (desvincula): <span x-text="importModal.stats?.created ?? 0"></span></li>
                                        <li>Creadas (mantiene activo): <span x-text="importModal.stats?.created_keep_activo ?? 0"></span></li>
                                        <li>Actualizadas: <span x-text="importModal.stats?.updated ?? 0"></span></li>
                                        <li>Sin ficha: <span x-text="importModal.stats?.skipped_no_ficha ?? 0"></span></li>
                                        <li>Errores: <span x-text="importModal.stats?.errors_count ?? 0"></span></li>
                                    </ul>
                                    <template x-if="(importModal.stats?.errors || []).length">
                                        <ul class="form-hint text-danger" style="margin:0.5rem 0 0; padding-left:1.1rem; max-height:8rem; overflow:auto;">
                                            <template x-for="(err, idx) in importModal.stats.errors" :key="idx">
                                                <li x-text="err"></li>
                                            </template>
                                        </ul>
                                    </template>
                                </div>
                            </div>

                            <div class="ficha-empleados-terminate-modal__actions" style="display:flex; gap:0.5rem; justify-content:flex-end; flex-wrap:wrap; padding: 0 1rem 1rem;">
                                <button type="button" class="btn btn--secondary" x-on:click="closeImportModal()" x-bind:disabled="importModal.busy">
                                    Cerrar
                                </button>
                                <button
                                    type="button"
                                    class="btn btn--secondary"
                                    x-on:click="runImportHistorico(true)"
                                    x-bind:disabled="importModal.busy || ! importModal.hasFile"
                                >
                                    <span x-show="! (importModal.busy && importModal.mode === 'dry')">Simular</span>
                                    <span x-show="importModal.busy && importModal.mode === 'dry'" x-cloak>Simulando…</span>
                                </button>
                                <button
                                    type="button"
                                    class="btn btn--primary"
                                    x-on:click="runImportHistorico(false)"
                                    x-bind:disabled="importModal.busy || ! importModal.hasFile || ! importModal.confirm"
                                >
                                    <span x-show="! (importModal.busy && importModal.mode === 'import')">Cargar</span>
                                    <span x-show="importModal.busy && importModal.mode === 'import'" x-cloak>Cargando…</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        class="desvinculaciones-seguimientos__revert-overlay"
                        x-show="revertModal.open"
                        x-cloak
                        x-on:keydown.escape.window="closeRevertModal()"
                    >
                        <div
                            class="modal-card desvinculaciones-seguimientos__revert-modal"
                            role="dialog"
                            aria-modal="true"
                            aria-labelledby="seguimientos-revert-title"
                        >
                            <div class="ficha-empleados-terminate-modal__header" style="padding: 1rem 1rem 0;">
                                <h3 id="seguimientos-revert-title" class="panel-title">Revertir desvinculación</h3>
                                <p class="panel-text">
                                    <span x-text="revertModal.full_name"></span>
                                    ·
                                    <span x-text="revertModal.document_number"></span>
                                </p>
                            </div>

                            <div class="ficha-empleados-terminate-modal__body" style="padding: 1rem;">
                                <div class="form-field">
                                    <label class="form-label" for="seguimientos-revert-reason">Motivo <span class="text-danger">*</span></label>
                                    <textarea
                                        id="seguimientos-revert-reason"
                                        class="form-input"
                                        rows="3"
                                        x-model="revertModal.reason"
                                        x-bind:disabled="reverting"
                                        placeholder="Indique el motivo de la reversión…"
                                    ></textarea>
                                    <p class="form-hint text-danger" x-show="revertModal.error" x-text="revertModal.error" x-cloak></p>
                                </div>
                            </div>

                            <div class="ficha-empleados-terminate-modal__actions" style="display:flex; gap:0.5rem; justify-content:flex-end; padding: 0 1rem 1rem;">
                                <button type="button" class="btn btn--secondary" x-on:click="closeRevertModal()" x-bind:disabled="reverting">
                                    Cancelar
                                </button>
                                <button
                                    type="button"
                                    class="btn btn--primary"
                                    x-on:click="confirmRevert()"
                                    x-bind:disabled="reverting || ! String(revertModal.reason || '').trim()"
                                >
                                    <span x-show="! reverting">Confirmar reversión</span>
                                    <span x-show="reverting" x-cloak>Revirtiendo…</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // DataTables fuera del estado Alpine: el Proxy reactivo rompe la API y deja el loader colgado.
            let seguimientosDtApi = null;

            function getSeguimientosAlpine() {
                const root = document.querySelector('.desvinculaciones-seguimientos');
                if (!root || !window.Alpine) {
                    return null;
                }
                return window.Alpine.$data(root);
            }

            function escapeSeguimientosHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function revealSeguimientosTable() {
                const wrap = document.querySelector('.desvinculaciones-seguimientos__table-wrap');
                if (wrap) {
                    wrap.classList.remove('data-table-wrap--booting');
                    const loader = wrap.querySelector('.data-table-wrap__loader');
                    if (loader) {
                        loader.setAttribute('aria-busy', 'false');
                    }
                }
            }

            function reloadSeguimientosTable() {
                if (seguimientosDtApi) {
                    seguimientosDtApi.ajax.reload(null, false);
                }
            }

            document.addEventListener('alpine:init', () => {
                Alpine.data('desvinculacionesSeguimientos', (config) => ({
                    canEdit: !!config.canEdit,
                    datatableUrl: config.datatableUrl,
                    exportUrlBase: config.exportUrl || '',
                    importHistoricoUrl: config.importHistoricoUrl || '',
                    csrf: config.csrf,
                    checkFields: config.checkFields || [],
                    checkLabels: config.checkLabels || {},
                    q: config.initialQ || '',
                    status: config.initialStatus || 'incompletos',
                    rehireable: config.initialRehireable || '',
                    fechaCampo: config.initialFechaCampo || config.defaultFechaCampo || 'termination_date',
                    defaultFechaCampo: config.defaultFechaCampo || 'termination_date',
                    fechaDesde: config.initialFechaDesde || '',
                    fechaHasta: config.initialFechaHasta || '',
                    statusOptions: [
                        { value: 'incompletos', label: 'Incompletos' },
                        { value: 'ok_todo', label: 'OK TODO' },
                        { value: 'sin_carta', label: 'Sin carta' },
                    ],
                    total: 0,
                    saving: {},
                    saveTimers: {},
                    savingCount: 0,
                    saveError: '',
                    reverting: false,
                    revertModal: {
                        open: false,
                        id: null,
                        document_number: '',
                        full_name: '',
                        revert_url: '',
                        reason: '',
                        error: '',
                    },
                    importModal: {
                        open: false,
                        busy: false,
                        mode: '',
                        hasFile: false,
                        fileName: '',
                        confirm: false,
                        error: '',
                        resultMessage: '',
                        stats: null,
                    },

                    get totalFormatted() {
                        return Number(this.total || 0).toLocaleString('es-CO');
                    },

                    get hasActiveFilters() {
                        return (this.q || '').trim() !== ''
                            || (this.fechaDesde || '') !== ''
                            || (this.fechaHasta || '') !== ''
                            || this.status !== 'incompletos'
                            || (this.rehireable || '') !== '';
                    },

                    get exportHref() {
                        const params = new URLSearchParams();
                        if (this.q) {
                            params.set('q', this.q);
                        }
                        params.set('status', this.status == null ? 'incompletos' : String(this.status));
                        if (this.rehireable) {
                            params.set('rehireable', this.rehireable);
                        }
                        params.set('fecha_campo', this.fechaCampo || this.defaultFechaCampo);
                        if (this.fechaDesde) {
                            params.set('fecha_desde', this.fechaDesde);
                        }
                        if (this.fechaHasta) {
                            params.set('fecha_hasta', this.fechaHasta);
                        }
                        const qs = params.toString();
                        return this.exportUrlBase + (qs ? '?' + qs : '');
                    },

                    filterParams() {
                        return {
                            q: this.q || '',
                            status: this.status == null ? 'incompletos' : String(this.status),
                            rehireable: this.rehireable || '',
                            fecha_campo: this.fechaCampo || this.defaultFechaCampo,
                            fecha_desde: this.fechaDesde || '',
                            fecha_hasta: this.fechaHasta || '',
                        };
                    },

                    applyFilters() {
                        reloadSeguimientosTable();
                    },

                    clearFilters() {
                        this.q = '';
                        this.status = 'incompletos';
                        this.rehireable = '';
                        this.fechaDesde = '';
                        this.fechaHasta = '';
                        this.fechaCampo = this.defaultFechaCampo;
                        this.setFechaCampoSelect(this.fechaCampo);
                        this.setRehireableSelect('');
                        reloadSeguimientosTable();
                    },

                    setFechaCampoSelect(value) {
                        const sel = document.getElementById('seguimientos-fecha-campo');
                        if (! sel || ! window.Alpine) {
                            return;
                        }
                        const wrap = sel.closest('[x-data]');
                        if (wrap) {
                            const data = window.Alpine.$data(wrap);
                            data.value = value;
                            const opt = (data.options || []).find((item) => String(item.value) === String(value));
                            data.selectedLabel = opt ? opt.label : '';
                        }
                    },

                    setRehireableSelect(value) {
                        const sel = document.getElementById('seguimientos-rehireable');
                        if (! sel || ! window.Alpine) {
                            return;
                        }
                        const wrap = sel.closest('[x-data]');
                        if (wrap) {
                            const data = window.Alpine.$data(wrap);
                            data.value = value;
                            const opt = (data.options || []).find((item) => String(item.value) === String(value));
                            data.selectedLabel = opt ? opt.label : '';
                        }
                    },

                    onFechaCampoChange(event) {
                        let value = event?.detail?.value;
                        if (value === undefined || value === null) {
                            value = event?.target?.value ?? '';
                        }
                        value = String(value || '').trim() || this.defaultFechaCampo;
                        if (this.fechaCampo === value) {
                            return;
                        }
                        this.fechaCampo = value;
                        if ((this.fechaDesde || '') !== '' || (this.fechaHasta || '') !== '') {
                            this.applyFilters();
                        }
                    },

                    onRehireableChange(event) {
                        let value = event?.detail?.value;
                        if (value === undefined || value === null) {
                            value = event?.target?.value ?? '';
                        }
                        value = String(value || '').trim();
                        if (! ['', '0', '1'].includes(value)) {
                            value = '';
                        }
                        if (this.rehireable === value) {
                            return;
                        }
                        this.rehireable = value;
                        this.applyFilters();
                    },

                    onDateRangeChange() {
                        const hasDateRange = (this.fechaDesde || '').trim() !== ''
                            || (this.fechaHasta || '').trim() !== '';

                        if (hasDateRange) {
                            this.status = '';
                        } else if (this.status === '') {
                            this.status = 'incompletos';
                        }

                        this.applyFilters();
                    },

                    setStatus(value) {
                        if (this.status === value) {
                            return;
                        }
                        this.status = value;
                        reloadSeguimientosTable();
                    },

                    saveKey(id, field) {
                        return id + ':' + field;
                    },

                    openImportModal() {
                        if (! this.canEdit || ! this.importHistoricoUrl) {
                            return;
                        }

                        this.importModal = {
                            open: true,
                            busy: false,
                            mode: '',
                            hasFile: false,
                            fileName: '',
                            confirm: false,
                            error: '',
                            resultMessage: '',
                            stats: null,
                        };

                        this.$nextTick(() => {
                            if (this.$refs.importFileInput) {
                                this.$refs.importFileInput.value = '';
                            }
                        });
                    },

                    closeImportModal() {
                        if (this.importModal.busy) {
                            return;
                        }

                        this.importModal.open = false;
                        this.importModal.error = '';
                    },

                    onImportFileChange(event) {
                        const file = event?.target?.files?.[0] || null;
                        this.importModal.hasFile = !! file;
                        this.importModal.fileName = file ? file.name : '';
                        this.importModal.error = '';
                        this.importModal.stats = null;
                        this.importModal.resultMessage = '';
                    },

                    async runImportHistorico(dryRun) {
                        if (! this.canEdit || ! this.importHistoricoUrl || this.importModal.busy) {
                            return;
                        }

                        const input = this.$refs.importFileInput;
                        const file = input?.files?.[0] || null;
                        if (! file) {
                            this.importModal.error = 'Seleccione un archivo Excel.';
                            return;
                        }

                        if (! dryRun && ! this.importModal.confirm) {
                            this.importModal.error = 'Marque la confirmación para la carga real.';
                            return;
                        }

                        this.importModal.busy = true;
                        this.importModal.mode = dryRun ? 'dry' : 'import';
                        this.importModal.error = '';
                        this.importModal.stats = null;
                        this.importModal.resultMessage = '';

                        const body = new FormData();
                        body.append('import_file', file);
                        body.append('dry_run', dryRun ? '1' : '0');
                        if (! dryRun) {
                            body.append('confirm_import', '1');
                        }

                        try {
                            const res = await fetch(this.importHistoricoUrl, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': this.csrf,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body,
                                credentials: 'same-origin',
                            });

                            const data = await res.json().catch(() => ({}));
                            if (! res.ok || ! data.ok) {
                                const firstError = data.errors
                                    ? Object.values(data.errors).flat()[0]
                                    : null;
                                throw new Error(firstError || data.message || 'No se pudo importar el archivo.');
                            }

                            this.importModal.stats = data.stats || null;
                            this.importModal.resultMessage = data.message || (dryRun ? 'Simulación lista.' : 'Carga completada.');

                            if (! dryRun) {
                                reloadSeguimientosTable();
                            }
                        } catch (e) {
                            this.importModal.error = e.message || 'Error al importar.';
                        } finally {
                            this.importModal.busy = false;
                            this.importModal.mode = '';
                        }
                    },

                    openRevertModal(row) {
                        if (! this.canEdit || this.reverting) {
                            return;
                        }

                        this.revertModal = {
                            open: true,
                            id: row.id,
                            document_number: row.document_number || '',
                            full_name: row.full_name || '',
                            revert_url: row.revert_url || '',
                            reason: '',
                            error: '',
                        };
                    },

                    closeRevertModal() {
                        if (this.reverting) {
                            return;
                        }

                        this.revertModal.open = false;
                        this.revertModal.error = '';
                        this.revertModal.reason = '';
                    },

                    async confirmRevert() {
                        const reason = String(this.revertModal.reason || '').trim();
                        if (! reason || ! this.revertModal.revert_url) {
                            this.revertModal.error = 'Indique el motivo (mínimo 5 caracteres).';
                            return;
                        }

                        this.reverting = true;
                        this.revertModal.error = '';

                        try {
                            const res = await fetch(this.revertModal.revert_url, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': this.csrf,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                                body: JSON.stringify({ reason }),
                            });

                            const body = await res.json().catch(() => ({}));

                            if (res.status === 403) {
                                throw new Error('Sin permiso para revertir desvinculaciones.');
                            }

                            if (res.status === 422) {
                                const msg = body.message
                                    || (body.errors ? Object.values(body.errors).flat().join(' ') : null)
                                    || 'Revise el motivo.';
                                throw new Error(msg);
                            }

                            if (! res.ok) {
                                throw new Error(body.message || 'No se pudo revertir la desvinculación.');
                            }

                            this.reverting = false;
                            this.closeRevertModal();
                            reloadSeguimientosTable();
                        } catch (e) {
                            this.revertModal.error = e.message || 'Error al revertir.';
                        } finally {
                            this.reverting = false;
                        }
                    },

                    onCheckChange(input) {
                        const id = Number(input.getAttribute('data-id') || 0);
                        const field = input.getAttribute('data-field') || '';
                        const updateUrl = input.getAttribute('data-update-url') || '';
                        if (! id || ! field || ! updateUrl) {
                            return;
                        }

                        this.queuePatch(
                            { id, update_url: updateUrl, $row: input.closest('tr') },
                            { [field]: !! input.checked },
                            field,
                        );
                    },

                    onPayrollChange(input) {
                        const id = Number(input.getAttribute('data-id') || 0);
                        const updateUrl = input.getAttribute('data-update-url') || '';
                        if (! id || ! updateUrl) {
                            return;
                        }

                        this.queuePatch(
                            { id, update_url: updateUrl, $row: input.closest('tr') },
                            { payroll_delivered_at: input.value || null },
                            'payroll_delivered_at',
                        );
                    },

                    queuePatch(row, payload, field) {
                        const key = this.saveKey(row.id, field);

                        if (this.saveTimers[key]) {
                            clearTimeout(this.saveTimers[key]);
                        }

                        this.saveTimers[key] = setTimeout(() => {
                            this.patchRow(row, payload, field);
                        }, 400);
                    },

                    async patchRow(row, payload, field) {
                        const key = this.saveKey(row.id, field);
                        this.saving[key] = true;
                        this.savingCount = Object.keys(this.saving).filter((k) => this.saving[k]).length;
                        this.saveError = '';

                        const $row = row.$row;
                        if ($row) {
                            $row.querySelectorAll('input').forEach((el) => {
                                if (el.getAttribute('data-field') === field || (field === 'payroll_delivered_at' && el.classList.contains('js-seguimiento-payroll'))) {
                                    el.disabled = true;
                                }
                            });
                        }

                        try {
                            const res = await fetch(row.update_url, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': this.csrf,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                                body: JSON.stringify(payload),
                            });

                            if (res.status === 403) {
                                throw new Error('Sin permiso para editar seguimientos.');
                            }

                            if (! res.ok) {
                                const body = await res.json().catch(() => ({}));
                                const msg = body.message
                                    || (body.errors ? Object.values(body.errors).flat().join(' ') : null)
                                    || 'No se pudo guardar.';
                                throw new Error(msg);
                            }

                            const json = await res.json();
                            if (json.followup && $row) {
                                const okTodo = $row.querySelector('.js-seguimiento-ok-todo');
                                if (okTodo) {
                                    okTodo.textContent = json.followup.ok_todo ? 'Sí' : 'No';
                                }
                            }
                        } catch (e) {
                            this.saveError = e.message || 'Error al guardar.';
                            reloadSeguimientosTable();
                        } finally {
                            delete this.saving[key];
                            this.savingCount = Object.keys(this.saving).length;
                            if ($row && this.canEdit) {
                                $row.querySelectorAll('input').forEach((el) => {
                                    el.disabled = false;
                                });
                            }
                        }
                    },
                }));
            });

            document.addEventListener('DOMContentLoaded', function () {
                const $ = window.jQuery;
                if (! $ || ! $.fn.DataTable) {
                    revealSeguimientosTable();
                    return;
                }

                const $table = $('.js-desvinculaciones-seguimientos-datatable');
                if (! $table.length) {
                    return;
                }

                const alpine = getSeguimientosAlpine();
                const canEdit = !!(alpine && alpine.canEdit);
                const checkFields = (alpine && alpine.checkFields) ? alpine.checkFields : @json(array_values($checkFields));
                const checkLabels = (alpine && alpine.checkLabels) ? alpine.checkLabels : @json($checkLabels);

                if ($.fn.DataTable.isDataTable($table[0])) {
                    $table.DataTable().destroy();
                }

                // Índices alineados con TerminationFollowupDatatableService::applyOrdering.
                const columns = [
                    { data: 'id', name: 'id' },
                    { data: 'document_number', name: 'document_number' },
                    {
                        data: 'full_name',
                        name: 'full_name',
                        render: (data) => escapeSeguimientosHtml(data),
                    },
                    {
                        data: 'position_name',
                        name: 'position_name',
                        defaultContent: '—',
                        render: (data) => escapeSeguimientosHtml(data || '—'),
                    },
                    {
                        data: 'termination_cause_name',
                        name: 'termination_cause_name',
                        defaultContent: '—',
                        render: (data) => escapeSeguimientosHtml(data || '—'),
                    },
                    { data: 'registered_at_display', name: 'registered_at' },
                    { data: 'termination_date_display', name: 'termination_date' },
                ];

                checkFields.forEach((field) => {
                    columns.push({
                        data: null,
                        name: field,
                        searchable: false,
                        className: 'desvinculaciones-seguimientos__check-td',
                        render: (_data, _type, row) => {
                            const checked = row.checks && row.checks[field] ? ' checked' : '';
                            const disabled = canEdit ? '' : ' disabled';
                            const label = escapeSeguimientosHtml(checkLabels[field] || field);
                            return (
                                '<input type="checkbox"'
                                + ' class="desvinculaciones-seguimientos__check js-seguimiento-check"'
                                + ' data-id="' + row.id + '"'
                                + ' data-field="' + escapeSeguimientosHtml(field) + '"'
                                + ' data-update-url="' + escapeSeguimientosHtml(row.update_url || '') + '"'
                                + ' aria-label="' + label + '"'
                                + checked
                                + disabled
                                + '>'
                            );
                        },
                    });
                });

                columns.push(
                    {
                        data: 'ok_todo',
                        name: 'ok_todo',
                        searchable: false,
                        render: (data) => (
                            '<span class="desvinculaciones-seguimientos__ok-todo js-seguimiento-ok-todo">'
                            + (data ? 'Sí' : 'No')
                            + '</span>'
                        ),
                    },
                    {
                        data: 'payroll_delivered_at',
                        name: 'payroll_delivered_at',
                        searchable: false,
                        render: (data, _type, row) => {
                            const disabled = canEdit ? '' : ' disabled';
                            const value = data ? escapeSeguimientosHtml(data) : '';
                            return (
                                '<input type="date"'
                                + ' class="form-input desvinculaciones-seguimientos__date js-seguimiento-payroll"'
                                + ' data-id="' + row.id + '"'
                                + ' data-update-url="' + escapeSeguimientosHtml(row.update_url || '') + '"'
                                + ' value="' + value + '"'
                                + disabled
                                + '>'
                            );
                        },
                    },
                    { data: 'letter_generated_label', name: 'letter_generated', searchable: false },
                    {
                        data: 'termination_cause_name',
                        name: 'termination_cause_name_causal',
                        defaultContent: '—',
                        render: (data) => escapeSeguimientosHtml(data || '—'),
                    },
                    { data: 'is_rehireable_label', name: 'is_rehireable', searchable: false },
                    {
                        data: 'termination_notes',
                        name: 'termination_notes',
                        className: 'desvinculaciones-seguimientos__notes',
                        render: (data) => escapeSeguimientosHtml(data || '—'),
                    },
                );

                if (canEdit) {
                    columns.push({
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'desvinculaciones-seguimientos__actions-td',
                        render: (_data, _type, row) => (
                            '<button type="button"'
                            + ' class="btn btn--secondary btn--sm desvinculaciones-seguimientos__revert-btn js-seguimiento-revert"'
                            + ' title="Revertir desvinculación"'
                            + ' aria-label="Revertir desvinculación"'
                            + ' data-id="' + row.id + '"'
                            + ' data-document-number="' + escapeSeguimientosHtml(row.document_number || '') + '"'
                            + ' data-full-name="' + escapeSeguimientosHtml(row.full_name || '') + '"'
                            + ' data-revert-url="' + escapeSeguimientosHtml(row.revert_url || '') + '"'
                            + '>'
                            + '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">'
                            + '<path d="M12 5V1L7 6l5 5V7c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6H4c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z"/>'
                            + '</svg>'
                            + '</button>'
                        ),
                    });
                }

                const nonOrderable = [];
                columns.forEach((col, index) => {
                    if (col.orderable === false) {
                        nonOrderable.push(index);
                    }
                });

                seguimientosDtApi = $table.DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: $table.data('dt-url'),
                        data: function (d) {
                            const live = getSeguimientosAlpine();
                            if (live && typeof live.filterParams === 'function') {
                                Object.assign(d, live.filterParams());
                            }
                        },
                        error: function () {
                            revealSeguimientosTable();
                            const live = getSeguimientosAlpine();
                            if (live) {
                                live.saveError = 'No se pudo cargar el listado de seguimientos.';
                            }
                        },
                    },
                    columns,
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                        emptyTable: 'No hay seguimientos con los filtros seleccionados.',
                    },
                    dom: '<"req-manage-dt-top"lf><"req-manage-table-scroll"t><"req-manage-dt-bottom"ip>',
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    pageLength: 25,
                    responsive: false,
                    order: [[0, 'desc']],
                    orderMulti: false,
                    columnDefs: nonOrderable.length
                        ? [{ targets: nonOrderable, orderable: false }]
                        : [],
                });

                // Tabla hasta el borde inferior: altura del área de scroll al viewport.
                (function setupSeguimientosTableScroll() {
                    const $shell = $table.closest('.panel__body, .desvinculaciones-seguimientos');
                    const $wrapper = $table.closest('.dataTables_wrapper');
                    const $scroll = $wrapper.find('.req-manage-table-scroll').first();
                    const $bottom = $wrapper.find('.req-manage-dt-bottom').first();

                    if (!$scroll.length) {
                        return;
                    }

                    const updateScrollArea = function () {
                        const bottomHeight = $bottom.outerHeight(true) || 0;
                        const rect = $scroll[0].getBoundingClientRect();
                        const available = Math.max(220, Math.floor(window.innerHeight - rect.top - bottomHeight - 8));

                        $scroll.css({
                            height: available + 'px',
                            maxHeight: available + 'px',
                        });
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
                    setTimeout(updateScrollArea, 0);
                    setTimeout(updateScrollArea, 200);
                    $(window).on('resize orientationchange', debounce(updateScrollArea, 100));
                    seguimientosDtApi.on('draw.dt', debounce(updateScrollArea, 50));

                    if (typeof ResizeObserver !== 'undefined') {
                        const observer = new ResizeObserver(debounce(updateScrollArea, 50));
                        const filtersEl = document.querySelector('.desvinculaciones-seguimientos__filters');
                        const toolbarEl = document.querySelector('.desvinculaciones-seguimientos__table-toolbar');
                        if (filtersEl) {
                            observer.observe(filtersEl);
                        }
                        if (toolbarEl) {
                            observer.observe(toolbarEl);
                        }
                        if ($shell.length) {
                            observer.observe($shell[0]);
                        }
                    }
                })();

                seguimientosDtApi.on('xhr.dt', function (_event, _settings, json) {
                    revealSeguimientosTable();
                    const live = getSeguimientosAlpine();
                    if (live) {
                        live.total = Number(json?.recordsFiltered || 0);
                    }
                    const el = document.getElementById('desvinculaciones-seguimientos-count');
                    if (el) {
                        el.textContent = Number(json?.recordsFiltered || 0).toLocaleString('es-CO');
                    }
                });

                seguimientosDtApi.on('error.dt', function () {
                    revealSeguimientosTable();
                });

                window.setTimeout(revealSeguimientosTable, 8000);

                $table.on('change', '.js-seguimiento-check', function () {
                    const live = getSeguimientosAlpine();
                    if (! live || ! live.canEdit) {
                        return;
                    }
                    live.onCheckChange(this);
                });

                $table.on('change', '.js-seguimiento-payroll', function () {
                    const live = getSeguimientosAlpine();
                    if (! live || ! live.canEdit) {
                        return;
                    }
                    live.onPayrollChange(this);
                });

                $table.on('click', '.js-seguimiento-revert', function () {
                    const live = getSeguimientosAlpine();
                    if (! live) {
                        return;
                    }
                    live.openRevertModal({
                        id: Number(this.getAttribute('data-id') || 0),
                        document_number: this.getAttribute('data-document-number') || '',
                        full_name: this.getAttribute('data-full-name') || '',
                        revert_url: this.getAttribute('data-revert-url') || '',
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
