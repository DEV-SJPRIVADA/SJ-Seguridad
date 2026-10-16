{{-- Modal global de confirmación de eliminación (Cursos, Acreditaciones, etc.) --}}
<x-modal name="confirm-delete" maxWidth="md" focusable>
    <div
        class="modal-card ficha-empleados-masivos-modal confirm-delete-modal"
        x-data="confirmDeleteModal"
        x-on:open-confirm-delete.window="open($event.detail)"
    >
        <div class="ficha-empleados-masivos-modal__header">
            <div class="ficha-empleados-masivos-modal__heading">
                <span class="ficha-empleados-masivos-modal__heading-icon confirm-delete-modal__icon" aria-hidden="true">
                    <x-lucide-trash-2 width="18" height="18" aria-hidden="true" />
                </span>
                <div>
                    <h3 class="ficha-empleados-masivos-modal__title" id="confirm-delete-title" x-text="title">
                        Confirmar eliminación
                    </h3>
                    <p class="ficha-empleados-masivos-modal__lead">
                        Esta acción no se puede deshacer.
                    </p>
                </div>
            </div>
            <button
                type="button"
                class="ficha-empleados-masivos-modal__close"
                aria-label="Cerrar"
                x-on:click="close()"
                x-bind:disabled="submitting"
            >
                <x-lucide-x width="18" height="18" aria-hidden="true" />
            </button>
        </div>

        <div class="confirm-delete-modal__body">
            <div class="alert alert--danger confirm-delete-modal__alert" role="status">
                <p class="confirm-delete-modal__message" x-text="message">
                    ¿Desea eliminar este registro?
                </p>
            </div>

            <div class="confirm-delete-modal__actions">
                <button
                    type="button"
                    class="btn btn--secondary"
                    x-on:click="close()"
                    x-bind:disabled="submitting"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    class="btn btn--danger"
                    x-on:click="confirm()"
                    x-bind:disabled="submitting"
                >
                    <span x-show="! submitting">Sí, eliminar</span>
                    <span x-show="submitting" x-cloak>Eliminando…</span>
                </button>
            </div>
        </div>
    </div>
</x-modal>
