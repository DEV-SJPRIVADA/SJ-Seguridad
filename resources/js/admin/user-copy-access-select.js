document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('apply-access-modal');
    const openBtn = document.getElementById('open-apply-access-modal');
    const form = document.getElementById('apply-access-form');
    const applySource = document.getElementById('apply-access-source');

    if (!modal || !openBtn || !form) {
        return;
    }

    const targetUserName = modal.dataset.targetUserName || 'este usuario';

    const openModal = () => {
        modal.style.display = 'flex';
    };

    const closeModal = () => {
        modal.style.display = 'none';
    };

    openBtn.addEventListener('click', openModal);

    modal.querySelectorAll('[data-apply-access-close]').forEach((el) => {
        el.addEventListener('click', closeModal);
    });

    form.addEventListener('submit', (event) => {
        const sourceInput = document.getElementById('apply-access-source');
        if (!sourceInput || !sourceInput.value) {
            event.preventDefault();
            alert('Por favor seleccione un usuario origen.');
            return;
        }

        const confirmed = window.confirm(
            `Se aplicara el acceso del usuario seleccionado a ${targetUserName}. Esta accion reemplazara rol y permisos directos. ¿Continuar?`,
        );

        if (!confirmed) {
            event.preventDefault();
        }
    });

    if (modal.dataset.openOnLoad === '1') {
        openModal();
    }
});
