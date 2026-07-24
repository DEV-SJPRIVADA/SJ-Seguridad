@php
    $leaderName = old('leader_name', auth()->user()->name);
    $reasonsByName = $catalogs['reasons']->keyBy(fn ($reason) => strtolower($reason->name));
    $reasonCargoNuevo = $reasonsByName->get('cargo nuevo')?->id;
    $reasonReemplazo = $reasonsByName->get('reemplazo')?->id;
    $reasonServicioNuevo = $reasonsByName->get('servicio nuevo')?->id;
    $quantityReasonIds = array_values(array_filter([$reasonCargoNuevo, $reasonServicioNuevo]));
    $selectedReasonId = (string) old('request_reason_id', '');
    $showQuantityInitially = in_array((int) $selectedReasonId, $quantityReasonIds, true);
    $showReplacementInitially = $selectedReasonId !== '' && (int) $selectedReasonId === (int) $reasonReemplazo;
    $quantityValue = old('quantity', 1);
    $clientTypesByName = $catalogs['clientTypes']->keyBy(fn ($type) => strtolower($type->name));
    $internalClientTypeId = $clientTypesByName->get('interno')?->id;
    $selectedClientTypeId = (string) old('client_type_id', '');
    $isInternalClientInitially = $selectedClientTypeId !== '' && (int) $selectedClientTypeId === (int) $internalClientTypeId;
@endphp

