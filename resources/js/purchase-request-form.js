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

    function createFotoMarkup(index) {
        return `
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
        `;
    }

    // Crea una tarjeta de producto (foto + descripción, referencia, utilización/ubicación/cantidad).
    function createItemRow(index) {
        const row = document.createElement('article');
        row.className = 'purchase-item-card';
        row.dataset.purchaseItemRow = 'true';
        row.innerHTML = `
            <header class="purchase-item-card__head">
                <span class="purchase-item-card__title">Producto</span>
                <button type="button" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger" data-remove-item title="Quitar producto" aria-label="Quitar producto">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                </button>
            </header>
            <div class="purchase-item-card__top">
                <div class="form-field purchase-item-card__foto">
                    <span class="form-label">Foto <span class="text-muted">(opcional)</span></span>
                    ${createFotoMarkup(index)}
                </div>
                <div class="form-field purchase-item-card__desc">
                    <label class="form-label" for="item-descripcion-${index}">Descripción</label>
                    <textarea id="item-descripcion-${index}" name="items[${index}][descripcion]" class="form-textarea purchase-item-card__desc-input" rows="4" placeholder="Descripción del producto" required></textarea>
                </div>
            </div>
            <div class="form-field purchase-item-card__referencia">
                <label class="form-label" for="item-referencia-${index}">Referencia</label>
                <input type="text" id="item-referencia-${index}" name="items[${index}][referencia]" class="form-input" placeholder="Marca-Modelo / código" required>
            </div>
            <div class="purchase-item-card__bottom">
                <div class="form-field">
                    <label class="form-label" for="item-utilizacion-${index}">Utilización</label>
                    <input type="text" id="item-utilizacion-${index}" name="items[${index}][utilizacion]" class="form-input" placeholder="Para quién / qué uso" required>
                </div>
                <div class="form-field">
                    <label class="form-label" for="item-ubicacion-${index}">Ubicación</label>
                    <input type="text" id="item-ubicacion-${index}" name="items[${index}][ubicacion]" class="form-input" placeholder="Ubicación / sede" required>
                </div>
                <div class="form-field purchase-item-card__cantidad">
                    <label class="form-label" for="item-cantidad-${index}">Cantidad</label>
                    <input type="number" id="item-cantidad-${index}" name="items[${index}][cantidad]" class="form-input" min="1" value="1" required>
                </div>
            </div>
        `;

        bindRemoveRow(row);
        row.querySelectorAll('[data-purchase-item-foto]').forEach(bindPhotoZone);

        return row;
    }

    addItemBtn.addEventListener('click', function () {
        itemsContainer.appendChild(createItemRow(itemIndex));
        itemIndex++;
    });

    function setImportStatus(message, isError) {
        const status = document.getElementById('purchase-items-import-status');

        if (!status) {
            return;
        }

        if (!message) {
            status.hidden = true;
            status.textContent = '';
            status.classList.remove('alert', 'alert--danger', 'alert--success');

            return;
        }

        status.hidden = false;
        status.textContent = message;
        status.classList.add('alert');
        status.classList.toggle('alert--danger', Boolean(isError));
        status.classList.toggle('alert--success', !isError);
    }

    function fillRowValues(row, item) {
        const cantidad = row.querySelector('[name*="[cantidad]"]');
        const descripcion = row.querySelector('[name*="[descripcion]"]');
        const referencia = row.querySelector('[name*="[referencia]"]');
        const utilizacion = row.querySelector('[name*="[utilizacion]"]');
        const ubicacion = row.querySelector('[name*="[ubicacion]"]');

        if (cantidad) {
            cantidad.value = item.cantidad || 1;
        }
        if (descripcion) {
            descripcion.value = item.descripcion || '';
        }
        if (referencia) {
            referencia.value = item.referencia || '';
        }
        if (utilizacion) {
            utilizacion.value = item.utilizacion || '';
        }
        if (ubicacion) {
            ubicacion.value = item.ubicacion || '';
        }
    }

    function replaceItemsFromImport(items) {
        itemsContainer.querySelectorAll('[data-purchase-item-row]').forEach(function (row) {
            row.querySelectorAll('[data-purchase-item-foto]').forEach(revokePreviewUrl);
            row.remove();
        });

        itemIndex = 0;
        items.forEach(function (item) {
            const row = createItemRow(itemIndex);
            fillRowValues(row, item);
            itemsContainer.appendChild(row);
            itemIndex++;
        });
    }

    const importInput = document.getElementById('purchase-items-import-file');

    if (importInput) {
        importInput.addEventListener('change', function () {
            const file = importInput.files?.[0];
            const url = importInput.dataset.purchaseItemsImportUrl;

            if (!file || !url) {
                return;
            }

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const body = new FormData();
            body.append('import_file', file);

            setImportStatus('Cargando productos desde Excel…', false);
            importInput.disabled = true;

            fetch(url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: body,
                credentials: 'same-origin',
            })
                .then(async function (response) {
                    const payload = await response.json().catch(function () {
                        return {};
                    });

                    if (!response.ok) {
                        const message = payload.message
                            || payload.errors?.import_file?.[0]
                            || 'No se pudo importar el archivo.';
                        throw new Error(message);
                    }

                    if (!Array.isArray(payload.items) || payload.items.length === 0) {
                        throw new Error('El archivo no trajo productos validos.');
                    }

                    replaceItemsFromImport(payload.items);

                    let message = payload.message || ('Se cargaron ' + payload.items.length + ' producto(s).');
                    if (Array.isArray(payload.warnings) && payload.warnings.length > 0) {
                        message += ' ' + payload.warnings.slice(0, 3).join(' ');
                    }
                    setImportStatus(message, false);
                })
                .catch(function (error) {
                    setImportStatus(error.message || 'Error al importar.', true);
                })
                .finally(function () {
                    importInput.value = '';
                    importInput.disabled = false;
                });
        });
    }

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

    const attachmentsInput = document.getElementById('purchase-attachments');
    const attachmentsSelected = document.getElementById('purchase-attachments-selected');
    const attachmentsExisting = document.getElementById('purchase-attachments-existing');
    const attachmentsCount = document.getElementById('purchase-attachments-count');

    function formatFileSize(bytes) {
        if (!bytes || bytes < 1024) {
            return (bytes || 0) + ' B';
        }
        const kb = bytes / 1024;
        if (kb < 1024) {
            return kb.toFixed(1) + ' KB';
        }
        return (kb / 1024).toFixed(1) + ' MB';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    let selectedFilesTransfer = new DataTransfer();

    function updateSelectedAttachmentsUI() {
        if (!attachmentsSelected) {
            return;
        }

        attachmentsSelected.innerHTML = '';
        const files = Array.from(selectedFilesTransfer.files);

        if (attachmentsCount) {
            if (files.length === 0) {
                attachmentsCount.textContent = attachmentsExisting ? 'Sin archivos nuevos seleccionados' : 'Sin archivos seleccionados';
            } else if (files.length === 1) {
                attachmentsCount.textContent = '1 archivo seleccionado';
            } else {
                attachmentsCount.textContent = `${files.length} archivos seleccionados`;
            }
        }

        files.forEach(function (file, index) {
            const item = document.createElement('li');
            item.className = 'purchase-attachment-item';
            item.innerHTML = `
                <div class="purchase-attachment-item__info">
                    <span style="display:inline-flex; align-items:center;" aria-hidden="true">📎</span>
                    <span class="purchase-attachment-item__name">${escapeHtml(file.name)}</span>
                    <span class="purchase-attachment-item__size">(${formatFileSize(file.size)})</span>
                </div>
                <button type="button" class="btn btn--secondary btn--sm purchase-attachment-item__remove" data-remove-selected-index="${index}" title="Quitar archivo">
                    &times; Quitar
                </button>
            `;
            attachmentsSelected.appendChild(item);
        });
    }

    if (attachmentsInput && attachmentsSelected) {
        attachmentsInput.addEventListener('change', function () {
            selectedFilesTransfer = new DataTransfer();
            Array.from(attachmentsInput.files || []).forEach(function (file) {
                selectedFilesTransfer.items.add(file);
            });
            attachmentsInput.files = selectedFilesTransfer.files;
            updateSelectedAttachmentsUI();
        });

        attachmentsSelected.addEventListener('click', function (event) {
            const removeBtn = event.target.closest('[data-remove-selected-index]');
            if (!removeBtn) {
                return;
            }

            const removeIndex = parseInt(removeBtn.dataset.removeSelectedIndex, 10);
            const newTransfer = new DataTransfer();
            Array.from(selectedFilesTransfer.files).forEach(function (file, idx) {
                if (idx !== removeIndex) {
                    newTransfer.items.add(file);
                }
            });
            selectedFilesTransfer = newTransfer;
            attachmentsInput.files = selectedFilesTransfer.files;
            updateSelectedAttachmentsUI();
        });
    }

    attachmentsExisting?.addEventListener('click', function (event) {
        const button = event.target.closest('[data-remove-attachment]');

        if (!button) {
            return;
        }

        button.closest('[data-existing-attachment]')?.remove();
    });
});
