import './bootstrap';
import Alpine from 'alpinejs';
import searchableSelect from './components/searchable-select';
import appSidebar from './components/app-sidebar';
import confirmDeleteModal from './confirm-delete';
import fichaTerminateNovedades from './components/ficha-terminate-novedades';

window.Alpine = Alpine;
Alpine.data('searchableSelect', searchableSelect);
Alpine.data('appSidebar', appSidebar);
Alpine.data('confirmDeleteModal', confirmDeleteModal);
// Aviso en vivo del cruce con novedades MT-GH-04 al desvincular en Ficha.
Alpine.data('fichaTerminateNovedades', fichaTerminateNovedades);
Alpine.start();
