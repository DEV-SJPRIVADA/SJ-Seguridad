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

        <!-- jQuery y DataTables CDN -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
        <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
        <script>
            $(document).ready(function() {
                // Silenciar alertas de DataTables (mejor UX)
                $.fn.dataTable.ext.errMode = 'throw';

                $('.js-datatable').each(function() {
                    if (!$.fn.DataTable.isDataTable(this)) {
                        $(this).DataTable({
                            language: {
                                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                            },
                            pageLength: 10,
                            responsive: true,
                            retrieve: true
                        });
                    }
                });

                $('.js-datatable-permissions').each(function() {
                    if (!$.fn.DataTable.isDataTable(this)) {
                        $(this).DataTable({
                            language: {
                                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                            },
                            paging: true,
                            pageLength: 25,
                            scrollY: '450px',
                            scrollCollapse: true,
                            responsive: true,
                            order: [[0, 'asc']],
                            columnDefs: [
                                { targets: [0], visible: false }
                            ]
                        });
                    }
                });
            });
        </script>
        <style>
            /* Layout Fijo */
            .app-shell {
                height: 100vh;
                display: flex;
                flex-direction: column;
                overflow: hidden;
                background: var(--color-bg, #f4f7fb);
            }

            .app-frame {
                display: grid;
                flex: 1;
                grid-template-columns: 280px minmax(0, 1fr);
                overflow: hidden;
            }

            .app-sidebar {
                background: #e9eef5;
                border-right: 1px solid var(--color-border, #dbe3ef);
                padding: 1.5rem 1rem;
                height: 100%;
                overflow-y: auto;
            }

            .app-workspace {
                display: flex;
                flex-direction: column;
                height: 100%;
                overflow: hidden;
                min-width: 0;
            }

            .module-strip {
                background: var(--color-surface, #ffffff);
                border-bottom: 1px solid var(--color-border, #dbe3ef);
                flex-shrink: 0;
            }

            .app-main {
                flex: 1;
                overflow-y: auto;
                width: 100%;
            }

            /* DataTables Custom */
            .dataTables_wrapper .dataTables_filter input {
                border: 1px solid var(--border-color);
                border-radius: var(--radius-sm);
                padding: 0.4rem 0.8rem;
                background-color: var(--bg-primary);
                color: var(--text-primary);
            }
            .dataTables_wrapper .dataTables_length select {
                border: 1px solid var(--border-color);
                border-radius: var(--radius-sm);
                background-color: var(--bg-primary);
                color: var(--text-primary);
                padding: 0.2rem 1.5rem 0.2rem 0.5rem;
            }
            table.dataTable thead th {
                border-bottom: 1px solid var(--border-color);
                background-color: var(--bg-secondary);
                color: var(--text-muted);
                font-weight: 500;
                text-transform: uppercase;
                font-size: 0.75rem;
                letter-spacing: 0.05em;
            }
            .dataTables_wrapper .dataTables_paginate .paginate_button.current {
                background: var(--brand-primary) !important;
                color: white !important;
                border: 1px solid var(--brand-primary) !important;
            }
            .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate {
                color: var(--text-muted) !important;
                font-size: 0.875rem;
                margin-top: 1rem;
            }
        </style>
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
                        {{ $header }}
                    @endisset

                    <main class="app-main">
                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>
    </body>
</html>
