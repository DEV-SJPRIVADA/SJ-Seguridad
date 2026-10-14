{{-- Subnavegación Generar | Histórico del tablero Inasistencias --}}
<div class="module-subnav requisition-subtabs">
    <div class="app-container">
        <div class="module-subnav__inner requisition-subtabs__inner">
            <p class="text-caption module-subnav__label">Inasistencias</p>
            <nav class="module-tabs" aria-label="Inasistencias">
                @foreach ($subTabs as $tab)
                    <a href="{{ $tab['url'] }}" class="module-tab {{ $tab['active'] ? 'module-tab--active' : '' }}">
                        {{ $tab['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>
    </div>
</div>
