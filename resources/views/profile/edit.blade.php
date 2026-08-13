<x-app-layout>
    <x-slot name="header">
        <div class="app-container app-container--narrow">
            <p class="eyebrow">Cuenta</p>
            <h2 class="page-title">Mi perfil</h2>
        </div>
    </x-slot>

    @php
        $initials = collect(explode(' ', $user->name))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->join('');
        $roleName = $user->roles->first()?->name;
        $roleLabel = $roleName ? ucwords(str_replace('-', ' ', $roleName)) : 'Sin rol';
    @endphp

    <div class="page-section profile-page">
        <div class="app-container app-container--narrow page-stack">
            @if (session('status') === 'temporary-password')
                <div class="notice notice--warning">
                    Debes actualizar tu contrasena temporal antes de continuar usando el sistema.
                </div>
            @endif

            <div class="panel profile-hero">
                <div class="profile-hero__inner">
                    <div class="profile-hero__avatar" aria-hidden="true">{{ $initials ?: '?' }}</div>
                    <div class="profile-hero__body">
                        <h3 class="profile-hero__name">{{ $user->name }}</h3>
                        <p class="profile-hero__email">{{ $user->email }}</p>
                        <div class="profile-hero__meta">
                            <span class="status-pill status-pill--info">{{ $roleLabel }}</span>
                            @if ($user->areaLabel())
                                <span class="status-pill status-pill--muted">{{ $user->areaLabel() }}</span>
                            @endif
                            @if ($user->site)
                                <span class="status-pill status-pill--muted">
                                    {{ $user->site->utilization }} ({{ $user->site->city }})
                                </span>
                            @endif
                            <span class="status-pill {{ $user->is_active ? 'status-pill--success' : 'status-pill--danger' }}">
                                {{ $user->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

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
                <div class="panel profile-card profile-card--danger">
                    <div class="profile-card__inner">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
