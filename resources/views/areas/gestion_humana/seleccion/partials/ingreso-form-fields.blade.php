{{--
  Variables: $prefix, $cityOptions, $positionOptions, $bloodTypeOptions,
  $clientOptions, $uniformOptions, $responsableOptions, $estadoOptions,
  $jefeOperativoOptions, $values, $mode

  En modo 'edit' los inputs de texto se enlazan con x-model="editForm.*" y los
  searchable-select se setean desde openEdit() con setSearchableValue(); en modo
  'create' se usan los value iniciales (old()).
--}}
@php
    $v = $values ?? [];
    $isEdit = ($mode ?? 'create') === 'edit';

    // Atributo Alpine para enlazar inputs de texto al estado de edición
    $bind = fn (string $field): string => $isEdit ? 'x-model="editForm.'.$field.'"' : '';

    // Estado duplicados (create|edit) para el aviso y confirmación
    $dupState = $isEdit ? 'edit' : 'create';

    // Cliente y dotación, RH y fecha de ingreso solo obligatorios si el estado es CONTRATADO
    // (getters Alpine createRequiresHiringData / editRequiresHiringData; selects vía syncHiringRequired).
    $requiresExpr = $isEdit ? 'editRequiresHiringData' : 'createRequiresHiringData';
    $hiringRequired = 'x-bind:required="'.$requiresExpr.'"';
    $hiringOptional = '<span class="seleccion-ingresos-form__optional" x-show="! '.$requiresExpr.'" x-cloak>(opcional)</span>';

    $submitLabel = $isEdit ? 'Actualizar ingreso' : 'Guardar ingreso';
    $modalName = $isEdit ? 'seleccion-ingreso-editar' : 'seleccion-ingreso-nuevo';
@endphp

