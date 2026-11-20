@php
    $prefix = $prefix ?? 'grant';
@endphp

<fieldset class="matrices-capabilities">
    <legend class="matrices-capabilities__title">¿Qué puede hacer?</legend>

    <label class="matrices-capabilities__item" for="{{ $prefix }}-register">
        <input id="{{ $prefix }}-register" type="checkbox" name="can_register" value="1" @checked(old('can_register'))>
        <span>
            <strong>Registrar</strong>
            <small>Crear registros propios.</small>
        </span>
    </label>

    <label class="matrices-capabilities__item" for="{{ $prefix }}-view-others">
        <input id="{{ $prefix }}-view-others" type="checkbox" name="can_view_others" value="1" @checked(old('can_view_others'))>
        <span>
            <strong>Ver todo</strong>
            <small>Consultar los registros de todos, no solo los suyos.</small>
        </span>
    </label>

    <label class="matrices-capabilities__item" for="{{ $prefix }}-edit-others">
        <input id="{{ $prefix }}-edit-others" type="checkbox" name="can_edit_others" value="1" @checked(old('can_edit_others'))>
        <span>
            <strong>Editar todo</strong>
            <small>Modificar registros de otros. Incluye ver todo.</small>
        </span>
    </label>

    <p class="matrices-capabilities__note">
        Si no marcas ninguna casilla, el acceso queda solo en lectura.
    </p>
</fieldset>
