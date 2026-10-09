<x-app-layout>
    <x-slot name="header">
        @include('modules.supplies.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section purchase-requests-page purchase-requests-page--list req-manage-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success" role="status">{{ session('status') }}</div>
            @endif

            <div class="page-header-inner purchase-requests-page__intro">
                <div class="pur-req-list__toolbar">
                    <div>
                        <h2 class="page-title">Aprobación de insumos</h2>
                        <p class="page-subtitle">Solicitudes pendientes de revisión y ajuste de cantidades.</p>
                    </div>
                    <div class="pur-req-detail__toolbar-actions">
                        <x-export-excel
                            route="{{ route('supplies.approval.export', ['module' => $module]) }}"
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
                            <h3 class="panel-title">Listado</h3>
                            <p class="panel-text panel-text--compact">
                                {{ $requests->count() }}
                                {{ $requests->count() === 1 ? 'solicitud' : 'solicitudes' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="panel__body req-manage-shell">
                    <div class="data-table-wrap req-manage-shell__table">
                        <table
                            class="supply-table js-datatable"
                            data-dt-responsive="false"
                            data-dt-compact="true"
                            data-dt-body-scroll="true"
                        >
                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Fecha</th>
                                    <th>Solicitante</th>
                                    <th>Área</th>
                                    <th>Sede</th>
                                    <th>Items</th>
                                    <th>Estado</th>
                                    <th class="purchase-request-actions-col">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($requests as $request)
                                    <tr>
                                        <td class="text-center">
                                            <span class="pur-req-list__folio">{{ $request->folio() }}</span>
                                        </td>
                                        <td class="text-center"><x-date-table :value="$request->created_at" datetime /></td>
                                        <td>{{ $request->user->name }}</td>
                                        <td>{{ config("access.areas.{$request->area_key}") }}</td>
                                        <td>{{ $request->site_utilization ?? '—' }}</td>
                                        <td class="text-center">{{ $request->items->count() }}</td>
                                        <td class="text-center">
                                            <span class="status-pill status-pill--req-{{ $request->status }}">
                                                {{ $request->statusLabel() }}
                                            </span>
                                        </td>
                                        <td class="text-center purchase-request-actions-col">
                                            <div class="purchase-request-row-actions">
                                                @if ($request->status === 'pendiente_calidad')
                                                    <a
                                                        href="{{ route('supplies.approval.edit', ['module' => $module, 'supply_request' => $request->id]) }}"
                                                        class="cursos-catalogo-page__icon-btn"
                                                        title="Revisar"
                                                        aria-label="Revisar"
                                                    >
                                                        <x-lucide-clipboard-check width="16" height="16" aria-hidden="true" />
                                                    </a>
                                                @else
                                                    <span class="text-muted">Procesada</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-muted">No hay solicitudes pendientes de aprobación.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
