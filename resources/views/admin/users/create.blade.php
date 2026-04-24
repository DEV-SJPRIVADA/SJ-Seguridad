<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="eyebrow">Accesos de Usuarios</p>
            <h2 class="page-title title-spaced">Crear usuario</h2>
        </div>
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                @include('admin.users.partials.form', [
                    'action' => route('admin.users.store'),
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
