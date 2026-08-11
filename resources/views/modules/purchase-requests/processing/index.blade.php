<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    @php
        $hasActiveFilters = ($filters['estado_compras'] ?? '') !== ''
            || ($filters['tipo'] ?? null)
            || ($filters['area_key'] ?? null)
            || ($filters['date_from'] ?? null)
            || ($filters['date_to'] ?? null);

        $hasDateFilters = ($filters['date_from'] ?? null) || ($filters['date_to'] ?? null);

        $bandejaQuery = fn (array $overrides = []) => array_filter([
            'estado_compras' => array_key_exists('estado_compras', $overrides) ? $overrides['estado_compras'] : ($filters['estado_compras'] ?: null),
            'tipo' => array_key_exists('tipo', $overrides) ? $overrides['tipo'] : ($filters['tipo'] ?: null),
            'area_key' => array_key_exists('area_key', $overrides) ? $overrides['area_key'] : ($filters['area_key'] ?: null),
            'date_from' => array_key_exists('date_from', $overrides) ? $overrides['date_from'] : ($filters['date_from'] ?: null),
            'date_to' => array_key_exists('date_to', $overrides) ? $overrides['date_to'] : ($filters['date_to'] ?: null),
        ], fn ($value) => $value !== null && $value !== '');
    @endphp

    <div class="page-section req-manage-page">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header panel__header--compact">
                    <h3 class="panel-title">Bandeja de compras</h3>
                    <p class="panel-text panel-text--compact">Cola unificada de solicitudes de compra aprobadas y suministros listos para procesamiento.</p>
                </div>

                <div class="panel__body req-manage-shell">
                    <details class="req-manage-shell__filters req-manage-filters req-manage-filters__panel req-manage-filters--bandeja" @if ($hasActiveFilters) open @endif>
                        <summary class="req-manage-filters__panel-toggle">
                            <span>Filtros</span>
                            @if ($hasActiveFilters)
                                <span class="req-manage-filters__panel-badge">Activos</span>
                            @endif
                        </summary>

                        <div class="req-manage-filters__panel-body">
                            <div class="req-manage-filters__head">
                                <div class="req-manage-filters__actions">
                                    @if ($hasActiveFilters)
                                        <a href="{{ route('purchase-requests.processing.index', ['module' => $module]) }}" class="btn btn--secondary btn--sm">Limpiar filtros</a>
                                    @endif
                                </div>
                            </div>

                            <div class="req-manage-filters__toolbar req-manage-filters__toolbar--bandeja">
                                <form method="GET" id="bandeja-filters-form" class="req-manage-filters__fields-col">
                                    @if ($filters['estado_compras'] ?? '')
                                        <input type="hidden" name="estado_compras" value="{{ $filters['estado_compras'] }}">
                                    @endif

                                    <div class="req-manage-filters__query-row req-manage-filters__row--bandeja">
                                        <div class="req-manage-filters__query-field req-manage-filters__field--date">
                                            <label class="req-manage-filters__label" for="bandeja-date-from">Desde</label>
                                            <input type="date" id="bandeja-date-from" name="date_from" class="form-input" value="{{ $filters['date_from'] ?? '' }}">
                                        </div>
                                        <div class="req-manage-filters__query-field req-manage-filters__field--date">
                                            <label class="req-manage-filters__label" for="bandeja-date-to">Hasta</label>
                                            <input type="date" id="bandeja-date-to" name="date_to" class="form-input" value="{{ $filters['date_to'] ?? '' }}">
                                        </div>
                                        <div class="req-manage-filters__query-field req-manage-filters__field--compact">
                                            <label class="req-manage-filters__label" for="bandeja-filter-area">Area solicitante</label>
                                            <select id="bandeja-filter-area" name="area_key" class="form-select">
                                                <option value="">Todas</option>
                                                @foreach ($areas as $areaKey => $areaLabel)
                                                    <option value="{{ $areaKey }}" @selected(($filters['area_key'] ?? '') === $areaKey)>{{ $areaLabel }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="req-manage-filters__query-field req-manage-filters__field--compact">
                                            <label class="req-manage-filters__label" for="bandeja-filter-tipo">Tipo</label>
                                            <select id="bandeja-filter-tipo" name="tipo" class="form-select">
                                                <option value="">Todos</option>
                                                <option value="purchase" @selected(($filters['tipo'] ?? '') === 'purchase')>Solicitud compra</option>
                                                <option value="supply" @selected(($filters['tipo'] ?? '') === 'supply')>Suministro</option>
                                            </select>
                                        </div>
                                    </div>
                                </form>

                                <div class="req-manage-filters__status-row req-manage-filters__status-col--right">
                                    <p class="req-manage-filters__status-label">Estado</p>
                                    <div class="req-manage-filters__pills req-manage-filters__pills--scroll">
                                        <a
                                            href="{{ route('purchase-requests.processing.index', ['module' => $module, ...$bandejaQuery(['estado_compras' => null])]) }}"
                                            class="req-manage-filters__pill {{ ($filters['estado_compras'] ?? '') === '' ? 'is-active' : '' }}"
                                        >Todos</a>
                                        @foreach ($estadosCompras as $estadoKey => $estadoLabel)
                                            <a
                                                href="{{ route('purchase-requests.processing.index', ['module' => $module, ...$bandejaQuery(['estado_compras' => $estadoKey])]) }}"
                                                class="req-manage-filters__pill status-pill--compras-{{ $estadoKey }} {{ ($filters['estado_compras'] ?? '') === $estadoKey ? 'is-active' : '' }}"
                                            >{{ $estadoLabel }}</a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <p class="req-manage-filters__meta req-manage-filters__meta--compact" title="{{ number_format($queueCount) }} {{ $queueCount === 1 ? 'elemento mostrado' : 'elementos mostrados' }}">
                                <strong>{{ number_format($queueCount) }}</strong>
                                {{ $queueCount === 1 ? 'elemento mostrado' : 'elementos mostrados' }}
                                @if ($queueTruncated ?? false)
                                    · de <strong>{{ number_format($queueTotalMatching) }}</strong> en total
                                    · Mostrando los {{ \App\Services\Compras\ComprasQueueService::DEFAULT_LIMIT }} mas recientes; use filtro de fechas para ver todos en un rango
                                @endif
                                @if ($filters['estado_compras'] ?? '')
                                    · Estado: <strong>{{ $estadosCompras[$filters['estado_compras']] ?? $filters['estado_compras'] }}</strong>
                                @endif
                                @if ($filters['tipo'] ?? null)
                                    · Tipo: <strong>{{ $filters['tipo'] === 'purchase' ? 'Solicitud compra' : 'Suministro' }}</strong>
                                @endif
                                @if ($filters['area_key'] ?? null)
                                    · Area: <strong>{{ $areas[$filters['area_key']] ?? $filters['area_key'] }}</strong>
                                @endif
                                @if ($hasDateFilters)
                                    · Fecha:
                                    <strong>{{ $filters['date_from'] ?? '…' }}</strong>
                                    —
                                    <strong>{{ $filters['date_to'] ?? '…' }}</strong>
                                @endif
                            </p>
                        </div>
                    </details>

                    <div class="data-table-wrap req-manage-shell__table">
                        <table
                            class="supply-table js-datatable"
                            style="width:100%"
                            data-dt-responsive="false"
                            data-dt-compact="true"
                            data-dt-body-scroll="true"
                        >
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Folio</th>
                                    <th>Fecha</th>
                                    <th>Solicitante</th>
                                    <th>Area</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($queueItems as $queueItem)
                                    <tr>
                                        <td>{{ $queueItem['tipo_label'] }}</td>
                                        <td>{{ $queueItem['folio'] }}</td>
                                        <td><x-date-table :value="$queueItem['fecha']" datetime /></td>
                                        <td>{{ $queueItem['solicitante'] ?? '—' }}</td>
                                        <td>{{ $queueItem['area'] ?? '—' }}</td>
                                        <td>
                                            <span class="status-pill status-pill--compras-{{ $queueItem['estado'] ?? 'pendiente' }}">
                                                {{ $queueItem['estado_label'] ?? '—' }}
                                            </span>
                                        </td>
                                        <td class="table-actions">
                                            @if ($queueItem['tipo'] === 'purchase')
                                                <a href="{{ route('purchase-requests.show', ['module' => $module, 'purchase_request' => $queueItem['id']]) }}" class="btn btn--secondary btn--sm">
                                                    Ver detalle
                                                </a>
                                                <a href="{{ route('purchase-requests.processing.purchase', ['module' => $module, 'purchase_request' => $queueItem['id']]) }}" class="btn btn--secondary btn--sm">
                                                    Procesar
                                                </a>
                                            @else
                                                <a href="{{ route('supplies.show', ['module' => $queueItem['model']->area_key, 'supply_request' => $queueItem['id']]) }}" class="btn btn--secondary btn--sm">
                                                    Ver detalle
                                                </a>
                                                <a href="{{ route('purchase-requests.processing.supply', ['module' => $module, 'supply_request' => $queueItem['id']]) }}" class="btn btn--secondary btn--sm">
                                                    Procesar
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-muted">No hay elementos en la bandeja con los filtros seleccionados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const filterForm = document.getElementById('bandeja-filters-form');
                if (!filterForm) {
                    return;
                }

                filterForm.querySelectorAll('select, input[type="date"]').forEach((input) => {
                    input.addEventListener('change', () => {
                        filterForm.submit();
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
