<x-app-layout>
    @php
        $showCreateModal = (bool) ($showCreateModal ?? false);
        $hasActiveFilters = ($filters['q'] ?? '') !== ''
            || ($filters['date_from'] ?? '') !== ''
            || ($filters['date_to'] ?? '') !== ''
            || ($filters['commercial_client_id'] ?? '') !== ''
            || ($filters['responsable_user_id'] ?? '') !== ''
            || ($filters['city_code'] ?? '') !== ''
            || ($filters['position_code'] ?? '') !== ''
            || ($filters['solicitud_status_code'] ?? '') !== ''
            || ($filters['estado'] ?? '') !== '';

        // URL de la lista conservando filtros activos y cambiando solo el estado (pills).
        $estadoUrl = fn (?string $estado): string => route(
            'gestion-humana.seleccion.examenes',
            array_filter(array_merge($filters, ['estado' => $estado]), fn ($value) => $value !== null && $value !== ''),
        );
        $historialUserOptions = $historialUserOptions ?? [];
    @endphp

    <x-slot name="header">
        @include('areas.gestion_humana.seleccion.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div
        class="page-section seleccion-examenes-page req-manage-page"
        x-data="seleccionExamens({
            lookupUrl: @js($lookupUrl),
            canEdit: @js($canEdit),
            storeUrl: @js($storeUrl),
            historialUrl: @js($historialUrl ?? ''),
            lookupIngresoUrl: @js($lookupIngresoUrl ?? ''),
            prefillDocument: @js($createPrefill['document_number'] ?? ''),
            oldConfirmDuplicate: @js(old('confirm_duplicate') ? true : false),
            oldDuplicates: @js([]),
        })"
        @seleccion-examen-open-edit.window="openEdit($event.detail)"
        @seleccion-examen-open-historial.window="openHistorial($event.detail)"
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
                        <form method="GET" action="{{ route('gestion-humana.seleccion.examenes') }}" class="req-manage-filters">
                            @if (($filters['estado'] ?? '') !== '')
                                <input type="hidden" name="estado" value="{{ $filters['estado'] }}">
                            @endif
                            {{-- Filtros en filas: 1) búsqueda + fechas ARL + acciones; 2) selectores; 3) estado (pills) --}}
                            <div class="seleccion-ingresos-filters">
                                <div class="seleccion-ingresos-filters__row seleccion-ingresos-filters__row--primary seleccion-ingresos-filters__row--dates-2">
                                    <div class="form-field">
                                        <label class="form-label" for="filter_q">Buscar</label>
                                        <input id="filter_q" name="q" type="search" class="form-input" value="{{ $filters['q'] }}" placeholder="Cédula, nombre, correo…">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_date_from">Fecha ARL desde</label>
                                        <input id="filter_date_from" name="date_from" type="date" class="form-input" value="{{ $filters['date_from'] }}">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_date_to">Fecha ARL hasta</label>
                                        <input id="filter_date_to" name="date_to" type="date" class="form-input" value="{{ $filters['date_to'] }}">
                                    </div>
                                    <div class="form-field cursos-registros-page__filter-actions seleccion-ingresos-filters__actions">
                                        <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Filtrar" aria-label="Filtrar">
                                            <x-lucide-search width="18" height="18" aria-hidden="true" />
                                        </button>
                                        <a href="{{ route('gestion-humana.seleccion.examenes') }}" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost" title="Limpiar filtros" aria-label="Limpiar filtros">
                                            <x-lucide-x width="18" height="18" aria-hidden="true" />
                                        </a>
                                    </div>
                                </div>

                                <div class="seleccion-ingresos-filters__row seleccion-ingresos-filters__row--selects">
                                    <div class="form-field">
                                        <label class="form-label" for="filter_commercial_client_id">Cliente</label>
                                        <x-searchable-select id="filter_commercial_client_id" name="commercial_client_id" :options="$clientOptions" :value="$filters['commercial_client_id']" placeholder="Todos" :allow-clear="true" />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_responsable_user_id">Responsable</label>
                                        <x-searchable-select id="filter_responsable_user_id" name="responsable_user_id" :options="$responsableOptions" :value="$filters['responsable_user_id']" placeholder="Todos" :allow-clear="true" />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_city_code">Ciudad</label>
                                        <x-searchable-select id="filter_city_code" name="city_code" :options="$cityOptions" :value="$filters['city_code']" placeholder="Todas" :allow-clear="true" />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_position_code">Cargo</label>
                                        <x-searchable-select id="filter_position_code" name="position_code" :options="$positionOptions" :value="$filters['position_code']" placeholder="Todos" :allow-clear="true" />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_solicitud_status_code">Solicitud</label>
                                        <x-searchable-select id="filter_solicitud_status_code" name="solicitud_status_code" :options="$solicitudStatusOptions" :value="$filters['solicitud_status_code']" placeholder="Todas" :allow-clear="true" />
                                    </div>
                                </div>

                                {{-- Estado del examen: pills como en Requisiciones (conservan los demás filtros) --}}
                                <div class="req-manage-filters__status-row">
                                    <p class="req-manage-filters__status-label">Estado</p>
                                    <div class="req-manage-filters__pills req-manage-filters__pills--scroll">
                                        <a href="{{ $estadoUrl(null) }}" class="req-manage-filters__pill {{ ($filters['estado'] ?? '') === '' ? 'is-active' : '' }}">Todos</a>
                                        @foreach ($estadoLabels as $estadoKey => $estadoLabel)
                                            <a
                                                href="{{ $estadoUrl($estadoKey) }}"
                                                class="req-manage-filters__pill status-pill--examen-{{ str_replace('_', '-', $estadoKey) }} {{ ($filters['estado'] ?? '') === $estadoKey ? 'is-active' : '' }}"
                                            >{{ $estadoLabel }}</a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </form>
                        </div>
                    </details>

                    {{-- Toolbar: historial (edición) · export Excel (filtros aplicados) · nuevo examen --}}
                    <div class="cursos-registros-page__table-toolbar">
                        <p class="req-manage-filters__meta">
                            <strong id="seleccion-examenes-count">…</strong>
                            <span>registro(s)</span>
                        </p>

                        <div class="cursos-registros-page__table-actions">
                            @if ($canEdit && ($historialUrl ?? null))
                                <button
                                    type="button"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Historial de cambios"
                                    aria-label="Historial de cambios"
                                    x-on:click.prevent="openHistorial({ url: historialUrl, filterable: true })"
                                >
                                    <x-lucide-history width="18" height="18" aria-hidden="true" />
                                </button>
                            @endif
                            <x-export-excel
                                route="{{ $exportUrl }}"
                                label=""
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                title="Exportar a Excel"
                                aria-label="Exportar a Excel"
                            />
                            @if ($canEdit)
                                <button
                                    type="button"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                    title="Nuevo examen"
                                    aria-label="Nuevo examen"
                                    x-on:click.prevent="$dispatch('open-modal', 'seleccion-examen-nuevo')"
                                >
                                    <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="data-table-wrap req-manage-shell__table cursos-registros-page__table-wrap data-table-wrap--booting">
                        @include('partials.data-table-loader')
                        <table
                            id="seleccion-examenes-datatable"
                            class="data-table js-seleccion-examenes-datatable"
                            data-dt-url="{{ $datatableUrl }}"
                            data-dt-can-edit="{{ $canEdit ? '1' : '0' }}"
                            style="width:100%"
                        >
                            {{-- Columnas = campos del formulario; Estado y Correo tras Fecha ARL (índices en SeleccionExamenDatatableService) --}}
                            <thead>
                                <tr>
                                    <th>CÉDULA</th>
                                    <th>NOMBRE</th>
                                    <th>CARGO</th>
                                    <th>SERVICIO</th>
                                    <th>CLIENTE</th>
                                    <th>CIUDAD</th>
                                    <th>EPS</th>
                                    <th>AFP</th>
                                    <th>FECHA NACIMIENTO</th>
                                    <th>DIRECCIÓN</th>
                                    <th>CELULAR</th>
                                    <th>ESTADO CIVIL</th>
                                    <th>FECHA ARL</th>
                                    <th>ESTADO</th>
                                    <th>CORREO</th>
                                    <th>SOLICITUD</th>
                                    <th>RESPONSABLE</th>
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
                @php
                    $formOptions = [
                        'cityOptions' => $cityOptions,
                        'positionOptions' => $positionOptions,
                        'epsOptions' => $epsOptions,
                        'afpOptions' => $afpOptions,
                        'maritalStatusOptions' => $maritalStatusOptions,
                        'solicitudStatusOptions' => $solicitudStatusOptions,
                        'estadoOptions' => $estadoOptions,
                        'clientOptions' => $clientOptions,
                        'responsableOptions' => $responsableOptions,
                    ];
                @endphp

                {{-- Modal Nuevo examen: mismo shell que Nuevo ingreso (secciones + pie icon-only) --}}
                <x-modal name="seleccion-examen-nuevo" maxWidth="3xl" :show="$showCreateModal" focusable>
                    <div class="modal-card ficha-empleados-masivos-modal seleccion-ingresos-modal">
                        <div class="ficha-empleados-masivos-modal__header">
                            <div class="ficha-empleados-masivos-modal__heading">
                                <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                                    <x-lucide-stethoscope width="18" height="18" aria-hidden="true" />
                                </span>
                                <div>
                                    <h3 class="ficha-empleados-masivos-modal__title">Nuevo examen ocupacional</h3>
                                    <p class="ficha-empleados-masivos-modal__lead">Registra la solicitud de examen del aspirante. Todos los campos son obligatorios; el estado inicia en SOLICITADO.</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="ficha-empleados-masivos-modal__close"
                                aria-label="Cerrar"
                                x-on:click="$dispatch('close-modal', 'seleccion-examen-nuevo')"
                            >
                                <x-lucide-x width="18" height="18" aria-hidden="true" />
                            </button>
                        </div>

                        @if (! empty($createPrefill) && ($prefillIngresoId ?? null))
                            <div class="alert alert--info ficha-empleados-masivos-modal__alert">
                                Datos traídos del ingreso #{{ $prefillIngresoId }} (cédula, nombre, correo, celular, ciudad, cargo, cliente y responsable). Complete los demás campos.
                            </div>
                        @endif

                        @if ($errors->any() && $showCreateModal)
                            <div class="alert alert--danger ficha-empleados-masivos-modal__alert">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form
                            method="POST"
                            action="{{ $storeUrl }}"
                            class="cursos-registros-page__form seleccion-ingresos-modal__form"
                            x-on:submit="guardSubmit($event, 'create')"
                        >
                            @csrf
                            @include('areas.gestion_humana.seleccion.partials.examen-form-fields', [
                                ...$formOptions,
                                'prefix' => 'create',
                                {{-- old() tras error de validación; si viene de «Pasar a examen», datos del ingreso --}}
                                'values' => [
                                    'document_number' => old('document_number', $createPrefill['document_number'] ?? ''),
                                    'full_name' => old('full_name', $createPrefill['full_name'] ?? ''),
                                    'position_code' => old('position_code', $createPrefill['position_code'] ?? ''),
                                    'servicio_sector' => old('servicio_sector', ''),
                                    'commercial_client_id' => old('commercial_client_id', $createPrefill['commercial_client_id'] ?? ''),
                                    'eps_code' => old('eps_code', ''),
                                    'afp_code' => old('afp_code', ''),
                                    'birth_date' => old('birth_date', ''),
                                    'city_code' => old('city_code', $createPrefill['city_code'] ?? ''),
                                    'address' => old('address', ''),
                                    'email' => old('email', $createPrefill['email'] ?? ''),
                                    'phone' => old('phone', $createPrefill['phone'] ?? ''),
                                    'marital_status_code' => old('marital_status_code', ''),
                                    'fecha_arl' => old('fecha_arl', ''),
                                    'estado' => old('estado', \App\Models\SeleccionExamenOcupacional::ESTADO_SOLICITADO),
                                    'solicitud_status_code' => old('solicitud_status_code', ''),
                                    'responsable_user_id' => old('responsable_user_id', $createPrefill['responsable_user_id'] ?? ''),
                                ],
                                'mode' => 'create',
                            ])
                        </form>
                    </div>
                </x-modal>

                {{-- Modal Editar examen: mismo partial en modo edit (x-model + setSearchableValue) --}}
                <x-modal name="seleccion-examen-editar" maxWidth="3xl" focusable>
                    <div class="modal-card ficha-empleados-masivos-modal seleccion-ingresos-modal">
                        <div class="ficha-empleados-masivos-modal__header">
                            <div class="ficha-empleados-masivos-modal__heading">
                                <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                                    <x-lucide-pencil width="18" height="18" aria-hidden="true" />
                                </span>
                                <div>
                                    <h3 class="ficha-empleados-masivos-modal__title">Editar examen ocupacional <span x-text="editForm.id ? '#' + editForm.id : ''"></span></h3>
                                    <p class="ficha-empleados-masivos-modal__lead">Actualiza los datos y el estado del examen.</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="ficha-empleados-masivos-modal__close"
                                aria-label="Cerrar"
                                x-on:click="$dispatch('close-modal', 'seleccion-examen-editar')"
                            >
                                <x-lucide-x width="18" height="18" aria-hidden="true" />
                            </button>
                        </div>

                        <form
                            method="POST"
                            :action="editForm.update_url"
                            class="cursos-registros-page__form seleccion-ingresos-modal__form"
                            x-on:submit="guardSubmit($event, 'edit')"
                        >
                            @csrf
                            @method('PATCH')
                            @include('areas.gestion_humana.seleccion.partials.examen-form-fields', [
                                ...$formOptions,
                                'prefix' => 'edit',
                                'values' => [],
                                'mode' => 'edit',
                            ])
                        </form>
                    </div>
                </x-modal>

                @include('areas.gestion_humana.seleccion.partials.examen-historial-modal', [
                    'historialUserOptions' => $historialUserOptions,
                ])
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            function seleccionExamens(config) {
                const emptyHistorialFilters = () => ({ q: '', user_id: '', action: '', date_from: '', date_to: '' });

                return {
                    lookupUrl: config.lookupUrl,
                    canEdit: config.canEdit,
                    historialUrl: config.historialUrl || '',
                    editForm: {},
                    createDuplicates: Array.isArray(config.oldDuplicates) ? config.oldDuplicates : [],
                    createConfirmDuplicate: Boolean(config.oldConfirmDuplicate),
                    editDuplicates: [],
                    editConfirmDuplicate: false,
                    historialLoading: false,
                    historialItems: [],
                    historialFilterable: false,
                    historialBaseUrl: '',
                    historialFilters: emptyHistorialFilters(),
                    lookupIngresoUrl: config.lookupIngresoUrl || '',
                    ingresoLookup: { loading: false, found: false, message: '' },

                    init() {
                        // Llegada desde «Pasar a examen»: valida duplicados de la cédula prellenada.
                        if (config.prefillDocument) {
                            this.lookupDuplicates(config.prefillDocument, 'create');
                        }
                    },

                    // Nuevo examen: busca la cédula en Ingreso y prellena los campos coincidentes.
                    async lookupIngreso(cedula) {
                        const value = String(cedula || '').trim();
                        if (! value || ! this.lookupIngresoUrl) {
                            this.ingresoLookup = { loading: false, found: false, message: '' };
                            return;
                        }

                        this.ingresoLookup = { loading: true, found: false, message: 'Buscando en Ingreso…' };

                        try {
                            const res = await fetch(this.lookupIngresoUrl + '?' + new URLSearchParams({ cedula: value }).toString(), {
                                headers: { 'Accept': 'application/json' },
                            });
                            const json = res.ok ? await res.json() : {};

                            if (! json.found || ! json.data) {
                                this.ingresoLookup = {
                                    loading: false,
                                    found: false,
                                    message: json.message || 'No hay ingreso registrado con esa cédula. Complete los datos manualmente.',
                                };
                                return;
                            }

                            const data = json.data;
                            ['full_name', 'email', 'phone'].forEach((field) => {
                                const input = document.getElementById('create_' + field);
                                if (input && data[field]) {
                                    input.value = data[field];
                                }
                            });
                            ['city_code', 'position_code', 'commercial_client_id', 'responsable_user_id'].forEach((field) => {
                                this.setSearchableIfOption('create_' + field, data[field]);
                            });

                            this.ingresoLookup = {
                                loading: false,
                                found: true,
                                message: 'Datos traídos del ingreso #' + json.ingreso_id + '. Revise y complete los demás campos.',
                            };
                        } catch (e) {
                            this.ingresoLookup = { loading: false, found: false, message: 'No se pudo consultar Ingreso. Complete los datos manualmente.' };
                        }
                    },

                    // Asigna valor a un searchable-select solo si la opción existe (catálogo activo).
                    setSearchableIfOption(id, value) {
                        if (value === undefined || value === null || value === '') {
                            return;
                        }
                        const input = document.getElementById(id);
                        const wrap = input ? input.closest('[x-data]') : null;
                        if (! wrap || ! window.Alpine) {
                            return;
                        }
                        const select = window.Alpine.$data(wrap);
                        const exists = (select.options || []).some((opt) => String(opt.value) === String(value));
                        if (exists) {
                            select.value = String(value);
                        }
                    },

                    // Edición: abre el modal compartido y carga selects searchable con los valores de la fila.
                    openEdit(row) {
                        this.editForm = Object.assign({}, row);
                        this.editDuplicates = [];
                        this.editConfirmDuplicate = false;
                        this.$dispatch('open-modal', 'seleccion-examen-editar');
                        this.$nextTick(() => {
                            setTimeout(() => {
                                this.setSearchableValue('edit_position_code', row.position_code);
                                this.setSearchableValue('edit_commercial_client_id', row.commercial_client_id);
                                this.setSearchableValue('edit_eps_code', row.eps_code);
                                this.setSearchableValue('edit_afp_code', row.afp_code);
                                this.setSearchableValue('edit_city_code', row.city_code);
                                this.setSearchableValue('edit_marital_status_code', row.marital_status_code);
                                this.setSearchableValue('edit_estado', row.estado);
                                this.setSearchableValue('edit_solicitud_status_code', row.solicitud_status_code);
                                this.setSearchableValue('edit_responsable_user_id', row.responsable_user_id);
                            }, 150);
                        });
                        this.lookupDuplicates(row.document_number || '', 'edit');
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

                    async lookupDuplicates(cedula, mode) {
                        const value = String(cedula || '').trim();
                        if (! value || ! this.lookupUrl) {
                            if (mode === 'edit') {
                                this.editDuplicates = [];
                            } else {
                                this.createDuplicates = [];
                            }
                            return;
                        }

                        const params = new URLSearchParams({ cedula: value });
                        if (mode === 'edit' && this.editForm.id) {
                            params.set('exclude_id', String(this.editForm.id));
                        }

                        try {
                            const res = await fetch(this.lookupUrl + '?' + params.toString(), {
                                headers: { 'Accept': 'application/json' },
                            });
                            if (! res.ok) return;
                            const data = await res.json();
                            const matches = Array.isArray(data.matches) ? data.matches : [];
                            if (mode === 'edit') {
                                this.editDuplicates = matches;
                                if (matches.length === 0) {
                                    this.editConfirmDuplicate = false;
                                }
                            } else {
                                this.createDuplicates = matches;
                                if (matches.length === 0) {
                                    this.createConfirmDuplicate = false;
                                }
                            }
                        } catch (e) {}
                    },

                    guardSubmit(event, mode) {
                        const duplicates = mode === 'edit' ? this.editDuplicates : this.createDuplicates;
                        const confirmed = mode === 'edit' ? this.editConfirmDuplicate : this.createConfirmDuplicate;
                        if (duplicates.length > 0 && ! confirmed) {
                            event.preventDefault();
                            alert('Ya existen exámenes con esta cédula. Marque la confirmación para continuar.');
                            return false;
                        }
                        return true;
                    },

                    // Historial de auditoría: global (filtrable por usuario/acción/fechas) o por registro.
                    historialActionKind(item) {
                        const action = String(item?.action || '').toLowerCase();
                        return ['create', 'update', 'delete'].includes(action) ? action : 'update';
                    },

                    historialActionLabel(item) {
                        return { create: 'Alta', update: 'Actualización', delete: 'Eliminación' }[this.historialActionKind(item)];
                    },

                    historialActionClass(item, type = 'badge') {
                        const prefix = type === 'dot' ? 'rn-historial-modal__dot--' : 'rn-historial-modal__badge--';
                        return { [prefix + this.historialActionKind(item)]: true };
                    },

                    historialQueryUrl() {
                        if (! this.historialBaseUrl) {
                            return '';
                        }
                        const url = new URL(this.historialBaseUrl, window.location.origin);
                        if (this.historialFilterable) {
                            const f = this.historialFilters || {};
                            ['q', 'user_id', 'action', 'date_from', 'date_to'].forEach((key) => {
                                const value = String(f[key] || '').trim();
                                if (value) {
                                    url.searchParams.set(key, value);
                                } else {
                                    url.searchParams.delete(key);
                                }
                            });
                        }
                        return url.pathname + url.search;
                    },

                    async fetchHistorial() {
                        const url = this.historialQueryUrl();
                        if (! url) {
                            return;
                        }
                        this.historialLoading = true;
                        this.historialItems = [];
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

                    async openHistorial(detail) {
                        const url = detail?.url || '';
                        if (! url) {
                            return;
                        }
                        this.historialFilterable = !! detail?.filterable;
                        this.historialBaseUrl = url;
                        this.historialFilters = emptyHistorialFilters();
                        this.setSearchableValue('examen-historial-user', '');
                        this.setSearchableValue('examen-historial-action', '');
                        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'seleccion-examen-historial' }));
                        await this.fetchHistorial();
                    },

                    applyHistorialFilters() {
                        if (this.historialFilterable) {
                            this.fetchHistorial();
                        }
                    },

                    clearHistorialFilters() {
                        this.historialFilters = emptyHistorialFilters();
                        this.setSearchableValue('examen-historial-user', '');
                        this.setSearchableValue('examen-historial-action', '');
                        this.fetchHistorial();
                    },
                };
            }

            document.addEventListener('DOMContentLoaded', function () {
                const $ = window.jQuery;
                if (! $ || ! $.fn.DataTable) {
                    return;
                }

                const $table = $('.js-seleccion-examenes-datatable');
                if (! $table.length) {
                    return;
                }

                const canEdit = $table.data('dt-can-edit') === 1 || $table.data('dt-can-edit') === '1';
                // 17 columnas de datos + Acciones (solo edición).
                const actionsIndex = canEdit ? 17 : -1;
                // Ordenables alineadas con SeleccionExamenDatatableService::applyOrdering.
                const nonOrderable = [4, 9, 10, 16];
                if (actionsIndex >= 0) {
                    nonOrderable.push(actionsIndex);
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
                    pageLength: 10,
                    responsive: false,
                    order: [[12, 'desc']],
                    columnDefs: [
                        { targets: nonOrderable, orderable: false },
                        ...(actionsIndex >= 0 ? [{ targets: [actionsIndex], searchable: false }] : []),
                    ],
                });

                api.on('xhr.dt', function (_event, _settings, json) {
                    $table.closest('.data-table-wrap').removeClass('data-table-wrap--booting');
                    const el = document.getElementById('seleccion-examenes-count');
                    if (el && json && typeof json.recordsFiltered !== 'undefined') {
                        el.textContent = json.recordsFiltered;
                    }
                });

                $table.on('click', '.js-seleccion-examen-edit', function () {
                    try {
                        const row = JSON.parse(this.getAttribute('data-examen-edit') || '{}');
                        window.dispatchEvent(new CustomEvent('seleccion-examen-open-edit', { detail: row }));
                    } catch (e) {}
                });

                $table.on('click', '.js-seleccion-examen-historial', function () {
                    const url = this.getAttribute('data-historial-url') || '';
                    window.dispatchEvent(new CustomEvent('seleccion-examen-open-historial', { detail: { url, filterable: false } }));
                });
            });
        </script>
    @endpush
</x-app-layout>
