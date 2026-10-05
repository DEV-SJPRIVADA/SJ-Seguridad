/**
 * Colapso de la barra Procesos (desktop).
 * Persiste en localStorage; en móvil el select sigue siendo el control principal.
 */
export default function appSidebar() {
    return {
        collapsed: false,

        init() {
            try {
                this.collapsed = window.localStorage.getItem('sj.sidebarCollapsed') === '1';
            } catch (e) {
                this.collapsed = false;
            }
        },

        toggle() {
            this.collapsed = !this.collapsed;

            try {
                window.localStorage.setItem('sj.sidebarCollapsed', this.collapsed ? '1' : '0');
            } catch (e) {
                // Sin persistencia si el almacenamiento no está disponible.
            }
        },
    };
}
