<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="eyebrow">Accesos de Usuarios</p>
            <h2 class="page-title title-spaced">Editar usuario</h2>
            <p class="page-subtitle">{{ $user->name }} · {{ $user->email }}</p>
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
