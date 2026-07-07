@php
    $selectedType = old('type', isset($document) ? $document->type : 'file');
    $selectedAreas = old('areas', $selectedAreas ?? []);
    $selectedUsers = collect(old('users', $selectedUsers ?? []))->map(fn ($id) => (int) $id)->all();
    $selectedRootProcess = old('root_process', $document->root_process ?? '');
    $selectedDocumentType = old('document_type', $document->document_type ?? '');
    $isActive = old('is_active', isset($document) ? $document->is_active : true);
@endphp

<div class="doc-form">
    <div class="doc-form__section">
        <h4 class="doc-form__section-title">Identificacion</h4>

        <div class="form-grid form-grid--two">
            <div class="form-field form-field--full">
                <label class="form-label" for="title">Titulo</label>
                <input type="text" name="title" id="title" class="form-input" value="{{ old('title', $document->title ?? '') }}" required>
                @error('title')<p class="text-small text-danger">{{ $message }}</p>@enderror
            </div>

            <div class="form-field">
                <label class="form-label" for="code">Codigo</label>
                <input type="text" name="code" id="code" class="form-input" value="{{ old('code', $document->code ?? '') }}" placeholder="Ej. SG-FR-001" required>
                @error('code')<p class="text-small text-danger">{{ $message }}</p>@enderror
            </div>

            <div class="form-field">
                <label class="form-label" for="root_process">Proceso raiz</label>
                <select name="root_process" id="root_process" class="form-select" required>
                    <option value="">Selecciona el area propietaria</option>
                    @foreach ($areas as $key => $label)
                        <option value="{{ $key }}" {{ $selectedRootProcess === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="text-small text-muted">Area a la que pertenece el documento.</p>
                @error('root_process')<p class="text-small text-danger">{{ $message }}</p>@enderror
            </div>

            <div class="form-field">
                <label class="form-label" for="document_type">Tipo de documento</label>
                <select name="document_type" id="document_type" class="form-select" required>
                    <option value="">Selecciona un tipo</option>
                    @foreach ($documentTypes as $key => $label)
                        <option value="{{ $key }}" {{ $selectedDocumentType === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('document_type')<p class="text-small text-danger">{{ $message }}</p>@enderror
            </div>

            <div class="form-field form-field--full">
                <label class="form-label" for="description">Descripcion</label>
                <textarea name="description" id="description" class="form-textarea" rows="3">{{ old('description', $document->description ?? '') }}</textarea>
                @error('description')<p class="text-small text-danger">{{ $message }}</p>@enderror
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
            <label class="doc-form__file" for="file">
                <input type="file" name="file" id="file" accept=".doc,.docx,.xls,.xlsx" data-doc-file-input>
                <span class="doc-form__file-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    Seleccionar archivo
                </span>
                <span class="doc-form__file-name" data-doc-file-name>Ningun archivo seleccionado</span>
            </label>
            <p class="text-small text-muted">Formatos permitidos: Word (.doc, .docx) y Excel (.xls, .xlsx).</p>
            @if (!empty($document?->original_name))
                <p class="text-small text-muted">Archivo actual: <strong>{{ $document->original_name }}</strong></p>
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

        <div class="form-field">
            <span class="form-label">Areas con acceso</span>
            <p class="text-small text-muted">Areas que podran consultar y descargar el documento.</p>
            <div class="doc-form__check-grid">
                @foreach ($areas as $key => $label)
                    <label class="doc-form__check">
                        <input type="checkbox" name="areas[]" value="{{ $key }}" {{ in_array($key, $selectedAreas, true) ? 'checked' : '' }}>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            @error('areas')<p class="text-small text-danger">{{ $message }}</p>@enderror
            @error('areas.*')<p class="text-small text-danger">{{ $message }}</p>@enderror
        </div>

        <div class="form-field">
            <span class="form-label">Usuarios especificos</span>
            <p class="text-small text-muted">Solo los usuarios seleccionados veran el documento en su tablero personal "Mis documentos".</p>
            <input type="text" id="search-doc-users" class="form-input" placeholder="Buscar usuario por nombre o correo..." style="max-width: 420px;">
            <div class="doc-form__check-grid" id="doc-users-grid">
                @foreach ($users as $assignableUser)
                    @php
                        $areaLabel = $assignableUser->area_key
                            ? (config("access.areas.{$assignableUser->area_key}") ?? $assignableUser->area_key)
                            : 'Sin area';
                    @endphp
                    <label class="doc-form__check js-doc-user-item" data-search="{{ strtolower($assignableUser->name.' '.$assignableUser->email.' '.$areaLabel) }}">
                        <input type="checkbox" name="users[]" value="{{ $assignableUser->id }}" {{ in_array($assignableUser->id, $selectedUsers, true) ? 'checked' : '' }}>
                        <span>
                            <strong>{{ $assignableUser->name }}</strong>
                            <span class="text-small text-muted block-spaced-sm">{{ $assignableUser->email }} · {{ $areaLabel }}</span>
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
                <input type="checkbox" name="is_active" value="1" {{ filter_var($isActive, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
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

        const fileInput = document.querySelector('[data-doc-file-input]');
        const fileName = document.querySelector('[data-doc-file-name]');

        if (fileInput && fileName) {
            fileInput.addEventListener('change', function () {
                fileName.textContent = fileInput.files.length
                    ? fileInput.files[0].name
                    : 'Ningun archivo seleccionado';
                fileName.classList.toggle('is-selected', fileInput.files.length > 0);
            });
        }

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
