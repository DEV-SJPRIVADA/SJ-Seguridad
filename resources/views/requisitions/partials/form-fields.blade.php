<div class="form-grid form-grid--two">

    {{-- GRUPO 1: CAMPOS PARA TODOS (MARCADOS CON OK) --}}
    @if ($requisition)
        <div class="form-field">
            <x-input-label value="Fecha de solicitud" />
            <input type="text" class="form-input" value="{{ $requisition->request_date?->format('Y-m-d') }}" readonly>
        </div>
    @endif

    <div class="form-field">
        <x-input-label value="Lider / solicitante" />
        <input type="text" class="form-input" value="{{ old('leader_name', $requisition?->leader_name ?? auth()->user()->name) }}" readonly>
    </div>

    <div class="form-field">
        <x-input-label value="Area solicitante" />
        <input type="text" class="form-input" value="{{ $moduleLabel }}" readonly>
    </div>

    <div class="form-field">
        <x-input-label for="position_id" value="Cargo solicitado" />
        <select id="position_id" name="position_id" class="form-select" required>
            <option value="">Selecciona un cargo</option>
            @foreach ($catalogs['positions'] as $item)
                <option value="{{ $item->id }}" @selected((string) old('position_id', $requisition?->position_id) === (string) $item->id)>{{ $item->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('position_id')" />
    </div>

    <div class="form-field">
        <x-input-label for="sex" value="Sexo" />
        <select id="sex" name="sex" class="form-select" required>
            <option value="">Selecciona una opcion</option>
            @foreach ($sexOptions as $sexKey => $sexLabel)
                <option value="{{ $sexKey }}" @selected(old('sex', $requisition?->sex) === $sexKey)>{{ $sexLabel }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('sex')" />
    </div>

    <div class="form-field">
        <x-input-label for="quantity" value="Cantidad" />
        <input id="quantity" name="quantity" type="number" min="1" max="999" class="form-input" value="{{ old('quantity', $requisition?->quantity ?? 1) }}" required>
        <x-input-error :messages="$errors->get('quantity')" />
    </div>

    <div class="form-field">
        <x-input-label for="replacement_document" value="Cedula a quien reemplaza" />
        <input id="replacement_document" name="replacement_document" type="text" class="form-input" value="{{ old('replacement_document', $requisition?->replacement_document) }}">
        <x-input-error :messages="$errors->get('replacement_document')" />
    </div>

    <div class="form-field">
        <x-input-label for="replacement_name" value="Nombre completo a quien reemplaza" />
        <input id="replacement_name" name="replacement_name" type="text" class="form-input" value="{{ old('replacement_name', $requisition?->replacement_name) }}">
        <x-input-error :messages="$errors->get('replacement_name')" />
    </div>

    <div class="form-field">
        <x-input-label for="operating_area_key" value="Area operativa" />
        <select id="operating_area_key" name="operating_area_key" class="form-select" required>
            <option value="">Selecciona un area</option>
            @foreach ($areaOptions as $areaKey => $areaName)
                <option value="{{ $areaKey }}" @selected(old('operating_area_key', $requisition?->operating_area_key) === $areaKey)>{{ $areaName }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('operating_area_key')" />
    </div>

    <div class="form-field">
        <x-input-label for="request_reason_id" value="Motivo" />
        <select id="request_reason_id" name="request_reason_id" class="form-select" required>
            <option value="">Selecciona un motivo</option>
            @foreach ($catalogs['reasons'] as $item)
                <option value="{{ $item->id }}" @selected((string) old('request_reason_id', $requisition?->request_reason_id) === (string) $item->id)>{{ $item->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('request_reason_id')" />
    </div>

    <div class="form-field">
        <x-input-label for="client_id" value="Cliente" />
        <select id="client_id" name="client_id" class="form-select" required>
            <option value="">Selecciona un cliente</option>
            @foreach ($catalogs['clients'] as $item)
                <option value="{{ $item->id }}" @selected((string) old('client_id', $requisition?->client_id) === (string) $item->id)>{{ $item->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('client_id')" />
    </div>

    <div class="form-field">
        <x-input-label for="city_id" value="Ciudad" />
        <select id="city_id" name="city_id" class="form-select" required>
            <option value="">Selecciona una ciudad</option>
            @foreach ($catalogs['cities'] as $item)
                <option value="{{ $item->id }}" @selected((string) old('city_id', $requisition?->city_id) === (string) $item->id)>{{ $item->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('city_id')" />
    </div>

    <div class="form-field">
        <x-input-label for="client_type_id" value="Tipo de cliente" />
        <select id="client_type_id" name="client_type_id" class="form-select" required>
            <option value="">Selecciona un tipo</option>
            @foreach ($catalogs['clientTypes'] as $item)
                <option value="{{ $item->id }}" @selected((string) old('client_type_id', $requisition?->client_type_id) === (string) $item->id)>{{ $item->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('client_type_id')" />
    </div>

    <div class="form-field">
        <x-input-label for="programming_type_id" value="Tipo de programacion" />
        <select id="programming_type_id" name="programming_type_id" class="form-select" required>
            <option value="">Selecciona una programacion</option>
            @foreach ($catalogs['programmingTypes'] as $item)
                <option value="{{ $item->id }}" @selected((string) old('programming_type_id', $requisition?->programming_type_id) === (string) $item->id)>{{ $item->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('programming_type_id')" />
    </div>

    <div class="form-field form-field--full">
        <x-input-label for="required_profile" value="Perfil requerido" />
        <textarea id="required_profile" name="required_profile" class="form-textarea" rows="4" required>{{ old('required_profile', $requisition?->required_profile) }}</textarea>
        <x-input-error :messages="$errors->get('required_profile')" />
    </div>

    <div class="form-field form-field--full">
        <x-input-label for="uniform_id" value="Dotacion requerida" />
        <select id="uniform_id" name="uniform_id" class="form-select">
            <option value="">Selecciona una dotacion</option>
            @foreach ($catalogs['uniforms'] as $item)
                <option value="{{ $item->id }}" @selected((string) old('uniform_id', $requisition?->uniform_id) === (string) $item->id)>{{ $item->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('uniform_id')" />
    </div>

    {{-- GRUPO 2: CAMPOS PARA GESTION HUMANA O SOLICITANTE SEGUN IMAGEN (CON OBSERVACIONES) --}}
    @if ($showHumanResourcesFields)
        <div class="form-field">
            <x-input-label for="contract_type_id" value="Tipo de contrato" />
            <select id="contract_type_id" name="contract_type_id" class="form-select">
                <option value="">Selecciona un tipo de contrato</option>
                @foreach ($catalogs['contractTypes'] as $item)
                    <option value="{{ $item->id }}" @selected((string) old('contract_type_id', $requisition?->contract_type_id) === (string) $item->id)>{{ $item->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('contract_type_id')" />
        </div>

        <div class="form-field">
            <x-input-label for="contract_duration" value="Duracion del contrato" />
            <input id="contract_duration" name="contract_duration" type="text" class="form-input" value="{{ old('contract_duration', $requisition?->contract_duration) }}">
            <x-input-error :messages="$errors->get('contract_duration')" />
        </div>

        <div class="form-field">
            <x-input-label for="base_salary" value="Valor salario base" />
            <input id="base_salary" name="base_salary" type="number" step="0.01" class="form-input" value="{{ old('base_salary', $requisition?->base_salary) }}">
            <x-input-error :messages="$errors->get('base_salary')" />
        </div>

        <div class="form-field">
            <x-input-label for="transport_allowance" value="Auxilio de transporte" />
            <input id="transport_allowance" name="transport_allowance" type="number" step="0.01" class="form-input" value="{{ old('transport_allowance', $requisition?->transport_allowance) }}">
            <x-input-error :messages="$errors->get('transport_allowance')" />
        </div>

        <div class="form-field">
            <x-input-label for="mobility_allowance" value="Auxilio de movilizacion" />
            <input id="mobility_allowance" name="mobility_allowance" type="number" step="0.01" class="form-input" value="{{ old('mobility_allowance', $requisition?->mobility_allowance) }}">
            <x-input-error :messages="$errors->get('mobility_allowance')" />
        </div>

        <div class="form-field">
            <x-input-label for="statutory_bonus" value="Bonificacion prestacional" />
            <input id="statutory_bonus" name="statutory_bonus" type="number" step="0.01" class="form-input" value="{{ old('statutory_bonus', $requisition?->statutory_bonus) }}">
            <x-input-error :messages="$errors->get('statutory_bonus')" />
        </div>

        <div class="form-field">
            <x-input-label for="non_statutory_bonus" value="Bonificacion no prestacional" />
            <input id="non_statutory_bonus" name="non_statutory_bonus" type="number" step="0.01" class="form-input" value="{{ old('non_statutory_bonus', $requisition?->non_statutory_bonus) }}">
            <x-input-error :messages="$errors->get('non_statutory_bonus')" />
        </div>

        <div class="form-field">
            <x-input-label for="other_allowances" value="Otros valores" />
            <input id="other_allowances" name="other_allowances" type="number" step="0.01" class="form-input" value="{{ old('other_allowances', $requisition?->other_allowances) }}">
            <x-input-error :messages="$errors->get('other_allowances')" />
        </div>

        <div class="form-field">
            <x-input-label for="leasing_contract" value="Contrato de arrendamiento" />
            <input id="leasing_contract" name="leasing_contract" type="text" class="form-input" value="{{ old('leasing_contract', $requisition?->leasing_contract) }}">
            <x-input-error :messages="$errors->get('leasing_contract')" />
        </div>

        <div class="form-field">
            <x-input-label for="cost_center" value="Centro de costo" />
            <input id="cost_center" name="cost_center" type="text" class="form-input" value="{{ old('cost_center', $requisition?->cost_center) }}">
            <x-input-error :messages="$errors->get('cost_center')" />
        </div>

        <div class="form-field form-field--full">
            <x-input-label for="requester_observation" value="Observaciones del solicitante" />
            <textarea id="requester_observation" name="requester_observation" class="form-textarea" rows="3">{{ old('requester_observation', $requisition?->requester_observation) }}</textarea>
            <x-input-error :messages="$errors->get('requester_observation')" />
        </div>

        <div class="form-field">
            <x-input-label for="status" value="Estado" />
            <select id="status" name="status" class="form-select" required>
                @foreach ($statusLabels as $statusKey => $statusLabel)
                    <option value="{{ $statusKey }}" @selected(old('status', $requisition?->status) === $statusKey)>{{ $statusLabel }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('status')" />
        </div>

        <div class="form-field">
            <x-input-label for="recruiter_name" value="Encargado de seleccion" />
            <input id="recruiter_name" name="recruiter_name" type="text" class="form-input" value="{{ old('recruiter_name', $requisition?->recruiter_name) }}">
            <x-input-error :messages="$errors->get('recruiter_name')" />
        </div>



        <div class="form-field">
            <x-input-label for="hiring_date" value="Fecha de contratacion" />
            <input id="hiring_date" name="hiring_date" type="date" class="form-input" value="{{ old('hiring_date', $requisition?->hiring_date?->format('Y-m-d')) }}">
            <x-input-error :messages="$errors->get('hiring_date')" />
        </div>

        <div class="form-field form-field--full">
            <x-input-label for="human_resources_observation" value="Observaciones de gestion humana" />
            <textarea id="human_resources_observation" name="human_resources_observation" class="form-textarea" rows="3">{{ old('human_resources_observation', $requisition?->human_resources_observation) }}</textarea>
            <x-input-error :messages="$errors->get('human_resources_observation')" />
        </div>
    @else
        {{-- Para el solicitante en modo CREACION, permitimos ver estos campos básicos --}}
        <div class="form-field">
            <x-input-label for="cost_center" value="Centro de costo" />
            <input id="cost_center" name="cost_center" type="text" class="form-input" value="{{ old('cost_center', $requisition?->cost_center) }}">
            <x-input-error :messages="$errors->get('cost_center')" />
        </div>

        <div class="form-field form-field--full">
            <x-input-label for="requester_observation" value="Observaciones del solicitante" />
            <textarea id="requester_observation" name="requester_observation" class="form-textarea" rows="4">{{ old('requester_observation', $requisition?->requester_observation) }}</textarea>
            <x-input-error :messages="$errors->get('requester_observation')" />
        </div>
    @endif
</div>
