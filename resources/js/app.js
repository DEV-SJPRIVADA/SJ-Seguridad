import './bootstrap';
import Alpine from 'alpinejs';
import searchableSelect from './components/searchable-select';
import appSidebar from './components/app-sidebar';

window.Alpine = Alpine;
Alpine.data('searchableSelect', searchableSelect);
Alpine.data('appSidebar', appSidebar);
Alpine.start();
