@php
    $tabs = $permissionForm['tabs'] ?? [];
    $help = $permissionForm['help'] ?? [];
    $sections = $permissionForm['sections'] ?? [];
    $selectedPermissions = $selectedPermissions ?? [];
    $areaLabels = $areas ?? [];
@endphp

<form method="POST" action="{{ $action }}" class="panel__body" style="padding: 0;" id="user-permissions-form">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="split-view">
        <aside class="split-view__sidebar">
            <div class="split-nav-item split-nav-item--active" data-target="section-user">
                <i>👤</i> {{ $tabs['user'] ?? 'Usuario' }}
            </div>
            <div class="split-nav-item" data-target="section-capabilities">
                <i>🛠️</i> {{ $tabs['capabilities'] ?? 'Que puede hacer' }}
            </div>

            <div style="margin-top: auto; padding: 1rem; background: #fff; border-radius: 12px; border: 1px solid var(--color-border);">
                <p class="text-caption" style="margin-bottom: 0.5rem;">Estado de Cuenta</p>
                <div class="form-field">
                    <label class="checkbox-card" style="padding: 0.5rem; border: none; background: transparent;">
                        <input type="checkbox" name="is_active" value="1" class="form-check" @checked(old('is_active', $user?->is_active ?? true))>
                        <span class="text-small font-bold">Usuario Activo</span>
                    </label>
                    <label class="checkbox-card" style="padding: 0.5rem; border: none; background: transparent; margin-top: -0.5rem;">
                        <input type="checkbox" name="must_change_password" value="1" class="form-check" @checked(old('must_change_password', $user?->must_change_password ?? true))>
                        <span class="text-small font-bold">Forzar Password</span>
                    </label>
                </div>
            </div>
        </aside>

        <main class="split-view__content">
            <div id="section-user" class="split-section">
                <div class="section-header">
                    <h3 class="section-header__title">Datos Generales</h3>
                    <p class="section-header__desc">Informacion basica del usuario y su area operativa.</p>
                </div>

                <div class="form-grid form-grid--two">
                    <div class="form-field">
                        <label class="form-label">Nombre completo</label>
                        <input name="name" type="text" class="form-input" value="{{ old('name', $user?->name) }}" required>
                    </div>
                    <div class="form-field">
                        <label class="form-label">Correo electronico</label>
                        <input name="email" type="email" class="form-input" value="{{ old('email', $user?->email) }}" required>
                    </div>
                    <div class="form-field">
                        <label class="form-label">Area base</label>
                        <select name="area_key" id="user-area-key" class="form-select">
                            <option value="">Sin area fija</option>
                            @foreach ($areas as $key => $label)
                                <option value="{{ $key }}" @selected(old('area_key', $user?->area_key) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="text-small text-muted">{{ $help['area_key'] ?? '' }}</p>
                    </div>
                    <div class="form-field">
                        <label class="form-label">Sede fisica</label>
                        <div style="display: flex; gap: 0.5rem; align-items: stretch;">
                            <select name="sede_id" id="user-sede-id" class="form-select" style="flex: 1;">
                                <option value="">Sin sede asignada</option>
                                @foreach ($sites ?? [] as $site)
                                    <option value="{{ $site->id }}" @selected((string) old('sede_id', $user?->sede_id) === (string) $site->id)>
                                        {{ $site->utilization }} ({{ $site->city }})
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn--secondary" id="open-sites-modal" title="Gestionar sedes">
                                Gestionar
                            </button>
                        </div>
                        <p class="text-small text-muted">Requerida para solicitar insumos.</p>
                    </div>
                    <div class="form-field">
                        <label class="form-label">Perfil / Rol Principal</label>
                        <select name="role" class="form-select" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}" @selected($selectedRole === $role->name)>{{ ucfirst($role->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field">
                        <label class="form-label">{{ $user ? 'Nueva Contrasena (opcional)' : 'Contrasena Temporal' }}</label>
                        <input name="password" type="password" class="form-input" @required(!$user)>
                    </div>
                </div>
            </div>

            <div id="section-capabilities" class="split-section" style="display: none;">
                <div class="section-header" style="display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem;">
                    <div>
                        <h3 class="section-header__title">{{ $tabs['capabilities'] ?? 'Que puede hacer' }}</h3>
                        <p class="section-header__desc">{{ $help['capabilities_intro'] ?? '' }}</p>
                    </div>
                    <div class="perm-search-bar" style="width: 280px;">
                        <input type="text" id="search-permissions" class="form-input" style="min-height: 38px; padding: 0.5rem 1rem;" placeholder="Buscar permiso...">
                    </div>
                </div>

                @include('admin.users.partials.permission-sections', [
                    'sections' => $sections,
                    'selectedPermissions' => $selectedPermissions,
                    'help' => $help,
                ])
            </div>
        </main>
    </div>

    <div class="panel__body" style="background: #f8fafc; border-top: 1px solid var(--color-border); border-bottom-left-radius: var(--radius-xl); border-bottom-right-radius: var(--radius-xl);">
        <div class="form-actions" style="border: none; padding: 0;">
            <p class="text-small text-muted">
                Los permisos marcados se suman a las capacidades base del rol seleccionado.
            </p>
            <div class="form-actions__group">
                <a href="{{ route('admin.users.index') }}" class="btn btn--secondary">Cancelar</a>
                <button type="submit" class="btn btn--primary">{{ $buttonLabel }}</button>
            </div>
        </div>
    </div>
