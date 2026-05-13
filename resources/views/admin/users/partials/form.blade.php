<form method="POST" action="{{ $action }}" class="panel__body" style="padding: 0;">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="split-view">
        <!-- Sidebar de Navegación -->
        <aside class="split-view__sidebar">
            <div class="split-nav-item split-nav-item--active" data-target="section-general">
                <i>👤</i> Datos Generales
            </div>

            <div class="split-nav-item" data-target="section-areas">
                <i>📍</i> Alcance por Áreas
            </div>
            
            <div class="split-nav-item" data-target="section-functional">
                <i>🛠️</i> Funcionalidades
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

        <!-- Contenido de Secciones -->
        <main class="split-view__content">
            
            <!-- SECCIÓN 1: DATOS GENERALES -->
            <div id="section-general" class="split-section">
                <div class="section-header">
                    <h3 class="section-header__title">Datos Generales</h3>
                    <p class="section-header__desc">Información básica del usuario y rol principal en el sistema.</p>
                </div>

                <div class="form-grid form-grid--two">
                    <div class="form-field">
                        <label class="form-label">Nombre completo</label>
                        <input name="name" type="text" class="form-input" value="{{ old('name', $user?->name) }}" required>
                    </div>
                    <div class="form-field">
                        <label class="form-label">Correo electrónico</label>
                        <input name="email" type="email" class="form-input" value="{{ old('email', $user?->email) }}" required>
                    </div>
                    <div class="form-field">
                        <label class="form-label">Área base</label>
                        <select name="area_key" class="form-select">
                            <option value="">Sin área fija</option>
                            @foreach ($areas as $key => $label)
                                <option value="{{ $key }}" @selected(old('area_key', $user?->area_key) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
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
                        <label class="form-label">{{ $user ? 'Nueva Contraseña (opcional)' : 'Contraseña Temporal' }}</label>
                        <input name="password" type="password" class="form-input" @required(!$user)>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2: ALCANCE POR ÁREAS -->
            <div id="section-areas" class="split-section" style="display: none;">
                
                {{-- Selector de Áreas (Grid) --}}
                <div id="area-selector-container">
                    <div class="section-header" style="display: flex; justify-content: space-between; align-items: flex-end;">
                        <div>
                            <h3 class="section-header__title">Alcance Geográfico</h3>
                            <p class="section-header__desc">Selecciona un área para configurar sus permisos de tablero.</p>
                        </div>
                        <div style="width: 250px;">
                            <input type="text" id="search-areas" class="form-input" style="min-height: 38px; padding: 0.5rem 1rem;" placeholder="🔍 Buscar área...">
                        </div>
                    </div>

                    <div class="areas-grid" style="grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));" id="areas-grid-list">
                        @foreach ($permissionGroups['areas'] as $area)
                            <div class="parameter-card js-area-card" data-area="{{ $area['key'] }}" data-name="{{ $area['label'] }}" onclick="openAreaDetail('{{ $area['key'] }}')">
                                <div style="background: var(--brand-blue-pale); width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <i>📍</i>
                                </div>
                                <h4>{{ $area['label'] }}</h4>
                                <span class="item-count">{{ $area['options']->count() }} opciones</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Detalle de Área Seleccionada --}}
                <div id="area-detail-container" style="display: none;">
                    <div class="back-to-grid" onclick="closeAreaDetail()" style="margin-bottom: 1.5rem; cursor: pointer;">
                        <i>⬅️</i> Volver al listado de áreas
                    </div>
                    
                    <div class="section-header">
                        <h3 class="section-header__title" id="selected-area-name">Nombre del Área</h3>
                        <p class="section-header__desc">Configura los accesos específicos para esta ubicación.</p>
                    </div>

                    <div class="switch-group">
                        @foreach ($permissionGroups['areas'] as $area)
                            <div id="options-area-{{ $area['key'] }}" class="js-area-options-group" style="display: none;">
                                @foreach ($area['options'] as $opt)
                                    <div class="switch-item">
                                        <div class="switch-item__info">
                                            <span class="switch-item__title">{{ $opt['label'] }}</span>
                                            <span class="switch-item__desc">{{ $opt['name'] }}</span>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="permissions[]" value="{{ $opt['name'] }}" @checked(in_array($opt['name'], $selectedPermissions, true))>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 3: PERMISOS FUNCIONALES -->
            <div id="section-functional" class="split-section" style="display: none;">
                
                {{-- Selector de Módulos (Grid) --}}
                <div id="functional-selector-container">
                    <div class="section-header" style="display: flex; justify-content: space-between; align-items: flex-end;">
                        <div>
                            <h3 class="section-header__title">Capacidades del Sistema</h3>
                            <p class="section-header__desc">Selecciona un módulo para configurar sus funciones específicas.</p>
                        </div>
                        <div style="width: 250px;">
                            <input type="text" id="search-functional" class="form-input" style="min-height: 38px; padding: 0.5rem 1rem;" placeholder="🔍 Buscar función...">
                        </div>
                    </div>

                    <div class="areas-grid" style="grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));" id="functional-grid-list">
                        @foreach ($permissionGroups['functional'] as $category => $perms)
                            <div class="parameter-card js-func-card" data-name="{{ $category }}" onclick="openFuncDetail('{{ Str::slug($category) }}', '{{ $category }}')">
                                <div style="background: var(--brand-blue-pale); width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <i>🛠️</i>
                                </div>
                                <h4>{{ $category }}</h4>
                                <span class="item-count">{{ count($perms) }} permisos</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Detalle de Funcionalidad Seleccionada --}}
                <div id="functional-detail-container" style="display: none;">
                    <div class="back-to-grid" onclick="closeFuncDetail()" style="margin-bottom: 1.5rem; cursor: pointer;">
                        <i>⬅️</i> Volver a módulos
                    </div>
                    
                    <div class="section-header">
                        <h3 class="section-header__title" id="selected-func-name">Módulo</h3>
                        <p class="section-header__desc">Habilita o deshabilita las funciones globales de este módulo.</p>
                    </div>

                    <div class="switch-group">
                        @foreach ($permissionGroups['functional'] as $category => $perms)
                            <div id="options-func-{{ Str::slug($category) }}" class="js-func-options-group" style="display: none;">
                                @foreach ($perms as $perm)
                                    <div class="switch-item">
                                        <div class="switch-item__info">
                                            <span class="switch-item__title">{{ $perm['label'] }}</span>
                                            <span class="switch-item__desc">{{ $perm['name'] }}</span>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="permissions[]" value="{{ $perm['name'] }}" @checked(in_array($perm['name'], $selectedPermissions, true))>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- Acciones Finales -->
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Navegación Principal (Split View) ---
        const navItems = document.querySelectorAll('.split-nav-item');
        const sections = document.querySelectorAll('.split-section');

        navItems.forEach(item => {
            item.addEventListener('click', function() {
                const target = this.getAttribute('data-target');
                navItems.forEach(i => i.classList.remove('split-nav-item--active'));
                this.classList.add('split-nav-item--active');
                sections.forEach(s => s.style.display = 'none');
                document.getElementById(target).style.display = 'block';
            });
        });

        // --- Gestión de Áreas (Cards + Detalle) ---
        window.openAreaDetail = function(key) {
            const name = document.querySelector(`.js-area-card[data-area="${key}"]`).getAttribute('data-name');
            document.getElementById('selected-area-name').innerText = name;
            document.getElementById('area-selector-container').style.display = 'none';
            document.getElementById('area-detail-container').style.display = 'block';
            
            document.querySelectorAll('.js-area-options-group').forEach(el => el.style.display = 'none');
            document.getElementById(`options-area-${key}`).style.display = 'block';
        }

        window.closeAreaDetail = function() {
            document.getElementById('area-detail-container').style.display = 'none';
            document.getElementById('area-selector-container').style.display = 'block';
        }

        // --- Gestión de Funcionalidades (Cards + Detalle) ---
        window.openFuncDetail = function(slug, name) {
            document.getElementById('selected-func-name').innerText = name;
            document.getElementById('functional-selector-container').style.display = 'none';
            document.getElementById('functional-detail-container').style.display = 'block';
            
            document.querySelectorAll('.js-func-options-group').forEach(el => el.style.display = 'none');
            document.getElementById(`options-func-${slug}`).style.display = 'block';
        }

        window.closeFuncDetail = function() {
            document.getElementById('functional-detail-container').style.display = 'none';
            document.getElementById('functional-selector-container').style.display = 'block';
        }

        // --- Filtros de Búsqueda ---
        document.getElementById('search-areas').addEventListener('input', function(e) {
            const q = e.target.value.toLowerCase();
            document.querySelectorAll('.js-area-card').forEach(card => {
                const name = card.getAttribute('data-name').toLowerCase();
                card.style.display = name.includes(q) ? 'flex' : 'none';
            });
        });

        document.getElementById('search-functional').addEventListener('input', function(e) {
            const q = e.target.value.toLowerCase();
            document.querySelectorAll('.js-func-card').forEach(card => {
                const name = card.getAttribute('data-name').toLowerCase();
                card.style.display = name.includes(q) ? 'flex' : 'none';
            });
        });
    });
</script>
