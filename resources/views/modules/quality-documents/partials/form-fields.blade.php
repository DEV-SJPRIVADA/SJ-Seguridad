@php
    $selectedType = old('type', isset($document) ? $document->type : 'file');
    $selectedAreas = old('areas', $selectedAreas ?? []);
    $selectedUsers = collect(old('users', $selectedUsers ?? []))->map(fn ($id) => (int) $id)->all();
    $catalogs = $catalogs ?? [];
@endphp

<div class="doc-form">
    <div class="doc-form__section">
        <h4 class="doc-form__section-title">Identificacion</h4>

        <div class="form-grid form-grid--two">
            <div class="form-field">
                <label class="form-label" for="code">Codigo</label>
                <input type="text" name="code" id="code" class="form-input" value="{{ old('code', $document->code ?? '') }}" placeholder="Ej. SG-FR-001" required>
                @error('code')<p class="text-small text-danger">{{ $message }}</p>@enderror
            </div>

            <div class="form-field">
                <label class="form-label" for="title">Nombre del documento o Registro</label>
                <input type="text" name="title" id="title" class="form-input" value="{{ old('title', $document->title ?? '') }}" required>
                @error('title')<p class="text-small text-danger">{{ $message }}</p>@enderror
            </div>

            <div class="form-field">
                <label class="form-label" for="process_key">Proceso al que pertenece</label>
                <select name="process_key" id="process_key" class="form-select" required>
                    <option value="">Seleccione un proceso</option>
                    @foreach ($catalogs['processes'] ?? [] as $key => $label)
                        <option value="{{ $key }}" @selected(old('process_key', $document->process_key ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('process_key')<p class="text-small text-danger">{{ $message }}</p>@enderror
            </div>

            <div class="form-field">
                <label class="form-label" for="document_type">Tipo</label>
                <select name="document_type" id="document_type" class="form-select" required>
                    <option value="">Seleccione un tipo</option>
                    @foreach ($catalogs['types'] ?? [] as $key => $label)
                        <option value="{{ $key }}" @selected(old('document_type', $document->document_type ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('document_type')<p class="text-small text-danger">{{ $message }}</p>@enderror
            </div>

            <div class="form-field">
                <label class="form-label" for="origin">Origen</label>
                <select name="origin" id="origin" class="form-select" required>
                    <option value="">Seleccione origen</option>
                    @foreach ($catalogs['origins'] ?? [] as $key => $label)
                        <option value="{{ $key }}" @selected(old('origin', $document->origin ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('origin')<p class="text-small text-danger">{{ $message }}</p>@enderror
            </div>

            <div class="form-field">
                <label class="form-label" for="document_status">Estado del documento</label>
                <select name="document_status" id="document_status" class="form-select" required>
                    <option value="">Seleccione estado</option>
                    @foreach ($catalogs['document_statuses'] ?? [] as $key => $label)
                        <option value="{{ $key }}" @selected(old('document_status', $document->document_status ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('document_status')<p class="text-small text-danger">{{ $message }}</p>@enderror
            </div>

            <div class="form-field">
                <label class="form-label" for="activity_status">Estado de la actividad</label>
                <select name="activity_status" id="activity_status" class="form-select" required>
                    <option value="">Seleccione estado</option>
                    @foreach ($catalogs['activity_statuses'] ?? [] as $key => $label)
                        <option value="{{ $key }}" @selected(old('activity_status', $document->activity_status ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('activity_status')<p class="text-small text-danger">{{ $message }}</p>@enderror
            </div>

            <div class="form-field">
                <label class="form-label" for="storage_type">Tipo de almacenamiento</label>
                <select name="storage_type" id="storage_type" class="form-select" required>
                    <option value="">Seleccione tipo</option>
                    @foreach ($catalogs['storage_types'] ?? [] as $key => $label)
                        <option value="{{ $key }}" @selected(old('storage_type', $document->storage_type ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('storage_type')<p class="text-small text-danger">{{ $message }}</p>@enderror
            </div>

            <div class="form-field">
                <label class="form-label" for="current_version">Version actual</label>
                <input type="text" name="current_version" id="current_version" class="form-input" value="{{ old('current_version', $document->current_version ?? '') }}" placeholder="Ej. 01">
                @error('current_version')<p class="text-small text-danger">{{ $message }}</p>@enderror
            </div>

            <div class="form-field">
                <label class="form-label" for="last_updated_at">Fecha ultima actualizacion</label>
                <input type="date" name="last_updated_at" id="last_updated_at" class="form-input" value="{{ old('last_updated_at', isset($document?->last_updated_at) ? $document->last_updated_at->format('Y-m-d') : '') }}">
                @error('last_updated_at')<p class="text-small text-danger">{{ $message }}</p>@enderror
            </div>

            <div class="form-field">
                <label class="form-label" for="retention_period">Tiempo de retencion documental</label>
                <input type="text" name="retention_period" id="retention_period" class="form-input" value="{{ old('retention_period', $document->retention_period ?? '') }}" placeholder="Ej. 5 anos">
                @error('retention_period')<p class="text-small text-danger">{{ $message }}</p>@enderror
            </div>

            <div class="form-field">
                <label class="form-label" for="final_disposition">Disposicion final</label>
                <input type="text" name="final_disposition" id="final_disposition" class="form-input" value="{{ old('final_disposition', $document->final_disposition ?? '') }}" placeholder="Ej. Conservacion permanente">
                @error('final_disposition')<p class="text-small text-danger">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    <div class="doc-form__section">
        <h4 class="doc-form__section-title">Recurso</h4>

        <div class="form-field">
            <span class="form-label">Tipo de recurso</span>
            <div class="doc-form__radio-row">
                <label class="doc-form__radio {{ $selectedType === 'file' ? 'is-active' : '' }}">
                    <input type="radio" name="type" value="file" data-doc-type-toggle {{ $selectedType === 'file' ? 'checked' : '' }}>
                    <span>Archivo (Word / Excel)</span>
                </label>
                <label class="doc-form__radio {{ $selectedType === 'link' ? 'is-active' : '' }}">
                    <input type="radio" name="type" value="link" data-doc-type-toggle {{ $selectedType === 'link' ? 'checked' : '' }}>
                    <span>Enlace externo</span>
                </label>
            </div>
            @error('type')<p class="text-small text-danger">{{ $message }}</p>@enderror
        </div>

        <div class="form-field" data-doc-file-field>
            <span class="form-label">Archivo (max. 10 MB)</span>
            <div class="doc-form__dropzone" data-doc-dropzone>
                <input type="file" name="file" id="file" accept=".doc,.docx,.xls,.xlsx" data-doc-file-input>
                <div class="doc-form__dropzone-content">
                    <div class="doc-form__dropzone-icon" aria-hidden="true">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                    </div>
                    <p class="doc-form__dropzone-title">Arrastra y suelta tu archivo aqui</p>
                    <p class="doc-form__dropzone-hint">o</p>
                    <label class="doc-form__dropzone-btn" for="file">Seleccionar archivo</label>
                    <p class="doc-form__dropzone-formats">Word (.doc, .docx) y Excel (.xls, .xlsx)</p>
                </div>
                <div class="doc-form__dropzone-selected" data-doc-file-selected hidden>
                    <div class="doc-form__dropzone-file-icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                    </div>
                    <div class="doc-form__dropzone-file-info">
                        <span class="doc-form__dropzone-file-name" data-doc-file-name>Ningun archivo seleccionado</span>
                        <span class="doc-form__dropzone-file-size" data-doc-file-size></span>
                    </div>
                    <button type="button" class="doc-form__dropzone-clear" data-doc-file-clear aria-label="Quitar archivo">&times;</button>
                </div>
            </div>
            @if (!empty($document?->original_name))
                <p class="text-small text-muted doc-form__current-file">Archivo actual: <strong>{{ $document->original_name }}</strong></p>
            @endif
            @error('file')<p class="text-small text-danger">{{ $message }}</p>@enderror
        </div>

        <div class="form-field" data-doc-link-field>
            <label class="form-label" for="external_url">URL del enlace</label>
            <input type="url" name="external_url" id="external_url" class="form-input" value="{{ old('external_url', $document->external_url ?? '') }}" placeholder="https://...">
            @error('external_url')<p class="text-small text-danger">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="doc-form__section">
        <h4 class="doc-form__section-title">Visibilidad</h4>

        <div class="form-field doc-form__visibility-block">
            <div class="doc-form__visibility-header">
                <div>
                    <span class="form-label">Areas con acceso</span>
                    <p class="text-small text-muted">Areas que podran consultar y descargar el documento.</p>
                </div>
                <div class="doc-form__visibility-actions">
                    <span class="doc-form__selection-count" data-areas-count>0 seleccionadas</span>
                    <button type="button" class="doc-form__toggle-all" data-areas-toggle-all>Seleccionar todas</button>
                </div>
            </div>
            <div class="doc-form__area-grid" id="doc-areas-grid">
                @foreach ($areas as $key => $label)
                    <label class="doc-form__area-card">
                        <input type="checkbox" name="areas[]" value="{{ $key }}" class="js-doc-area-checkbox" {{ in_array($key, $selectedAreas, true) ? 'checked' : '' }}>
                        <span class="doc-form__area-card-body">
                            <span class="doc-form__area-card-check" aria-hidden="true"></span>
                            <span class="doc-form__area-card-label">{{ $label }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('areas')<p class="text-small text-danger">{{ $message }}</p>@enderror
            @error('areas.*')<p class="text-small text-danger">{{ $message }}</p>@enderror
        </div>

        <div class="form-field doc-form__visibility-block">
            <div class="doc-form__visibility-header">
                <div>
                    <span class="form-label">Usuarios especificos</span>
                    <p class="text-small text-muted">Solo los usuarios seleccionados veran el documento en su tablero personal "Mis documentos".</p>
                </div>
                <span class="doc-form__selection-count" data-users-count>0 seleccionados</span>
            </div>
            <div class="doc-form__user-search-wrap">
                <svg class="doc-form__user-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="search-doc-users" class="form-input doc-form__user-search" placeholder="Buscar por nombre, correo o area...">
            </div>
            <div class="doc-form__user-grid" id="doc-users-grid">
                @foreach ($users as $assignableUser)
                    @php
                        $areaLabel = $assignableUser->area_key
                            ? (config("access.areas.{$assignableUser->area_key}") ?? $assignableUser->area_key)
                            : 'Sin area';
                        $initials = collect(explode(' ', $assignableUser->name))
                            ->filter()
                            ->take(2)
                            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                            ->implode('');
                    @endphp
                    <label class="doc-form__user-card js-doc-user-item" data-search="{{ strtolower($assignableUser->name.' '.$assignableUser->email.' '.$areaLabel) }}">
                        <input type="checkbox" name="users[]" value="{{ $assignableUser->id }}" class="js-doc-user-checkbox" {{ in_array($assignableUser->id, $selectedUsers, true) ? 'checked' : '' }}>
                        <span class="doc-form__user-card-body">
                            <span class="doc-form__user-avatar" aria-hidden="true">{{ $initials ?: '?' }}</span>
                            <span class="doc-form__user-info">
                                <strong class="doc-form__user-name">{{ $assignableUser->name }}</strong>
                                <span class="doc-form__user-meta">{{ $assignableUser->email }}</span>
                                <span class="doc-form__user-area">{{ $areaLabel }}</span>
                            </span>
                            <span class="doc-form__user-card-check" aria-hidden="true"></span>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('users')<p class="text-small text-danger">{{ $message }}</p>@enderror
            @error('users.*')<p class="text-small text-danger">{{ $message }}</p>@enderror
        </div>

        <div class="form-field">
            <label class="doc-form__check doc-form__check--switch">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" {{ filter_var($isActive ?? old('is_active', $document->is_active ?? true), FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                <span>Documento activo (visible en bibliotecas y Mis documentos)</span>
            </label>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const radios = document.querySelectorAll('[data-doc-type-toggle]');
        const fileField = document.querySelector('[data-doc-file-field]');
        const linkField = document.querySelector('[data-doc-link-field]');

        function syncTypeFields() {
            const selected = document.querySelector('[data-doc-type-toggle]:checked')?.value || 'file';
            fileField.style.display = selected === 'file' ? '' : 'none';
            linkField.style.display = selected === 'link' ? '' : 'none';

            document.querySelectorAll('.doc-form__radio').forEach((label) => {
                const input = label.querySelector('input');
                label.classList.toggle('is-active', input.checked);
            });
        }

        radios.forEach((radio) => radio.addEventListener('change', syncTypeFields));
        syncTypeFields();

        const dropzone = document.querySelector('[data-doc-dropzone]');
        const fileInput = document.querySelector('[data-doc-file-input]');
        const fileName = document.querySelector('[data-doc-file-name]');
        const fileSize = document.querySelector('[data-doc-file-size]');
        const fileSelected = document.querySelector('[data-doc-file-selected]');
        const fileClear = document.querySelector('[data-doc-file-clear]');
        const dropzoneContent = dropzone?.querySelector('.doc-form__dropzone-content');
        const allowedExtensions = ['doc', 'docx', 'xls', 'xlsx'];

        function formatFileSize(bytes) {
            if (!bytes) return '';
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1048576).toFixed(1) + ' MB';
        }

        function isAllowedFile(file) {
            const ext = file.name.split('.').pop()?.toLowerCase() || '';
            return allowedExtensions.includes(ext);
        }

        function showSelectedFile(file) {
            if (!file || !fileName || !fileSelected || !dropzoneContent) return;

            fileName.textContent = file.name;
            if (fileSize) fileSize.textContent = formatFileSize(file.size);
            dropzoneContent.hidden = true;
            fileSelected.hidden = false;
            dropzone.classList.add('has-file');
        }

        function clearSelectedFile() {
            if (!fileInput || !fileSelected || !dropzoneContent || !dropzone) return;

            fileInput.value = '';
            dropzoneContent.hidden = false;
            fileSelected.hidden = true;
            dropzone.classList.remove('has-file', 'is-dragover');
            if (fileName) fileName.textContent = 'Ningun archivo seleccionado';
            if (fileSize) fileSize.textContent = '';
        }

        function handleFile(file) {
            if (!file || !isAllowedFile(file)) {
                alert('Formato no permitido. Usa Word (.doc, .docx) o Excel (.xls, .xlsx).');
                return;
            }

            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            fileInput.files = dataTransfer.files;
            showSelectedFile(file);
        }

        if (dropzone && fileInput) {
            fileInput.addEventListener('change', function () {
                if (fileInput.files.length) {
                    handleFile(fileInput.files[0]);
                }
            });

            ['dragenter', 'dragover'].forEach((eventName) => {
                dropzone.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    dropzone.classList.add('is-dragover');
                });
            });

            ['dragleave', 'drop'].forEach((eventName) => {
                dropzone.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    dropzone.classList.remove('is-dragover');
                });
            });

            dropzone.addEventListener('drop', function (event) {
                const file = event.dataTransfer?.files?.[0];
                if (file) handleFile(file);
            });

            if (dropzoneContent) {
                dropzoneContent.addEventListener('click', function (event) {
                    if (event.target.closest('.doc-form__dropzone-btn')) return;
                    fileInput.click();
                });
            }

            if (fileClear) {
                fileClear.addEventListener('click', clearSelectedFile);
            }
        }

        const areaCheckboxes = document.querySelectorAll('.js-doc-area-checkbox');
        const areasCount = document.querySelector('[data-areas-count]');
        const areasToggleAll = document.querySelector('[data-areas-toggle-all]');

        function updateAreasCount() {
            const checked = Array.from(areaCheckboxes).filter((cb) => cb.checked).length;
            if (areasCount) areasCount.textContent = checked + ' seleccionada' + (checked === 1 ? '' : 's');
            if (areasToggleAll) {
                const allChecked = checked === areaCheckboxes.length && areaCheckboxes.length > 0;
                areasToggleAll.textContent = allChecked ? 'Deseleccionar todas' : 'Seleccionar todas';
            }
        }

        areaCheckboxes.forEach((cb) => cb.addEventListener('change', updateAreasCount));
        updateAreasCount();

        if (areasToggleAll) {
            areasToggleAll.addEventListener('click', function () {
                const allChecked = Array.from(areaCheckboxes).every((cb) => cb.checked);
                areaCheckboxes.forEach((cb) => { cb.checked = !allChecked; });
                updateAreasCount();
            });
        }

        const userCheckboxes = document.querySelectorAll('.js-doc-user-checkbox');
        const usersCount = document.querySelector('[data-users-count]');

        function updateUsersCount() {
            const checked = Array.from(userCheckboxes).filter((cb) => cb.checked).length;
            if (usersCount) usersCount.textContent = checked + ' seleccionado' + (checked === 1 ? '' : 's');
        }

        userCheckboxes.forEach((cb) => cb.addEventListener('change', updateUsersCount));
        updateUsersCount();

        const userSearch = document.getElementById('search-doc-users');
        const userItems = document.querySelectorAll('.js-doc-user-item');

        if (userSearch && userItems.length) {
            userSearch.addEventListener('input', function () {
                const term = userSearch.value.trim().toLowerCase();

                userItems.forEach((item) => {
                    const haystack = item.getAttribute('data-search') || '';
                    item.style.display = !term || haystack.includes(term) ? '' : 'none';
                });
            });
        }
    });
</script>
