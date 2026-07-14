@php
    /** @var \App\Models\CommercialService $service */
@endphp

<div class="form-stack">
    <h4 class="panel-title" style="font-size:1rem;">Servicio / contrato</h4>
    <div class="form-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem;">
        <div class="form-field">
            <x-input-label for="portfolio" value="Portafolio" />
            <select id="portfolio" name="portfolio" class="form-select" required>
                @foreach ($portfolios as $key => $label)
                    <option value="{{ $key }}" @selected(old('portfolio', $service->portfolio) === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('portfolio')" />
        </div>
        <div class="form-field">
            <x-input-label for="contract_number" value="No. contrato" />
            <x-text-input id="contract_number" name="contract_number" class="form-input" :value="old('contract_number', $service->contract_number)" />
            <x-input-error :messages="$errors->get('contract_number')" />
        </div>
        <div class="form-field">
            <x-input-label for="advisor_name" value="Asesor comercial" />
            <x-text-input id="advisor_name" name="advisor_name" class="form-input" :value="old('advisor_name', $service->advisor_name)" />
            <x-input-error :messages="$errors->get('advisor_name')" />
        </div>
        <div class="form-field">
            <x-input-label for="commercial_sector_id" value="Sector" />
            <select id="commercial_sector_id" name="commercial_sector_id" class="form-select">
                <option value="">—</option>
                @foreach ($sectors as $sector)
                    <option value="{{ $sector->id }}" @selected((string) old('commercial_sector_id', $service->commercial_sector_id) === (string) $sector->id)>{{ $sector->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-field">
            <x-input-label for="commercial_client_type_id" value="Tipo cliente" />
            <select id="commercial_client_type_id" name="commercial_client_type_id" class="form-select">
                <option value="">—</option>
                @foreach ($clientTypes as $type)
                    <option value="{{ $type->id }}" @selected((string) old('commercial_client_type_id', $service->commercial_client_type_id) === (string) $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-field">
            <x-input-label for="commercial_service_type_id" value="Tipo de servicio" />
            <select id="commercial_service_type_id" name="commercial_service_type_id" class="form-select">
                <option value="">—</option>
                @foreach ($serviceTypes as $type)
                    <option value="{{ $type->id }}" @selected((string) old('commercial_service_type_id', $service->commercial_service_type_id) === (string) $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="form-field">
        <x-input-label for="service_description" value="Descripcion del servicio" />
        <textarea id="service_description" name="service_description" class="form-input" rows="3">{{ old('service_description', $service->service_description) }}</textarea>
    </div>

    <h4 class="panel-title" style="font-size:1rem;">Contacto operativo</h4>
    <div class="form-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem;">
        <div class="form-field">
            <x-input-label for="contact_name" value="Contacto" />
            <x-text-input id="contact_name" name="contact_name" class="form-input" :value="old('contact_name', $service->contact_name)" />
        </div>
        <div class="form-field">
            <x-input-label for="contact_role" value="Cargo" />
            <x-text-input id="contact_role" name="contact_role" class="form-input" :value="old('contact_role', $service->contact_role)" />
        </div>
        <div class="form-field">
            <x-input-label for="contact_phone" value="Telefono contacto" />
            <x-text-input id="contact_phone" name="contact_phone" class="form-input" :value="old('contact_phone', $service->contact_phone)" />
        </div>
        <div class="form-field">
            <x-input-label for="contact_email" value="Correo" />
            <x-text-input id="contact_email" name="contact_email" class="form-input" :value="old('contact_email', $service->contact_email)" />
        </div>
    </div>

    <h4 class="panel-title" style="font-size:1rem;">Vigencia</h4>
    <div class="form-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:1rem;">
        <div class="form-field">
            <x-input-label for="contract_start" value="Inicio contrato" />
            <x-text-input id="contract_start" name="contract_start" type="date" class="form-input" :value="old('contract_start', optional($service->contract_start)->format('Y-m-d'))" />
        </div>
        <div class="form-field">
            <x-input-label for="contract_end" value="Fin contrato" />
            <x-text-input id="contract_end" name="contract_end" type="date" class="form-input" :value="old('contract_end', optional($service->contract_end)->format('Y-m-d'))" />
        </div>
        <div class="form-field">
            <x-input-label for="duration_months" value="Duracion (meses)" />
            <x-text-input id="duration_months" name="duration_months" type="number" min="0" class="form-input" :value="old('duration_months', $service->duration_months)" />
        </div>
    </div>

    <h4 class="panel-title" style="font-size:1rem;">Checklist documental</h4>
    <div class="form-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:1rem;">
        @foreach ($documentFields as $field => $label)
            <div class="form-field">
                <x-input-label :for="$field" :value="$label" />
                <select id="{{ $field }}" name="{{ $field }}" class="form-select">
                    <option value="">—</option>
                    @foreach ($documentStatuses as $statusKey => $statusLabel)
                        <option value="{{ $statusKey }}" @selected(old($field, $service->{$field}) === $statusKey)>{{ $statusLabel }}</option>
                    @endforeach
                </select>
            </div>
        @endforeach
    </div>
</div>
