<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.partials.ficha-empleados-subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section ficha-empleados-catalogs-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success ficha-empleados-catalogs-page__alert">{{ session('status') }}</div>
            @endif

            <div id="ficha-catalog-selector-screen">
                <div class="page-header-inner ficha-empleados-catalogs-page__head">
                    <h2 class="page-title">Catalogos de empleados</h2>
                    <p class="page-subtitle">Valores de los selectores en crear/editar ficha e importacion masiva.</p>
                </div>

                <div class="ficha-empleados-catalogs-page__grid">
                    @foreach ($catalogs as $catalog)
                        <button type="button" class="ficha-empleados-catalogs-page__card" data-catalog-key="{{ $catalog['key'] }}">
                            <span class="ficha-empleados-catalogs-page__card-icon" aria-hidden="true">
                                <x-lucide-icon name="list" :size="22" />
                            </span>
                            <span class="ficha-empleados-catalogs-page__card-title">{{ $catalog['label'] }}</span>
                            <span class="ficha-empleados-catalogs-page__card-count">{{ count($catalog['items']) }} registrados</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div id="ficha-catalog-management-screen" class="ficha-empleados-catalogs-page__manage" hidden>
                <button type="button" class="ficha-empleados-catalogs-page__back" data-catalog-back>
                    <x-lucide-icon name="arrow-left" :size="18" />
                    Volver al tablero
                </button>

                @foreach ($catalogs as $catalog)
                    <section id="section-{{ $catalog['key'] }}" class="ficha-empleados-catalogs-page__section" hidden>
                        <div class="panel">
                            <div class="panel__header">
                                <h3 class="panel-title">Gestionar: {{ $catalog['label'] }}</h3>
                                <p class="panel-text">Codigo y nombre usados en formularios de ficha de empleado.</p>
                            </div>

                            <div class="panel__body section-stack">
                                <form
                                    method="POST"
                                    action="{{ route('gestion-humana.ficha-empleados.catalogs.store', ['type' => $catalog['key']]) }}"
                                    class="ficha-empleados-catalogs-page__create-form"
                                >
                                    @csrf
                                    <div class="ficha-empleados-catalogs-page__create-row">
                                        <div class="form-field">
                                            <label class="form-label" for="code_new_{{ $catalog['key'] }}">Codigo</label>
                                            <input
                                                id="code_new_{{ $catalog['key'] }}"
                                                name="code"
                                                type="text"
                                                class="form-input"
                                                maxlength="50"
                                                required
                                                placeholder="Ej. EPS001"
                                            >
                                        </div>
                                        <div class="form-field ficha-empleados-catalogs-page__name-field">
                                            <label class="form-label" for="name_new_{{ $catalog['key'] }}">Nombre</label>
                                            <input
                                                id="name_new_{{ $catalog['key'] }}"
                                                name="name"
                                                type="text"
                                                class="form-input"
                                                maxlength="255"
                                                required
                                                placeholder="Nombre visible"
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
                                            <button type="submit" class="btn btn--primary btn--sm">Agregar</button>
                                        </div>
                                    </div>
                                </form>

                                <div class="data-table-wrap">
                                    <table class="data-table js-datatable" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>Codigo</th>
                                                <th>Nombre</th>
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
                                                        <button
                                                            type="button"
                                                            class="btn btn--secondary btn--sm btn-ficha-catalog-edit"
                                                            data-label="{{ $catalog['label'] }}"
                                                            data-code="{{ $item->code }}"
                                                            data-name="{{ $item->name }}"
                                                            data-active="{{ $item->is_active ? '1' : '0' }}"
                                                            data-sort="{{ $item->sort_order ?? 0 }}"
                                                            data-update-url="{{ route('gestion-humana.ficha-empleados.catalogs.update', ['type' => $catalog['key'], 'item' => $item->id]) }}"
                                                        >Editar</button>

                                                        <form
                                                            method="POST"
                                                            action="{{ route('gestion-humana.ficha-empleados.catalogs.destroy', ['type' => $catalog['key'], 'item' => $item->id]) }}"
                                                            class="ficha-empleados-catalogs-page__delete-form"
                                                            onsubmit="return confirm('Eliminar este registro del catalogo?')"
                                                        >
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn--danger btn--sm">Eliminar</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-muted">Sin registros en este catalogo.</td>
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

    <div id="ficha-catalog-modal" class="ficha-empleados-catalogs-page__modal" hidden>
        <div class="ficha-empleados-catalogs-page__modal-backdrop" data-catalog-modal-close></div>
        <div class="panel ficha-empleados-catalogs-page__modal-card">
            <div class="panel__header">
                <h3 class="panel-title" id="ficha-catalog-modal-title">Editar catalogo</h3>
                <button type="button" class="btn btn--secondary btn--sm" data-catalog-modal-close aria-label="Cerrar">✕</button>
            </div>
            <form method="POST" id="ficha-catalog-edit-form" class="panel__body form-stack">
                @csrf
                @method('PATCH')
                <div class="form-field">
                    <label class="form-label" for="ficha-catalog-edit-code">Codigo</label>
                    <input id="ficha-catalog-edit-code" name="code" type="text" class="form-input" maxlength="50" required>
                </div>
                <div class="form-field">
                    <label class="form-label" for="ficha-catalog-edit-name">Nombre</label>
                    <input id="ficha-catalog-edit-name" name="name" type="text" class="form-input" maxlength="255" required>
                </div>
                <div class="form-field">
                    <label class="form-label" for="ficha-catalog-edit-sort">Orden</label>
                    <input id="ficha-catalog-edit-sort" name="sort_order" type="number" min="0" max="9999" class="form-input">
                </div>
                <label class="checkbox-card">
                    <input type="checkbox" id="ficha-catalog-edit-active" name="is_active" value="1" class="form-check">
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
            var selectorScreen = document.getElementById('ficha-catalog-selector-screen');
            var manageScreen = document.getElementById('ficha-catalog-management-screen');
            var modal = document.getElementById('ficha-catalog-modal');
            var editForm = document.getElementById('ficha-catalog-edit-form');
            var modalTitle = document.getElementById('ficha-catalog-modal-title');
            var editCode = document.getElementById('ficha-catalog-edit-code');
            var editName = document.getElementById('ficha-catalog-edit-name');
            var editSort = document.getElementById('ficha-catalog-edit-sort');
            var editActive = document.getElementById('ficha-catalog-edit-active');

            function showSection(key) {
                selectorScreen.hidden = true;
                manageScreen.hidden = false;
                document.querySelectorAll('.ficha-empleados-catalogs-page__section').forEach(function (section) {
                    section.hidden = section.id !== 'section-' + key;
                });
                window.scrollTo(0, 0);
            }

            function showSelector() {
                selectorScreen.hidden = false;
                manageScreen.hidden = true;
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

            document.querySelectorAll('[data-catalog-back]').forEach(function (button) {
                button.addEventListener('click', showSelector);
            });

            document.querySelectorAll('[data-catalog-modal-close]').forEach(function (button) {
                button.addEventListener('click', closeModal);
            });

            document.querySelectorAll('.btn-ficha-catalog-edit').forEach(function (button) {
                button.addEventListener('click', function () {
                    modalTitle.textContent = 'Editar: ' + button.getAttribute('data-label');
                    editCode.value = button.getAttribute('data-code') || '';
                    editName.value = button.getAttribute('data-name') || '';
                    editSort.value = button.getAttribute('data-sort') || '0';
                    editActive.checked = button.getAttribute('data-active') === '1';
                    editForm.action = button.getAttribute('data-update-url') || '';
                    openModal();
                    editName.focus();
                });
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });

            var hash = window.location.hash.replace('#', '');
            if (hash.indexOf('section-') === 0) {
                showSection(hash.replace('section-', ''));
            }
        });
    </script>
</x-app-layout>
