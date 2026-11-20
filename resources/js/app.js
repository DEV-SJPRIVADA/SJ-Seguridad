import './bootstrap';
import Alpine from 'alpinejs';
import searchableSelect from './components/searchable-select';
import appSidebar from './components/app-sidebar';
import confirmDeleteModal from './confirm-delete';
import fichaTerminateNovedades from './components/ficha-terminate-novedades';
import matrixBuilder from './components/matrix-builder';
import matrixSignaturePad from './components/matrix-signature-pad';

window.Alpine = Alpine;
Alpine.data('searchableSelect', searchableSelect);
Alpine.data('appSidebar', appSidebar);
Alpine.data('confirmDeleteModal', confirmDeleteModal);
// Aviso en vivo del cruce con novedades MT-GH-04 al desvincular en Ficha.
Alpine.data('fichaTerminateNovedades', fichaTerminateNovedades);
// Constructor visual y firma de las matrices dinámicas.
Alpine.data('matrixBuilder', matrixBuilder);
Alpine.data('matrixSignaturePad', matrixSignaturePad);
Alpine.start();
