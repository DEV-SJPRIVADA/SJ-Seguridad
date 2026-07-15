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

        <!-- jQuery y DataTables CDN -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
        <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif

        <link rel="stylesheet" href="{{ asset('css/user-admin.css') }}?v={{ time() }}">

        @if (request()->routeIs('quality-documents.*'))
            <link rel="stylesheet" href="{{ asset('css/quality-documents.css') }}?v={{ @filemtime(public_path('css/quality-documents.css')) ?: time() }}">
        @endif

        @if (request()->routeIs('indicadores.*'))
            <link rel="stylesheet" href="{{ asset('css/indicadores.css') }}?v={{ @filemtime(public_path('css/indicadores.css')) ?: time() }}">
        @endif

        @stack('styles')

        <style>
            /* Estilo Global de Tablas Corporativas */
            .data-table th, 
            .js-datatable thead th,
            table.dataTable thead th,
            table.dataTable thead td {
                background-color: #003366 !important; /* Azul oscuro corporativo */
                color: #ffffff !important;
                font-size: clamp(10px, 0.8vw, 13px) !important;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                padding: 12px 10px !important;
                white-space: nowrap !important;
                border-bottom: 2px solid #002244 !important;
                text-align: center !important;
            }

            .data-table tbody tr:hover, 
            .js-datatable tbody tr:hover,
            table.dataTable tbody tr:hover {
                background-color: rgba(0, 51, 102, 0.05) !important;
            }

            /* Forzar centrado de texto en celdas de encabezado de DataTables */
            table.dataTable thead th {
                text-align: center !important;
            }
        </style>
        <script>
            $(document).ready(function() {
                // Silenciar alertas de DataTables (mejor UX)
                $.fn.dataTable.ext.errMode = 'throw';

                $('.js-datatable').each(function() {
                    if (!$.fn.DataTable.isDataTable(this)) {
                        var config = {
                            language: {
                                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                                buttons: {
                                    copyTitle: 'Copiado al portapapeles',
                                    copySuccess: {
                                        _: '%d filas copiadas',
                                        1: '1 fila copiada'
                                    }
                                }
                            },
                            dom: 'lfrtip',
                            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Todos']],
                            pageLength: 10,
                            responsive: true,
                            retrieve: true
                        };

                        if (!$(this).is('[data-no-excel]')) {
                            config.dom = 'lBfrtip';
                            config.buttons = [
                                {
                                    extend: 'excelHtml5',
                                    text: '<i class="fas fa-file-excel"></i> Exportar a Excel',
                                    className: 'btn btn--secondary btn--sm',
                                    titleAttr: 'Exportar tabla a Excel'
                                }
                            ];
                        }

                        $(this).DataTable(config);
                    }
                });

                $('.js-datatable-permissions').each(function() {
                    if (!$.fn.DataTable.isDataTable(this)) {
                        $(this).DataTable({
                            language: {
                                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                            },
                            paging: false,
                            scrollY: '450px',
                            scrollCollapse: true,
                            responsive: true,
                            order: [[0, 'asc']],
                            autoWidth: false,
                            columnDefs: [
                                { targets: [0], visible: false },
                                { targets: [1], width: '30%' },
                                { targets: [2], width: '55%' },
                                { targets: [3], width: '15%', className: 'text-center' }
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
                display: flex;
                flex: 1;
                overflow: hidden;
            }

            .app-sidebar {
                flex-shrink: 0;
                background: #e9eef5;
                border-right: 1px solid var(--color-border, #dbe3ef);
                padding: 1.5rem 1rem;
                height: 100%;
                overflow-y: auto;
            }

            .app-workspace {
                flex: 1;
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
                background: var(--color-bg, #f4f7fb);
            }

            @media (max-width: 1024px) {
                .nav-toggle {
                    display: inline-flex !important;
                }
                .app-shell {
                    height: auto !important;
                    min-height: 100vh !important;
                    overflow-y: auto !important;
                }
                .app-frame {
                    flex-direction: column !important;
                    overflow: visible !important;
                    height: auto !important;
                }
                .app-sidebar {
                    display: block !important;
                    width: 100% !important;
                    height: auto !important;
                    border-right: none !important;
                    border-bottom: 1px solid var(--color-border) !important;
                    padding: 1rem !important;
                }
                .app-workspace {
                    height: auto !important;
                    overflow: visible !important;
                    width: 100% !important;
                }
                .app-main {
                    overflow: visible !important;
                    padding: 0 !important;
                }
                .app-container {
                    width: 100% !important;
                    padding-left: 1rem !important;
                    padding-right: 1rem !important;
                    margin: 0 !important;
                }
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

            /* Estilos para Tablas de Suministros (Premium Look) */
            .supply-table {
                width: 100%;
                border-collapse: separate !important;
                border-spacing: 0 10px !important;
                margin-top: 0;
            }
            .supply-table thead th {
                padding: 14px 15px !important;
                background: #003366 !important;
                border: none !important;
                color: #ffffff !important;
                font-weight: 600 !important;
                text-transform: uppercase;
                font-size: 0.75rem;
                letter-spacing: 0.05em;
                text-align: center !important;
            }
            .supply-table thead tr th:first-child {
                border-top-left-radius: 12px;
                border-bottom-left-radius: 12px;
            }
            .supply-table thead tr th:last-child {
                border-top-right-radius: 12px;
                border-bottom-right-radius: 12px;
            }
            .supply-table tbody tr {
                background: #ffffff !important;
                box-shadow: 0 2px 6px rgba(0,0,0,0.02) !important;
                transition: all 0.2s ease;
            }
            .supply-table tbody tr:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important;
            }
            .supply-table tbody td {
                padding: 15px !important;
                border: none !important;
                vertical-align: middle !important;
                text-align: center !important;
            }
            .supply-table tbody tr td:first-child {
                border-top-left-radius: 12px;
                border-bottom-left-radius: 12px;
                text-align: left !important;
            }
            .supply-table tbody tr td:last-child {
                border-top-right-radius: 12px;
                border-bottom-right-radius: 12px;
            }

            /* Inputs estilizados para suministros */
            .supply-input, .supply-textarea {
                border: 1.5px solid #e2e8f0 !important;
                border-radius: 10px !important;
                padding: 0.5rem 0.8rem !important;
                font-size: 0.9rem !important;
                transition: all 0.2s !important;
                width: 100% !important;
                background-color: #f8fafc !important;
                font-family: inherit;
            }
            .supply-input:focus, .supply-textarea:focus {
                border-color: var(--color-primary, #2563eb) !important;
                background-color: #ffffff !important;
                box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1) !important;
                outline: none !important;
            }
            .supply-textarea {
                min-height: 100px;
                resize: vertical;
            }
            .currency-input-wrap {
                position: relative;
                display: flex;
                align-items: center;
            }
            .currency-input-wrap::before {
                content: '$';
                position: absolute;
                left: 12px;
                color: #64748b;
                font-weight: 600;
                z-index: 5;
            }
            .currency-input-wrap .supply-input {
                padding-left: 28px !important;
            }
            .supply-select {
                appearance: none;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
                background-repeat: no-repeat;
                background-position: right 0.75rem center;
                background-size: 1rem;
                padding-right: 2.5rem !important;
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

            /* Supply Statuses Forced */
            .status-pill--req-pendiente_calidad {
                background-color: #e0f2fe !important;
                color: #0369a1 !important;
            }

            .status-pill--req-aprobada_calidad {
                background-color: #dcfce7 !important;
                color: #15803d !important;
            }

            .status-pill--req-rechazada_calidad {
                background-color: #ffe4e6 !important;
                color: #be123c !important;
            }

            .status-pill--req-en_compras {
                background-color: #fef3c7 !important;
                color: #92400e !important;
            }

            .status-pill--req-completada {
                background-color: #e2e8f0 !important;
                color: #475569 !important;
            }

            /* DataTables Buttons Premium Styles */
            .dt-buttons {
                margin-bottom: 15px !important;
                display: flex !important;
                gap: 8px !important;
            }
            .dt-button {
                background: #ffffff !important;
                border: 1px solid var(--color-border-strong, #cbd5e1) !important;
                border-radius: 12px !important;
                color: #334155 !important;
                padding: 8px 16px !important;
                font-size: 0.85rem !important;
                font-weight: 700 !important;
                display: inline-flex !important;
                align-items: center !important;
                gap: 8px !important;
                transition: all 0.2s ease !important;
                box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important;
                cursor: pointer !important;
            }
            .dt-button:hover {
                background: #f8fafc !important;
                border-color: var(--color-border-strong) !important;
                transform: translateY(-1px) !important;
                box-shadow: 0 4px 6px rgba(0,0,0,0.05) !important;
            }
            .dt-button.buttons-excel {
                color: #15803d !important; /* Verde Excel */
            }
            .dt-button.buttons-excel:hover {
                background: #f0fdf4 !important;
            }
            div.dataTables_wrapper .dataTables_filter input {
                border: 1.5px solid #e2e8f0 !important;
                border-radius: 10px !important;
                padding: 5px 10px !important;
                margin-left: 10px !important;
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

            .dashboard-stat-grid:not(.dashboard-stat-grid--matriz-kpis) {
                display: grid !important;
                gap: 1rem !important;
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)) !important;
                margin-bottom: 2rem !important;
            }

            .dashboard-stat-grid:not(.dashboard-stat-grid--matriz-kpis) .card {
                padding: 1.25rem !important;
                height: 100% !important;
            }

            /* Comercial: 6 KPIs siempre en una fila (se compactan); wrap solo en celular */
            .dashboard-stat-grid.dashboard-stat-grid--matriz-kpis {
                display: flex !important;
                flex-wrap: nowrap !important;
                align-items: stretch !important;
                gap: 0.5rem !important;
                margin-bottom: 1rem !important;
                width: 100% !important;
            }

            .dashboard-stat-grid--matriz-kpis > .kpi-card,
            .dashboard-stat-grid--matriz-kpis > .card {
                flex: 1 1 0 !important;
                min-width: 0 !important;
                max-width: none !important;
                height: auto !important;
                padding: 0.55rem 0.6rem !important;
                box-sizing: border-box !important;
            }

            .dashboard-stat-grid--matriz-kpis .text-caption {
                font-size: clamp(0.55rem, 0.8vw, 0.7rem) !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
            }

            .dashboard-stat-grid--matriz-kpis .kpi-value {
                font-size: clamp(1rem, 1.5vw, 1.5rem) !important;
            }

            .dashboard-stat-grid--matriz-kpis .text-small {
                font-size: clamp(0.5rem, 0.65vw, 0.65rem) !important;
                line-height: 1.2 !important;
            }

            @media (max-width: 1024px) {
                .dashboard-stat-grid:not(.dashboard-stat-grid--matriz-kpis) {
                    grid-template-columns: 1fr !important;
                }
                .dashboard-stat-grid:not(.dashboard-stat-grid--matriz-kpis) .card {
                    text-align: center !important;
                }
                .dashboard-hero__header {
                    flex-direction: column !important;
                    align-items: center !important;
                    text-align: center !important;
                    gap: 1.5rem !important;
                }
                /* Botones de navegación (módulos y pestañas) en dos columnas en mobile */
                .app-sidebar__nav, 
                .module-tabs, 
                .requisition-subtabs__inner {
                    display: flex !important;
                    flex-direction: row !important;
                    flex-wrap: wrap !important;
                    gap: 0.5rem !important;
                    justify-content: center !important;
                }

                .sidebar-link, 
                .module-tab,
                .requisition-subtabs__inner .module-tab {
                    width: calc(50% - 0.5rem) !important;
                    margin: 0 !important;
                    justify-content: center !important;
                    text-align: center !important;
                    padding: 0.6rem 0.4rem !important;
                    font-size: 0.8rem !important;
                }

                .app-sidebar__header {
                    text-align: center !important;
                }
            }

            @media (max-width: 640px) {
                .dashboard-stat-grid.dashboard-stat-grid--matriz-kpis {
                    flex-wrap: wrap !important;
                }

                .dashboard-stat-grid--matriz-kpis > .kpi-card,
                .dashboard-stat-grid--matriz-kpis > .card {
                    flex: 1 1 calc(50% - 0.35rem) !important;
                    max-width: calc(50% - 0.35rem) !important;
                }

                .dashboard-stat-grid--matriz-kpis .text-caption {
                    white-space: normal !important;
                }
            }

            /* Fix para flechas de paginación gigantes */
            nav[role="navigation"] svg {
                width: 1.25rem !important;
                height: 1.25rem !important;
                display: inline-block !important;
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
                        <p class="text-caption">Procesos</p>
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
