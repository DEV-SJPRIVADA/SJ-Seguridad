<x-app-layout>
    <x-slot name="header">
        @include('areas.calidad.servicio_cliente.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Catálogos FO-GI-22: mismo tablero de tarjetas y tabla que Parámetros de requisiciones. --}}
    <div class="page-section ficha-empleados-catalogs-page requisition-parameters-page servicio-cliente-catalogs-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success ficha-empleados-catalogs-page__alert">{{ session('status') }}</div>
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

            <div id="servicio-cliente-catalog-selector">
                <div class="page-header-inner ficha-empleados-catalogs-page__head">
                    <h2 class="page-title">Catálogos</h2>
                    <p class="page-subtitle">Seleccione un catálogo. En el selector del caso se muestra el nombre.</p>
                </div>

                <div class="ficha-empleados-catalogs-page__grid">
                    @foreach ($catalogs as $type => $items)
                        <button
                            type="button"
                            class="ficha-empleados-catalogs-page__card"
                            onclick="showServicioClienteCatalog('{{ $type }}')"
                        >
                            <span class="ficha-empleados-catalogs-page__card-icon" aria-hidden="true">
                                <x-lucide-settings-2 width="22" height="22" />
                            </span>
                            <span class="ficha-empleados-catalogs-page__card-title">{{ $labels[$type] }}</span>
                            <span class="ficha-empleados-catalogs-page__card-count">{{ $items->count() }} registrados</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div id="servicio-cliente-catalog-manage" class="ficha-empleados-catalogs-page__manage" hidden>
                <button type="button" class="ficha-empleados-catalogs-page__back" onclick="showServicioClienteCatalogSelector()">
                    <x-lucide-arrow-left width="18" height="18" aria-hidden="true" />
                    Volver al tablero
                </button>

                @foreach ($catalogs as $type => $items)
                    <section id="section-{{ $type }}" class="servicio-cliente-catalog-section ficha-empleados-catalogs-page__section" hidden>
                        <div class="panel">
                            <div class="panel__header panel__header--compact">
                                <h3 class="panel-title">{{ $labels[$type] }}</h3>
                            </div>
                            <div class="panel__body section-stack">
                                <form
                                    method="POST"
                                    action="{{ route('calidad.servicio-cliente.catalogos.store', $type) }}"
                                    class="ficha-empleados-catalogs-page__create-form"
                                >
                                    @csrf
                                    <div class="ficha-empleados-catalogs-page__create-row">
                                        <div class="form-field ficha-empleados-catalogs-page__name-field">
                                            <label class="form-label" for="name_{{ $type }}">Nuevo valor</label>
                                            <input
                                                id="name_{{ $type }}"
                                                name="name"
                                                type="text"
                                                class="form-input"
                                                placeholder="Nombre…"
                                                maxlength="150"
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
                                    <table class="data-table js-datatable" data-dt-responsive="false" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th class="servicio-cliente-catalogs-page__col-id">Id</th>
                                                <th class="servicio-cliente-catalogs-page__col-name">Nombre</th>
                                                <th class="requisition-parameters-page__col-status">Estado</th>
                                                <th class="requisition-parameters-page__col-actions">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($items as $item)
                                                <tr>
                                                    <td>{{ $item->id }}</td>
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
                                                                class="cursos-catalogo-page__icon-btn btn-servicio-cliente-catalog-edit"
                                                                title="Editar"
                                                                aria-label="Editar"
                                                                data-name="{{ $item->name }}"
                                                                data-active="{{ $item->is_active ? '1' : '0' }}"
                                                                data-label="{{ $labels[$type] }}"
                                                                data-update-url="{{ route('calidad.servicio-cliente.catalogos.update', [$type, $item]) }}"
                                                            >
                                                                <x-lucide-pencil width="16" height="16" aria-hidden="true" />
                                                            </button>
                                                            <form
                                                                method="POST"
                                                                action="{{ route('calidad.servicio-cliente.catalogos.destroy', [$type, $item]) }}"
                                                                class="cursos-catalogo-page__delete-form"
                                                                onsubmit="return confirm('¿Eliminar este valor del catálogo?');"
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
                                                    <td colspan="4" class="text-muted">Sin registros en este catálogo.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </div>

    <div id="servicio-cliente-catalog-modal" class="ficha-empleados-catalogs-page__modal" hidden>
        <div class="ficha-empleados-catalogs-page__modal-backdrop" onclick="closeServicioClienteCatalogModal()"></div>
        <div
            class="panel ficha-empleados-catalogs-page__modal-card"
            role="dialog"
            aria-modal="true"
            aria-labelledby="servicio-cliente-catalog-modal-title"
        >
            <div class="ficha-empleados-catalogs-page__modal-header">
                <div class="ficha-empleados-catalogs-page__modal-heading">
                    <span class="ficha-empleados-catalogs-page__modal-heading-icon" aria-hidden="true">
                        <x-lucide-pencil width="20" height="20" />
                    </span>
                    <div>
                        <h3 class="ficha-empleados-catalogs-page__modal-title" id="servicio-cliente-catalog-modal-title">Editar catálogo</h3>
                        <p class="ficha-empleados-catalogs-page__modal-lead">Este nombre es el que aparece en el selector del caso.</p>
                    </div>
                </div>
                <button
                    type="button"
                    class="ficha-empleados-catalogs-page__modal-close"
                    onclick="closeServicioClienteCatalogModal()"
                    title="Cerrar"
                    aria-label="Cerrar"
                >
                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                </button>
            </div>
            <form method="POST" id="servicio-cliente-catalog-edit-form" class="ficha-empleados-catalogs-page__modal-body">
                @csrf
                @method('PATCH')
                <div class="ficha-empleados-catalogs-page__modal-fields">
                    <div class="form-field">
                        <label class="form-label" for="servicio-cliente-catalog-edit-name">Nombre</label>
                        <input id="servicio-cliente-catalog-edit-name" name="name" type="text" class="form-input" maxlength="150" required autocomplete="off">
                    </div>
                    <label class="ficha-empleados-catalogs-page__modal-active" for="servicio-cliente-catalog-edit-active">
                        <input type="checkbox" id="servicio-cliente-catalog-edit-active" name="is_active" value="1" class="form-check">
                        <span>
                            <span class="ficha-empleados-catalogs-page__modal-active-title">Activo para formularios</span>
                            <span class="ficha-empleados-catalogs-page__modal-active-text">Si está inactivo no aparece en los selectores.</span>
                        </span>
                    </label>
                </div>
                <div class="ficha-empleados-catalogs-page__modal-actions">
                    <button
                        type="button"
                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                        onclick="closeServicioClienteCatalogModal()"
                        title="Cancelar"
                        aria-label="Cancelar"
                    >
                        <x-lucide-x width="18" height="18" aria-hidden="true" />
                    </button>
                    <button
                        type="submit"
                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                        title="Guardar cambios"
                        aria-label="Guardar cambios"
                    >
                        <x-lucide-save width="18" height="18" aria-hidden="true" />
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function servicioClienteCatalogQuery() {
            return new URLSearchParams(window.location.search).get('catalog');
        }

        function setServicioClienteCatalogQuery(key) {
            const url = new URL(window.location.href);
            if (key) {
                url.searchParams.set('catalog', key);
            } else {
                url.searchParams.delete('catalog');
            }
            window.history.replaceState({}, '', url.toString());
        }

        function showServicioClienteCatalog(key) {
            document.getElementById('servicio-cliente-catalog-selector').hidden = true;
            document.getElementById('servicio-cliente-catalog-manage').hidden = false;

            document.querySelectorAll('.servicio-cliente-catalog-section').forEach(function (section) {
                section.hidden = true;
                section.style.display = 'none';
            });

            const section = document.getElementById('section-' + key);
            if (section) {
                section.hidden = false;
                section.style.display = 'block';
            }

            setServicioClienteCatalogQuery(key);
            window.scrollTo(0, 0);
            adjustServicioClienteCatalogTable(section);
        }

        function adjustServicioClienteCatalogTable(section) {
            if (!section || !window.jQuery || !jQuery.fn.DataTable) {
                return;
            }

            section.querySelectorAll('table.js-datatable').forEach(function (table) {
                if (!jQuery.fn.DataTable.isDataTable(table)) {
                    return;
                }

                const api = jQuery(table).DataTable();
                jQuery(table).css('width', '100%');
                api.columns.adjust();
            });
        }

        function showServicioClienteCatalogSelector() {
            document.getElementById('servicio-cliente-catalog-selector').hidden = false;
            document.getElementById('servicio-cliente-catalog-manage').hidden = true;
            document.querySelectorAll('.servicio-cliente-catalog-section').forEach(function (section) {
                section.hidden = true;
                section.style.display = 'none';
            });
            setServicioClienteCatalogQuery(null);
        }

        document.addEventListener('DOMContentLoaded', function () {
            const openCatalog = servicioClienteCatalogQuery();
            if (openCatalog && document.getElementById('section-' + openCatalog)) {
                showServicioClienteCatalog(openCatalog);
                setTimeout(function () {
                    adjustServicioClienteCatalogTable(document.getElementById('section-' + openCatalog));
                }, 0);
            }

            const modal = document.getElementById('servicio-cliente-catalog-modal');
            const form = document.getElementById('servicio-cliente-catalog-edit-form');
            const title = document.getElementById('servicio-cliente-catalog-modal-title');
            const nameInput = document.getElementById('servicio-cliente-catalog-edit-name');
            const activeInput = document.getElementById('servicio-cliente-catalog-edit-active');

            document.querySelectorAll('.btn-servicio-cliente-catalog-edit').forEach(function (button) {
                button.addEventListener('click', function () {
                    title.textContent = 'Editar: ' + (button.getAttribute('data-label') || '');
                    nameInput.value = button.getAttribute('data-name') || '';
                    activeInput.checked = button.getAttribute('data-active') === '1';
                    form.action = button.getAttribute('data-update-url') || '';
                    modal.hidden = false;
                    nameInput.focus();
                });
            });

            window.closeServicioClienteCatalogModal = function () {
                modal.hidden = true;
            };

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeServicioClienteCatalogModal();
                }
            });
        });
    </script>
</x-app-layout>
