document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('user-permissions-form');
    if (! form) {
        return;
    }

    const config = window.userPermissionsFormConfig ?? {};
    const areaLabels = config.areaLabels ?? {};
    const initialTab = config.initialTab ?? 'section-user';
    const navItems = form.querySelectorAll('.js-user-form-tab');
    const sections = form.querySelectorAll('.user-form__section');
    const areaKeySelect = document.getElementById('user-area-key');
    const assignedAreaLabel = document.getElementById('assigned-area-label');
    const searchInput = document.getElementById('search-permissions');
    const roleSelect = form.querySelector('select[name="role"]');
    const previewTags = document.getElementById('permission-preview-tags');
    const previewRole = document.getElementById('permission-preview-role');
    const previewCount = document.getElementById('permission-preview-count');
    const previewEmpty = document.getElementById('permission-preview-empty');
    const advancedToggle = document.getElementById('toggle-advanced-permissions');
    const filterChips = form.querySelectorAll('.js-perm-filter-chip');

    function activateTab(targetId) {
        navItems.forEach((item) => {
            const isActive = item.getAttribute('data-target') === targetId;
            item.classList.toggle('module-tab--active', isActive);
            item.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        sections.forEach((section) => {
            section.hidden = section.id !== targetId;
        });

        if (targetId === 'section-capabilities') {
            searchInput?.focus({ preventScroll: true });
        }
    }

    navItems.forEach((item) => {
        item.addEventListener('click', () => {
            activateTab(item.getAttribute('data-target'));
        });
    });

    function refreshAssignedAreaLabel() {
        if (! areaKeySelect || ! assignedAreaLabel) {
            return;
        }

        const key = areaKeySelect.value;
        assignedAreaLabel.textContent = key ? (areaLabels[key] || key) : 'Sin area fija';
    }

    areaKeySelect?.addEventListener('change', refreshAssignedAreaLabel);
    refreshAssignedAreaLabel();

    form.querySelectorAll('.js-perm-accordion-toggle').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const item = toggle.closest('.js-perm-accordion');
            const isOpen = item.classList.contains('is-open');
            item.classList.toggle('is-open', ! isOpen);
            toggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        });
    });

    function updateSubgroupCount(subgroup) {
        const countEl = subgroup.querySelector('.js-perm-subgroup-count');
        if (! countEl) {
            return;
        }

        const boxes = subgroup.querySelectorAll('.js-permission-checkbox');
        const checked = Array.from(boxes).filter((cb) => cb.checked).length;
        countEl.textContent = checked > 0 ? `${checked}/${boxes.length}` : `${boxes.length}`;
    }

    function updateSectionBadge(accordion) {
        const badge = accordion.querySelector('.js-perm-badge');
        if (! badge) {
            return;
        }

        const total = parseInt(badge.getAttribute('data-total') || '0', 10);
        const checked = accordion.querySelectorAll('.js-permission-checkbox:checked').length;
        badge.textContent = `${checked}/${total}`;
    }

    function updatePreview() {
        const checkedBoxes = Array.from(form.querySelectorAll('.js-permission-checkbox:checked'));
        const labels = checkedBoxes.map((checkbox) => {
            const item = checkbox.closest('.js-permission-item');
            return item?.querySelector('.switch-item__title')?.textContent?.trim() || checkbox.value;
        });

        if (previewRole && roleSelect) {
            const roleLabel = roleSelect.options[roleSelect.selectedIndex]?.textContent?.trim() || 'Sin rol';
            previewRole.textContent = roleLabel;
        }

        if (previewCount) {
            previewCount.textContent = `${checkedBoxes.length} permiso${checkedBoxes.length === 1 ? '' : 's'} adicional${checkedBoxes.length === 1 ? '' : 'es'}`;
        }

        if (previewTags) {
            previewTags.innerHTML = '';
            labels.forEach((label) => {
                const li = document.createElement('li');
                li.className = 'user-permission-tag';
                li.textContent = label;
                previewTags.appendChild(li);
            });
        }

        if (previewEmpty) {
            previewEmpty.hidden = labels.length > 0;
        }
    }

    function refreshPermissionUi() {
        form.querySelectorAll('.js-permission-item').forEach((item) => {
            const checkbox = item.querySelector('.js-permission-checkbox');
            item.classList.toggle('switch-item--active', checkbox?.checked ?? false);
        });

        form.querySelectorAll('.js-perm-accordion').forEach(updateSectionBadge);
        form.querySelectorAll('.js-permission-group, .js-permission-area').forEach(updateSubgroupCount);
        updatePreview();
    }

    form.addEventListener('change', (event) => {
        if (event.target.classList.contains('js-permission-checkbox') || event.target.name === 'role') {
            refreshPermissionUi();
        }
    });

    form.querySelectorAll('.js-perm-bulk').forEach((button) => {
        button.addEventListener('click', () => {
            const accordion = button.closest('.js-perm-accordion');
            if (! accordion) {
                return;
            }

            const enable = button.getAttribute('data-action') === 'enable';
            accordion.querySelectorAll('.js-permission-checkbox').forEach((checkbox) => {
                checkbox.checked = enable;
            });

            refreshPermissionUi();
        });
    });

    function applySearch(query) {
        form.querySelectorAll('.js-perm-accordion').forEach((accordion) => {
            const sectionVisible = query === '' || (accordion.getAttribute('data-search') || '').includes(query);
            let hasVisibleItem = false;

            accordion.querySelectorAll('.js-permission-item').forEach((item) => {
                const itemSearch = item.getAttribute('data-search') || '';
                const match = query === '' || itemSearch.includes(query);
                item.style.display = match ? '' : 'none';
                if (match) {
                    hasVisibleItem = true;
                }
            });

            accordion.querySelectorAll('.js-permission-group, .js-permission-area').forEach((group) => {
                const groupSearch = group.getAttribute('data-search') || '';
                const visibleItems = Array.from(group.querySelectorAll('.js-permission-item'))
                    .some((item) => item.style.display !== 'none');

                if (query !== '' && (groupSearch.includes(query) || visibleItems)) {
                    group.open = true;
                }

                group.style.display = (query === '' || groupSearch.includes(query) || visibleItems) ? '' : 'none';
                if (group.style.display !== 'none') {
                    hasVisibleItem = true;
                }
            });

            if (query !== '' && hasVisibleItem) {
                accordion.classList.add('is-open');
                accordion.querySelector('.js-perm-accordion-toggle')?.setAttribute('aria-expanded', 'true');
            }

            accordion.style.display = (sectionVisible || hasVisibleItem) ? '' : 'none';
        });
    }

    searchInput?.addEventListener('input', () => {
        applySearch(searchInput.value.trim().toLowerCase());
    });

    filterChips.forEach((chip) => {
        chip.addEventListener('click', () => {
            const filter = chip.getAttribute('data-filter') || 'all';

            filterChips.forEach((item) => {
                item.classList.toggle('module-tab--active', item === chip);
            });

            form.querySelectorAll('.js-perm-accordion').forEach((accordion) => {
                const section = accordion.getAttribute('data-section') || '';
                const visible = filter === 'all' || section === filter;
                accordion.style.display = visible ? '' : 'none';
            });

            if (searchInput?.value.trim()) {
                applySearch(searchInput.value.trim().toLowerCase());
            }
        });
    });

    advancedToggle?.addEventListener('change', () => {
        form.classList.toggle('user-form--advanced', advancedToggle.checked);
        localStorage.setItem('userPermissionsAdvanced', advancedToggle.checked ? '1' : '0');
    });

    if (advancedToggle && localStorage.getItem('userPermissionsAdvanced') === '1') {
        advancedToggle.checked = true;
        form.classList.add('user-form--advanced');
    }

    refreshPermissionUi();
    activateTab(initialTab);

    document.getElementById('validation-error-summary')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
});
