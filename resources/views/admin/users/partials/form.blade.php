@php
    $tabs = $permissionForm['tabs'] ?? [];
    $help = $permissionForm['help'] ?? [];
    $sections = $permissionForm['sections'] ?? [];
    $selectedPermissions = $selectedPermissions ?? [];
    $areaLabels = $areas ?? [];
    $compactCreate = ($compactCreate ?? false) || $user === null;

    $initialTab = 'section-user';
    if ($errors->any()) {
        $hasUserFieldError = $errors->hasAny(['name', 'email', 'document_number', 'area_key', 'sede_id', 'role']);
        $hasSecurityFieldError = ! $compactCreate && $errors->has('password');
        $hasPermissionError = $errors->has('permissions') || collect($errors->keys())->contains(
            fn (string $key): bool => str_starts_with($key, 'permissions.')
        );

        if ($hasPermissionError && ! $hasUserFieldError && ! $hasSecurityFieldError) {
            $initialTab = 'section-capabilities';
        } elseif ($hasSecurityFieldError && ! $hasUserFieldError) {
            $initialTab = 'section-security';
        }
    }

    if (request()->query('tab') === 'capabilities') {
        $initialTab = 'section-capabilities';
    } elseif (request()->query('tab') === 'security' && ! $compactCreate) {
        $initialTab = 'section-security';
    }
@endphp

