<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body>
        <div class="app-shell">
            @include('layouts.navigation')

            <div class="app-frame">
                <aside class="app-sidebar">
                    <div class="app-sidebar__header">
                        <p class="text-caption">Modulos autorizados</p>
                    </div>

                    <nav class="app-sidebar__nav">
                        @foreach ($appNavigation as $module)
                            <a href="{{ $module['url'] }}" class="sidebar-link {{ $module['active'] ? 'sidebar-link--active' : '' }}">
                                <span class="sidebar-link__title">{{ $module['label'] }}</span>
                            </a>
                        @endforeach
                    </nav>
                </aside>

                <div class="app-workspace">
                    @if ($currentModule)
                        <div class="module-strip">
                            <div class="app-container">
                                <div class="module-strip__inner">
                                    <div>
                                        <p class="text-caption">Modulo actual</p>
                                        <p class="panel-title title-spaced">{{ $currentModule['label'] }}</p>
                                    </div>

                                    <nav class="module-tabs">
                                        @foreach ($currentModuleTabs as $tab)
                                            <a href="{{ $tab['url'] }}" class="module-tab {{ $tab['active'] ? 'module-tab--active' : '' }}">
                                                {{ $tab['label'] }}
                                            </a>
                                        @endforeach
                                    </nav>
                                </div>
                            </div>
                        </div>
                    @endif

                    @isset($header)
                        <header class="page-header">
                            <div class="app-container page-header-inner">
                                {{ $header }}
                            </div>
                        </header>
                    @endisset

                    <main class="app-main">
                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>
    </body>
</html>
