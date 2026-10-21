{{-- Pestañas del tablero Catálogos. Mismo chrome que el resto de subnavs. --}}
<div class="module-strip module-subnav">
    <div class="app-container">
        <div class="module-strip__inner module-subnav__inner">
            <p class="text-caption module-subnav__label">Catálogos</p>
            <nav class="module-tabs" aria-label="Catálogos">
                @foreach ($subTabs as $tab)
                    <a href="{{ $tab['url'] }}" class="module-tab {{ $tab['active'] ? 'module-tab--active' : '' }}">
                        {{ $tab['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>
    </div>
</div>