</form>

@include('admin.users.partials.sites-modal')

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('user-permissions-form');
        const navItems = document.querySelectorAll('.split-nav-item');
        const sections = document.querySelectorAll('.split-section');
        const areaKeySelect = document.getElementById('user-area-key');
        const assignedAreaLabel = document.getElementById('assigned-area-label');
        const searchInput = document.getElementById('search-permissions');
        const areaLabels = @json($areaLabels);

        navItems.forEach(item => {
            item.addEventListener('click', function() {
                const target = this.getAttribute('data-target');
                navItems.forEach(i => i.classList.remove('split-nav-item--active'));
                this.classList.add('split-nav-item--active');
                sections.forEach(s => s.style.display = 'none');
                document.getElementById(target).style.display = 'block';
            });
        });

        function refreshAssignedAreaLabel() {
            const key = areaKeySelect.value;
            if (assignedAreaLabel) {
                assignedAreaLabel.textContent = key ? (areaLabels[key] || key) : 'Sin area fija';
            }
        }

        areaKeySelect.addEventListener('change', refreshAssignedAreaLabel);
        refreshAssignedAreaLabel();

        form.querySelectorAll('.js-perm-accordion-toggle').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const item = this.closest('.js-perm-accordion');
                const isOpen = item.classList.contains('is-open');
                item.classList.toggle('is-open', ! isOpen);
                this.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            });
        });

        function updateSubgroupCount(subgroup) {
            const countEl = subgroup.querySelector('.js-perm-subgroup-count');
            if (! countEl) {
                return;
            }

            const boxes = subgroup.querySelectorAll('.js-permission-checkbox');
            const checked = Array.from(boxes).filter(cb => cb.checked).length;
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

        function refreshPermissionUi() {
            form.querySelectorAll('.js-permission-item').forEach(item => {
                const checkbox = item.querySelector('.js-permission-checkbox');
                item.classList.toggle('switch-item--active', checkbox?.checked ?? false);
            });

            form.querySelectorAll('.js-perm-accordion').forEach(updateSectionBadge);
            form.querySelectorAll('.js-permission-group, .js-permission-area').forEach(updateSubgroupCount);
        }

        form.addEventListener('change', function(event) {
            if (event.target.classList.contains('js-permission-checkbox')) {
                refreshPermissionUi();
            }
        });

        searchInput.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();

            form.querySelectorAll('.js-perm-accordion').forEach(accordion => {
                let sectionVisible = query === '' || (accordion.getAttribute('data-search') || '').includes(query);
                let hasVisibleItem = false;

                accordion.querySelectorAll('.js-permission-item').forEach(item => {
                    const itemSearch = item.getAttribute('data-search') || '';
                    const match = query === '' || itemSearch.includes(query);
                    item.style.display = match ? '' : 'none';
                    if (match) {
                        hasVisibleItem = true;
                    }
                });

                accordion.querySelectorAll('.js-permission-group, .js-permission-area').forEach(group => {
                    const groupSearch = group.getAttribute('data-search') || '';
                    const visibleItems = Array.from(group.querySelectorAll('.js-permission-item'))
                        .some(item => item.style.display !== 'none');

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
        });

        refreshPermissionUi();
    });
</script>
