<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.desvinculaciones.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Seguimientos: DataTables server-side + filtros Alpine; checks/fecha/revert con eventos delegados --}}
    <div class="page-section desvinculaciones-seguimientos-page">
        <div class="app-container">
            <div
                class="panel desvinculaciones-seguimientos"
                x-data="desvinculacionesSeguimientos(@js([
                    'canEdit' => (bool) $canEditSeguimientos,
                    'datatableUrl' => $datatableUrl,
                    'exportUrl' => $exportUrl,
                    'csrf' => csrf_token(),
                    'checkFields' => $checkFields,
                    'checkLabels' => $checkLabels,
                    'initialQ' => $filters['q'] ?? '',
                    'initialStatus' => $filters['status'] ?? 'incompletos',
                    'initialFechaCampo' => $filters['fecha_campo'] ?? \App\Models\EmployeeTerminationFollowup::DEFAULT_DATE_FILTER_FIELD,
                    'initialFechaDesde' => $filters['fecha_desde'] ?? '',
                    'initialFechaHasta' => $filters['fecha_hasta'] ?? '',
                    'defaultFechaCampo' => \App\Models\EmployeeTerminationFollowup::DEFAULT_DATE_FILTER_FIELD,
                ]))"
            >
                <div class="panel__body panel__body--compact">
                    @php
                        $hasActiveFilters = ($filters['q'] ?? '') !== ''
                            || ($filters['fecha_desde'] ?? '') !== ''
                            || ($filters['fecha_hasta'] ?? '') !== ''
                            || (($filters['status'] ?? 'incompletos') !== 'incompletos');
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
                            <a
                                x-bind:href="exportHref"
                                class="btn btn--secondary btn--sm desvinculaciones-seguimientos__export-btn"
                                title="Exportar a Excel"
                            >
                                <x-selfhst-microsoft-excel-2013 width="16" height="16" aria-hidden="true" />
                            </a>
                        </div>
                    </div>

                    <div class="data-table-wrap desvinculaciones-seguimientos__table-wrap data-table-wrap--booting">
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
                                    <th class="desvinculaciones-seguimientos__col-doc">CEDULA</th>
                                    <th class="desvinculaciones-seguimientos__col-name">NOMBRE Y APELLIDOS</th>
                                    <th class="desvinculaciones-seguimientos__col-wide">CARGO</th>
                                    <th class="desvinculaciones-seguimientos__col-wide">TIPO DESVINCULACION</th>
                                    <th class="desvinculaciones-seguimientos__col-wide">FECHA DE REGISTRO</th>
                                    <th class="desvinculaciones-seguimientos__col-wide">FECHA DESVINCULACION</th>
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
                    csrf: config.csrf,
                    checkFields: config.checkFields || [],
                    checkLabels: config.checkLabels || {},
                    q: config.initialQ || '',
                    status: config.initialStatus || 'incompletos',
                    fechaCampo: config.initialFechaCampo || config.defaultFechaCampo || 'payroll_delivered_at',
                    defaultFechaCampo: config.defaultFechaCampo || 'payroll_delivered_at',
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

                    get totalFormatted() {
                        return Number(this.total || 0).toLocaleString('es-CO');
                    },

                    get hasActiveFilters() {
                        return (this.q || '').trim() !== ''
                            || (this.fechaDesde || '') !== ''
                            || (this.fechaHasta || '') !== ''
                            || this.status !== 'incompletos';
                    },

                    get exportHref() {
                        const params = new URLSearchParams();
                        if (this.q) {
                            params.set('q', this.q);
                        }
                        params.set('status', this.status == null ? 'incompletos' : String(this.status));
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
                        this.fechaDesde = '';
                        this.fechaHasta = '';
                        this.fechaCampo = this.defaultFechaCampo;
                        this.setFechaCampoSelect(this.fechaCampo);
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
                                    okTodo.textContent = json.followup.ok_todo ? 'Si' : 'No';
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

                const columns = [
                    { data: 'id' },
                    { data: 'document_number' },
                    {
                        data: 'full_name',
                        render: (data) => escapeSeguimientosHtml(data),
                    },
                    {
                        data: 'position_name',
                        defaultContent: '—',
                        render: (data) => escapeSeguimientosHtml(data || '—'),
                    },
                    {
                        data: 'termination_cause_name',
                        defaultContent: '—',
                        render: (data) => escapeSeguimientosHtml(data || '—'),
                    },
                    { data: 'registered_at_display', orderable: false },
                    { data: 'termination_date_display', orderable: false },
                ];

                checkFields.forEach((field) => {
                    columns.push({
                        data: null,
                        orderable: false,
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
                        orderable: false,
                        searchable: false,
                        render: (data) => (
                            '<span class="desvinculaciones-seguimientos__ok-todo js-seguimiento-ok-todo">'
                            + (data ? 'Si' : 'No')
                            + '</span>'
                        ),
                    },
                    {
                        data: 'payroll_delivered_at',
                        orderable: false,
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
                    { data: 'letter_generated_label', orderable: false, searchable: false },
                    {
                        data: 'termination_cause_name',
                        orderable: false,
                        defaultContent: '—',
                        render: (data) => escapeSeguimientosHtml(data || '—'),
                    },
                    { data: 'is_rehireable_label', orderable: false, searchable: false },
                    {
                        data: 'termination_notes',
                        orderable: false,
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
                    dom: '<"req-manage-dt-top"l><"req-manage-table-scroll"t><"req-manage-dt-bottom"ip>',
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    pageLength: 25,
                    responsive: false,
                    order: [[0, 'desc']],
                    columnDefs: [{ targets: nonOrderable, orderable: false, searchable: false }],
                });

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
