<x-app-layout>
    <x-slot name="header">
        <div class="app-container">
            <div class="panel">
                <div class="panel__body panel__body--soft">
                    <div class="action-row">
                        <div>
                            <p class="eyebrow">Accesos de Usuarios</p>
                            <h2 class="page-title title-spaced">Crear usuario</h2>
                            <p class="page-subtitle">Define datos base, rol y permisos iniciales del nuevo usuario.</p>
                        </div>

                        <div class="form-actions__group">
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
            <div class="panel">
                @include('admin.users.partials.form', [
                    'action' => route('admin.users.store'),
                    'areas' => $areas,
                    'sites' => $sites,
                    'allSites' => $allSites,
                    'roles' => $roles,
                    'permissionForm' => $permissionForm,
                    'buttonLabel' => 'Guardar usuario',
                    'method' => 'POST',
                    'selectedPermissions' => old('permissions', []),
                    'selectedRole' => old('role'),
                    'user' => null,
                ])
            </div>
        </div>
    </div>
</x-app-layout>
