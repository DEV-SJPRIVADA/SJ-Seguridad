{{-- Modal alta registro matriz MT-ST-04: cédula + lookup ficha + campos editables --}}
@php
    $show = $show ?? false;
@endphp
<x-modal name="mt-st-04-nuevo" maxWidth="2xl" :show="$show" focusable>
    <div
        class="modal-card ficha-empleados-masivos-modal cursos-registros-page__create-modal"
        x-data="{
            lookupUrl: @js($lookupUrl),
            identityLocked: {{ $show && old('document_number') ? 'true' : 'false' }},
            documentNumber: @js(old('document_number', '')),
            fullName: @js(old('full_name', '')),
            cargo: @js(old('cargo', '')),
            ciudad: @js(old('ciudad', '')),
            puesto: @js(old('puesto', '')),
            lookupError: '',
            async lookupFicha(cedula) {
                const value = String(cedula || '').trim();
                this.lookupError = '';
                if (!value || this.identityLocked) return;
                try {
                    const res = await fetch(this.lookupUrl + '?cedula=' + encodeURIComponent(value), {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    if (data.found) {
                        this.documentNumber = data.document_number || value;
                        this.fullName = data.full_name || '';
                        this.cargo = data.cargo || '';
                        this.ciudad = data.ciudad || '';
                        this.puesto = data.puesto || '';
                        this.identityLocked = true;
                    } else {
                        this.lookupError = 'La cédula no existe en Ficha empleados.';
                        this.fullName = '';
                        this.cargo = '';
                        this.ciudad = '';
                        this.puesto = '';
                    }
                } catch (e) {}
            },
            unlockIdentity() {
                this.identityLocked = false;
                this.documentNumber = '';
                this.fullName = '';
                this.cargo = '';
                this.ciudad = '';
                this.puesto = '';
                this.lookupError = '';
            },
        }"
    >
        <div class="ficha-empleados-masivos-modal__header">
            <div class="ficha-empleados-masivos-modal__heading">
                <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                    <x-lucide-plus width="18" height="18" aria-hidden="true" />
                </span>
                <div>
                    <h3 class="ficha-empleados-masivos-modal__title">Nuevo registro MT-ST-04</h3>
                    <p class="ficha-empleados-masivos-modal__lead">
                        La cédula debe existir en Ficha empleados. Vencimientos y estados se calculan al guardar.
                    </p>
                </div>
            </div>
            <button
                type="button"
                class="ficha-empleados-masivos-modal__close"
                aria-label="Cerrar"
                x-on:click="$dispatch('close-modal', 'mt-st-04-nuevo')"
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
            action="{{ route('gestion-humana.mt-st-04.matriz.store') }}"
            class="cursos-registros-page__form"
        >
            @csrf
            <div class="cursos-registros-page__form-grid">
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
                            @blur="lookupFicha($event.target.value)"
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
                    <p class="panel-text" style="margin-top:0.25rem;font-size:0.8rem;color:var(--color-danger, #b91c1c);" x-show="lookupError" x-text="lookupError" x-cloak></p>
                </div>
                <div class="form-field">
                    <label class="form-label" for="create_full_name">Nombre</label>
                    <input id="create_full_name" type="text" class="form-input" readonly x-model="fullName" placeholder="Desde Ficha">
                </div>
                <div class="form-field">
                    <label class="form-label" for="create_cargo">Cargo</label>
                    <input id="create_cargo" type="text" class="form-input" readonly x-model="cargo" placeholder="Desde Ficha">
                </div>
                <div class="form-field">
                    <label class="form-label" for="create_ciudad">Ciudad</label>
                    <input id="create_ciudad" type="text" class="form-input" readonly x-model="ciudad" placeholder="Desde Ficha">
                </div>
                <div class="form-field">
                    <label class="form-label" for="create_puesto">Puesto</label>
                    <input id="create_puesto" type="text" class="form-input" readonly x-model="puesto" placeholder="Desde Ficha">
                </div>

                <div class="form-field">
                    <label class="form-label" for="create_arma">Arma</label>
                    <x-searchable-select
                        id="create_arma"
                        name="arma"
                        :options="$siNoOptions"
                        :value="old('arma', '')"
                        placeholder="Seleccionar"
                        :allow-clear="true"
                    />
                </div>
                <div class="form-field">
                    <label class="form-label" for="create_fecha_examen_1">Fecha de examen (psicofísico)</label>
                    <input id="create_fecha_examen_1" name="fecha_examen_1" type="date" class="form-input" value="{{ old('fecha_examen_1') }}">
                </div>
                <div class="form-field">
                    <label class="form-label" for="create_apto">Apto</label>
                    <x-searchable-select
                        id="create_apto"
                        name="apto"
                        :options="$siNoOptions"
                        :value="old('apto', '')"
                        placeholder="Seleccionar"
                        :allow-clear="true"
                    />
                </div>
                <div class="form-field cursos-registros-page__form-span">
                    <label class="form-label" for="create_observaciones_1">Observaciones</label>
                    <textarea id="create_observaciones_1" name="observaciones_1" class="form-input" rows="2">{{ old('observaciones_1') }}</textarea>
                </div>

                <div class="form-field">
                    <label class="form-label" for="create_fecha_examen_2">Fecha examen (psicosensométrico)</label>
                    <input id="create_fecha_examen_2" name="fecha_examen_2" type="date" class="form-input" value="{{ old('fecha_examen_2') }}">
                </div>
                <div class="form-field cursos-registros-page__form-span">
                    <label class="form-label" for="create_observaciones_2">Observaciones 2</label>
                    <textarea id="create_observaciones_2" name="observaciones_2" class="form-input" rows="2">{{ old('observaciones_2') }}</textarea>
                </div>
            </div>

            <div class="cursos-registros-page__form-actions">
                <button type="button" class="btn btn--secondary" x-on:click="$dispatch('close-modal', 'mt-st-04-nuevo')">Cancelar</button>
                <button type="submit" class="btn btn--primary">Guardar</button>
            </div>
        </form>
    </div>
</x-modal>
