<x-app-layout>
    <x-slot name="header">
        @include('modules.requisitions.partials.subnav', ['moduleLabel' => $moduleLabel, 'subTabs' => $subTabs])
    </x-slot>

    <!-- Chart.js CDN -->
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
            min-height: 36px !important; /* Más compactos */
            padding: 0.5rem 0.75rem !important;
            font-size: 0.85rem !important;
        }
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        .kpi-card {
            padding: 0.75rem 1rem !important; /* Más compacto */
            transition: transform 0.2s;
        }
        .kpi-card .text-caption {
            font-size: 0.7rem !important;
            margin-bottom: 0 !important;
        }
        .kpi-card:hover {
            transform: translateY(-3px);
        }
        .kpi-value {
            font-size: 1.6rem; /* Reducido de 2.2rem */
            font-weight: 800;
            line-height: 1;
            margin: 0.25rem 0;
        }
        .kpi-card .text-small {
            font-size: 0.65rem !important;
        }
        .dashboard-stat-grid {
            margin-bottom: 1rem !important; /* Reducido de 2rem */
            gap: 0.75rem !important;
        }

        /* Responsividad para móviles */
        @media (max-width: 768px) {
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
            .chart-container {
                height: 220px !important;
                width: 100% !important;
            }
            .kpi-value {
                font-size: 1.4rem;
            }
            .dashboard-scroll-area {
                max-height: none !important;
                overflow: visible !important;
                padding: 0 !important;
                width: 100% !important;
            }
        }
        /* Contenedor flexible para fijar el top y scrollear el resto */
        .page-section {
            display: flex !important;
            flex-direction: column !important;
            height: 100% !important;
            overflow: hidden !important;
            padding-top: 0.5rem !important;
            padding-bottom: 0.5rem !important;
        }
        .app-container {
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
        }
        
        .dashboard-filters, .dashboard-stat-grid {
            flex-shrink: 0; /* Evita que los filtros o KPIs se encojan */
        }

        .dashboard-scroll-area {
            flex-grow: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 0.5rem;
            /* Se remueve el max-height para que tome el espacio sobrante automáticamente */
        }
        
        .dashboard-scroll-area::-webkit-scrollbar {
            width: 8px;
        }
        .dashboard-scroll-area::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }
        .dashboard-scroll-area::-webkit-scrollbar-thumb {
            background-color: var(--color-sky);
            border-radius: 10px;
            border: 2px solid #f1f5f9;
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
            <div class="dashboard-stat-grid bottom-spaced">
                <article class="card kpi-card" style="border-left: 5px solid var(--color-primary);">
                    <p class="text-caption">Total Solicitudes</p>
                    <p class="kpi-value">{{ $stats['total'] }}</p>
                    <p class="text-small text-muted">Bajo los filtros seleccionados</p>
                </article>

                <article class="card kpi-card" style="border-left: 5px solid var(--color-sky);">
                    <p class="text-caption">Solicitadas</p>
                    <p class="kpi-value" style="color: var(--color-sky);">{{ $stats['solicitada'] }}</p>
                    <p class="text-small text-muted">Pendientes de inicio</p>
                </article>

                <article class="card kpi-card" style="border-left: 5px solid var(--color-warning);">
                    <p class="text-caption">En Gestión</p>
                    <p class="kpi-value" style="color: var(--color-warning);">{{ $stats['en_gestion'] }}</p>
                    <p class="text-small text-muted">Procesos activos</p>
                </article>

                <article class="card kpi-card" style="border-left: 5px solid var(--color-success);">
                    <p class="text-caption">Contratadas</p>
                    <p class="kpi-value" style="color: var(--color-success);">{{ $stats['contratado'] }}</p>
                    <p class="text-small text-muted">Procesos finalizados</p>
                </article>
            </div>

            {{-- ÁREA DE GRÁFICOS CON SCROLL --}}
            <div class="dashboard-scroll-area">
                <div class="form-panels">
                    {{-- Tendencia Mensual --}}
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Tendencia de Solicitudes</h3>
                        </div>
                        <div class="panel__body">
                            <div class="chart-container">
                                <canvas id="trendChart"></canvas>
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
                                <canvas id="statusChart"></canvas>
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
                                <canvas id="cityChart"></canvas>
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
                                <canvas id="clientChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-submit form on change
            const filterForm = document.querySelector('.dashboard-filters');
            const inputs = filterForm.querySelectorAll('select');
            
            inputs.forEach(input => {
                input.addEventListener('change', () => {
                    filterForm.submit();
                });
            });

            const data = @json($chartData);

            // 1. Gráfico de Tendencia (Líneas)
            new Chart(document.getElementById('trendChart'), {
                type: 'line',
                data: {
                    labels: data.trend.labels,
                    datasets: [{
                        label: 'Solicitudes',
                        data: data.trend.data,
                        borderColor: '#1984c7',
                        backgroundColor: 'rgba(25, 132, 199, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }
                }
            });

            // 2. Gráfico de Estado (Doughnut)
            new Chart(document.getElementById('statusChart'), {
                type: 'doughnut',
                data: {
                    labels: data.status.labels,
                    datasets: [{
                        data: data.status.data,
                        backgroundColor: ['#0369a1', '#92400e', '#15803d', '#be123c', '#64748b'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { position: 'bottom' } 
                    }
                }
            });

            // 3. Gráfico de Ciudades (Barras Horizontales)
            new Chart(document.getElementById('cityChart'), {
                type: 'bar',
                data: {
                    labels: data.cities.labels,
                    datasets: [{
                        label: 'Solicitudes',
                        data: data.cities.data,
                        backgroundColor: '#20214f',
                        borderRadius: 8
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }
                }
            });

            // 4. Gráfico de Clientes (Barras)
            new Chart(document.getElementById('clientChart'), {
                type: 'bar',
                data: {
                    labels: data.clients.labels,
                    datasets: [{
                        label: 'Solicitudes',
                        data: data.clients.data,
                        backgroundColor: '#1984c7',
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }
                }
            });
        });
    </script>
</x-app-layout>
