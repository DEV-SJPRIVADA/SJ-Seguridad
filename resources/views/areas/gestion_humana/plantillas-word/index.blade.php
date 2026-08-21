<x-app-layout>
    <x-slot name="header">
        <div class="app-container">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">Plantillas Word</h2>
                <p class="panel-text">Gestion humana — tipos de documento y plantillas .docx</p>
            </div>
        </div>
    </x-slot>

    <div class="module-subnav requisition-subtabs">
        <div class="app-container">
            <div class="module-subnav__inner requisition-subtabs__inner">
                <p class="text-caption module-subnav__label">Plantillas Word</p>
                <nav class="module-tabs" aria-label="Plantillas Word">
                    @foreach ($subTabs as $tab)
                        <a href="{{ $tab['url'] }}" class="module-tab {{ $tab['active'] ? 'module-tab--active' : '' }}">
                            {{ $tab['label'] }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </div>
    </div>

    <div class="page-section ficha-empleados-catalogs-page">
        <div class="app-container section-stack">
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

            @if ($activeTab === 'tipos')
                <div class="panel">
                    <div class="panel__header">
                        <h3 class="panel-title">Tipos de documento</h3>
                        <p class="panel-text">Catalogo editable (codigo, nombre, estado y orden). No elimine un tipo con plantillas asociadas: desactivelo.</p>
                    </div>
                    <div class="panel__body section-stack">
                        @if ($canManage)
                            <form
                                method="POST"
                                action="{{ route('gestion-humana.plantillas-word.types.store') }}"
                                class="ficha-empleados-catalogs-page__create-form"
                            >
                                @csrf
                                <div class="ficha-empleados-catalogs-page__create-row">
                                    <div class="form-field">
                                        <label class="form-label" for="type_code_new">Codigo</label>
                                        <input
                                            id="type_code_new"
                                            name="code"
                                            type="text"
                                            class="form-input"
                                            maxlength="50"
                                            required
                                            value="{{ old('code') }}"
                                            placeholder="Ej. desvinculacion"
                                        >
                                    </div>
                                    <div class="form-field ficha-empleados-catalogs-page__name-field">
                                        <label class="form-label" for="type_name_new">Nombre</label>
                                        <input
                                            id="type_name_new"
                                            name="name"
                                            type="text"
                                            class="form-input"
                                            maxlength="255"
                                            required
                                            value="{{ old('name') }}"
                                            placeholder="Nombre visible"
                                        >
                                    </div>
                                    <div class="form-field ficha-empleados-catalogs-page__sort-field">
                                        <label class="form-label" for="type_sort_new">Orden</label>
                                        <input
                                            id="type_sort_new"
                                            name="sort_order"
                                            type="number"
                                            min="0"
                                            max="9999"
                                            class="form-input"
                                            value="{{ old('sort_order', 0) }}"
                                        >
                                    </div>
                                    <div class="ficha-empleados-catalogs-page__create-actions">
                                        <label class="ficha-empleados-catalogs-page__active-check">
                                            <input type="checkbox" name="is_active" value="1" class="form-check" @checked(old('is_active', true))>
                                            <span>Activo</span>
                                        </label>
                                        <button type="submit" class="btn btn--primary btn--sm">Agregar tipo</button>
                                    </div>
                                </div>
                            </form>
                        @endif

                        <div class="data-table-wrap">
                            <table class="data-table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Codigo</th>
                                        <th>Nombre</th>
                                        <th style="width:80px;">Orden</th>
                                        <th style="width:100px;">Estado</th>
                                        <th style="width:90px;">Plantillas</th>
                                        @if ($canManage)
                                            <th style="width:200px;">Acciones</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($types as $type)
                                        <tr>
                                            <td><code>{{ $type->code }}</code></td>
                                            <td>{{ $type->name }}</td>
                                            <td>{{ $type->sort_order }}</td>
                                            <td>
                                                <span class="status-pill {{ $type->is_active ? 'status-pill--success' : 'status-pill--muted' }}">
                                                    {{ $type->is_active ? 'Activo' : 'Inactivo' }}
                                                </span>
                                            </td>
                                            <td>{{ $type->templates_count }}</td>
                                            @if ($canManage)
                                                <td class="table-actions">
                                                    <button
                                                        type="button"
                                                        class="btn btn--secondary btn--sm btn-plantillas-word-type-edit"
                                                        data-code="{{ $type->code }}"
                                                        data-name="{{ $type->name }}"
                                                        data-active="{{ $type->is_active ? '1' : '0' }}"
                                                        data-sort="{{ $type->sort_order }}"
                                                        data-update-url="{{ route('gestion-humana.plantillas-word.types.update', $type) }}"
                                                    >Editar</button>
                                                    @if ($type->templates_count === 0)
                                                        <form
                                                            method="POST"
                                                            action="{{ route('gestion-humana.plantillas-word.types.destroy', $type) }}"
                                                            class="ficha-empleados-catalogs-page__delete-form"
                                                            onsubmit="return confirm('Eliminar este tipo de documento?')"
                                                        >
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn--danger btn--sm">Eliminar</button>
                                                        </form>
                                                    @else
                                                        <span class="text-muted text-caption" title="Desactive el tipo en su lugar">Con plantillas</span>
                                                    @endif
                                                </td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $canManage ? 6 : 5 }}" class="text-muted">Sin tipos de documento.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <div class="panel">
                    <div class="panel__header">
                        <h3 class="panel-title">Plantillas</h3>
                        <p class="panel-text">
                            Agregue etiqueta + tipo activo + archivo <code>.docx</code>. Reemplazar solo cambia el archivo; eliminar pide confirmacion.
                        </p>
                    </div>
                    <div class="panel__body section-stack">
                        @if (! empty($placeholders))
                            <details class="ficha-empleados-letter-templates__placeholders">
                                <summary class="text-caption">Variables disponibles (placeholders)</summary>
                                <ul class="ficha-empleados-letter-templates__placeholder-list">
                                    @foreach ($placeholders as $key => $description)
                                        <li><code>[{{ $key }}]</code> — {{ $description }}</li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif

                        @if ($canManage)
                            <form
                                method="POST"
                                action="{{ route('gestion-humana.plantillas-word.templates.store') }}"
                                enctype="multipart/form-data"
                                class="ficha-empleados-catalogs-page__create-form"
                            >
                                @csrf
                                <div class="ficha-empleados-catalogs-page__create-row">
                                    <div class="form-field ficha-empleados-catalogs-page__name-field">
                                        <label class="form-label" for="template_label_new">Etiqueta</label>
                                        <input
                                            id="template_label_new"
                                            name="label"
                                            type="text"
                                            class="form-input"
                                            maxlength="255"
                                            required
                                            value="{{ old('label') }}"
                                            placeholder="Ej. Aceptacion de renuncia"
                                        >
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="template_type_new">Tipo</label>
                                        <select id="template_type_new" name="word_document_type_id" class="form-select" required>
                                            <option value="">Seleccione…</option>
                                            @foreach ($activeTypes as $activeType)
                                                <option value="{{ $activeType->id }}" @selected((string) old('word_document_type_id') === (string) $activeType->id)>
                                                    {{ $activeType->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-field ficha-empleados-catalogs-page__sort-field">
                                        <label class="form-label" for="template_sort_new">Orden</label>
                                        <input
                                            id="template_sort_new"
                                            name="sort_order"
                                            type="number"
                                            min="0"
                                            max="9999"
                                            class="form-input"
                                            value="{{ old('sort_order', 0) }}"
                                        >
                                    </div>
                                    <div class="form-field">
                                        <span class="form-label" id="template_file_new_label">Archivo .docx</span>
                                        <div class="plantillas-word-file-picker">
                                            <input
                                                id="template_file_new"
                                                name="template"
                                                type="file"
                                                class="plantillas-word-file-picker__input"
                                                accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                                required
                                                data-plantillas-word-file
                                                aria-labelledby="template_file_new_label"
                                            >
                                            <label for="template_file_new" class="btn btn--secondary btn--sm">Seleccionar archivo</label>
                                            <span class="plantillas-word-file-picker__name" data-plantillas-word-file-name>Sin archivo seleccionado</span>
                                        </div>
                                    </div>
                                    <div class="ficha-empleados-catalogs-page__create-actions">
                                        <button type="submit" class="btn btn--primary btn--sm">Agregar plantilla</button>
                                    </div>
                                </div>
                            </form>
                        @endif

                        <div class="data-table-wrap">
                            <table class="data-table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Etiqueta</th>
                                        <th>Tipo</th>
                                        <th style="width:80px;">Orden</th>
                                        <th style="width:110px;">Archivo</th>
                                        <th style="width:{{ $canManage ? '320px' : '120px' }};">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($templates as $template)
                                        <tr>
                                            <td>{{ $template->label }}</td>
                                            <td>
                                                @if ($template->type)
                                                    {{ $template->type->name }}
                                                    <span class="text-muted">(<code>{{ $template->type->code }}</code>)</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>{{ $template->sort_order }}</td>
                                            <td>
                                                @if ($template->hasTemplateFile())
                                                    <span class="status-pill status-pill--success">Cargada</span>
                                                @else
                                                    <span class="status-pill status-pill--muted">Pendiente</span>
                                                @endif
                                            </td>
                                            <td class="table-actions ficha-empleados-letter-templates__actions">
                                                @if ($template->hasTemplateFile())
                                                    <a
                                                        href="{{ route('gestion-humana.plantillas-word.templates.download', $template) }}"
                                                        class="btn btn--secondary btn--sm"
                                                    >Descargar</a>
                                                @endif
                                                @if ($canManage)
                                                    <form
                                                        method="POST"
                                                        action="{{ route('gestion-humana.plantillas-word.templates.replace', $template) }}"
                                                        enctype="multipart/form-data"
                                                        class="ficha-empleados-letter-templates__upload-form"
                                                    >
                                                        @csrf
                                                        <div class="plantillas-word-file-picker plantillas-word-file-picker--compact">
                                                            <input
                                                                id="template_file_replace_{{ $template->id }}"
                                                                type="file"
                                                                name="template"
                                                                accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                                                class="plantillas-word-file-picker__input"
                                                                required
                                                                data-plantillas-word-file
                                                            >
                                                            <label for="template_file_replace_{{ $template->id }}" class="btn btn--secondary btn--sm">Seleccionar archivo</label>
                                                            <span class="plantillas-word-file-picker__name" data-plantillas-word-file-name hidden>Sin archivo</span>
                                                        </div>
                                                        <button type="submit" class="btn btn--primary btn--sm">Reemplazar</button>
                                                    </form>
                                                    <form
                                                        method="POST"
                                                        action="{{ route('gestion-humana.plantillas-word.templates.destroy', $template) }}"
                                                        onsubmit="return confirm('Eliminar esta plantilla Word? Se borrara el archivo y el registro.');"
                                                    >
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn--danger btn--sm">Eliminar</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-muted">Sin plantillas registradas. Agregue al menos una de tipo Desvinculacion para generar cartas.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        (function () {
            document.querySelectorAll('[data-plantillas-word-file]').forEach(function (input) {
                var picker = input.closest('.plantillas-word-file-picker');
                var nameEl = picker ? picker.querySelector('[data-plantillas-word-file-name]') : null;

                input.addEventListener('change', function () {
                    if (! nameEl) {
                        return;
                    }
                    var file = input.files && input.files[0];
                    nameEl.textContent = file ? file.name : (nameEl.hidden ? 'Sin archivo' : 'Sin archivo seleccionado');
                    if (nameEl.hasAttribute('hidden') && file) {
                        nameEl.hidden = false;
                    }
                });
            });
        })();
    </script>

    @if ($canManage)
        <div id="plantillas-word-type-modal" class="ficha-empleados-catalogs-page__modal" hidden>
            <div class="ficha-empleados-catalogs-page__modal-backdrop" data-type-modal-close></div>
            <div class="panel ficha-empleados-catalogs-page__modal-card">
                <div class="panel__header panel-heading-row">
                    <h3 class="panel-title" id="plantillas-word-type-modal-title">Editar tipo</h3>
                    <button type="button" class="btn btn--secondary btn--sm" data-type-modal-close aria-label="Cerrar">✕</button>
                </div>
                <form method="POST" id="plantillas-word-type-edit-form" class="panel__body form-stack">
                    @csrf
                    @method('PATCH')
                    <div class="form-field">
                        <label class="form-label" for="plantillas-word-edit-code">Codigo</label>
                        <input id="plantillas-word-edit-code" name="code" type="text" class="form-input" maxlength="50" required>
                    </div>
                    <div class="form-field">
                        <label class="form-label" for="plantillas-word-edit-name">Nombre</label>
                        <input id="plantillas-word-edit-name" name="name" type="text" class="form-input" maxlength="255" required>
                    </div>
                    <div class="form-field">
                        <label class="form-label" for="plantillas-word-edit-sort">Orden</label>
                        <input id="plantillas-word-edit-sort" name="sort_order" type="number" min="0" max="9999" class="form-input">
                    </div>
                    <label class="ficha-empleados-catalogs-page__active-check">
                        <input type="checkbox" id="plantillas-word-edit-active" name="is_active" value="1" class="form-check">
                        <span>Activo</span>
                    </label>
                    <div class="form-actions">
                        <button type="button" class="btn btn--secondary" data-type-modal-close>Cancelar</button>
                        <button type="submit" class="btn btn--primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            (function () {
                var modal = document.getElementById('plantillas-word-type-modal');
                var editForm = document.getElementById('plantillas-word-type-edit-form');
                var editCode = document.getElementById('plantillas-word-edit-code');
                var editName = document.getElementById('plantillas-word-edit-name');
                var editSort = document.getElementById('plantillas-word-edit-sort');
                var editActive = document.getElementById('plantillas-word-edit-active');

                function closeModal() {
                    if (modal) {
                        modal.hidden = true;
                    }
                }

                document.querySelectorAll('[data-type-modal-close]').forEach(function (button) {
                    button.addEventListener('click', closeModal);
                });

                document.querySelectorAll('.btn-plantillas-word-type-edit').forEach(function (button) {
                    button.addEventListener('click', function () {
                        editForm.action = button.getAttribute('data-update-url');
                        editCode.value = button.getAttribute('data-code') || '';
                        editName.value = button.getAttribute('data-name') || '';
                        editSort.value = button.getAttribute('data-sort') || '0';
                        editActive.checked = button.getAttribute('data-active') === '1';
                        modal.hidden = false;
                    });
                });
            })();
        </script>
    @endif
</x-app-layout>
