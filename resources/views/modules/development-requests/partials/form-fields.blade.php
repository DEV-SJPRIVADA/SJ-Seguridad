{{-- Variables: $module, $areas, $leaders, $tipoOptions, $prioridadOptions, $developmentRequest? --}}
@php
    $r = $developmentRequest ?? null;
@endphp

<div class="dev-req-form">
    <div class="dev-req-form__meta">
        <div class="dev-req-form__meta-item">
            <span class="dev-req-form__meta-label">Formato</span>
            <span class="dev-req-form__meta-value">FO-TIC-23</span>
        </div>
        <div class="dev-req-form__meta-item">
            <span class="dev-req-form__meta-label">Area de trabajo</span>
            <span class="dev-req-form__meta-value">{{ strtoupper((string) $module) }}</span>
        </div>
        @if ($r)
            <div class="dev-req-form__meta-item">
                <span class="dev-req-form__meta-label">Estado</span>
                <span class="dev-req-form__meta-value">{{ $r->estadoLabel() }}</span>
            </div>
            @if ($r->code)
                <div class="dev-req-form__meta-item">
                    <span class="dev-req-form__meta-label">Codigo</span>
                    <span class="dev-req-form__meta-value">{{ $r->code }}</span>
                </div>
            @endif
        @else
            <div class="dev-req-form__meta-item">
                <span class="dev-req-form__meta-label">Estado</span>
                <span class="dev-req-form__meta-value">Nueva solicitud</span>
            </div>
        @endif
    </div>

    <section class="dev-req-form__section">
        <header class="dev-req-form__section-head">
            <span class="dev-req-form__section-step">1</span>
            <div>
                <h4 class="dev-req-form__section-title">Clasificacion</h4>
                <p class="dev-req-form__section-desc">Define area, tipo, prioridad sugerida y el lider que debe aprobar.</p>
            </div>
        </header>

        <div class="form-grid form-grid--two">
            <div class="form-field">
                <label class="form-label" for="area_key">Area solicitante *</label>
                <x-searchable-select
                    id="area_key"
                    name="area_key"
                    :options="collect($areas)->map(fn ($label, $value) => ['value' => (string) $value, 'label' => $label])->values()->all()"
                    :value="old('area_key', $r?->area_key ?? $module)"
                    placeholder="Seleccione area"
                    :required="true"
                    :allowClear="false"
                />
                <x-input-error :messages="$errors->get('area_key')" />
            </div>
            <div class="form-field">
                <label class="form-label" for="request_type">Tipo de solicitud *</label>
                <x-searchable-select
                    id="request_type"
                    name="request_type"
                    :options="$tipoOptions"
                    :value="old('request_type', $r?->request_type)"
                    placeholder="Seleccione tipo"
                    :required="true"
                    :allowClear="false"
                />
                <x-input-error :messages="$errors->get('request_type')" />
            </div>
            <div class="form-field">
                <label class="form-label" for="suggested_priority">Prioridad sugerida *</label>
                <x-searchable-select
                    id="suggested_priority"
                    name="suggested_priority"
                    :options="$prioridadOptions"
                    :value="old('suggested_priority', $r?->suggested_priority)"
                    placeholder="Seleccione prioridad"
                    :required="true"
                    :allowClear="false"
                />
                <x-input-error :messages="$errors->get('suggested_priority')" />
            </div>
            <div class="form-field">
                <label class="form-label" for="leader_id">Lider de area que aprueba *</label>
                <x-searchable-select
                    id="leader_id"
                    name="leader_id"
                    :options="collect($leaders)->map(fn ($d) => ['value' => (string) $d->id, 'label' => $d->name])->all()"
                    :value="old('leader_id', $r?->leader_id)"
                    placeholder="Seleccione lider"
                    searchPlaceholder="Buscar lider…"
                    :required="true"
                    :allowClear="false"
                />
                <x-input-error :messages="$errors->get('leader_id')" />
            </div>
        </div>
    </section>

    <section class="dev-req-form__section">
        <header class="dev-req-form__section-head">
            <span class="dev-req-form__section-step">2</span>
            <div>
                <h4 class="dev-req-form__section-title">Identificacion del requerimiento</h4>
                <p class="dev-req-form__section-desc">Nombre claro del pedido y datos de quien lo solicita.</p>
            </div>
        </header>

        <div class="form-field bottom-spaced">
            <label class="form-label" for="title">Nombre del requerimiento *</label>
            <input
                type="text"
                name="title"
                id="title"
                class="form-input"
                maxlength="255"
                required
                placeholder="Ej. Automatizar reporte semanal de incidencias"
                value="{{ old('title', $r?->title) }}"
            >
            <x-input-error :messages="$errors->get('title')" />
        </div>

        <div class="form-grid form-grid--two">
            <div class="form-field">
                <label class="form-label" for="requester_name">Persona que solicita *</label>
                <input type="text" name="requester_name" id="requester_name" class="form-input" required
                       value="{{ old('requester_name', $r?->requester_name ?? ($defaultRequester['name'] ?? '')) }}">
                <x-input-error :messages="$errors->get('requester_name')" />
            </div>
            <div class="form-field">
                <label class="form-label" for="requester_position">Cargo</label>
                <input type="text" name="requester_position" id="requester_position" class="form-input"
                       value="{{ old('requester_position', $r?->requester_position) }}">
            </div>
            <div class="form-field">
                <label class="form-label" for="requester_email">Correo *</label>
                <input type="email" name="requester_email" id="requester_email" class="form-input" required
                       value="{{ old('requester_email', $r?->requester_email ?? ($defaultRequester['email'] ?? '')) }}">
                <x-input-error :messages="$errors->get('requester_email')" />
            </div>
            <div class="form-field">
                <label class="form-label" for="requester_phone">Telefono / ext</label>
                <input type="text" name="requester_phone" id="requester_phone" class="form-input"
                       value="{{ old('requester_phone', $r?->requester_phone) }}">
            </div>
            <div class="form-field">
                <label class="form-label" for="proceso_sede">Proceso / sede</label>
                <input type="text" name="proceso_sede" id="proceso_sede" class="form-input"
                       value="{{ old('proceso_sede', $r?->proceso_sede) }}">
            </div>
            <div class="form-field">
                <label class="form-label" for="associated_norm">Norma / formato asociado</label>
                <input type="text" name="associated_norm" id="associated_norm" class="form-input"
                       value="{{ old('associated_norm', $r?->associated_norm) }}">
            </div>
            <div class="form-field">
                <label class="form-label" for="desired_date">Fecha deseada</label>
                <input type="date" name="desired_date" id="desired_date" class="form-input"
                       value="{{ old('desired_date', optional($r?->desired_date)?->format('Y-m-d')) }}">
            </div>
        </div>
    </section>

    <section class="dev-req-form__section">
        <header class="dev-req-form__section-head">
            <span class="dev-req-form__section-step">3</span>
            <div>
                <h4 class="dev-req-form__section-title">Necesidad y contexto</h4>
                <p class="dev-req-form__section-desc">Explica el problema actual, lo que debe hacer el sistema y quienes lo usaran.</p>
            </div>
        </header>

        @foreach ([
            'description' => ['label' => 'Descripcion general *', 'hint' => 'Resumen del objetivo de negocio en pocas lineas.', 'rows' => 3],
            'current_process_problem' => ['label' => 'Como se hace hoy y cual es el problema *', 'hint' => 'Describe el proceso actual y el dolor operativo.', 'rows' => 4],
            'desired_steps' => ['label' => 'Que necesita que haga el sistema (paso a paso) *', 'hint' => 'Lista los pasos esperados del flujo.', 'rows' => 4],
            'users_description' => ['label' => 'Quien usara esto *', 'hint' => 'Roles, areas o perfiles que interactuaran con la solucion.', 'rows' => 3],
        ] as $field => $meta)
            <div class="form-field">
                <label class="form-label" for="{{ $field }}">{{ $meta['label'] }}</label>
                <p class="dev-req-form__hint">{{ $meta['hint'] }}</p>
                <textarea
                    name="{{ $field }}"
                    id="{{ $field }}"
                    class="form-textarea"
                    rows="{{ $meta['rows'] }}"
                    required
                >{{ old($field, $r?->{$field}) }}</textarea>
                <x-input-error :messages="$errors->get($field)" />
            </div>
        @endforeach
    </section>

    <section class="dev-req-form__section">
        <header class="dev-req-form__section-head">
            <span class="dev-req-form__section-step">4</span>
            <div>
                <h4 class="dev-req-form__section-title">Alcance y reglas</h4>
                <p class="dev-req-form__section-desc">Delimita que incluye, que queda fuera y que no se debe permitir.</p>
            </div>
        </header>

        @foreach ([
            'restrictions' => ['label' => 'Que no se deberia permitir *', 'required' => true, 'rows' => 3],
            'scope_in' => ['label' => 'Alcance: que SI incluye *', 'required' => true, 'rows' => 3],
            'scope_out' => ['label' => 'Fuera de alcance', 'required' => false, 'rows' => 3],
        ] as $field => $meta)
            <div class="form-field">
                <label class="form-label" for="{{ $field }}">{{ $meta['label'] }}</label>
                <textarea
                    name="{{ $field }}"
                    id="{{ $field }}"
                    class="form-textarea"
                    rows="{{ $meta['rows'] }}"
                    @if ($meta['required']) required @endif
                >{{ old($field, $r?->{$field}) }}</textarea>
                <x-input-error :messages="$errors->get($field)" />
            </div>
        @endforeach
    </section>

    <section class="dev-req-form__section">
        <header class="dev-req-form__section-head">
            <span class="dev-req-form__section-step">5</span>
            <div>
                <h4 class="dev-req-form__section-title">Criterios y cierre</h4>
                <p class="dev-req-form__section-desc">Define como se valida el exito, reportes esperados y justificacion de urgencia.</p>
            </div>
        </header>

        @foreach ([
            'acceptance_criteria' => ['label' => 'Criterio de aceptacion *', 'required' => true, 'rows' => 3],
            'reports' => ['label' => 'Reportes o indicadores', 'required' => false, 'rows' => 3],
            'desired_date_justification' => ['label' => 'Justificacion de urgencia / fecha', 'required' => false, 'rows' => 3],
            'notes' => ['label' => 'Notas adicionales', 'required' => false, 'rows' => 3],
        ] as $field => $meta)
            <div class="form-field">
                <label class="form-label" for="{{ $field }}">{{ $meta['label'] }}</label>
                <textarea
                    name="{{ $field }}"
                    id="{{ $field }}"
                    class="form-textarea"
                    rows="{{ $meta['rows'] }}"
                    @if ($meta['required']) required @endif
                >{{ old($field, $r?->{$field}) }}</textarea>
                <x-input-error :messages="$errors->get($field)" />
            </div>
        @endforeach
    </section>

    <section class="dev-req-form__section">
        <header class="dev-req-form__section-head">
            <span class="dev-req-form__section-step">6</span>
            <div>
                <h4 class="dev-req-form__section-title">Anexos</h4>
                <p class="dev-req-form__section-desc">Adjunta evidencias, bocetos o formatos de apoyo (PDF, Office o imagen).</p>
            </div>
        </header>

        <div class="form-field">
            <label class="form-label" for="attachments">Archivos</label>
            <label class="dev-req-form__upload" for="attachments">
                <span class="dev-req-form__upload-icon" aria-hidden="true">
                    <x-lucide-paperclip width="20" height="20" />
                </span>
                <span class="dev-req-form__upload-copy">
                    <span class="dev-req-form__upload-title">Seleccionar archivos</span>
                    <span class="dev-req-form__upload-hint">PDF, Word, Excel o imagen. Puedes elegir varios.</span>
                </span>
                <input
                    type="file"
                    name="attachments[]"
                    id="attachments"
                    class="dev-req-form__upload-input"
                    multiple
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp"
                >
            </label>
            <x-input-error :messages="$errors->get('attachments')" />
            <x-input-error :messages="$errors->get('attachments.*')" />
        </div>

        @if ($r?->attachments?->isNotEmpty())
            <div class="dev-req-form__attachments">
                <p class="form-label">Anexos actuales</p>
                <ul class="dev-req-form__attachment-list">
                    @foreach ($r->attachments as $att)
                        <li class="dev-req-form__attachment-item">
                            <x-lucide-file width="16" height="16" aria-hidden="true" />
                            <a href="{{ route('development-requests.attachments.download', ['module' => $module, 'development_request' => $r, 'attachment' => $att]) }}">
                                {{ $att->original_name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </section>
</div>
