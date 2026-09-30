@php
    $v = $values ?? [];
    $mode = $mode ?? 'create';
    $canEditGh = (bool) ($canEditGh ?? ($mode !== 'review'));
    $canReviewNomina = (bool) ($canReviewNomina ?? ($mode === 'review'));
    $showNomina = (bool) ($showNomina ?? ($mode !== 'create'));
    $alpine = (bool) ($alpine ?? false);
@endphp

<div class="cursos-registros-page__form-grid">
    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_document_number">Cédula</label>
        <input
            id="{{ $prefix }}_document_number"
            @if ($canEditGh) name="document_number" @endif
            type="text"
            class="form-input"
            maxlength="50"
            @if ($canEditGh) required @endif
            @disabled(! $canEditGh)
            @if ($alpine)
                x-model="form.document_number"
                @if ($canEditGh) x-on:blur="lookupCedula()" @endif
            @else
                value="{{ $v['document_number'] ?? '' }}"
                @if ($canEditGh) x-on:blur="form.document_number = $el.value; lookupCedula()" @endif
            @endif
        >
        <p class="form-hint" x-show="lookupMessage" x-text="lookupMessage" x-cloak></p>
    </div>

    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_employee_name">Nombre</label>
        <input
            id="{{ $prefix }}_employee_name"
            @if ($canEditGh) name="employee_name" @endif
            type="text"
            class="form-input"
            maxlength="255"
            @if ($canEditGh) required @endif
            @disabled(! $canEditGh)
            @if ($alpine) x-model="form.employee_name" @else value="{{ $v['employee_name'] ?? '' }}" @endif
        >
    </div>

    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_cargo">Cargo</label>
        <input
            id="{{ $prefix }}_cargo"
            @if ($canEditGh) name="cargo" @endif
            type="text"
            class="form-input"
            maxlength="150"
            @disabled(! $canEditGh)
            @if ($alpine) x-model="form.cargo" @else value="{{ $v['cargo'] ?? '' }}" @endif
        >
    </div>

    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_destino">Destino</label>
        <input
            id="{{ $prefix }}_destino"
            @if ($canEditGh) name="destino" @endif
            type="text"
            class="form-input"
            maxlength="255"
            @disabled(! $canEditGh)
            @if ($alpine) x-model="form.destino" @else value="{{ $v['destino'] ?? '' }}" @endif
        >
    </div>

    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_tipo_incapacidad">Tipo incapacidad</label>
        @if ($canEditGh)
            <x-searchable-select
                :id="$prefix.'_tipo_incapacidad'"
                name="tipo_incapacidad"
                :options="$tipoOptions"
                :value="$alpine ? '' : ($v['tipo_incapacidad'] ?? '')"
                placeholder="Seleccionar…"
                :required="true"
                :allow-clear="false"
            />
        @else
            <input
                id="{{ $prefix }}_tipo_incapacidad"
                type="text"
                class="form-input"
                disabled
                @if ($alpine) x-model="form.tipo_incapacidad" @else value="{{ $v['tipo_incapacidad'] ?? '' }}" @endif
            >
        @endif
    </div>

    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_dias">Días</label>
        <input
            id="{{ $prefix }}_dias"
            @if ($canEditGh) name="dias" @endif
            type="number"
            min="0"
            class="form-input"
            @if ($canEditGh) required @endif
            @disabled(! $canEditGh)
            @if ($alpine) x-model="form.dias" @else value="{{ $v['dias'] ?? '' }}" @endif
        >
    </div>

    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_fecha_inicio">Fecha inicio</label>
        <input
            id="{{ $prefix }}_fecha_inicio"
            @if ($canEditGh) name="fecha_inicio" @endif
            type="date"
            class="form-input"
            @if ($canEditGh) required @endif
            @disabled(! $canEditGh)
            @if ($alpine) x-model="form.fecha_inicio" @else value="{{ $v['fecha_inicio'] ?? '' }}" @endif
        >
    </div>

    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_fecha_fin">Fecha fin</label>
        <input
            id="{{ $prefix }}_fecha_fin"
            @if ($canEditGh) name="fecha_fin" @endif
            type="date"
            class="form-input"
            @if ($canEditGh) required @endif
            @disabled(! $canEditGh)
            @if ($alpine) x-model="form.fecha_fin" @else value="{{ $v['fecha_fin'] ?? '' }}" @endif
        >
    </div>

    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_fecha_recepcion">Fecha recepción</label>
        <input
            id="{{ $prefix }}_fecha_recepcion"
            @if ($canEditGh) name="fecha_recepcion" @endif
            type="date"
            class="form-input"
            @disabled(! $canEditGh)
            @if ($alpine) x-model="form.fecha_recepcion" @else value="{{ $v['fecha_recepcion'] ?? '' }}" @endif
        >
    </div>

    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_fecha_devolucion">Fecha devolución</label>
        <input
            id="{{ $prefix }}_fecha_devolucion"
            @if ($canEditGh) name="fecha_devolucion" @endif
            type="date"
            class="form-input"
            @disabled(! $canEditGh)
            @if ($alpine) x-model="form.fecha_devolucion" @else value="{{ $v['fecha_devolucion'] ?? '' }}" @endif
        >
    </div>

    <div class="form-field" style="grid-column: 1 / -1;">
        <label class="form-label" for="{{ $prefix }}_observacion_devolucion">Obs. devolución</label>
        <textarea
            id="{{ $prefix }}_observacion_devolucion"
            @if ($canEditGh) name="observacion_devolucion" @endif
            class="form-input"
            rows="2"
            @disabled(! $canEditGh)
            @if ($alpine) x-model="form.observacion_devolucion" @endif
        >@if (! $alpine){{ $v['observacion_devolucion'] ?? '' }}@endif</textarea>
    </div>

    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_fecha_registro_control_roll">Fecha reg. control roll</label>
        <input
            id="{{ $prefix }}_fecha_registro_control_roll"
            @if ($canEditGh) name="fecha_registro_control_roll" @endif
            type="date"
            class="form-input"
            @disabled(! $canEditGh)
            @if ($alpine) x-model="form.fecha_registro_control_roll" @else value="{{ $v['fecha_registro_control_roll'] ?? '' }}" @endif
        >
    </div>

    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_fecha_envio_final">Fecha envío final</label>
        <input
            id="{{ $prefix }}_fecha_envio_final"
            @if ($canEditGh) name="fecha_envio_final" @endif
            type="date"
            class="form-input"
            @disabled(! $canEditGh)
            @if ($alpine) x-model="form.fecha_envio_final" @else value="{{ $v['fecha_envio_final'] ?? '' }}" @endif
        >
    </div>

    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_novedad_control_roll">Novedad control roll</label>
        <input
            id="{{ $prefix }}_novedad_control_roll"
            @if ($canEditGh) name="novedad_control_roll" @endif
            type="text"
            class="form-input"
            maxlength="120"
            @disabled(! $canEditGh)
            @if ($alpine) x-model="form.novedad_control_roll" @else value="{{ $v['novedad_control_roll'] ?? '' }}" @endif
        >
    </div>

    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_extemporanea">Extemporánea</label>
        <label class="inline-flex items-center gap-2 text-sm">
            <input
                id="{{ $prefix }}_extemporanea"
                @if ($canEditGh) name="extemporanea" @endif
                type="checkbox"
                value="1"
                @disabled(! $canEditGh)
                @if ($alpine)
                    x-model="form.extemporanea"
                @else
                    @checked(! empty($v['extemporanea']))
                @endif
            >
            <span>Sí</span>
        </label>
    </div>

    <div class="form-field" style="grid-column: 1 / -1;">
        <label class="form-label" for="{{ $prefix }}_observaciones">Observaciones</label>
        <textarea
            id="{{ $prefix }}_observaciones"
            @if ($canEditGh) name="observaciones" @endif
            class="form-input"
            rows="2"
            @disabled(! $canEditGh)
            @if ($alpine) x-model="form.observaciones" @endif
        >@if (! $alpine){{ $v['observaciones'] ?? '' }}@endif</textarea>
    </div>

    @if ($showNomina)
        <div class="form-field">
            <label class="form-label" for="{{ $prefix }}_observacion_nomina">Observación Nómina</label>
            <input
                id="{{ $prefix }}_observacion_nomina"
                @if ($canReviewNomina) name="observacion_nomina" @endif
                type="text"
                class="form-input"
                maxlength="255"
                @disabled(! $canReviewNomina)
                @if ($alpine) x-model="form.observacion_nomina" @else value="{{ $v['observacion_nomina'] ?? '' }}" @endif
            >
        </div>
        <div class="form-field">
            <label class="form-label" for="{{ $prefix }}_dias_entrega">Días entrega</label>
            <input
                id="{{ $prefix }}_dias_entrega"
                @if ($canReviewNomina) name="dias_entrega" @endif
                type="number"
                min="0"
                class="form-input"
                @disabled(! $canReviewNomina)
                @if ($alpine) x-model="form.dias_entrega" @else value="{{ $v['dias_entrega'] ?? '' }}" @endif
            >
        </div>
    @endif
</div>
