<div class="requisition-subtabs bottom-spaced">
    <div class="requisition-subtabs__inner">
        <nav class="module-tabs">
            @foreach ($subTabs as $tab)
                <a href="{{ $tab['url'] }}" class="module-tab {{ $tab['active'] ? 'module-tab--active' : '' }}">
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </nav>
    </div>
</div>
