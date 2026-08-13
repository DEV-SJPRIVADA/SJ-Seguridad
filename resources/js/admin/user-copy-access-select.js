document.addEventListener('DOMContentLoaded', () => {
    if (typeof jQuery === 'undefined' || ! jQuery.fn.select2) {
        return;
    }

    const $ = jQuery;
    const selectLanguage = {
        noResults: () => 'Sin resultados',
        searching: () => 'Buscando…',
        inputTooShort: () => 'Escriba para buscar',
    };

    const baseSelectOptions = {
        width: '100%',
        allowClear: true,
        language: selectLanguage,
    };

    const $copyFromUser = $('#copy-from-user');

    if ($copyFromUser.length) {
        $copyFromUser.select2({
            ...baseSelectOptions,
            placeholder: 'Buscar usuario origen…',
        });
    }

    const $modal = $('#apply-access-modal');
    const $applySource = $('#apply-access-source');
    const $openBtn = $('#open-apply-access-modal');
    const $form = $('#apply-access-form');

    if ($applySource.length) {
        $applySource.select2({
            ...baseSelectOptions,
            placeholder: 'Buscar usuario origen…',
            dropdownParent: $modal.length ? $modal : $(document.body),
        });
    }

    if (! $modal.length || ! $openBtn.length || ! $form.length) {
        return;
    }

    const targetUserName = $modal.data('target-user-name') || 'este usuario';

    const openModal = () => {
        $modal.css('display', 'flex');

        if ($applySource.length) {
            $applySource.select2('open');
        }
    };

    const closeModal = () => {
        $modal.css('display', 'none');

        if ($applySource.length) {
            $applySource.select2('close');
        }
    };

    $openBtn.on('click', openModal);
    $modal.find('[data-apply-access-close]').on('click', closeModal);

    $form.on('submit', (event) => {
        const sourceLabel = $applySource.find('option:selected').text().trim() || 'el usuario seleccionado';
        const confirmed = window.confirm(
            `Se aplicara el acceso de ${sourceLabel} a ${targetUserName}. Esta accion reemplazara rol y permisos directos. ¿Continuar?`,
        );

        if (! confirmed) {
            event.preventDefault();
        }
    });

    if ($modal.data('open-on-load') === 1 || $modal.data('open-on-load') === '1') {
        openModal();
    }
});
