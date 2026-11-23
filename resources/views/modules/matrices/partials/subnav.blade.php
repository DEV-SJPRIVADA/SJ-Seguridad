{{--
    Subnavegación del módulo Matrices dinámicas.

    Las pestañas se desplazan horizontalmente con flechas cuando hay varias
    matrices asignadas, igual que la franja de tableros del layout.
--}}
@php
    $label = $label ?? 'Matrices';
    $tabs = $tabs ?? [];
@endphp

<div class="module-strip module-subnav">
    <div class="app-container">
        <div class="module-subnav__inner module-subnav__inner--scroll">
            <p class="text-caption module-subnav__label">{{ $label }}</p>
            <div class="module-tabs-scroller matrices-subnav__scroller" data-module-tabs-scroller>
                <button
                    type="button"
                    class="module-tabs-scroller__btn"
                    data-tabs-scroll="prev"
                    title="Anterior"
                    aria-label="Ver matrices anteriores"
                >
                    <x-lucide-chevron-left width="16" height="16" aria-hidden="true" />
                </button>
                <nav class="module-tabs module-tabs--scroll module-tabs-scroller__track" aria-label="Matrices dinámicas">
                    @foreach ($tabs as $tab)
                        <a href="{{ $tab['url'] }}" class="module-tab {{ ! empty($tab['active']) ? 'module-tab--active' : '' }}">
                            {{ $tab['label'] }}
                        </a>
                    @endforeach
                </nav>
                <button
                    type="button"
                    class="module-tabs-scroller__btn"
                    data-tabs-scroll="next"
                    title="Siguiente"
                    aria-label="Ver matrices siguientes"
                >
                    <x-lucide-chevron-right width="16" height="16" aria-hidden="true" />
                </button>
            </div>
        </div>
    </div>
</div>
