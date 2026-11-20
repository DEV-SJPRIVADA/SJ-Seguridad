<x-app-layout>
    <x-slot name="header">
        @include('modules.matrices.partials.subnav', ['tabs' => $tabs, 'label' => $matrix->name])
    </x-slot>

    @php
        $isEdit = $submission !== null;

        $valueFor = function ($field) use ($values) {
            $value = $values->get($field->id);

            return match ($field->type) {
                'number', 'currency' => $value?->value_number,
                'date' => $value?->value_date?->format('Y-m-d'),
                'checkbox' => $value?->value_json ?? [],
                default => $value?->value_text,
            };
        };

        $groupedFields = $fields->groupBy(fn ($field) => $field->section ?: 'General');
    @endphp

    <div class="page-section matrices-page matrices-form-page">
        <div class="app-container">
            @if ($errors->any())
                <div class="alert alert--danger bottom-spaced" role="alert">
                    <p class="matrices-alert-line"><strong>Revisa el formulario:</strong></p>
                    @foreach ($errors->all() as $error)
                        <p class="matrices-alert-line">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <header class="matrices-hero matrices-hero--compact">
                <div class="matrices-hero__copy">
                    <p class="matrices-hero__eyebrow">{{ $isEdit ? 'Editar registro' : 'Nuevo registro' }}</p>
                    <h1 class="matrices-hero__title">{{ $isEdit ? $submission->folio : $matrix->name }}</h1>
                    <p class="matrices-hero__text">
                        {{ $isEdit ? 'Actualiza la informaciÃ³n y guarda los cambios.' : 'Diligencia los campos y guarda el registro.' }}
                    </p>
                </div>

                <a href="{{ $isEdit
                    ? route('matrices.submissions.show', ['module' => $module, 'matrix' => $matrix->id, 'submission' => $submission->id])
                    : route('matrices.submissions.index', ['module' => $module, 'matrix' => $matrix->id]) }}" class="btn btn--secondary">
                    Cancelar
                </a>
            </header>

            <form
                method="POST"
                action="{{ $isEdit
                    ? route('matrices.submissions.update', ['module' => $module, 'matrix' => $matrix->id, 'submission' => $submission->id])
                    : route('matrices.submissions.store', ['module' => $module, 'matrix' => $matrix->id]) }}"
                enctype="multipart/form-data"
                class="matrices-form"
            >
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                @foreach ($groupedFields as $section => $sectionFields)
                    <section class="panel matrices-form__section">
                        <div class="panel__header panel__header--compact">
                            <h2 class="matrices-panel-title">{{ $section }}</h2>
                        </div>
                        <div class="panel__body">
                            <div class="matrices-form-grid">
                                @foreach ($sectionFields as $field)
                                    @php
                                        $key = $field->field_key;
                                        $name = 'values['.$key.']';
                                        $current = old($name, $valueFor($field));
                                        $fieldAttachments = $attachments->get($field->id) ?? collect();
                                        $span = in_array((int) $field->col_span, [4, 6], true) ? (int) $field->col_span : 12;
                                    @endphp

                                    <div class="form-field matrices-form-grid__col matrices-form-grid__col--{{ $span }}">
                                        @switch($field->type)
                                            @case('textarea')
                                                <label class="form-label" for="field-{{ $key }}">
                                                    {{ $field->label }}@if ($field->required)<span class="matrices-required">*</span>@endif
                                                </label>
                                                <textarea
                                                    id="field-{{ $key }}"
                                                    class="form-input @error('values.'.$key) form-input--error @enderror"
                                                    name="{{ $name }}"
                                                    rows="4"
                                                    maxlength="5000"
                                                    placeholder="{{ $field->placeholder }}"
                                                    @if ($field->required) required @endif
                                                >{{ $current }}</textarea>
                                                @break

                                            @case('number')
                                            @case('currency')
                                                <label class="form-label" for="field-{{ $key }}">
                                                    {{ $field->label }}@if ($field->required)<span class="matrices-required">*</span>@endif
                                                </label>
                                                <input
                                                    id="field-{{ $key }}"
                                                    type="number"
                                                    step="{{ $field->type === 'currency' ? '0.01' : 'any' }}"
                                                    class="form-input @error('values.'.$key) form-input--error @enderror"
                                                    name="{{ $name }}"
                                                    value="{{ $current }}"
                                                    placeholder="{{ $field->placeholder }}"
                                                    @if ($field->required) required @endif
                                                >
                                                @break

                                            @case('date')
                                                <label class="form-label" for="field-{{ $key }}">
                                                    {{ $field->label }}@if ($field->required)<span class="matrices-required">*</span>@endif
                                                </label>
                                                <input
                                                    id="field-{{ $key }}"
                                                    type="date"
                                                    class="form-input @error('values.'.$key) form-input--error @enderror"
                                                    name="{{ $name }}"
                                                    value="{{ $current }}"
                                                    @if ($field->required) required @endif
                                                >
                                                @break

                                            @case('select')
                                                <label class="form-label" for="field-{{ $key }}">
                                                    {{ $field->label }}@if ($field->required)<span class="matrices-required">*</span>@endif
                                                </label>
                                                <x-searchable-select
                                                    id="field-{{ $key }}"
                                                    :name="$name"
                                                    :options="collect($field->options ?? [])->map(fn ($option) => ['value' => (string) ($option['key'] ?? ''), 'label' => (string) ($option['label'] ?? '')])->values()->all()"
                                                    :value="$current"
                                                    :placeholder="$field->placeholder ?: 'Selecciona una opciÃ³n'"
                                                    searchPlaceholder="Buscar opciÃ³nâ€¦"
                                                    :required="$field->required"
                                                    :allowClear="! $field->required"
                                                />
                                                @break

                                            @case('radio')
                                                <fieldset class="matrices-choice">
                                                    <legend class="form-label">
                                                        {{ $field->label }}@if ($field->required)<span class="matrices-required">*</span>@endif
                                                    </legend>
                                                    @foreach ($field->options ?? [] as $option)
                                                        <label class="matrices-choice__item">
                                                            <input
                                                                type="radio"
                                                                name="{{ $name }}"
                                                                value="{{ $option['key'] ?? '' }}"
                                                                @checked((string) $current === (string) ($option['key'] ?? ''))
                                                                @if ($field->required) required @endif
                                                            >
                                                            <span>{{ $option['label'] ?? '' }}</span>
                                                        </label>
                                                    @endforeach
                                                </fieldset>
                                                @break

                                            @case('checkbox')
                                                <fieldset class="matrices-choice">
                                                    <legend class="form-label">
                                                        {{ $field->label }}@if ($field->required)<span class="matrices-required">*</span>@endif
                                                    </legend>
                                                    @php
                                                        $selected = is_array($current) ? $current : [];
                                                    @endphp
                                                    @foreach ($field->options ?? [] as $option)
                                                        <label class="matrices-choice__item">
                                                            <input
                                                                type="checkbox"
                                                                name="{{ $name }}[]"
                                                                value="{{ $option['key'] ?? '' }}"
                                                                @checked(in_array((string) ($option['key'] ?? ''), array_map('strval', $selected), true))
                                                            >
                                                            <span>{{ $option['label'] ?? '' }}</span>
                                                        </label>
                                                    @endforeach
                                                </fieldset>
                                                @break

                                            @case('file')
                                                <span class="form-label">
                                                    {{ $field->label }}@if ($field->required)<span class="matrices-required">*</span>@endif
                                                </span>
                                                @if ($field->help)
                                                    <p class="matrices-help">{{ $field->help }}</p>
                                                @endif

                                                @if ($fieldAttachments->isNotEmpty())
                                                    <ul class="matrices-attachments">
                                                        @foreach ($fieldAttachments as $attachment)
                                                            <li class="matrices-attachments__item">
                                                                <span class="matrices-attachments__name">{{ $attachment->original_name }}</span>
                                                                <span class="matrices-muted">{{ $attachment->humanSize() }}</span>
                                                                <a href="{{ route('matrices.attachments.download', ['module' => $module, 'matrix' => $matrix->id, 'attachment' => $attachment->id]) }}" class="matrices-link">Descargar</a>
                                                                <button
                                                                    type="submit"
                                                                    form="attachment-delete-{{ $attachment->id }}"
                                                                    class="matrices-link matrices-link--danger"
                                                                    onclick="return confirm('Â¿Eliminar el archivo {{ $attachment->original_name }}?');"
                                                                >
                                                                    Eliminar
                                                                </button>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif

                                                <input
                                                    id="field-{{ $key }}"
                                                    type="file"
                                                    class="form-input matrices-file-input"
                                                    name="files[{{ $key }}][]"
                                                    multiple
                                                    @if ($field->required && $fieldAttachments->isEmpty()) required @endif
                                                >
                                                <p class="matrices-help">MÃ¡ximo 10 archivos por campo, 10 MB por archivo.</p>
                                                @break

                                            @case('signature')
                                                <div
                                                    class="matrices-signature"
                                                    x-data="matrixSignaturePad({
                                                        fieldKey: @js($key),
                                                        profileUri: @js($profileSignatureUri),
                                                        existingUrl: @js($fieldAttachments->first() ? route('matrices.attachments.preview', ['module' => $module, 'matrix' => $matrix->id, 'attachment' => $fieldAttachments->first()->id]) : ''),
                                                        required: @js((bool) $field->required),
                                                        mode: @js(old('signature_mode.'.$key, '')),
                                                    })"
                                                >
                                                    <span class="form-label">
                                                        {{ $field->label }}@if ($field->required)<span class="matrices-required">*</span>@endif
                                                    </span>

                                                    <input type="hidden" :name="'signature_mode[' + fieldKey + ']'" x-model="mode">
                                                    <input type="hidden" :name="'signature_canvas[' + fieldKey + ']'" x-model="dataUrl">

                                                    <div class="matrices-signature__choices">
                                                        <button type="button" class="btn btn--secondary btn--sm" @click="startDraw()" :class="{ 'is-active': mode === 'draw' }">
                                                            Dibujar firma
                                                        </button>
                                                        <button
                                                            type="button"
                                                            class="btn btn--secondary btn--sm"
                                                            @click="useProfile()"
                                                            :class="{ 'is-active': mode === 'profile' }"
                                                            :disabled="! profileUri"
                                                            :title="profileUri ? 'Usar la firma de tu perfil' : 'No tienes firma registrada en tu perfil'"
                                                        >
                                                            Usar firma del perfil
                                                        </button>
                                                        <button type="button" class="btn btn--secondary btn--sm" @click="clearCanvas()">Limpiar</button>
                                                    </div>

                                                    <div class="matrices-signature__existing" x-show="existingUrl && ! hasSignature && ! dataUrl">
                                                        <img :src="existingUrl" alt="Firma registrada" class="matrices-signature__image">
                                                        <span class="matrices-muted">Firma actual del registro. Dibuja o elige otra para reemplazarla.</span>
                                                    </div>

                                                    <div class="matrices-signature__canvas-wrap" x-show="mode === 'draw'" x-cloak>
                                                        <canvas
                                                            class="matrices-signature__canvas"
                                                            @pointerdown.prevent="begin($event)"
                                                            @pointermove.prevent="move($event)"
                                                            @pointerup.prevent="end()"
                                                            @pointerleave="end()"
                                                        ></canvas>
                                                        <span class="matrices-help">Firma con el mouse o el dedo.</span>
                                                    </div>

                                                    <div class="matrices-signature__profile" x-show="mode === 'profile' && profileUri" x-cloak>
                                                        <img :src="profileUri" alt="Firma del perfil" class="matrices-signature__image">
                                                    </div>
                                                </div>
                                                @break

                                            @default
                                                <label class="form-label" for="field-{{ $key }}">
                                                    {{ $field->label }}@if ($field->required)<span class="matrices-required">*</span>@endif
                                                </label>
                                                <input
                                                    id="field-{{ $key }}"
                                                    type="text"
                                                    class="form-input @error('values.'.$key) form-input--error @enderror"
                                                    name="{{ $name }}"
                                                    value="{{ $current }}"
                                                    maxlength="500"
                                                    placeholder="{{ $field->placeholder }}"
                                                    @if ($field->required) required @endif
                                                >
                                        @endswitch

                                        @if ($field->help && $field->type !== 'file')
                                            <p class="matrices-help">{{ $field->help }}</p>
                                        @endif

                                        @error('values.'.$key)
                                            <p class="matrices-error">{{ $message }}</p>
                                        @enderror
                                        @error('files.'.$key)
                                            <p class="matrices-error">{{ $message }}</p>
                                        @enderror
                                        @error('signature_canvas.'.$key)
                                            <p class="matrices-error">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endforeach

                <section class="panel matrices-form__section">
                    <div class="panel__header panel__header--compact">
                        <h2 class="matrices-panel-title">AsignaciÃ³n</h2>
                    </div>
                    <div class="panel__body">
                        <div class="matrices-form-grid">
                            <div class="form-field matrices-form-grid__col matrices-form-grid__col--12">
                                <label class="form-label" for="assigned-to">Asignar a un usuario (opcional)</label>
                                <x-searchable-select
                                    id="assigned-to"
                                    name="assigned_to_user_id"
                                    :options="collect($assigneeOptions)->map(fn ($label, $value) => ['value' => (string) $value, 'label' => $label])->values()->all()"
                                    :value="(string) old('assigned_to_user_id', $assignedTo ?? '')"
                                    placeholder="Sin asignar"
                                    searchPlaceholder="Buscar usuarioâ€¦"
                                    :allowClear="true"
                                />
                                <p class="matrices-help">El usuario asignado puede ver y editar este registro aunque no haya creado la matriz.</p>
                                @error('assigned_to_user_id')
                                    <p class="matrices-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </section>

                <div class="matrices-form__actions">
                    <a href="{{ $isEdit
                        ? route('matrices.submissions.show', ['module' => $module, 'matrix' => $matrix->id, 'submission' => $submission->id])
                        : route('matrices.submissions.index', ['module' => $module, 'matrix' => $matrix->id]) }}" class="btn btn--secondary">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn--primary">
                        {{ $isEdit ? 'Guardar cambios' : 'Guardar registro' }}
                    </button>
                </div>
            </form>

            @if ($isEdit)
                @foreach ($attachments->flatten() as $attachment)
                    <form
                        id="attachment-delete-{{ $attachment->id }}"
                        method="POST"
                        action="{{ route('matrices.attachments.destroy', ['module' => $module, 'matrix' => $matrix->id, 'attachment' => $attachment->id]) }}"
                        hidden
                    >
                        @csrf
                        @method('DELETE')
                    </form>
                @endforeach
            @endif
        </div>
    </div>
</x-app-layout>