<div class="seleccion-ingresos-form">
    {{-- Sección 1: identidad y contacto del aspirante --}}
    <section class="seleccion-ingresos-form__section" aria-labelledby="{{ $prefix }}_section_aspirante">
        <header class="seleccion-ingresos-form__section-head">
            <span class="seleccion-ingresos-form__section-icon" aria-hidden="true">
                <x-lucide-user-round width="16" height="16" aria-hidden="true" />
            </span>
            <h4 id="{{ $prefix }}_section_aspirante" class="seleccion-ingresos-form__section-title">Datos del aspirante</h4>
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
                    x-on:blur="lookupDuplicates($event.target.value, '{{ $mode }}')"
                >
            </div>
            <div class="form-field seleccion-ingresos-form__span-2">
                <label class="form-label" for="{{ $prefix }}_full_name">APELLIDOS Y NOMBRE</label>
                <input id="{{ $prefix }}_full_name" name="full_name" type="text" class="form-input" maxlength="255" required autocomplete="off" value="{{ $v['full_name'] ?? '' }}" {!! $bind('full_name') !!}>
            </div>
            <div class="form-field">
                <label class="form-label" for="{{ $prefix }}_email">CORREO</label>
                <input id="{{ $prefix }}_email" name="email" type="email" class="form-input" maxlength="150" required autocomplete="off" value="{{ $v['email'] ?? '' }}" {!! $bind('email') !!}>
            </div>
            <div class="form-field">
                <label class="form-label" for="{{ $prefix }}_phone">TELÉFONO</label>
                <input id="{{ $prefix }}_phone" name="phone" type="tel" class="form-input" maxlength="40" required autocomplete="off" value="{{ $v['phone'] ?? '' }}" {!! $bind('phone') !!}>
            </div>
            <div class="form-field">
                <label class="form-label" for="{{ $prefix }}_blood_type_code">RH {!! $hiringOptional !!}</label>
                <x-searchable-select
                    id="{{ $prefix }}_blood_type_code"
                    name="blood_type_code"
                    :options="$bloodTypeOptions"
                    :value="$v['blood_type_code'] ?? ''"
                    placeholder="Seleccionar…"
                    :required="false"
                    :allow-clear="false"
                />
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
            <div class="form-field seleccion-ingresos-form__span-2">
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
        </div>

        {{-- Aviso de cédula ya registrada: se confirma explícitamente para poder guardar --}}
        <div class="alert alert--warning seleccion-ingresos-form__duplicates" x-show="{{ $dupState }}Duplicates.length > 0" x-cloak>
            <p class="mb-2">Ya existen ingresos con esta cédula:</p>
            <ul class="mb-2">
                <template x-for="row in {{ $dupState }}Duplicates" :key="row.id">
                    <li>
                        #<span x-text="row.id"></span>
                        — <span x-text="row.full_name"></span>
                        (<span x-text="row.fecha_ingreso || 's/f'"></span>)
                    </li>
                </template>
            </ul>
            <label class="cursos-registros-page__checkbox-label">
                <input type="checkbox" name="confirm_duplicate" value="1" x-model="{{ $dupState }}ConfirmDuplicate">
                Confirmo guardar de todos modos
            </label>
        </div>
    </section>

    {{-- Sección 2: cliente asignado y dotación (tallas) --}}
    <section class="seleccion-ingresos-form__section" aria-labelledby="{{ $prefix }}_section_dotacion">
        <header class="seleccion-ingresos-form__section-head">
            <span class="seleccion-ingresos-form__section-icon" aria-hidden="true">
                <x-lucide-shirt width="16" height="16" aria-hidden="true" />
            </span>
            <h4 id="{{ $prefix }}_section_dotacion" class="seleccion-ingresos-form__section-title">Cliente y dotación</h4>
            <span class="seleccion-ingresos-form__optional">Obligatorios solo si el estado es Contratado (igual que RH y fecha de ingreso)</span>
        </header>

        <div class="seleccion-ingresos-form__grid">
            <div class="form-field seleccion-ingresos-form__span-2">
                <label class="form-label" for="{{ $prefix }}_commercial_client_id">CLIENTE {!! $hiringOptional !!}</label>
                <x-searchable-select
                    id="{{ $prefix }}_commercial_client_id"
                    name="commercial_client_id"
                    :options="$clientOptions"
                    :value="$v['commercial_client_id'] ?? ''"
                    placeholder="Seleccionar…"
                    :required="false"
                    :allow-clear="false"
                />
            </div>
            <div class="form-field">
                <label class="form-label" for="{{ $prefix }}_requisition_uniform_id">TIPO DOTACIÓN {!! $hiringOptional !!}</label>
                <x-searchable-select
                    id="{{ $prefix }}_requisition_uniform_id"
                    name="requisition_uniform_id"
                    :options="$uniformOptions"
                    :value="$v['requisition_uniform_id'] ?? ''"
                    placeholder="Seleccionar…"
                    :required="false"
                    :allow-clear="false"
                />
            </div>
            <div class="form-field">
                <label class="form-label" for="{{ $prefix }}_shirt_size">TALLA CAMISA {!! $hiringOptional !!}</label>
                <input id="{{ $prefix }}_shirt_size" name="shirt_size" type="text" class="form-input" maxlength="40" {!! $hiringRequired !!} value="{{ $v['shirt_size'] ?? '' }}" {!! $bind('shirt_size') !!}>
            </div>
            <div class="form-field">
                <label class="form-label" for="{{ $prefix }}_pants_size">TALLA PANTALÓN {!! $hiringOptional !!}</label>
                <input id="{{ $prefix }}_pants_size" name="pants_size" type="text" class="form-input" maxlength="40" {!! $hiringRequired !!} value="{{ $v['pants_size'] ?? '' }}" {!! $bind('pants_size') !!}>
            </div>
            <div class="form-field">
                <label class="form-label" for="{{ $prefix }}_shoes_size">TALLA ZAPATOS {!! $hiringOptional !!}</label>
                <input id="{{ $prefix }}_shoes_size" name="shoes_size" type="text" class="form-input" maxlength="40" {!! $hiringRequired !!} value="{{ $v['shoes_size'] ?? '' }}" {!! $bind('shoes_size') !!}>
            </div>
        </div>
    </section>

    {{-- Sección 3: fechas, estado y responsables del proceso --}}
    <section class="seleccion-ingresos-form__section" aria-labelledby="{{ $prefix }}_section_proceso">
        <header class="seleccion-ingresos-form__section-head">
            <span class="seleccion-ingresos-form__section-icon" aria-hidden="true">
                <x-lucide-clipboard-check width="16" height="16" aria-hidden="true" />
            </span>
            <h4 id="{{ $prefix }}_section_proceso" class="seleccion-ingresos-form__section-title">Proceso de selección</h4>
        </header>

        <div class="seleccion-ingresos-form__grid">
            <div class="form-field">
                <label class="form-label" for="{{ $prefix }}_fecha_ingreso">FECHA DE INGRESO {!! $hiringOptional !!}</label>
                <input id="{{ $prefix }}_fecha_ingreso" name="fecha_ingreso" type="date" class="form-input" {!! $hiringRequired !!} value="{{ $v['fecha_ingreso'] ?? '' }}" {!! $bind('fecha_ingreso') !!}>
            </div>
            <div class="form-field">
                <label class="form-label" for="{{ $prefix }}_fecha_asistencia">
                    FECHA DE ASISTENCIA <span class="seleccion-ingresos-form__optional">(opcional)</span>
                </label>
                <input id="{{ $prefix }}_fecha_asistencia" name="fecha_asistencia" type="date" class="form-input" value="{{ $v['fecha_asistencia'] ?? '' }}" {!! $bind('fecha_asistencia') !!}>
            </div>
            <div class="form-field">
                <label class="form-label" for="{{ $prefix }}_estado_code">ESTADO</label>
                {{-- El estado decide si los datos de contratación son obligatorios --}}
                <x-searchable-select
                    id="{{ $prefix }}_estado_code"
                    name="estado_code"
                    :options="$estadoOptions"
                    :value="$v['estado_code'] ?? ''"
                    placeholder="Seleccionar…"
                    :required="true"
                    :allow-clear="false"
                    x-on:change="onEstadoChange($event, '{{ $dupState }}')"
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
            <div class="form-field">
                <label class="form-label" for="{{ $prefix }}_jefe_ope_code">JEFE OPE ASIGNADO</label>
                <x-searchable-select
                    id="{{ $prefix }}_jefe_ope_code"
                    name="jefe_ope_code"
                    :options="$jefeOperativoOptions"
                    :value="$v['jefe_ope_code'] ?? ''"
                    placeholder="Seleccionar…"
                    :required="true"
                    :allow-clear="false"
                />
                @if (empty($jefeOperativoOptions))
                    <p class="seleccion-ingresos-page__form-hint">
                        No hay jefes operativos en el catálogo. Agrégalos en la pestaña Catálogos de Selección para poder guardar el ingreso.
                    </p>
                @endif
            </div>
            <div class="form-field">
                <label class="form-label" for="{{ $prefix }}_referido">
                    REFERIDO <span class="seleccion-ingresos-form__optional">(opcional)</span>
                </label>
                <input id="{{ $prefix }}_referido" name="referido" type="text" class="form-input" maxlength="255" value="{{ $v['referido'] ?? '' }}" {!! $bind('referido') !!}>
            </div>
            {{-- Reemplazo: la cédula se busca en Ficha al salir del campo y llena «Reemplaza a»; ambos editables --}}
            <div class="form-field">
                <label class="form-label" for="{{ $prefix }}_reemplaza_cedula">
                    CÉDULA REEMPLAZO <span class="seleccion-ingresos-form__optional">(opcional)</span>
                </label>
                <input
                    id="{{ $prefix }}_reemplaza_cedula"
                    name="reemplaza_cedula"
                    type="text"
                    class="form-input"
                    maxlength="50"
                    inputmode="numeric"
                    autocomplete="off"
                    placeholder="Buscar en Ficha…"
                    value="{{ $v['reemplaza_cedula'] ?? '' }}"
                    {!! $bind('reemplaza_cedula') !!}
                    x-on:change="lookupReemplazo($event.target.value, '{{ $dupState }}')"
                    x-on:keydown.enter.prevent="lookupReemplazo($event.target.value, '{{ $dupState }}')"
                >
                <p
                    class="seleccion-ingresos-form__lookup-hint"
                    x-show="reemplazoLookup.{{ $dupState }}.message"
                    x-cloak
                    x-bind:class="{
                        'seleccion-ingresos-form__lookup-hint--ok': reemplazoLookup.{{ $dupState }}.found,
                        'seleccion-ingresos-form__lookup-hint--warn': ! reemplazoLookup.{{ $dupState }}.found && ! reemplazoLookup.{{ $dupState }}.loading,
                    }"
                    x-text="reemplazoLookup.{{ $dupState }}.message"
                ></p>
            </div>
            <div class="form-field seleccion-ingresos-form__span-2">
                <label class="form-label" for="{{ $prefix }}_reemplaza_a">REEMPLAZA A</label>
                <input id="{{ $prefix }}_reemplaza_a" name="reemplaza_a" type="text" class="form-input" maxlength="255" required autocomplete="off" value="{{ $v['reemplaza_a'] ?? '' }}" {!! $bind('reemplaza_a') !!}>
            </div>
            <div class="form-field seleccion-ingresos-form__span-3">
                <label class="form-label" for="{{ $prefix }}_observaciones">
                    OBSERVACIONES <span class="seleccion-ingresos-form__optional">(opcional)</span>
                </label>
                <textarea id="{{ $prefix }}_observaciones" name="observaciones" rows="3" class="form-input" maxlength="1000" placeholder="Observaciones del proceso…" {!! $bind('observaciones') !!}>{{ $v['observaciones'] ?? '' }}</textarea>
            </div>
        </div>
    </section>
</div>

{{-- Campos faltantes / inválidos detectados al pulsar Guardar (validación visible en el modal) --}}
<div class="alert alert--danger seleccion-ingresos-form__errors" x-show="formErrors.{{ $dupState }}.length > 0" x-cloak role="alert">
    <p class="mb-0"><strong>Revise los siguientes campos:</strong></p>
    <ul class="mb-0">
        <template x-for="message in formErrors.{{ $dupState }}" :key="message">
            <li x-text="message"></li>
        </template>
    </ul>
</div>

{{-- Pie fijo del modal: cancelar (cierra) y guardar, icon-only canónicos --}}
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
        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary seleccion-ingresos-form__submit"
        title="{{ $submitLabel }}"
        aria-label="{{ $submitLabel }}"
    >
        <x-lucide-save width="18" height="18" aria-hidden="true" />
    </button>
</div>
