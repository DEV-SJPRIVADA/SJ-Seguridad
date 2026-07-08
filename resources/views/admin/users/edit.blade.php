<x-app-layout>
    <x-slot name="header">
        <div class="app-container">
            <div class="panel">
                <div class="panel__body panel__body--soft">
                    <div class="action-row">
                        <div>
                            <p class="eyebrow">Accesos de Usuarios</p>
                            <h2 class="page-title title-spaced">Editar usuario</h2>
                            <p class="page-subtitle">{{ $user->name }} | {{ $user->email }}</p>
                        </div>

                        <div class="form-actions__group">
                            <span class="status-pill {{ $user->is_active ? 'status-pill--success' : 'status-pill--danger' }}">
                                {{ $user->is_active ? 'Activo' : 'Bloqueado' }}
                            </span>
                            <span class="status-pill status-pill--muted">
                                {{ $selectedRole ?: 'Sin rol' }}
                            </span>
                            <a href="{{ route('admin.users.index') }}" class="btn btn--secondary">
                                Volver al listado
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            @if (session('status') === 'user-updated')
                <div class="notice notice--success bottom-spaced">
                    Usuario actualizado correctamente.
                </div>
            @endif

            <div class="panel">
                @include('admin.users.partials.form', [
                    'action' => route('admin.users.update', $user),
                    'areas' => $areas,
                    'sites' => $sites,
                    'allSites' => $allSites,
                    'roles' => $roles,
                    'permissionGroups' => $permissionGroups,
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
