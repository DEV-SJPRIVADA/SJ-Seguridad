<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.desvinculaciones.partials.subnav', ['subTabs' => $subTabs])
        <div class="app-container">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">Seguimientos</h2>
                <p class="panel-text">Gestion humana — checklist post-retiro</p>
            </div>
        </div>
    </x-slot>

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
                    'initialStatus' => $filters['status'] ?? 'todos',
                    'initialFechaDesde' => $filters['fecha_desde'] ?? '',
                    'initialFechaHasta' => $filters['fecha_hasta'] ?? '',
                ]))"
                x-init="init()"
            >
                <div class="panel__body panel__body--compact">
                    @unless ($canEditSeguimientos)
                        <p class="panel-text desvinculaciones-seguimientos__readonly-notice">
                            Vista de solo lectura. Para editar checks o fecha de nomina necesita el permiso de edicion de Seguimientos.
                        </p>
                    @endunless

                    <div class="req-manage-filters desvinculaciones-seguimientos__filters">
                        <div class="req-manage-filters__toolbar desvinculaciones-seguimientos__toolbar">
                            <div class="desvinculaciones-seguimientos__filters-main">
                                <div class="req-manage-filters__search-col desvinculaciones-seguimientos__search">
                                    <label class="req-manage-filters__label" for="seguimientos-search-q">Buscar</label>
                                    <div class="req-manage-filters__search-group">
                                        <input
                                            id="seguimientos-search-q"
                                            type="search"
                                            class="form-input"
                                            placeholder="Cedula o nombre"
                                            x-model="q"
                                            x-on:keydown.enter.prevent="applyFilters()"
                                            autocomplete="off"
                                        >
                                        <button type="button" class="btn btn--primary btn--sm" x-on:click="applyFilters()">
                                            Buscar
                                        </button>
                                    </div>
                                </div>

                                <div class="desvinculaciones-seguimientos__date-range" role="group" aria-label="Rango fecha entregado nomina">
                                    <span class="req-manage-filters__label desvinculaciones-seguimientos__date-range-title">
                                        FECHA ENTREGADO NOMINA
                                    </span>
                                    <div class="desvinculaciones-seguimientos__date-fields">
                                        <div class="desvinculaciones-seguimientos__date-field">
                                            <label class="req-manage-filters__label" for="seguimientos-fecha-desde">Desde</label>
                                            <input
                                                id="seguimientos-fecha-desde"
                                                type="date"
                                                class="form-input desvinculaciones-seguimientos__date-input"
                                                x-model="fechaDesde"
                                                x-on:change="applyFilters()"
                                            >
                                        </div>
                                        <div class="desvinculaciones-seguimientos__date-field">
                                            <label class="req-manage-filters__label" for="seguimientos-fecha-hasta">Hasta</label>
                                            <input
                                                id="seguimientos-fecha-hasta"
                                                type="date"
                                                class="form-input desvinculaciones-seguimientos__date-input"
                                                x-model="fechaHasta"
                                                x-on:change="applyFilters()"
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="desvinculaciones-seguimientos__export">
                                <a
                                    x-bind:href="exportHref"
                                    class="btn btn--secondary btn--sm desvinculaciones-seguimientos__export-btn"
                                    title="Exportar a Excel"
                                >
                                    <x-selfhst-microsoft-excel-2013 width="16" height="16" aria-hidden="true" />                                    
                                </a>
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

                        <p class="req-manage-filters__meta">
                            <strong x-text="totalFormatted"></strong>
                            <span x-text="total === 1 ? 'seguimiento' : 'seguimientos'"></span>
                            <span x-show="savingCount > 0" x-cloak> · Guardando…</span>
                            <span x-show="saveError" class="text-danger" x-cloak x-text="saveError"></span>
                        </p>
                    </div>

                    <div class="data-table-wrap desvinculaciones-seguimientos__table-wrap" x-bind:class="{ 'data-table-wrap--booting': loading }">
                        <template x-if="loading && rows.length === 0">
                            <p class="panel-text">Cargando seguimientos…</p>
                        </template>

                        <table class="data-table desvinculaciones-seguimientos__table">
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
                            <tbody>
                                <template x-if="! loading && rows.length === 0">
                                    <tr>
                                        <td colspan="{{ 11 + count($checkFields) + ($canEditSeguimientos ? 1 : 0) }}" class="desvinculaciones-seguimientos__empty">
                                            No hay seguimientos con los filtros seleccionados.
                                        </td>
                                    </tr>
                                </template>
                                <template x-for="row in rows" :key="row.id">
                                    <tr>
                                        <td x-text="row.id"></td>
                                        <td x-text="row.document_number"></td>
                                        <td x-text="row.full_name"></td>
                                        <td x-text="row.position_name || '—'"></td>
                                        <td x-text="row.termination_cause_name || '—'"></td>
                                        <td x-text="row.registered_at_display"></td>
                                        <td x-text="row.termination_date_display"></td>
                                        <template x-for="field in checkFields" :key="row.id + '-' + field">
                                            <td class="desvinculaciones-seguimientos__check-td">
                                                <input
                                                    type="checkbox"
                                                    class="desvinculaciones-seguimientos__check"
                                                    x-bind:checked="row.checks[field]"
                                                    x-bind:disabled="! canEdit || isSaving(row.id, field)"
                                                    x-on:change="onCheckChange(row, field, $event.target.checked)"
                                                    x-bind:aria-label="checkLabels[field]"
                                                >
                                            </td>
                                        </template>
                                        <td>
                                            <span
                                                class="desvinculaciones-seguimientos__ok-todo"
                                                x-text="row.ok_todo ? 'Si' : 'No'"
                                            ></span>
                                        </td>
                                        <td>
                                            <input
                                                type="date"
                                                class="form-input desvinculaciones-seguimientos__date"
                                                x-model="row.payroll_delivered_at"
                                                x-bind:disabled="! canEdit || isSaving(row.id, 'payroll_delivered_at')"
                                                x-on:change="onPayrollChange(row)"
                                            >
                                        </td>
                                        <td x-text="row.letter_generated_label"></td>
                                        <td x-text="row.termination_cause_name || '—'"></td>
                                        <td x-text="row.is_rehireable_label"></td>
                                        <td class="desvinculaciones-seguimientos__notes" x-text="row.termination_notes || '—'"></td>
                                        @if ($canEditSeguimientos)
                                            <td class="desvinculaciones-seguimientos__actions-td">
                                                <button
                                                    type="button"
                                                    class="btn btn--secondary btn--sm desvinculaciones-seguimientos__revert-btn"
                                                    title="Revertir desvinculacion"
                                                    aria-label="Revertir desvinculacion"
                                                    x-bind:disabled="reverting"
                                                    x-on:click="openRevertModal(row)"
                                                >
                                                    <x-ri-issues-reopen-fill width="18" height="18" aria-hidden="true" />
                                                </button>
                                            </td>
                                        @endif
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="desvinculaciones-seguimientos__pager" x-show="pages > 1" x-cloak>
                        <button
                            type="button"
                            class="btn btn--secondary btn--sm"
                            x-bind:disabled="page <= 1 || loading"
                            x-on:click="goPage(page - 1)"
                        >
                            Anterior
                        </button>
                        <span class="panel-text" x-text="'Pagina ' + page + ' de ' + pages"></span>
                        <button
                            type="button"
                            class="btn btn--secondary btn--sm"
                            x-bind:disabled="page >= pages || loading"
                            x-on:click="goPage(page + 1)"
                        >
                            Siguiente
                        </button>
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
                            aria-labelledby="revert-desvinculacion-title"
                            @click.stop
                        >
                            <div class="ficha-empleados-masivos-modal__header">
                                <div>
                                    <h3 id="revert-desvinculacion-title" class="ficha-empleados-masivos-modal__title">Revertir desvinculacion</h3>
                                    <p class="ficha-empleados-masivos-modal__lead">
                                        <span x-text="revertModal.full_name"></span>
                                        — <span x-text="revertModal.document_number"></span>
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="ficha-empleados-masivos-modal__close"
                                    aria-label="Cerrar"
                                    x-on:click="closeRevertModal()"
                                >
                                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                                </button>
                            </div>

                            <div class="panel__body form-stack">
                                <p class="panel-text">
                                    Se reactivara al empleado, se eliminara el seguimiento y se borraran las cartas generadas de este retiro.
                                </p>
                                <div class="form-field">
                                    <label class="form-label" for="revert-reason">Motivo <span class="text-danger">*</span></label>
                                    <textarea
                                        id="revert-reason"
                                        class="form-input supply-textarea"
                                        rows="3"
                                        maxlength="1000"
                                        x-model="revertModal.reason"
                                        x-bind:disabled="reverting"
                                        placeholder="Indique el motivo de la reversion…"
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
                                    <span x-show="! reverting">Confirmar reversion</span>
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
            document.addEventListener('alpine:init', () => {
                Alpine.data('desvinculacionesSeguimientos', (config) => ({
                    canEdit: !!config.canEdit,
                    datatableUrl: config.datatableUrl,
                    exportUrlBase: config.exportUrl || '',
                    csrf: config.csrf,
                    checkFields: config.checkFields || [],
                    checkLabels: config.checkLabels || {},
                    q: config.initialQ || '',
                    status: config.initialStatus || 'todos',
                    fechaDesde: config.initialFechaDesde || '',
                    fechaHasta: config.initialFechaHasta || '',
                    statusOptions: [
                        { value: 'todos', label: 'Todos' },
                        { value: 'incompletos', label: 'Incompletos' },
                        { value: 'ok_todo', label: 'OK TODO' },
                        { value: 'sin_carta', label: 'Sin carta' },
                    ],
                    rows: [],
                    total: 0,
                    page: 1,
                    perPage: 25,
                    loading: false,
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

                    get pages() {
                        return Math.max(1, Math.ceil(this.total / this.perPage));
                    },

                    get totalFormatted() {
                        return Number(this.total || 0).toLocaleString('es-CO');
                    },

                    get exportHref() {
                        const params = new URLSearchParams();
                        if (this.q) {
                            params.set('q', this.q);
                        }
                        if (this.status && this.status !== 'todos') {
                            params.set('status', this.status);
                        }
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
                            status: this.status || 'todos',
                            fecha_desde: this.fechaDesde || '',
                            fecha_hasta: this.fechaHasta || '',
                        };
                    },

                    init() {
                        this.load();
                    },

                    applyFilters() {
                        this.page = 1;
                        this.load();
                    },

                    setStatus(value) {
                        if (this.status === value) {
                            return;
                        }
                        this.status = value;
                        this.page = 1;
                        this.load();
                    },

                    goPage(next) {
                        this.page = Math.min(this.pages, Math.max(1, next));
                        this.load();
                    },

                    saveKey(id, field) {
                        return id + ':' + field;
                    },

                    isSaving(id, field) {
                        return !!this.saving[this.saveKey(id, field)];
                    },

                    openRevertModal(row) {
                        if (!this.canEdit || this.reverting) {
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
                        if (!reason || !this.revertModal.revert_url) {
                            this.revertModal.error = 'Indique el motivo (minimo 5 caracteres).';
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

                            if (!res.ok) {
                                throw new Error(body.message || 'No se pudo revertir la desvinculacion.');
                            }

                            this.reverting = false;
                            this.closeRevertModal();
                            await this.load();
                        } catch (e) {
                            this.revertModal.error = e.message || 'Error al revertir.';
                        } finally {
                            this.reverting = false;
                        }
                    },

                    async load() {
                        this.loading = true;
                        this.saveError = '';

                        const params = new URLSearchParams({
                            ...this.filterParams(),
                            draw: '1',
                            start: String((this.page - 1) * this.perPage),
                            length: String(this.perPage),
                        });

                        try {
                            const res = await fetch(this.datatableUrl + '?' + params.toString(), {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                            });

                            if (!res.ok) {
                                throw new Error('No se pudo cargar el listado.');
                            }

                            const json = await res.json();
                            this.rows = Array.isArray(json.data) ? json.data : [];
                            this.total = Number(json.recordsFiltered || 0);
                        } catch (e) {
                            this.rows = [];
                            this.total = 0;
                            this.saveError = e.message || 'Error al cargar.';
                        } finally {
                            this.loading = false;
                        }
                    },

                    onCheckChange(row, field, checked) {
                        if (!this.canEdit) {
                            return;
                        }

                        row.checks[field] = !!checked;
                        row.ok_todo = this.computeOkTodo(row);
                        this.queuePatch(row, { [field]: !!checked }, field);
                    },

                    onPayrollChange(row) {
                        if (!this.canEdit) {
                            return;
                        }

                        const value = row.payroll_delivered_at || null;
                        this.queuePatch(row, { payroll_delivered_at: value }, 'payroll_delivered_at');
                    },

                    computeOkTodo(row) {
                        return this.checkFields.every((field) => !!row.checks[field]);
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

                            if (!res.ok) {
                                const body = await res.json().catch(() => ({}));
                                const msg = body.message
                                    || (body.errors ? Object.values(body.errors).flat().join(' ') : null)
                                    || 'No se pudo guardar.';
                                throw new Error(msg);
                            }

                            const json = await res.json();
                            if (json.followup) {
                                Object.assign(row, json.followup);
                            }
                        } catch (e) {
                            this.saveError = e.message || 'Error al guardar.';
                            await this.load();
                        } finally {
                            delete this.saving[key];
                            this.savingCount = Object.keys(this.saving).length;
                        }
                    },
                }));
            });
        </script>
    @endpush
</x-app-layout>
