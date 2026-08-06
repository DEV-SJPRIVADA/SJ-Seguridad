<x-app-layout>
    <x-slot name="header">
        <div class="app-container">
            <div class="admin-user-create__header action-row action-row--compact">
                <div class="panel-heading-row panel-heading-row--wrap">
                    <div>
                        <p class="eyebrow">Accesos de usuarios</p>
                        <h2 class="panel-title panel-title--page">Crear usuario</h2>
                        <p class="panel-text">Datos base, rol y permisos. La contrasena temporal sera la cedula.</p>
                    </div>
                </div>
                <div class="form-actions__group">
                    <a href="{{ route('admin.users.index') }}" class="btn btn--secondary btn--sm">
                        Volver
                    </a>
                    <button type="submit" form="user-permissions-form" class="btn btn--primary btn--sm">
                        Guardar usuario
                    </button>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="page-section admin-user-create">
        <div class="app-container">
            <div class="panel panel--compact">
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
                    'compactCreate' => true,
                ])
            </div>
        </div>
    </div>
</x-app-layout>
