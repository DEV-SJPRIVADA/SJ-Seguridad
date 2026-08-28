document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('user-permissions-form');
    if (! form) {
        return;
    }

    const config = window.userPermissionsFormConfig ?? {};
    const areaLabels = config.areaLabels ?? {};
    const initialTab = config.initialTab ?? 'section-user';
    const initialNavKey = config.initialNavKey ?? '_global';
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
    const areaNavItems = form.querySelectorAll('.js-perm-area-nav');
    const areaPanels = form.querySelectorAll('.js-perm-area-panel');

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

    function activateAreaPanel(areaKey) {
        areaNavItems.forEach((item) => {
            const isActive = item.getAttribute('data-area-key') === areaKey;
            item.classList.toggle('is-active', isActive);
            item.setAttribute('aria-current', isActive ? 'true' : 'false');
        });

        areaPanels.forEach((panel) => {
            const isActive = panel.getAttribute('data-area-key') === areaKey;
            panel.classList.toggle('is-active', isActive);
            panel.hidden = ! isActive;
        });
    }

    areaNavItems.forEach((item) => {
        item.addEventListener('click', () => {
            activateAreaPanel(item.getAttribute('data-area-key'));
        });
    });

    function refreshAssignedAreaLabel() {
        if (! areaKeySelect || ! assignedAreaLabel) {
            return;
        }

        const key = areaKeySelect.value;
        assignedAreaLabel.textContent = key ? (areaLabels[key] || key) : 'Sin area fija';
    }

    areaKeySelect?.addEventListener('change', () => {
        refreshAssignedAreaLabel();

        if (areaKeySelect.value && form.querySelector('.js-perm-area-nav[data-area-key="_assigned"]')) {
            activateAreaPanel('_assigned');
        }
    });
    refreshAssignedAreaLabel();

    function updateSubgroupCount(subgroup) {
        const countEl = subgroup.querySelector('.js-perm-subgroup-count');
        if (! countEl) {
            return;
        }

        const boxes = subgroup.querySelectorAll('.js-permission-checkbox');
        const checked = Array.from(boxes).filter((cb) => cb.checked).length;
        countEl.textContent = checked > 0 ? `${checked}/${boxes.length}` : `${boxes.length}`;
    }

    function updateNavBadge(navItem) {
        const badge = navItem.querySelector('.js-perm-nav-badge');
        if (! badge) {
            return;
        }

        const areaKey = navItem.getAttribute('data-area-key');
        const panel = form.querySelector(`.js-perm-area-panel[data-area-key="${areaKey}"]`);
        const total = parseInt(badge.getAttribute('data-total') || '0', 10);
        const checked = panel?.querySelectorAll('.js-permission-checkbox:checked').length ?? 0;
        badge.textContent = `${checked}/${total}`;
    }

    function updatePanelCount(panel) {
        const countEl = panel.querySelector('.js-perm-panel-count');
        if (! countEl) {
            return;
        }

        const total = parseInt(countEl.getAttribute('data-total') || '0', 10);
        const checked = panel.querySelectorAll('.js-permission-checkbox:checked').length;
        countEl.textContent = `${checked}/${total} activos`;
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

        areaNavItems.forEach(updateNavBadge);
        areaPanels.forEach(updatePanelCount);
        form.querySelectorAll('.js-permission-group, .js-permission-area').forEach(updateSubgroupCount);
        updatePreview();
    }

    form.addEventListener('change', (event) => {
        if (event.target.classList.contains('js-permission-checkbox')) {
            const permValue = event.target.value;
            const isChecked = event.target.checked;
            form.querySelectorAll(`.js-permission-checkbox[value="${CSS.escape(permValue)}"]`).forEach((cb) => {
                if (cb !== event.target) {
                    cb.checked = isChecked;
                }
            });
            refreshPermissionUi();
        } else if (event.target.name === 'role') {
            refreshPermissionUi();
        }
    });

    form.querySelectorAll('.js-perm-bulk').forEach((button) => {
        button.addEventListener('click', () => {
            const panel = button.closest('.js-perm-area-panel');
            if (! panel) {
                return;
            }

            const enable = button.getAttribute('data-action') === 'enable';
            panel.querySelectorAll('.js-permission-checkbox').forEach((checkbox) => {
                checkbox.checked = enable;
            });

            refreshPermissionUi();
        });
    });

    function applySearch(query) {
        let firstMatchKey = null;

        areaPanels.forEach((panel) => {
            let hasVisibleItem = false;

            panel.querySelectorAll('.js-permission-item').forEach((item) => {
                const itemSearch = item.getAttribute('data-search') || '';
                const match = query === '' || itemSearch.includes(query);
                item.style.display = match ? '' : 'none';
                if (match) {
                    hasVisibleItem = true;
                }
            });

            panel.querySelectorAll('.js-permission-group, .js-permission-area').forEach((group) => {
                const groupSearch = group.getAttribute('data-search') || '';
                const visibleItems = Array.from(group.querySelectorAll('.js-permission-item'))
                    .some((item) => item.style.display !== 'none');

                group.style.display = (query === '' || groupSearch.includes(query) || visibleItems) ? '' : 'none';
                if (group.style.display !== 'none') {
                    hasVisibleItem = true;
                }
            });

            const panelSearch = panel.getAttribute('data-search') || '';
            const panelMatches = query === '' || panelSearch.includes(query) || hasVisibleItem;
            const areaKey = panel.getAttribute('data-area-key');

            areaNavItems.forEach((navItem) => {
                if (navItem.getAttribute('data-area-key') !== areaKey) {
                    return;
                }

                navItem.classList.toggle('has-search-match', query !== '' && panelMatches);
                navItem.style.display = panelMatches ? '' : 'none';
            });

            if (query !== '' && panelMatches && firstMatchKey === null) {
                firstMatchKey = areaKey;
            }
        });

        if (query !== '' && firstMatchKey !== null) {
            activateAreaPanel(firstMatchKey);
        }

        if (query === '') {
            areaNavItems.forEach((navItem) => {
                navItem.style.display = '';
                navItem.classList.remove('has-search-match');
            });
        }
    }

    searchInput?.addEventListener('input', () => {
        applySearch(searchInput.value.trim().toLowerCase());
    });

    advancedToggle?.addEventListener('change', () => {
        form.classList.toggle('user-form--advanced', advancedToggle.checked);
        localStorage.setItem('userPermissionsAdvanced', advancedToggle.checked ? '1' : '0');
    });

    if (advancedToggle && localStorage.getItem('userPermissionsAdvanced') === '1') {
        advancedToggle.checked = true;
        form.classList.add('user-form--advanced');
    }

    if (areaNavItems.length > 0) {
        const hasInitial = Array.from(areaNavItems).some(
            (item) => item.getAttribute('data-area-key') === initialNavKey,
        );
        activateAreaPanel(hasInitial ? initialNavKey : areaNavItems[0].getAttribute('data-area-key'));
    }

    refreshPermissionUi();
    activateTab(initialTab);

    document.getElementById('validation-error-summary')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
});
