@props(['disabled' => false])

<div class="form-password-field" x-data="{ show: false }">
    <input
        @disabled($disabled)
        {{ $attributes->merge(['class' => 'form-input', 'type' => 'password', 'autocomplete' => 'current-password']) }}
        x-bind:type="show ? 'text' : 'password'"
    >

    <button
        type="button"
        class="form-password-field__toggle"
        x-on:click="show = ! show"
        x-bind:aria-label="show ? 'Ocultar contraseña' : 'Mostrar contraseña'"
        x-bind:aria-pressed="show.toString()"
    >
        <span x-show="! show" x-cloak>
            <x-lucide-eye width="20" height="20" aria-hidden="true" />
        </span>
        <span x-show="show" x-cloak>
            <x-lucide-eye-off width="20" height="20" aria-hidden="true" />
        </span>
    </button>
</div>
