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
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            padding: 1.25rem 1.5rem;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.02);
            transition: box-shadow 0.2s;
        }
        .dashboard-filters:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.03);
        }
        .page-section {
            padding-top: 0.5rem !important;
            padding-bottom: 0.5rem !important;
            height: auto !important;
            overflow: visible !important;
        }
        .filter-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: flex-end;
        }
        .filter-grid .form-field {
            margin: 0;
            flex: 1 1 130px;
            min-width: 0;
        }
        .filter-grid .form-field--year {
            flex: 0 1 100px;
        }
        .filter-grid .form-field--month {
            flex: 0 1 110px;
        }
        .filter-grid .form-field--actions {
            flex: 0 0 auto;
            display: flex;
            gap: 0.5rem;
            align-items: flex-end;
            padding-bottom: 1px;
        }
        .filter-grid .form-label {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #475569;
            margin-bottom: 4px;
        }
        .filter-grid .form-label svg {
            width: 14px;
            height: 14px;
            flex-shrink: 0;
            opacity: 0.6;
        }
        .filter-grid .form-select {
            min-height: 38px;
            padding: 0.5rem 2rem 0.5rem 0.75rem;
            font-size: 0.85rem;
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            background-color: #fff;
            color: #1e293b;
            width: 100%;
            transition: border-color 0.15s, box-shadow 0.15s;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.6rem center;
            background-size: 1rem;
            cursor: pointer;
        }
        .filter-grid .form-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            outline: none;
        }
        .filter-grid .form-select:hover {
            border-color: #94a3b8;
        }
        .btn--clean {
            min-height: 38px;
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            background: #fff;
            color: #64748b;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            text-decoration: none;
        }
        .btn--clean:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #334155;
        }
        .filters-hint {
            margin-top: 0.75rem;
            font-size: 0.75rem;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            border-top: 1px solid #f1f5f9;
            padding-top: 0.75rem;
        }
        .filters-hint svg {
            width: 13px;
            height: 13px;
            flex-shrink: 0;
            opacity: 0.6;
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
                        <label class="form-label">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            Portafolio
                        </label>
                        <select name="portfolio" class="form-select">
                            <option value="">Todos</option>
                            @foreach ($portfolios as $key => $label)
                                <option value="{{ $key }}" @selected($filters['portfolio'] === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field">
                        <label class="form-label">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Ciudad
                        </label>
                        <select name="city" class="form-select">
                            <option value="">Todas</option>
                            @foreach ($cities as $city)
                                <option value="{{ $city }}" @selected($filters['city'] === $city)>{{ $city }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field form-field--year">
                        <label class="form-label">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Año
                        </label>
                        <select name="year" class="form-select">
                            @foreach ($yearOptions as $year)
                                <option value="{{ $year }}" @selected((int) $filters['year'] === (int) $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field form-field--month">
                        <label class="form-label">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            Mes
                        </label>
                        <select name="month" class="form-select">
                            <option value="">Todos</option>
                            @foreach (['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'] as $idx => $monthLabel)
                                <option value="{{ $idx + 1 }}" @selected($filters['month'] !== null && (int) $filters['month'] === ($idx + 1))>{{ $monthLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field--actions">
                        <a href="{{ route('comercial.dashboard') }}" class="btn--clean" title="Limpiar filtros">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Limpiar
                        </a>
                    </div>
                </div>
                <div class="filters-hint">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Mes y año definen la fecha de referencia para todos los KPIs. Portafolio y ciudad filtran en todos los indicadores.
                </div>
            </form>

            <div class="dashboard-stat-grid dashboard-stat-grid--matriz-kpis bottom-spaced">
                <a href="{{ route('comercial.matriz.clients.index', array_filter(['city' => $filters['city'] ?: null])) }}" class="card kpi-card" style="border-left: 5px solid var(--color-primary);">
                    <p class="text-caption">Clientes activos</p>
                    <p class="kpi-value">{{ $stats['active_clients'] }}</p>
                    <p class="text-small text-muted">Con servicio vigente al {{ $referenceDate->locale('es')->isoFormat('MMM YYYY') }}</p>
                </a>

                <a href="{{ route('comercial.matriz.services.index', array_filter(['portfolio' => $filters['portfolio'] ?: null])) }}" class="card kpi-card" style="border-left: 5px solid var(--color-sky);">
                    <p class="text-caption">Servicios activos</p>
                    <p class="kpi-value" style="color: var(--color-sky);">{{ $stats['active_services'] }}</p>
                    <p class="text-small text-muted">Vigentes al {{ $referenceDate->locale('es')->isoFormat('MMM YYYY') }}</p>
                </a>

                <a href="{{ route('comercial.matriz.clients.index') }}" class="card kpi-card" style="border-left: 5px solid #0f766e;">
                    <p class="text-caption">Clientes nuevos</p>
                    <p class="kpi-value" style="color: #0f766e;">{{ $stats['new_clients'] }}</p>
                    <p class="text-small text-muted">
                        Ingresados en {{ $referenceDate->locale('es')->isoFormat('MMM YYYY') }}
                    </p>
                </a>

                <a href="{{ route('comercial.matriz.services.index', ['vigencia' => 'expiring']) }}" class="card kpi-card" style="border-left: 5px solid var(--color-warning);">
                    <p class="text-caption">Servicios por vencer</p>
                    <p class="kpi-value" style="color: var(--color-warning);">{{ $stats['expiring_soon'] }}</p>
                    <p class="text-small text-muted">Proximos 30 dias desde {{ $referenceDate->locale('es')->isoFormat('MMM YYYY') }}</p>
                </a>

                <a href="{{ route('comercial.matriz.services.index', ['vigencia' => 'expired']) }}" class="card kpi-card" style="border-left: 5px solid #be123c;">
                    <p class="text-caption">Servicios vencidos</p>
                    <p class="kpi-value" style="color: #be123c;">{{ $stats['expired'] }}</p>
                    <p class="text-small text-muted">Contract_end previo al {{ $referenceDate->locale('es')->isoFormat('MMM YYYY') }}</p>
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
