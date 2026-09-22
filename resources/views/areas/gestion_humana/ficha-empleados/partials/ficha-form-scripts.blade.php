<script>
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
