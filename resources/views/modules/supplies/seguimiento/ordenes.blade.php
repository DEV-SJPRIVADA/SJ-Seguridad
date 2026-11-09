@php
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
                        <h2 class="page-title">Órdenes de suministros</h2>
                        <p class="page-subtitle">
                            Órdenes de compra del producto
                            @if ($monthLabel)
                                en {{ $monthLabel }} de {{ $filters['year'] }}.
                            @else
                                en {{ $filters['year'] }}.
                            @endif
                            El valor incluye IVA. Las anuladas no se listan.
                        </p>
                    </div>
                </div>
            </div>

            <div class="panel purchase-requests-page__panel">
                <div class="panel__header panel__header--compact">
                    <div class="pur-req-list__header-row">
                        <div>
                            <h3 class="panel-title">
                                @if ($productName)
                                    {{ $productName }}
                                @else
                                    Órdenes de compra
                                @endif
                            </h3>
                            <p class="panel-text panel-text--compact">
                                @if ($productKey && $productName)
                                    {{ count($orders) }}
                                    {{ count($orders) === 1 ? 'orden' : 'órdenes' }}
                                @else
                                    Elija un producto en Historial, en Gasto o en el filtro.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <div class="panel__body req-manage-shell">
                    @include('modules.supplies.seguimiento.partials.filters', [
                        'clearRoute' => 'supplies.seguimiento.ordenes',
                        'filtersOpen' => ! ($filters['product'] ?? null),
                        'showMonthFilter' => true,
                    ])

                    {{-- Una sola tabla: OC del producto elegido. --}}
                    <div class="req-manage-shell__table">
                        <div class="data-table-wrap req-manage-table-scroll supply-seg-page__orders-wrap">
                            @if (! $productKey)
                                <p class="panel-text">Seleccione un producto para ver sus órdenes de compra.</p>
                            @elseif ($productName === null)
                                <p class="panel-text">No se encontró ese producto en el periodo filtrado.</p>
                            @else
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>OC</th>
                                            <th>Fecha</th>
                                            <th>Folio suministro</th>
                                            <th class="text-center">Cantidad</th>
                                            <th class="text-right">Valor</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($orders as $order)
                                            <tr>
                                                <td class="text-center">{{ $order['numero_oc'] }}</td>
                                                <td class="text-center">{{ $order['fecha'] }}</td>
                                                <td class="text-center">{{ $order['folio'] }}</td>
                                                <td class="text-center">{{ $fmtQty($order['cantidad']) }}</td>
                                                <td class="text-right">{{ $fmtMoney($order['valor']) }}</td>
                                                <td class="text-center">{{ $order['estado'] }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center">
                                                    @if ($filters['month'] ?? null)
                                                        Este producto no tiene órdenes de compra en el mes.
                                                    @else
                                                        Este producto no tiene órdenes de compra en el año.
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    @if ($orders !== [])
                                        <tfoot>
                                            <tr>
                                                <th>Totales</th>
                                                <th></th>
                                                <th></th>
                                                <th class="text-center">{{ $fmtQty($orderTotals['cantidad']) }}</th>
                                                <th class="text-right">{{ $fmtMoney($orderTotals['valor']) }}</th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    @endif
                                </table>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
