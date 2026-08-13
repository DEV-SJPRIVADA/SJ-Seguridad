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
    <select
        id="{{ $id }}"
        name="{{ $name }}"
        class="form-select js-ficha-select"
        @disabled($disabled)
        @if ($required) required @endif
    >
        <option value="">—</option>
        @foreach ($catalogs[$catalogKey] ?? [] as $item)
            <option value="{{ $item['code'] }}" @selected((string) $value === (string) $item['code'])>
                {{ $item['code'] }} — {{ $item['name'] }}
            </option>
        @endforeach
    </select>
</div>
