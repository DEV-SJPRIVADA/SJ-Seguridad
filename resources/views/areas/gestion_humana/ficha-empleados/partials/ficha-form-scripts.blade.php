<script>
    document.addEventListener('DOMContentLoaded', function () {
        var salaryInput = document.getElementById('salary');
        if (!salaryInput) {
            return;
        }

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
