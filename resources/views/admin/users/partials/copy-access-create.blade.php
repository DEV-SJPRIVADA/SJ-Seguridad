@php
    $selectedCopyFrom = (int) request()->query('copy_from', 0);
    $includeAreaChecked = request()->has('include_area') ? request()->boolean('include_area') : true;
    $includeSedeChecked = request()->has('include_sede') ? request()->boolean('include_sede') : true;
    $copyFormId = 'copy-access-create-form';
    $copyCandidateOptions = $copyCandidates->map(fn ($candidate) => [
        'value' => (string) $candidate->id,
        'label' => trim($candidate->name.' · '.$candidate->email.($candidate->document_number ? ' · '.$candidate->document_number : '').(! $candidate->is_active ? ' (inactivo)' : '')),
    ])->all();
@endphp

<aside class="copy-access-panel copy-access-panel--sidebar card card--muted" aria-label="Copiar acceso de otro usuario">
    <div class="copy-access-panel__header">
        <div>
            <h3 class="text-small font-bold">Copiar acceso de otro usuario</h3>
            <p class="text-small text-muted">Precarga rol, permisos y opcionalmente area base y sede.</p>
        </div>
    </div>

    @if ($copyError)
        <div class="notice notice--warning block-spaced-sm" role="alert">
            <p class="text-small">{{ $copyError }}</p>
        </div>
    @endif

    @if ($copyFromUser)
        <div class="notice notice--info block-spaced-sm" role="status">
            <p class="text-small">
                Acceso precargado desde <strong>{{ $copyFromUser->name }}</strong>
                ({{ $copyFromUser->email }})@if (! $copyFromUser->is_active) · inactivo @endif.
                Revisa los permisos antes de crear el usuario.
            </p>
        </div>
    @endif

    <div class="copy-access-panel__form copy-access-panel__form--sidebar">
        <div class="form-field copy-access-panel__select">
            <label class="form-label" for="copy-from-user">Usuario origen</label>
            <x-searchable-select
                id="copy-from-user"
                name="copy_from"
                form="{{ $copyFormId }}"
                :options="$copyCandidateOptions"
                :value="$selectedCopyFrom"
                placeholder="Seleccione un usuario"
                searchPlaceholder="Buscar usuario…"
            />
        </div>

        <div class="copy-access-panel__options copy-access-panel__options--stack">
            <label class="copy-access-panel__toggle">
                <input type="checkbox" name="include_area" value="1" form="{{ $copyFormId }}" @checked($includeAreaChecked)>
                <span>Incluir area base</span>
            </label>
            <label class="copy-access-panel__toggle">
                <input type="checkbox" name="include_sede" value="1" form="{{ $copyFormId }}" @checked($includeSedeChecked)>
                <span>Incluir sede</span>
            </label>
        </div>

        <button type="submit" form="{{ $copyFormId }}" class="btn btn--secondary btn--sm copy-access-panel__submit">
            Aplicar acceso
        </button>
    </div>
</aside>