<div class="req-form">

    <div class="req-form__meta">
        <div class="req-form__meta-item">
            <span class="req-form__meta-label">Lider / solicitante</span>
            <span class="req-form__meta-value">{{ $leaderName }}</span>
        </div>
        <div class="req-form__meta-item">
            <span class="req-form__meta-label">Area solicitante</span>
            <span class="req-form__meta-value">{{ $moduleLabel }}</span>
        </div>
    </div>

    <section class="req-form__section">
        <header class="req-form__section-head">
            <span class="req-form__section-step">1</span>
            <div>
                <h4 class="req-form__section-title">Motivo de la solicitud</h4>
                <p class="req-form__section-desc">Indica si es cargo nuevo, reemplazo o servicio nuevo.</p>
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
                    data-quantity-ids="{{ implode(',', $quantityReasonIds) }}"
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
                <input id="replacement_document" name="replacement_document" type="text" class="form-input" value="{{ old('replacement_document') }}" placeholder="Documento de identidad">
                <x-input-error :messages="$errors->get('replacement_document')" />
            </div>

            <div class="form-field">
                <x-input-label for="replacement_name" value="Nombre completo a quien reemplaza" />
                <input id="replacement_name" name="replacement_name" type="text" class="form-input" value="{{ old('replacement_name') }}" placeholder="Nombre del colaborador a reemplazar">
                <x-input-error :messages="$errors->get('replacement_name')" />
            </div>
        </div>
    </section>

    <section class="req-form__section">
        <header class="req-form__section-head">
            <span class="req-form__section-step">2</span>
            <div>
                <h4 class="req-form__section-title">Cargo solicitado</h4>
                <p class="req-form__section-desc">Define el puesto y el sexo requerido. La cantidad solo aplica para cargo nuevo o servicio nuevo.</p>
            </div>
        </header>

        <div class="form-grid form-grid--two">
            <div class="form-field">
                <x-input-label for="position_id" value="Cargo solicitado *" />
                <select id="position_id" name="position_id" class="form-select" required>
                    <option value="">Selecciona un cargo</option>
                    @foreach ($catalogs['positions'] as $item)
                        <option value="{{ $item->id }}" @selected((string) old('position_id') === (string) $item->id)>{{ $item->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('position_id')" />
            </div>

            <div class="form-field">
                <x-input-label for="sex" value="Genero *" />
                <select id="sex" name="sex" class="form-select" required>
                    <option value="">Selecciona una opcion</option>
                    @foreach ($sexOptions as $sexKey => $sexLabel)
                        <option value="{{ $sexKey }}" @selected(old('sex') === $sexKey)>{{ $sexLabel }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('sex')" />
            </div>

            <div id="js-quantity-group" class="form-field" @unless($showQuantityInitially) hidden @endunless>
                <x-input-label for="quantity_visible" value="Cantidad *" />
                <input id="quantity_visible" type="number" min="1" max="999" class="form-input" value="{{ $showQuantityInitially ? $quantityValue : 1 }}">
                <p class="req-form__hint">Cada unidad genera una requisicion independiente con codigo propio.</p>
                <x-input-error :messages="$errors->get('quantity')" />
            </div>
        </div>

        <input type="hidden" name="quantity" id="quantity" value="{{ $showQuantityInitially ? $quantityValue : 1 }}">
    </section>

    <section class="req-form__section">
        <header class="req-form__section-head">
            <span class="req-form__section-step">3</span>
            <div>
                <h4 class="req-form__section-title">Servicio y ubicacion</h4>
                <p class="req-form__section-desc">Tipo de cliente, ubicacion y condiciones del servicio.</p>
            </div>
        </header>

        <div class="form-grid form-grid--two">
            <div class="form-field">
                <x-input-label for="operating_area_key" value="Area operativa *" />
                <select id="operating_area_key" name="operating_area_key" class="form-select" required>
                    <option value="">Selecciona un area</option>
                    @foreach ($areaOptions as $areaKey => $areaName)
                        <option value="{{ $areaKey }}" @selected(old('operating_area_key') === $areaKey)>{{ $areaName }}</option>
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
                        <option value="{{ $item->id }}" @selected((string) old('city_id') === (string) $item->id)>{{ $item->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('city_id')" />
            </div>

            <div class="form-field">
                <x-input-label for="programming_type_id" value="Tipo de programacion *" />
                <select id="programming_type_id" name="programming_type_id" class="form-select" required>
                    <option value="">Selecciona una programacion</option>
                    @foreach ($catalogs['programmingTypes'] as $item)
                        <option value="{{ $item->id }}" @selected((string) old('programming_type_id') === (string) $item->id)>{{ $item->name }}</option>
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
                <p class="req-form__section-desc">Describe las funciones del puesto y el uniforme requerido.</p>
            </div>
        </header>

        <div class="form-grid form-grid--two">
            <div class="form-field form-field--full">
                <x-input-label for="required_profile" value="Perfil requerido *" />
                <textarea id="required_profile" name="required_profile" class="form-textarea" rows="4" required placeholder="Ej. Control de ingreso, rondas perimetrales, manejo de bitacora...">{{ old('required_profile') }}</textarea>
                <x-input-error :messages="$errors->get('required_profile')" />
            </div>

            <div class="form-field form-field--full">
                <x-input-label for="uniform_id" value="Dotacion requerida *" />
                <select id="uniform_id" name="uniform_id" class="form-select" required>
                    <option value="">Selecciona una dotacion</option>
                    @foreach ($catalogs['uniforms'] as $item)
                        <option value="{{ $item->id }}" @selected((string) old('uniform_id') === (string) $item->id)>{{ $item->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('uniform_id')" />
            </div>
        </div>
    </section>

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
                <input id="cost_center" name="cost_center" type="text" class="form-input" value="{{ old('cost_center') }}" required placeholder="Ej. CC-001">
                <x-input-error :messages="$errors->get('cost_center')" />
            </div>

            <div class="form-field form-field--full">
                <x-input-label for="requester_observation" value="Observaciones del solicitante" />
                <textarea id="requester_observation" name="requester_observation" class="form-textarea" rows="3" placeholder="Informacion adicional que ayude a gestion humana (opcional).">{{ old('requester_observation') }}</textarea>
                <x-input-error :messages="$errors->get('requester_observation')" />
            </div>
        </div>
    </section>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const reasonSelect = document.getElementById('request_reason_id');
        const replacementGroup = document.getElementById('js-replacement-group');
        const quantityGroup = document.getElementById('js-quantity-group');
        const quantityHidden = document.getElementById('quantity');
        const quantityVisible = document.getElementById('quantity_visible');
        const clientTypeSelect = document.getElementById('client_type_id');
        const clientGroup = document.getElementById('js-client-group');
        const internalNotice = document.getElementById('js-internal-client-notice');
        const replacementFields = [
            document.getElementById('replacement_document'),
            document.getElementById('replacement_name'),
        ];

        if (!reasonSelect) {
            return;
        }

        const replacementReasonId = reasonSelect.dataset.replacementId || '';
        const quantityReasonIds = (reasonSelect.dataset.quantityIds || '')
            .split(',')
            .filter(Boolean);

        function syncQuantityValue() {
            if (!quantityHidden) {
                return;
            }

            const showQuantity = quantityReasonIds.includes(reasonSelect.value);

            if (showQuantity && quantityVisible) {
                const parsed = parseInt(quantityVisible.value || '1', 10);
                quantityHidden.value = String(Number.isFinite(parsed) && parsed > 0 ? parsed : 1);
            } else {
                quantityHidden.value = '1';
            }
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
                    hiddenId.value = '';
                    resetClientPicker();
                } else {
                    hiddenId.setAttribute('required', 'required');
                }
            }
        }

        function toggleReasonDependentFields() {
            const reasonId = reasonSelect.value;
            const isReplacement = reasonId !== '' && reasonId === replacementReasonId;
            const showQuantity = quantityReasonIds.includes(reasonId);

            if (replacementGroup) {
                replacementGroup.hidden = !isReplacement;
            }

            replacementFields.forEach(function (field) {
                if (field) {
                    field.required = isReplacement;
                }
            });

            if (quantityGroup) {
                quantityGroup.hidden = !showQuantity;
            }

            if (quantityVisible) {
                quantityVisible.required = showQuantity;
            }

            syncQuantityValue();
        }

        if (quantityVisible) {
            quantityVisible.addEventListener('input', syncQuantityValue);
            quantityVisible.addEventListener('change', syncQuantityValue);
        }

        if (clientTypeSelect) {
            clientTypeSelect.addEventListener('change', toggleClientTypeFields);
            toggleClientTypeFields();
        }

        reasonSelect.addEventListener('change', toggleReasonDependentFields);
        toggleReasonDependentFields();
    });
</script>
