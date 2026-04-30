<x-app-layout>
    <x-slot name="header">
        @include('requisitions.partials.subnav', ['moduleLabel' => $moduleLabel, 'subTabs' => $subTabs])
    </x-slot>

    <style>
        .parameter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .parameter-card {
            background: #fff;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--shadow-soft);
        }

        .parameter-card:hover {
            transform: translateY(-4px);
            border-color: var(--color-sky);
            box-shadow: var(--shadow-card);
        }

        .parameter-card i {
            font-size: 2rem;
            color: var(--color-primary);
        }

        .parameter-card h4 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--color-text);
        }

        .parameter-card .item-count {
            font-size: 0.75rem;
            color: var(--color-text-soft);
            background: var(--color-bg);
            padding: 2px 8px;
            border-radius: 999px;
        }

        .parameter-section {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .back-to-grid {
            margin-bottom: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--color-sky);
            font-weight: 600;
            cursor: pointer;
        }

        .back-to-grid:hover {
            text-decoration: underline;
        }
    </style>

    <div class="page-section">
        <div class="app-container">
            
            {{-- PANTALLA 1: SELECTOR DE PARÁMETROS --}}
            <div id="parameter-selector-screen">
                <div class="page-header-inner" style="padding-top: 0;">
                    <h2 class="page-title">Tablero de Parámetros</h2>
                    <p class="page-subtitle">Selecciona una categoría para gestionar sus valores disponibles.</p>
                </div>

                <div class="parameter-grid">
                    @foreach ($catalogs as $catalog)
                        <div class="parameter-card" onclick="showParameterSection('{{ $catalog['key'] }}')">
                            <div style="background: var(--brand-blue-pale); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-settings-2"><path d="M20 7h-9"/><path d="M14 17H5"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/></svg>
                            </div>
                            <h4>{{ $catalog['label'] }}</h4>
                            <span class="item-count">{{ count($catalog['items']) }} registrados</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- PANTALLA 2: GESTIÓN DE PARÁMETRO SELECCIONADO --}}
            <div id="parameter-management-screen" style="display: none;">
                <div class="back-to-grid" onclick="showSelectorScreen()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                    Volver al tablero
                </div>

                @foreach ($catalogs as $catalog)
                    <section id="section-{{ $catalog['key'] }}" class="parameter-section">
                        <div class="panel">
                            <div class="panel__header">
                                <h3 class="panel-title">Gestionar: {{ $catalog['label'] }}</h3>
                                <p class="panel-text">Añade o edita los valores que aparecen en los formularios de requisiciones.</p>
                            </div>

                            <div class="panel__body section-stack">
                                {{-- Formulario agregar --}}
                                <form
                                    method="POST"
                                    action="{{ route('requisitions.parameters.store', ['module' => $moduleKey, 'type' => $catalog['key']]) }}"
                                    class="form-stack"
                                    style="background: var(--color-bg); padding: 1.5rem; border-radius: var(--radius-lg); margin-bottom: 2rem;"
                                >
                                    @csrf
                                    <div style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
                                        <div class="form-field" style="flex: 1; min-width: 250px;">
                                            <x-input-label :for="'name_'.$catalog['key']" value="Nuevo valor para {{ $catalog['label'] }}" />
                                            <input id="{{ 'name_'.$catalog['key'] }}" name="name" type="text" class="form-input" placeholder="Escribe el nombre aquí..." required>
                                        </div>
                                        
                                        <div style="display: flex; align-items: center; gap: 1rem; height: 44px;">
                                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; margin: 0;">
                                                <input type="checkbox" name="is_active" value="1" class="form-check" checked>
                                                <span style="font-size: 0.9rem; font-weight: 600;">Activo</span>
                                            </label>
                                            <button type="submit" class="btn btn--primary">Agregar</button>
                                        </div>
                                    </div>
                                </form>

                                {{-- Tabla --}}
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
                                                            data-active="{{ $item->is_active ? '1' : '0' }}"
                                                            data-label="{{ $catalog['label'] }}"
                                                            data-update-url="{{ route('requisitions.parameters.update', ['module' => $moduleKey, 'type' => $catalog['key'], 'parameterId' => $item->id]) }}"
                                                        >Editar</button>

                                                        <form
                                                            method="POST"
                                                            action="{{ route('requisitions.parameters.destroy', ['module' => $moduleKey, 'type' => $catalog['key'], 'parameterId' => $item->id]) }}"
                                                            style="display:inline;"
                                                            onsubmit="return confirm('¿Eliminar este parametro?')"
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
                    <button type="submit" class="btn btn--primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function showParameterSection(key) {
        document.getElementById('parameter-selector-screen').style.display = 'none';
        document.getElementById('parameter-management-screen').style.display = 'block';
        
        // Ocultar todas las secciones
        document.querySelectorAll('.parameter-section').forEach(s => s.style.display = 'none');
        
        // Mostrar la seleccionada
        const section = document.getElementById('section-' + key);
        if (section) section.style.display = 'block';

        // Scroll al inicio
        window.scrollTo(0, 0);
    }

    function showSelectorScreen() {
        document.getElementById('parameter-selector-screen').style.display = 'block';
        document.getElementById('parameter-management-screen').style.display = 'none';
    }

    $(document).ready(function() {
        // Inicializar DataTables solo si no están ya inicializados (evitar errores al ocultar/mostrar)
        if ($.fn.DataTable.isDataTable('.js-datatable')) {
            // Ya están, opcionalmente podrías destruirlos y recrearlos o solo ajustar columnas
        }

        // Modal de edicion
        const $modal   = $('#param-modal');
        const $form    = $('#param-edit-form');
        const $mTitle  = $('#param-modal-title');
        const $mName   = $('#edit-param-name');
        const $mActive = $('#edit-param-active');

        $(document).on('click', '.btn-param-edit', function() {
            const data = $(this).data();
            $mTitle.text('Editar: ' + data.label);
            $mName.val(data.name);
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
