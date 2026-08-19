@php
    $user = auth()->user();
    $user?->loadMissing('roles');
    $roleName = $user?->roles->first()?->name;
    $roleLabel = $roleName ? ucwords(str_replace('-', ' ', $roleName)) : 'Sin rol';
    $areaLabel = $user?->areaLabel() ?: 'Sin area asignada';
@endphp

@if ($user)
    <div class="app-sidebar-footer">
        <div class="app-sidebar-footer__identity">
            <p class="app-sidebar-footer__name">{{ $user->name }}</p>
            <p class="app-sidebar-footer__meta">{{ $roleLabel }}</p>
            <p class="app-sidebar-footer__meta">{{ $areaLabel }}</p>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="app-sidebar-footer__logout-form">
            @csrf
            <button type="submit" class="btn btn--secondary btn--sm app-sidebar-footer__logout">
                Cerrar sesion
            </button>
        </form>

        <div class="app-sidebar-footer__brand">
            <x-application-logo class="app-sidebar-footer__logo" />
            <p class="app-sidebar-footer__product">{{ config('app.name') }}</p>
        </div>
    </div>
@endif
