@php
    $label = $label ?? 'Matrices';
    $tabs = $tabs ?? [];
@endphp

<div class="module-strip module-subnav">
    <div class="app-container">
        <div class="module-strip__inner module-subnav__inner">
            <p class="text-caption module-subnav__label">{{ $label }}</p>
            <nav class="module-tabs" aria-label="Matrices dinámicas">
                @foreach ($tabs as $tab)
                    <a href="{{ $tab['url'] }}" class="module-tab {{ ! empty($tab['active']) ? 'module-tab--active' : '' }}">
                        {{ $tab['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>
    </div>
</div>
