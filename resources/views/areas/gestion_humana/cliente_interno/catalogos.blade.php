<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.cliente_interno.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Catálogos Cliente interno: selector de tarjetas + gestión por ?catalog= --}}
    <div class="page-section ficha-empleados-catalogs-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success ficha-empleados-catalogs-page__alert">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert--danger ficha-empleados-catalogs-page__alert">{{ session('error') }}</div>
            @endif

            <div id="ci-catalog-selector-screen">
                <div class="page-header-inner ficha-empleados-catalogs-page__head">
                    <h2 class="page-title">Catálogos de Cliente interno</h2>
                    <p class="page-subtitle">Elija un catálogo para crear o editar registros. Los <strong>tipos de solicitud</strong> son obligatorios antes de registrar solicitudes.</p>
                </div>

                <div class="ficha-empleados-catalogs-page__grid">
                    @foreach ($catalogs as $catalog)
                        <button type="button" class="ficha-empleados-catalogs-page__card" data-catalog-key="{{ $catalog['key'] }}">
                            <span class="ficha-empleados-catalogs-page__card-icon" aria-hidden="true">
                                @if ($catalog['key'] === 'tipos-solicitud')
                                    <x-lucide-tags width="22" height="22" aria-hidden="true" />
                                @else
                                    <x-lucide-list width="22" height="22" aria-hidden="true" />
                                @endif
                            </span>
                            <span class="ficha-empleados-catalogs-page__card-title">{{ $catalog['label'] }}</span>
                            <span class="ficha-empleados-catalogs-page__card-count">{{ count($catalog['items']) }} registrados</span>
                            @if ($catalog['key'] === 'tipos-solicitud' && $catalog['items']->isEmpty())
                                <span class="status-pill status-pill--warning">Sin tipos — créelos aquí</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            <div id="ci-catalog-management-screen" class="ficha-empleados-catalogs-page__manage" hidden>
                <div class="ficha-empleados-catalogs-page__manage-toolbar">
                    <button type="button" class="ficha-empleados-catalogs-page__back" data-catalog-back>
                        <x-lucide-arrow-left width="18" height="18" aria-hidden="true" />
                        Volver a catálogos
                    </button>

                    {{-- Cambio rápido entre Estados y Tipos sin volver al tablero --}}
                    <nav class="module-tabs ficha-empleados-catalogs-page__switch" aria-label="Catálogo activo">
                        @foreach ($catalogs as $catalog)
                            <button
                                type="button"
                                class="module-tab"
                                data-catalog-switch="{{ $catalog['key'] }}"
                            >
                                {{ $catalog['label'] }}
                                <span class="ficha-empleados-catalogs-page__switch-count">{{ count($catalog['items']) }}</span>
                            </button>
                        @endforeach
                    </nav>
                </div>

                @foreach ($catalogs as $catalog)
                    <section id="section-{{ $catalog['key'] }}" class="ficha-empleados-catalogs-page__section" hidden>
                        <div class="panel">
                            <div class="panel__header">
                                <h3 class="panel-title">Gestionar: {{ $catalog['label'] }}</h3>
                                <p class="panel-text">
                                    @if ($catalog['key'] === 'tipos-solicitud')
                                        Cree aquí los tipos (por ejemplo Certificado laboral, Carta laboral). Luego aparecerán en el formulario de Solicitudes.
                                    @else
                                        {{ $catalog['columnLabels']['code'] }} y {{ $catalog['columnLabels']['name'] }} usados en solicitudes de Cliente interno.
                                    @endif
                                </p>
                            </div>

                            <div class="panel__body section-stack">
                                {{-- Alta rápida de ítem de catálogo --}}
                                <form
                                    method="POST"
                                    action="{{ route('gestion-humana.cliente-interno.catalogos.store', ['type' => $catalog['key']]) }}"
                                    class="ficha-empleados-catalogs-page__create-form"
                                >
                                    @csrf
                                    <div class="ficha-empleados-catalogs-page__create-row">
                                        <div class="form-field">
                                            <label class="form-label" for="code_new_{{ $catalog['key'] }}">{{ $catalog['columnLabels']['code'] }}</label>
                                            <input
                                                id="code_new_{{ $catalog['key'] }}"
                                                name="code"
                                                type="text"
                                                class="form-input"
                                                maxlength="50"
                                                required
                                                placeholder="{{ $catalog['key'] === 'tipos-solicitud' ? 'Ej. CERTIFICADO' : $catalog['columnLabels']['code'] }}"
                                            >
                                        </div>
                                        <div class="form-field ficha-empleados-catalogs-page__name-field">
                                            <label class="form-label" for="name_new_{{ $catalog['key'] }}">{{ $catalog['columnLabels']['name'] }}</label>
                                            <input
                                                id="name_new_{{ $catalog['key'] }}"
                                                name="name"
                                                type="text"
                                                class="form-input"
                                                maxlength="{{ $catalog['key'] === 'estados' ? 100 : 150 }}"
                                                required
                                                placeholder="{{ $catalog['key'] === 'tipos-solicitud' ? 'Ej. Certificado laboral' : $catalog['columnLabels']['name'] }}"
                                            >
                                        </div>
                                        <div class="form-field ficha-empleados-catalogs-page__sort-field">
                                            <label class="form-label" for="sort_new_{{ $catalog['key'] }}">Orden</label>
                                            <input
                                                id="sort_new_{{ $catalog['key'] }}"
                                                name="sort_order"
                                                type="number"
                                                min="0"
                                                max="9999"
                                                class="form-input"
                                                value="0"
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
                                                aria-label="Agregar {{ $catalog['label'] }}"
                                            >
                                                <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                            </button>
                                        </div>
                                    </div>
                                </form>

                                @if ($catalog['items']->isEmpty() && $catalog['emptyMessage'])
                                    <div class="alert alert--info" role="status">
                                        {{ $catalog['emptyMessage'] }}
                                    </div>
                                @endif

                                <div class="data-table-wrap">
                                    <table class="data-table js-datatable" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>{{ $catalog['columnLabels']['code'] }}</th>
                                                <th>{{ $catalog['columnLabels']['name'] }}</th>
                                                <th style="width:80px;">Orden</th>
                                                <th style="width:100px;">Estado</th>
                                                <th style="width:180px;">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($catalog['items'] as $item)
                                                <tr>
                                                    <td><code>{{ $item->code }}</code></td>
                                                    <td>{{ $item->name }}</td>
                                                    <td>{{ $item->sort_order ?? 0 }}</td>
                                                    <td>
                                                        <span class="status-pill {{ $item->is_active ? 'status-pill--success' : 'status-pill--muted' }}">
                                                            {{ $item->is_active ? 'Activo' : 'Inactivo' }}
                                                        </span>
                                                    </td>
                                                    <td class="table-actions">
                                                        <div class="cursos-catalogo-page__row-actions">
                                                            <button
                                                                type="button"
                                                                class="cursos-catalogo-page__icon-btn btn-ci-catalog-edit"
                                                                title="Editar"
                                                                aria-label="Editar"
                                                                data-label="{{ $catalog['label'] }}"
                                                                data-code="{{ $item->code }}"
                                                                data-name="{{ $item->name }}"
                                                                data-active="{{ $item->is_active ? '1' : '0' }}"
                                                                data-sort="{{ $item->sort_order ?? 0 }}"
                                                                data-code-label="{{ $catalog['columnLabels']['code'] }}"
                                                                data-name-label="{{ $catalog['columnLabels']['name'] }}"
                                                                data-update-url="{{ route('gestion-humana.cliente-interno.catalogos.update', ['type' => $catalog['key'], 'item' => $item->id]) }}"
                                                            >
                                                                <x-lucide-pencil width="16" height="16" aria-hidden="true" />
                                                            </button>

                                                            <form
                                                                method="POST"
                                                                action="{{ route('gestion-humana.cliente-interno.catalogos.destroy', ['type' => $catalog['key'], 'item' => $item->id]) }}"
                                                                class="cursos-catalogo-page__delete-form"
                                                                onsubmit="return confirm('¿Eliminar este registro del catálogo?')"
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
                                                    <td colspan="5" class="text-muted">Sin registros en este catálogo. Use el formulario de arriba para agregar el primero.</td>
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

    {{-- Modal edición de ítem --}}
    <div id="ci-catalog-modal" class="ficha-empleados-catalogs-page__modal" hidden>
        <div class="ficha-empleados-catalogs-page__modal-backdrop" data-catalog-modal-close></div>
        <div class="panel ficha-empleados-catalogs-page__modal-card">
            <div class="panel__header">
                <h3 class="panel-title" id="ci-catalog-modal-title">Editar catálogo</h3>
                <button type="button" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost" data-catalog-modal-close title="Cerrar" aria-label="Cerrar">
                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                </button>
            </div>
            <form method="POST" id="ci-catalog-edit-form" class="panel__body form-stack">
                @csrf
                @method('PATCH')
                <div class="form-field">
                    <label class="form-label" for="ci-catalog-edit-code"><span id="ci-modal-code-label">Código</span></label>
                    <input id="ci-catalog-edit-code" name="code" type="text" class="form-input" maxlength="50" required>
                </div>
                <div class="form-field">
                    <label class="form-label" for="ci-catalog-edit-name"><span id="ci-modal-name-label">Nombre</span></label>
                    <input id="ci-catalog-edit-name" name="name" type="text" class="form-input" maxlength="150" required>
                </div>
                <div class="form-field">
                    <label class="form-label" for="ci-catalog-edit-sort">Orden</label>
                    <input id="ci-catalog-edit-sort" name="sort_order" type="number" min="0" max="9999" class="form-input">
                </div>
                <label class="checkbox-card">
                    <input type="checkbox" id="ci-catalog-edit-active" name="is_active" value="1" class="form-check">
                    <span>
                        <span class="checkbox-card__title">Activo en formularios</span>
                    </span>
                </label>
                <div class="form-actions">
                    <button type="button" class="btn btn--secondary" data-catalog-modal-close>Cancelar</button>
                    <button type="submit" class="btn btn--primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var selectorScreen = document.getElementById('ci-catalog-selector-screen');
            var manageScreen = document.getElementById('ci-catalog-management-screen');
            var modal = document.getElementById('ci-catalog-modal');
            var editForm = document.getElementById('ci-catalog-edit-form');
            var modalTitle = document.getElementById('ci-catalog-modal-title');
            var editCode = document.getElementById('ci-catalog-edit-code');
            var editName = document.getElementById('ci-catalog-edit-name');
            var editSort = document.getElementById('ci-catalog-edit-sort');
            var editActive = document.getElementById('ci-catalog-edit-active');
            var defaultCatalog = @json($activeCatalog);

            function catalogQueryKey() {
                var fromQuery = new URLSearchParams(window.location.search).get('catalog');
                if (fromQuery) {
                    return fromQuery;
                }

                return defaultCatalog || null;
            }

            function setCatalogQuery(key) {
                var url = new URL(window.location.href);
                if (key) {
                    url.searchParams.set('catalog', key);
                } else {
                    url.searchParams.delete('catalog');
                }
                url.hash = '';
                window.history.replaceState({}, '', url.toString());
            }

            function syncSwitchTabs(key) {
                document.querySelectorAll('[data-catalog-switch]').forEach(function (tab) {
                    var active = tab.getAttribute('data-catalog-switch') === key;
                    tab.classList.toggle('module-tab--active', active);
                    tab.setAttribute('aria-current', active ? 'page' : 'false');
                });
            }

            function showSection(key) {
                selectorScreen.hidden = true;
                manageScreen.hidden = false;
                document.querySelectorAll('.ficha-empleados-catalogs-page__section').forEach(function (section) {
                    section.hidden = section.id !== 'section-' + key;
                });
                syncSwitchTabs(key);
                setCatalogQuery(key);
                window.scrollTo(0, 0);
            }

            function showSelector() {
                selectorScreen.hidden = false;
                manageScreen.hidden = true;
                setCatalogQuery(null);
            }

            function openModal() {
                modal.hidden = false;
            }

            function closeModal() {
                modal.hidden = true;
            }

            document.querySelectorAll('[data-catalog-key]').forEach(function (button) {
                button.addEventListener('click', function () {
                    showSection(button.getAttribute('data-catalog-key'));
                });
            });

            document.querySelectorAll('[data-catalog-switch]').forEach(function (button) {
                button.addEventListener('click', function () {
                    showSection(button.getAttribute('data-catalog-switch'));
                });
            });

            document.querySelectorAll('[data-catalog-back]').forEach(function (button) {
                button.addEventListener('click', showSelector);
            });

            document.querySelectorAll('[data-catalog-modal-close]').forEach(function (button) {
                button.addEventListener('click', closeModal);
            });

            document.querySelectorAll('.btn-ci-catalog-edit').forEach(function (button) {
                button.addEventListener('click', function () {
                    modalTitle.textContent = 'Editar: ' + button.getAttribute('data-label');
                    editCode.value = button.getAttribute('data-code') || '';
                    editName.value = button.getAttribute('data-name') || '';
                    editSort.value = button.getAttribute('data-sort') || '0';
                    editActive.checked = button.getAttribute('data-active') === '1';
                    editForm.action = button.getAttribute('data-update-url') || '';
                    var codeLabel = document.getElementById('ci-modal-code-label');
                    var nameLabel = document.getElementById('ci-modal-name-label');
                    if (codeLabel) { codeLabel.textContent = button.getAttribute('data-code-label') || 'Código'; }
                    if (nameLabel) { nameLabel.textContent = button.getAttribute('data-name-label') || 'Nombre'; }
                    openModal();
                    editName.focus();
                });
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });

            // Solo abre un catálogo si viene ?catalog= en la URL
            var openCatalog = catalogQueryKey();
            if (openCatalog && document.getElementById('section-' + openCatalog)) {
                showSection(openCatalog);
            }
        });
    </script>
</x-app-layout>
