<x-app-layout>
    <x-slot name="header">
        <h2 class="page-title">{{ __('Perfil') }}</h2>
    </x-slot>

    <div class="page-section page-section--spacious">
        <div class="app-container app-container--narrow page-stack">
            @if (session('status') === 'temporary-password')
                <div class="notice notice--warning">
                    Debes actualizar tu contrasena temporal antes de continuar usando el sistema.
                </div>
            @endif

            <div class="panel profile-card">
                <div class="profile-card__inner">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="panel profile-card">
                <div class="profile-card__inner">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            @if (auth()->user()?->hasRole('super-admin'))
                <div class="panel profile-card">
                    <div class="profile-card__inner">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
