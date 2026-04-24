import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

document.querySelectorAll('[data-permission-filter-root]').forEach((root) => {
    const search = root.querySelector('[data-permission-search]');
    const moduleSelect = root.querySelector('[data-permission-module]');
    const clear = root.querySelector('[data-permission-clear]');
    const tableWrap = root.parentElement?.querySelector('.permission-table-wrap');

    if (!search || !moduleSelect || !clear || !tableWrap) {
        return;
    }

    const sections = Array.from(tableWrap.querySelectorAll('[data-permission-section]'));

    const applyFilters = () => {
        const query = search.value.trim().toLowerCase();
        const moduleValue = moduleSelect.value;

        sections.forEach((section) => {
            const sectionModule = section.getAttribute('data-module-key') ?? '';
            const sectionLabel = section.getAttribute('data-module-label') ?? '';
            const rows = Array.from(section.querySelectorAll('[data-permission-row]'));
            const moduleMatches = moduleValue === '' || moduleValue === sectionModule;
            const queryMatchesModule = query === '' || sectionLabel.includes(query);

            let visibleRows = 0;

            rows.forEach((row) => {
                const rowSearch = row.getAttribute('data-search') ?? '';
                const rowMatches = query === '' || queryMatchesModule || rowSearch.includes(query);
                const visible = moduleMatches && rowMatches;

                row.style.display = visible ? '' : 'none';

                if (visible) {
                    visibleRows += 1;
                }
            });

            section.style.display = visibleRows > 0 ? '' : 'none';
        });
    };

    search.addEventListener('input', applyFilters);
    moduleSelect.addEventListener('change', applyFilters);
    clear.addEventListener('click', () => {
        search.value = '';
        moduleSelect.value = '';
        applyFilters();
    });

    applyFilters();
});
