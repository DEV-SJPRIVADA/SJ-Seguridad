<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
            jQuery('.ficha-empleados-form .js-ficha-select').select2({
                width: '100%',
                placeholder: 'Seleccionar…',
                allowClear: true,
                language: {
                    noResults: function () {
                        return 'Sin resultados';
                    },
                    searching: function () {
                        return 'Buscando…';
                    }
                }
            });
        }

        var salaryInput = document.getElementById('salary');
        if (!salaryInput) {
            return;
        }

        var currencyFormatter = new Intl.NumberFormat('es-CO', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        });

        function parseCurrency(value) {
            var digits = String(value || '').replace(/[^\d]/g, '');
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
            salaryInput.value = parseCurrency(salaryInput.value);
        });

        salaryInput.addEventListener('blur', function () {
            salaryInput.value = formatCurrency(salaryInput.value);
        });

        var form = document.getElementById('ficha-empleados-form');
        if (form) {
            form.addEventListener('submit', function () {
                salaryInput.value = parseCurrency(salaryInput.value);
            });
        }
    });
</script>
