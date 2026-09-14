<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }}</title>

        <!-- Tipografía: Neue Haas Grotesk Display (Vite / resources/fonts) -->

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body>
        <div @class(['guest-shell', $shellClass])>
            <div class="guest-stack">
                <div class="guest-card">
                    <div class="guest-logo">
                        <a href="/">
                            <x-application-logo class="brand-logo" />
                        </a>
                    </div>

                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
