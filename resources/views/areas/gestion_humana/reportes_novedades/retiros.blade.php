<x-app-layout>
    @php
        $hasActiveFilters = ($filters['q'] ?? '') !== ''
            || ($filters['fecha_desde'] ?? '') !== ''
            || ($filters['fecha_hasta'] ?? '') !== '';
    @endphp

    <x-slot name="header">
        @include('areas.gestion_humana.reportes_novedades.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div
        class="page-section reportes-novedades-page req-manage-page"
        x-data="reportesNovedadesRetiros({
            lookupUrl: @js($lookupUrl),
            canEdit: @js($canEdit),
            canReview: @js($canReview),
            storeUrl: @js($storeUrl),
            defaultNovedad: @js($defaultNovedad),
        })"
        @rn-retiro-open-edit.window="openEdit($event.detail)"
        @rn-open-historial.window="openHistorial($event.detail)"
    >
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert--danger">{{ session('error') }}</div>
            @endif

            <div class="panel">
                <div class="panel__body panel__body--compact req-manage-shell">
                    <details class="req-manage-shell__filters req-manage-filters req-manage-filters__panel" @if ($hasActiveFilters) open @endif>
                        <summary class="req-manage-filters__panel-toggle">
                            <span>Filtros</span>
                            @if ($hasActiveFilters)
                                <span class="req-manage-filters__panel-badge">Activos</span>
                            @endif
                        </summary>
                        <div class="req-manage-filters__panel-body">
                            <form method="GET" action="{{ route('gestion-humana.reportes-novedades.retiros') }}" class="req-manage-filters">
                                <div class="cursos-registros-page__filters">
                                    <div class="form-field">
                                        <label class="form-label" for="filter_q">Buscar</label>
                                        <input id="filter_q" name="q" type="search" class="form-input" value="{{ $filters['q'] }}" placeholder="Cédula o nombre…">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_fecha_desde">Retiro desde</label>
                                        <input id="filter_fecha_desde" name="fecha_desde" type="date" class="form-input" value="{{ $filters['fecha_desde'] }}">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_fecha_hasta">Retiro hasta</label>
                                        <input id="filter_fecha_hasta" name="fecha_hasta" type="date" class="form-input" value="{{ $filters['fecha_hasta'] }}">
                                    </div>
                                    <div class="form-field cursos-registros-page__filter-actions">
                                        <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Filtrar" aria-label="Filtrar">
                                            <x-lucide-search width="18" height="18" aria-hidden="true" />
                                        </button>
                                        <a href="{{ route('gestion-humana.reportes-novedades.retiros') }}" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost" title="Limpiar filtros" aria-label="Limpiar filtros">
                                            <x-lucide-x width="18" height="18" aria-hidden="true" />
                                        </a>
                                        @if ($canExport)
                                            <x-export-excel
                                                route="{{ $exportUrl }}"
                                                label=""
                                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                            />
                                        @endif
                                    </div>
                                </div>
                            </form>
                        </div>
                    </details>

                    <div class="cursos-registros-page__table-toolbar">
                        <p class="req-manage-filters__meta">
                            <strong id="rn-retiros-count">…</strong>
                            <span>registro(s)</span>
                        </p>
                        <div class="cursos-registros-page__table-actions">
                            <button
                                type="button"
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                title="Historial de la hoja"
                                aria-label="Historial de la hoja"
                                x-on:click.prevent="openHistorial({ url: @js($historialUrl) })"
                            >
                                <x-lucide-history width="18" height="18" aria-hidden="true" />
                            </button>
                            @if ($canEdit)
                                <button
                                    type="button"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                    title="Nuevo retiro"
                                    aria-label="Nuevo retiro"
                                    x-on:click.prevent="$dispatch('open-modal', 'rn-retiro-nuevo')"
                                >
                                    <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="data-table-wrap req-manage-shell__table cursos-registros-page__table-wrap data-table-wrap--booting">
                        @include('partials.data-table-loader')
                        <table
                            id="rn-retiros-datatable"
                            class="data-table js-rn-retiros-datatable"
                            data-dt-url="{{ $datatableUrl }}"
                            style="width:100%"
                        >
                            <thead>
                                <tr>
                                    <th>Cédula</th>
                                    <th>Nombre</th>
                                    <th>Ingreso</th>
                                    <th>Tipo</th>
                                    <th>Cargo</th>
                                    <th>Destino</th>
                                    <th>Novedad</th>
                                    <th>Fecha retiro</th>
                                    <th>Motivo</th>
                                    <th>Observaciones</th>
                                    <th>Nómina</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if ($canEdit)
                <x-modal name="rn-retiro-nuevo" maxWidth="2xl" :show="$showCreateModal" focusable>
                    <div class="modal-card ficha-empleados-masivos-modal">
                        <div class="ficha-empleados-masivos-modal__header">
                            <div class="ficha-empleados-masivos-modal__heading">
                                <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                                    <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                </span>
                                <div>
                                    <h3 class="ficha-empleados-masivos-modal__title">Nuevo retiro</h3>
                                    <p class="ficha-empleados-masivos-modal__lead">Alta manual Gestion Humana.</p>
                                </div>
                            </div>
                            <button type="button" class="ficha-empleados-masivos-modal__close" aria-label="Cerrar" x-on:click="$dispatch('close-modal', 'rn-retiro-nuevo')">
                                <x-lucide-x width="18" height="18" aria-hidden="true" />
                            </button>
                        </div>

                        @if ($errors->any() && $showCreateModal)
                            <div class="alert alert--danger ficha-empleados-masivos-modal__alert">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ $storeUrl }}" class="cursos-registros-page__form">
                            @csrf
                            @include('areas.gestion_humana.reportes_novedades.partials.retiro-form-fields', [
                                'prefix' => 'create',
                                'mode' => 'create',
                                'motivoOptions' => $motivoOptions,
                                'defaultNovedad' => $defaultNovedad,
                                'canEditGh' => true,
                                'canReviewNomina' => false,
                                'showNomina' => false,
                                'alpine' => false,
                                'values' => [
                                    'document_number' => old('document_number', ''),
                                    'employee_name' => old('employee_name', ''),
                                    'fecha_ingreso' => old('fecha_ingreso', ''),
                                    'tipo' => old('tipo', ''),
                                    'cargo' => old('cargo', ''),
                                    'destino' => old('destino', ''),
                                    'novedad' => old('novedad', $defaultNovedad),
                                    'fecha_retiro' => old('fecha_retiro', ''),
                                    'motivo_retiro' => old('motivo_retiro', ''),
                                    'observaciones' => old('observaciones', ''),
                                    'observacion_nomina' => '',
                                ],
                            ])
                            <div class="ficha-empleados-masivos-modal__footer">
                                <button type="button" class="btn btn--ghost" x-on:click="$dispatch('close-modal', 'rn-retiro-nuevo')">Cancelar</button>
                                <button type="submit" class="btn btn--primary">Guardar</button>
                            </div>
                        </form>
                    </div>
                </x-modal>
            @endif

            <x-modal name="rn-retiro-editar" maxWidth="2xl" focusable>
                <div class="modal-card ficha-empleados-masivos-modal">
                    <div class="ficha-empleados-masivos-modal__header">
                        <div class="ficha-empleados-masivos-modal__heading">
                            <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                                <x-lucide-pencil width="18" height="18" aria-hidden="true" />
                            </span>
                            <div>
                                <h3 class="ficha-empleados-masivos-modal__title" x-text="editTitle">Editar retiro</h3>
                                <p class="ficha-empleados-masivos-modal__lead">Campos editables según su permiso.</p>
                            </div>
                        </div>
                        <button type="button" class="ficha-empleados-masivos-modal__close" aria-label="Cerrar" x-on:click="$dispatch('close-modal', 'rn-retiro-editar')">
                            <x-lucide-x width="18" height="18" aria-hidden="true" />
                        </button>
                    </div>

                    <form
                        method="POST"
                        class="cursos-registros-page__form"
                        :action="editMode === 'gh' ? editUpdateUrl : editReviewUrl"
                        x-show="editMode === 'gh' || editMode === 'review'"
                    >
                        @csrf
                        @method('PATCH')
                        @include('areas.gestion_humana.reportes_novedades.partials.retiro-form-fields', [
                            'prefix' => 'edit',
                            'mode' => 'edit',
                            'motivoOptions' => $motivoOptions,
                            'defaultNovedad' => $defaultNovedad,
                            'canEditGh' => $canEdit,
                            'canReviewNomina' => $canReview && ! $canEdit,
                            'showNomina' => true,
                            'alpine' => true,
                            'values' => [],
                        ])
                        <div class="ficha-empleados-masivos-modal__footer">
                            <button type="button" class="btn btn--ghost" x-on:click="$dispatch('close-modal', 'rn-retiro-editar')">Cancelar</button>
                            <button type="submit" class="btn btn--primary" x-text="editMode === 'gh' ? 'Actualizar' : 'Guardar revisión'">Guardar</button>
                        </div>
                    </form>
                </div>
            </x-modal>

            <x-modal name="rn-historial" maxWidth="2xl" focusable>
                <div class="modal-card ficha-empleados-masivos-modal">
                    <div class="ficha-empleados-masivos-modal__header">
                        <div class="ficha-empleados-masivos-modal__heading">
                            <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                                <x-lucide-history width="18" height="18" aria-hidden="true" />
                            </span>
                            <div>
                                <h3 class="ficha-empleados-masivos-modal__title">Historial</h3>
                                <p class="ficha-empleados-masivos-modal__lead">Eventos de auditoría de Retiros.</p>
                            </div>
                        </div>
                        <button type="button" class="ficha-empleados-masivos-modal__close" aria-label="Cerrar" x-on:click="$dispatch('close-modal', 'rn-historial')">
                            <x-lucide-x width="18" height="18" aria-hidden="true" />
                        </button>
                    </div>
                    <div class="ficha-empleados-masivos-modal__body">
                        <p class="panel-text" x-show="historialLoading">Cargando…</p>
                        <p class="panel-text" x-show="!historialLoading && historialItems.length === 0">Sin eventos.</p>
                        <ul class="space-y-2" x-show="!historialLoading && historialItems.length > 0">
                            <template x-for="item in historialItems" :key="item.id">
                                <li class="border-b border-slate-200 py-2 text-sm">
                                    <div class="font-medium" x-text="item.created_at_display"></div>
                                    <div x-text="(item.user_name || 'Sistema') + ' — ' + item.summary"></div>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </x-modal>
        </div>
    </div>

    @push('scripts')
        <script>
            function reportesNovedadesRetiros(config) {
                return {
                    lookupUrl: config.lookupUrl,
                    canEdit: !!config.canEdit,
                    canReview: !!config.canReview,
                    storeUrl: config.storeUrl,
                    defaultNovedad: config.defaultNovedad || 'RETIRO',
                    editMode: 'gh',
                    editTitle: 'Editar retiro',
                    editUpdateUrl: '',
                    editReviewUrl: '',
                    form: {
                        document_number: '',
                        employee_name: '',
                        fecha_ingreso: '',
                        tipo: '',
                        cargo: '',
                        destino: '',
                        novedad: config.defaultNovedad || 'RETIRO',
                        fecha_retiro: '',
                        motivo_retiro: '',
                        observaciones: '',
                        observacion_nomina: '',
                    },
                    lookupMessage: '',
                    historialLoading: false,
                    historialItems: [],
                    openEdit(row) {
                        this.form = {
                            document_number: row.document_number || '',
                            employee_name: row.employee_name || '',
                            fecha_ingreso: row.fecha_ingreso || '',
                            tipo: row.tipo || '',
                            cargo: row.cargo || '',
                            destino: row.destino || '',
                            novedad: row.novedad || this.defaultNovedad,
                            fecha_retiro: row.fecha_retiro || '',
                            motivo_retiro: row.motivo_retiro || '',
                            observaciones: row.observaciones || '',
                            observacion_nomina: row.observacion_nomina || '',
                        };
                        this.editUpdateUrl = row.update_url || '';
                        this.editReviewUrl = row.review_url || '';
                        if (row.can_edit) {
                            this.editMode = 'gh';
                            this.editTitle = 'Editar retiro';
                        } else {
                            this.editMode = 'review';
                            this.editTitle = 'Revisión Nómina';
                        }
                        this.lookupMessage = '';
                        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'rn-retiro-editar' }));
                        this.$nextTick(() => {
                            this.setSearchableValue('edit_motivo_retiro', row.motivo_retiro || '');
                        });
                    },
                    setSearchableValue(id, value) {
                        const input = document.getElementById(id);
                        if (! input || ! window.Alpine) {
                            return;
                        }
                        const wrap = input.closest('[x-data]');
                        if (! wrap) {
                            return;
                        }
                        window.Alpine.$data(wrap).value = value !== undefined && value !== null ? String(value) : '';
                    },
                    async lookupCedula() {
                        const cedula = (this.form.document_number || '').trim();
                        if (!cedula || !this.canEdit) {
                            return;
                        }
                        try {
                            const response = await fetch(this.lookupUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: JSON.stringify({ document_number: cedula }),
                            });
                            const data = await response.json();
                            if (data.found) {
                                this.form.employee_name = data.employee_name || this.form.employee_name;
                                this.form.cargo = data.cargo || this.form.cargo;
                                this.form.destino = data.destino || this.form.destino;
                                this.form.tipo = data.tipo || this.form.tipo;
                                this.form.fecha_ingreso = data.fecha_ingreso || this.form.fecha_ingreso;
                                this.lookupMessage = 'Datos precargados desde Ficha.';
                            } else {
                                this.lookupMessage = data.message || 'No encontrado en Ficha; complete manualmente.';
                            }
                        } catch (e) {
                            this.lookupMessage = 'No se pudo consultar Ficha.';
                        }
                    },
                    async openHistorial(detail) {
                        const url = detail?.url || '';
                        if (!url) {
                            return;
                        }
                        this.historialLoading = true;
                        this.historialItems = [];
                        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'rn-historial' }));
                        try {
                            const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                            const data = await response.json();
                            this.historialItems = data.data || [];
                        } catch (e) {
                            this.historialItems = [];
                        } finally {
                            this.historialLoading = false;
                        }
                    },
                };
            }

            document.addEventListener('DOMContentLoaded', function () {
                const $ = window.jQuery;
                if (! $ || ! $.fn.DataTable) {
                    return;
                }

                const $table = $('.js-rn-retiros-datatable');
                if (! $table.length) {
                    return;
                }

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
                    pageLength: 25,
                    responsive: false,
                    order: [[7, 'desc']],
                    columnDefs: [{ targets: [11], orderable: false, searchable: false }],
                });

                api.on('xhr.dt', function (_event, _settings, json) {
                    $table.closest('.data-table-wrap').removeClass('data-table-wrap--booting');
                    const el = document.getElementById('rn-retiros-count');
                    if (el && json && typeof json.recordsFiltered !== 'undefined') {
                        el.textContent = json.recordsFiltered;
                    }
                });

                $table.on('click', '.js-rn-retiro-edit', function () {
                    try {
                        const row = JSON.parse(this.getAttribute('data-row-edit') || '{}');
                        window.dispatchEvent(new CustomEvent('rn-retiro-open-edit', { detail: row }));
                    } catch (e) {}
                });

                $table.on('click', '.js-rn-historial', function () {
                    const url = this.getAttribute('data-historial-url') || '';
                    window.dispatchEvent(new CustomEvent('rn-open-historial', { detail: { url } }));
                });
            });
        </script>
    @endpush
</x-app-layout>
