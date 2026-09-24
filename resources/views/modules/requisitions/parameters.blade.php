<x-app-layout>
    <x-slot name="header">
        @include('modules.requisitions.partials.subnav', ['moduleLabel' => $moduleLabel, 'subTabs' => $subTabs])
    </x-slot>

    <div class="page-section ficha-empleados-catalogs-page requisition-parameters-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success ficha-empleados-catalogs-page__alert">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert--danger ficha-empleados-catalogs-page__alert">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert--danger ficha-empleados-catalogs-page__alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div id="parameter-selector-screen">
                <div class="page-header-inner ficha-empleados-catalogs-page__head">
                    <h2 class="page-title">Parámetros</h2>
                    <p class="page-subtitle">Seleccione una categoría para gestionar los valores de formularios de requisiciones.</p>
                </div>

                <div class="ficha-empleados-catalogs-page__grid">
                    @foreach ($catalogs as $catalog)
                        <button
                            type="button"
                            class="ficha-empleados-catalogs-page__card"
                            onclick="showParameterSection('{{ $catalog['key'] }}')"
                        >
                            <span class="ficha-empleados-catalogs-page__card-icon" aria-hidden="true">
                                <x-lucide-settings-2 width="22" height="22" />
                            </span>
                            <span class="ficha-empleados-catalogs-page__card-title">{{ $catalog['label'] }}</span>
                            <span class="ficha-empleados-catalogs-page__card-count">{{ count($catalog['items']) }} registrados</span>
                        </button>
                    @endforeach

                    @if ($showSelectionOfficers ?? false)
                        <button
                            type="button"
                            class="ficha-empleados-catalogs-page__card"
                            onclick="showParameterSection('selection-officers')"
                        >
                            <span class="ficha-empleados-catalogs-page__card-icon" aria-hidden="true">
                                <x-lucide-users width="22" height="22" />
                            </span>
                            <span class="ficha-empleados-catalogs-page__card-title">Encargados de selección</span>
                            <span class="ficha-empleados-catalogs-page__card-count">{{ count($gestionHumanaUsers ?? []) }} usuarios GH</span>
                        </button>
                    @endif
                </div>
            </div>

            <div id="parameter-management-screen" class="ficha-empleados-catalogs-page__manage" hidden>
                <button type="button" class="ficha-empleados-catalogs-page__back" onclick="showSelectorScreen()">
                    <x-lucide-arrow-left width="18" height="18" aria-hidden="true" />
                    Volver al tablero
                </button>

                @foreach ($catalogs as $catalog)
                    <section id="section-{{ $catalog['key'] }}" class="parameter-section ficha-empleados-catalogs-page__section" hidden>
                        <div class="panel">
                            <div class="panel__header panel__header--compact">
                                <h3 class="panel-title">{{ $catalog['label'] }}</h3>
                            </div>

                            <div class="panel__body section-stack">
                                <form
                                    method="POST"
                                    action="{{ route('requisitions.parameters.store', ['module' => $moduleKey, 'type' => $catalog['key']]) }}"
                                    class="ficha-empleados-catalogs-page__create-form"
                                >
                                    @csrf
                                    <div class="ficha-empleados-catalogs-page__create-row">
                                        <div class="form-field ficha-empleados-catalogs-page__name-field">
                                            <label class="form-label" for="name_{{ $catalog['key'] }}">
                                                {{ $catalog['key'] === 'emails' ? 'Nuevo correo' : 'Nuevo valor' }}
                                            </label>
                                            <input
                                                id="name_{{ $catalog['key'] }}"
                                                name="name"
                                                type="{{ $catalog['key'] === 'emails' ? 'email' : 'text' }}"
                                                class="form-input"
                                                placeholder="{{ $catalog['key'] === 'emails' ? 'ej. notificaciones@empresa.com' : 'Nombre…' }}"
                                                required
                                            >
                                        </div>
                                        <div class="ficha-empleados-catalogs-page__create-actions">
                                            <label class="ficha-empleados-catalogs-page__active-check">
                                                <input type="checkbox" name="is_active" value="1" class="form-check" checked>
                                                <span>Activo</span>
                                            </label>
                                            <button
                                                type="submit"
                                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                                title="Agregar"
                                                aria-label="Agregar"
                                            >
                                                <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                            </button>
                                        </div>
                                    </div>
                                </form>

                                <div class="data-table-wrap">
                                    <table class="data-table js-datatable" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>Nombre</th>
                                                <th class="requisition-parameters-page__col-status">Estado</th>
                                                <th class="requisition-parameters-page__col-actions">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($catalog['items'] as $item)
                                                <tr>
                                                    <td>{{ $item->name }}</td>
                                                    <td>
                                                        <span class="status-pill {{ $item->is_active ? 'status-pill--success' : 'status-pill--muted' }}">
                                                            {{ $item->is_active ? 'Activo' : 'Inactivo' }}
                                                        </span>
                                                    </td>
                                                    <td class="table-actions">
                                                        <div class="cursos-catalogo-page__row-actions">
                                                            <button
                                                                type="button"
                                                                class="cursos-catalogo-page__icon-btn btn-param-edit"
                                                                title="Editar"
                                                                aria-label="Editar"
                                                                data-type="{{ $catalog['key'] }}"
                                                                data-id="{{ $item->id }}"
                                                                data-name="{{ $item->name }}"
                                                                data-active="{{ $item->is_active ? '1' : '0' }}"
                                                                data-label="{{ $catalog['label'] }}"
                                                                data-update-url="{{ route('requisitions.parameters.update', ['module' => $moduleKey, 'type' => $catalog['key'], 'parameterId' => $item->id]) }}"
                                                            >
                                                                <x-lucide-pencil width="16" height="16" aria-hidden="true" />
                                                            </button>
                                                            <form
                                                                method="POST"
                                                                action="{{ route('requisitions.parameters.destroy', ['module' => $moduleKey, 'type' => $catalog['key'], 'parameterId' => $item->id]) }}"
                                                                class="cursos-catalogo-page__delete-form"
                                                                onsubmit="return confirm('¿Eliminar este parámetro?')"
                                                            >
                                                                @csrf
                                                                @method('DELETE')
                                                                <button
                                                                    type="submit"
                                                                    class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger"
                                                                    title="Eliminar"
                                                                    aria-label="Eliminar"
                                                                >
                                                                    <x-lucide-trash-2 width="16" height="16" aria-hidden="true" />
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-muted">Sin registros en este catálogo.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </section>
                @endforeach

                @if ($showSelectionOfficers ?? false)
                    <section id="section-selection-officers" class="parameter-section ficha-empleados-catalogs-page__section" hidden>
                        @include('modules.requisitions.partials.selection-officers', [
                            'moduleKey' => $moduleKey,
                            'gestionHumanaUsers' => $gestionHumanaUsers,
                            'selectionOfficerAccess' => $selectionOfficerAccess,
                        ])
                    </section>
                @endif
            </div>
        </div>
    </div>

    <div id="param-modal" class="ficha-empleados-catalogs-page__modal" hidden>
        <div class="ficha-empleados-catalogs-page__modal-backdrop" onclick="closeParamModal()"></div>
        <div class="panel ficha-empleados-catalogs-page__modal-card">
            <div class="panel__header panel-heading-row">
                <h3 class="panel-title" id="param-modal-title">Editar parámetro</h3>
                <button
                    type="button"
                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                    onclick="closeParamModal()"
                    title="Cerrar"
                    aria-label="Cerrar"
                >
                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                </button>
            </div>
            <form method="POST" id="param-edit-form" class="panel__body form-stack">
                @csrf
                @method('PATCH')
                <div class="form-field">
                    <label class="form-label" for="edit-param-name" id="edit-param-name-label">Nombre</label>
                    <input id="edit-param-name" name="name" type="text" class="form-input" required autocomplete="off">
                </div>
                <label class="checkbox-card">
                    <input type="checkbox" id="edit-param-active" name="is_active" value="1" class="form-check">
                    <span>
                        <span class="checkbox-card__title">Activo para formularios</span>
                    </span>
                </label>
                <div class="form-actions">
                    <button type="button" class="btn btn--secondary" onclick="closeParamModal()">Cancelar</button>
                    <button type="submit" class="btn btn--primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function catalogQueryKey() {
        return new URLSearchParams(window.location.search).get('catalog');
    }

    function setCatalogQuery(key) {
        const url = new URL(window.location.href);
        if (key) {
            url.searchParams.set('catalog', key);
        } else {
            url.searchParams.delete('catalog');
        }
        url.hash = '';
        window.history.replaceState({}, '', url.toString());
    }

    function showParameterSection(key) {
        document.getElementById('parameter-selector-screen').hidden = true;
        const manage = document.getElementById('parameter-management-screen');
        manage.hidden = false;

        document.querySelectorAll('.parameter-section').forEach(function (s) {
            s.hidden = true;
            s.style.display = 'none';
        });

        const section = document.getElementById('section-' + key);
        if (section) {
            section.hidden = false;
            section.style.display = 'block';
        }

        setCatalogQuery(key);
        window.scrollTo(0, 0);
    }

    function showSelectorScreen() {
        document.getElementById('parameter-selector-screen').hidden = false;
        document.getElementById('parameter-management-screen').hidden = true;
        document.querySelectorAll('.parameter-section').forEach(function (s) {
            s.hidden = true;
            s.style.display = 'none';
        });
        setCatalogQuery(null);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const openCatalog = catalogQueryKey();
        if (openCatalog && document.getElementById('section-' + openCatalog)) {
            showParameterSection(openCatalog);
        }

        const modal = document.getElementById('param-modal');
        const form = document.getElementById('param-edit-form');
        const mTitle = document.getElementById('param-modal-title');
        const mName = document.getElementById('edit-param-name');
        const mActive = document.getElementById('edit-param-active');
        const mNameLabel = document.getElementById('edit-param-name-label');

        document.querySelectorAll('.btn-param-edit').forEach(function (button) {
            button.addEventListener('click', function () {
                const type = button.getAttribute('data-type') || '';
                const isEmail = type === 'emails';
                mTitle.textContent = 'Editar: ' + (button.getAttribute('data-label') || '');
                mName.value = button.getAttribute('data-name') || '';
                mName.setAttribute('type', isEmail ? 'email' : 'text');
                mName.setAttribute('placeholder', isEmail ? 'ej. notificaciones@empresa.com' : '');
                mNameLabel.textContent = isEmail ? 'Correo' : 'Nombre';
                mActive.checked = button.getAttribute('data-active') === '1';
                form.action = button.getAttribute('data-update-url') || '';
                modal.hidden = false;
                mName.focus();
            });
        });

        window.closeParamModal = function () {
            modal.hidden = true;
        };

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeParamModal();
            }
        });
    });
    </script>
</x-app-layout>
