<script>
    /**
     * Validación visible de los formularios de Ficha empleados.
     *
     * Los formularios usan `novalidate` porque los selectores buscables requeridos
     * incluyen un input proxy invisible: con la validación nativa, el navegador
     * enfocaba ese proxy y bloqueaba el envío sin mostrar ningún mensaje al usuario.
     *
     * Devuelve { messages, firstField } para que el componente Alpine muestre la lista
     * de campos faltantes, resalte el campo y haga scroll al primero con error.
     */
    window.fichaEmpleadosValidateForm = function (form) {
        if (! form) {
            return { messages: [], firstField: null };
        }

        form.querySelectorAll('.ficha-empleados-form__field--invalid').forEach(function (el) {
            el.classList.remove('ficha-empleados-form__field--invalid');
        });

        var messages = [];
        var firstField = null;

        function resolveLabel(el) {
            var field = el.closest('.form-field');
            var labelEl = field ? field.querySelector('.form-label') : null;
            var raw = labelEl
                ? labelEl.textContent
                : (el.getAttribute('aria-label') || el.getAttribute('name') || 'Campo');

            return String(raw).replace(/\*/g, '').replace(/\s+/g, ' ').trim() || 'Campo';
        }

        form.querySelectorAll('input, select, textarea').forEach(function (el) {
            var field = el.closest('.form-field');
            var invalid = false;

            if (el.dataset.ssProxy === '1') {
                // Select buscable requerido: el valor real vive en el input hidden.
                if (! el.required) {
                    return;
                }

                var wrap = el.closest('.searchable-select-wrap');
                var hidden = wrap ? wrap.querySelector('input[type="hidden"][name]') : null;
                invalid = ! hidden || String(hidden.value || '').trim() === '';
            } else {
                // willValidate excluye hidden, disabled y readonly.
                if (! el.willValidate || el.checkValidity()) {
                    return;
                }

                invalid = true;
            }

            if (! invalid) {
                return;
            }

            var reason = 'es obligatorio';

            if (el.dataset.ssProxy !== '1' && ! el.validity.valueMissing) {
                reason = (el.validity.typeMismatch || el.validity.patternMismatch)
                    ? 'tiene un formato no válido'
                    : (el.validationMessage || 'no es válido');
            }

            var text = resolveLabel(el) + ': ' + reason;

            if (messages.indexOf(text) === -1) {
                messages.push(text);
            }

            if (field) {
                field.classList.add('ficha-empleados-form__field--invalid');
                firstField = firstField || field;
            }
        });

        return { messages: messages, firstField: firstField };
    };

    document.addEventListener('DOMContentLoaded', function () {
        var salaryInput = document.getElementById('salary');
        var birthDateInput = document.getElementById('birth_date');
        var ageInput = document.getElementById('payroll_extra_age');

        if (salaryInput) {
            var currencyFormatter = new Intl.NumberFormat('es-CO', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            });

            function parseCurrency(value) {
                var raw = String(value || '').trim();
                if (raw === '') {
                    return '';
                }

                var plainNumeric = raw.replace(/,/g, '');
                if (/^-?\d+(\.\d+)?$/.test(plainNumeric)) {
                    return String(Math.round(parseFloat(plainNumeric)));
                }

                var digits = raw.replace(/[^\d]/g, '');
                return digits === '' ? '' : digits;
            }

            function formatCurrency(value) {
                var parsed = parseCurrency(value);
                if (parsed === '') {
                    return '';
                }

                return currencyFormatter.format(Number(parsed));
            }

            salaryInput.value = formatCurrency(salaryInput.dataset.initialValue || salaryInput.value);

            salaryInput.addEventListener('focus', function () {
                if (salaryInput.readOnly || salaryInput.disabled) {
                    return;
                }

                salaryInput.value = parseCurrency(salaryInput.value);
            });

            salaryInput.addEventListener('blur', function () {
                if (salaryInput.readOnly || salaryInput.disabled) {
                    return;
                }

                salaryInput.value = formatCurrency(salaryInput.value);
            });

            var form = document.getElementById('ficha-empleados-form');
            if (form) {
                form.addEventListener('submit', function () {
                    salaryInput.value = parseCurrency(salaryInput.value);
                });
            }
        }

        if (birthDateInput && ageInput) {
            function calculateAgeFromBirthDate(isoDate) {
                if (!isoDate) {
                    return '';
                }

                var parts = String(isoDate).split('-');
                if (parts.length !== 3) {
                    return '';
                }

                var year = parseInt(parts[0], 10);
                var month = parseInt(parts[1], 10);
                var day = parseInt(parts[2], 10);
                if (!year || !month || !day) {
                    return '';
                }

                var birth = new Date(year, month - 1, day);
                if (
                    birth.getFullYear() !== year
                    || birth.getMonth() !== month - 1
                    || birth.getDate() !== day
                ) {
                    return '';
                }

                var today = new Date();
                var age = today.getFullYear() - birth.getFullYear();
                var monthDiff = today.getMonth() - birth.getMonth();
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                    age -= 1;
                }

                if (age < 0 || age > 120) {
                    return '';
                }

                return String(age);
            }

            function syncAgeFromBirthDate() {
                ageInput.value = calculateAgeFromBirthDate(birthDateInput.value);
            }

            birthDateInput.addEventListener('change', syncAgeFromBirthDate);
            birthDateInput.addEventListener('input', syncAgeFromBirthDate);

            if (birthDateInput.value) {
                syncAgeFromBirthDate();
            }
        }
    });
</script>
