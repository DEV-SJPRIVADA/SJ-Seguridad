{{-- Variables: $tipoOptions, $escuelaOptions, $estadoOptions, $lookupUrl, $show --}}
<x-modal name="cursos-nuevo" maxWidth="2xl" :show="$show" focusable>
    <div
        class="modal-card ficha-empleados-masivos-modal cursos-registros-page__create-modal"
        x-data="{
            lookupUrl: @js($lookupUrl),
            identityLocked: {{ $show && old('document_number') ? 'true' : 'false' }},
            documentNumber: @js(old('document_number', '')),
            fullName: @js(old('full_name', '')),
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
                        this.identityLocked = true;
                    }
                } catch (e) {}
            },
            unlockIdentity() {
                this.identityLocked = false;
                this.documentNumber = '';
                this.fullName = '';
            },
            applyPrefill(detail) {
                this.documentNumber = detail?.document_number || '';
                this.fullName = detail?.full_name || '';
                this.identityLocked = !!(this.documentNumber && this.fullName);
            },
        }"
        x-on:cursos-nuevo-prefill.window="applyPrefill($event.detail)"
        x-on:cursos-nuevo-reset.window="unlockIdentity()"
    >
        <div class="ficha-empleados-masivos-modal__header">
            <div class="ficha-empleados-masivos-modal__heading">
                <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                    <x-lucide-plus width="18" height="18" aria-hidden="true" />
                </span>
                <div>
                    <h3 class="ficha-empleados-masivos-modal__title">Nuevo registro</h3>
                    <p class="ficha-empleados-masivos-modal__lead">
                        La cédula y el nombre se toman de Ficha empleados (solo lectura tras la búsqueda).
                    </p>
                </div>
            </div>
            <button
                type="button"
                class="ficha-empleados-masivos-modal__close"
                aria-label="Cerrar"
                x-on:click="$dispatch('close-modal', 'cursos-nuevo')"
            >
                <x-lucide-x width="18" height="18" aria-hidden="true" />
            </button>
        </div>

        @if ($errors->any() && ! $errors->has('import_file'))
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
            action="{{ route('gestion-humana.cursos.registros.store') }}"
            class="cursos-registros-page__form"
            enctype="multipart/form-data"
        >
            @csrf
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
                            class="btn btn--ghost btn--sm"
                            x-show="identityLocked"
                            x-cloak
                            x-on:click="unlockIdentity()"
                            title="Cambiar persona"
                        >Cambiar</button>
                    </div>
                </div>
                <div class="form-field">
                    <label class="form-label" for="create_full_name">NOMBRE COMPLETO</label>
                    <input
                        id="create_full_name"
                        name="full_name"
                        type="text"
                        class="form-input"
                        maxlength="255"
                        required
                        readonly
                        x-model="fullName"
                        x-ref="createName"
                    >
                </div>
                <div class="form-field">
                    <label class="form-label" for="create_curso_tipo_id">TIPO CURSO</label>
                    <x-searchable-select
                        id="create_curso_tipo_id"
                        name="curso_tipo_id"
                        :options="$tipoOptions"
                        :value="old('curso_tipo_id')"
                        placeholder="Seleccionar tipo"
                        :required="true"
                    />
                </div>
                <div class="form-field">
                    <label class="form-label" for="create_curso_escuela_id">ESCUELA</label>
                    <x-searchable-select
                        id="create_curso_escuela_id"
                        name="curso_escuela_id"
                        :options="$escuelaOptions"
                        :value="old('curso_escuela_id')"
                        placeholder="Seleccionar escuela"
                        :required="true"
                    />
                </div>
                <div class="form-field">
                    <label class="form-label" for="create_fecha_expedicion">FECHA EXPEDICION</label>
                    <input
                        id="create_fecha_expedicion"
                        name="fecha_expedicion"
                        type="date"
                        class="form-input"
                        required
                        value="{{ old('fecha_expedicion') }}"
                    >
                </div>
                <div class="form-field">
                    <label class="form-label" for="create_numero_curso">No.CURSO</label>
                    <input
                        id="create_numero_curso"
                        name="numero_curso"
                        type="text"
                        class="form-input"
                        maxlength="100"
                        required
                        value="{{ old('numero_curso') }}"
                    >
                </div>
                <div class="form-field">
                    <label class="form-label" for="create_estado">ESTADO</label>
                    <x-searchable-select
                        id="create_estado"
                        name="estado"
                        :options="$estadoOptions"
                        :value="old('estado', \App\Models\EmployeeCurso::ESTADO_ACTUALIZADO)"
                        placeholder="Seleccione estado"
                        :required="true"
                    />
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
                <div class="form-field cursos-registros-page__form-span">
                    <label class="form-label" for="create_document">DOCUMENTO (opcional)</label>
                    <div class="cursos-registros-page__file-picker" x-data="{ fileName: '' }">
                        <input
                            id="create_document"
                            name="document"
                            type="file"
                            class="cursos-registros-page__file-input"
                            accept=".pdf,.jpg,.jpeg,.png,.webp"
                            @change="fileName = $event.target.files?.[0]?.name || ''"
                        >
                        <span class="cursos-registros-page__file-name" x-text="fileName || 'Sin archivo seleccionado'"></span>
                        <div class="cursos-registros-page__file-actions">
                            <label for="create_document" class="btn btn--secondary btn--sm">
                                <x-lucide-upload width="15" height="15" aria-hidden="true" />
                                Elegir archivo
                            </label>
                        </div>
                    </div>
                    <p class="panel-text">PDF, JPG, PNG o WEBP. Máximo 10 MB.</p>
                </div>
            </div>
            <div class="cursos-registros-page__form-actions">
                <button type="button" class="btn btn--secondary" x-on:click="$dispatch('close-modal', 'cursos-nuevo')">Cancelar</button>
                <button type="submit" class="btn btn--primary">Guardar</button>
            </div>
        </form>
    </div>
</x-modal>
