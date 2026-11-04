<x-app-layout>
    <x-slot name="header">
        <div class="app-container gh-area-dashboard-page__header">
            <h2 class="gh-area-dashboard-page__title">Dashboard de Compras</h2>
        </div>
    </x-slot>

    {{-- Informe Compras: pestañas Solicitudes / Órdenes, filtros searchable-select y KPIs al estilo GH --}}
    @php
        $activeTab = $filters['tab'] ?? 'solicitudes';
        $isSolicitudes = $activeTab === 'solicitudes';
    @endphp

    <script type="application/json" id="compras-chart-data">@json($chartData)</script>
    @vite(['resources/js/compras-dashboard-charts.js'])

    <div class="page-section cursos-dashboard-page formacion-dashboard-page gh-area-dashboard-page compras-dashboard-page">
        <div class="module-subnav requisition-subtabs">
            <div class="app-container">
                <div class="module-subnav__inner requisition-subtabs__inner">
                    <p class="text-caption module-subnav__label">Dashboard</p>
                    <nav class="module-tabs" aria-label="Dashboard Compras">
                        @foreach ($tabs as $tab)
                            <a href="{{ $tab['url'] }}" class="module-tab {{ $tab['active'] ? 'module-tab--active' : '' }}">
                                {{ $tab['label'] }}
                            </a>
                        @endforeach
                    </nav>
                </div>
            </div>
        </div>

        <div class="app-container section-stack">
            <div class="panel cursos-dashboard-page__filters-panel">
                <div class="panel__body panel__body--compact">
                    <form method="GET" action="{{ route('compras.dashboard') }}" class="dashboard-filters compras-dashboard-page__filter-form">
                        <input type="hidden" name="tab" value="{{ $activeTab }}">
                        <div class="cursos-dashboard-page__filters">
                            <div class="form-field">
                                <label class="form-label" for="compras-dashboard-area">Área solicitante</label>
                                <x-searchable-select
                                    id="compras-dashboard-area"
                                    name="area_key"
                                    :options="$areas"
                                    :value="$filters['area_key'] ?? ''"
                                    placeholder="Todas las áreas"
                                    searchPlaceholder="Buscar área…"
                                />
                            </div>
                            <div class="form-field">
                                <label class="form-label" for="compras-dashboard-tipo">Tipo</label>
                                <x-searchable-select
                                    id="compras-dashboard-tipo"
                                    name="tipo"
                                    :options="[
                                        ['value' => 'purchase', 'label' => 'Solicitud compra'],
                                        ['value' => 'supply', 'label' => 'Suministro'],
                                    ]"
                                    :value="$filters['tipo'] ?? ''"
                                    placeholder="Todos los tipos"
                                    searchPlaceholder="Buscar tipo…"
                                />
                            </div>
                            <div class="form-field">
                                <label class="form-label" for="compras-dashboard-year">Año</label>
                                <x-searchable-select
                                    id="compras-dashboard-year"
                                    name="year"
                                    :options="$yearSelectOptions"
                                    :value="$filters['year']"
                                    placeholder="Seleccionar año"
                                    :allow-clear="false"
                                />
                            </div>
                            <div class="form-field">
                                <label class="form-label" for="compras-dashboard-month">Mes</label>
                                <x-searchable-select
                                    id="compras-dashboard-month"
                                    name="month"
                                    :options="$monthSelectOptions"
                                    :value="$filters['month']"
                                    placeholder="Todos"
                                    searchPlaceholder="Buscar mes…"
                                    :allow-clear="true"
                                />
                            </div>
                            <div class="form-field cursos-dashboard-page__filter-actions">
                                <button
                                    type="submit"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                    title="Filtrar"
                                    aria-label="Filtrar"
                                >
                                    <x-lucide-search width="18" height="18" aria-hidden="true" />
                                </button>
                                <a
                                    href="{{ route('compras.dashboard', ['tab' => $activeTab]) }}"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Limpiar filtros"
                                    aria-label="Limpiar filtros"
                                >
                                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            @if ($isSolicitudes)
                <div class="cursos-dashboard-page__kpi-grid gh-area-dashboard-page__kpi-grid compras-dashboard-page__kpi-grid compras-dashboard-page__kpi-grid--solicitudes">
                    <div class="card cursos-dashboard-page__kpi" style="border-left-color:#0369a1;">
                        <p class="cursos-dashboard-page__kpi-label">Por autorizar</p>
                        <p class="cursos-dashboard-page__kpi-value">{{ number_format($stats['pendiente_director']) }}</p>
                    </div>

                    @can('purchase.tab.processing')
                        <a href="{{ route('purchase-requests.processing.index', array_merge(['module' => 'compras'], $bandejaLinks['index'])) }}" class="card cursos-dashboard-page__kpi" style="border-left-color:#0f172a;">
                            <p class="cursos-dashboard-page__kpi-label">En bandeja</p>
                            <p class="cursos-dashboard-page__kpi-value">{{ number_format($stats['bandeja_total']) }}</p>
                        </a>
                        <a href="{{ route('purchase-requests.processing.index', array_merge(['module' => 'compras'], $bandejaLinks['pendiente'])) }}" class="card cursos-dashboard-page__kpi" style="border-left-color:#ea580c;">
                            <p class="cursos-dashboard-page__kpi-label">Por comprar</p>
                            <p class="cursos-dashboard-page__kpi-value">{{ number_format($stats['bandeja_pendiente']) }}</p>
                        </a>
                        <a href="{{ route('purchase-requests.processing.index', array_merge(['module' => 'compras'], $bandejaLinks['en_curso'])) }}" class="card cursos-dashboard-page__kpi" style="border-left-color:#1984c7;">
                            <p class="cursos-dashboard-page__kpi-label">Con orden</p>
                            <p class="cursos-dashboard-page__kpi-value">{{ number_format($stats['bandeja_en_curso']) }}</p>
                        </a>
                    @else
                        <div class="card cursos-dashboard-page__kpi" style="border-left-color:#0f172a;">
                            <p class="cursos-dashboard-page__kpi-label">En bandeja</p>
                            <p class="cursos-dashboard-page__kpi-value">{{ number_format($stats['bandeja_total']) }}</p>
                        </div>
                        <div class="card cursos-dashboard-page__kpi" style="border-left-color:#ea580c;">
                            <p class="cursos-dashboard-page__kpi-label">Por comprar</p>
                            <p class="cursos-dashboard-page__kpi-value">{{ number_format($stats['bandeja_pendiente']) }}</p>
                        </div>
                        <div class="card cursos-dashboard-page__kpi" style="border-left-color:#1984c7;">
                            <p class="cursos-dashboard-page__kpi-label">Con orden</p>
                            <p class="cursos-dashboard-page__kpi-value">{{ number_format($stats['bandeja_en_curso']) }}</p>
                        </div>
                    @endcan

                    <div class="card cursos-dashboard-page__kpi" style="border-left-color:#15803d;">
                        <p class="cursos-dashboard-page__kpi-label">Cerrado</p>
                        <p class="cursos-dashboard-page__kpi-value">{{ number_format($stats['completadas_periodo']) }}</p>
                    </div>
                </div>

                <div class="gh-area-dashboard-page__charts compras-dashboard-page__charts">
                    <div class="gh-area-dashboard-page__charts-row gh-area-dashboard-page__charts-row--top compras-dashboard-page__charts-row--two">
                        <div class="panel">
                            <div class="panel__header">
                                <h3 class="panel-title">Tendencia mensual ({{ $filters['year'] }})</h3>
                            </div>
                            <div class="panel__body">
                                <div id="trendChart" class="cursos-dashboard-page__chart"></div>
                            </div>
                        </div>
                        <div class="panel">
                            <div class="panel__header">
                                <h3 class="panel-title">Estado solicitudes</h3>
                            </div>
                            <div class="panel__body">
                                <div id="purchaseStatusChart" class="cursos-dashboard-page__chart"></div>
                            </div>
                        </div>
                    </div>
                    <div class="gh-area-dashboard-page__charts-row gh-area-dashboard-page__charts-row--bottom compras-dashboard-page__charts-row--three">
                        <div class="panel">
                            <div class="panel__header">
                                <h3 class="panel-title">Bandeja por estado</h3>
                            </div>
                            <div class="panel__body">
                                <div id="bandejaStatusChart" class="cursos-dashboard-page__chart"></div>
                            </div>
                        </div>
                        <div class="panel">
                            <div class="panel__header">
                                <h3 class="panel-title">Bandeja por tipo</h3>
                            </div>
                            <div class="panel__body">
                                <div id="tipoBandejaChart" class="cursos-dashboard-page__chart"></div>
                            </div>
                        </div>
                        <div class="panel">
                            <div class="panel__header">
                                <h3 class="panel-title">Top 5 áreas solicitantes</h3>
                            </div>
                            <div class="panel__body">
                                <div id="areaChart" class="cursos-dashboard-page__chart"></div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="cursos-dashboard-page__kpi-grid gh-area-dashboard-page__kpi-grid compras-dashboard-page__kpi-grid compras-dashboard-page__kpi-grid--ordenes">
                    <div class="card cursos-dashboard-page__kpi" style="border-left-color:#15803d;">
                        <p class="cursos-dashboard-page__kpi-label">Compras aprobadas</p>
                        <p class="cursos-dashboard-page__kpi-value">{{ number_format($orderStats['aprobadas']) }}</p>
                    </div>
                    <div class="card cursos-dashboard-page__kpi" style="border-left-color:#0369a1;">
                        <p class="cursos-dashboard-page__kpi-label">Compras realizadas</p>
                        <p class="cursos-dashboard-page__kpi-value">{{ number_format($orderStats['realizadas']) }}</p>
                    </div>
                    <div class="card cursos-dashboard-page__kpi" style="border-left-color:#7c3aed;">
                        <p class="cursos-dashboard-page__kpi-label">Aprobadas / realizadas</p>
                        <p class="cursos-dashboard-page__kpi-value">{{ $orderStats['relacion_display'] }}</p>
                        <p class="formacion-dashboard-page__kpi-hint">{{ $orderStats['relacion_pct_display'] }}</p>
                    </div>
                    <div class="card cursos-dashboard-page__kpi" style="border-left-color:#ca8a04;">
                        <p class="cursos-dashboard-page__kpi-label">Días a OC (autorización)</p>
                        <p class="cursos-dashboard-page__kpi-value">{{ $orderStats['ciclo_aprobacion_display'] }}</p>
                    </div>
                    <div class="card cursos-dashboard-page__kpi" style="border-left-color:#0f172a;">
                        <p class="cursos-dashboard-page__kpi-label">Días a OC (solicitud)</p>
                        <p class="cursos-dashboard-page__kpi-value">{{ $orderStats['ciclo_solicitud_display'] }}</p>
                    </div>
                    <div class="card cursos-dashboard-page__kpi" style="border-left-color:#0d9488;">
                        <p class="cursos-dashboard-page__kpi-label">Evaluación promedio</p>
                        <p class="cursos-dashboard-page__kpi-value">{{ $orderStats['evaluacion_promedio_display'] }}</p>
                    </div>
                    <div class="card cursos-dashboard-page__kpi" style="border-left-color:#be123c;">
                        <p class="cursos-dashboard-page__kpi-label">Cobertura evaluación</p>
                        <p class="cursos-dashboard-page__kpi-value">{{ $orderStats['cobertura_display'] }}</p>
                        <p class="formacion-dashboard-page__kpi-hint">{{ $orderStats['cobertura_pct_display'] }}</p>
                    </div>
                </div>

                <div class="gh-area-dashboard-page__charts compras-dashboard-page__charts">
                    <div class="gh-area-dashboard-page__charts-row gh-area-dashboard-page__charts-row--top compras-dashboard-page__charts-row--two">
                        <div class="panel">
                            <div class="panel__header">
                                <h3 class="panel-title">Órdenes emitidas ({{ $filters['year'] }})</h3>
                            </div>
                            <div class="panel__body">
                                <div id="ordersTrendChart" class="cursos-dashboard-page__chart"></div>
                            </div>
                        </div>
                        <div class="panel">
                            <div class="panel__header">
                                <h3 class="panel-title">Días promedio a la OC</h3>
                            </div>
                            <div class="panel__body">
                                <div id="ordersCycleChart" class="cursos-dashboard-page__chart"></div>
                            </div>
                        </div>
                    </div>
                    <div class="gh-area-dashboard-page__charts-row gh-area-dashboard-page__charts-row--bottom compras-dashboard-page__charts-row--two">
                        <div class="panel">
                            <div class="panel__header">
                                <h3 class="panel-title">Evaluación promedio %</h3>
                            </div>
                            <div class="panel__body">
                                <div id="ordersEvaluationChart" class="cursos-dashboard-page__chart"></div>
                            </div>
                        </div>
                        <div class="panel">
                            <div class="panel__header">
                                <h3 class="panel-title">Cobertura de evaluación</h3>
                            </div>
                            <div class="panel__body">
                                <div id="ordersCoverageChart" class="cursos-dashboard-page__chart"></div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
