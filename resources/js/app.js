import './bootstrap';
import Alpine from 'alpinejs';
import searchableSelect from './components/searchable-select';

window.Alpine = Alpine;
Alpine.data('searchableSelect', searchableSelect);
Alpine.start();
