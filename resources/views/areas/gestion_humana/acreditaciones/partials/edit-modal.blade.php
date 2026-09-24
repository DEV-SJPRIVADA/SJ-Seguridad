{{-- Variables: $cargoApoOptions — estado Alpine en página padre (editOpen, editForm, lookupName). --}}
<div
    class="cursos-registros-page__modal"
    x-show="editOpen"
    x-cloak
    @keydown.escape.window="closeEdit()"
    role="presentation"
>
    <div class="cursos-registros-page__modal-backdrop" @click="closeEdit()"></div>
    <div
        class="cursos-registros-page__modal-panel panel acreditaciones-edit-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="acreditaciones-edit-title"
        @click.stop
    >
        <div class="modal-card ficha-empleados-masivos-modal cursos-registros-page__create-modal">
            <div class="ficha-empleados-masivos-modal__header">
                <div class="ficha-empleados-masivos-modal__heading">
                    <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                        <x-lucide-square-pen width="18" height="18" />
                    </span>
                    <div>
                        <h3 class="ficha-empleados-masivos-modal__title" id="acreditaciones-edit-title">Editar acreditado</h3>
                        <p class="ficha-empleados-masivos-modal__lead">
                            La cédula debe existir en Ficha empleados. El estado se recalcula al guardar.
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    class="ficha-empleados-masivos-modal__close"
                    title="Cerrar"
                    aria-label="Cerrar"
                    @click="closeEdit()"
                >
                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                </button>
            </div>

            <form method="POST" :action="editForm.update_url" class="cursos-registros-page__form">
                @csrf
                @method('PATCH')

                <div class="cursos-registros-page__form-grid">
                    <div class="form-field">
                        <label class="form-label" for="edit_document_number">CEDULA</label>
                        <div class="cursos-registros-page__identity-row">
                            <input
                                id="edit_document_number"
                                name="document_number"
                                type="text"
                                class="form-input"
                                maxlength="50"
                                required
                                x-model="editForm.document_number"
                                x-bind:readonly="editIdentityLocked"
                                @blur="lookupName($event.target.value, 'edit')"
                            >
                            <button
                                type="button"
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                x-show="editIdentityLocked"
                                x-cloak
                                @click="unlockEditIdentity()"
                                title="Cambiar persona"
                                aria-label="Cambiar persona"
                            >
                                <x-lucide-user-round-pen width="18" height="18" aria-hidden="true" />
                            </button>
                        </div>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="edit_full_name">NOMBRE COMPLETO</label>
                        <input
                            id="edit_full_name"
                            type="text"
                            class="form-input"
                            maxlength="255"
                            readonly
                            x-model="editForm.full_name"
                            placeholder="Se completa desde Ficha"
                        >
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="edit_cargo">CARGO</label>
                        <input
                            id="edit_cargo"
                            name="cargo"
                            type="text"
                            class="form-input"
                            maxlength="255"
                            required
                            x-model="editForm.cargo"
                        >
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="edit_cargo_apo">CARGO APO</label>
                        <x-searchable-select
                            id="edit_cargo_apo"
                            name="cargo_apo"
                            class="js-edit-cargo-apo-select"
                            :options="$cargoApoOptions"
                            :value="''"
                            placeholder="Seleccionar CARGO APO"
                            searchPlaceholder="Buscar CARGO APO…"
                            :required="true"
                            :allow-clear="false"
                        />
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="edit_vigencia_acr">VIGEN.ACR</label>
                        <input
                            id="edit_vigencia_acr"
                            name="vigencia_acr"
                            type="date"
                            class="form-input"
                            x-model="editForm.vigencia_acr"
                        >
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="edit_estado">ESTADO</label>
                        <input
                            id="edit_estado"
                            type="text"
                            class="form-input"
                            readonly
                            x-model="editForm.estado_label"
                            title="Se calcula automáticamente al guardar"
                        >
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="edit_renovacion">RENOVACIONES</label>
                        <x-searchable-select
                            id="edit_renovacion"
                            name="renovacion"
                            class="js-edit-renovacion-select"
                            :options="$renovacionOptions"
                            :value="''"
                            placeholder="Sin renovación"
                            :allow-clear="true"
                        />
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="edit_fecha_solicitud">FECHA SOLICITUD</label>
                        <input
                            id="edit_fecha_solicitud"
                            name="fecha_solicitud"
                            type="date"
                            class="form-input"
                            x-model="editForm.fecha_solicitud"
                        >
                    </div>

                    <div class="form-field cursos-registros-page__form-span">
                        <label class="form-label" for="edit_observaciones">OBSERVACIONES</label>
                        <textarea
                            id="edit_observaciones"
                            name="observaciones"
                            class="form-input"
                            rows="3"
                            x-model="editForm.observaciones"
                        ></textarea>
                    </div>
                </div>

                <p class="acreditaciones-edit-modal__hint">
                    Indique al menos una fecha (VIGEN.ACR o FECHA SOLICITUD). El ESTADO no es editable.
                </p>

                <div class="cursos-registros-page__form-actions acreditaciones-edit-modal__actions">
                    <button type="button" class="btn btn--secondary" @click="closeEdit()">Cancelar</button>
                    <button type="submit" class="btn btn--primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
