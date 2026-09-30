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
        x-data="reportesNovedadesVacaciones({
            lookupUrl: @js($lookupUrl),
            canEdit: @js($canEdit),
            canReview: @js($canReview),
            storeUrl: @js($storeUrl),
        })"
        @rn-vacacion-open-edit.window="openEdit($event.detail)"
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
                            <form method="GET" action="{{ route('gestion-humana.reportes-novedades.vacaciones') }}" class="req-manage-filters">
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
                                        <label class="form-label" for="filter_fecha_hasta">Inicio hasta</label>
                                        <input id="filter_fecha_hasta" name="fecha_hasta" type="date" class="form-input" value="{{ $filters['fecha_hasta'] }}">
                                    </div>
                                    <div class="form-field cursos-registros-page__filter-actions">
                                        <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Filtrar" aria-label="Filtrar">
                                            <x-lucide-search width="18" height="18" aria-hidden="true" />
                                        </button>
                                        <a href="{{ route('gestion-humana.reportes-novedades.vacaciones') }}" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost" title="Limpiar filtros" aria-label="Limpiar filtros">
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
                            <strong id="rn-vacaciones-count">…</strong>
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
                                    x-on:click.prevent="openCreate()"
                                    title="Nueva vacación"
                                    aria-label="Nueva vacación"
                                >
                                    <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="data-table-wrap req-manage-shell__table cursos-registros-page__table-wrap data-table-wrap--booting">
                        @include('partials.data-table-loader')
                        <table
                            id="rn-vacaciones-datatable"
                            class="data-table js-rn-vacaciones-datatable"
                            data-dt-url="{{ $datatableUrl }}"
                            style="width:100%"
                        >
                            <thead>
                                <tr>
                                    <th>Cédula</th>
                                    <th>Nombre</th>
                                    <th>Cargo</th>
                                    <th>Destino</th>
                                    <th>Novedad</th>
                                    <th>Días</th>
                                    <th>Inicio</th>
                                    <th>Observaciones</th>
                                    <th>Obs. Nómina</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if ($canEdit)
                <x-modal name="rn-vacacion-nuevo" maxWidth="3xl" :show="$showCreateModal" focusable>
                    <div class="modal-card ficha-empleados-masivos-modal rn-novedad-modal">
                        <div class="ficha-empleados-masivos-modal__header rn-novedad-modal__header">
                            <div class="ficha-empleados-masivos-modal__heading">
                                <span class="ficha-empleados-masivos-modal__heading-icon rn-novedad-modal__icon" aria-hidden="true">
                                    <x-lucide-calendar-days width="18" height="18" aria-hidden="true" />
                                </span>
                                <div>
                                    <p class="rn-novedad-modal__eyebrow">Reportes de novedades</p>
                                    <h3 class="ficha-empleados-masivos-modal__title">Nueva vacación</h3>
                                    <p class="ficha-empleados-masivos-modal__lead">Busque la cédula para precargar datos de Ficha y complete la novedad.</p>
                                </div>
                            </div>
                            <button type="button" class="ficha-empleados-masivos-modal__close" aria-label="Cerrar" x-on:click="$dispatch('close-modal', 'rn-vacacion-nuevo')">
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

                        <form method="POST" action="{{ $storeUrl }}" class="cursos-registros-page__form rn-novedad-modal__form">
                            @csrf
                            <div class="rn-novedad-modal__body">
                                @include('areas.gestion_humana.reportes_novedades.partials.vacacion-form-fields', [
                                    'prefix' => 'create',
                                    'mode' => 'create',
                                    'novedadOptions' => $novedadOptions,
                                    'canEditGh' => true,
                                    'canReviewNomina' => false,
                                    'showNomina' => false,
                                    'alpine' => true,
                                    'values' => [
                                        'document_number' => old('document_number', ''),
                                        'employee_name' => old('employee_name', ''),
                                        'cargo' => old('cargo', ''),
                                        'destino' => old('destino', ''),
                                        'novedad' => old('novedad', ''),
                                        'dias_novedad' => old('dias_novedad', ''),
                                        'fecha_inicio' => old('fecha_inicio', ''),
                                        'observaciones' => old('observaciones', ''),
                                        'observacion_nomina' => '',
                                    ],
                                ])
                            </div>
                            <div class="ficha-empleados-masivos-modal__footer rn-novedad-modal__footer">
                                <button type="button" class="btn btn--ghost" x-on:click="$dispatch('close-modal', 'rn-vacacion-nuevo')">
                                    <x-lucide-x width="16" height="16" aria-hidden="true" />
                                    Cancelar
                                </button>
                                <button type="submit" class="btn btn--primary">
                                    <x-lucide-save width="16" height="16" aria-hidden="true" />
                                    Guardar
                                </button>
                            </div>
                        </form>
                    </div>
                </x-modal>
            @endif

            <x-modal name="rn-vacacion-editar" maxWidth="3xl" focusable>
                <div class="modal-card ficha-empleados-masivos-modal">
                    <div class="ficha-empleados-masivos-modal__header">
                        <div class="ficha-empleados-masivos-modal__heading">
                            <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                                <x-lucide-pencil width="18" height="18" aria-hidden="true" />
                            </span>
                            <div>
                                <h3 class="ficha-empleados-masivos-modal__title" x-text="editTitle">Editar vacación</h3>
                                <p class="ficha-empleados-masivos-modal__lead">Campos editables según su permiso.</p>
                            </div>
                        </div>
                        <button type="button" class="ficha-empleados-masivos-modal__close" aria-label="Cerrar" x-on:click="$dispatch('close-modal', 'rn-vacacion-editar')">
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
                        @include('areas.gestion_humana.reportes_novedades.partials.vacacion-form-fields', [
                            'prefix' => 'edit',
                            'mode' => 'edit',
                            'novedadOptions' => $novedadOptions,
                            'canEditGh' => $canEdit,
                            'canReviewNomina' => $canReview && ! $canEdit,
                            'showNomina' => true,
                            'alpine' => true,
                            'values' => [],
                        ])
                        <div class="ficha-empleados-masivos-modal__footer">
                            <button type="button" class="btn btn--ghost" x-on:click="$dispatch('close-modal', 'rn-vacacion-editar')">Cancelar</button>
                            <button type="submit" class="btn btn--primary" x-text="editMode === 'gh' ? 'Actualizar' : 'Guardar revisión'">Guardar</button>
                        </div>
                    </form>
                </div>
            </x-modal>

            @include('areas.gestion_humana.reportes_novedades.partials.historial-modal', [
                'sheetLabel' => 'Vacaciones',
            ])
        </div>
    </div>

    @push('scripts')
        <script>
            function reportesNovedadesVacaciones(config) {
                const emptyForm = () => ({
                    document_number: '',
                    employee_name: '',
                    cargo: '',
                    destino: '',
                    novedad: '',
                    dias_novedad: '',
                    fecha_inicio: '',
                    observaciones: '',
                    observacion_nomina: '',
                });

                return {
                    lookupUrl: config.lookupUrl,
                    canEdit: !!config.canEdit,
                    canReview: !!config.canReview,
                    storeUrl: config.storeUrl,
                    editMode: 'gh',
                    editTitle: 'Editar vacación',
                    editUpdateUrl: '',
                    editReviewUrl: '',
                    form: {
                        document_number: @js(old('document_number', '')),
                        employee_name: @js(old('employee_name', '')),
                        cargo: @js(old('cargo', '')),
                        destino: @js(old('destino', '')),
                        novedad: @js(old('novedad', '')),
                        dias_novedad: @js(old('dias_novedad', '')),
                        fecha_inicio: @js(old('fecha_inicio', '')),
                        observaciones: @js(old('observaciones', '')),
                        observacion_nomina: '',
                    },
                    lookupMessage: '',
                    lookupOk: false,
                    lookupLoading: false,
                    historialLoading: false,
                    historialItems: [],
                    openCreate() {
                        this.form = emptyForm();
                        this.lookupMessage = '';
                        this.lookupOk = false;
                        this.lookupLoading = false;
                        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'rn-vacacion-nuevo' }));
                        this.$nextTick(() => {
                            this.setSearchableValue('create_novedad', '');
                            document.getElementById('create_document_number')?.focus();
                        });
                    },
                    openEdit(row) {
                        this.form = {
                            document_number: row.document_number || '',
                            employee_name: row.employee_name || '',
                            cargo: row.cargo || '',
                            destino: row.destino || '',
                            novedad: row.novedad || '',
                            dias_novedad: row.dias_novedad ?? '',
                            fecha_inicio: row.fecha_inicio || '',
                            observaciones: row.observaciones || '',
                            observacion_nomina: row.observacion_nomina || '',
                        };
                        this.editUpdateUrl = row.update_url || '';
                        this.editReviewUrl = row.review_url || '';
                        if (row.can_edit) {
                            this.editMode = 'gh';
                            this.editTitle = 'Editar vacación';
                        } else {
                            this.editMode = 'review';
                            this.editTitle = 'Revisión Nómina';
                        }
                        this.lookupMessage = '';
                        this.lookupOk = false;
                        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'rn-vacacion-editar' }));
                        this.$nextTick(() => {
                            this.setSearchableValue('edit_novedad', row.novedad || '');
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
                        this.lookupLoading = true;
                        this.lookupOk = false;
                        this.lookupMessage = 'Consultando Ficha…';
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
                                this.form.document_number = data.document_number || cedula;
                                this.form.employee_name = data.employee_name || '';
                                this.form.cargo = data.cargo || '';
                                this.form.destino = data.destino || '';
                                this.lookupOk = true;
                                this.lookupMessage = 'Datos precargados desde Ficha.';
                            } else {
                                this.lookupOk = false;
                                this.lookupMessage = data.message || 'No encontrado en Ficha; complete manualmente.';
                            }
                        } catch (e) {
                            this.lookupOk = false;
                            this.lookupMessage = 'No se pudo consultar Ficha.';
                        } finally {
                            this.lookupLoading = false;
                        }
                    },
                    historialActionKind(item) {
                        const action = String(item?.action || '').toLowerCase();
                        const eventType = String(item?.event_type || '').toLowerCase();
                        if (action === 'create' || eventType.includes('created') || eventType.endsWith('_create')) {
                            return 'create';
                        }
                        if (action === 'review' || eventType.includes('review')) {
                            return 'review';
                        }
                        if (action === 'delete' || eventType.includes('deleted') || eventType.includes('delete')) {
                            return 'delete';
                        }
                        if (action === 'export' || eventType === 'export' || action.includes('excel')) {
                            return 'update';
                        }
                        if (action === 'update' || eventType.includes('updated') || eventType.includes('update')) {
                            return 'update';
                        }
                        return 'update';
                    },

                    historialActionLabel(item) {
                        const kind = this.historialActionKind(item);
                        const labels = {
                            create: 'Alta',
                            update: 'Actualización',
                            review: 'Revisión',
                            delete: 'Eliminación',
                        };
                        if (String(item?.action || '').toLowerCase() === 'export' || String(item?.event_type || '') === 'export') {
                            return 'Exportación';
                        }
                        return labels[kind] || 'Evento';
                    },

                    historialActionClass(item, type = 'badge') {
                        const kind = this.historialActionKind(item);
                        const prefix = type === 'dot' ? 'rn-historial-modal__dot--' : 'rn-historial-modal__badge--';
                        return { [prefix + kind]: true };
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

                const $table = $('.js-rn-vacaciones-datatable');
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
                    columnDefs: [{ targets: [9], orderable: false, searchable: false }],
                });

                api.on('xhr.dt', function (_event, _settings, json) {
                    $table.closest('.data-table-wrap').removeClass('data-table-wrap--booting');
                    const el = document.getElementById('rn-vacaciones-count');
                    if (el && json && typeof json.recordsFiltered !== 'undefined') {
                        el.textContent = json.recordsFiltered;
                    }
                });

                $table.on('click', '.js-rn-vacacion-edit', function () {
                    try {
                        const row = JSON.parse(this.getAttribute('data-row-edit') || '{}');
                        window.dispatchEvent(new CustomEvent('rn-vacacion-open-edit', { detail: row }));
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
