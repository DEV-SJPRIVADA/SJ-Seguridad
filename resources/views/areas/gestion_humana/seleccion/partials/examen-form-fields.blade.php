{{--
  Variables: $prefix, $cityOptions, $positionOptions, $epsOptions, $afpOptions,
  $maritalStatusOptions, $solicitudStatusOptions, $estadoOptions, $clientOptions,
  $responsableOptions, $values, $mode

  Mismo layout por secciones que Ingreso (clases seleccion-ingresos-form*).
  En modo 'edit' los inputs de texto usan x-model="editForm.*" y los
  searchable-select se setean desde openEdit() con setSearchableValue().
--}}
@php
    $v = $values ?? [];
    $isEdit = ($mode ?? 'create') === 'edit';

    // Atributo Alpine para enlazar inputs de texto al estado de edición
    $bind = fn (string $field): string => $isEdit ? 'x-model="editForm.'.$field.'"' : '';

    // Estado duplicados (create|edit) para el aviso y confirmación
    $dupState = $isEdit ? 'edit' : 'create';

    $submitLabel = $isEdit ? 'Actualizar examen' : 'Guardar examen';
    $modalName = $isEdit ? 'seleccion-examen-editar' : 'seleccion-examen-nuevo';
@endphp

<div class="seleccion-ingresos-form">
    {{-- Sección 1: identidad y contacto del aspirante --}}
    <section class="seleccion-ingresos-form__section" aria-labelledby="{{ $prefix }}_examen_section_aspirante">
        <header class="seleccion-ingresos-form__section-head">
            <span class="seleccion-ingresos-form__section-icon" aria-hidden="true">
                <x-lucide-user-round width="16" height="16" aria-hidden="true" />
            </span>
            <h4 id="{{ $prefix }}_examen_section_aspirante" class="seleccion-ingresos-form__section-title">Datos del aspirante</h4>
        </header>

        <div class="seleccion-ingresos-form__grid">
            <div class="form-field">
                <label class="form-label" for="{{ $prefix }}_document_number">CÉDULA</label>
                <input
                    id="{{ $prefix }}_document_number"
                    name="document_number"
                    type="text"
                    class="form-input"
                    maxlength="50"
                    required
                    inputmode="numeric"
                    autocomplete="off"
                    value="{{ $v['document_number'] ?? '' }}"
                    {!! $bind('document_number') !!}
                    x-on:blur="lookupDuplicates($event.target.value, '{{ $dupState }}')"
                >
            </div>
            <div class="form-field seleccion-ingresos-form__span-2">
                <label class="form-label" for="{{ $prefix }}_full_name">APELLIDOS Y NOMBRES</label>
                <input id="{{ $prefix }}_full_name" name="full_name" type="text" class="form-input" maxlength="255" required autocomplete="off" value="{{ $v['full_name'] ?? '' }}" {!! $bind('full_name') !!}>
            </div>
            <div class="form-field">
                <label class="form-label" for="{{ $prefix }}_birth_date">FECHA NACIMIENTO</label>
                <input id="{{ $prefix }}_birth_date" name="birth_date" type="date" class="form-input" required value="{{ $v['birth_date'] ?? '' }}" {!! $bind('birth_date') !!}>
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
                <label class="form-label" for="{{ $prefix }}_phone">CELULAR</label>
                <input id="{{ $prefix }}_phone" name="phone" type="tel" class="form-input" maxlength="40" required autocomplete="off" value="{{ $v['phone'] ?? '' }}" {!! $bind('phone') !!}>
            </div>
            <div class="form-field seleccion-ingresos-form__span-2">
                <label class="form-label" for="{{ $prefix }}_email">CORREO</label>
                <input
                    id="{{ $prefix }}_email"
                    name="email"
                    type="email"
                    class="form-input"
                    maxlength="150"
                    required
                    autocomplete="off"
                    placeholder="nombre@dominio.com"
                    pattern="[^@\s]+@[^@\s]+\.[^@\s]+"
                    title="Ingrese un correo válido (ej. nombre@dominio.com)"
                    value="{{ $v['email'] ?? '' }}"
                    {!! $bind('email') !!}
                >
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
            <div class="form-field seleccion-ingresos-form__span-3">
                <label class="form-label" for="{{ $prefix }}_address">DIRECCIÓN</label>
                <input id="{{ $prefix }}_address" name="address" type="text" class="form-input" maxlength="255" required autocomplete="off" value="{{ $v['address'] ?? '' }}" {!! $bind('address') !!}>
            </div>
        </div>
    </section>

    {{-- Sección 2: cliente, cargo y afiliaciones --}}
    <section class="seleccion-ingresos-form__section" aria-labelledby="{{ $prefix }}_examen_section_afiliacion">
        <header class="seleccion-ingresos-form__section-head">
            <span class="seleccion-ingresos-form__section-icon" aria-hidden="true">
                <x-lucide-briefcase width="16" height="16" aria-hidden="true" />
            </span>
            <h4 id="{{ $prefix }}_examen_section_afiliacion" class="seleccion-ingresos-form__section-title">Cargo y afiliaciones</h4>
        </header>

        <div class="seleccion-ingresos-form__grid">
            <div class="form-field seleccion-ingresos-form__span-2">
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
                <input id="{{ $prefix }}_servicio_sector" name="servicio_sector" type="text" class="form-input" maxlength="255" required autocomplete="off" value="{{ $v['servicio_sector'] ?? '' }}" {!! $bind('servicio_sector') !!}>
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
                <label class="form-label" for="{{ $prefix }}_afp_code">PENSIÓN (AFP)</label>
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
        </div>
    </section>

    {{-- Sección 3: fechas, estado operativo y responsable del examen --}}
    <section class="seleccion-ingresos-form__section" aria-labelledby="{{ $prefix }}_examen_section_proceso">
        <header class="seleccion-ingresos-form__section-head">
            <span class="seleccion-ingresos-form__section-icon" aria-hidden="true">
                <x-lucide-clipboard-check width="16" height="16" aria-hidden="true" />
            </span>
            <h4 id="{{ $prefix }}_examen_section_proceso" class="seleccion-ingresos-form__section-title">Proceso del examen</h4>
        </header>

        <div class="seleccion-ingresos-form__grid">
            <div class="form-field">
                <label class="form-label" for="{{ $prefix }}_fecha_arl">FECHA DE ARL</label>
                <input id="{{ $prefix }}_fecha_arl" name="fecha_arl" type="date" class="form-input" required value="{{ $v['fecha_arl'] ?? '' }}" {!! $bind('fecha_arl') !!}>
            </div>
            <div class="form-field">
                <label class="form-label" for="{{ $prefix }}_estado">ESTADO</label>
                <x-searchable-select
                    id="{{ $prefix }}_estado"
                    name="estado"
                    :options="$estadoOptions"
                    :value="$v['estado'] ?? ''"
                    placeholder="Seleccionar…"
                    :required="true"
                    :allow-clear="false"
                />
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
            <div class="form-field seleccion-ingresos-form__span-2">
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
    </section>

    {{-- Aviso de cédula ya registrada en otros exámenes: exige confirmación --}}
    <div class="alert alert--warning seleccion-ingresos-form__duplicates" x-show="{{ $dupState }}Duplicates.length > 0" x-cloak>
        <p class="mb-2">Ya existen exámenes con esta cédula:</p>
        <ul class="mb-2">
            <template x-for="row in {{ $dupState }}Duplicates" :key="row.id">
                <li>
                    #<span x-text="row.id"></span>
                    — <span x-text="row.full_name"></span>
                    (<span x-text="row.fecha_arl || 's/f'"></span>)
                </li>
            </template>
        </ul>
        <label class="cursos-registros-page__checkbox-label">
            <input type="checkbox" name="confirm_duplicate" value="1" x-model="{{ $dupState }}ConfirmDuplicate">
            Confirmo guardar de todos modos
        </label>
    </div>
</div>

{{-- Pie del modal: cancelar (cierra) y guardar, icon-only canónicos --}}
<div class="seleccion-ingresos-form__footer">
    <button
        type="button"
        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
        title="Cancelar"
        aria-label="Cancelar"
        x-on:click="$dispatch('close-modal', '{{ $modalName }}')"
    >
        <x-lucide-x width="18" height="18" aria-hidden="true" />
    </button>
    <button
        type="submit"
        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
        title="{{ $submitLabel }}"
        aria-label="{{ $submitLabel }}"
    >
        <x-lucide-save width="18" height="18" aria-hidden="true" />
    </button>
</div>
