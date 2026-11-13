{{-- Firma manuscrita reutilizable: pasar :user o :src (data URI de un snapshot). Sin archivo, no pinta nada. --}}
@props([
    'user' => null,
    'src' => null,
    'height' => '48',
    'alt' => 'Firma',
])

@php
    $uri = $src ?? ($user instanceof \App\Models\User ? $user->signatureDataUri() : null);
@endphp

@if (filled($uri))
    <img
        src="{{ $uri }}"
        alt="{{ $alt }}"
        {{ $attributes->merge(['class' => 'user-signature']) }}
        style="max-height: {{ $height }}px; width: auto;"
    >
@endif
