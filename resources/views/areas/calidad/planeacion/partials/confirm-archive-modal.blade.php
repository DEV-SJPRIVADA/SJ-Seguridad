{{-- Confirmación de archivo: el indicador se puede restablecer después. --}}
<x-modal name="confirm-archive" maxWidth="md" focusable>
    <div
        class="modal-card ficha-empleados-masivos-modal confirm-delete-modal confirm-archive-modal"
        x-data="{
            title: 'Archivar indicador',
            message: '¿Archivar este indicador? Dejará de capturarse hasta que lo restablezca.',
            submitting: false,
            pendingForm: null,
            open(detail = {}) {
                this.pendingForm = detail.form || null;
                this.title = String(detail.title || 'Archivar indicador').trim() || 'Archivar indicador';
                this.message = String(detail.message || '¿Archivar este indicador? Dejará de capturarse hasta que lo restablezca.').trim();
                this.submitting = false;
                this.$dispatch('open-modal', 'confirm-archive');
            },
            close() {
                if (this.submitting) {
                    return;
                }
                this.pendingForm = null;
                this.$dispatch('close-modal', 'confirm-archive');
            },
            confirm() {
                if (! this.pendingForm || this.submitting) {
                    return;
                }
                this.submitting = true;
                this.pendingForm.dataset.confirmArchiveArmed = '1';
                if (typeof this.pendingForm.requestSubmit === 'function') {
                    this.pendingForm.requestSubmit();
                } else {
                    this.pendingForm.submit();
                }
            },
        }"
        x-on:open-confirm-archive.window="open($event.detail)"
    >
        <div class="ficha-empleados-masivos-modal__header">
            <div class="ficha-empleados-masivos-modal__heading">
                <span class="ficha-empleados-masivos-modal__heading-icon confirm-archive-modal__icon" aria-hidden="true">
                    <x-lucide-archive width="18" height="18" aria-hidden="true" />
                </span>
                <div>
                    <h3 class="ficha-empleados-masivos-modal__title" id="confirm-archive-title" x-text="title">
                        Archivar indicador
                    </h3>
                    <p class="ficha-empleados-masivos-modal__lead">
                        No se borra el historial. Puede restablecerlo después.
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
                    ¿Archivar este indicador? Dejará de capturarse hasta que lo restablezca.
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
                    <span x-show="! submitting">Archivar</span>
                    <span x-show="submitting" x-cloak>Archivando…</span>
                </button>
            </div>
        </div>
    </div>
</x-modal>

@once
    <script>
        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (! (form instanceof HTMLFormElement) || ! form.classList.contains('js-confirm-archive')) {
                return;
            }
            if (form.dataset.confirmArchiveArmed === '1') {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            window.dispatchEvent(new CustomEvent('open-confirm-archive', {
                detail: {
                    form: form,
                    title: form.getAttribute('data-confirm-title') || 'Archivar indicador',
                    message: form.getAttribute('data-confirm-message')
                        || '¿Archivar este indicador? Dejará de capturarse hasta que lo restablezca.',
                },
            }));
        }, true);
    </script>
@endonce
