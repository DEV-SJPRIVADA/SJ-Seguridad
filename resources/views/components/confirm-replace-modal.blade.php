{{-- Modal de confirmación de reemplazo de captura (no reutilizar el de eliminar). --}}
<x-modal name="confirm-replace" maxWidth="md" focusable>
    <div
        class="modal-card ficha-empleados-masivos-modal confirm-delete-modal confirm-replace-modal"
        x-data="{
            title: 'Confirmar reemplazo',
            message: 'Ya existe una captura de este mes. Si continúa, se reemplazarán los datos actuales.',
            submitting: false,
            pendingForm: null,
            open(detail = {}) {
                this.pendingForm = detail.form || null;
                this.title = String(detail.title || 'Confirmar reemplazo').trim() || 'Confirmar reemplazo';
                this.message = String(detail.message || 'Ya existe una captura de este mes. Si continúa, se reemplazarán los datos actuales.').trim();
                this.submitting = false;
                this.$dispatch('open-modal', 'confirm-replace');
            },
            close() {
                if (this.submitting) {
                    return;
                }
                this.pendingForm = null;
                this.$dispatch('close-modal', 'confirm-replace');
            },
            confirm() {
                if (! this.pendingForm || this.submitting) {
                    return;
                }
                this.submitting = true;
                const confirmed = this.pendingForm.querySelector('[name=replace_confirmed]');
                if (confirmed) {
                    confirmed.value = '1';
                }
                this.pendingForm.dataset.replaceArmed = '1';
                if (typeof this.pendingForm.requestSubmit === 'function') {
                    this.pendingForm.requestSubmit();
                } else {
                    this.pendingForm.submit();
                }
            },
        }"
        x-on:open-confirm-replace.window="open($event.detail)"
    >
        <div class="ficha-empleados-masivos-modal__header">
            <div class="ficha-empleados-masivos-modal__heading">
                <span class="ficha-empleados-masivos-modal__heading-icon confirm-replace-modal__icon" aria-hidden="true">
                    <x-lucide-replace width="18" height="18" aria-hidden="true" />
                </span>
                <div>
                    <h3 class="ficha-empleados-masivos-modal__title" id="confirm-replace-title" x-text="title">
                        Confirmar reemplazo
                    </h3>
                    <p class="ficha-empleados-masivos-modal__lead">
                        Los datos actuales de ese mes se sustituirán.
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
            <div class="alert alert--warning confirm-delete-modal__alert" role="status">
                <p class="confirm-delete-modal__message" x-text="message">
                    Ya existe una captura de este mes. Si continúa, se reemplazarán los datos actuales.
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
                    class="btn btn--primary"
                    x-on:click="confirm()"
                    x-bind:disabled="submitting"
                >
                    <span x-show="! submitting">Reemplazar</span>
                    <span x-show="submitting" x-cloak>Reemplazando…</span>
                </button>
            </div>
        </div>
    </div>
</x-modal>