<form method="POST" action="{{ $action }}" class="user-form panel__body {{ $compactCreate ? 'user-form--create' : '' }}" id="user-permissions-form">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="user-form__tabs module-subnav">
        <div class="module-subnav__inner">
            <p class="text-caption module-subnav__label">{{ $compactCreate ? 'Nuevo usuario' : 'Edicion de usuario' }}</p>
            <nav class="module-tabs" aria-label="{{ $compactCreate ? 'Nuevo usuario' : 'Edicion de usuario' }}" role="tablist">
                <button
                    type="button"
                    role="tab"
                    class="module-tab js-user-form-tab {{ $initialTab === 'section-user' ? 'module-tab--active' : '' }}"
                    data-target="section-user"
                    aria-selected="{{ $initialTab === 'section-user' ? 'true' : 'false' }}"
                >
                    {{ $compactCreate ? 'Datos' : ($tabs['user'] ?? 'Identidad') }}
                </button>
                <button
                    type="button"
                    role="tab"
                    class="module-tab js-user-form-tab {{ $initialTab === 'section-capabilities' ? 'module-tab--active' : '' }}"
                    data-target="section-capabilities"
                    aria-selected="{{ $initialTab === 'section-capabilities' ? 'true' : 'false' }}"
                >
                    {{ $compactCreate ? 'Permisos' : ($tabs['capabilities'] ?? 'Acceso y permisos') }}
                </button>
                @unless ($compactCreate)
                    <button
                        type="button"
                        role="tab"
                        class="module-tab js-user-form-tab {{ $initialTab === 'section-security' ? 'module-tab--active' : '' }}"
                        data-target="section-security"
                        aria-selected="{{ $initialTab === 'section-security' ? 'true' : 'false' }}"
                    >
                        {{ $tabs['security'] ?? 'Seguridad' }}
                    </button>
                @endunless
            </nav>
        </div>
    </div>

    <div class="user-form__body">
        @if ($errors->any())
            <div id="validation-error-summary" class="notice notice--danger bottom-spaced" role="alert">
                <p class="text-small font-bold">Revisa los siguientes campos:</p>
                <ul class="text-small user-form__error-list">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div id="section-user" class="user-form__section" @if ($initialTab !== 'section-user') hidden @endif>
            @unless ($compactCreate)
                <div class="section-header">
                    <h3 class="section-header__title">Datos generales</h3>
                    <p class="section-header__desc">Informacion basica del usuario y su area operativa.</p>
                </div>
            @endunless

            <div class="form-grid {{ $compactCreate ? 'admin-user-create__grid' : 'form-grid--two' }}">
                <div class="form-field">
                    <label class="form-label">Nombre completo</label>
                    <input name="name" type="text" class="form-input @error('name') form-input--invalid @enderror" value="{{ old('name', $user?->name) }}" required>
                    <x-input-error :messages="$errors->get('name')" />
                </div>
                <div class="form-field">
                    <label class="form-label">Cedula</label>
                    <input name="document_number" type="text" class="form-input @error('document_number') form-input--invalid @enderror" value="{{ old('document_number', $user?->document_number) }}" maxlength="50" required>
                    <x-input-error :messages="$errors->get('document_number')" />
                    @if ($compactCreate)
                        <p class="text-small text-muted">Contrasena temporal y acceso inicial.</p>
                    @elseif (! $user)
                        <p class="text-small text-muted">Se usara como contrasena temporal al crear el usuario.</p>
                    @endif
                </div>
                <div class="form-field">
                    <label class="form-label">Correo electronico</label>
                    <input name="email" type="email" class="form-input @error('email') form-input--invalid @enderror" value="{{ old('email', $user?->email) }}" required>
                    <x-input-error :messages="$errors->get('email')" />
                    @if ($compactCreate)
                        <p class="text-small text-muted">Recibira credenciales por correo.</p>
                    @endif
                </div>
                <div class="form-field">
                    <label class="form-label">Perfil / Rol principal</label>
                    <select name="role" class="form-select @error('role') form-input--invalid @enderror" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}" @selected($selectedRole === $role->name)>{{ ucfirst($role->name) }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role')" />
                </div>
                <div class="form-field">
                    <label class="form-label">Area base</label>
                    <select name="area_key" id="user-area-key" class="form-select @error('area_key') form-input--invalid @enderror">
                        <option value="">Sin area fija</option>
                        @foreach ($areas as $key => $label)
                            <option value="{{ $key }}" @selected(old('area_key', $user?->area_key) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('area_key')" />
                    @unless ($compactCreate)
                        <p class="text-small text-muted">{{ $help['area_key'] ?? '' }}</p>
                    @endunless
                </div>
                <div class="form-field">
                    <label class="form-label">Sede fisica</label>
                    <div class="user-form__sede-row">
                        <select name="sede_id" id="user-sede-id" class="form-select @error('sede_id') form-input--invalid @enderror">
                            <option value="">Sin sede asignada</option>
                            @foreach ($sites ?? [] as $site)
                                <option value="{{ $site->id }}" @selected((string) old('sede_id', $user?->sede_id) === (string) $site->id)>
                                    {{ $site->utilization }} ({{ $site->city }})
                                </option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn--secondary btn--sm" id="open-sites-modal" title="Gestionar sedes">
                            Gestionar
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('sede_id')" />
                    @unless ($compactCreate)
                        <p class="text-small text-muted">Requerida para solicitar insumos.</p>
                    @endunless
                </div>
            </div>

            @if ($compactCreate)
                <div class="admin-user-create__security">
                    <label class="admin-user-create__toggle">
                        <input type="checkbox" name="is_active" value="1" class="form-check" @checked(old('is_active', true))>
                        <span>Usuario activo</span>
                    </label>
                    <label class="admin-user-create__toggle">
                        <input type="checkbox" name="must_change_password" value="1" class="form-check" @checked(old('must_change_password', true))>
                        <span>Forzar cambio de contrasena al ingresar</span>
                    </label>
                </div>
            @endif
        </div>

        <div id="section-capabilities" class="user-form__section" @if ($initialTab !== 'section-capabilities') hidden @endif>
            <div class="section-header section-header--split {{ $compactCreate ? 'section-header--compact' : '' }}">
                <div>
                    @unless ($compactCreate)
                        <h3 class="section-header__title">{{ $tabs['capabilities'] ?? 'Acceso y permisos' }}</h3>
                        <p class="section-header__desc">{{ $help['capabilities_intro'] ?? '' }}</p>
                    @else
                        <h3 class="section-header__title">Permisos iniciales</h3>
                        <p class="section-header__desc">Opcional. Se suman al rol seleccionado.</p>
                    @endunless
                </div>
                <div class="perm-search-bar">
                    <input type="text" id="search-permissions" class="form-input" placeholder="Buscar permiso...">
                </div>
            </div>

            <div class="user-permissions-toolbar">
                <div class="module-tabs user-permissions-filters" aria-label="Filtrar permisos">
                    <button type="button" class="module-tab module-tab--active js-perm-filter-chip" data-filter="all">Todos</button>
                    <button type="button" class="module-tab js-perm-filter-chip" data-filter="assigned">Su area</button>
                    <button type="button" class="module-tab js-perm-filter-chip" data-filter="global">Transversales</button>
                    <button type="button" class="module-tab js-perm-filter-chip" data-filter="other">Otras areas</button>
                </div>
                <label class="user-permissions-advanced">
                    <input type="checkbox" id="toggle-advanced-permissions" class="form-check">
                    <span class="text-small">Mostrar identificadores tecnicos</span>
                </label>
            </div>

            <div class="user-permissions-layout {{ $compactCreate ? 'user-permissions-layout--compact' : '' }}">
                <div class="user-permissions-layout__main">
                    @include('admin.users.partials.permission-sections', [
                        'sections' => $sections,
                        'selectedPermissions' => $selectedPermissions,
                        'help' => $help,
                    ])

                    <x-input-error :messages="$errors->get('permissions')" />
                    @if ($errors->has('permissions.*'))
                        <x-input-error :messages="collect($errors->getMessages())->filter(fn ($messages, $key) => str_starts_with($key, 'permissions.'))->flatten()->all()" />
                    @endif
                </div>

                @unless ($compactCreate)
                    @include('admin.users.partials.permission-preview', [
                        'selectedRole' => $selectedRole,
                    ])
                @endunless
            </div>
        </div>

        @unless ($compactCreate)
        <div id="section-security" class="user-form__section" @if ($initialTab !== 'section-security') hidden @endif>
            <div class="section-header">
                <h3 class="section-header__title">Seguridad de cuenta</h3>
                <p class="section-header__desc">Control de acceso, contrasena y estado operativo del usuario.</p>
            </div>

            <div class="form-grid form-grid--two">
                <div class="form-field">
                    <div class="card card--muted user-form__security-card">
                        <p class="text-caption">Estado de cuenta</p>
                        <label class="checkbox-card user-form__security-option">
                            <input type="checkbox" name="is_active" value="1" class="form-check" @checked(old('is_active', $user?->is_active ?? true))>
                            <span class="text-small font-bold">Usuario activo</span>
                        </label>
                        <label class="checkbox-card user-form__security-option">
                            <input type="checkbox" name="must_change_password" value="1" class="form-check" @checked(old('must_change_password', $user?->must_change_password ?? true))>
                            <span class="text-small font-bold">Forzar cambio de contrasena al ingresar</span>
                        </label>
                    </div>
                </div>
                @if ($user)
                    <div class="form-field">
                        <label class="form-label">Nueva contrasena (opcional)</label>
                        <x-password-input name="password" autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" />
                        <p class="text-small text-muted">Dejar vacio para mantener la contrasena actual.</p>
                    </div>
                @else
                    <div class="form-field">
                        <div class="card card--muted user-form__security-card">
                            <p class="text-caption">Contrasena inicial</p>
                            <p class="text-small">La contrasena temporal sera la <strong>cedula</strong> ingresada. El usuario recibira un correo de bienvenida con sus credenciales.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        @endunless
    </div>

    <div class="user-form__footer {{ $compactCreate ? 'user-form__footer--compact' : '' }}">
        <div class="form-actions user-form__footer-actions">
            @unless ($compactCreate)
                <p class="text-small text-muted">
                    Los permisos marcados se suman a las capacidades base del rol seleccionado.
                </p>
            @endunless
            <div class="form-actions__group">
                <a href="{{ route('admin.users.index') }}" class="btn btn--secondary btn--sm">Cancelar</a>
                @unless ($compactCreate)
                    <button type="submit" class="btn btn--primary btn--sm">{{ $buttonLabel }}</button>
                @endunless
            </div>
        </div>
    </div>
</form>

@include('admin.users.partials.sites-modal')

@push('scripts')
    <script>
        window.userPermissionsFormConfig = {
            areaLabels: @json($areaLabels),
            initialTab: @json($initialTab),
        };
    </script>
    @vite('resources/js/admin/user-permissions-form.js')
@endpush
