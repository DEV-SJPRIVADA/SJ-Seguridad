<x-app-layout>
    <x-slot name="header">
        <div class="app-container" style="padding-top: 0.75rem; padding-bottom: 0.75rem;">
            <h2 class="panel-title" style="margin:0;">Dashboard Compras</h2>
            <p class="panel-text" style="margin:0.25rem 0 0;">Indicadores de solicitudes de compra, bandeja y suministros</p>
        </div>
    </x-slot>

    <script type="application/json" id="compras-chart-data">@json($chartData)</script>
    @vite(['resources/js/compras-dashboard-charts.js'])

    <style>
        .dashboard-filters {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            padding: 1.25rem 1.5rem;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .page-section {
            padding-top: 0.5rem !important;
            padding-bottom: 0.5rem !important;
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
        .filter-grid .form-field--year { flex: 0 1 100px; }
        .filter-grid .form-field--month { flex: 0 1 110px; }
        .filter-grid .form-field--actions {
            flex: 0 0 auto;
            display: flex;
            gap: 0.5rem;
            align-items: flex-end;
        }
        .filter-grid .form-label {
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #475569;
            margin-bottom: 4px;
        }
        .filter-grid .form-select {
            min-height: 38px;
            padding: 0.5rem 2rem 0.5rem 0.75rem;
            font-size: 0.85rem;
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            width: 100%;
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
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        .chart-container {
            position: relative;
            height: 280px;
            max-height: 280px;
            width: 100%;
            overflow: hidden;
        }
        .chart-container > div {
            width: 100%;
            height: 100%;
        }
        .dashboard-scroll-area .form-panels {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            width: 100%;
        }
        @media (max-width: 900px) {
            .dashboard-scroll-area .form-panels { grid-template-columns: 1fr !important; }
        }
    </style>

    <div class="page-section">
        <div class="app-container">
            <form method="GET" action="{{ route('compras.dashboard') }}" class="dashboard-filters">
                <div class="filter-grid">
                    <div class="form-field">
                        <label class="form-label">Area solicitante</label>
                        <select name="area_key" class="form-select">
                            <option value="">Todas</option>
                            @foreach ($areas as $areaKey => $areaLabel)
                                <option value="{{ $areaKey }}" @selected($filters['area_key'] === $areaKey)>{{ $areaLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-select">
                            <option value="">Todos</option>
                            <option value="purchase" @selected($filters['tipo'] === 'purchase')>Solicitud compra</option>
                            <option value="supply" @selected($filters['tipo'] === 'supply')>Suministro</option>
                        </select>
                    </div>
                    <div class="form-field form-field--year">
                        <label class="form-label">Ano</label>
                        <select name="year" class="form-select">
                            @foreach ($yearOptions as $year)
                                <option value="{{ $year }}" @selected((int) $filters['year'] === (int) $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field form-field--month">
                        <label class="form-label">Mes</label>
                        <select name="month" class="form-select">
                            <option value="">Todos</option>
                            @foreach (['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'] as $idx => $monthLabel)
                                <option value="{{ $idx + 1 }}" @selected($filters['month'] !== null && (int) $filters['month'] === ($idx + 1))>{{ $monthLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field--actions">
                        <a href="{{ route('compras.dashboard') }}" class="btn--clean">Limpiar</a>
                    </div>
                </div>
                <p class="form-hint" style="margin-top: 0.75rem; margin-bottom: 0;">
                    Ano y mes aplican a solicitudes creadas y completadas en el periodo. La bandeja activa es una foto actual.
                </p>
            </form>

            <div class="dashboard-stat-grid dashboard-stat-grid--compras-kpis bottom-spaced">
                <article class="compras-dashboard-kpi compras-dashboard-kpi--periodo">
                    <span class="compras-dashboard-kpi__label">Solicitudes en periodo</span>
                    <span class="compras-dashboard-kpi__value">{{ number_format($stats['solicitudes_periodo']) }}</span>
                </article>

                <article class="compras-dashboard-kpi compras-dashboard-kpi--pendiente-director">
                    <span class="compras-dashboard-kpi__label">Pendientes director</span>
                    <span class="compras-dashboard-kpi__value">{{ number_format($stats['pendiente_director']) }}</span>
                </article>

                @can('purchase.tab.processing')
                    <a href="{{ route('purchase-requests.processing.index', ['module' => 'compras']) }}" class="compras-dashboard-kpi compras-dashboard-kpi--bandeja">
                        <span class="compras-dashboard-kpi__label">En bandeja</span>
                        <span class="compras-dashboard-kpi__value">{{ number_format($stats['bandeja_total']) }}</span>
                    </a>

                    <a href="{{ route('purchase-requests.processing.index', ['module' => 'compras', 'estado_compras' => \App\Models\PurchaseRequest::COMPRAS_PENDIENTE]) }}" class="compras-dashboard-kpi compras-dashboard-kpi--bandeja-pendiente">
                        <span class="compras-dashboard-kpi__label">Bandeja pendiente</span>
                        <span class="compras-dashboard-kpi__value">{{ number_format($stats['bandeja_pendiente']) }}</span>
                    </a>

                    <a href="{{ route('purchase-requests.processing.index', ['module' => 'compras', 'estado_compras' => \App\Models\PurchaseRequest::COMPRAS_EN_CURSO]) }}" class="compras-dashboard-kpi compras-dashboard-kpi--en-curso">
                        <span class="compras-dashboard-kpi__label">En curso</span>
                        <span class="compras-dashboard-kpi__value">{{ number_format($stats['bandeja_en_curso']) }}</span>
                    </a>
                @else
                    <article class="compras-dashboard-kpi compras-dashboard-kpi--bandeja">
                        <span class="compras-dashboard-kpi__label">En bandeja</span>
                        <span class="compras-dashboard-kpi__value">{{ number_format($stats['bandeja_total']) }}</span>
                    </article>

                    <article class="compras-dashboard-kpi compras-dashboard-kpi--bandeja-pendiente">
                        <span class="compras-dashboard-kpi__label">Bandeja pendiente</span>
                        <span class="compras-dashboard-kpi__value">{{ number_format($stats['bandeja_pendiente']) }}</span>
                    </article>

                    <article class="compras-dashboard-kpi compras-dashboard-kpi--en-curso">
                        <span class="compras-dashboard-kpi__label">En curso</span>
                        <span class="compras-dashboard-kpi__value">{{ number_format($stats['bandeja_en_curso']) }}</span>
                    </article>
                @endcan

                <article class="compras-dashboard-kpi compras-dashboard-kpi--completadas">
                    <span class="compras-dashboard-kpi__label">Completadas</span>
                    <span class="compras-dashboard-kpi__value">{{ number_format($stats['completadas_periodo']) }}</span>
                </article>

                <article class="compras-dashboard-kpi compras-dashboard-kpi--urgentes">
                    <span class="compras-dashboard-kpi__label">Urgentes en bandeja</span>
                    <span class="compras-dashboard-kpi__value">{{ number_format($stats['urgentes_bandeja']) }}</span>
                </article>
            </div>

            <div class="dashboard-scroll-area">
                <div class="form-panels">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Tendencia mensual ({{ $filters['year'] }})</h3>
                        </div>
                        <div class="panel__body">
                            <div class="chart-container">
                                <div id="trendChart"></div>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Estado solicitudes (periodo)</h3>
                        </div>
                        <div class="panel__body">
                            <div class="chart-container">
                                <div id="purchaseStatusChart"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-panels block-spaced-lg">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Bandeja por estado (actual)</h3>
                        </div>
                        <div class="panel__body">
                            <div class="chart-container">
                                <div id="bandejaStatusChart"></div>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Bandeja por tipo</h3>
                        </div>
                        <div class="panel__body">
                            <div class="chart-container">
                                <div id="tipoBandejaChart"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-panels block-spaced-lg">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Top 5 areas solicitantes</h3>
                        </div>
                        <div class="panel__body">
                            <div class="chart-container">
                                <div id="areaChart"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
