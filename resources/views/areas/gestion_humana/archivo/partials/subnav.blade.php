<div class="module-subnav requisition-subtabs">
    <div class="app-container">
        <div class="module-subnav__inner requisition-subtabs__inner">
            <p class="text-caption module-subnav__label">Archivo</p>
            <nav class="module-tabs" aria-label="Archivo">
                @foreach ($subTabs as $tab)
                    <a href="{{ $tab['url'] }}" class="module-tab {{ $tab['active'] ? 'module-tab--active' : '' }}">
                        {{ $tab['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>
    </div>
</div>
