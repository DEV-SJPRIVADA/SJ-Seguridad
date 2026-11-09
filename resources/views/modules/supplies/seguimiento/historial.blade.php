@php
    $filterQuery = array_filter([
        'year' => $filters['year'],
        'area_key' => $filters['area_key'],
        'sede_id' => $filters['sede_id'],
        'product' => $filters['product'],
    ], fn ($value) => $value !== null && $value !== '');
    $colCount = 1 + (count($monthLabels) * 3) + 3;
@endphp

<x-app-layout>
    <x-slot name="header">
        @include('modules.supplies.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section purchase-requests-page purchase-requests-page--list req-manage-page supply-seg-page">
        <div class="app-container">
            <div class="page-header-inner purchase-requests-page__intro">
                <div class="pur-req-list__toolbar">
                    <div>
                        <h2 class="page-title">Historial de suministros</h2>
                        <p class="page-subtitle">Insumos autorizados por Calidad en {{ $filters['year'] }}. Inventario (último declarado), solicitud y autorizado por mes. Sin precios.</p>
                    </div>
                    <div class="pur-req-detail__toolbar-actions">
                        <x-export-excel
                            route="{{ route('supplies.seguimiento.historial.export', array_merge(['module' => $module], $filterQuery)) }}"
                            label=""
                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                            title="Exportar Excel"
                            aria-label="Exportar Excel"
                        />
                    </div>
                </div>
            </div>

            <div class="panel purchase-requests-page__panel">
                <div class="panel__header panel__header--compact">
                    <div class="pur-req-list__header-row">
                        <div>
                            <h3 class="panel-title">Productos × meses</h3>
                            <p class="panel-text panel-text--compact">
                                {{ count($matrix['rows']) }}
                                {{ count($matrix['rows']) === 1 ? 'producto' : 'productos' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="panel__body req-manage-shell">
                    @include('modules.supplies.seguimiento.partials.filters', [
                        'clearRoute' => 'supplies.seguimiento.historial',
                    ])

                    {{-- Matriz con 3 columnas por mes; el scroll queda dentro del panel. --}}
                    <div class="req-manage-shell__table">
                        <div class="data-table-wrap req-manage-table-scroll supply-seg-page__matrix-wrap">
                            <table class="data-table supply-seg-page__matrix">
                                <thead>
                                    <tr>
                                        <th rowspan="2" class="supply-seg-page__sticky">Producto</th>
                                        @foreach ($monthLabels as $month => $label)
                                            <th colspan="3" class="text-center supply-seg-page__month-group">{{ $label }}</th>
                                        @endforeach
                                        <th colspan="3" class="text-center supply-seg-page__month-group">Total</th>
                                    </tr>
                                    <tr>
                                        @foreach ($monthLabels as $month => $label)
                                            <th class="text-center supply-seg-page__sub" title="Inventario">Inv.</th>
                                            <th class="text-center supply-seg-page__sub" title="Solicitud">Sol.</th>
                                            <th class="text-center supply-seg-page__sub supply-seg-page__month-end" title="Autorizado">Aut.</th>
                                        @endforeach
                                        <th class="text-center supply-seg-page__sub" title="Último inventario">Inv.</th>
                                        <th class="text-center supply-seg-page__sub" title="Total solicitado">Sol.</th>
                                        <th class="text-center supply-seg-page__sub" title="Total autorizado">Aut.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($matrix['rows'] as $row)
                                        <tr class="{{ ($filters['product'] ?? null) === $row['key'] ? 'supply-seg-page__row--active' : '' }}">
                                            <td class="supply-seg-page__sticky">
                                                <a
                                                    href="{{ route('supplies.seguimiento.ordenes', array_merge(['module' => $module], $filterQuery, ['product' => $row['key']])) }}"
                                                    class="supply-seg-page__product-link"
                                                    title="Ver órdenes de {{ $row['name'] }}"
                                                >
                                                    <span class="supply-seg-page__product-name">{{ $row['name'] }}</span>
                                                    @if ($row['is_custom'])
                                                        <span class="badge badge--muted">Fuera de catálogo</span>
                                                    @endif
                                                </a>
                                            </td>
                                            @foreach ($monthLabels as $month => $label)
                                                @php
                                                    $cell = $row['months'][$month];
                                                    $empty = $cell['requested'] === 0 && $cell['authorized'] === 0 && $cell['inventory'] === null;
                                                @endphp
                                                <td class="text-center">{{ $empty ? '—' : ($cell['inventory'] ?? '—') }}</td>
                                                <td class="text-center">{{ $empty ? '—' : $cell['requested'] }}</td>
                                                <td class="text-center supply-seg-page__month-end">{{ $empty ? '—' : $cell['authorized'] }}</td>
                                            @endforeach
                                            <td class="text-center">{{ $row['year_inventory'] ?? '—' }}</td>
                                            <td class="text-center">{{ $row['year_requested'] }}</td>
                                            <td class="text-center">{{ $row['year_authorized'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $colCount }}" class="text-center">No hay insumos autorizados por Calidad en el periodo.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                @if ($matrix['rows'] !== [])
                                    <tfoot>
                                        <tr>
                                            <th class="supply-seg-page__sticky">Totales</th>
                                            @foreach ($monthLabels as $month => $label)
                                                @php
                                                    $cell = $matrix['totals'][$month];
                                                    $empty = $cell['requested'] === 0 && $cell['authorized'] === 0;
                                                @endphp
                                                <th class="text-center">—</th>
                                                <th class="text-center">{{ $empty ? '—' : $cell['requested'] }}</th>
                                                <th class="text-center supply-seg-page__month-end">{{ $empty ? '—' : $cell['authorized'] }}</th>
                                            @endforeach
                                            <th class="text-center">—</th>
                                            <th class="text-center">{{ $matrix['year_totals']['requested'] }}</th>
                                            <th class="text-center">{{ $matrix['year_totals']['authorized'] }}</th>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
