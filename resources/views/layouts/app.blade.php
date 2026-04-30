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
            .dataTables_wrapper .dataTables_filter {
                margin-bottom: 1.5rem !important;
                text-align: right !important;
            }
            .dataTables_wrapper .dataTables_filter input {
                border: 1px solid var(--color-border) !important;
                border-radius: 12px !important;
                padding: 0.6rem 1rem !important;
                background-color: #fff !important;
                color: var(--color-text) !important;
                min-width: 280px !important;
                margin-left: 0.5rem !important;
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

            /* Sistema de Toasts */
            .toast-container {
                position: fixed;
                bottom: 2rem;
                right: 2rem;
                z-index: 99999;
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
                pointer-events: none;
            }

            .toast {
                pointer-events: auto;
                min-width: 300px;
                max-width: 450px;
                padding: 1rem 1.25rem;
                border-radius: 16px;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                animation: toast-in 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
                border: 1px solid transparent;
            }

            @keyframes toast-in {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }

            .toast--success {
                background: #ecfdf5;
                border-color: #10b981;
                color: #065f46;
            }

            .toast--error {
                background: #fff1f2;
                border-color: #f43f5e;
                color: #9f1239;
            }

            .toast__close {
                background: transparent;
                border: 0;
                color: inherit;
                cursor: pointer;
                opacity: 0.6;
                font-size: 1.2rem;
                line-height: 1;
            }

            .toast__close:hover {
                opacity: 1;
            }
            /* Requisition Statuses Forced */
            .status-pill--req-solicitada {
                background-color: #e0f2fe !important;
                color: #0369a1 !important;
            }

            .status-pill--req-en_gestion {
                background-color: #fef3c7 !important;
                color: #92400e !important;
            }

            .status-pill--req-contratado {
                background-color: #dcfce7 !important;
                color: #15803d !important;
            }

            .status-pill--req-cancelada {
                background-color: #ffe4e6 !important;
                color: #be123c !important;
            }

            /* Workspace Layout Enforcement */
            .app-workspace {
                display: flex !important;
                flex-direction: column !important;
                overflow: hidden !important;
            }

            .module-strip {
                z-index: 25;
                position: relative;
                flex-shrink: 0 !important;
                box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            }

            .app-workspace-header {
                z-index: 20;
                position: relative;
                flex-shrink: 0 !important;
                background: var(--color-surface);
                border-bottom: 1px solid var(--color-border);
                box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            }

            .requisition-subtabs {
                margin: 0 !important;
                padding: 0 !important;
            }

            .dashboard-stat-grid {
                display: grid !important;
                gap: 1rem !important;
                grid-template-columns: repeat(4, 1fr) !important;
                margin-bottom: 2rem !important;
            }

            .dashboard-stat-grid .card {
                padding: 0.75rem 1rem !important;
            }

            .dashboard-stat-grid .text-caption {
                font-size: 0.7rem !important;
                margin-bottom: 0.25rem !important;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }

            .dashboard-stat-grid .page-title {
                font-size: 1.4rem !important;
                margin: 0 !important;
            }
        </style>

        <script>
            window.showToast = function(message, type = 'success') {
                const container = document.getElementById('global-toast-container');
                if (!container) return;

                const toast = document.createElement('div');
                toast.className = `toast toast--${type}`;
                toast.innerHTML = `
                    <span>${message}</span>
                    <button class="toast__close" onclick="this.parentElement.remove()">✕</button>
                `;

                container.appendChild(toast);

                setTimeout(() => {
                    toast.style.animation = 'toast-in 0.4s reverse forwards';
                    setTimeout(() => toast.remove(), 400);
                }, 5000);
            };

            // Disparar desde session status de Laravel
            $(document).ready(function() {
                @if (session('status'))
                    @php
                        $msg = session('status');
                        $type = 'success';
                        
                        // Mapeo simple de mensajes comunes
                        $messages = [
                            'user-created' => 'Usuario creado correctamente.',
                            'user-updated' => 'Usuario actualizado correctamente.',
                            'requisition-created' => 'Requisicion registrada con exito.',
                            'requisition-updated' => 'Requisicion actualizada correctamente.',
                            'requisition-parameter-created' => 'Parametro registrado correctamente.',
                            'requisition-parameter-updated' => 'Parametro actualizado correctamente.',
                            'requisition-parameter-deleted' => 'Parametro eliminado correctamente.',
                        ];

                        $finalMsg = $messages[$msg] ?? $msg;
                        if (str_contains(strtolower($msg), 'error') || str_contains(strtolower($msg), 'fail')) {
                            $type = 'error';
                        }
                    @endphp
                    showToast("{{ $finalMsg }}", "{{ $type }}");
                @endif

                @if ($errors->any())
                    showToast("Por favor verifica los errores en el formulario.", "error");
                @endif
            });
        </script>
    </head>
    <body>
        <div id="global-toast-container" class="toast-container"></div>
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
                        <div class="app-workspace-header">
                            {{ $header }}
                        </div>
                    @endisset

                    <main class="app-main">
                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>
        @stack('scripts')
    </body>
</html>
