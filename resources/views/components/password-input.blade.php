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
            <x-lucide-icon name="eye" :size="20" />
        </span>
        <span x-show="show" x-cloak>
            <x-lucide-icon name="eye-off" :size="20" />
        </span>
    </button>
</div>
