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
        'showDestino' => true,
        'showTipo' => false,
        'showFechaIngreso' => false,
    ])

    <section class="rn-novedad-form__section">
        <div class="rn-novedad-form__section-head">
            <span class="rn-novedad-form__section-step" aria-hidden="true">
                <span class="rn-novedad-form__section-index">2</span>
                <x-lucide-heart-pulse width="16" height="16" />
            </span>
            <div>
                <h4 class="rn-novedad-form__section-title">Incapacidad</h4>
                <p class="rn-novedad-form__section-desc">Tipo, días y fechas del periodo de incapacidad.</p>
            </div>
        </div>

        <div class="rn-novedad-form__grid">
            <div class="form-field">
                <label class="form-label" for="{{ $prefix }}_tipo_incapacidad">Tipo incapacidad</label>
                @if ($canEditGh)
                    <x-searchable-select
                        :id="$prefix.'_tipo_incapacidad'"
                        name="tipo_incapacidad"
                        :options="$tipoOptions"
                        :value="$v['tipo_incapacidad'] ?? ''"
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
        </div>
    </section>

    <section class="rn-novedad-form__section">
        <div class="rn-novedad-form__section-head">
            <span class="rn-novedad-form__section-step" aria-hidden="true">
                <span class="rn-novedad-form__section-index">3</span>
                <x-lucide-clipboard-list width="16" height="16" />
            </span>
            <div>
                <h4 class="rn-novedad-form__section-title">Control y trámite</h4>
                <p class="rn-novedad-form__section-desc">Recepción, devolución, control roll y observaciones GH.</p>
            </div>
        </div>

        <div class="rn-novedad-form__grid">
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

            <div class="form-field rn-novedad-form__span">
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
                <label class="form-label" for="{{ $prefix }}_dias_entrega_preview">Días entrega</label>
                <input
                    id="{{ $prefix }}_dias_entrega_preview"
                    type="text"
                    class="form-input"
                    readonly
                    disabled
                    @if ($alpine)
                        :value="diasEntregaCalculados === null || diasEntregaCalculados === '' ? '' : diasEntregaCalculados"
                    @else
                        value="{{ $v['dias_entrega'] ?? '' }}"
                    @endif
                >
                <p class="form-hint">Automático (hoy − inicio, o envío final − inicio).</p>
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
                <label class="rn-novedad-form__check">
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
