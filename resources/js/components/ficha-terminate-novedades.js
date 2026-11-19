/**
 * Aprobación del cruce con novedades MT-GH-04 en el modal de desvinculación.
 *
 * El servidor ya no bloquea la desvinculación individual: consulta las Vacaciones /
 * Incapacidades / Permisos solapadas con el retiro y, si existen, exige confirmación
 * con motivo (queda auditado quién aprobó).
 *
 * Este componente solo avisa en vivo al elegir fechas, para no obligar a enviar el
 * formulario para descubrir el cruce. La validación definitiva sigue en el backend.
 */
export default function fichaTerminateNovedades(config = {}) {
    return {
        url: config.url || '',
        hasConflicts: false,
        conflicts: [],
        acknowledged: Boolean(config.acknowledged),
        reason: config.reason || '',
        // Bloque de aprobación visible cuando el servidor ya reportó el cruce.
        approvalOpenOnLoad: Boolean(config.approvalOpenOnLoad),
        checking: false,
        timer: null,

        init() {
            const lastWorkDay = this.$root.querySelector('[name="last_work_day"]');
            const terminationDate = this.$root.querySelector('[name="termination_date"]');

            // Reconsulta cuando cambia cualquiera de las dos fechas del retiro.
            [lastWorkDay, terminationDate].forEach((input) => {
                if (! input) {
                    return;
                }

                input.addEventListener('change', () => this.scheduleCheck());
            });

            if (lastWorkDay?.value && terminationDate?.value) {
                this.scheduleCheck();
            }
        },

        scheduleCheck() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.check(), 250);
        },

        async check() {
            const lastWorkDay = this.$root.querySelector('[name="last_work_day"]')?.value || '';
            const terminationDate = this.$root.querySelector('[name="termination_date"]')?.value || '';

            if (! lastWorkDay || ! terminationDate) {
                this.hasConflicts = false;
                this.conflicts = [];

                return;
            }

            this.checking = true;

            try {
                const params = new URLSearchParams({
                    last_work_day: lastWorkDay,
                    termination_date: terminationDate,
                });

                const response = await fetch(`${this.url}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (! response.ok) {
                    return;
                }

                const data = await response.json();
                this.hasConflicts = Boolean(data.has_conflicts);
                this.conflicts = data.conflicts || [];

                // Si desaparece el cruce (el usuario movió las fechas), se limpia la aprobación.
                if (! this.hasConflicts) {
                    this.acknowledged = false;
                    this.reason = '';
                }
            } catch (error) {
                // Silencioso: el backend vuelve a validar al enviar.
            } finally {
                this.checking = false;
            }
        },
    };
}
