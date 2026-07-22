@props([
    'id',
    'icon',
    'title',
    'help' => '',
    'meta' => null,
    'open' => false,
    'search' => '',
    'badge' => 0,
    'total' => 0,
])

<div
    class="perm-accordion__item js-perm-accordion {{ $open ? 'is-open' : '' }}"
    data-search="{{ $search }}"
    id="perm-section-{{ $id }}"
>
    <button type="button" class="perm-accordion__header js-perm-accordion-toggle" aria-expanded="{{ $open ? 'true' : 'false' }}">
        <span class="perm-accordion__icon perm-accordion__icon--{{ $id }}">{{ $icon }}</span>
        <span class="perm-accordion__titles">
            <span class="perm-accordion__title">{{ $title }}</span>
            @if ($help !== '')
                <span class="perm-accordion__subtitle">{{ $help }}</span>
            @endif
            @if ($meta)
                <span class="perm-accordion__meta">{!! $meta !!}</span>
            @endif
        </span>
        <span class="perm-accordion__counts">
            <span class="perm-accordion__badge js-perm-badge" data-section="{{ $id }}" data-total="{{ $total }}">{{ $badge }}/{{ $total }}</span>
        </span>
        <span class="perm-accordion__chevron" aria-hidden="true"></span>
    </button>

    <div class="perm-accordion__body">
        <div class="perm-accordion__inner">
            {{ $slot }}
        </div>
    </div>
</div>
