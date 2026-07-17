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

            @if (session('permission_warnings'))
                <div class="notice notice--warning bottom-spaced">
                    <p class="text-small font-bold">Avisos de permisos</p>
                    <ul class="text-small" style="margin: 0.5rem 0 0 1rem;">
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
