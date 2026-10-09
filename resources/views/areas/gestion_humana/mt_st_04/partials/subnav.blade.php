{{-- Subnavegación Dashboard | Matriz del tablero MT-ST-04 --}}
<div class="module-subnav requisition-subtabs">
    <div class="app-container">
        <div class="module-subnav__inner requisition-subtabs__inner">
            <p class="text-caption module-subnav__label">MT-ST-04</p>
            <nav class="module-tabs" aria-label="MT-ST-04">
                @foreach ($subTabs as $tab)
                    <a href="{{ $tab['url'] }}" class="module-tab {{ $tab['active'] ? 'module-tab--active' : '' }}">
                        {{ $tab['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>
    </div>
</div>
