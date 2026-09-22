{{--
  Variables: $prefix, $cityOptions, $positionOptions, $bloodTypeOptions,
  $clientOptions, $uniformOptions, $responsableOptions, $values, $mode
--}}
@php
    $v = $values ?? [];
@endphp

<div class="cursos-registros-page__form-grid">
    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_document_number">CEDULA</label>
        <input
            id="{{ $prefix }}_document_number"
            name="document_number"
            type="text"
            class="form-input"
            maxlength="50"
            required
            value="{{ $v['document_number'] ?? '' }}"
            @if ($mode === 'create')
                x-on:blur="lookupDuplicates($event.target.value, 'create')"
            @endif
        >
    </div>
    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_full_name">APELLIDOS Y NOMBRE</label>
        <input id="{{ $prefix }}_full_name" name="full_name" type="text" class="form-input" maxlength="255" required value="{{ $v['full_name'] ?? '' }}">
    </div>
    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_email">CORREO</label>
        <input id="{{ $prefix }}_email" name="email" type="email" class="form-input" maxlength="150" required value="{{ $v['email'] ?? '' }}">
    </div>
    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_phone">TELEFONO</label>
        <input id="{{ $prefix }}_phone" name="phone" type="text" class="form-input" maxlength="40" required value="{{ $v['phone'] ?? '' }}">
    </div>
    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_city_code">CIUDAD</label>
        <x-searchable-select
            id="{{ $prefix }}_city_code"
            name="city_code"
            :options="$cityOptions"
            :value="$v['city_code'] ?? ''"
            placeholder="Seleccionar…"
            :required="true"
            :allow-clear="false"
        />
    </div>
    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_position_code">CARGO</label>
        <x-searchable-select
            id="{{ $prefix }}_position_code"
            name="position_code"
            :options="$positionOptions"
            :value="$v['position_code'] ?? ''"
            placeholder="Seleccionar…"
            :required="true"
            :allow-clear="false"
        />
    </div>
    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_commercial_client_id">CLIENTE</label>
        <x-searchable-select
            id="{{ $prefix }}_commercial_client_id"
            name="commercial_client_id"
            :options="$clientOptions"
            :value="$v['commercial_client_id'] ?? ''"
            placeholder="Seleccionar…"
            :required="true"
            :allow-clear="false"
        />
    </div>
    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_shirt_size">TALLA CAMISA</label>
        <input id="{{ $prefix }}_shirt_size" name="shirt_size" type="text" class="form-input" maxlength="40" required value="{{ $v['shirt_size'] ?? '' }}">
    </div>
    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_pants_size">TALLA PANTALÓN</label>
        <input id="{{ $prefix }}_pants_size" name="pants_size" type="text" class="form-input" maxlength="40" required value="{{ $v['pants_size'] ?? '' }}">
    </div>
    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_shoes_size">TALLA ZAPATOS</label>
        <input id="{{ $prefix }}_shoes_size" name="shoes_size" type="text" class="form-input" maxlength="40" required value="{{ $v['shoes_size'] ?? '' }}">
    </div>
    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_requisition_uniform_id">TIPO DOTACION</label>
        <x-searchable-select
            id="{{ $prefix }}_requisition_uniform_id"
            name="requisition_uniform_id"
            :options="$uniformOptions"
            :value="$v['requisition_uniform_id'] ?? ''"
            placeholder="Seleccionar…"
            :required="true"
            :allow-clear="false"
        />
    </div>
    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_fecha_ingreso">FECHA DE INGRESO</label>
        <input id="{{ $prefix }}_fecha_ingreso" name="fecha_ingreso" type="date" class="form-input" required value="{{ $v['fecha_ingreso'] ?? '' }}">
    </div>
    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_blood_type_code">RH</label>
        <x-searchable-select
            id="{{ $prefix }}_blood_type_code"
            name="blood_type_code"
            :options="$bloodTypeOptions"
            :value="$v['blood_type_code'] ?? ''"
            placeholder="Seleccionar…"
            :required="true"
            :allow-clear="false"
        />
    </div>
    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_reemplaza_a">REEMPLAZA A</label>
        <input id="{{ $prefix }}_reemplaza_a" name="reemplaza_a" type="text" class="form-input" maxlength="255" required value="{{ $v['reemplaza_a'] ?? '' }}">
    </div>
    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_responsable_user_id">RESPONSABLE</label>
        <x-searchable-select
            id="{{ $prefix }}_responsable_user_id"
            name="responsable_user_id"
            :options="$responsableOptions"
            :value="$v['responsable_user_id'] ?? ''"
            placeholder="Seleccionar…"
            :required="true"
            :allow-clear="false"
        />
    </div>
    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_jefe_ope">JEFE OPE ASIGNADO</label>
        <input id="{{ $prefix }}_jefe_ope" name="jefe_ope" type="text" class="form-input" maxlength="255" required value="{{ $v['jefe_ope'] ?? '' }}">
    </div>
</div>

@if ($mode === 'create')
    <div class="alert alert--warning" x-show="createDuplicates.length > 0" x-cloak>
        <p class="mb-2">Ya existen ingresos con esta cédula:</p>
        <ul class="mb-2">
            <template x-for="row in createDuplicates" :key="row.id">
                <li>
                    #<span x-text="row.id"></span>
                    — <span x-text="row.full_name"></span>
                    (<span x-text="row.fecha_ingreso || 's/f'"></span>)
                </li>
            </template>
        </ul>
        <label class="cursos-registros-page__checkbox-label">
            <input type="checkbox" name="confirm_duplicate" value="1" x-model="createConfirmDuplicate">
            Confirmo guardar de todos modos
        </label>
    </div>
@endif

<div class="cursos-registros-page__form-actions">
    <button type="submit" class="btn btn--primary">
        {{ $mode === 'create' ? 'Guardar' : 'Actualizar' }}
    </button>
</div>
