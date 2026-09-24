{{-- Estado Alpine en página padre: bulkUpdateOpen, selectedCount/Rows/Ids, bulkForm, bulkHasPayload, submittingBulk. --}}
@php
    $observacionesMax = (int) config('acreditaciones.limits.observaciones_max', 5000);
@endphp
<div
    class="cursos-registros-page__modal"
    x-show="bulkUpdateOpen"
    x-cloak
    @keydown.escape.window="closeBulkUpdate()"
    role="presentation"
>
    <div class="cursos-registros-page__modal-backdrop" @click="closeBulkUpdate()"></div>
    <div
        class="cursos-registros-page__modal-panel panel acreditaciones-bulk-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="acreditaciones-bulk-update-title"
        @click.stop
    >
        <div class="modal-card ficha-empleados-masivos-modal cursos-registros-page__create-modal">
            <div class="ficha-empleados-masivos-modal__header">
                <div class="ficha-empleados-masivos-modal__heading">
                    <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                        <x-lucide-list-checks width="18" height="18" />
                    </span>
                    <div>
                        <h3 class="ficha-empleados-masivos-modal__title" id="acreditaciones-bulk-update-title">
                            Actualizar seleccionados
                        </h3>
                        <p class="ficha-empleados-masivos-modal__lead">
                            Se actualizarán
                            <strong x-text="selectedCount"></strong>
                            registro(s). Complete al menos un campo; los vacíos no se modifican.
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    class="ficha-empleados-masivos-modal__close"
                    title="Cerrar"
                    aria-label="Cerrar"
                    @click="closeBulkUpdate()"
                    :disabled="submittingBulk"
                >
                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                </button>
            </div>

            <div class="cursos-registros-page__bulk-list-wrap acreditaciones-bulk-modal__list">
                <table class="data-table cursos-registros-page__bulk-list">
                    <thead>
                        <tr>
                            <th>Cédula</th>
                            <th>Nombre</th>
                            <th>CARGO APO</th>
                            <th>VIGEN.ACR</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="row in selectedRows" :key="row.id">
                            <tr>
                                <td x-text="row.document_number"></td>
                                <td x-text="row.full_name"></td>
                                <td x-text="row.cargo_apo"></td>
                                <td x-text="row.vigencia_acr || '—'"></td>
                                <td x-text="row.estado"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <form
                class="cursos-registros-page__form"
                x-on:submit.prevent="submitBulkUpdate()"
            >
                <div class="cursos-registros-page__form-grid">
                    <div class="form-field" @change="bulkForm.renovacion = ($event.detail && $event.detail.value !== undefined) ? String($event.detail.value) : (bulkForm.renovacion || '')">
                        <label class="form-label" for="bulk_renovacion">Renovaciones</label>
                        <x-searchable-select
                            id="bulk_renovacion"
                            name="renovacion_ui"
                            class="js-bulk-renovacion-select"
                            :options="$renovacionOptions"
                            :value="''"
                            placeholder="Sin cambio"
                            :allow-clear="true"
                        />
                    </div>
                    <div class="form-field">
                        <label class="form-label" for="bulk_fecha_solicitud">Fecha de solicitud</label>
                        <input
                            id="bulk_fecha_solicitud"
                            type="date"
                            class="form-input"
                            x-model="bulkForm.fecha_solicitud"
                            autocomplete="off"
                        >
                    </div>
                    <div class="form-field cursos-registros-page__form-span">
                        <label class="form-label" for="bulk_observaciones">Observaciones</label>
                        <textarea
                            id="bulk_observaciones"
                            class="form-input"
                            rows="3"
                            maxlength="{{ $observacionesMax }}"
                            x-model="bulkForm.observaciones"
                            placeholder="Si completa este campo, se sobrescribe en todos los seleccionados"
                        ></textarea>
                    </div>
                </div>

                <p class="acreditaciones-bulk-modal__hint">
                    Si indica fecha de solicitud, el estado de esos registros pasará a <strong>EN PROCESO</strong>.
                </p>

                <div class="cursos-registros-page__form-actions acreditaciones-bulk-modal__actions">
                    <button
                        type="button"
                        class="btn btn--secondary"
                        @click="closeBulkUpdate()"
                        :disabled="submittingBulk"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="btn btn--primary"
                        :disabled="selectedCount < 1 || ! bulkHasPayload || submittingBulk"
                    >
                        <span x-show="! submittingBulk">Aplicar cambios</span>
                        <span x-show="submittingBulk" x-cloak>Guardando…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
