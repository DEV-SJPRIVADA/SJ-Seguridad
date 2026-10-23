<x-app-layout>
    @php
        $showCreateModal = (bool) ($showCreateModal ?? false);
        $hasActiveFilters = ($filters['q'] ?? '') !== ''
            || ($filters['date_from'] ?? '') !== ''
            || ($filters['date_to'] ?? '') !== ''
            || ($filters['asistencia_from'] ?? '') !== ''
            || ($filters['asistencia_to'] ?? '') !== ''
            || ($filters['estado_code'] ?? '') !== ''
            || ($filters['commercial_client_id'] ?? '') !== ''
            || ($filters['responsable_user_id'] ?? '') !== ''
            || ($filters['city_code'] ?? '') !== ''
            || ($filters['position_code'] ?? '') !== '';
    @endphp

    <x-slot name="header">
        @include('areas.gestion_humana.seleccion.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div
        class="page-section seleccion-ingresos-page req-manage-page"
        x-data="seleccionIngresos({
            lookupUrl: @js($lookupUrl),
            lookupPerfilUrl: @js($lookupPerfilUrl ?? ''),
            oldEstadoCode: @js(old('estado_code', '')),
            canEdit: @js($canEdit),
            storeUrl: @js($storeUrl),
            oldConfirmDuplicate: @js(old('confirm_duplicate') ? true : false),
            oldDuplicates: @js([]),
        })"
        @seleccion-ingreso-open-edit.window="openEdit($event.detail)"
    >
        <div class="app-container">
            {{-- El éxito (session status) se muestra solo como toast global del layout. --}}
            @if (session('error'))
                <div class="alert alert--danger">{{ session('error') }}</div>
            @endif

            {{-- Errores de servidor al editar (el modal Editar no se reabre tras la redirección) --}}
            @if ($errors->any() && ! $showCreateModal)
                <div class="alert alert--danger">
                    <p class="mb-0"><strong>No se pudo actualizar el ingreso:</strong></p>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
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
                        <form method="GET" action="{{ route('gestion-humana.seleccion.ingresos') }}" class="req-manage-filters">
                            {{-- Filtros en dos filas: 1) búsqueda + fechas + acciones a la derecha; 2) selectores --}}
                            <div class="seleccion-ingresos-filters">
                            <div class="seleccion-ingresos-filters__row seleccion-ingresos-filters__row--primary">
                                <div class="form-field">
                                    <label class="form-label" for="filter_q">Buscar</label>
                                    <input id="filter_q" name="q" type="search" class="form-input" value="{{ $filters['q'] }}" placeholder="Cédula, nombre, correo…">
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_date_from">Ingreso desde</label>
                                    <input id="filter_date_from" name="date_from" type="date" class="form-input" value="{{ $filters['date_from'] }}">
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_date_to">Ingreso hasta</label>
                                    <input id="filter_date_to" name="date_to" type="date" class="form-input" value="{{ $filters['date_to'] }}">
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_asistencia_from">Asistencia desde</label>
                                    <input id="filter_asistencia_from" name="asistencia_from" type="date" class="form-input" value="{{ $filters['asistencia_from'] }}">
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_asistencia_to">Asistencia hasta</label>
                                    <input id="filter_asistencia_to" name="asistencia_to" type="date" class="form-input" value="{{ $filters['asistencia_to'] }}">
                                </div>
                                <div class="form-field cursos-registros-page__filter-actions seleccion-ingresos-filters__actions">
                                    <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Filtrar" aria-label="Filtrar">
                                        <x-lucide-search width="18" height="18" aria-hidden="true" />
                                    </button>
                                    <a href="{{ route('gestion-humana.seleccion.ingresos') }}" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost" title="Limpiar filtros" aria-label="Limpiar filtros">
                                        <x-lucide-x width="18" height="18" aria-hidden="true" />
                                    </a>
                                </div>
                            </div>

                            <div class="seleccion-ingresos-filters__row seleccion-ingresos-filters__row--selects">
                                <div class="form-field">
                                    <label class="form-label" for="filter_estado_code">Estado</label>
                                    <x-searchable-select
                                        id="filter_estado_code"
                                        name="estado_code"
                                        :options="$estadoOptions"
                                        :value="$filters['estado_code']"
                                        placeholder="Todos"
                                        :allow-clear="true"
                                    />
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_commercial_client_id">Cliente</label>
                                    <x-searchable-select
                                        id="filter_commercial_client_id"
                                        name="commercial_client_id"
                                        :options="$clientOptions"
                                        :value="$filters['commercial_client_id']"
                                        placeholder="Todos"
                                        :allow-clear="true"
                                    />
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_responsable_user_id">Responsable</label>
                                    <x-searchable-select
                                        id="filter_responsable_user_id"
                                        name="responsable_user_id"
                                        :options="$responsableOptions"
                                        :value="$filters['responsable_user_id']"
                                        placeholder="Todos"
                                        :allow-clear="true"
                                    />
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_city_code">Ciudad</label>
                                    <x-searchable-select
                                        id="filter_city_code"
                                        name="city_code"
                                        :options="$cityOptions"
                                        :value="$filters['city_code']"
                                        placeholder="Todas"
                                        :allow-clear="true"
                                    />
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_position_code">Cargo</label>
                                    <x-searchable-select
                                        id="filter_position_code"
                                        name="position_code"
                                        :options="$positionOptions"
                                        :value="$filters['position_code']"
                                        placeholder="Todos"
                                        :allow-clear="true"
                                    />
                                </div>
                            </div>
                            </div>
                        </form>
                        </div>
                    </details>

                        <div class="cursos-registros-page__table-toolbar">
                            <p class="req-manage-filters__meta">
                                <strong id="seleccion-ingresos-count">…</strong>
                                <span>registro(s)</span>
                            </p>

                            {{-- Toolbar: export Excel (filtros aplicados) a la izquierda de Nuevo ingreso --}}
                            <div class="cursos-registros-page__table-actions">
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
                                        title="Nuevo ingreso"
                                        aria-label="Nuevo ingreso"
                                        x-on:click.prevent="$dispatch('open-modal', 'seleccion-ingreso-nuevo')"
                                    >
                                        <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                    </button>
                                @endif
                            </div>
                        </div>

                    <div class="data-table-wrap req-manage-shell__table cursos-registros-page__table-wrap data-table-wrap--booting">
                        @include('partials.data-table-loader')
                        <table
                            id="seleccion-ingresos-datatable"
                            class="data-table js-seleccion-ingresos-datatable"
                            data-dt-url="{{ $datatableUrl }}"
                            data-dt-can-edit="{{ $canEdit ? '1' : '0' }}"
                            style="width:100%"
                        >
                            <thead>
                                <tr>
                                    <th>CÉDULA</th>
                                    <th>NOMBRE</th>
                                    <th>CORREO</th>
                                    <th>TELÉFONO</th>
                                    <th>CIUDAD</th>
                                    <th>CARGO</th>
                                    <th>CLIENTE</th>
                                    <th>CAMISA</th>
                                    <th>PANTALÓN</th>
                                    <th>ZAPATOS</th>
                                    <th>FECHA INGRESO</th>
                                    <th>FECHA ASISTENCIA</th>
                                    <th>ESTADO</th>
                                    <th>RH</th>
                                    <th>RESPONSABLE</th>
                                    <th>REFERIDO</th>
                                    <th>JEFE OPERATIVO</th>
                                    <th>OBSERVACIONES</th>
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
                <x-modal name="seleccion-ingreso-nuevo" maxWidth="3xl" :show="$showCreateModal" focusable>
                    <div class="modal-card ficha-empleados-masivos-modal seleccion-ingresos-modal">
                        <div class="ficha-empleados-masivos-modal__header">
                            <div class="ficha-empleados-masivos-modal__heading">
                                <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                                    <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                </span>
                                <div>
                                    <h3 class="ficha-empleados-masivos-modal__title">Nuevo ingreso</h3>
                                    <p class="ficha-empleados-masivos-modal__lead">Registra el ingreso del aspirante a la nómina de seguridad. Todos los campos son obligatorios salvo los marcados como opcionales.</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="ficha-empleados-masivos-modal__close"
                                aria-label="Cerrar"
                                x-on:click="$dispatch('close-modal', 'seleccion-ingreso-nuevo')"
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

                        <form
                            method="POST"
                            action="{{ $storeUrl }}"
                            class="cursos-registros-page__form seleccion-ingresos-modal__form"
                            novalidate
                            x-on:submit="guardSubmit($event, 'create')"
                        >
                            @csrf
                            @include('areas.gestion_humana.seleccion.partials.ingreso-form-fields', [
                                'prefix' => 'create',
                                'cityOptions' => $cityOptions,
                                'positionOptions' => $positionOptions,
                                'bloodTypeOptions' => $bloodTypeOptions,
                                'clientOptions' => $clientOptions,
                                'uniformOptions' => $uniformOptions,
                                'responsableOptions' => $responsableOptions,
                                'estadoOptions' => $estadoOptions,
                                'jefeOperativoOptions' => $jefeOperativoOptions,
                                'values' => [
                                    'document_number' => old('document_number', ''),
                                    'full_name' => old('full_name', ''),
                                    'email' => old('email', ''),
                                    'phone' => old('phone', ''),
                                    'city_code' => old('city_code', ''),
                                    'position_code' => old('position_code', ''),
                                    'commercial_client_id' => old('commercial_client_id', ''),
                                    'shirt_size' => old('shirt_size', ''),
                                    'pants_size' => old('pants_size', ''),
                                    'shoes_size' => old('shoes_size', ''),
                                    'requisition_uniform_id' => old('requisition_uniform_id', ''),
                                    'fecha_ingreso' => old('fecha_ingreso', ''),
                                    'fecha_asistencia' => old('fecha_asistencia', ''),
                                    'estado_code' => old('estado_code', ''),
                                    'blood_type_code' => old('blood_type_code', ''),
                                    'reemplaza_cedula' => old('reemplaza_cedula', ''),
                                    'reemplaza_a' => old('reemplaza_a', ''),
                                    'responsable_user_id' => old('responsable_user_id', ''),
                                    'referido' => old('referido', ''),
                                    'jefe_ope_code' => old('jefe_ope_code', ''),
                                    'observaciones' => old('observaciones', ''),
                                ],
                                'mode' => 'create',
                            ])
                        </form>
                    </div>
                </x-modal>

                <x-modal name="seleccion-ingreso-editar" maxWidth="3xl" focusable>
                    <div class="modal-card ficha-empleados-masivos-modal seleccion-ingresos-modal">
                        <div class="ficha-empleados-masivos-modal__header">
                            <div class="ficha-empleados-masivos-modal__heading">
                                <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                                    <x-lucide-pencil width="18" height="18" aria-hidden="true" />
                                </span>
                                <div>
                                    <h3 class="ficha-empleados-masivos-modal__title">Editar ingreso <span x-text="editForm.id ? '#'+editForm.id : ''"></span></h3>
                                    <p class="ficha-empleados-masivos-modal__lead">Actualiza los datos del ingreso.</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="ficha-empleados-masivos-modal__close"
                                aria-label="Cerrar"
                                x-on:click="$dispatch('close-modal', 'seleccion-ingreso-editar')"
                            >
                                <x-lucide-x width="18" height="18" aria-hidden="true" />
                            </button>
                        </div>

                        <form
                            method="POST"
                            :action="editForm.update_url"
                            class="cursos-registros-page__form seleccion-ingresos-modal__form"
                            novalidate
                            x-on:submit="guardSubmit($event, 'edit')"
                        >
                            @csrf
                            @method('PATCH')
                            @include('areas.gestion_humana.seleccion.partials.ingreso-form-fields', [
                                'prefix' => 'edit',
                                'cityOptions' => $cityOptions,
                                'positionOptions' => $positionOptions,
                                'bloodTypeOptions' => $bloodTypeOptions,
                                'clientOptions' => $clientOptions,
                                'uniformOptions' => $uniformOptions,
                                'responsableOptions' => $responsableOptions,
                                'estadoOptions' => $estadoOptions,
                                'jefeOperativoOptions' => $jefeOperativoOptions,
                                'mode' => 'edit',
                            ])
                        </form>
                    </div>
                </x-modal>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            function seleccionIngresos(config) {
                return {
                    lookupUrl: config.lookupUrl,
                    canEdit: config.canEdit,
                    editForm: {},
                    createDuplicates: Array.isArray(config.oldDuplicates) ? config.oldDuplicates : [],
                    createConfirmDuplicate: Boolean(config.oldConfirmDuplicate),
                    editDuplicates: [],
                    editConfirmDuplicate: false,
                    formErrors: { create: [], edit: [] },
                    createEstadoCode: String(config.oldEstadoCode || ''),

                    init() {
                        // Nuevo (incl. reapertura tras error): ajusta required de los selects según el estado.
                        this.$nextTick(() => this.syncHiringRequired('create'));
                    },
                    lookupPerfilUrl: config.lookupPerfilUrl || '',
                    reemplazoLookup: {
                        create: { loading: false, found: false, message: '' },
                        edit: { loading: false, found: false, message: '' },
                    },

                    openEdit(row) {
                        this.editForm = Object.assign({}, row);
                        this.editDuplicates = [];
                        this.editConfirmDuplicate = false;
                        this.formErrors.edit = [];
                        this.reemplazoLookup.edit = { loading: false, found: false, message: '' };
                        this.$dispatch('open-modal', 'seleccion-ingreso-editar');
                        this.$nextTick(() => {
                            setTimeout(() => {
                                this.setSearchableValue('edit_city_code', row.city_code);
                                this.setSearchableValue('edit_position_code', row.position_code);
                                this.setSearchableValue('edit_commercial_client_id', row.commercial_client_id);
                                this.setSearchableValue('edit_requisition_uniform_id', row.requisition_uniform_id);
                                this.setSearchableValue('edit_blood_type_code', row.blood_type_code);
                                this.setSearchableValue('edit_responsable_user_id', row.responsable_user_id);
                                this.setSearchableValue('edit_estado_code', row.estado_code);
                                this.setSearchableValue('edit_jefe_ope_code', row.jefe_ope_code);
                                this.syncHiringRequired('edit');
                            }, 150);
                        });
                        this.lookupDuplicates(row.document_number || '', 'edit');
                    },

                    // Cliente y dotación, RH y fecha de ingreso solo obligatorios con estado CONTRATADO (Nuevo y Editar).
                    isContratado(code) {
                        return String(code || '').trim().toUpperCase() === @js(\App\Models\SeleccionIngreso::ESTADO_CONTRATADO);
                    },

                    get createRequiresHiringData() {
                        return this.isContratado(this.createEstadoCode);
                    },

                    get editRequiresHiringData() {
                        return this.isContratado(this.editForm.estado_code);
                    },

                    onEstadoChange(event, mode) {
                        const value = String(event?.detail?.value ?? event?.target?.value ?? '');
                        if (mode === 'edit') {
                            this.editForm.estado_code = value;
                        } else {
                            this.createEstadoCode = value;
                        }
                        this.syncHiringRequired(mode);
                    },

                    // Los searchable-select no reciben x-bind:required: se ajusta su estado interno.
                    syncHiringRequired(mode = 'edit') {
                        const required = mode === 'edit' ? this.editRequiresHiringData : this.createRequiresHiringData;
                        ['commercial_client_id', 'requisition_uniform_id', 'blood_type_code'].forEach((field) => {
                            const input = document.getElementById(mode + '_' + field);
                            const wrap = input ? input.closest('[x-data]') : null;
                            if (wrap && window.Alpine) {
                                window.Alpine.$data(wrap).required = required;
                            }
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

                    // Cédula de reemplazo: busca el perfil en Ficha y, si existe, llena «Reemplaza a».
                    // Sin coincidencia solo avisa; ambos campos siguen editables a mano.
                    async lookupReemplazo(cedula, mode) {
                        const value = String(cedula || '').trim();
                        const state = this.reemplazoLookup[mode];
                        if (! state) {
                            return;
                        }
                        if (! value || ! this.lookupPerfilUrl) {
                            this.reemplazoLookup[mode] = { loading: false, found: false, message: '' };
                            return;
                        }

                        this.reemplazoLookup[mode] = { loading: true, found: false, message: 'Buscando en Ficha…' };

                        try {
                            const res = await fetch(this.lookupPerfilUrl + '?' + new URLSearchParams({ cedula: value }).toString(), {
                                headers: { 'Accept': 'application/json' },
                            });
                            const data = res.ok ? await res.json() : {};

                            if (data.found && data.full_name) {
                                if (mode === 'edit') {
                                    this.editForm.reemplaza_a = data.full_name;
                                } else {
                                    const input = document.getElementById('create_reemplaza_a');
                                    if (input) {
                                        input.value = data.full_name;
                                    }
                                }
                                this.reemplazoLookup[mode] = { loading: false, found: true, message: 'Perfil encontrado: ' + data.full_name };
                            } else {
                                this.reemplazoLookup[mode] = {
                                    loading: false,
                                    found: false,
                                    message: data.message || 'No se encontró un perfil con esa cédula. Escriba el nombre manualmente.',
                                };
                            }
                        } catch (e) {
                            this.reemplazoLookup[mode] = { loading: false, found: false, message: 'No se pudo consultar Ficha. Escriba el nombre manualmente.' };
                        }
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

                    // Validación visible: los forms son novalidate porque los searchable-select usan un input
                    // oculto requerido y el navegador bloqueaba el envío sin mostrar ningún mensaje.
                    validateForm(form, mode) {
                        form.querySelectorAll('.seleccion-ingresos-form__field--invalid')
                            .forEach((el) => el.classList.remove('seleccion-ingresos-form__field--invalid'));

                        const messages = [];
                        let first = null;
                        form.querySelectorAll('input, select, textarea').forEach((el) => {
                            if (el.disabled || el.type === 'hidden' || el.checkValidity()) {
                                return;
                            }
                            const field = el.closest('.form-field');
                            const label = (field?.querySelector('.form-label')?.textContent || el.name || 'Campo')
                                .replace('(opcional)', '')
                                .replace(/\s+/g, ' ')
                                .trim();
                            const reason = el.validity.valueMissing
                                ? 'es obligatorio'
                                : (el.validity.typeMismatch || el.validity.patternMismatch ? 'formato no válido' : el.validationMessage);
                            const text = label + ': ' + reason;
                            if (! messages.includes(text)) {
                                messages.push(text);
                            }
                            field?.classList.add('seleccion-ingresos-form__field--invalid');
                            first = first || field;
                        });

                        this.formErrors[mode] = messages;
                        if (first) {
                            first.scrollIntoView({ block: 'center', behavior: 'smooth' });
                        }

                        return messages.length === 0;
                    },

                    guardSubmit(event, mode) {
                        if (! this.validateForm(event.target, mode)) {
                            event.preventDefault();
                            return false;
                        }

                        const duplicates = mode === 'edit' ? this.editDuplicates : this.createDuplicates;
                        const confirmed = mode === 'edit' ? this.editConfirmDuplicate : this.createConfirmDuplicate;
                        if (duplicates.length > 0 && ! confirmed) {
                            event.preventDefault();
                            alert('Ya existen ingresos con esta cédula. Marque la confirmación para continuar.');
                            return false;
                        }
                        return true;
                    },
                };
            }

            document.addEventListener('DOMContentLoaded', function () {
                const $ = window.jQuery;
                if (! $ || ! $.fn.DataTable) {
                    return;
                }

                const $table = $('.js-seleccion-ingresos-datatable');
                if (! $table.length) {
                    return;
                }

                const canEdit = $table.data('dt-can-edit') === 1 || $table.data('dt-can-edit') === '1';
                const actionsIndex = canEdit ? 18 : -1;

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
                    order: [[10, 'desc']],
                    columnDefs: actionsIndex >= 0
                        ? [{ targets: [actionsIndex], orderable: false, searchable: false }]
                        : [],
                });

                api.on('xhr.dt', function (_event, _settings, json) {
                    $table.closest('.data-table-wrap').removeClass('data-table-wrap--booting');
                    const el = document.getElementById('seleccion-ingresos-count');
                    if (el && json && typeof json.recordsFiltered !== 'undefined') {
                        el.textContent = json.recordsFiltered;
                    }
                });

                $table.on('click', '.js-seleccion-ingreso-edit', function () {
                    try {
                        const row = JSON.parse(this.getAttribute('data-ingreso-edit') || '{}');
                        window.dispatchEvent(new CustomEvent('seleccion-ingreso-open-edit', { detail: row }));
                    } catch (e) {}
                });
            });
        </script>
    @endpush
</x-app-layout>
