<x-app-layout>
    <x-slot name="header">
        @include('requisitions.partials.subnav', ['moduleLabel' => $moduleLabel, 'subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="form-layout">
                @foreach ($catalogs as $catalog)
                    <section class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">{{ $catalog['label'] }}</h3>
                            <p class="panel-text">Catalogo reutilizable en formularios y tableros de requisiciones.</p>
                        </div>

                        <div class="panel__body section-stack">
                            {{-- Formulario agregar --}}
                            <form
                                method="POST"
                                action="{{ route('requisitions.parameters.store', ['module' => $moduleKey, 'type' => $catalog['key']]) }}"
                                class="form-stack"
                            >
                                @csrf
                                <div class="form-field">
                                    <x-input-label :for="'name_'.$catalog['key']" value="Nuevo valor" />
                                    <input id="{{ 'name_'.$catalog['key'] }}" name="name" type="text" class="form-input" required>
                                </div>
                                <label class="checkbox-card form-field--full">
                                    <input type="checkbox" name="is_active" value="1" class="form-check" checked>
                                    <span>
                                        <span class="checkbox-card__title">Activo para formularios</span>
                                        <span class="checkbox-card__text">Si se desmarca, quedara creado pero oculto en nuevas solicitudes.</span>
                                    </span>
                                </label>
                                <div class="form-actions form-field--full">
                                    <span class="text-small text-muted">Los catalogos impactan formularios, filtros y tableros del modulo.</span>
                                    <button type="submit" class="btn btn--secondary">Agregar</button>
                                </div>
                            </form>

                            {{-- Tabla / datatable --}}
                            <div class="data-table-wrap">
                                <table class="data-table js-datatable" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th style="width:100px;">Estado</th>
                                            <th style="width:160px;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($catalog['items'] as $item)
                                            <tr>
                                                <td>{{ $item->name }}</td>
                                                <td>
                                                    <span class="status-pill {{ $item->is_active ? 'status-pill--success' : 'status-pill--muted' }}">
                                                        {{ $item->is_active ? 'Activo' : 'Inactivo' }}
                                                    </span>
                                                </td>
                                                <td class="table-actions">
                                                    <button
                                                        type="button"
                                                        class="btn btn--secondary btn-param-edit"
                                                        data-type="{{ $catalog['key'] }}"
                                                        data-id="{{ $item->id }}"
                                                        data-name="{{ $item->name }}"
                                                        data-sort="{{ $item->sort_order }}"
                                                        data-active="{{ $item->is_active ? '1' : '0' }}"
                                                        data-label="{{ $catalog['label'] }}"
                                                        data-update-url="{{ route('requisitions.parameters.update', ['module' => $moduleKey, 'type' => $catalog['key'], 'parameterId' => $item->id]) }}"
                                                    >Editar</button>

                                                    <form
                                                        method="POST"
                                                        action="{{ route('requisitions.parameters.destroy', ['module' => $moduleKey, 'type' => $catalog['key'], 'parameterId' => $item->id]) }}"
                                                        style="display:inline;"
                                                        onsubmit="return confirm('¿Eliminar este parametro? Esta accion no se puede deshacer.')"
                                                    >
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn--danger">Eliminar</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Modal de edicion --}}
    <div id="param-modal" style="display:none; position:fixed; inset:0; z-index:9999; align-items:center; justify-content:center;">
        <div id="param-modal-backdrop" style="position:absolute; inset:0; background:rgba(0,0,0,.5);" onclick="closeParamModal()"></div>
        <div class="panel" style="position:relative; z-index:1; width:100%; max-width:480px; margin:1rem;">
            <div class="panel__header">
                <h3 class="panel-title" id="param-modal-title">Editar parametro</h3>
                <button type="button" class="btn btn--secondary" onclick="closeParamModal()">✕</button>
            </div>
            <form method="POST" id="param-edit-form" class="panel__body form-stack">
                @csrf
                @method('PATCH')
                <div class="form-stack">
                    <div class="form-field">
                        <x-input-label for="edit-param-name" value="Nombre" />
                        <input id="edit-param-name" name="name" type="text" class="form-input" required autocomplete="off">
                    </div>
                    <label class="checkbox-card">
                        <input type="checkbox" id="edit-param-active" name="is_active" value="1" class="form-check">
                        <span>
                            <span class="checkbox-card__title">Activo para formularios</span>
                        </span>
                    </label>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn--secondary" onclick="closeParamModal()">Cancelar</button>
                    <x-primary-button>Guardar cambios</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // ── Modal de edicion ─────────────────────────────────────────────────
        const $modal   = $('#param-modal');
        const $form    = $('#param-edit-form');
        const $mTitle  = $('#param-modal-title');
        const $mName   = $('#edit-param-name');
        const $mSort   = $('#edit-param-sort');
        const $mActive = $('#edit-param-active');

        // Usar delegación de eventos para que funcione con DataTables (paginación/búsqueda)
        $(document).on('click', '.btn-param-edit', function() {
            const data = $(this).data();
            $mTitle.text('Editar: ' + data.label);
            $mName.val(data.name);
            $mSort.val(data.sort);
            $mActive.prop('checked', data.active === 1);
            $form.attr('action', data.updateUrl);
            $modal.css('display', 'flex');
            $mName.focus();
        });

        window.closeParamModal = function () {
            $modal.hide();
        };

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') closeParamModal();
        });
    });
    </script>
</x-app-layout>
