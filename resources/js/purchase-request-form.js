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

            row.remove();
        });
    }

    function createItemRow(index) {
        const row = document.createElement('tr');
        row.dataset.purchaseItemRow = 'true';
        row.innerHTML = `
            <td>
                <input type="number" name="items[${index}][cantidad]" class="supply-input" min="1" value="1" required>
            </td>
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

        return row;
    }

    addItemBtn.addEventListener('click', function () {
        itemsContainer.appendChild(createItemRow(itemIndex));
        itemIndex++;
    });

    itemsContainer.querySelectorAll('[data-purchase-item-row]').forEach(bindRemoveRow);

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
