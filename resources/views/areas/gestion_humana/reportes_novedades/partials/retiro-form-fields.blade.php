@php
    $v = $values ?? [];
    $mode = $mode ?? 'create';
    $canEditGh = (bool) ($canEditGh ?? ($mode !== 'review'));
    $canReviewNomina = (bool) ($canReviewNomina ?? ($mode === 'review'));
    $showNomina = (bool) ($showNomina ?? ($mode !== 'create'));
    $alpine = (bool) ($alpine ?? false);
    $defaultNovedad = $defaultNovedad ?? 'RETIRO';
@endphp

<div class="rn-novedad-form">
    @include('areas.gestion_humana.reportes_novedades.partials.employee-lookup-fields', [
        'prefix' => $prefix,
        'values' => $v,
        'canEditGh' => $canEditGh,
        'alpine' => $alpine,
        'showDestino' => true,
        'showTipo' => true,
        'showFechaIngreso' => true,
    ])

    <section class="rn-novedad-form__section">
        <div class="rn-novedad-form__section-head">
            <span class="rn-novedad-form__section-step" aria-hidden="true">
                <span class="rn-novedad-form__section-index">2</span>
                <x-lucide-user-minus width="16" height="16" />
            </span>
            <div>
                <h4 class="rn-novedad-form__section-title">Novedad de retiro</h4>
                <p class="rn-novedad-form__section-desc">Fecha, motivo y observaciones del retiro.</p>
            </div>
        </div>

        <div class="rn-novedad-form__grid">
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
                        :value="$v['motivo_retiro'] ?? ''"
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

            <div class="form-field rn-novedad-form__span">
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
        </div>
    </section>

    @if ($showNomina)
        <section class="rn-novedad-form__section rn-novedad-form__section--nomina">
            <div class="rn-novedad-form__section-head">
                <span class="rn-novedad-form__section-step rn-novedad-form__section-step--nomina" aria-hidden="true">
                    <x-lucide-badge-check width="16" height="16" />
                </span>
                <div>
                    <h4 class="rn-novedad-form__section-title">Revisión Nómina</h4>
                    <p class="rn-novedad-form__section-desc">Solo editable con permiso de revisar.</p>
                </div>
            </div>
            <div class="rn-novedad-form__grid">
                <div class="form-field rn-novedad-form__span">
                    <label class="form-label" for="{{ $prefix }}_observacion_nomina">Observación Nómina</label>
                    <input
                        id="{{ $prefix }}_observacion_nomina"
                        @if ($canReviewNomina) name="observacion_nomina" @endif
                        type="text"
                        class="form-input{{ (! $alpine && filled($v['observacion_nomina'] ?? null)) ? ' rn-novedad-form__input--nomina-filled' : '' }}"
                        maxlength="255"
                        @disabled(! $canReviewNomina)
                        @if ($alpine)
                            x-model="form.observacion_nomina"
                            :class="{ 'rn-novedad-form__input--nomina-filled': (form.observacion_nomina || '').toString().trim() !== '' }"
                        @else
                            value="{{ $v['observacion_nomina'] ?? '' }}"
                        @endif
                    >
                </div>
            </div>
        </section>
    @endif
</div>
