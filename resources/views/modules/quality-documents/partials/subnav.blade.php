<div class="module-strip module-subnav">
    <div class="app-container">
        <div class="module-strip__inner module-subnav__inner">
            <p class="text-caption module-subnav__label">Documentos</p>
            <nav class="module-tabs" aria-label="Documentos">
                @foreach ($subTabs as $tab)
                    <a href="{{ $tab['url'] }}" class="module-tab {{ $tab['active'] ? 'module-tab--active' : '' }}">
                        {{ $tab['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>
    </div>
</div>
