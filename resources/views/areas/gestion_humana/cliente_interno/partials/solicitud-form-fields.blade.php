{{-- Campos del formulario de solicitud (alta / edición). --}}
@php
    $prefix = $prefix ?? 'create';
    $values = $values ?? [];
    $tipoSolicitudOptions = $tipoSolicitudOptions ?? [];
    $estadoFormOptions = $estadoFormOptions ?? [];
    $isEdit = ($mode ?? 'create') === 'edit';
@endphp

<div class="cursos-registros-page__form-grid">
    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_fecha_solicitud">Fecha de solicitud <span class="text-danger">*</span></label>
        <input
            id="{{ $prefix }}_fecha_solicitud"
            name="fecha_solicitud"
            type="date"
            class="form-input"
            required
            @if ($isEdit)
                x-model="editForm.fecha_solicitud"
            @else
                value="{{ $values['fecha_solicitud'] ?? '' }}"
            @endif
        >
    </div>

    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_nombre_apellidos">Nombre y apellidos <span class="text-danger">*</span></label>
        <input
            id="{{ $prefix }}_nombre_apellidos"
            name="nombre_apellidos"
            type="text"
            class="form-input"
            maxlength="255"
            required
            autocomplete="name"
            @if ($isEdit)
                x-model="editForm.nombre_apellidos"
            @else
                value="{{ $values['nombre_apellidos'] ?? '' }}"
            @endif
        >
    </div>

    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_cedula">Cédula <span class="text-danger">*</span></label>
        <input
            id="{{ $prefix }}_cedula"
            name="cedula"
            type="text"
            class="form-input"
            maxlength="50"
            required
            autocomplete="off"
            @if ($isEdit)
                x-model="editForm.cedula"
            @else
                value="{{ $values['cedula'] ?? '' }}"
            @endif
        >
    </div>

    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_correo_electronico">Correo electrónico</label>
        <input
            id="{{ $prefix }}_correo_electronico"
            name="correo_electronico"
            type="email"
            class="form-input"
            maxlength="150"
            autocomplete="email"
            @if ($isEdit)
                x-model="editForm.correo_electronico"
            @else
                value="{{ $values['correo_electronico'] ?? '' }}"
            @endif
        >
    </div>

    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_tipo_solicitud_id">Solicitud <span class="text-danger">*</span></label>
        <x-searchable-select
            id="{{ $prefix }}_tipo_solicitud_id"
            name="tipo_solicitud_id"
            :options="$tipoSolicitudOptions"
            :value="$values['tipo_solicitud_id'] ?? ''"
            placeholder="Seleccionar…"
            :required="true"
            :allow-clear="false"
        />
    </div>

    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_fecha_respuesta">Fecha de respuesta</label>
        <input
            id="{{ $prefix }}_fecha_respuesta"
            name="fecha_respuesta"
            type="date"
            class="form-input"
            @if ($isEdit)
                x-model="editForm.fecha_respuesta"
            @else
                value="{{ $values['fecha_respuesta'] ?? '' }}"
            @endif
        >
    </div>

    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_estado_id">Estado</label>
        <x-searchable-select
            id="{{ $prefix }}_estado_id"
            name="estado_id"
            :options="$estadoFormOptions"
            :value="$values['estado_id'] ?? ''"
            placeholder="Sin estado"
            :allow-clear="true"
        />
    </div>

    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_dias_respuesta">Días de respuesta</label>
        <input
            id="{{ $prefix }}_dias_respuesta"
            name="dias_respuesta"
            type="number"
            class="form-input"
            min="0"
            max="9999"
            step="1"
            @if ($isEdit)
                x-model="editForm.dias_respuesta"
                x-on:input="markDiasTouched()"
            @else
                value="{{ $values['dias_respuesta'] ?? '' }}"
            @endif
            placeholder="Automático (hábiles)"
        >
        <p class="form-hint">Lun–vie entre solicitud y respuesta. Si lo edita, se conserva como valor manual.</p>
        @if ($isEdit)
            <input type="hidden" name="dias_respuesta_touched" :value="diasTouched ? 1 : 0">
            <label class="cursos-registros-page__checkbox-label" style="margin-top:0.5rem;">
                <input type="checkbox" name="recalcular_dias" value="1" x-model="recalcularDias">
                Recalcular días hábiles (quita el override manual)
            </label>
        @endif
    </div>

    <div class="form-field" style="grid-column: 1 / -1;">
        <label class="form-label" for="{{ $prefix }}_novedad">Novedad</label>
        <textarea
            id="{{ $prefix }}_novedad"
            name="novedad"
            class="form-input"
            rows="3"
            maxlength="5000"
            @if ($isEdit)
                x-model="editForm.novedad"
            @else
            @endif
        >@if (! $isEdit){{ $values['novedad'] ?? '' }}@endif</textarea>
    </div>
</div>

<div class="cursos-registros-page__form-actions">
    <button type="submit" class="btn btn--primary">
        {{ $isEdit ? 'Actualizar' : 'Guardar' }}
    </button>
</div>
