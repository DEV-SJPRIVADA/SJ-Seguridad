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
<x-modal name="acreditaciones-nuevo" maxWidth="3xl" :show="$show" focusable>
    <div
        class="modal-card ficha-empleados-masivos-modal cursos-registros-page__create-modal acreditaciones-nuevo-modal"
        x-data="{
            lookupUrl: @js($lookupUrl),
            identityLocked: {{ $show && old('document_number') ? 'true' : 'false' }},
            documentNumber: @js(old('document_number', '')),
            fullName: @js(old('full_name', '')),
            cargo: @js(old('cargo', '')),
            requiresAcreditacion: {{ $requiresAcreditacionChecked ? 'true' : 'false' }},
            async lookupName(cedula, { force = false } = {}) {
                const value = String(cedula || '').trim();
                if (!value || (this.identityLocked && !force)) return;
                try {
                    const res = await fetch(this.lookupUrl + '?cedula=' + encodeURIComponent(value), {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    if (data.found) {
                        this.documentNumber = data.document_number || value;
                        if (data.full_name) {
                            this.fullName = data.full_name;
                        }
                        // Completar cargo desde Ficha (también si venía vacío en prefill).
                        if (data.cargo) {
                            this.cargo = data.cargo;
                        }
                        this.identityLocked = Boolean(this.documentNumber && this.fullName);
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
                // Si falta nombre o cargo, forzar lookup aunque la identidad ya esté bloqueada.
                if (doc && (!name || !cargo)) {
                    this.lookupName(doc, { force: true });
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
                        Si la cédula está en Ficha, nombre y cargo se completan solos. Si no, puede capturarlos a mano.
                        Si desactiva «Requiere acreditación», la cédula sí debe existir en Ficha (solo actualiza esa marca).
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
            class="cursos-registros-page__form acreditaciones-nuevo-modal__form"
        >
            @csrf
            @if (! empty($validacionesReturn))
                <input type="hidden" name="_return_context" value="validaciones">
                <input type="hidden" name="_return_fecha_reporte" value="{{ $validacionesReturn['fecha_reporte'] ?? '' }}">
                <input type="hidden" name="_return_run_token" value="{{ $validacionesReturn['run_token'] ?? '' }}">
            @elseif (! empty($returnContext))
                <input type="hidden" name="_return_context" value="{{ $returnContext }}">
            @endif

            {{-- Identidad desde Ficha --}}
            <section class="acreditaciones-nuevo-modal__section" aria-label="Identidad">
                <h4 class="acreditaciones-nuevo-modal__section-title">Identidad</h4>
                <div class="acreditaciones-nuevo-modal__grid acreditaciones-nuevo-modal__grid--2">
                    <div class="form-field">
                        <label class="form-label" for="create_document_number">Cédula</label>
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
                        <label class="form-label" for="create_full_name">Nombre completo</label>
                        <input
                            id="create_full_name"
                            name="full_name"
                            type="text"
                            class="form-input"
                            maxlength="255"
                            required
                            x-model="fullName"
                            x-bind:readonly="identityLocked"
                            placeholder="Desde Ficha o manual"
                        >
                    </div>
                </div>

                {{-- Requisito + Estado en la misma fila --}}
                <div class="acreditaciones-nuevo-modal__req-row">
                    <div class="form-field acreditaciones-nuevo-modal__req-check">
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
                        <p class="acreditaciones-nuevo-modal__hint" x-show="!requiresAcreditacion" x-cloak>
                            Al guardar se actualizará la ficha (no requiere acreditación) y no se creará un acreditado.
                        </p>
                    </div>
                    <div class="form-field">
                        <label class="form-label" for="create_estado">Estado</label>
                        <input
                            id="create_estado"
                            type="text"
                            class="form-input"
                            readonly
                            value="Automático"
                            title="Se calcula al guardar"
                        >
                    </div>
                </div>
            </section>

            {{-- Cargo | Cargo APO | Vigen.ACR (+ observaciones) --}}
            <fieldset
                class="acreditaciones-nuevo-modal__section acreditaciones-nuevo-modal__section--acred"
                style="border:0;min-inline-size:0;"
                :disabled="!requiresAcreditacion"
            >
                <h4 class="acreditaciones-nuevo-modal__section-title">Datos de acreditación</h4>
                <div :class="{ 'opacity-50': !requiresAcreditacion }">
                    <div class="acreditaciones-nuevo-modal__grid acreditaciones-nuevo-modal__grid--3">
                        <div class="form-field">
                            <label class="form-label" for="create_cargo">Cargo</label>
                            <input
                                id="create_cargo"
                                name="cargo"
                                type="text"
                                class="form-input"
                                maxlength="255"
                                x-model="cargo"
                                x-bind:readonly="identityLocked"
                                placeholder="Desde Ficha o manual"
                            >
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="create_cargo_apo">Cargo APO</label>
                            <x-searchable-select
                                id="create_cargo_apo"
                                name="cargo_apo"
                                :options="$cargoApoOptions"
                                :value="old('cargo_apo')"
                                placeholder="Seleccionar cargo APO"
                                :required="true"
                                :allow-clear="false"
                            />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="create_vigencia_acr">Vigen.ACR</label>
                            <input
                                id="create_vigencia_acr"
                                name="vigencia_acr"
                                type="date"
                                class="form-input"
                                value="{{ old('vigencia_acr') }}"
                            >
                        </div>
                    </div>

                    <div class="form-field acreditaciones-nuevo-modal__grid--spaced">
                        <label class="form-label" for="create_observaciones">Observaciones</label>
                        <textarea
                            id="create_observaciones"
                            name="observaciones"
                            class="form-input"
                            rows="2"
                        >{{ old('observaciones') }}</textarea>
                    </div>
                </div>
            </fieldset>

            {{-- Renovación + fecha de solicitud --}}
            <fieldset
                class="acreditaciones-nuevo-modal__section acreditaciones-nuevo-modal__section--acred"
                style="border:0;min-inline-size:0;"
                :disabled="!requiresAcreditacion"
            >
                <h4 class="acreditaciones-nuevo-modal__section-title">Renovaciones</h4>
                <div :class="{ 'opacity-50': !requiresAcreditacion }">
                    <div class="acreditaciones-nuevo-modal__grid acreditaciones-nuevo-modal__grid--2">
                        <div class="form-field">
                            <label class="form-label" for="create_renovacion">Renovaciones</label>
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
                            <label class="form-label" for="create_fecha_solicitud">Fecha solicitud</label>
                            <input
                                id="create_fecha_solicitud"
                                name="fecha_solicitud"
                                type="date"
                                class="form-input"
                                value="{{ old('fecha_solicitud') }}"
                            >
                        </div>
                    </div>
                </div>
                <p class="acreditaciones-nuevo-modal__hint" x-show="requiresAcreditacion">
                    Debe indicar al menos una fecha (Vigen.ACR o fecha solicitud). El estado no es editable.
                </p>
            </fieldset>

            <div class="acreditaciones-nuevo-modal__actions">
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
