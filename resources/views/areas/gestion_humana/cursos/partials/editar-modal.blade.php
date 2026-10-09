{{-- Variables: $tipoOptions, $escuelaOptions, $estadoOptions
     Estado Alpine en página padre: editOpen, editForm, editIdentityLocked, closeEdit, unlockEditIdentity, lookupName, syncEditSelects.
     Opcional: $returnContext / $returnCola para volver a Validaciones. --}}
@php
    $returnContext = $returnContext ?? null;
    $returnCola = $returnCola ?? null;
@endphp
<div
    class="cursos-registros-page__modal"
    x-show="editOpen"
    x-cloak
    @keydown.escape.window="closeEdit()"
    role="presentation"
>
    <div class="cursos-registros-page__modal-backdrop" @click="closeEdit()"></div>
    <div
        class="cursos-registros-page__modal-panel panel cursos-edit-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="cursos-edit-title"
        @click.stop
    >
        <div class="modal-card ficha-empleados-masivos-modal cursos-registros-page__create-modal">
            <div class="ficha-empleados-masivos-modal__header">
                <div class="ficha-empleados-masivos-modal__heading">
                    <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                        <x-lucide-square-pen width="18" height="18" />
                    </span>
                    <div>
                        <h3 class="ficha-empleados-masivos-modal__title" id="cursos-edit-title">Editar registro</h3>
                        <p class="ficha-empleados-masivos-modal__lead">
                            La cédula y el nombre se toman de Ficha empleados. Escuela, tipo y estado usan el catálogo.
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
                @if (! empty($returnContext))
                    <input type="hidden" name="_return_context" value="{{ $returnContext }}">
                    @if (! empty($returnCola))
                        <input type="hidden" name="cola" value="{{ $returnCola }}">
                    @endif
                @endif

                <div class="cursos-registros-page__form-grid">
                    <div class="form-field">
                        <label class="form-label" for="edit_document_number">CÉDULA</label>
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
                            name="full_name"
                            type="text"
                            class="form-input"
                            maxlength="255"
                            required
                            readonly
                            x-model="editForm.full_name"
                            placeholder="Se completa desde Ficha"
                        >
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="edit_curso_tipo_id">TIPO CURSO</label>
                        <x-searchable-select
                            id="edit_curso_tipo_id"
                            name="curso_tipo_id"
                            class="js-edit-curso-tipo-select"
                            :options="$tipoOptions"
                            :value="''"
                            placeholder="Seleccionar tipo"
                            searchPlaceholder="Buscar tipo…"
                            :required="true"
                            :allow-clear="false"
                        />
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="edit_curso_escuela_id">ESCUELA</label>
                        <x-searchable-select
                            id="edit_curso_escuela_id"
                            name="curso_escuela_id"
                            class="js-edit-curso-escuela-select"
                            :options="$escuelaOptions"
                            :value="''"
                            placeholder="Seleccionar escuela"
                            searchPlaceholder="Buscar escuela…"
                            :required="true"
                            :allow-clear="false"
                        />
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="edit_fecha_expedicion">FECHA EXPEDICIÓN</label>
                        <input
                            id="edit_fecha_expedicion"
                            name="fecha_expedicion"
                            type="date"
                            class="form-input"
                            required
                            x-model="editForm.fecha_expedicion"
                        >
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="edit_numero_curso">No.CURSO</label>
                        <input
                            id="edit_numero_curso"
                            name="numero_curso"
                            type="text"
                            class="form-input"
                            maxlength="100"
                            required
                            x-model="editForm.numero_curso"
                            placeholder="Ej. ECSP0015-M256412"
                        >
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="edit_estado">ESTADO</label>
                        <x-searchable-select
                            id="edit_estado"
                            name="estado"
                            class="js-edit-estado-select"
                            :options="$estadoOptions"
                            :value="''"
                            placeholder="Seleccione estado"
                            :required="true"
                            :allow-clear="false"
                        />
                    </div>

                    <div class="form-field cursos-registros-page__form-span">
                        <label class="form-label" for="edit_observaciones">OBSERVACIONES</label>
                        <textarea
                            id="edit_observaciones"
                            name="observaciones"
                            class="form-input"
                            rows="3"
                            x-model="editForm.observaciones"
                            placeholder="Notas opcionales"
                        ></textarea>
                    </div>
                </div>

                <p class="cursos-edit-modal__hint">
                    Al cambiar la fecha de expedición, el sistema puede recalcular vigencia y estado (salvo SOLICITADO).
                </p>

                <div class="cursos-registros-page__form-actions cursos-edit-modal__actions">
                    <button type="button" class="btn btn--secondary" @click="closeEdit()">Cancelar</button>
                    <button type="submit" class="btn btn--primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
