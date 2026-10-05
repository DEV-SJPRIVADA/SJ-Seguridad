<x-app-layout>
    @php
        $showCreateModal = (bool) ($showCreateModal ?? false);
        $hasActiveFilters = ($filters['anio'] ?? '') !== ''
            || ($filters['mes'] ?? '') !== ''
            || ($filters['estado_id'] ?? '') !== ''
            || ($filters['cedula'] ?? '') !== ''
            || ($filters['nombre'] ?? '') !== '';
    @endphp

    <x-slot name="header">
        @include('areas.gestion_humana.cliente_interno.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div
        class="page-section cliente-interno-solicitudes-page req-manage-page"
        x-data="clienteInternoSolicitudes({
            canEdit: @js($canEdit),
        })"
        @cliente-interno-solicitud-open-edit.window="openEdit($event.detail)"
    >
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert--danger">{{ session('error') }}</div>
            @endif
            @if ($errors->has('import_file') || $errors->has('confirm_replace') || $errors->has('anio') || $errors->has('mes'))
                <div class="alert alert--danger">
                    {{ $errors->first('import_file') ?: ($errors->first('confirm_replace') ?: ($errors->first('anio') ?: $errors->first('mes'))) }}
                </div>
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
                            <form method="GET" action="{{ route('gestion-humana.cliente-interno.solicitudes') }}" class="req-manage-filters">
                                <div class="cursos-registros-page__filters">
                                    <div class="form-field">
                                        <label class="form-label" for="filter_anio">Año</label>
                                        <x-searchable-select
                                            id="filter_anio"
                                            name="anio"
                                            :options="$filterAnioOptions"
                                            :value="$filters['anio']"
                                            placeholder="Todos"
                                            :allow-clear="true"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_mes">Mes</label>
                                        <x-searchable-select
                                            id="filter_mes"
                                            name="mes"
                                            :options="$filterMesOptions"
                                            :value="$filters['mes']"
                                            placeholder="Todos"
                                            :allow-clear="true"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_estado_id">Estado</label>
                                        <x-searchable-select
                                            id="filter_estado_id"
                                            name="estado_id"
                                            :options="$filterEstadoOptions"
                                            :value="$filters['estado_id']"
                                            placeholder="Todos"
                                            :allow-clear="true"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_cedula">Cédula</label>
                                        <input
                                            id="filter_cedula"
                                            name="cedula"
                                            type="text"
                                            class="form-input"
                                            value="{{ $filters['cedula'] }}"
                                            autocomplete="off"
                                        >
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_nombre">Nombre</label>
                                        <input
                                            id="filter_nombre"
                                            name="nombre"
                                            type="text"
                                            class="form-input"
                                            value="{{ $filters['nombre'] }}"
                                            autocomplete="off"
                                        >
                                    </div>
                                    <div class="form-field cursos-registros-page__filter-actions">
                                        <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Filtrar" aria-label="Filtrar">
                                            <x-lucide-search width="18" height="18" aria-hidden="true" />
                                        </button>
                                        <a href="{{ route('gestion-humana.cliente-interno.solicitudes') }}" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost" title="Limpiar filtros" aria-label="Limpiar filtros">
                                            <x-lucide-x width="18" height="18" aria-hidden="true" />
                                        </a>
                                        <x-export-excel
                                            route="{{ $exportUrl }}"
                                            label=""
                                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                            title="Exportar a Excel (respeta filtros)"
                                            aria-label="Exportar a Excel"
                                        />
                                    </div>
                                </div>
                            </form>
                        </div>
                    </details>

                    <div class="cursos-registros-page__table-toolbar">
                        <p class="req-manage-filters__meta">
                            <strong id="cliente-interno-solicitudes-count">…</strong>
                            <span>registro(s)</span>
                        </p>

                        @if ($canEdit)
                            <div class="cursos-registros-page__table-actions">
                                <button
                                    type="button"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Plantilla e importar (reemplaza periodo)"
                                    aria-label="Plantilla e importar"
                                    x-on:click.prevent="$dispatch('open-modal', 'cliente-interno-import')"
                                >
                                    <x-lucide-upload width="18" height="18" aria-hidden="true" />
                                </button>
                                <button
                                    type="button"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                    title="Nueva solicitud"
                                    aria-label="Nueva solicitud"
                                    x-on:click.prevent="$dispatch('open-modal', 'cliente-interno-solicitud-nueva')"
                                >
                                    <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                </button>
                            </div>
                        @endif
                    </div>

                    <div class="data-table-wrap req-manage-shell__table cursos-registros-page__table-wrap data-table-wrap--booting">
                        @include('partials.data-table-loader')
                        <table
                            id="cliente-interno-solicitudes-datatable"
                            class="data-table js-cliente-interno-solicitudes-datatable"
                            data-dt-url="{{ $datatableUrl }}"
                            data-dt-can-edit="{{ $canEdit ? '1' : '0' }}"
                            style="width:100%"
                        >
                            <thead>
                                <tr>
                                    <th>Fecha solicitud</th>
                                    <th>Nombre</th>
                                    <th>Cédula</th>
                                    <th>Correo</th>
                                    <th>Solicitud</th>
                                    <th>Fecha respuesta</th>
                                    <th>Estado</th>
                                    <th>Novedad</th>
                                    <th>Días</th>
                                    @if ($canEdit)
                                        <th>Acciones</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if ($canEdit)
                <x-modal name="cliente-interno-solicitud-nueva" maxWidth="2xl" :show="$showCreateModal" focusable>
                    <div class="modal-card ficha-empleados-masivos-modal">
                        <div class="ficha-empleados-masivos-modal__header">
                            <div class="ficha-empleados-masivos-modal__heading">
                                <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                                    <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                </span>
                                <div>
                                    <h3 class="ficha-empleados-masivos-modal__title">Nueva solicitud</h3>
                                    <p class="ficha-empleados-masivos-modal__lead">Obligatorios: fecha, nombre, cédula y tipo de solicitud.</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="ficha-empleados-masivos-modal__close"
                                aria-label="Cerrar"
                                x-on:click="$dispatch('close-modal', 'cliente-interno-solicitud-nueva')"
                            >
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

                        @if (count($tipoSolicitudOptions) === 0)
                            <div class="alert alert--warning ficha-empleados-masivos-modal__alert">
                                No hay tipos de solicitud activos.
                                <a href="{{ route('gestion-humana.cliente-interno.catalogos', ['catalog' => 'tipos-solicitud']) }}" class="font-semibold underline">
                                    Créelos en Catálogos → Tipos de solicitud
                                </a>
                                (código + nombre, botón +).
                            </div>
                        @endif

                        <form method="POST" action="{{ $storeUrl }}" class="cursos-registros-page__form">
                            @csrf
                            @include('areas.gestion_humana.cliente_interno.partials.solicitud-form-fields', [
                                'prefix' => 'create',
                                'mode' => 'create',
                                'tipoSolicitudOptions' => $tipoSolicitudOptions,
                                'estadoFormOptions' => $estadoFormOptions,
                                'values' => [
                                    'fecha_solicitud' => old('fecha_solicitud', ''),
                                    'nombre_apellidos' => old('nombre_apellidos', ''),
                                    'cedula' => old('cedula', ''),
                                    'correo_electronico' => old('correo_electronico', ''),
                                    'tipo_solicitud_id' => old('tipo_solicitud_id', ''),
                                    'fecha_respuesta' => old('fecha_respuesta', ''),
                                    'estado_id' => old('estado_id', ''),
                                    'novedad' => old('novedad', ''),
                                    'dias_respuesta' => old('dias_respuesta', ''),
                                ],
                            ])
                        </form>
                    </div>
                </x-modal>

                {{-- Modal edición: se abre desde acciones de fila (CustomEvent). --}}
                <div
                    class="cursos-registros-page__modal"
                    x-show="editOpen"
                    x-cloak
                    @keydown.escape.window="editOpen = false"
                >
                    <div class="cursos-registros-page__modal-backdrop" @click="editOpen = false"></div>
                    <div class="cursos-registros-page__modal-panel panel" role="dialog" aria-modal="true" style="max-width: 56rem;">
                        <div class="panel__header panel-heading-row">
                            <h3 class="panel-title">Editar solicitud</h3>
                            <button type="button" class="btn btn--ghost btn--sm" @click="editOpen = false">Cerrar</button>
                        </div>
                        <div class="panel__body">
                            <form method="POST" :action="editForm.update_url" class="cursos-registros-page__form">
                                @csrf
                                @method('PATCH')
                                @include('areas.gestion_humana.cliente_interno.partials.solicitud-form-fields', [
                                    'prefix' => 'edit',
                                    'mode' => 'edit',
                                    'tipoSolicitudOptions' => $tipoSolicitudOptions,
                                    'estadoFormOptions' => $estadoFormOptions,
                                    'values' => [
                                        'tipo_solicitud_id' => '',
                                        'estado_id' => '',
                                    ],
                                ])
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            @if ($canEdit)
                @include('areas.gestion_humana.cliente_interno.partials.import-modal', [
                    'importTemplateUrl' => $importTemplateUrl,
                    'importUrl' => $importUrl,
                    'periodCountUrl' => $periodCountUrl,
                    'importAnioOptions' => $importAnioOptions,
                    'importMesOptions' => $importMesOptions,
                    'show' => $showImportModal ?? false,
                ])
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            function clienteInternoSolicitudes(config) {
                return {
                    canEdit: Boolean(config.canEdit),
                    editOpen: false,
                    editForm: {},
                    diasTouched: false,
                    recalcularDias: false,

                    openEdit(row) {
                        this.editForm = Object.assign({}, row);
                        this.diasTouched = false;
                        this.recalcularDias = false;
                        this.editOpen = true;
                        this.$nextTick(() => {
                            this.setSearchableValue('edit_tipo_solicitud_id', row.tipo_solicitud_id);
                            this.setSearchableValue('edit_estado_id', row.estado_id);
                        });
                    },

                    markDiasTouched() {
                        this.diasTouched = true;
                        this.recalcularDias = false;
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
                };
            }

            document.addEventListener('DOMContentLoaded', function () {
                const $ = window.jQuery;
                if (! $ || ! $.fn.DataTable) {
                    return;
                }

                const $table = $('.js-cliente-interno-solicitudes-datatable');
                if (! $table.length) {
                    return;
                }

                const canEdit = $table.data('dt-can-edit') === 1 || $table.data('dt-can-edit') === '1';
                const actionsIndex = canEdit ? 9 : -1;

                const api = $table.DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: { url: $table.data('dt-url') },
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                        emptyTable: 'No hay solicitudes para este filtro.',
                    },
                    dom: '<"req-manage-dt-top"lf><"req-manage-table-scroll"t><"req-manage-dt-bottom"ip>',
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    pageLength: 25,
                    responsive: false,
                    order: [[0, 'desc']],
                    columnDefs: actionsIndex >= 0
                        ? [{ targets: [actionsIndex], orderable: false, searchable: false }]
                        : [],
                });

                api.on('xhr.dt', function (_event, _settings, json) {
                    $table.closest('.data-table-wrap').removeClass('data-table-wrap--booting');
                    const el = document.getElementById('cliente-interno-solicitudes-count');
                    if (el && json && typeof json.recordsFiltered !== 'undefined') {
                        el.textContent = json.recordsFiltered;
                    }
                });

                $table.on('click', '.js-cliente-interno-solicitud-edit', function () {
                    try {
                        const row = JSON.parse(this.getAttribute('data-solicitud-edit') || '{}');
                        window.dispatchEvent(new CustomEvent('cliente-interno-solicitud-open-edit', { detail: row }));
                    } catch (e) {}
                });

                @if ($canEdit)
                const form = document.querySelector('[data-ci-import-form]');
                if (form) {
                    const fileInput = form.querySelector('[data-ci-import-file]');
                    const fileName = form.querySelector('[data-ci-import-name]');
                    const submitBtn = form.querySelector('[data-ci-import-submit]');
                    const confirmBox = form.querySelector('[data-ci-import-confirm]');
                    const loading = document.querySelector('[data-ci-import-loading]');
                    const countEl = form.querySelector('[data-ci-import-period-count]');
                    const anioInput = document.getElementById('ci_import_anio');
                    const mesInput = document.getElementById('ci_import_mes');
                    const periodCountUrl = form.getAttribute('data-period-count-url');
                    let countTimer = null;

                    const syncSubmit = () => {
                        const hasFile = !!(fileInput?.files && fileInput.files.length > 0);
                        const confirmed = !!(confirmBox && confirmBox.checked);
                        const hasPeriod = !!(anioInput?.value && mesInput?.value);
                        if (submitBtn) {
                            submitBtn.disabled = !(hasFile && confirmed && hasPeriod);
                        }
                    };

                    const refreshPeriodCount = () => {
                        const anio = anioInput?.value || '';
                        const mes = mesInput?.value || '';
                        if (! periodCountUrl || ! anio || ! mes || ! countEl) {
                            if (countEl) {
                                countEl.textContent = 'Seleccione año y mes para ver cuántas filas se borrarán.';
                            }
                            syncSubmit();
                            return;
                        }

                        countEl.textContent = 'Consultando filas del periodo…';
                        const url = new URL(periodCountUrl, window.location.origin);
                        url.searchParams.set('anio', anio);
                        url.searchParams.set('mes', mes);

                        fetch(url.toString(), {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        })
                            .then((res) => {
                                if (! res.ok) {
                                    throw new Error('No se pudo consultar el conteo.');
                                }
                                return res.json();
                            })
                            .then((data) => {
                                const n = typeof data.count === 'number' ? data.count : 0;
                                const label = data.label || (mes + '/' + anio);
                                countEl.textContent = n === 1
                                    ? 'Se borrará 1 solicitud de ' + label + '.'
                                    : 'Se borrarán ' + n + ' solicitudes de ' + label + '.';
                            })
                            .catch(() => {
                                countEl.textContent = 'No se pudo obtener el conteo del periodo.';
                            })
                            .finally(syncSubmit);
                    };

                    const scheduleCount = () => {
                        clearTimeout(countTimer);
                        countTimer = setTimeout(refreshPeriodCount, 200);
                        syncSubmit();
                    };

                    fileInput?.addEventListener('change', () => {
                        const name = fileInput.files?.[0]?.name || 'Sin archivo seleccionado';
                        if (fileName) {
                            fileName.textContent = name;
                        }
                        syncSubmit();
                    });

                    confirmBox?.addEventListener('change', syncSubmit);
                    anioInput?.addEventListener('change', scheduleCount);
                    mesInput?.addEventListener('change', scheduleCount);
                    refreshPeriodCount();
                    syncSubmit();

                    form.addEventListener('submit', () => {
                        if (loading) {
                            loading.hidden = false;
                            loading.setAttribute('aria-busy', 'true');
                        }
                        if (submitBtn) {
                            submitBtn.disabled = true;
                        }
                    });
                }
                @endif
            });
        </script>
    @endpush
</x-app-layout>
