@php
    $leaderName = old('leader_name', $requisition?->leader_name ?? auth()->user()->name);
    $reasonsByName = $catalogs['reasons']->keyBy(fn ($reason) => strtolower($reason->name));
    $reasonReemplazo = $reasonsByName->get('reemplazo')?->id;
    $selectedReasonId = (string) old('request_reason_id', $requisition?->request_reason_id);
    $showReplacementInitially = $selectedReasonId !== '' && (int) $selectedReasonId === (int) $reasonReemplazo;
    $clientTypesByName = $catalogs['clientTypes']->keyBy(fn ($type) => strtolower($type->name));
    $internalClientTypeId = $clientTypesByName->get('interno')?->id;
    $selectedClientTypeId = (string) old('client_type_id', $requisition?->client_type_id);
    $isInternalClientInitially = $selectedClientTypeId !== '' && (int) $selectedClientTypeId === (int) $internalClientTypeId;
    $currentStatus = old('status', $requisition?->status);
@endphp

<div class="req-form">

    <div class="req-form__meta req-form__meta--edit">
        @if ($requisition)
            <div class="req-form__meta-item">
                <span class="req-form__meta-label">Codigo</span>
                <span class="req-form__meta-value">{{ $requisition->code }}</span>
            </div>
            <div class="req-form__meta-item">
                <span class="req-form__meta-label">Fecha de solicitud</span>
                <span class="req-form__meta-value">{{ $requisition->request_date?->format('Y-m-d') }}</span>
            </div>
        @endif
        <div class="req-form__meta-item">
            <span class="req-form__meta-label">Lider / solicitante</span>
            <span class="req-form__meta-value">{{ $leaderName }}</span>
        </div>
        <div class="req-form__meta-item">
            <span class="req-form__meta-label">Area solicitante</span>
            <span class="req-form__meta-value">{{ $moduleLabel }}</span>
        </div>
        @if ($requisition && $showHumanResourcesFields)
            <div class="req-form__meta-item">
                <span class="req-form__meta-label">Estado actual</span>
                <span class="status-pill status-pill--req-{{ $requisition->status }}">{{ $statusLabels[$requisition->status] ?? $requisition->status }}</span>
            </div>
        @endif
    </div>

    <section class="req-form__section">
        <header class="req-form__section-head">
            <span class="req-form__section-step">1</span>
            <div>
                <h4 class="req-form__section-title">Motivo de la solicitud</h4>
                <p class="req-form__section-desc">Motivo registrado por el solicitante y datos de reemplazo si aplica.</p>
            </div>
        </header>

        <div class="form-grid form-grid--two">
            <div class="form-field">
                <x-input-label for="request_reason_id" value="Motivo *" />
                <select
                    id="request_reason_id"
                    name="request_reason_id"
                    class="form-select"
                    required
                    data-replacement-id="{{ $reasonReemplazo }}"
                >
                    <option value="">Selecciona un motivo</option>
                    @foreach ($catalogs['reasons'] as $item)
                        <option value="{{ $item->id }}" @selected($selectedReasonId === (string) $item->id)>{{ $item->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('request_reason_id')" />
            </div>
        </div>

        <div id="js-replacement-group" class="req-form__replacement" @unless($showReplacementInitially) hidden @endunless>
            <div class="form-field">
                <x-input-label for="replacement_document" value="Cedula a quien reemplaza" />
                <input id="replacement_document" name="replacement_document" type="text" class="form-input" value="{{ old('replacement_document', $requisition?->replacement_document) }}" placeholder="Documento de identidad">
                <x-input-error :messages="$errors->get('replacement_document')" />
            </div>

            <div class="form-field">
                <x-input-label for="replacement_name" value="Nombre completo a quien reemplaza" />
                <input id="replacement_name" name="replacement_name" type="text" class="form-input" value="{{ old('replacement_name', $requisition?->replacement_name) }}" placeholder="Nombre del colaborador a reemplazar">
                <x-input-error :messages="$errors->get('replacement_name')" />
            </div>
        </div>
    </section>

    <section class="req-form__section">
        <header class="req-form__section-head">
            <span class="req-form__section-step">2</span>
            <div>
                <h4 class="req-form__section-title">Cargo solicitado</h4>
                <p class="req-form__section-desc">Puesto, sexo requerido y cantidad de plazas.</p>
            </div>
        </header>

        <div class="form-grid form-grid--two">
            <div class="form-field">
                <x-input-label for="position_id" value="Cargo solicitado *" />
                <select id="position_id" name="position_id" class="form-select" required>
                    <option value="">Selecciona un cargo</option>
                    @foreach ($catalogs['positions'] as $item)
                        <option value="{{ $item->id }}" @selected((string) old('position_id', $requisition?->position_id) === (string) $item->id)>{{ $item->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('position_id')" />
            </div>

            <div class="form-field">
                <x-input-label for="sex" value="Sexo *" />
                <select id="sex" name="sex" class="form-select" required>
                    <option value="">Selecciona una opcion</option>
                    @foreach ($sexOptions as $sexKey => $sexLabel)
                        <option value="{{ $sexKey }}" @selected(old('sex', $requisition?->sex) === $sexKey)>{{ $sexLabel }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('sex')" />
            </div>

            <div class="form-field">
                <x-input-label for="quantity" value="Cantidad *" />
                <input id="quantity" name="quantity" type="number" min="1" max="999" class="form-input" value="{{ old('quantity', $requisition?->quantity ?? 1) }}" required>
                <x-input-error :messages="$errors->get('quantity')" />
            </div>
        </div>
    </section>

    <section class="req-form__section">
        <header class="req-form__section-head">
            <span class="req-form__section-step">3</span>
            <div>
                <h4 class="req-form__section-title">Servicio y ubicacion</h4>
                <p class="req-form__section-desc">Area operativa, cliente, ciudad y programacion del servicio.</p>
            </div>
        </header>

        <div class="form-grid form-grid--two">
            <div class="form-field">
                <x-input-label for="operating_area_key" value="Area operativa *" />
                <select id="operating_area_key" name="operating_area_key" class="form-select" required>
                    <option value="">Selecciona un area</option>
                    @foreach ($areaOptions as $areaKey => $areaName)
                        <option value="{{ $areaKey }}" @selected(old('operating_area_key', $requisition?->operating_area_key) === $areaKey)>{{ $areaName }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('operating_area_key')" />
            </div>

            <div class="form-field">
                <x-input-label for="client_type_id" value="Tipo de cliente *" />
                <select
                    id="client_type_id"
                    name="client_type_id"
                    class="form-select"
                    required
                    data-internal-id="{{ $internalClientTypeId }}"
                >
                    <option value="">Selecciona un tipo</option>
                    @foreach ($catalogs['clientTypes'] as $item)
                        <option value="{{ $item->id }}" @selected($selectedClientTypeId === (string) $item->id)>{{ $item->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('client_type_id')" />
            </div>

            <div id="js-internal-client-notice" class="req-form__internal-notice req-form__field-span" @unless($isInternalClientInitially) hidden @endunless>
                <p class="req-form__hint" style="margin:0;">Personal administrativo interno: no requiere cliente de la matriz comercial.</p>
            </div>

            <div id="js-client-group" class="form-field req-form__field-span" @if($isInternalClientInitially) hidden @endif>
                @include('modules.requisitions.partials.commercial-client-picker', [
                    'clientSearchUrl' => $clientSearchUrl,
                    'selectedCommercialClient' => $selectedCommercialClient ?? null,
                    'clientRequired' => ! $isInternalClientInitially,
                ])
            </div>

            <div class="form-field">
                <x-input-label for="city_id" value="Ciudad *" />
                <select id="city_id" name="city_id" class="form-select" required>
                    <option value="">Selecciona una ciudad</option>
                    @foreach ($catalogs['cities'] as $item)
                        <option value="{{ $item->id }}" @selected((string) old('city_id', $requisition?->city_id) === (string) $item->id)>{{ $item->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('city_id')" />
            </div>

            <div class="form-field">
                <x-input-label for="programming_type_id" value="Tipo de programacion *" />
                <select id="programming_type_id" name="programming_type_id" class="form-select" required>
                    <option value="">Selecciona una programacion</option>
                    @foreach ($catalogs['programmingTypes'] as $item)
                        <option value="{{ $item->id }}" @selected((string) old('programming_type_id', $requisition?->programming_type_id) === (string) $item->id)>{{ $item->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('programming_type_id')" />
            </div>
        </div>
    </section>

    <section class="req-form__section">
        <header class="req-form__section-head">
            <span class="req-form__section-step">4</span>
            <div>
                <h4 class="req-form__section-title">Perfil y dotacion</h4>
                <p class="req-form__section-desc">Funciones del puesto y uniforme requerido.</p>
            </div>
        </header>

        <div class="form-grid form-grid--two">
            <div class="form-field form-field--full">
                <x-input-label for="required_profile" value="Perfil requerido *" />
                <textarea id="required_profile" name="required_profile" class="form-textarea" rows="4" required>{{ old('required_profile', $requisition?->required_profile) }}</textarea>
                <x-input-error :messages="$errors->get('required_profile')" />
            </div>

            <div class="form-field form-field--full">
                <x-input-label for="uniform_id" value="Dotacion requerida *" />
                <select id="uniform_id" name="uniform_id" class="form-select" required>
                    <option value="">Selecciona una dotacion</option>
                    @foreach ($catalogs['uniforms'] as $item)
                        <option value="{{ $item->id }}" @selected((string) old('uniform_id', $requisition?->uniform_id) === (string) $item->id)>{{ $item->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('uniform_id')" />
            </div>
        </div>
    </section>

    @if ($showHumanResourcesFields)
        <section class="req-form__section">
            <header class="req-form__section-head">
                <span class="req-form__section-step">5</span>
                <div>
                    <h4 class="req-form__section-title">Compensacion y contrato</h4>
                    <p class="req-form__section-desc">Valores economicos y condiciones contractuales para la contratacion.</p>
                </div>
            </header>

            <div class="form-grid form-grid--two">
                <div class="form-field">
                    <x-input-label for="contract_type_id" value="Tipo de contrato *" />
                    <select id="contract_type_id" name="contract_type_id" class="form-select">
                        <option value="">Selecciona un tipo de contrato</option>
                        @foreach ($catalogs['contractTypes'] as $item)
                            <option value="{{ $item->id }}" @selected((string) old('contract_type_id', $requisition?->contract_type_id) === (string) $item->id)>{{ $item->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('contract_type_id')" />
                </div>

                <div class="form-field">
                    <x-input-label for="contract_duration" value="Duracion del contrato *" />
                    <input id="contract_duration" name="contract_duration" type="text" class="form-input" value="{{ old('contract_duration', $requisition?->contract_duration) }}">
                    <x-input-error :messages="$errors->get('contract_duration')" />
                </div>

                <div class="form-field">
                    <x-input-label for="base_salary" value="Valor salario base *" />
                    <input id="base_salary" name="base_salary" type="text" class="form-input js-currency" data-raw-name="base_salary" value="{{ old('base_salary', $requisition?->base_salary ?? 0) }}">
                    <x-input-error :messages="$errors->get('base_salary')" />
                </div>

                <div class="form-field">
                    <x-input-label for="transport_allowance" value="Auxilio de transporte *" />
                    <input id="transport_allowance" name="transport_allowance" type="text" class="form-input js-currency" data-raw-name="transport_allowance" value="{{ old('transport_allowance', $requisition?->transport_allowance ?? 0) }}">
                    <x-input-error :messages="$errors->get('transport_allowance')" />
                </div>

                <div class="form-field">
                    <x-input-label for="mobility_allowance" value="Auxilio de movilizacion" />
                    <input id="mobility_allowance" name="mobility_allowance" type="text" class="form-input js-currency" data-raw-name="mobility_allowance" value="{{ old('mobility_allowance', $requisition?->mobility_allowance ?? 0) }}">
                    <x-input-error :messages="$errors->get('mobility_allowance')" />
                </div>

                <div class="form-field">
                    <x-input-label for="statutory_bonus" value="Bonificacion prestacional *" />
                    <input id="statutory_bonus" name="statutory_bonus" type="text" class="form-input js-currency" data-raw-name="statutory_bonus" value="{{ old('statutory_bonus', $requisition?->statutory_bonus ?? 0) }}">
                    <x-input-error :messages="$errors->get('statutory_bonus')" />
                </div>

                <div class="form-field">
                    <x-input-label for="non_statutory_bonus" value="Bonificacion no prestacional" />
                    <input id="non_statutory_bonus" name="non_statutory_bonus" type="text" class="form-input js-currency" data-raw-name="non_statutory_bonus" value="{{ old('non_statutory_bonus', $requisition?->non_statutory_bonus ?? 0) }}">
                    <x-input-error :messages="$errors->get('non_statutory_bonus')" />
                </div>

                <div class="form-field">
                    <x-input-label for="other_allowances" value="Otros valores" />
                    <input id="other_allowances" name="other_allowances" type="text" class="form-input" value="{{ old('other_allowances', $requisition?->other_allowances) }}" maxlength="500">
                    <x-input-error :messages="$errors->get('other_allowances')" />
                </div>

                <div class="form-field">
                    <x-input-label for="leasing_contract" value="Contrato de arrendamiento" />
                    <input id="leasing_contract" name="leasing_contract" type="text" class="form-input js-currency" data-raw-name="leasing_contract" value="{{ old('leasing_contract', $requisition?->leasing_contract ?? 0) }}">
                    <x-input-error :messages="$errors->get('leasing_contract')" />
                </div>
            </div>
        </section>

        <section class="req-form__section">
            <header class="req-form__section-head">
                <span class="req-form__section-step">6</span>
                <div>
                    <h4 class="req-form__section-title">Gestion y seguimiento</h4>
                    <p class="req-form__section-desc">Estado del proceso, reclutador, fechas y observaciones operativas.</p>
                </div>
            </header>

            <div class="form-grid form-grid--two">
                <div class="form-field">
                    <x-input-label for="cost_center" value="Centro de costo *" />
                    <input id="cost_center" name="cost_center" type="text" class="form-input" value="{{ old('cost_center', $requisition?->cost_center) }}">
                    <x-input-error :messages="$errors->get('cost_center')" />
                </div>

                <div class="form-field">
                    <x-input-label for="status" value="Estado *" />
                    <select id="status" name="status" class="form-select" required>
                        @foreach ($statusLabels as $statusKey => $statusLabel)
                            <option value="{{ $statusKey }}" @selected($currentStatus === $statusKey)>{{ $statusLabel }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('status')" />
                </div>

                <div class="form-field">
                    <x-input-label for="recruiter_id" value="Reclutador" />
                    <select id="recruiter_id" name="recruiter_id" class="form-select">
                        <option value="">Selecciona un reclutador</option>
                        @foreach ($catalogs['recruiters'] as $item)
                            <option value="{{ $item->id }}" @selected((string) old('recruiter_id', $requisition?->recruiter_id) === (string) $item->id)>{{ $item->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('recruiter_id')" />
                </div>

                <div class="form-field">
                    <x-input-label for="hiring_date" value="Fecha de contratacion" />
                    <input id="hiring_date" name="hiring_date" type="date" class="form-input" value="{{ old('hiring_date', $requisition?->hiring_date?->format('Y-m-d')) }}">
                    <x-input-error :messages="$errors->get('hiring_date')" />
                </div>

                <div class="form-field form-field--full">
                    <x-input-label for="requester_observation" value="Observaciones del solicitante" />
                    <textarea id="requester_observation" name="requester_observation" class="form-textarea" rows="3">{{ old('requester_observation', $requisition?->requester_observation) }}</textarea>
                    <x-input-error :messages="$errors->get('requester_observation')" />
                </div>

                <div class="form-field form-field--full">
                    <x-input-label for="human_resources_observation" value="Observaciones de gestion humana" />
                    <textarea id="human_resources_observation" name="human_resources_observation" class="form-textarea" rows="3" placeholder="Notas internas visibles en el seguimiento operativo.">{{ old('human_resources_observation', $requisition?->human_resources_observation) }}</textarea>
                    <x-input-error :messages="$errors->get('human_resources_observation')" />
                </div>
            </div>
        </section>
    @else
        <section class="req-form__section">
            <header class="req-form__section-head">
                <span class="req-form__section-step">5</span>
                <div>
                    <h4 class="req-form__section-title">Datos administrativos</h4>
                    <p class="req-form__section-desc">Centro de costo y observaciones para gestion humana.</p>
                </div>
            </header>

            <div class="form-grid form-grid--two">
                <div class="form-field">
                    <x-input-label for="cost_center" value="Centro de costo *" />
                    <input id="cost_center" name="cost_center" type="text" class="form-input" value="{{ old('cost_center', $requisition?->cost_center) }}" required>
                    <x-input-error :messages="$errors->get('cost_center')" />
                </div>

                <div class="form-field form-field--full">
                    <x-input-label for="requester_observation" value="Observaciones del solicitante" />
                    <textarea id="requester_observation" name="requester_observation" class="form-textarea" rows="4">{{ old('requester_observation', $requisition?->requester_observation) }}</textarea>
                    <x-input-error :messages="$errors->get('requester_observation')" />
                </div>
            </div>
        </section>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const reasonSelect = document.getElementById('request_reason_id');
        const replacementGroup = document.getElementById('js-replacement-group');
        const clientTypeSelect = document.getElementById('client_type_id');
        const clientGroup = document.getElementById('js-client-group');
        const internalNotice = document.getElementById('js-internal-client-notice');
        const replacementFields = [
            document.getElementById('replacement_document'),
            document.getElementById('replacement_name'),
        ];

        function toggleReplacementFields() {
            if (!reasonSelect || !replacementGroup) {
                return;
            }

            const replacementReasonId = reasonSelect.dataset.replacementId || '';
            const isReplacement = reasonSelect.value !== '' && reasonSelect.value === replacementReasonId;

            replacementGroup.hidden = !isReplacement;

            replacementFields.forEach(function (field) {
                if (field) {
                    field.required = isReplacement;
                }
            });
        }

        function resetClientPicker() {
            const picker = document.querySelector('#js-client-group .js-client-picker');
            if (!picker) {
                return;
            }

            const hiddenId = picker.querySelector('.js-client-picker-id');
            const selectedWrap = picker.querySelector('.js-client-picker-selected');
            const searchWrap = picker.querySelector('.js-client-picker-search');
            const input = picker.querySelector('.js-client-picker-search-input');
            const results = picker.querySelector('.js-client-picker-results');
            const hint = picker.querySelector('.js-client-picker-hint');

            if (hiddenId) {
                hiddenId.value = '';
            }
            if (selectedWrap) {
                selectedWrap.style.display = 'none';
            }
            if (searchWrap) {
                searchWrap.style.display = '';
            }
            if (input) {
                input.value = '';
            }
            if (results) {
                results.hidden = true;
                results.innerHTML = '';
            }
            if (hint) {
                hint.hidden = true;
                hint.textContent = '';
            }
        }

        function toggleClientTypeFields() {
            if (!clientTypeSelect) {
                return;
            }

            const internalTypeId = clientTypeSelect.dataset.internalId || '';
            const isInternal = clientTypeSelect.value !== '' && clientTypeSelect.value === internalTypeId;
            const hiddenId = document.querySelector('#js-client-group .js-client-picker-id');

            if (clientGroup) {
                clientGroup.hidden = isInternal;
            }

            if (internalNotice) {
                internalNotice.hidden = !isInternal;
            }

            if (hiddenId) {
                if (isInternal) {
                    hiddenId.removeAttribute('required');
                } else {
                    hiddenId.setAttribute('required', 'required');
                }
            }
        }

        if (reasonSelect) {
            reasonSelect.addEventListener('change', toggleReplacementFields);
            toggleReplacementFields();
        }

        if (clientTypeSelect) {
            clientTypeSelect.addEventListener('change', function () {
                const internalTypeId = clientTypeSelect.dataset.internalId || '';
                const isInternal = clientTypeSelect.value !== '' && clientTypeSelect.value === internalTypeId;

                if (isInternal) {
                    resetClientPicker();
                }

                toggleClientTypeFields();
            });
            toggleClientTypeFields();
        }

        const currencyInputs = document.querySelectorAll('.js-currency');
        const formatter = new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            minimumFractionDigits: 0,
        });

        currencyInputs.forEach(function (input) {
            const rawName = input.getAttribute('data-raw-name');
            if (!rawName) {
                return;
            }

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = rawName;
            hiddenInput.value = input.value;
            input.parentNode.appendChild(hiddenInput);
            input.removeAttribute('name');

            function formatDisplayValue(val) {
                const numericValue = parseFloat(val.toString().replace(/[^0-9.-]+/g, '')) || 0;
                hiddenInput.value = numericValue;
                input.value = formatter.format(numericValue);
            }

            input.addEventListener('focus', function () {
                input.value = hiddenInput.value;
            });

            input.addEventListener('blur', function () {
                formatDisplayValue(input.value);
            });

            if (input.value) {
                formatDisplayValue(input.value);
            }
        });
    });
</script>
