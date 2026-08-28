@props([
    'id',
    'name',
    'label',
    'catalogKey',
    'catalogs',
    'value' => '',
    'required' => false,
    'disabled' => false,
])

<div {{ $attributes->merge(['class' => 'form-field']) }}>
    <label class="form-label" for="{{ $id }}">
        {{ $label }}
        @if ($required)
            <span class="text-danger">*</span>
        @endif
    </label>
    <x-searchable-select
        :id="$id"
        :name="$name"
        :options="$catalogs[$catalogKey] ?? []"
        :value="$value"
        :required="$required"
        :disabled="$disabled"
    />
    @php
        $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    @endphp
    <x-input-error :messages="$errors->get($errorKey)" />
</div>
