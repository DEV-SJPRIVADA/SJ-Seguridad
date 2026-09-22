{{--
  Variables: $prefix, $cityOptions, $positionOptions, $epsOptions, $afpOptions,
  $maritalStatusOptions, $solicitudStatusOptions, $clientOptions, $responsableOptions, $values, $mode
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
        <label class="form-label" for="{{ $prefix }}_full_name">APELLIDOS Y NOMBRES</label>
        <input id="{{ $prefix }}_full_name" name="full_name" type="text" class="form-input" maxlength="255" required value="{{ $v['full_name'] ?? '' }}">
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
        <label class="form-label" for="{{ $prefix }}_servicio_sector">SERVICIO/SECTOR</label>
        <input id="{{ $prefix }}_servicio_sector" name="servicio_sector" type="text" class="form-input" maxlength="255" required value="{{ $v['servicio_sector'] ?? '' }}">
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
        <label class="form-label" for="{{ $prefix }}_eps_code">EPS</label>
        <x-searchable-select
            id="{{ $prefix }}_eps_code"
            name="eps_code"
            :options="$epsOptions"
            :value="$v['eps_code'] ?? ''"
            placeholder="Seleccionar…"
            :required="true"
            :allow-clear="false"
        />
    </div>
    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_afp_code">PENSION (AFP)</label>
        <x-searchable-select
            id="{{ $prefix }}_afp_code"
            name="afp_code"
            :options="$afpOptions"
            :value="$v['afp_code'] ?? ''"
            placeholder="Seleccionar…"
            :required="true"
            :allow-clear="false"
        />
    </div>
    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_birth_date">FECHA NACIMIENTO</label>
        <input id="{{ $prefix }}_birth_date" name="birth_date" type="date" class="form-input" required value="{{ $v['birth_date'] ?? '' }}">
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
        <label class="form-label" for="{{ $prefix }}_address">DIRECCION</label>
        <input id="{{ $prefix }}_address" name="address" type="text" class="form-input" maxlength="255" required value="{{ $v['address'] ?? '' }}">
    </div>
    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_email">CORREO</label>
        <input id="{{ $prefix }}_email" name="email" type="email" class="form-input" maxlength="150" required value="{{ $v['email'] ?? '' }}">
    </div>
    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_phone">CELULAR</label>
        <input id="{{ $prefix }}_phone" name="phone" type="text" class="form-input" maxlength="40" required value="{{ $v['phone'] ?? '' }}">
    </div>
    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_marital_status_code">ESTADO CIVIL</label>
        <x-searchable-select
            id="{{ $prefix }}_marital_status_code"
            name="marital_status_code"
            :options="$maritalStatusOptions"
            :value="$v['marital_status_code'] ?? ''"
            placeholder="Seleccionar…"
            :required="true"
            :allow-clear="false"
        />
    </div>
    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_fecha_arl">FECHA DE ARL</label>
        <input id="{{ $prefix }}_fecha_arl" name="fecha_arl" type="date" class="form-input" required value="{{ $v['fecha_arl'] ?? '' }}">
    </div>
    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_solicitud_status_code">SOLICITUD</label>
        <x-searchable-select
            id="{{ $prefix }}_solicitud_status_code"
            name="solicitud_status_code"
            :options="$solicitudStatusOptions"
            :value="$v['solicitud_status_code'] ?? ''"
            placeholder="Seleccionar…"
            :required="true"
            :allow-clear="false"
        />
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
</div>

@if ($mode === 'create')
    <div class="alert alert--warning" x-show="createDuplicates.length > 0" x-cloak>
        <p class="mb-2">Ya existen exámenes con esta cédula:</p>
        <ul class="mb-2">
            <template x-for="row in createDuplicates" :key="row.id">
                <li>
                    #<span x-text="row.id"></span>
                    — <span x-text="row.full_name"></span>
                    (<span x-text="row.fecha_arl || 's/f'"></span>)
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
