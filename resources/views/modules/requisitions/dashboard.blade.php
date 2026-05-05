<x-app-layout>
    <x-slot name="header">
        @include('modules.requisitions.partials.subnav', ['moduleLabel' => $moduleLabel, 'subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="dashboard-stat-grid bottom-spaced">
                <article class="card card--muted">
                    <p class="text-caption">Total</p>
                    <p class="panel-title title-spaced">{{ $stats['total'] }}</p>
                    <p class="text-small text-muted">Solicitudes acumuladas del area.</p>
                </article>

                <article class="card card--muted">
                    <p class="text-caption">Solicitadas</p>
                    <p class="panel-title title-spaced">{{ $stats['solicitada'] }}</p>
                    <p class="text-small text-muted">Pendientes de toma por gestion humana.</p>
                </article>

                <article class="card card--info">
                    <p class="text-caption">En gestion</p>
                    <p class="panel-title title-spaced">{{ $stats['en_gestion'] }}</p>
                    <p class="text-small text-small--info">Procesos que ya estan siendo trabajados.</p>
                </article>

                <article class="card card--muted">
                    <p class="text-caption">Cerradas</p>
                    <p class="panel-title title-spaced">{{ $stats['contratado'] + $stats['cancelada'] }}</p>
                    <p class="text-small text-muted">Contratadas o canceladas.</p>
                </article>
            </div>

            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Ultimas requisiciones del area</h3>
                    <p class="panel-text">Vista rapida del estado actual de las solicitudes registradas en {{ $moduleLabel }}.</p>
                </div>

                <div class="panel__body">
                    <div class="data-table-wrap">
                        <table class="data-table js-datatable">
                            <thead>
                                <tr>
                                    <th>Codigo</th>
                                    <th>Fecha</th>
                                    <th>Cargo</th>
                                    <th>Cliente</th>
                                    <th>Reemplaza a</th>
                                    <th>Solicita</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($latestRequisitions as $requisition)
                                    <tr>
                                        <td>{{ $requisition->code }}</td>
                                        <td>{{ $requisition->request_date?->format('Y-m-d') }}</td>
                                        <td>{{ $requisition->position?->name }}</td>
                                        <td>{{ $requisition->client?->name }}</td>
                                        <td>{{ $requisition->replacement_name ?? 'N/A' }}</td>
                                        <td>{{ $requisition->requester?->name }}</td>
                                        <td>
                                            <span class="status-pill status-pill--req-{{ $requisition->status }}">
                                                {{ $statusLabels[$requisition->status] ?? $requisition->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
