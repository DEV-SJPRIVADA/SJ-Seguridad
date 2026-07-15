<x-app-layout>
    <x-slot name="header">
        <div class="app-container" style="padding-top: 0.75rem; padding-bottom: 0.75rem;">
            <h2 class="panel-title" style="margin:0;">Dashboard Comercial</h2>
            <p class="panel-text" style="margin:0.25rem 0 0;">Indicadores de clientes y servicios (MT-CO-01)</p>
        </div>
    </x-slot>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .dashboard-filters {
            background: #fff;
            padding: 1rem;
            border-radius: var(--radius-lg);
            border: 1px solid var(--color-border);
            margin-bottom: 0.5rem;
            box-shadow: var(--shadow-soft);
        }
        .page-section {
            padding-top: 0.5rem !important;
            padding-bottom: 0.5rem !important;
            height: auto !important;
            overflow: visible !important;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 0.75rem;
            align-items: flex-end;
        }
        .filter-grid .form-label {
            font-size: 0.8rem;
            margin-bottom: 2px;
        }
        .filter-grid .form-select, .filter-grid .btn {
            min-height: 36px !important;
            padding: 0.5rem 0.75rem !important;
            font-size: 0.85rem !important;
        }
        .chart-container {
            position: relative;
            height: 280px;
            max-height: 280px;
            width: 100%;
            max-width: 100%;
            overflow: hidden;
        }
        .chart-container canvas {
            max-width: 100% !important;
            max-height: 100% !important;
        }
        .dashboard-scroll-area {
            max-width: 100%;
            overflow-x: hidden;
        }
        .dashboard-scroll-area .form-panels {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            width: 100%;
            max-width: 100%;
        }
        .dashboard-scroll-area .form-panels > .panel {
            min-width: 0;
            max-width: 100%;
            overflow: hidden;
        }
        .kpi-card {
            padding: 0.75rem 1rem !important;
            transition: transform 0.2s;
            text-decoration: none;
            color: inherit;
            display: block;
            min-width: 0;
        }
        .kpi-card .text-caption {
            font-size: 0.7rem !important;
            margin-bottom: 0 !important;
        }
        .kpi-card:hover {
            transform: translateY(-3px);
        }
        a.kpi-card:hover {
            color: inherit;
        }
        .kpi-value {
            font-size: 1.6rem;
            font-weight: 800;
            line-height: 1;
            margin: 0.25rem 0;
        }
        .kpi-card .text-small {
            font-size: 0.65rem !important;
        }
        .dashboard-stat-grid.dashboard-stat-grid--comercial-kpis {
            display: flex !important;
            flex-wrap: nowrap !important;
            align-items: stretch !important;
            gap: 0.5rem !important;
            margin-bottom: 1rem !important;
            width: 100%;
        }
        .dashboard-stat-grid--comercial-kpis .kpi-card {
            flex: 1 1 0 !important;
            min-width: 0 !important;
            padding: clamp(0.45rem, 0.9vw, 0.75rem) clamp(0.4rem, 0.8vw, 0.85rem) !important;
            height: auto !important;
        }
        .dashboard-stat-grid--comercial-kpis .text-caption {
            font-size: clamp(0.58rem, 0.85vw, 0.72rem) !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .dashboard-stat-grid--comercial-kpis .kpi-value {
            font-size: clamp(1.05rem, 1.7vw, 1.55rem);
        }
        .dashboard-stat-grid--comercial-kpis .text-small {
            font-size: clamp(0.52rem, 0.7vw, 0.65rem) !important;
            line-height: 1.25;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        @media (max-width: 900px) {
            .dashboard-scroll-area .form-panels {
                grid-template-columns: minmax(0, 1fr) !important;
            }
        }
        /* Solo celular: KPIs en 2 columnas */
        @media (max-width: 640px) {
            .dashboard-stat-grid.dashboard-stat-grid--comercial-kpis {
                flex-wrap: wrap !important;
            }
            .dashboard-stat-grid--comercial-kpis .text-caption {
                white-space: normal;
            }
            .dashboard-stat-grid--comercial-kpis .kpi-value {
                font-size: 1.35rem;
            }
            .form-panels {
                grid-template-columns: minmax(0, 1fr) !important;
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
            .chart-container {
                height: 220px !important;
                max-height: 220px !important;
                width: 100% !important;
            }
        }
        .app-container {
            height: auto;
            overflow: visible;
        }
        .dashboard-filters, .dashboard-stat-grid {
            margin-bottom: 1rem;
        }
    </style>

    <div class="page-section">
        <div class="app-container">
            <form method="GET" action="{{ route('comercial.dashboard') }}" class="dashboard-filters">
                <div class="filter-grid">
                    <div class="form-field">
                        <label class="form-label">Portafolio</label>
                        <select name="portfolio" class="form-select">
                            <option value="">Todos</option>
                            @foreach ($portfolios as $key => $label)
                                <option value="{{ $key }}" @selected($filters['portfolio'] === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field">
                        <label class="form-label">Ciudad</label>
                        <select name="city" class="form-select">
                            <option value="">Todas</option>
                            @foreach ($cities as $city)
                                <option value="{{ $city }}" @selected($filters['city'] === $city)>{{ $city }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field" style="max-width: 110px;">
                        <label class="form-label">Año</label>
                        <select name="year" class="form-select">
                            @foreach ($yearOptions as $year)
                                <option value="{{ $year }}" @selected((int) $filters['year'] === (int) $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field" style="max-width: 120px;">
                        <label class="form-label">Mes (altas)</label>
                        <select name="month" class="form-select">
                            <option value="">Todos</option>
                            @foreach (['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'] as $idx => $monthLabel)
                                <option value="{{ $idx + 1 }}" @selected((int) $filters['month'] === ($idx + 1))>{{ $monthLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <a href="{{ route('comercial.dashboard') }}" class="btn btn--secondary" style="height: 36px;" title="Limpiar filtros">Limpiar</a>
                    </div>
                </div>
                <p class="panel-text" style="margin:0.65rem 0 0;">Año/mes afectan <strong>clientes nuevos</strong> y la tendencia de altas. Portafolio y ciudad aplican a todos los KPIs de stock.</p>
            </form>

            <div class="dashboard-stat-grid dashboard-stat-grid--matriz-kpis bottom-spaced">
                <a href="{{ route('comercial.matriz.clients.index', array_filter(['city' => $filters['city'] ?: null])) }}" class="card kpi-card" style="border-left: 5px solid var(--color-primary);">
                    <p class="text-caption">Total clientes</p>
                    <p class="kpi-value">{{ $stats['total_clients'] }}</p>
                    <p class="text-small text-muted">Stock actual (portafolio/ciudad)</p>
                </a>

                <a href="{{ route('comercial.matriz.clients.index') }}" class="card kpi-card" style="border-left: 5px solid #0f766e;">
                    <p class="text-caption">Clientes nuevos</p>
                    <p class="kpi-value" style="color: #0f766e;">{{ $stats['new_clients'] }}</p>
                    <p class="text-small text-muted">
                        Ingresados
                        @if ($filters['month'])
                            en {{ ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'][$filters['month']] }} {{ $filters['year'] }}
                        @else
                            en {{ $filters['year'] }}
                        @endif
                    </p>
                </a>

                <a href="{{ route('comercial.matriz.services.index', array_filter(['portfolio' => $filters['portfolio'] ?: null])) }}" class="card kpi-card" style="border-left: 5px solid var(--color-sky);">
                    <p class="text-caption">Servicios activos</p>
                    <p class="kpi-value" style="color: var(--color-sky);">{{ $stats['active_services'] }}</p>
                    <p class="text-small text-muted">Excluye portafolio Inactivos</p>
                </a>

                <a href="{{ route('comercial.matriz.services.index', ['vigencia' => 'expiring']) }}" class="card kpi-card" style="border-left: 5px solid var(--color-warning);">
                    <p class="text-caption">Por vencer â‰¤30 dias</p>
                    <p class="kpi-value" style="color: var(--color-warning);">{{ $stats['expiring_soon'] }}</p>
                    <p class="text-small text-muted">Contratos proximos a vencer</p>
                </a>

                <a href="{{ route('comercial.matriz.services.index', ['vigencia' => 'expired']) }}" class="card kpi-card" style="border-left: 5px solid #be123c;">
                    <p class="text-caption">Vencidos</p>
                    <p class="kpi-value" style="color: #be123c;">{{ $stats['expired'] }}</p>
                    <p class="text-small text-muted">Fin de contrato ya pasado</p>
                </a>

                <a href="{{ route('comercial.matriz.services.index', ['portfolio' => \App\Models\CommercialService::PORTFOLIO_INACTIVOS]) }}" class="card kpi-card" style="border-left: 5px solid #64748b;">
                    <p class="text-caption">Servicios inactivos</p>
                    <p class="kpi-value" style="color: #64748b;">{{ $stats['inactive_services'] }}</p>
                    <p class="text-small text-muted">Portafolio Inactivos</p>
                </a>
            </div>

            <div class="dashboard-scroll-area">
                <div class="form-panels">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Tendencia de altas (inicio contrato)</h3>
                        </div>
                        <div class="panel__body">
                            <div class="chart-container">
                                <canvas id="trendChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Distribucion por portafolio</h3>
                        </div>
                        <div class="panel__body">
                            <div class="chart-container">
                                <canvas id="portfolioChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-panels block-spaced-lg">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Top 5 ciudades</h3>
                        </div>
                        <div class="panel__body">
                            <div class="chart-container">
                                <canvas id="cityChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Top 5 tipos de servicio</h3>
                        </div>
                        <div class="panel__body">
                            <div class="chart-container">
                                <canvas id="serviceTypeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filterForm = document.querySelector('.dashboard-filters');
            if (filterForm) {
                filterForm.querySelectorAll('select').forEach(function (input) {
                    input.addEventListener('change', function () {
                        filterForm.submit();
                    });
                });
            }

            const data = @json($chartData);
            const chartDefaults = {
                responsive: true,
                maintainAspectRatio: false,
                resizeDelay: 120,
                animation: false,
            };

            new Chart(document.getElementById('trendChart'), {
                type: 'line',
                data: {
                    labels: data.trend.labels,
                    datasets: [{
                        label: 'Servicios',
                        data: data.trend.data,
                        borderColor: '#1984c7',
                        backgroundColor: 'rgba(25, 132, 199, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    ...chartDefaults,
                    plugins: { legend: { display: false } }
                }
            });

            new Chart(document.getElementById('portfolioChart'), {
                type: 'doughnut',
                data: {
                    labels: data.portfolio.labels,
                    datasets: [{
                        data: data.portfolio.data,
                        backgroundColor: ['#0369a1', '#15803d', '#92400e', '#64748b'],
                        borderWidth: 0
                    }]
                },
                options: {
                    ...chartDefaults,
                    plugins: { legend: { position: 'bottom' } }
                }
            });

            new Chart(document.getElementById('cityChart'), {
                type: 'bar',
                data: {
                    labels: data.cities.labels,
                    datasets: [{
                        label: 'Servicios',
                        data: data.cities.data,
                        backgroundColor: '#20214f',
                        borderRadius: 8
                    }]
                },
                options: {
                    ...chartDefaults,
                    indexAxis: 'y',
                    plugins: { legend: { display: false } }
                }
            });

            new Chart(document.getElementById('serviceTypeChart'), {
                type: 'bar',
                data: {
                    labels: data.serviceTypes.labels,
                    datasets: [{
                        label: 'Servicios',
                        data: data.serviceTypes.data,
                        backgroundColor: '#1984c7',
                        borderRadius: 8
                    }]
                },
                options: {
                    ...chartDefaults,
                    plugins: { legend: { display: false } }
                }
            });
        });
    </script>
</x-app-layout>
