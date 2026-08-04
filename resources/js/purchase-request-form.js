document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('purchase-request-form');
    const itemsContainer = document.getElementById('purchase-items-container');
    const addItemBtn = document.getElementById('purchase-add-item-btn');
    const solicitudParaSelect = document.getElementById('solicitud_para');
    const clienteFields = document.getElementById('purchase-cliente-fields');

    if (!form || !itemsContainer || !addItemBtn) {
        return;
    }

    let itemIndex = itemsContainer.querySelectorAll('[data-purchase-item-row]').length;
    const previewUrls = new WeakMap();

    function toggleClienteFields() {
        if (!clienteFields || !solicitudParaSelect) {
            return;
        }

        const isCliente = solicitudParaSelect.value === 'Cliente';
        clienteFields.hidden = !isCliente;
        clienteFields.querySelectorAll('input, select').forEach(function (field) {
            if (field.type === 'radio' || field.type === 'checkbox') {
                field.disabled = !isCliente;
            } else {
                field.required = isCliente && field.dataset.clienteRequired === 'true';
            }
        });
    }

    function revokePreviewUrl(zone) {
        const existing = previewUrls.get(zone);

        if (existing) {
            URL.revokeObjectURL(existing);
            previewUrls.delete(zone);
        }
    }

    function showPhotoPreview(zone, file) {
        const placeholder = zone.querySelector('.purchase-item-foto__placeholder');
        const preview = zone.querySelector('.purchase-item-foto__preview');
        const img = zone.querySelector('.purchase-item-foto__img');
        const name = zone.querySelector('.purchase-item-foto__name');

        if (!placeholder || !preview || !img || !name) {
            return;
        }

        revokePreviewUrl(zone);

        const url = URL.createObjectURL(file);
        previewUrls.set(zone, url);

        img.src = url;
        name.textContent = file.name;
        placeholder.hidden = true;
        preview.hidden = false;
        zone.classList.add('has-file');
    }

    function clearPhotoPreview(zone) {
        const input = zone.querySelector('.purchase-item-foto__input');
        const existingPathInput = zone.querySelector('.purchase-item-foto__existing-path');
        const placeholder = zone.querySelector('.purchase-item-foto__placeholder');
        const preview = zone.querySelector('.purchase-item-foto__preview');
        const img = zone.querySelector('.purchase-item-foto__img');
        const name = zone.querySelector('.purchase-item-foto__name');

        revokePreviewUrl(zone);

        if (input) {
            input.value = '';
        }

        if (existingPathInput) {
            existingPathInput.value = '';
        }

        if (img) {
            img.removeAttribute('src');
        }

        if (name) {
            name.textContent = '';
        }

        if (placeholder) {
            placeholder.hidden = false;
        }

        if (preview) {
            preview.hidden = true;
        }

        zone.classList.remove('has-file', 'is-dragover');
    }

    function assignPhotoFile(zone, file) {
        if (!file || !file.type.startsWith('image/')) {
            return;
        }

        const input = zone.querySelector('.purchase-item-foto__input');

        if (!input) {
            return;
        }

        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        input.files = dataTransfer.files;
        showPhotoPreview(zone, file);
    }

    function bindPhotoZone(zone) {
        if (!zone || zone.dataset.photoBound === 'true') {
            return;
        }

        zone.dataset.photoBound = 'true';

        const input = zone.querySelector('.purchase-item-foto__input');

        input?.addEventListener('change', function () {
            const file = input.files?.[0];

            if (file) {
                assignPhotoFile(zone, file);
            } else {
                clearPhotoPreview(zone);
            }
        });

        zone.addEventListener('click', function (event) {
            if (event.target.closest('.purchase-item-foto__clear')) {
                return;
            }

            input?.click();
        });

        zone.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                input?.click();
            }
        });

        zone.querySelector('.purchase-item-foto__clear')?.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            clearPhotoPreview(zone);
        });

        ['dragenter', 'dragover'].forEach(function (eventName) {
            zone.addEventListener(eventName, function (event) {
                event.preventDefault();
                zone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach(function (eventName) {
            zone.addEventListener(eventName, function (event) {
                event.preventDefault();
                zone.classList.remove('is-dragover');
            });
        });

        zone.addEventListener('drop', function (event) {
            const file = event.dataTransfer?.files?.[0];
            assignPhotoFile(zone, file);
        });
    }

    function bindRemoveRow(row) {
        const removeBtn = row.querySelector('[data-remove-item]');

        if (!removeBtn) {
            return;
        }

        removeBtn.addEventListener('click', function () {
            const rows = itemsContainer.querySelectorAll('[data-purchase-item-row]');

            if (rows.length <= 1) {
                return;
            }

            row.querySelectorAll('[data-purchase-item-foto]').forEach(revokePreviewUrl);
            row.remove();
        });
    }

    function createFotoCell(index) {
        return `
            <td class="purchase-item-foto-cell">
                <div class="purchase-item-foto" data-purchase-item-foto role="button" tabindex="0" title="Subir foto del producto (opcional)">
                    <input type="hidden" name="items[${index}][existing_foto_path]" value="" class="purchase-item-foto__existing-path">
                    <input type="file" name="items[${index}][foto]" class="purchase-item-foto__input" accept="image/jpeg,image/png,image/webp,image/gif">
                    <div class="purchase-item-foto__placeholder">
                        <span class="purchase-item-foto__icon" aria-hidden="true">📷</span>
                        <span class="purchase-item-foto__hint">Subir foto</span>
                    </div>
                    <div class="purchase-item-foto__preview" hidden>
                        <img src="" alt="Vista previa" class="purchase-item-foto__img">
                        <span class="purchase-item-foto__name"></span>
                        <button type="button" class="purchase-item-foto__clear" aria-label="Quitar foto">&times;</button>
                    </div>
                </div>
            </td>
        `;
    }

    function createItemRow(index) {
        const row = document.createElement('tr');
        row.dataset.purchaseItemRow = 'true';
        row.innerHTML = `
            <td>
                <input type="number" name="items[${index}][cantidad]" class="supply-input" min="1" value="1" required>
            </td>
            ${createFotoCell(index)}
            <td>
                <input type="text" name="items[${index}][descripcion]" class="supply-input" placeholder="Descripcion del producto" required>
            </td>
            <td>
                <input type="text" name="items[${index}][referencia]" class="supply-input" placeholder="Referencia / codigo" required>
            </td>
            <td>
                <input type="text" name="items[${index}][utilizacion]" class="supply-input" placeholder="Uso previsto" required>
            </td>
            <td>
                <input type="text" name="items[${index}][ubicacion]" class="supply-input" placeholder="Ubicacion / sede" required>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn--secondary btn--sm" data-remove-item>Quitar</button>
            </td>
        `;

        bindRemoveRow(row);
        row.querySelectorAll('[data-purchase-item-foto]').forEach(bindPhotoZone);

        return row;
    }

    addItemBtn.addEventListener('click', function () {
        itemsContainer.appendChild(createItemRow(itemIndex));
        itemIndex++;
    });

    itemsContainer.querySelectorAll('[data-purchase-item-row]').forEach(function (row) {
        bindRemoveRow(row);
        row.querySelectorAll('[data-purchase-item-foto]').forEach(bindPhotoZone);
    });

    if (solicitudParaSelect) {
        solicitudParaSelect.addEventListener('change', toggleClienteFields);
        toggleClienteFields();
    }

    form.addEventListener('submit', function (event) {
        if (itemsContainer.querySelectorAll('[data-purchase-item-row]').length === 0) {
            event.preventDefault();
        }
    });
});
