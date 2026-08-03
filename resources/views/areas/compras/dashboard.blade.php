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
        .kpi-card {
            padding: 0.75rem 1rem !important;
            text-decoration: none;
            color: inherit;
            display: block;
            min-width: 0;
            transition: transform 0.2s;
        }
        .kpi-card:hover { transform: translateY(-3px); color: inherit; }
        .kpi-value {
            font-size: 1.6rem;
            font-weight: 800;
            line-height: 1;
            margin: 0.25rem 0;
        }
        .dashboard-stat-grid.dashboard-stat-grid--compras-kpis {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 0.5rem !important;
            margin-bottom: 1rem !important;
        }
        .dashboard-stat-grid--compras-kpis .kpi-card {
            flex: 1 1 calc(16.66% - 0.5rem);
            min-width: 140px;
        }
        @media (max-width: 900px) {
            .dashboard-scroll-area .form-panels { grid-template-columns: 1fr !important; }
            .dashboard-stat-grid--compras-kpis .kpi-card { flex: 1 1 calc(50% - 0.5rem); }
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
                <article class="card kpi-card" style="border-left: 5px solid var(--color-primary);">
                    <p class="text-caption">Solicitudes en periodo</p>
                    <p class="kpi-value">{{ number_format($stats['solicitudes_periodo']) }}</p>
                    <p class="text-small text-muted">Compras registradas {{ $referenceDate->locale('es')->isoFormat('MMM YYYY') }}</p>
                </article>

                <article class="card kpi-card" style="border-left: 5px solid #6366f1;">
                    <p class="text-caption">Pendientes director</p>
                    <p class="kpi-value" style="color: #6366f1;">{{ number_format($stats['pendiente_director']) }}</p>
                    <p class="text-small text-muted">Sin autorizacion aun</p>
                </article>

                @can('purchase.tab.processing')
                    <a href="{{ route('purchase-requests.processing.index', ['module' => 'compras']) }}" class="card kpi-card" style="border-left: 5px solid var(--color-sky);">
                        <p class="text-caption">En bandeja</p>
                        <p class="kpi-value" style="color: var(--color-sky);">{{ number_format($stats['bandeja_total']) }}</p>
                        <p class="text-small text-muted">Compras + suministros activos</p>
                    </a>

                    <a href="{{ route('purchase-requests.processing.index', ['module' => 'compras', 'estado_compras' => \App\Models\PurchaseRequest::COMPRAS_PENDIENTE]) }}" class="card kpi-card" style="border-left: 5px solid var(--color-warning);">
                        <p class="text-caption">Bandeja pendiente</p>
                        <p class="kpi-value" style="color: var(--color-warning);">{{ number_format($stats['bandeja_pendiente']) }}</p>
                        <p class="text-small text-muted">Por iniciar procesamiento</p>
                    </a>

                    <a href="{{ route('purchase-requests.processing.index', ['module' => 'compras', 'estado_compras' => \App\Models\PurchaseRequest::COMPRAS_EN_CURSO]) }}" class="card kpi-card" style="border-left: 5px solid #0ea5e9;">
                        <p class="text-caption">En curso</p>
                        <p class="kpi-value" style="color: #0ea5e9;">{{ number_format($stats['bandeja_en_curso']) }}</p>
                        <p class="text-small text-muted">Procesamiento activo</p>
                    </a>
                @else
                    <article class="card kpi-card" style="border-left: 5px solid var(--color-sky);">
                        <p class="text-caption">En bandeja</p>
                        <p class="kpi-value" style="color: var(--color-sky);">{{ number_format($stats['bandeja_total']) }}</p>
                        <p class="text-small text-muted">Compras + suministros activos</p>
                    </article>

                    <article class="card kpi-card" style="border-left: 5px solid var(--color-warning);">
                        <p class="text-caption">Bandeja pendiente</p>
                        <p class="kpi-value" style="color: var(--color-warning);">{{ number_format($stats['bandeja_pendiente']) }}</p>
                        <p class="text-small text-muted">Por iniciar procesamiento</p>
                    </article>

                    <article class="card kpi-card" style="border-left: 5px solid #0ea5e9;">
                        <p class="text-caption">En curso</p>
                        <p class="kpi-value" style="color: #0ea5e9;">{{ number_format($stats['bandeja_en_curso']) }}</p>
                        <p class="text-small text-muted">Procesamiento activo</p>
                    </article>
                @endcan

                <article class="card kpi-card" style="border-left: 5px solid #15803d;">
                    <p class="text-caption">Completadas</p>
                    <p class="kpi-value" style="color: #15803d;">{{ number_format($stats['completadas_periodo']) }}</p>
                    <p class="text-small text-muted">Cerradas en el periodo</p>
                </article>

                <article class="card kpi-card" style="border-left: 5px solid #be123c;">
                    <p class="text-caption">Urgentes en bandeja</p>
                    <p class="kpi-value" style="color: #be123c;">{{ number_format($stats['urgentes_bandeja']) }}</p>
                    <p class="text-small text-muted">Solicitudes marcadas urgentes</p>
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
