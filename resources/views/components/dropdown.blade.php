@props(['align' => 'right', 'width' => '48', 'contentClasses' => ''])

@php
$alignmentClasses = match ($align) {
    'left' => 'dropdown__panel--left',
    'top' => 'dropdown__panel--top',
    default => '',
};
@endphp

<div class="dropdown" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
    <div @click="open = ! open">
        {{ $trigger }}
    </div>

    <div x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="dropdown__panel {{ $alignmentClasses }} {{ $contentClasses }}"
            style="display: none;"
            @click="open = false">
        {{ $content }}
    </div>
</div>
