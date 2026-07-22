@props(['perm', 'selectedPermissions'])

<div class="switch-item js-permission-item" data-search="{{ Str::lower($perm['label'].' '.$perm['name']) }}">
    <div class="switch-item__info">
        <span class="switch-item__title">{{ $perm['label'] }}</span>
        <code class="switch-item__code" title="Identificador interno">{{ $perm['name'] }}</code>
    </div>
    <label class="toggle-switch">
        <input
            type="checkbox"
            name="permissions[]"
            value="{{ $perm['name'] }}"
            class="js-permission-checkbox"
            @checked(in_array($perm['name'], $selectedPermissions, true))
        >
        <span class="toggle-slider"></span>
    </label>
</div>
