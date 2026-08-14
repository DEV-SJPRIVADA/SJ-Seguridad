<x-app-layout>
    <x-slot name="header">
        @include('modules.requisitions.partials.subnav', ['moduleLabel' => $moduleLabel, 'subTabs' => $subTabs])
    </x-slot>

    <script type="application/json" id="requisitions-chart-data">@json($chartData)</script>
    @vite(['resources/js/requisitions-dashboard-charts.js'])

    <style>
        .dashboard-filters {
            background: #fff;
            padding: 1rem;
            border-radius: var(--radius-lg);
            border: 1px solid var(--color-border);
            margin-bottom: 0.5rem;
            box-shadow: var(--shadow-soft);
        }
        /* Override local para asegurar que el cambio de padding se aplique */
        .page-section {
            padding-top: 0.5rem !important;
            padding-bottom: 0.5rem !important;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 0.75rem; /* Reducido de 1rem */
            align-items: flex-end;
        }
        .filter-grid .form-label {
            font-size: 0.8rem; /* Más pequeño */
            margin-bottom: 2px;
        }
        .filter-grid .form-select, .filter-grid .btn {
            min-height: var(--control-height-chrome);
            padding: 0.35rem 0.75rem;
            font-size: var(--control-font-size);
        }
        .dashboard-stat-grid {
            margin-bottom: 0.5rem !important;
            gap: 0.75rem !important;
        }

        /* Responsividad para móviles */
        @media (max-width: 768px) {
            .req-dashboard-charts .form-panels {
                grid-template-columns: 1fr !important;
            }
            .form-panels {
                grid-template-columns: 1fr !important;
                width: 100% !important;
                gap: 1rem;
                display: flex !important;
                flex-direction: column;
            }
            .panel {
                width: 100% !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
            }
            .dashboard-scroll-area {
                max-height: none !important;
                overflow: visible !important;
                padding: 0 !important;
                width: 100% !important;
            }
        }
        /* Flujo natural para permitir el scroll de toda la página */
        .page-section {
            padding-top: 0.5rem !important;
            padding-bottom: 0.5rem !important;
            height: auto !important;
            overflow: visible !important;
        }
        .app-container {
            height: auto;
            overflow: visible;
        }
        
        .dashboard-filters {
            margin-bottom: 0.5rem;
        }

        .dashboard-stat-grid.bottom-spaced {
            margin-bottom: 0.5rem !important;
        }

        .dashboard-scroll-area {
            padding-right: 0.5rem;
        }
    </style>

    <div class="page-section">
        <div class="app-container">
            
            {{-- SECCIÓN DE FILTROS --}}
            <form method="GET" action="{{ route('requisitions.dashboard', ['module' => $moduleKey]) }}" class="dashboard-filters">

                <div class="filter-grid">
                    <div class="form-field">
                        <label class="form-label">Cliente</label>
                        <select name="client_id" class="form-select select2">
                            <option value="">Todos los clientes</option>
                            @foreach ($catalogs['clients'] as $client)
                                <option value="{{ $client->id }}" @selected($filters['client_id'] == $client->id)>{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field">
                        <label class="form-label">Cargo</label>
                        <select name="position_id" class="form-select select2">
                            <option value="">Todos los cargos</option>
                            @foreach ($catalogs['positions'] as $pos)
                                <option value="{{ $pos->id }}" @selected($filters['position_id'] == $pos->id)>{{ $pos->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field">
                        <label class="form-label">Ciudad</label>
                        <select name="city_id" class="form-select">
                            <option value="">Todas las ciudades</option>
                            @foreach ($catalogs['cities'] as $city)
                                <option value="{{ $city->id }}" @selected($filters['city_id'] == $city->id)>{{ $city->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field">
                        <label class="form-label">Estado</label>
                        <select name="status" class="form-select">
                            <option value="">Todos los estados</option>
                            @foreach ($statusLabels as $key => $label)
                                <option value="{{ $key }}" @selected($filters['status'] == $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field" style="max-width: 100px;">
                        <label class="form-label">Año</label>
                        <select name="year" class="form-select">
                            @for ($y = now()->year; $y >= 2024; $y--)
                                <option value="{{ $y }}" @selected($filters['year'] == $y)>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-field" style="max-width: 120px;">
                        <label class="form-label">Mes</label>
                        <select name="month" class="form-select">
                            <option value="">Todos</option>
                            @foreach (['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'] as $idx => $m)
                                <option value="{{ $idx + 1 }}" @selected($filters['month'] == ($idx + 1))>{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <a href="{{ route('requisitions.dashboard', ['module' => $moduleKey]) }}" class="btn btn--secondary" style="height: 44px; width: 44px; padding: 0;" title="Limpiar">🔄</a>
                    </div>
                </div>
            </form>

            {{-- KPIs --}}
            @if ($dashboardGlobalScope ?? false)
                <div class="dashboard-stat-grid dashboard-stat-grid--requisition-kpis req-dashboard-kpis bottom-spaced"></div>
            @endif
            <div class="dashboard-stat-grid dashboard-stat-grid--requisition-kpis req-dashboard-kpis bottom-spaced">
                <article class="req-dashboard-kpi req-dashboard-kpi--total">
                    <span class="req-dashboard-kpi__label">Total solicitudes</span>
                    <span class="req-dashboard-kpi__value">{{ number_format($stats['total']) }}</span>
                </article>

                <article class="req-dashboard-kpi req-dashboard-kpi--solicitada">
                    <span class="req-dashboard-kpi__label">Solicitadas</span>
                    <span class="req-dashboard-kpi__value">{{ number_format($stats['solicitada']) }}</span>
                </article>

                <article class="req-dashboard-kpi req-dashboard-kpi--en-gestion">
                    <span class="req-dashboard-kpi__label">En gestión</span>
                    <span class="req-dashboard-kpi__value">{{ number_format($stats['en_gestion']) }}</span>
                </article>

                <article class="req-dashboard-kpi req-dashboard-kpi--contratado">
                    <span class="req-dashboard-kpi__label">Contratadas</span>
                    <span class="req-dashboard-kpi__value">{{ number_format($stats['contratado']) }}</span>
                </article>

                <article class="req-dashboard-kpi req-dashboard-kpi--cancelada">
                    <span class="req-dashboard-kpi__label">Canceladas</span>
                    <span class="req-dashboard-kpi__value">{{ number_format($stats['cancelada']) }}</span>
                </article>
            </div>

            {{-- ÁREA DE GRÁFICOS CON SCROLL --}}
            <div class="dashboard-scroll-area req-dashboard-charts">
                <div class="form-panels">
                    {{-- Tendencia Mensual --}}
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Tendencia de Solicitudes</h3>
                        </div>
                        <div class="panel__body">
                            <div class="chart-container">
                                <div id="trendChart"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Distribución por Estado --}}
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Distribución por Estado</h3>
                        </div>
                        <div class="panel__body">
                            <div class="chart-container">
                                <div id="statusChart"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-panels block-spaced-lg">
                    {{-- Top Ciudades --}}
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Top 5 Ciudades</h3>
                        </div>
                        <div class="panel__body">
                            <div class="chart-container">
                                <div id="cityChart"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Top Clientes --}}
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Top 5 Clientes</h3>
                        </div>
                        <div class="panel__body">
                            <div class="chart-container">
                                <div id="clientChart"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
