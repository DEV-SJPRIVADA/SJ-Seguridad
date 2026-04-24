<form method="POST" action="{{ $action }}" class="form-stack panel__body">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="form-layout">
        <section class="card card--muted">
            <div class="panel-divider">
                <p class="eyebrow">Administracion de usuario</p>
                <h3 class="panel-title title-spaced">Datos generales y control de acceso</h3>
            </div>

            <div class="form-grid form-grid--two block-spaced">
                <div class="form-field">
                    <x-input-label for="name" value="Nombre completo" />
                    <x-text-input id="name" name="name" type="text" :value="old('name', $user?->name)" required autofocus />
                    <x-input-error :messages="$errors->get('name')" />
                </div>

                <div class="form-field">
                    <x-input-label for="email" value="Correo" />
                    <x-text-input id="email" name="email" type="email" :value="old('email', $user?->email)" required />
                    <x-input-error :messages="$errors->get('email')" />
                </div>

                <div class="form-field">
                    <x-input-label for="password" :value="$user ? 'Nueva contrasena (opcional)' : 'Contrasena temporal'" />
                    <x-text-input id="password" name="password" type="password" :required="! $user" />
                    <x-input-error :messages="$errors->get('password')" />
                </div>

                @if ($user)
                    <div class="form-field">
                        <x-input-label for="password_confirmation" value="Confirmar contrasena" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" />
                    </div>
                @endif

                <div class="form-field">
                    <x-input-label for="role" value="Perfil / rol base" />
                    <select id="role" name="role" class="form-select" required>
                        <option value="">Selecciona un rol</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}" @selected($selectedRole === $role->name)>
                                {{ ucfirst($role->name) }}
                            </option>
                            @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role')" />
                </div>
            </div>
        </section>

        <aside class="card section-stack">
            <div>
                <p class="text-caption">Estado del usuario</p>
                <p class="text-small text-muted block-spaced-sm">Controla si puede ingresar y si debe cambiar su contrasena en el siguiente acceso.</p>
            </div>

            <label class="checkbox-card">
                <input type="checkbox" name="is_active" value="1" class="form-check" @checked(old('is_active', $user?->is_active ?? true))>
                <span>
                    <span class="checkbox-card__title">Usuario activo</span>
                    <span class="checkbox-card__text">Si se desactiva, no podra iniciar sesion ni operar modulos.</span>
                </span>
            </label>

            <label class="checkbox-card">
                <input type="checkbox" name="must_change_password" value="1" class="form-check" @checked(old('must_change_password', $user?->must_change_password ?? true))>
                <span>
                    <span class="checkbox-card__title">Forzar cambio inicial</span>
                    <span class="checkbox-card__text">Obliga a renovar la contrasena temporal en el primer ingreso.</span>
                </span>
            </label>

            <div class="card card--info">
                <span class="font-semibold">super-admin</span> conserva acceso total. Los roles <span class="font-semibold">administrador</span> y <span class="font-semibold">usuario</span> dependen de los permisos marcados en esta pantalla.
            </div>
        </aside>
    </div>

    <section class="panel">
        <div class="panel__header">
            <h3 class="panel-title">Matriz de permisos por area y tablero</h3>
            <p class="panel-text">Activa la visualizacion del modulo, su gestion y los tableros internos habilitados para cada usuario en formato tabular con filtros.</p>
        </div>
        <div class="panel__body">
            <div class="permission-filter-bar" data-permission-filter-root>
                <input type="search" class="form-input permission-filter-bar__search" placeholder="Buscar modulo, permiso o codigo" data-permission-search>
                <select class="form-select permission-filter-bar__select" data-permission-module>
                    <option value="">Todos los modulos</option>
                    <option value="{{ $permissionGroups['administration_module']['key'] }}">{{ $permissionGroups['administration_module']['area'] }}</option>
                    @foreach ($permissionGroups['area_permissions'] as $area)
                        <option value="{{ $area['key'] }}">{{ $area['area'] }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn--secondary" data-permission-clear>Limpiar</button>
            </div>

            <div class="data-table-wrap permission-table-wrap">
                <table class="data-table permission-table">
                    <thead>
                        <tr>
                            <th>Modulo / permiso</th>
                            <th class="table-center permission-table__checkbox-col">Asignar</th>
                        </tr>
                    </thead>

                    <tbody data-permission-section data-module-key="{{ $permissionGroups['administration_module']['key'] }}" data-module-label="{{ \Illuminate\Support\Str::lower($permissionGroups['administration_module']['area']) }}">
                        <tr class="permission-table__module-row">
                            <td colspan="2">00.- {{ \Illuminate\Support\Str::upper($permissionGroups['administration_module']['area']) }}</td>
                        </tr>
                        @foreach ($permissionGroups['administration_module']['rows'] as $row)
                            <tr data-permission-row data-module-key="{{ $permissionGroups['administration_module']['key'] }}" data-search="{{ \Illuminate\Support\Str::lower($permissionGroups['administration_module']['area'].' '.$row['label'].' '.$row['name']) }}">
                                <td class="permission-table__label">{{ $row['label'] }}</td>
                                <td class="table-center">
                                    <input type="checkbox" name="permissions[]" value="{{ $row['name'] }}" class="form-check" @checked(in_array($row['name'], $selectedPermissions, true))>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    @foreach ($permissionGroups['area_permissions'] as $index => $area)
                        <tbody data-permission-section data-module-key="{{ $area['key'] }}" data-module-label="{{ \Illuminate\Support\Str::lower($area['area']) }}">
                            <tr class="permission-table__module-row">
                                <td colspan="2">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}.- {{ \Illuminate\Support\Str::upper($area['area']) }}</td>
                            </tr>
                            @foreach ($area['rows'] as $row)
                                <tr data-permission-row data-module-key="{{ $area['key'] }}" data-search="{{ \Illuminate\Support\Str::lower($area['area'].' '.$row['label'].' '.$row['name']) }}">
                                    <td class="permission-table__label">{{ $row['label'] }}</td>
                                    <td class="table-center">
                                        <input type="checkbox" name="permissions[]" value="{{ $row['name'] }}" class="form-check" @checked(in_array($row['name'], $selectedPermissions, true))>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    @endforeach
                </table>
            </div>
        </div>
    </section>

    <div class="form-actions">
        <p class="text-small text-muted">
            El rol define una base minima. La visualizacion, la modificacion de modulos y los tableros disponibles se controlan desde la matriz de permisos.
        </p>

        <div class="form-actions__group">
            <a href="{{ route('admin.users.index') }}" class="btn btn--secondary">
                Volver
            </a>
            <x-primary-button>{{ $buttonLabel }}</x-primary-button>
        </div>
    </div>
</form>
