@php
    $v = $values ?? [];
    $mode = $mode ?? 'create';
    $canEditGh = (bool) ($canEditGh ?? ($mode !== 'review'));
    $canReviewNomina = (bool) ($canReviewNomina ?? ($mode === 'review'));
    $showNomina = (bool) ($showNomina ?? ($mode !== 'create'));
    $alpine = (bool) ($alpine ?? false);
@endphp

<div class="rn-novedad-form">
    @include('areas.gestion_humana.reportes_novedades.partials.employee-lookup-fields', [
        'prefix' => $prefix,
        'values' => $v,
        'canEditGh' => $canEditGh,
        'alpine' => $alpine,
        'showDestino' => false,
        'showTipo' => true,
        'showFechaIngreso' => false,
    ])

    <section class="rn-novedad-form__section">
        <div class="rn-novedad-form__section-head">
            <span class="rn-novedad-form__section-step" aria-hidden="true">
                <span class="rn-novedad-form__section-index">2</span>
                <x-lucide-file-text width="16" height="16" />
            </span>
            <div>
                <h4 class="rn-novedad-form__section-title">Novedad</h4>
                <p class="rn-novedad-form__section-desc">Permiso, licencia o ausencia y su periodo.</p>
            </div>
        </div>

        <div class="rn-novedad-form__grid">
            <div class="form-field">
                <label class="form-label" for="{{ $prefix }}_novedad">Novedad</label>
                @if ($canEditGh)
                    <x-searchable-select
                        :id="$prefix.'_novedad'"
                        name="novedad"
                        :options="$novedadOptions"
                        :value="$v['novedad'] ?? ''"
                        placeholder="Seleccionar…"
                        :required="true"
                        :allow-clear="false"
                    />
                @else
                    <input
                        id="{{ $prefix }}_novedad"
                        type="text"
                        class="form-input"
                        disabled
                        @if ($alpine) x-model="form.novedad" @else value="{{ $v['novedad'] ?? '' }}" @endif
                    >
                @endif
            </div>

            <div class="form-field">
                <label class="form-label" for="{{ $prefix }}_dias_novedad">Días</label>
                <input
                    id="{{ $prefix }}_dias_novedad"
                    @if ($canEditGh) name="dias_novedad" @endif
                    type="number"
                    min="0"
                    class="form-input"
                    @if ($canEditGh) required @endif
                    @disabled(! $canEditGh)
                    @if ($alpine) x-model="form.dias_novedad" @else value="{{ $v['dias_novedad'] ?? '' }}" @endif
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
                <label class="form-label" for="{{ $prefix }}_marca_gh">Marca GH</label>
                <label class="rn-novedad-form__check">
                    <input
                        id="{{ $prefix }}_marca_gh"
                        @if ($canEditGh) name="marca_gh" @endif
                        type="checkbox"
                        value="1"
                        @disabled(! $canEditGh)
                        @if ($alpine)
                            x-model="form.marca_gh"
                        @elseif (! empty($v['marca_gh']))
                            checked
                        @endif
                    >
                    <span>Marcado</span>
                </label>
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
