{{-- Variables: $module, $areas, $leaders, $tipoOptions, $prioridadOptions, $developmentRequest? --}}
@php
    $r = $developmentRequest ?? null;
@endphp

<div class="dashboard-stat-grid bottom-spaced">
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

<div class="form-field bottom-spaced">
    <label class="form-label" for="title">Nombre del requerimiento *</label>
    <input type="text" name="title" id="title" class="form-input" maxlength="255" required
           value="{{ old('title', $r?->title) }}">
    <x-input-error :messages="$errors->get('title')" />
</div>

<div class="dashboard-stat-grid bottom-spaced">
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

@foreach ([
    'description' => 'Descripcion general *',
    'current_process_problem' => 'Como se hace hoy y cual es el problema *',
    'desired_steps' => 'Que necesita que haga el sistema (paso a paso) *',
    'users_description' => 'Quien usara esto *',
    'restrictions' => 'Que no se deberia permitir *',
    'scope_in' => 'Alcance: que SI incluye *',
    'scope_out' => 'Fuera de alcance',
    'acceptance_criteria' => 'Criterio de aceptacion *',
    'reports' => 'Reportes o indicadores',
    'desired_date_justification' => 'Justificacion de urgencia / fecha',
    'notes' => 'Notas adicionales',
] as $field => $label)
    <div class="form-field bottom-spaced">
        <label class="form-label" for="{{ $field }}">{{ $label }}</label>
        <textarea name="{{ $field }}" id="{{ $field }}" class="form-input" rows="3"
            @if (str_ends_with($label, '*')) required @endif
        >{{ old($field, $r?->{$field}) }}</textarea>
        <x-input-error :messages="$errors->get($field)" />
    </div>
@endforeach

<div class="form-field bottom-spaced">
    <label class="form-label" for="attachments">Anexos (PDF, Office, imagen)</label>
    <input type="file" name="attachments[]" id="attachments" class="form-input" multiple
           accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp">
    <x-input-error :messages="$errors->get('attachments')" />
    <x-input-error :messages="$errors->get('attachments.*')" />
</div>

@if ($r?->attachments?->isNotEmpty())
    <div class="bottom-spaced">
        <p class="form-label">Anexos actuales</p>
        <ul>
            @foreach ($r->attachments as $att)
                <li>
                    <a href="{{ route('development-requests.attachments.download', ['module' => $module, 'development_request' => $r, 'attachment' => $att]) }}">
                        {{ $att->original_name }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
