/**
 * Intercepta formularios `.js-confirm-delete` y abre el modal corporativo
 * en lugar del `window.confirm` nativo.
 */
export default function confirmDeleteModal() {
    return {
        title: 'Confirmar eliminación',
        message: '¿Desea eliminar este registro?',
        submitting: false,
        pendingForm: null,

        open(detail = {}) {
            this.pendingForm = detail.form || null;
            this.title = String(detail.title || 'Confirmar eliminación').trim() || 'Confirmar eliminación';
            this.message = String(detail.message || '¿Desea eliminar este registro?').trim()
                || '¿Desea eliminar este registro?';
            this.submitting = false;
            this.$dispatch('open-modal', 'confirm-delete');
        },

        close() {
            if (this.submitting) {
                return;
            }
            this.pendingForm = null;
            this.$dispatch('close-modal', 'confirm-delete');
        },

        confirm() {
            if (! this.pendingForm || this.submitting) {
                return;
            }

            this.submitting = true;
            this.pendingForm.dataset.confirmDeleteArmed = '1';
            if (typeof this.pendingForm.requestSubmit === 'function') {
                this.pendingForm.requestSubmit();
            } else {
                this.pendingForm.submit();
            }
        },
    };
}

function bindConfirmDeleteForms() {
    document.addEventListener(
        'submit',
        (event) => {
            const form = event.target;
            if (! (form instanceof HTMLFormElement)) {
                return;
            }
            if (! form.classList.contains('js-confirm-delete')) {
                return;
            }
            if (form.dataset.confirmDeleteArmed === '1') {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            window.dispatchEvent(
                new CustomEvent('open-confirm-delete', {
                    detail: {
                        form,
                        title: form.getAttribute('data-confirm-title') || 'Confirmar eliminación',
                        message: form.getAttribute('data-confirm-message')
                            || '¿Desea eliminar este registro? Esta acción no se puede deshacer.',
                    },
                }),
            );
        },
        true,
    );
}

bindConfirmDeleteForms();
