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
        x-data="reportesNovedadesIncapacidades({
            lookupUrl: @js($lookupUrl),
            canEdit: @js($canEdit),
            canReview: @js($canReview),
            storeUrl: @js($storeUrl),
        })"
        @rn-incapacidad-open-edit.window="openEdit($event.detail)"
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
                            <form method="GET" action="{{ route('gestion-humana.reportes-novedades.incapacidades') }}" class="req-manage-filters">
                                <div class="cursos-registros-page__filters">
                                    <div class="form-field">
                                        <label class="form-label" for="filter_q">Buscar</label>
                                        <input id="filter_q" name="q" type="search" class="form-input" value="{{ $filters['q'] }}" placeholder="Cédula o nombre…">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_fecha_desde">Inicio desde</label>
                                        <input id="filter_fecha_desde" name="fecha_desde" type="date" class="form-input" value="{{ $filters['fecha_desde'] }}">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_fecha_hasta">Fin hasta</label>
                                        <input id="filter_fecha_hasta" name="fecha_hasta" type="date" class="form-input" value="{{ $filters['fecha_hasta'] }}">
                                    </div>
                                    <div class="form-field cursos-registros-page__filter-actions">
                                        <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Filtrar" aria-label="Filtrar">
                                            <x-lucide-search width="18" height="18" aria-hidden="true" />
                                        </button>
                                        <a href="{{ route('gestion-humana.reportes-novedades.incapacidades') }}" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost" title="Limpiar filtros" aria-label="Limpiar filtros">
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
                            <strong id="rn-incapacidades-count">…</strong>
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
                                    title="Nueva incapacidad"
                                    aria-label="Nueva incapacidad"
                                    x-on:click.prevent="$dispatch('open-modal', 'rn-incapacidad-nuevo')"
                                >
                                    <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="data-table-wrap req-manage-shell__table cursos-registros-page__table-wrap data-table-wrap--booting">
                        @include('partials.data-table-loader')
                        <table
                            id="rn-incapacidades-datatable"
                            class="data-table js-rn-incapacidades-datatable"
                            data-dt-url="{{ $datatableUrl }}"
                            style="width:100%"
                        >
                            <thead>
                                <tr>
                                    <th>Cédula</th>
                                    <th>Nombre</th>
                                    <th>Cargo</th>
                                    <th>Destino</th>
                                    <th>Tipo</th>
                                    <th>Días</th>
                                    <th>Inicio</th>
                                    <th>Fin</th>
                                    <th>Recepción</th>
                                    <th>Obs. Nómina</th>
                                    <th>Días entrega</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if ($canEdit)
                <x-modal name="rn-incapacidad-nuevo" maxWidth="2xl" :show="$showCreateModal" focusable>
                    <div class="modal-card ficha-empleados-masivos-modal">
                        <div class="ficha-empleados-masivos-modal__header">
                            <div class="ficha-empleados-masivos-modal__heading">
                                <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                                    <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                </span>
                                <div>
                                    <h3 class="ficha-empleados-masivos-modal__title">Nueva incapacidad</h3>
                                    <p class="ficha-empleados-masivos-modal__lead">Columnas Gestion Humana.</p>
                                </div>
                            </div>
                            <button type="button" class="ficha-empleados-masivos-modal__close" aria-label="Cerrar" x-on:click="$dispatch('close-modal', 'rn-incapacidad-nuevo')">
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
                            @include('areas.gestion_humana.reportes_novedades.partials.incapacidad-form-fields', [
                                'prefix' => 'create',
                                'mode' => 'create',
                                'tipoOptions' => $tipoOptions,
                                'canEditGh' => true,
                                'canReviewNomina' => false,
                                'showNomina' => false,
                                'alpine' => false,
                                'values' => [
                                    'document_number' => old('document_number', ''),
                                    'employee_name' => old('employee_name', ''),
                                    'cargo' => old('cargo', ''),
                                    'destino' => old('destino', ''),
                                    'tipo_incapacidad' => old('tipo_incapacidad', ''),
                                    'dias' => old('dias', ''),
                                    'fecha_inicio' => old('fecha_inicio', ''),
                                    'fecha_fin' => old('fecha_fin', ''),
                                    'fecha_recepcion' => old('fecha_recepcion', ''),
                                    'fecha_devolucion' => old('fecha_devolucion', ''),
                                    'observacion_devolucion' => old('observacion_devolucion', ''),
                                    'fecha_registro_control_roll' => old('fecha_registro_control_roll', ''),
                                    'fecha_envio_final' => old('fecha_envio_final', ''),
                                    'novedad_control_roll' => old('novedad_control_roll', ''),
                                    'extemporanea' => old('extemporanea', false),
                                    'observaciones' => old('observaciones', ''),
                                    'observacion_nomina' => '',
                                    'dias_entrega' => '',
                                ],
                            ])
                            <div class="ficha-empleados-masivos-modal__footer">
                                <button type="button" class="btn btn--ghost" x-on:click="$dispatch('close-modal', 'rn-incapacidad-nuevo')">Cancelar</button>
                                <button type="submit" class="btn btn--primary">Guardar</button>
                            </div>
                        </form>
                    </div>
                </x-modal>
            @endif

            <x-modal name="rn-incapacidad-editar" maxWidth="2xl" focusable>
                <div class="modal-card ficha-empleados-masivos-modal">
                    <div class="ficha-empleados-masivos-modal__header">
                        <div class="ficha-empleados-masivos-modal__heading">
                            <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                                <x-lucide-pencil width="18" height="18" aria-hidden="true" />
                            </span>
                            <div>
                                <h3 class="ficha-empleados-masivos-modal__title" x-text="editTitle">Editar incapacidad</h3>
                                <p class="ficha-empleados-masivos-modal__lead">Campos editables según su permiso.</p>
                            </div>
                        </div>
                        <button type="button" class="ficha-empleados-masivos-modal__close" aria-label="Cerrar" x-on:click="$dispatch('close-modal', 'rn-incapacidad-editar')">
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
                        @include('areas.gestion_humana.reportes_novedades.partials.incapacidad-form-fields', [
                            'prefix' => 'edit',
                            'mode' => 'edit',
                            'tipoOptions' => $tipoOptions,
                            'canEditGh' => $canEdit,
                            'canReviewNomina' => $canReview && ! $canEdit,
                            'showNomina' => true,
                            'alpine' => true,
                            'values' => [],
                        ])
                        <div class="ficha-empleados-masivos-modal__footer">
                            <button type="button" class="btn btn--ghost" x-on:click="$dispatch('close-modal', 'rn-incapacidad-editar')">Cancelar</button>
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
                                <p class="ficha-empleados-masivos-modal__lead">Eventos de auditoría de Incapacidades.</p>
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
            function reportesNovedadesIncapacidades(config) {
                return {
                    lookupUrl: config.lookupUrl,
                    canEdit: !!config.canEdit,
                    canReview: !!config.canReview,
                    storeUrl: config.storeUrl,
                    editMode: 'gh',
                    editTitle: 'Editar incapacidad',
                    editUpdateUrl: '',
                    editReviewUrl: '',
                    form: {
                        document_number: '',
                        employee_name: '',
                        cargo: '',
                        destino: '',
                        tipo_incapacidad: '',
                        dias: '',
                        fecha_inicio: '',
                        fecha_fin: '',
                        fecha_recepcion: '',
                        fecha_devolucion: '',
                        observacion_devolucion: '',
                        fecha_registro_control_roll: '',
                        fecha_envio_final: '',
                        novedad_control_roll: '',
                        extemporanea: false,
                        observaciones: '',
                        observacion_nomina: '',
                        dias_entrega: '',
                    },
                    lookupMessage: '',
                    historialLoading: false,
                    historialItems: [],
                    openEdit(row) {
                        this.form = {
                            document_number: row.document_number || '',
                            employee_name: row.employee_name || '',
                            cargo: row.cargo || '',
                            destino: row.destino || '',
                            tipo_incapacidad: row.tipo_incapacidad || '',
                            dias: row.dias ?? '',
                            fecha_inicio: row.fecha_inicio || '',
                            fecha_fin: row.fecha_fin || '',
                            fecha_recepcion: row.fecha_recepcion || '',
                            fecha_devolucion: row.fecha_devolucion || '',
                            observacion_devolucion: row.observacion_devolucion || '',
                            fecha_registro_control_roll: row.fecha_registro_control_roll || '',
                            fecha_envio_final: row.fecha_envio_final || '',
                            novedad_control_roll: row.novedad_control_roll || '',
                            extemporanea: !!row.extemporanea,
                            observaciones: row.observaciones || '',
                            observacion_nomina: row.observacion_nomina || '',
                            dias_entrega: row.dias_entrega ?? '',
                        };
                        this.editUpdateUrl = row.update_url || '';
                        this.editReviewUrl = row.review_url || '';
                        if (row.can_edit) {
                            this.editMode = 'gh';
                            this.editTitle = 'Editar incapacidad';
                        } else {
                            this.editMode = 'review';
                            this.editTitle = 'Revisión Nómina';
                        }
                        this.lookupMessage = '';
                        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'rn-incapacidad-editar' }));
                        this.$nextTick(() => {
                            this.setSearchableValue('edit_tipo_incapacidad', row.tipo_incapacidad || '');
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

                const $table = $('.js-rn-incapacidades-datatable');
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
                    order: [[6, 'desc']],
                    columnDefs: [{ targets: [11], orderable: false, searchable: false }],
                });

                api.on('xhr.dt', function (_event, _settings, json) {
                    $table.closest('.data-table-wrap').removeClass('data-table-wrap--booting');
                    const el = document.getElementById('rn-incapacidades-count');
                    if (el && json && typeof json.recordsFiltered !== 'undefined') {
                        el.textContent = json.recordsFiltered;
                    }
                });

                $table.on('click', '.js-rn-incapacidad-edit', function () {
                    try {
                        const row = JSON.parse(this.getAttribute('data-row-edit') || '{}');
                        window.dispatchEvent(new CustomEvent('rn-incapacidad-open-edit', { detail: row }));
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
