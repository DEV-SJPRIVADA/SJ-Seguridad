<x-app-layout>
    <x-slot name="header">
        <div class="app-container">
            <div class="admin-user-edit__header action-row">
                <div>
                    <p class="eyebrow">Accesos de usuarios</p>
                    <h2 class="panel-title panel-title--page">Editar usuario</h2>
                    <p class="panel-text admin-user-edit__meta">
                        {{ $user->name }} · {{ $user->email }}
                        · {{ $user->areaLabel() ?: 'Sin area base' }}
                        @if ($user->sede)
                            · {{ $user->sede->utilization }} ({{ $user->sede->city }})
                        @else
                            · Sin sede asignada
                        @endif
                    </p>
                </div>

                <div class="form-actions__group admin-user-edit__actions">
                    <span class="status-pill {{ $user->is_active ? 'status-pill--success' : 'status-pill--danger' }}">
                        {{ $user->is_active ? 'Activo' : 'Bloqueado' }}
                    </span>
                    <span class="status-pill status-pill--muted">
                        {{ $selectedRole ?: 'Sin rol' }}
                    </span>
                    <a href="{{ route('admin.users.index', ['selected' => $user->id]) }}" class="btn btn--secondary btn--sm">
                        Volver al listado
                    </a>
                    <button type="submit" form="user-permissions-form" class="btn btn--primary btn--sm">
                        Actualizar usuario
                    </button>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="page-section admin-user-edit">
        <div class="app-container">
            @if (session('status') === 'user-updated')
                <div class="notice notice--success bottom-spaced">
                    Usuario actualizado correctamente.
                </div>
            @endif

            @if (session('permission_warnings'))
                <div class="notice notice--warning bottom-spaced">
                    <p class="text-small font-bold">Avisos de permisos</p>
                    <ul class="text-small user-form__error-list">
                        @foreach (session('permission_warnings', []) as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="panel">
                @include('admin.users.partials.form', [
                    'action' => route('admin.users.update', $user),
                    'areas' => $areas,
                    'sites' => $sites,
                    'allSites' => $allSites,
                    'roles' => $roles,
                    'permissionForm' => $permissionForm,
                    'buttonLabel' => 'Actualizar usuario',
                    'method' => 'PATCH',
                    'selectedPermissions' => old('permissions', $selectedPermissions),
                    'selectedRole' => old('role', $selectedRole),
                    'user' => $user,
                ])
            </div>
        </div>
    </div>
</x-app-layout>
