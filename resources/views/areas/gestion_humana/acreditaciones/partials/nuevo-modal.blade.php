{{-- Variables: $cargoApoOptions, $lookupUrl, $show, $validacionesReturn (opcional), $returnContext (opcional) --}}
@php
    $validacionesReturn = $validacionesReturn ?? null;
    $returnContext = $returnContext ?? null;
    $requiresAcreditacionOld = old('requires_acreditacion', '1');
    $requiresAcreditacionChecked = $requiresAcreditacionOld === true
        || $requiresAcreditacionOld === 1
        || $requiresAcreditacionOld === '1'
        || $requiresAcreditacionOld === 'true';
@endphp
<x-modal name="acreditaciones-nuevo" maxWidth="2xl" :show="$show" focusable>
    <div
        class="modal-card ficha-empleados-masivos-modal cursos-registros-page__create-modal"
        x-data="{
            lookupUrl: @js($lookupUrl),
            identityLocked: {{ $show && old('document_number') ? 'true' : 'false' }},
            documentNumber: @js(old('document_number', '')),
            fullName: @js(old('full_name', '')),
            cargo: @js(old('cargo', '')),
            requiresAcreditacion: {{ $requiresAcreditacionChecked ? 'true' : 'false' }},
            async lookupName(cedula) {
                const value = String(cedula || '').trim();
                if (!value || this.identityLocked) return;
                try {
                    const res = await fetch(this.lookupUrl + '?cedula=' + encodeURIComponent(value), {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    if (data.found && data.full_name) {
                        this.documentNumber = data.document_number || value;
                        this.fullName = data.full_name;
                        this.cargo = data.cargo || '';
                        this.identityLocked = true;
                    }
                } catch (e) {}
            },
            unlockIdentity() {
                this.identityLocked = false;
                this.documentNumber = '';
                this.fullName = '';
                this.cargo = '';
            },
            prefillFromValidaciones(detail) {
                const doc = String(detail?.document_number || '').trim();
                const name = String(detail?.full_name || '').trim();
                const cargo = String(detail?.cargo || '').trim();
                this.documentNumber = doc;
                this.fullName = name;
                this.cargo = cargo;
                this.identityLocked = Boolean(doc && name);
                if (doc && (! name || ! cargo)) {
                    this.lookupName(doc);
                }
            },
            prefillFromPending(detail) {
                this.prefillFromValidaciones(detail);
            },
        }"
        @acreditaciones-open-nuevo.window="prefillFromValidaciones($event.detail)"
        @acreditaciones-nuevo-prefill.window="prefillFromPending($event.detail)"
    >
        <div class="ficha-empleados-masivos-modal__header">
            <div class="ficha-empleados-masivos-modal__heading">
                <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                    <x-lucide-plus width="18" height="18" aria-hidden="true" />
                </span>
                <div>
                    <h3 class="ficha-empleados-masivos-modal__title">Nuevo acreditado</h3>
                    <p class="ficha-empleados-masivos-modal__lead">
                        La cédula debe existir en Ficha empleados. Si desactiva «Requiere acreditación», puede guardar solo esa marca en ficha.
                    </p>
                </div>
            </div>
            <button
                type="button"
                class="ficha-empleados-masivos-modal__close"
                aria-label="Cerrar"
                x-on:click="$dispatch('close-modal', 'acreditaciones-nuevo')"
            >
                <x-lucide-x width="18" height="18" aria-hidden="true" />
            </button>
        </div>

        @if ($errors->any())
            <div class="alert alert--danger ficha-empleados-masivos-modal__alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('gestion-humana.acreditaciones.acreditados.store') }}"
            class="cursos-registros-page__form"
        >
            @csrf
            @if (! empty($validacionesReturn))
                <input type="hidden" name="_return_context" value="validaciones">
                <input type="hidden" name="_return_fecha_reporte" value="{{ $validacionesReturn['fecha_reporte'] ?? '' }}">
                <input type="hidden" name="_return_run_token" value="{{ $validacionesReturn['run_token'] ?? '' }}">
            @elseif (! empty($returnContext))
                <input type="hidden" name="_return_context" value="{{ $returnContext }}">
            @endif
            <div class="cursos-registros-page__form-grid">
                <div class="form-field">
                    <label class="form-label" for="create_document_number">CEDULA</label>
                    <div class="cursos-registros-page__identity-row">
                        <input
                            id="create_document_number"
                            name="document_number"
                            type="text"
                            class="form-input"
                            maxlength="50"
                            required
                            x-model="documentNumber"
                            x-bind:readonly="identityLocked"
                            @blur="lookupName($event.target.value)"
                        >
                        <button
                            type="button"
                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                            x-show="identityLocked"
                            x-cloak
                            x-on:click="unlockIdentity()"
                            title="Cambiar persona"
                            aria-label="Cambiar persona"
                        >
                            <x-lucide-user-round-pen width="18" height="18" aria-hidden="true" />
                        </button>
                    </div>
                </div>
                <div class="form-field">
                    <label class="form-label" for="create_full_name">NOMBRE COMPLETO</label>
                    <input
                        id="create_full_name"
                        type="text"
                        class="form-input"
                        maxlength="255"
                        readonly
                        x-model="fullName"
                        placeholder="Se completa desde Ficha"
                    >
                </div>
                <div class="form-field cursos-registros-page__form-span">
                    <label class="cursos-registros-page__checkbox-label">
                        <input type="hidden" name="requires_acreditacion" value="0">
                        <input
                            id="create_requires_acreditacion"
                            name="requires_acreditacion"
                            type="checkbox"
                            value="1"
                            x-model="requiresAcreditacion"
                        >
                        Esta persona requiere acreditación
                    </label>
                    <p class="panel-text" style="margin-top:0.35rem;font-size:0.85rem;" x-show="!requiresAcreditacion" x-cloak>
                        Al guardar se actualizará la ficha (no requiere acreditación) y no se creará un acreditado.
                    </p>
                </div>

                <fieldset
                    class="cursos-registros-page__form-span"
                    style="border:0;margin:0;padding:0;min-inline-size:0;"
                    :disabled="!requiresAcreditacion"
                >
                    <div class="cursos-registros-page__form-grid" :class="{ 'opacity-50': !requiresAcreditacion }">
                        <div class="form-field">
                            <label class="form-label" for="create_cargo">CARGO</label>
                            <input
                                id="create_cargo"
                                name="cargo"
                                type="text"
                                class="form-input"
                                maxlength="255"
                                readonly
                                x-model="cargo"
                                placeholder="Se completa desde Ficha"
                            >
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="create_cargo_apo">CARGO APO</label>
                            <x-searchable-select
                                id="create_cargo_apo"
                                name="cargo_apo"
                                :options="$cargoApoOptions"
                                :value="old('cargo_apo')"
                                placeholder="Seleccionar CARGO APO"
                                :required="true"
                                :allow-clear="false"
                            />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="create_vigencia_acr">VIGEN.ACR</label>
                            <input
                                id="create_vigencia_acr"
                                name="vigencia_acr"
                                type="date"
                                class="form-input"
                                value="{{ old('vigencia_acr') }}"
                            >
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="create_estado">ESTADO</label>
                            <input
                                id="create_estado"
                                type="text"
                                class="form-input"
                                readonly
                                value="Automático"
                                title="Se calcula al guardar"
                            >
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="create_renovacion">RENOVACIONES</label>
                            <x-searchable-select
                                id="create_renovacion"
                                name="renovacion"
                                :options="$renovacionOptions"
                                :value="old('renovacion', '')"
                                placeholder="Sin renovación"
                                :allow-clear="true"
                            />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="create_fecha_solicitud">FECHA SOLICITUD</label>
                            <input
                                id="create_fecha_solicitud"
                                name="fecha_solicitud"
                                type="date"
                                class="form-input"
                                value="{{ old('fecha_solicitud') }}"
                            >
                        </div>
                        <div class="form-field cursos-registros-page__form-span">
                            <label class="form-label" for="create_observaciones">OBSERVACIONES</label>
                            <textarea
                                id="create_observaciones"
                                name="observaciones"
                                class="form-input"
                                rows="2"
                            >{{ old('observaciones') }}</textarea>
                        </div>
                    </div>
                </fieldset>
            </div>
            <p class="panel-text" style="font-size:0.85rem;" x-show="requiresAcreditacion">
                Debe indicar al menos una fecha (VIGEN.ACR o FECHA SOLICITUD). El ESTADO no es editable.
            </p>
            <div class="cursos-registros-page__form-actions">
                <button
                    type="submit"
                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                    title="Guardar"
                    aria-label="Guardar"
                >
                    <x-lucide-save width="18" height="18" aria-hidden="true" />
                </button>
            </div>
        </form>
    </div>
</x-modal>
