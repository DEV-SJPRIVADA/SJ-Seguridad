@php
    $filterQuery = array_filter([
        'year' => $filters['year'],
        'area_key' => $filters['area_key'],
        'sede_id' => $filters['sede_id'],
        'product' => $filters['product'],
    ], fn ($value) => $value !== null && $value !== '');
    $colCount = 1 + (count($monthLabels) * 3) + 3;
    $fmtQty = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.') ?: '0';
    $fmtMoney = fn ($n) => '$ '.number_format((float) $n, 2, ',', '.');
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
                        <h2 class="page-title">Gasto de suministros</h2>
                        <p class="page-subtitle">Lo solicitado (autorizado por Calidad) frente a lo comprado. El valor incluye IVA y solo cuenta órdenes recibidas. Lo solicitado se agrupa por mes del pedido; lo comprado, por mes de recibido.</p>
                    </div>
                    <div class="pur-req-detail__toolbar-actions">
                        <x-export-excel
                            route="{{ route('supplies.seguimiento.gasto.export', array_merge(['module' => $module], $filterQuery)) }}"
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
                            <h3 class="panel-title">Pedido vs compra · {{ $filters['year'] }}</h3>
                            <p class="panel-text panel-text--compact">
                                {{ count($matrix['rows']) }}
                                {{ count($matrix['rows']) === 1 ? 'producto' : 'productos' }}
                                · Valor total {{ $fmtMoney($matrix['year_totals']['value']) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="panel__body req-manage-shell">
                    @include('modules.supplies.seguimiento.partials.filters', [
                        'clearRoute' => 'supplies.seguimiento.gasto',
                    ])

                    {{-- Matriz con 3 columnas por mes (solicitud, comprado, valor). --}}
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
                                            <th class="text-center supply-seg-page__sub" title="Solicitud">Sol.</th>
                                            <th class="text-center supply-seg-page__sub" title="Comprado">Comp.</th>
                                            <th class="text-center supply-seg-page__sub supply-seg-page__month-end" title="Valor con IVA">Valor</th>
                                        @endforeach
                                        <th class="text-center supply-seg-page__sub" title="Total solicitado">Sol.</th>
                                        <th class="text-center supply-seg-page__sub" title="Total comprado">Comp.</th>
                                        <th class="text-center supply-seg-page__sub" title="Valor con IVA">Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($matrix['rows'] as $row)
                                        <tr>
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
                                                    $empty = $cell['requested'] === 0 && $cell['purchased'] == 0 && $cell['value'] == 0;
                                                @endphp
                                                <td class="text-center">{{ $empty ? '—' : $cell['requested'] }}</td>
                                                <td class="text-center">{{ $empty ? '—' : $fmtQty($cell['purchased']) }}</td>
                                                <td class="text-center supply-seg-page__month-end">{{ $empty ? '—' : $fmtMoney($cell['value']) }}</td>
                                            @endforeach
                                            <td class="text-center">{{ $row['year_requested'] }}</td>
                                            <td class="text-center">{{ $fmtQty($row['year_purchased']) }}</td>
                                            <td class="text-center">{{ $fmtMoney($row['year_value']) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $colCount }}" class="text-center">No hay gasto ni solicitudes autorizadas en el periodo.</td>
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
                                                    $empty = $cell['requested'] === 0 && $cell['purchased'] == 0 && $cell['value'] == 0;
                                                @endphp
                                                <th class="text-center">{{ $empty ? '—' : $cell['requested'] }}</th>
                                                <th class="text-center">{{ $empty ? '—' : $fmtQty($cell['purchased']) }}</th>
                                                <th class="text-center supply-seg-page__month-end">{{ $empty ? '—' : $fmtMoney($cell['value']) }}</th>
                                            @endforeach
                                            <th class="text-center">{{ $matrix['year_totals']['requested'] }}</th>
                                            <th class="text-center">{{ $fmtQty($matrix['year_totals']['purchased']) }}</th>
                                            <th class="text-center">{{ $fmtMoney($matrix['year_totals']['value']) }}</th>
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
