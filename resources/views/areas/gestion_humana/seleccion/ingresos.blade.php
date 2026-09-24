<x-app-layout>
    @php
        $showCreateModal = (bool) ($showCreateModal ?? false);
    @endphp

    <x-slot name="header">
        @include('areas.gestion_humana.seleccion.partials.subnav', ['subTabs' => $subTabs])
        <div class="app-container">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">Ingreso</h2>
                <p class="panel-text">Gestion humana — registros de ingreso</p>
            </div>
        </div>
    </x-slot>

    <div
        class="page-section seleccion-ingresos-page req-manage-page"
        x-data="seleccionIngresos({
            lookupUrl: @js($lookupUrl),
            canEdit: @js($canEdit),
            storeUrl: @js($storeUrl),
            oldConfirmDuplicate: @js(old('confirm_duplicate') ? true : false),
            oldDuplicates: @js([]),
        })"
        @seleccion-ingreso-open-edit.window="openEdit($event.detail)"
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
                    <div class="req-manage-shell__filters">
                        <form method="GET" action="{{ route('gestion-humana.seleccion.ingresos') }}" class="req-manage-filters">
                            <div class="cursos-registros-page__filters">
                                <div class="form-field">
                                    <label class="form-label" for="filter_q">Buscar</label>
                                    <input id="filter_q" name="q" type="search" class="form-input" value="{{ $filters['q'] }}" placeholder="Cédula, nombre, correo…">
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_date_from">Fecha desde</label>
                                    <input id="filter_date_from" name="date_from" type="date" class="form-input" value="{{ $filters['date_from'] }}">
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_date_to">Fecha hasta</label>
                                    <input id="filter_date_to" name="date_to" type="date" class="form-input" value="{{ $filters['date_to'] }}">
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
                                <div class="form-field cursos-registros-page__filter-actions">
                                    <button type="submit" class="btn btn--primary btn--sm">Filtrar</button>
                                    <a href="{{ route('gestion-humana.seleccion.ingresos') }}" class="btn btn--secondary btn--sm">Limpiar</a>
                                    <x-export-excel
                                        route="{{ $exportUrl }}"
                                        label=""
                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    />
                                </div>
                            </div>
                        </form>

                        <div class="cursos-registros-page__table-toolbar">
                            <p class="req-manage-filters__meta">
                                <strong id="seleccion-ingresos-count">…</strong>
                                <span>registro(s)</span>
                            </p>

                            @if ($canEdit)
                                <div class="cursos-registros-page__table-actions">
                                    <button
                                        type="button"
                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                        title="Nuevo ingreso"
                                        aria-label="Nuevo ingreso"
                                        x-on:click.prevent="$dispatch('open-modal', 'seleccion-ingreso-nuevo')"
                                    >
                                        <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                    </button>
                                </div>
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
                                    <th>CEDULA</th>
                                    <th>NOMBRE</th>
                                    <th>CORREO</th>
                                    <th>TELEFONO</th>
                                    <th>CIUDAD</th>
                                    <th>CARGO</th>
                                    <th>CLIENTE</th>
                                    <th>CAMISA</th>
                                    <th>PANTALON</th>
                                    <th>ZAPATOS</th>
                                    <th>FECHA INGRESO</th>
                                    <th>RH</th>
                                    <th>RESPONSABLE</th>
                                    <th>REFERIDO</th>
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
                <x-modal name="seleccion-ingreso-nuevo" maxWidth="2xl" :show="$showCreateModal" focusable>
                    <div class="modal-card ficha-empleados-masivos-modal">
                        <div class="ficha-empleados-masivos-modal__header">
                            <div class="ficha-empleados-masivos-modal__heading">
                                <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                                    <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                </span>
                                <div>
                                    <h3 class="ficha-empleados-masivos-modal__title">Nuevo ingreso</h3>
                                    <p class="ficha-empleados-masivos-modal__lead">Todos los campos son obligatorios.</p>
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
                            class="cursos-registros-page__form"
                            x-on:submit="return guardSubmit($event, 'create')"
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
                                    'blood_type_code' => old('blood_type_code', ''),
                                    'reemplaza_a' => old('reemplaza_a', ''),
                                    'responsable_user_id' => old('responsable_user_id', ''),
                                    'referido' => old('referido', ''),
                                    'jefe_ope' => old('jefe_ope', ''),
                                ],
                                'mode' => 'create',
                            ])
                        </form>
                    </div>
                </x-modal>

                <div
                    class="cursos-registros-page__modal"
                    x-show="editOpen"
                    x-cloak
                    @keydown.escape.window="editOpen = false"
                >
                    <div class="cursos-registros-page__modal-backdrop" @click="editOpen = false"></div>
                    <div class="cursos-registros-page__modal-panel panel" role="dialog" aria-modal="true" style="max-width: 56rem;">
                        <div class="panel__header panel-heading-row">
                            <h3 class="panel-title">Editar ingreso</h3>
                            <button type="button" class="btn btn--ghost btn--sm" @click="editOpen = false">Cerrar</button>
                        </div>
                        <div class="panel__body">
                            <form
                                method="POST"
                                :action="editForm.update_url"
                                class="cursos-registros-page__form"
                                x-on:submit="return guardSubmit($event, 'edit')"
                            >
                                @csrf
                                @method('PATCH')
                                <div class="cursos-registros-page__form-grid">
                                    <div class="form-field">
                                        <label class="form-label" for="edit_document_number">CEDULA</label>
                                        <input id="edit_document_number" name="document_number" type="text" class="form-input" maxlength="50" required
                                            x-model="editForm.document_number"
                                            @blur="lookupDuplicates($event.target.value, 'edit')">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_full_name">APELLIDOS Y NOMBRE</label>
                                        <input id="edit_full_name" name="full_name" type="text" class="form-input" maxlength="255" required x-model="editForm.full_name">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_email">CORREO</label>
                                        <input id="edit_email" name="email" type="email" class="form-input" maxlength="150" required x-model="editForm.email">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_phone">TELEFONO</label>
                                        <input id="edit_phone" name="phone" type="text" class="form-input" maxlength="40" required x-model="editForm.phone">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_city_code">CIUDAD</label>
                                        <x-searchable-select
                                            id="edit_city_code"
                                            name="city_code"
                                            :options="$cityOptions"
                                            value=""
                                            placeholder="Seleccionar…"
                                            :required="true"
                                            :allow-clear="false"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_position_code">CARGO</label>
                                        <x-searchable-select
                                            id="edit_position_code"
                                            name="position_code"
                                            :options="$positionOptions"
                                            value=""
                                            placeholder="Seleccionar…"
                                            :required="true"
                                            :allow-clear="false"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_commercial_client_id">CLIENTE</label>
                                        <x-searchable-select
                                            id="edit_commercial_client_id"
                                            name="commercial_client_id"
                                            :options="$clientOptions"
                                            value=""
                                            placeholder="Seleccionar…"
                                            :required="true"
                                            :allow-clear="false"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_shirt_size">TALLA CAMISA</label>
                                        <input id="edit_shirt_size" name="shirt_size" type="text" class="form-input" maxlength="40" required x-model="editForm.shirt_size">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_pants_size">TALLA PANTALÓN</label>
                                        <input id="edit_pants_size" name="pants_size" type="text" class="form-input" maxlength="40" required x-model="editForm.pants_size">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_shoes_size">TALLA ZAPATOS</label>
                                        <input id="edit_shoes_size" name="shoes_size" type="text" class="form-input" maxlength="40" required x-model="editForm.shoes_size">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_requisition_uniform_id">TIPO DOTACION</label>
                                        <x-searchable-select
                                            id="edit_requisition_uniform_id"
                                            name="requisition_uniform_id"
                                            :options="$uniformOptions"
                                            value=""
                                            placeholder="Seleccionar…"
                                            :required="true"
                                            :allow-clear="false"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_fecha_ingreso">FECHA DE INGRESO</label>
                                        <input id="edit_fecha_ingreso" name="fecha_ingreso" type="date" class="form-input" required x-model="editForm.fecha_ingreso">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_blood_type_code">RH</label>
                                        <x-searchable-select
                                            id="edit_blood_type_code"
                                            name="blood_type_code"
                                            :options="$bloodTypeOptions"
                                            value=""
                                            placeholder="Seleccionar…"
                                            :required="true"
                                            :allow-clear="false"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_reemplaza_a">REEMPLAZA A</label>
                                        <input id="edit_reemplaza_a" name="reemplaza_a" type="text" class="form-input" maxlength="255" required x-model="editForm.reemplaza_a">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_responsable_user_id">RESPONSABLE</label>
                                        <x-searchable-select
                                            id="edit_responsable_user_id"
                                            name="responsable_user_id"
                                            :options="$responsableOptions"
                                            value=""
                                            placeholder="Seleccionar…"
                                            :required="true"
                                            :allow-clear="false"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_referido">REFERIDO</label>
                                        <input id="edit_referido" name="referido" type="text" class="form-input" maxlength="255" required x-model="editForm.referido">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_jefe_ope">JEFE OPE ASIGNADO</label>
                                        <input id="edit_jefe_ope" name="jefe_ope" type="text" class="form-input" maxlength="255" required x-model="editForm.jefe_ope">
                                    </div>
                                </div>

                                <div class="alert alert--warning" x-show="editDuplicates.length > 0" x-cloak>
                                    <p class="mb-2">Ya existen ingresos con esta cédula:</p>
                                    <ul class="mb-2">
                                        <template x-for="row in editDuplicates" :key="row.id">
                                            <li>
                                                #<span x-text="row.id"></span>
                                                — <span x-text="row.full_name"></span>
                                                (<span x-text="row.fecha_ingreso || 's/f'"></span>)
                                            </li>
                                        </template>
                                    </ul>
                                    <label class="cursos-registros-page__checkbox-label">
                                        <input type="checkbox" name="confirm_duplicate" value="1" x-model="editConfirmDuplicate">
                                        Confirmo guardar de todos modos
                                    </label>
                                </div>

                                <div class="cursos-registros-page__form-actions">
                                    <button type="submit" class="btn btn--primary">Actualizar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            function seleccionIngresos(config) {
                return {
                    lookupUrl: config.lookupUrl,
                    canEdit: config.canEdit,
                    editOpen: false,
                    editForm: {},
                    createDuplicates: Array.isArray(config.oldDuplicates) ? config.oldDuplicates : [],
                    createConfirmDuplicate: Boolean(config.oldConfirmDuplicate),
                    editDuplicates: [],
                    editConfirmDuplicate: false,

                    openEdit(row) {
                        this.editForm = Object.assign({}, row);
                        this.editDuplicates = [];
                        this.editConfirmDuplicate = false;
                        this.editOpen = true;
                        this.$nextTick(() => {
                            this.setSearchableValue('edit_city_code', row.city_code);
                            this.setSearchableValue('edit_position_code', row.position_code);
                            this.setSearchableValue('edit_commercial_client_id', row.commercial_client_id);
                            this.setSearchableValue('edit_requisition_uniform_id', row.requisition_uniform_id);
                            this.setSearchableValue('edit_blood_type_code', row.blood_type_code);
                            this.setSearchableValue('edit_responsable_user_id', row.responsable_user_id);
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
                const actionsIndex = canEdit ? 14 : -1;

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
