@php
    $selectedCopyFrom = (int) request()->query('copy_from', 0);
    $includeAreaChecked = request()->has('include_area') ? request()->boolean('include_area') : true;
    $includeSedeChecked = request()->has('include_sede') ? request()->boolean('include_sede') : true;
@endphp

<div class="copy-access-panel card card--muted bottom-spaced">
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

    <form method="GET" action="{{ route('admin.users.create') }}" class="copy-access-panel__form">
        <input type="hidden" name="tab" value="capabilities">

        <div class="copy-access-panel__fields">
            <div class="form-field copy-access-panel__select">
                <label class="form-label" for="copy-from-user">Usuario origen</label>
                @php
                    $copyCandidateOptions = $copyCandidates->map(fn ($candidate) => [
                        'value' => (string) $candidate->id,
                        'label' => trim($candidate->name.' · '.$candidate->email.($candidate->document_number ? ' · '.$candidate->document_number : '').(! $candidate->is_active ? ' (inactivo)' : '')),
                    ])->all();
                @endphp
                <x-searchable-select
                    id="copy-from-user"
                    name="copy_from"
                    :options="$copyCandidateOptions"
                    :value="$selectedCopyFrom"
                    placeholder="Seleccione un usuario"
                    searchPlaceholder="Buscar usuario…"
                />
            </div>

            <div class="copy-access-panel__options">
                <label class="copy-access-panel__toggle">
                    <input type="checkbox" name="include_area" value="1" @checked($includeAreaChecked)>
                    <span>Incluir area base</span>
                </label>
                <label class="copy-access-panel__toggle">
                    <input type="checkbox" name="include_sede" value="1" @checked($includeSedeChecked)>
                    <span>Incluir sede</span>
                </label>
            </div>

        </div>
        <div class="block-spaced-sm">
            <button type="submit" class="btn btn--secondary btn--sm bottom-spaced-sm">Aplicar acceso</button>
        </div>
    </form>
</div>
