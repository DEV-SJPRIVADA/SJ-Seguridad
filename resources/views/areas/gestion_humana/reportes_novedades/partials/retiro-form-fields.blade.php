@php
    $v = $values ?? [];
    $mode = $mode ?? 'create';
    $canEditGh = (bool) ($canEditGh ?? ($mode !== 'review'));
    $canReviewNomina = (bool) ($canReviewNomina ?? ($mode === 'review'));
    $showNomina = (bool) ($showNomina ?? ($mode !== 'create'));
    $alpine = (bool) ($alpine ?? false);
    $defaultNovedad = $defaultNovedad ?? 'RETIRO';
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
        <label class="form-label" for="{{ $prefix }}_fecha_ingreso">Fecha ingreso</label>
        <input
            id="{{ $prefix }}_fecha_ingreso"
            @if ($canEditGh) name="fecha_ingreso" @endif
            type="date"
            class="form-input"
            @disabled(! $canEditGh)
            @if ($alpine) x-model="form.fecha_ingreso" @else value="{{ $v['fecha_ingreso'] ?? '' }}" @endif
        >
    </div>

    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_tipo">Tipo</label>
        <input
            id="{{ $prefix }}_tipo"
            @if ($canEditGh) name="tipo" @endif
            type="text"
            class="form-input"
            maxlength="80"
            @disabled(! $canEditGh)
            @if ($alpine) x-model="form.tipo" @else value="{{ $v['tipo'] ?? '' }}" @endif
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
        <label class="form-label" for="{{ $prefix }}_novedad">Novedad</label>
        <input
            id="{{ $prefix }}_novedad"
            @if ($canEditGh) name="novedad" @endif
            type="text"
            class="form-input"
            maxlength="80"
            @if ($canEditGh) required @endif
            @disabled(! $canEditGh)
            @if ($alpine) x-model="form.novedad" @else value="{{ $v['novedad'] ?? $defaultNovedad }}" @endif
        >
    </div>

    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_fecha_retiro">Fecha retiro</label>
        <input
            id="{{ $prefix }}_fecha_retiro"
            @if ($canEditGh) name="fecha_retiro" @endif
            type="date"
            class="form-input"
            @disabled(! $canEditGh)
            @if ($alpine) x-model="form.fecha_retiro" @else value="{{ $v['fecha_retiro'] ?? '' }}" @endif
        >
    </div>

    <div class="form-field">
        <label class="form-label" for="{{ $prefix }}_motivo_retiro">Motivo retiro</label>
        @if ($canEditGh)
            <x-searchable-select
                :id="$prefix.'_motivo_retiro'"
                name="motivo_retiro"
                :options="$motivoOptions"
                :value="$alpine ? '' : ($v['motivo_retiro'] ?? '')"
                placeholder="Seleccionar…"
                :required="false"
                :allow-clear="true"
            />
        @else
            <input
                id="{{ $prefix }}_motivo_retiro"
                type="text"
                class="form-input"
                disabled
                @if ($alpine) x-model="form.motivo_retiro" @else value="{{ $v['motivo_retiro'] ?? '' }}" @endif
            >
        @endif
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
        <div class="form-field" style="grid-column: 1 / -1;">
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
    @endif
</div>
