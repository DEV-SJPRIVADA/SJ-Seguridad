{{--
    Catálogos MT-GI-11: selector de catálogo + tabla de valores por catálogo.

    Cinco catálogos comparten la misma forma; el de estados agrega el color.
--}}
<x-app-layout>
    <x-slot name="header">
        @include('areas.calidad.mt_gi_11.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section ficha-empleados-catalogs-page requisition-parameters-page mt-gi-11-catalogs-page">
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

            <div id="mt-gi-11-catalog-selector">
                <div class="page-header-inner ficha-empleados-catalogs-page__head">
                    <h2 class="page-title">Catálogos</h2>
                    <p class="page-subtitle">Seleccione un catálogo. Los valores inactivos no aparecen en los selectores del formulario.</p>
                </div>

                <div class="ficha-empleados-catalogs-page__grid">
                    @foreach ($catalogs as $type => $items)
                        <button type="button" class="ficha-empleados-catalogs-page__card" onclick="showMtGi11Catalog('{{ $type }}')">
                            <span class="ficha-empleados-catalogs-page__card-icon" aria-hidden="true">
                                <x-lucide-settings-2 width="22" height="22" />
                            </span>
                            <span class="ficha-empleados-catalogs-page__card-title">{{ $labels[$type] }}</span>
                            <span class="ficha-empleados-catalogs-page__card-count">{{ $items->count() }} registrados</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div id="mt-gi-11-catalog-manage" class="ficha-empleados-catalogs-page__manage" hidden>
                <button type="button" class="ficha-empleados-catalogs-page__back" onclick="showMtGi11CatalogSelector()">
                    <x-lucide-arrow-left width="18" height="18" aria-hidden="true" />
                    Volver al tablero
                </button>

                @foreach ($catalogs as $type => $items)
                    @php $esEstados = $type === 'estados'; @endphp
                    <section id="mt-gi-11-section-{{ $type }}" class="mt-gi-11-catalog-section ficha-empleados-catalogs-page__section" hidden>
                        <div class="panel">
                            <div class="panel__header panel__header--compact">
                                <h3 class="panel-title">{{ $labels[$type] }}</h3>
                            </div>
                            <div class="panel__body section-stack">
                                <form method="POST" action="{{ route('calidad.mt-gi-11.catalogos.store', $type) }}" class="ficha-empleados-catalogs-page__create-form">
                                    @csrf
                                    <div class="ficha-empleados-catalogs-page__create-row">
                                        <div class="form-field ficha-empleados-catalogs-page__name-field">
                                            <label class="form-label" for="name_{{ $type }}">Nuevo valor</label>
                                            <input id="name_{{ $type }}" name="name" type="text" class="form-input" placeholder="Nombre…" maxlength="150" required>
                                        </div>

                                        @if ($esEstados)
                                            <div class="form-field">
                                                <label class="form-label" for="color_{{ $type }}">Color</label>
                                                <select id="color_{{ $type }}" name="color" class="form-input">
                                                    <option value="warning">Ámbar (pendiente)</option>
                                                    <option value="info">Azul (en revisión)</option>
                                                    <option value="success">Verde (aprobado)</option>
                                                    <option value="danger">Rojo (rechazado)</option>
                                                    <option value="muted">Gris (neutro)</option>
                                                </select>
                                            </div>
                                        @endif

                                        <div class="ficha-empleados-catalogs-page__create-actions">
                                            <label class="ficha-empleados-catalogs-page__active-check">
                                                <input type="checkbox" name="is_active" value="1" class="form-check" checked>
                                                <span>Activo</span>
                                            </label>
                                            <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Agregar" aria-label="Agregar">
                                                <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                            </button>
                                        </div>
                                    </div>
                                </form>

                                <div class="data-table-wrap">
                                    <table class="data-table js-datatable" data-dt-responsive="false" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>Id</th>
                                                <th>Nombre</th>
                                                @if ($esEstados)
                                                    <th>Color</th>
                                                @endif
                                                <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($items as $item)
                                                <tr>
                                                    <td>{{ $item->id }}</td>
                                                    <td>{{ $item->name }}</td>
                                                    @if ($esEstados)
                                                        <td>
                                                            <span class="status-pill status-pill--{{ $item->pillClass() }}">{{ $item->name }}</span>
                                                        </td>
                                                    @endif
                                                    <td>
                                                        <span class="status-pill {{ $item->is_active ? 'status-pill--success' : 'status-pill--muted' }}">
                                                            {{ $item->is_active ? 'Activo' : 'Inactivo' }}
                                                        </span>
                                                    </td>
                                                    <td class="table-actions">
                                                        <div class="cursos-catalogo-page__row-actions">
                                                            <button
                                                                type="button"
                                                                class="cursos-catalogo-page__icon-btn btn-mt-gi-11-catalog-edit"
                                                                title="Editar"
                                                                aria-label="Editar"
                                                                data-name="{{ $item->name }}"
                                                                data-active="{{ $item->is_active ? '1' : '0' }}"
                                                                data-color="{{ $esEstados ? $item->pillClass() : '' }}"
                                                                data-label="{{ $labels[$type] }}"
                                                                data-color-field="{{ $esEstados ? '1' : '0' }}"
                                                                data-update-url="{{ route('calidad.mt-gi-11.catalogos.update', [$type, $item]) }}"
                                                            >
                                                                <x-lucide-pencil width="16" height="16" aria-hidden="true" />
                                                            </button>
                                                            <form method="POST" action="{{ route('calidad.mt-gi-11.catalogos.destroy', [$type, $item]) }}" class="cursos-catalogo-page__delete-form" onsubmit="return confirm('¿Eliminar este valor del catálogo?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger" title="Eliminar" aria-label="Eliminar">
                                                                    <x-lucide-trash-2 width="16" height="16" aria-hidden="true" />
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="{{ $esEstados ? 5 : 4 }}" class="text-muted">Sin registros en este catálogo.</td>
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

    {{-- Modal de edición de un valor de catálogo --}}
    <div id="mt-gi-11-catalog-modal" class="ficha-empleados-catalogs-page__modal" hidden>
        <div class="ficha-empleados-catalogs-page__modal-backdrop" onclick="closeMtGi11CatalogModal()"></div>
        <div class="panel ficha-empleados-catalogs-page__modal-card" role="dialog" aria-modal="true" aria-labelledby="mt-gi-11-catalog-modal-title">
            <div class="ficha-empleados-catalogs-page__modal-header">
                <div class="ficha-empleados-catalogs-page__modal-heading">
                    <span class="ficha-empleados-catalogs-page__modal-heading-icon" aria-hidden="true">
                        <x-lucide-pencil width="20" height="20" />
                    </span>
                    <div>
                        <h3 class="ficha-empleados-catalogs-page__modal-title" id="mt-gi-11-catalog-modal-title">Editar catálogo</h3>
                        <p class="ficha-empleados-catalogs-page__modal-lead">Este nombre es el que aparece en el selector del formulario.</p>
                    </div>
                </div>
                <button type="button" class="ficha-empleados-catalogs-page__modal-close" onclick="closeMtGi11CatalogModal()" title="Cerrar" aria-label="Cerrar">
                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                </button>
            </div>
            <form method="POST" id="mt-gi-11-catalog-edit-form" class="ficha-empleados-catalogs-page__modal-body">
                @csrf
                @method('PATCH')
                <div class="ficha-empleados-catalogs-page__modal-fields">
                    <div class="form-field">
                        <label class="form-label" for="mt-gi-11-catalog-edit-name">Nombre</label>
                        <input id="mt-gi-11-catalog-edit-name" name="name" type="text" class="form-input" maxlength="150" required autocomplete="off">
                    </div>
                    <div class="form-field" id="mt-gi-11-catalog-edit-color-field" hidden>
                        <label class="form-label" for="mt-gi-11-catalog-edit-color">Color</label>
                        <select id="mt-gi-11-catalog-edit-color" name="color" class="form-input">
                            <option value="warning">Ámbar (pendiente)</option>
                            <option value="info">Azul (en revisión)</option>
                            <option value="success">Verde (aprobado)</option>
                            <option value="danger">Rojo (rechazado)</option>
                            <option value="muted">Gris (neutro)</option>
                        </select>
                    </div>
                    <label class="ficha-empleados-catalogs-page__modal-active" for="mt-gi-11-catalog-edit-active">
                        <input type="checkbox" id="mt-gi-11-catalog-edit-active" name="is_active" value="1" class="form-check">
                        <span>
                            <span class="ficha-empleados-catalogs-page__modal-active-title">Activo para formularios</span>
                            <span class="ficha-empleados-catalogs-page__modal-active-text">Si está inactivo no aparece en los selectores.</span>
                        </span>
                    </label>
                </div>
                <div class="ficha-empleados-catalogs-page__modal-actions">
                    <button type="button" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost" onclick="closeMtGi11CatalogModal()" title="Cancelar" aria-label="Cancelar">
                        <x-lucide-x width="18" height="18" aria-hidden="true" />
                    </button>
                    <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Guardar cambios" aria-label="Guardar cambios">
                        <x-lucide-save width="18" height="18" aria-hidden="true" />
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Navegación entre el tablero de catálogos y la tabla de cada uno.
        function mtGi11CatalogQuery() {
            return new URLSearchParams(window.location.search).get('catalog');
        }

        function setMtGi11CatalogQuery(key) {
            const url = new URL(window.location.href);
            if (key) {
                url.searchParams.set('catalog', key);
            } else {
                url.searchParams.delete('catalog');
            }
            window.history.replaceState({}, '', url.toString());
        }

        function showMtGi11Catalog(key) {
            document.getElementById('mt-gi-11-catalog-selector').hidden = true;
            document.getElementById('mt-gi-11-catalog-manage').hidden = false;

            document.querySelectorAll('.mt-gi-11-catalog-section').forEach(function (section) {
                section.hidden = true;
                section.style.display = 'none';
            });

            const section = document.getElementById('mt-gi-11-section-' + key);
            if (section) {
                section.hidden = false;
                section.style.display = 'block';
            }

            setMtGi11CatalogQuery(key);
            window.scrollTo(0, 0);
            adjustMtGi11CatalogTable(section);
        }

        // DataTables necesita recalcular anchos al mostrar una sección oculta.
        function adjustMtGi11CatalogTable(section) {
            if (!section || !window.jQuery || !jQuery.fn.DataTable) {
                return;
            }

            section.querySelectorAll('table.js-datatable').forEach(function (table) {
                if (!jQuery.fn.DataTable.isDataTable(table)) {
                    return;
                }

                jQuery(table).css('width', '100%');
                jQuery(table).DataTable().columns.adjust();
            });
        }

        function showMtGi11CatalogSelector() {
            document.getElementById('mt-gi-11-catalog-selector').hidden = false;
            document.getElementById('mt-gi-11-catalog-manage').hidden = true;

            document.querySelectorAll('.mt-gi-11-catalog-section').forEach(function (section) {
                section.hidden = true;
                section.style.display = 'none';
            });

            setMtGi11CatalogQuery(null);
        }

        function closeMtGi11CatalogModal() {
            document.getElementById('mt-gi-11-catalog-modal').hidden = true;
        }

        document.addEventListener('DOMContentLoaded', function () {
            const openCatalog = mtGi11CatalogQuery();
            if (openCatalog && document.getElementById('mt-gi-11-section-' + openCatalog)) {
                showMtGi11Catalog(openCatalog);
                setTimeout(function () {
                    adjustMtGi11CatalogTable(document.getElementById('mt-gi-11-section-' + openCatalog));
                }, 0);
            }

            const modal = document.getElementById('mt-gi-11-catalog-modal');
            const form = document.getElementById('mt-gi-11-catalog-edit-form');
            const title = document.getElementById('mt-gi-11-catalog-modal-title');
            const nameInput = document.getElementById('mt-gi-11-catalog-edit-name');
            const activeInput = document.getElementById('mt-gi-11-catalog-edit-active');
            const colorField = document.getElementById('mt-gi-11-catalog-edit-color-field');
            const colorInput = document.getElementById('mt-gi-11-catalog-edit-color');

            document.querySelectorAll('.btn-mt-gi-11-catalog-edit').forEach(function (button) {
                button.addEventListener('click', function () {
                    title.textContent = 'Editar: ' + (button.getAttribute('data-label') || '');
                    nameInput.value = button.getAttribute('data-name') || '';
                    activeInput.checked = button.getAttribute('data-active') === '1';

                    // El selector de color solo aplica al catálogo de estados.
                    const hasColor = button.getAttribute('data-color-field') === '1';
                    colorField.hidden = !hasColor;
                    if (hasColor) {
                        colorInput.value = button.getAttribute('data-color') || 'muted';
                    }

                    form.action = button.getAttribute('data-update-url') || '';
                    modal.hidden = false;
                    nameInput.focus();
                });
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeMtGi11CatalogModal();
                }
            });
        });
    </script>
</x-app-layout>
