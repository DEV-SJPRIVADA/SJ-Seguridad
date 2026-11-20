@php
    $prefix = $prefix ?? 'grant';
    $grant = $grant ?? null;
@endphp

<div class="matrices-capabilities matrices-capabilities--inline">
    <label class="matrices-capabilities__item" for="{{ $prefix }}-register">
        <input id="{{ $prefix }}-register" type="checkbox" name="can_register" value="1" @checked($grant?->can_register)>
        <span><strong>Registrar</strong></span>
    </label>

    <label class="matrices-capabilities__item" for="{{ $prefix }}-view-others">
        <input id="{{ $prefix }}-view-others" type="checkbox" name="can_view_others" value="1" @checked($grant?->can_view_others)>
        <span><strong>Ver todo</strong></span>
    </label>

    <label class="matrices-capabilities__item" for="{{ $prefix }}-edit-others">
        <input id="{{ $prefix }}-edit-others" type="checkbox" name="can_edit_others" value="1" @checked($grant?->can_edit_others)>
        <span><strong>Editar todo</strong></span>
    </label>
</div>
