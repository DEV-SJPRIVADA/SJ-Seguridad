@php
    $v = $values ?? [];
    $canEditGh = (bool) ($canEditGh ?? true);
    $alpine = (bool) ($alpine ?? false);
    $showDestino = (bool) ($showDestino ?? true);
    $showTipo = (bool) ($showTipo ?? false);
    $showFechaIngreso = (bool) ($showFechaIngreso ?? false);
    $sectionIndex = $sectionIndex ?? 1;
@endphp

<section class="rn-novedad-form__section">
    <div class="rn-novedad-form__section-head">
        <span class="rn-novedad-form__section-step" aria-hidden="true">
            <span class="rn-novedad-form__section-index">{{ $sectionIndex }}</span>
            <x-lucide-user-round-search width="16" height="16" />
        </span>
        <div>
            <h4 class="rn-novedad-form__section-title">Empleado</h4>
            <p class="rn-novedad-form__section-desc">Ingrese la cédula para precargar nombre, cargo y destino desde Ficha.</p>
        </div>
    </div>

    <div class="rn-novedad-form__grid">
        <div class="form-field rn-novedad-form__cedula">
            <label class="form-label" for="{{ $prefix }}_document_number">Cédula</label>
            <div class="rn-novedad-form__cedula-row">
                <input
                    id="{{ $prefix }}_document_number"
                    @if ($canEditGh) name="document_number" @endif
                    type="text"
                    class="form-input"
                    maxlength="50"
                    inputmode="numeric"
                    autocomplete="off"
                    placeholder="Ej. 1111809038"
                    @if ($canEditGh) required @endif
                    @disabled(! $canEditGh)
                    @if ($alpine)
                        x-model="form.document_number"
                        @if ($canEditGh)
                            x-on:blur="lookupCedula()"
                            x-on:keydown.enter.prevent="lookupCedula()"
                        @endif
                    @else
                        value="{{ $v['document_number'] ?? '' }}"
                        @if ($canEditGh)
                            x-on:blur="form.document_number = $el.value; lookupCedula()"
                            x-on:keydown.enter.prevent="form.document_number = $el.value; lookupCedula()"
                        @endif
                    @endif
                >
                <button
                    type="button"
                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                    title="Buscar en Ficha"
                    aria-label="Buscar en Ficha"
                    @if (! $canEditGh) disabled @endif
                    x-on:click.prevent="lookupCedula()"
                    x-bind:disabled="lookupLoading || ! canEdit"
                >
                    <span x-show="! lookupLoading" class="inline-flex">
                        <x-lucide-search width="16" height="16" aria-hidden="true" />
                    </span>
                    <span x-show="lookupLoading" x-cloak class="rn-novedad-form__spinner" aria-hidden="true"></span>
                </button>
            </div>
            <p
                class="rn-novedad-form__lookup-msg"
                x-show="lookupMessage"
                x-cloak
                x-bind:class="{
                    'rn-novedad-form__lookup-msg--ok': lookupOk,
                    'rn-novedad-form__lookup-msg--warn': lookupMessage && ! lookupOk && ! lookupLoading
                }"
                x-text="lookupMessage"
            ></p>
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

        @if ($showDestino)
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
        @endif

        @if ($showTipo)
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
        @endif

        @if ($showFechaIngreso)
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
        @endif
    </div>
</section>
