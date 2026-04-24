@props(['active'])

@php
$classes = ($active ?? false)
            ? 'nav-mobile-link nav-mobile-link--active'
            : 'nav-mobile-link';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
