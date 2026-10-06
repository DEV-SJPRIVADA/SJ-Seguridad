<x-app-layout>
    <x-slot name="header">
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
    </x-slot>

    <div class="page-section plantillas-word-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert--danger">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert--danger">
                    <ul class="plantillas-word-page__error-list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($activeTab === 'tipos')
                <div class="panel plantillas-word-page__panel">
                    <div class="panel__header panel__header--compact">
                        <div class="plantillas-word-page__header-row">
                            <h3 class="panel-title">Tipos de documento</h3>
                        </div>
                    </div>
                    <div class="panel__body section-stack">
                        @if ($canManage)
                            <section class="plantillas-word-form__section">
                                <header class="plantillas-word-form__section-head">
                                    <span class="plantillas-word-form__section-step">1</span>
                                    <div>
                                        <h4 class="plantillas-word-form__section-title">Agregar tipo</h4>
                                        <p class="plantillas-word-form__section-desc">Defina codigo unico, nombre visible y orden de aparicion.</p>
                                    </div>
                                </header>

                                <form
                                    method="POST"
                                    action="{{ route('gestion-humana.plantillas-word.types.store') }}"
                                    class="plantillas-word-form__create"
                                >
                                    @csrf
                                    <div class="plantillas-word-form__create-row">
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
                                        <div class="form-field plantillas-word-form__field--grow">
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
                                        <div class="form-field plantillas-word-form__field--sort">
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
                                        <div class="plantillas-word-form__create-actions">
                                            <label class="plantillas-word-form__check">
                                                <input type="checkbox" name="is_active" value="1" class="form-check" @checked(old('is_active', true))>
                                                <span>Activo</span>
                                            </label>
                                            <button
                                                type="submit"
                                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                                title="Agregar tipo"
                                                aria-label="Agregar tipo"
                                            >
                                                <x-lucide-plus width="16" height="16" aria-hidden="true" />
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </section>
                        @endif

                        <div class="data-table-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Codigo</th>
                                        <th>Nombre</th>
                                        <th class="plantillas-word-page__col-sort">Orden</th>
                                        <th class="plantillas-word-page__col-status">Estado</th>
                                        <th class="plantillas-word-page__col-count">Plantillas</th>
                                        @if ($canManage)
                                            <th class="plantillas-word-page__col-actions">Acciones</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($types as $type)
                                        <tr>
                                            <td><code class="plantillas-word-page__code">{{ $type->code }}</code></td>
                                            <td><span class="plantillas-word-page__name">{{ $type->name }}</span></td>
                                            <td>{{ $type->sort_order }}</td>
                                            <td>
                                                <span class="status-pill {{ $type->is_active ? 'status-pill--success' : 'status-pill--muted' }}">
                                                    {{ $type->is_active ? 'Activo' : 'Inactivo' }}
                                                </span>
                                            </td>
                                            <td>{{ $type->templates_count }}</td>
                                            @if ($canManage)
                                                <td class="table-actions">
                                                    <div class="plantillas-word-page__row-actions">
                                                        <button
                                                            type="button"
                                                            class="cursos-catalogo-page__icon-btn btn-plantillas-word-type-edit"
                                                            title="Editar"
                                                            aria-label="Editar"
                                                            data-code="{{ $type->code }}"
                                                            data-name="{{ $type->name }}"
                                                            data-active="{{ $type->is_active ? '1' : '0' }}"
                                                            data-sort="{{ $type->sort_order }}"
                                                            data-update-url="{{ route('gestion-humana.plantillas-word.types.update', $type) }}"
                                                        >
                                                            <x-lucide-pencil width="16" height="16" aria-hidden="true" />
                                                        </button>
                                                        @if ($type->templates_count === 0)
                                                            <form
                                                                method="POST"
                                                                action="{{ route('gestion-humana.plantillas-word.types.destroy', $type) }}"
                                                                class="plantillas-word-page__icon-form"
                                                                onsubmit="return confirm('Eliminar este tipo de documento?')"
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
                                                        @else
                                                            <span class="text-muted text-caption" title="Desactive el tipo en su lugar">Con plantillas</span>
                                                        @endif
                                                    </div>
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
                <div class="panel plantillas-word-page__panel">
                    <div class="panel__header panel__header--compact">
                        <div class="plantillas-word-page__header-row">
                            <h3 class="panel-title">Plantillas</h3>
                            @if (! empty($placeholders))
                                <button
                                    type="button"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Ver variables"
                                    aria-label="Ver variables"
                                    x-data=""
                                    x-on:click="$dispatch('open-modal', 'plantillas-word-variables')"
                                >
                                    <x-lucide-braces width="16" height="16" aria-hidden="true" />
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="panel__body section-stack">
                        @php
                            $hasActiveFilters = ($filters['q'] ?? '') !== ''
                                || ($filters['type'] ?? '') !== ''
                                || ($filters['file'] ?? '') !== '';
                        @endphp

                        @if ($canManage)
                            <section class="plantillas-word-form__section">
                                <header class="plantillas-word-form__section-head">
                                    <span class="plantillas-word-form__section-step">1</span>
                                    <div>
                                        <h4 class="plantillas-word-form__section-title">Agregar plantilla</h4>
                                        <p class="plantillas-word-form__section-desc">Etiqueta, tipo activo y archivo master .docx (puede arrastrar y soltar).</p>
                                    </div>
                                </header>

                                <form
                                    method="POST"
                                    action="{{ route('gestion-humana.plantillas-word.templates.store') }}"
                                    enctype="multipart/form-data"
                                    class="plantillas-word-form__create"
                                >
                                    @csrf
                                    <div class="plantillas-word-form__create-grid">
                                        <div class="form-field plantillas-word-form__field--grow">
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
                                            <x-searchable-select
                                                id="template_type_new"
                                                name="word_document_type_id"
                                                :options="$activeTypes->map(fn ($type) => [
                                                    'value' => (string) $type->id,
                                                    'label' => $type->code.' — '.$type->name,
                                                ])->values()->all()"
                                                :value="old('word_document_type_id')"
                                                placeholder="Seleccione tipo…"
                                                searchPlaceholder="Buscar tipo…"
                                                :required="true"
                                            />
                                        </div>
                                        <div class="form-field plantillas-word-form__field--sort">
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
                                        <div class="form-field plantillas-word-form__field--file">
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
                                                <label for="template_file_new" class="plantillas-word-file-picker__zone">
                                                    <span class="plantillas-word-file-picker__icon" aria-hidden="true">
                                                        <x-lucide-file-up width="18" height="18" />
                                                    </span>
                                                    <span class="plantillas-word-file-picker__copy">
                                                        <span class="plantillas-word-file-picker__title">Arrastre o seleccione archivo</span>
                                                        <span class="plantillas-word-file-picker__hint">Solo .docx · máximo 5 MB</span>
                                                    </span>
                                                </label>
                                                <span class="plantillas-word-file-picker__name" data-plantillas-word-file-name>Sin archivo seleccionado</span>
                                            </div>
                                        </div>
                                        <div class="plantillas-word-form__create-actions">
                                            <button
                                                type="submit"
                                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                                title="Agregar plantilla"
                                                aria-label="Agregar plantilla"
                                            >
                                                <x-lucide-plus width="16" height="16" aria-hidden="true" />
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </section>
                        @endif

                        <form
                            method="GET"
                            action="{{ route('gestion-humana.plantillas-word.index') }}"
                            class="plantillas-word-page__filters"
                        >
                            <input type="hidden" name="tab" value="plantillas">

                            <div class="plantillas-word-page__filters-field plantillas-word-page__filters-field--search">
                                <label class="sr-only" for="plantillas-word-filter-q">Buscar</label>
                                <input
                                    id="plantillas-word-filter-q"
                                    type="search"
                                    name="q"
                                    class="form-input"
                                    value="{{ $filters['q'] ?? '' }}"
                                    placeholder="Buscar por etiqueta…"
                                >
                            </div>

                            <div class="plantillas-word-page__filters-field plantillas-word-page__filters-field--type">
                                <label class="sr-only" for="plantillas-word-filter-type">Tipo</label>
                                <x-searchable-select
                                    id="plantillas-word-filter-type"
                                    name="type"
                                    :options="$types->map(fn ($type) => [
                                        'value' => (string) $type->id,
                                        'label' => $type->code.' — '.$type->name,
                                    ])->values()->all()"
                                    :value="$filters['type'] ?? ''"
                                    placeholder="Todos los tipos"
                                    searchPlaceholder="Buscar tipo…"
                                    :allowClear="true"
                                />
                            </div>

                            <div class="plantillas-word-page__filters-field plantillas-word-page__filters-field--file">
                                <label class="sr-only" for="plantillas-word-filter-file">Archivo</label>
                                <x-searchable-select
                                    id="plantillas-word-filter-file"
                                    name="file"
                                    :options="[
                                        ['value' => '', 'label' => 'Cualquier archivo'],
                                        ['value' => 'cargada', 'label' => 'Cargada'],
                                        ['value' => 'pendiente', 'label' => 'Pendiente'],
                                    ]"
                                    :value="$filters['file'] ?? ''"
                                    placeholder="Archivo…"
                                    searchPlaceholder="Buscar…"
                                    :allowClear="true"
                                />
                            </div>

                            <div class="plantillas-word-page__filters-actions">
                                <button
                                    type="submit"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                    title="Filtrar"
                                    aria-label="Filtrar"
                                >
                                    <x-lucide-search width="16" height="16" aria-hidden="true" />
                                </button>
                                @if ($hasActiveFilters)
                                    <a
                                        href="{{ route('gestion-humana.plantillas-word.index', ['tab' => 'plantillas']) }}"
                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                        title="Limpiar filtros"
                                        aria-label="Limpiar filtros"
                                    >
                                        <x-lucide-x width="16" height="16" aria-hidden="true" />
                                    </a>
                                @endif
                            </div>
                        </form>

                        @if ($hasActiveFilters)
                            <p class="plantillas-word-page__filters-meta text-caption text-muted">
                                {{ $templates->count() }} resultado{{ $templates->count() === 1 ? '' : 's' }}
                                @if (($filters['q'] ?? '') !== '')
                                    · Etiqueta: <strong>{{ $filters['q'] }}</strong>
                                @endif
                                @if (($filters['type'] ?? '') !== '')
                                    @php $filterType = $types->firstWhere('id', (int) $filters['type']); @endphp
                                    @if ($filterType)
                                        · Tipo: <strong>{{ $filterType->name }}</strong>
                                    @endif
                                @endif
                                @if (($filters['file'] ?? '') === 'cargada')
                                    · Archivo: <strong>Cargada</strong>
                                @elseif (($filters['file'] ?? '') === 'pendiente')
                                    · Archivo: <strong>Pendiente</strong>
                                @endif
                            </p>
                        @endif

                        <div class="data-table-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Etiqueta</th>
                                        <th>Tipo</th>
                                        <th class="plantillas-word-page__col-sort">Orden</th>
                                        <th class="plantillas-word-page__col-file">Archivo</th>
                                        <th class="plantillas-word-page__col-actions-wide">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($templates as $template)
                                        <tr>
                                            <td><span class="plantillas-word-page__name">{{ $template->label }}</span></td>
                                            <td>
                                                @if ($template->type)
                                                    {{ $template->type->name }}
                                                    <span class="text-muted">(<code class="plantillas-word-page__code">{{ $template->type->code }}</code>)</span>
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
                                            <td class="table-actions">
                                                <div class="plantillas-word-page__row-actions plantillas-word-page__row-actions--templates">
                                                    @if ($template->hasTemplateFile())
                                                        <a
                                                            href="{{ route('gestion-humana.plantillas-word.templates.download', $template) }}"
                                                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                                            title="Descargar"
                                                            aria-label="Descargar"
                                                        >
                                                            <x-lucide-download width="16" height="16" aria-hidden="true" />
                                                        </a>
                                                    @endif
                                                    @if ($canManage)
                                                        <button
                                                            type="button"
                                                            class="cursos-catalogo-page__icon-btn btn-plantillas-word-template-edit"
                                                            title="Editar"
                                                            aria-label="Editar"
                                                            data-label="{{ $template->label }}"
                                                            data-type-id="{{ $template->word_document_type_id }}"
                                                            data-sort="{{ $template->sort_order }}"
                                                            data-update-url="{{ route('gestion-humana.plantillas-word.templates.update', $template) }}"
                                                        >
                                                            <x-lucide-pencil width="16" height="16" aria-hidden="true" />
                                                        </button>
                                                        <form
                                                            method="POST"
                                                            action="{{ route('gestion-humana.plantillas-word.templates.replace', $template) }}"
                                                            enctype="multipart/form-data"
                                                            class="plantillas-word-page__replace-form"
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
                                                                <label
                                                                    for="template_file_replace_{{ $template->id }}"
                                                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost plantillas-word-file-picker__select-btn"
                                                                    title="Seleccionar archivo"
                                                                    aria-label="Seleccionar archivo"
                                                                >
                                                                    <x-lucide-file-up width="16" height="16" aria-hidden="true" />
                                                                </label>
                                                                <span class="plantillas-word-file-picker__name" data-plantillas-word-file-name hidden>Sin archivo</span>
                                                            </div>
                                                            <button
                                                                type="submit"
                                                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                                                title="Reemplazar"
                                                                aria-label="Reemplazar"
                                                            >
                                                                <x-lucide-replace width="16" height="16" aria-hidden="true" />
                                                            </button>
                                                        </form>
                                                        <form
                                                            method="POST"
                                                            action="{{ route('gestion-humana.plantillas-word.templates.destroy', $template) }}"
                                                            class="plantillas-word-page__icon-form"
                                                            onsubmit="return confirm('Eliminar esta plantilla Word? Se borrara el archivo y el registro.');"
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
                                                    @endif
                                                </div>
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
            var isDocxFile = function (file) {
                if (! file) {
                    return false;
                }

                var name = String(file.name || '').toLowerCase();
                var type = String(file.type || '').toLowerCase();

                return name.endsWith('.docx')
                    || type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            };

            document.querySelectorAll('[data-plantillas-word-file]').forEach(function (input) {
                var picker = input.closest('.plantillas-word-file-picker');
                var nameEl = picker ? picker.querySelector('[data-plantillas-word-file-name]') : null;
                var label = picker ? picker.querySelector('label') : null;
                var isCompact = picker && picker.classList.contains('plantillas-word-file-picker--compact');
                var dragDepth = 0;

                // Marca visual cuando hay .docx elegido (icono compacto o zona de alta).
                var syncFileState = function () {
                    var file = input.files && input.files[0];
                    var fileName = file ? file.name : '';
                    var hasFile = !!file;

                    if (picker) {
                        picker.classList.toggle('plantillas-word-file-picker--has-file', hasFile);
                    }

                    if (nameEl && ! isCompact) {
                        nameEl.textContent = fileName || 'Sin archivo seleccionado';
                    }

                    if (label && isCompact) {
                        label.title = hasFile ? ('Archivo: ' + fileName) : 'Seleccionar archivo';
                        label.setAttribute('aria-label', hasFile ? ('Archivo seleccionado: ' + fileName) : 'Seleccionar archivo');
                        label.classList.toggle('req-manage-filters__icon-btn--primary', hasFile);
                        label.classList.toggle('req-manage-filters__icon-btn--ghost', ! hasFile);
                    }
                };

                var assignFile = function (file) {
                    if (! isDocxFile(file)) {
                        window.alert('Solo se permiten archivos .docx.');
                        return;
                    }

                    var transfer = new DataTransfer();
                    transfer.items.add(file);
                    input.files = transfer.files;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                };

                input.addEventListener('change', syncFileState);
                syncFileState();

                if (! picker || isCompact) {
                    return;
                }

                // Arrastrar y soltar sobre la zona de carga (alta de plantilla).
                ['dragenter', 'dragover'].forEach(function (eventName) {
                    picker.addEventListener(eventName, function (event) {
                        event.preventDefault();
                        event.stopPropagation();
                        if (eventName === 'dragenter') {
                            dragDepth += 1;
                        }
                        picker.classList.add('plantillas-word-file-picker--dragover');
                    });
                });

                picker.addEventListener('dragleave', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    dragDepth = Math.max(0, dragDepth - 1);
                    if (dragDepth === 0) {
                        picker.classList.remove('plantillas-word-file-picker--dragover');
                    }
                });

                picker.addEventListener('drop', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    dragDepth = 0;
                    picker.classList.remove('plantillas-word-file-picker--dragover');

                    var file = event.dataTransfer && event.dataTransfer.files
                        ? event.dataTransfer.files[0]
                        : null;
                    assignFile(file);
                });
            });
        })();
    </script>

    @if ($canManage)
        <div id="plantillas-word-type-modal" class="ficha-empleados-catalogs-page__modal" hidden>
            <div class="ficha-empleados-catalogs-page__modal-backdrop" data-type-modal-close></div>
            <div
                class="panel ficha-empleados-catalogs-page__modal-card"
                role="dialog"
                aria-modal="true"
                aria-labelledby="plantillas-word-type-modal-title"
            >
                <div class="ficha-empleados-catalogs-page__modal-header">
                    <div class="ficha-empleados-catalogs-page__modal-heading">
                        <span class="ficha-empleados-catalogs-page__modal-heading-icon" aria-hidden="true">
                            <x-lucide-pencil width="20" height="20" />
                        </span>
                        <div>
                            <h3 class="ficha-empleados-catalogs-page__modal-title" id="plantillas-word-type-modal-title">Editar tipo</h3>
                            <p class="ficha-empleados-catalogs-page__modal-lead">
                                Actualice código, nombre, orden y si el tipo permanece activo.
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="ficha-empleados-catalogs-page__modal-close"
                        data-type-modal-close
                        title="Cerrar"
                        aria-label="Cerrar"
                    >
                        <x-lucide-x width="18" height="18" aria-hidden="true" />
                    </button>
                </div>
                <form method="POST" id="plantillas-word-type-edit-form" class="ficha-empleados-catalogs-page__modal-body">
                    @csrf
                    @method('PATCH')
                    <div class="ficha-empleados-catalogs-page__modal-fields">
                        <div class="form-field">
                            <label class="form-label" for="plantillas-word-edit-code">Código</label>
                            <input id="plantillas-word-edit-code" name="code" type="text" class="form-input" maxlength="50" required>
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="plantillas-word-edit-name">Nombre</label>
                            <input id="plantillas-word-edit-name" name="name" type="text" class="form-input" maxlength="255" required>
                        </div>
                        <div class="ficha-empleados-catalogs-page__modal-meta">
                            <div class="form-field">
                                <label class="form-label" for="plantillas-word-edit-sort">Orden</label>
                                <input id="plantillas-word-edit-sort" name="sort_order" type="number" min="0" max="9999" class="form-input">
                            </div>
                            <label class="ficha-empleados-catalogs-page__modal-active" for="plantillas-word-edit-active">
                                <input type="checkbox" id="plantillas-word-edit-active" name="is_active" value="1" class="form-check">
                                <span>
                                    <span class="ficha-empleados-catalogs-page__modal-active-title">Activo</span>
                                    <span class="ficha-empleados-catalogs-page__modal-active-text">Si está inactivo no se ofrece al elegir plantilla.</span>
                                </span>
                            </label>
                        </div>
                    </div>
                    <div class="ficha-empleados-catalogs-page__modal-actions">
                        <button
                            type="button"
                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                            data-type-modal-close
                            title="Cancelar"
                            aria-label="Cancelar"
                        >
                            <x-lucide-x width="18" height="18" aria-hidden="true" />
                        </button>
                        <button
                            type="submit"
                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                            title="Guardar"
                            aria-label="Guardar"
                        >
                            <x-lucide-save width="18" height="18" aria-hidden="true" />
                        </button>
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

        {{-- Modal Editar plantilla: etiqueta, tipo y orden --}}
        <div id="plantillas-word-template-modal" class="ficha-empleados-catalogs-page__modal plantillas-word-edit-modal-shell" hidden>
            <div class="ficha-empleados-catalogs-page__modal-backdrop" data-template-modal-close></div>
            <div class="panel plantillas-word-edit-modal" role="dialog" aria-modal="true" aria-labelledby="plantillas-word-template-modal-title">
                <div class="plantillas-word-edit-modal__header">
                    <div class="plantillas-word-edit-modal__heading">
                        <span class="plantillas-word-edit-modal__heading-icon" aria-hidden="true">
                            <x-lucide-file-pen-line width="18" height="18" />
                        </span>
                        <div class="plantillas-word-edit-modal__heading-copy">
                            <h3 id="plantillas-word-template-modal-title" class="plantillas-word-edit-modal__title">Editar plantilla</h3>
                            <p class="plantillas-word-edit-modal__lead">
                                Actualice la etiqueta, el tipo de documento y el orden de visualización. El archivo .docx se reemplaza desde la fila.
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="plantillas-word-edit-modal__close"
                        data-template-modal-close
                        title="Cerrar"
                        aria-label="Cerrar"
                    >
                        <x-lucide-x width="18" height="18" aria-hidden="true" />
                    </button>
                </div>

                <form method="POST" id="plantillas-word-template-edit-form" class="plantillas-word-edit-modal__form">
                    @csrf
                    @method('PATCH')

                    <div class="plantillas-word-edit-modal__body">
                        <div class="plantillas-word-edit-modal__field plantillas-word-edit-modal__field--full">
                            <label class="form-label" for="plantillas-word-edit-template-label">Etiqueta</label>
                            <input
                                id="plantillas-word-edit-template-label"
                                name="label"
                                type="text"
                                class="form-input"
                                maxlength="255"
                                required
                                autocomplete="off"
                            >
                            <p class="form-hint">Nombre visible al generar cartas.</p>
                        </div>

                        <div class="plantillas-word-edit-modal__field plantillas-word-edit-modal__field--type">
                            <label class="form-label" for="plantillas-word-edit-template-type">Tipo</label>
                            <x-searchable-select
                                id="plantillas-word-edit-template-type"
                                name="word_document_type_id"
                                :options="$types->map(fn ($type) => [
                                    'value' => (string) $type->id,
                                    'label' => $type->code.' — '.$type->name.($type->is_active ? '' : ' (inactivo)'),
                                ])->values()->all()"
                                value=""
                                placeholder="Seleccione tipo…"
                                searchPlaceholder="Buscar tipo…"
                                :required="true"
                                :allowClear="false"
                            />
                        </div>

                        <div class="plantillas-word-edit-modal__field plantillas-word-edit-modal__field--sort">
                            <label class="form-label" for="plantillas-word-edit-template-sort">Orden</label>
                            <input
                                id="plantillas-word-edit-template-sort"
                                name="sort_order"
                                type="number"
                                min="0"
                                max="9999"
                                class="form-input"
                            >
                            <p class="form-hint">Menor número aparece primero.</p>
                        </div>
                    </div>

                    <div class="plantillas-word-edit-modal__actions">
                        <button type="button" class="btn btn--secondary btn--sm" data-template-modal-close>Cancelar</button>
                        <button type="submit" class="btn btn--primary btn--sm plantillas-word-edit-modal__submit">
                            <x-lucide-save width="16" height="16" aria-hidden="true" />
                            <span>Guardar cambios</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            (function () {
                var modal = document.getElementById('plantillas-word-template-modal');
                var editForm = document.getElementById('plantillas-word-template-edit-form');
                var editLabel = document.getElementById('plantillas-word-edit-template-label');
                var editSort = document.getElementById('plantillas-word-edit-template-sort');
                var typeInput = document.getElementById('plantillas-word-edit-template-type');

                function closeModal() {
                    if (modal) {
                        modal.hidden = true;
                    }
                }

                function setSearchableSelectValue(hiddenInput, value) {
                    if (! hiddenInput) {
                        return;
                    }

                    var wrap = hiddenInput.closest('[x-data]');
                    if (! wrap || ! window.Alpine || typeof Alpine.$data !== 'function') {
                        hiddenInput.value = value;
                        return;
                    }

                    var data = Alpine.$data(wrap);
                    var nextValue = value !== null && value !== undefined ? String(value) : '';
                    data.value = nextValue;

                    var found = Array.isArray(data.options)
                        ? data.options.find(function (opt) {
                            return String(opt.value) === nextValue;
                        })
                        : null;
                    data.selectedLabel = found && String(found.value) !== '' ? found.label : '';

                    if (data.$refs && data.$refs.hiddenInput) {
                        data.$refs.hiddenInput.value = nextValue;
                    } else {
                        hiddenInput.value = nextValue;
                    }
                }

                document.querySelectorAll('[data-template-modal-close]').forEach(function (button) {
                    button.addEventListener('click', closeModal);
                });

                document.querySelectorAll('.btn-plantillas-word-template-edit').forEach(function (button) {
                    button.addEventListener('click', function () {
                        editForm.action = button.getAttribute('data-update-url');
                        editLabel.value = button.getAttribute('data-label') || '';
                        editSort.value = button.getAttribute('data-sort') || '0';
                        setSearchableSelectValue(typeInput, button.getAttribute('data-type-id') || '');
                        modal.hidden = false;
                    });
                });
            })();
        </script>
    @endif

    @if (! empty($placeholders))
        @php
            $placeholderCatalog = collect($placeholders)
                ->map(static function (array $items, string $category): array {
                    return [
                        'category' => $category,
                        'items' => collect($items)
                            ->map(static fn (string $description, string $key): array => [
                                'key' => $key,
                                'token' => '${'.$key.'}',
                                'description' => $description,
                            ])
                            ->values()
                            ->all(),
                    ];
                })
                ->values()
                ->all();
        @endphp

        <x-modal name="plantillas-word-variables" maxWidth="lg" focusable>
            <div
                class="modal-card ficha-empleados-consult-modal plantillas-word-variables-modal"
                x-data="plantillasWordVariablesModal(@js($placeholderCatalog))"
                x-on:open-modal.window="if ($event.detail === 'plantillas-word-variables') { focusFilter(); }"
            >
                <div class="ficha-empleados-consult-modal__header">
                    <div class="ficha-empleados-consult-modal__heading">
                        <span class="ficha-empleados-consult-modal__heading-icon" aria-hidden="true">
                            <x-lucide-braces width="18" height="18" />
                        </span>
                        <div class="ficha-empleados-consult-modal__heading-copy">
                            <div class="ficha-empleados-consult-modal__title-row">
                                <h3 class="ficha-empleados-consult-modal__title">Variables disponibles</h3>
                                <span
                                    class="ficha-empleados-consult-modal__count"
                                    x-text="visibleCount"
                                    title="Variables visibles"
                                ></span>
                            </div>
                            <p class="ficha-empleados-consult-modal__lead">
                                Placeholders para plantillas Word. Copie el formato <code>${CLAVE}</code>.
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="ficha-empleados-consult-modal__close"
                        title="Cerrar"
                        aria-label="Cerrar"
                        x-on:click="$dispatch('close-modal', 'plantillas-word-variables')"
                    >
                        <x-lucide-x width="18" height="18" aria-hidden="true" />
                    </button>
                </div>

                {{-- Filtro por clave, token o descripción --}}
                <div class="plantillas-word-variables-modal__filter">
                    <label class="sr-only" for="plantillas-word-variables-filter">Filtrar variables</label>
                    <div class="plantillas-word-variables-modal__filter-row">
                        <span class="plantillas-word-variables-modal__filter-icon" aria-hidden="true">
                            <x-lucide-search width="16" height="16" />
                        </span>
                        <input
                            id="plantillas-word-variables-filter"
                            type="search"
                            class="form-input plantillas-word-variables-modal__filter-input"
                            placeholder="Filtrar por clave o descripción…"
                            autocomplete="off"
                            x-ref="filterInput"
                            x-model="query"
                        >
                        <button
                            type="button"
                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                            title="Limpiar filtro"
                            aria-label="Limpiar filtro"
                            x-show="query.trim() !== ''"
                            x-cloak
                            x-on:click="query = ''; focusFilter();"
                        >
                            <x-lucide-x width="16" height="16" aria-hidden="true" />
                        </button>
                    </div>
                </div>

                <div class="plantillas-word-variables-modal__scroll" role="region" aria-label="Listado de variables">
                    <p class="plantillas-word-variables-modal__empty" x-show="filteredGroups.length === 0" x-cloak>
                        No hay variables que coincidan con el filtro.
                    </p>

                    <div class="ficha-empleados-letter-templates__placeholder-groups" x-show="filteredGroups.length > 0">
                        <template x-for="group in filteredGroups" :key="group.category">
                            <div class="ficha-empleados-letter-templates__placeholder-group">
                                <h4 class="ficha-empleados-letter-templates__category-title" x-text="group.category"></h4>
                                <ul class="ficha-empleados-letter-templates__placeholder-list">
                                    <template x-for="item in group.items" :key="item.key">
                                        <li>
                                            <code x-text="item.token"></code>
                                            <span> — </span>
                                            <span x-text="item.description"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </x-modal>

        @once
            @push('scripts')
                <script>
                    function plantillasWordVariablesModal(groups) {
                        return {
                            groups: Array.isArray(groups) ? groups : [],
                            query: '',

                            get filteredGroups() {
                                const needle = this.query.trim().toLowerCase();
                                if (! needle) {
                                    return this.groups;
                                }

                                return this.groups
                                    .map((group) => {
                                        const items = (group.items || []).filter((item) => {
                                            const haystack = [
                                                group.category || '',
                                                item.key || '',
                                                item.token || '',
                                                item.description || '',
                                            ].join(' ').toLowerCase();

                                            return haystack.includes(needle);
                                        });

                                        return { category: group.category, items };
                                    })
                                    .filter((group) => group.items.length > 0);
                            },

                            get visibleCount() {
                                return this.filteredGroups.reduce((total, group) => total + group.items.length, 0);
                            },

                            focusFilter() {
                                this.$nextTick(() => {
                                    this.$refs.filterInput?.focus();
                                });
                            },
                        };
                    }
                </script>
            @endpush
        @endonce
    @endif
</x-app-layout>
