{{-- Modal edición registro matriz; estado Alpine en la página padre (editOpen, editForm). --}}
<div
    class="cursos-registros-page__modal"
    x-show="editOpen"
    x-cloak
    @keydown.escape.window="closeEdit()"
    role="presentation"
>
    <div class="cursos-registros-page__modal-backdrop" @click="closeEdit()"></div>
    <div
        class="cursos-registros-page__modal-panel panel"
        role="dialog"
        aria-modal="true"
        aria-labelledby="mt-st-04-edit-title"
        @click.stop
    >
        <div class="modal-card ficha-empleados-masivos-modal cursos-registros-page__create-modal">
            <div class="ficha-empleados-masivos-modal__header">
                <div class="ficha-empleados-masivos-modal__heading">
                    <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                        <x-lucide-square-pen width="18" height="18" />
                    </span>
                    <div>
                        <h3 class="ficha-empleados-masivos-modal__title" id="mt-st-04-edit-title">Editar registro MT-ST-04</h3>
                        <p class="ficha-empleados-masivos-modal__lead">
                            Vencimientos y estados se recalculan al guardar. Nombre, cargo, ciudad y puesto vienen de Ficha.
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

            <form method="POST" :action="editForm.update_url" class="cursos-registros-page__form mt-st-04-form">
                @csrf
                @method('PATCH')

                {{-- Identidad desde Ficha (solo lectura salvo cambio de cédula) --}}
                <section class="mt-st-04-form-section mt-st-04-form-section--identidad" aria-labelledby="mt-st-04-edit-identidad-title">
                    <div class="mt-st-04-form-section__head">
                        <h4 class="mt-st-04-form-section__title" id="mt-st-04-edit-identidad-title">Identificación (Ficha)</h4>
                        <p class="mt-st-04-form-section__desc">Datos laborales en vivo; no se editan en esta matriz.</p>
                    </div>
                    <div class="cursos-registros-page__form-grid">
                        <div class="form-field">
                            <label class="form-label" for="edit_document_number">Cédula</label>
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
                                    @blur="lookupFicha($event.target.value, 'edit')"
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
                            <label class="form-label" for="edit_full_name">Nombre</label>
                            <input id="edit_full_name" type="text" class="form-input" readonly x-model="editForm.full_name">
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="edit_cargo">Cargo</label>
                            <input id="edit_cargo" type="text" class="form-input" readonly x-model="editForm.cargo">
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="edit_ciudad">Ciudad</label>
                            <input id="edit_ciudad" type="text" class="form-input" readonly x-model="editForm.ciudad">
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="edit_puesto">Puesto</label>
                            <input id="edit_puesto" type="text" class="form-input" readonly x-model="editForm.puesto">
                        </div>
                    </div>
                </section>

                {{-- Bloque examen psicofísico (manejo de armas) --}}
                <section class="mt-st-04-form-section mt-st-04-form-section--psico" aria-labelledby="mt-st-04-edit-psico-title">
                    <div class="mt-st-04-form-section__head">
                        <h4 class="mt-st-04-form-section__title" id="mt-st-04-edit-psico-title">Examen psicofísico (armas)</h4>
                        <p class="mt-st-04-form-section__desc">Arma, fecha de examen, aptitud y observaciones del examen 1.</p>
                    </div>
                    <div class="cursos-registros-page__form-grid">
                        <div class="form-field">
                            <label class="form-label" for="edit_arma">Arma</label>
                            <x-searchable-select
                                id="edit_arma"
                                name="arma"
                                class="js-edit-arma-select"
                                :options="$siNoOptions"
                                :value="''"
                                placeholder="Seleccionar"
                                :allow-clear="true"
                            />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="edit_fecha_examen_1">Fecha de examen</label>
                            <input id="edit_fecha_examen_1" name="fecha_examen_1" type="date" class="form-input" x-model="editForm.fecha_examen_1">
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="edit_fecha_vencimiento_1">Fecha de vencimiento</label>
                            <input id="edit_fecha_vencimiento_1" type="date" class="form-input" readonly x-model="editForm.fecha_vencimiento_1" title="Calculada al guardar">
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="edit_apto">Apto</label>
                            <x-searchable-select
                                id="edit_apto"
                                name="apto"
                                class="js-edit-apto-select"
                                :options="$siNoOptions"
                                :value="''"
                                placeholder="Seleccionar"
                                :allow-clear="true"
                            />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="edit_estado_1">Estado</label>
                            <input id="edit_estado_1" type="text" class="form-input" readonly x-model="editForm.estado_1" title="Calculado al guardar">
                        </div>
                        <div class="form-field cursos-registros-page__form-span">
                            <label class="form-label" for="edit_observaciones_1">Observaciones</label>
                            <textarea id="edit_observaciones_1" name="observaciones_1" class="form-input" rows="2" x-model="editForm.observaciones_1"></textarea>
                        </div>
                    </div>
                </section>

                {{-- Bloque examen psicosensométrico (seguridad vial); NO APLICA si cargo GUARDA/OPERADOR --}}
                <section class="mt-st-04-form-section mt-st-04-form-section--senso" aria-labelledby="mt-st-04-edit-senso-title">
                    <div class="mt-st-04-form-section__head">
                        <h4 class="mt-st-04-form-section__title" id="mt-st-04-edit-senso-title">Examen psicosensométrico (vial)</h4>
                        <p class="mt-st-04-form-section__desc">
                            Fecha de examen y observaciones del examen 2. Cargos GUARDA u OPERADOR quedan en NO APLICA.
                        </p>
                    </div>
                    <div class="cursos-registros-page__form-grid">
                        <div class="form-field">
                            <label class="form-label" for="edit_fecha_examen_2">Fecha de examen</label>
                            <input id="edit_fecha_examen_2" name="fecha_examen_2" type="date" class="form-input" x-model="editForm.fecha_examen_2">
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="edit_fecha_vencimiento_2">Fecha de vencimiento</label>
                            <input id="edit_fecha_vencimiento_2" type="date" class="form-input" readonly x-model="editForm.fecha_vencimiento_2" title="Calculada al guardar">
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="edit_estado_2">Estado</label>
                            <input id="edit_estado_2" type="text" class="form-input" readonly x-model="editForm.estado_2" title="Calculado al guardar">
                        </div>
                        <div class="form-field cursos-registros-page__form-span">
                            <label class="form-label" for="edit_observaciones_2">Observaciones</label>
                            <textarea id="edit_observaciones_2" name="observaciones_2" class="form-input" rows="2" x-model="editForm.observaciones_2"></textarea>
                        </div>
                    </div>
                </section>

                <div class="cursos-registros-page__form-actions">
                    <button type="button" class="btn btn--secondary" @click="closeEdit()">Cancelar</button>
                    <button type="submit" class="btn btn--primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
