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
                        <h2 class="page-title">Mis solicitudes de insumos</h2>
                        <p class="page-subtitle">Historial de pedidos de tu usuario en el area seleccionada.</p>
                    </div>
                    <div class="pur-req-detail__toolbar-actions">
                        <x-export-excel
                            route="{{ route('supplies.export', ['module' => $module]) }}"
                            label=""
                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                            title="Exportar Excel"
                            aria-label="Exportar Excel"
                        />
                        <a
                            href="{{ route('supplies.create', ['module' => $module]) }}"
                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                            title="Nueva solicitud"
                            aria-label="Nueva solicitud"
                        >
                            <x-lucide-plus width="18" height="18" aria-hidden="true" />
                        </a>
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
                                    <th>Estado</th>
                                    <th>Items</th>
                                    <th class="purchase-request-actions-col">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($requests as $request)
                                    <tr>
                                        <td class="text-center">
                                            <span class="pur-req-list__folio">{{ $request->folio() }}</span>
                                        </td>
                                        <td class="text-center"><x-date-table :value="$request->created_at" /></td>
                                        <td class="text-center">
                                            <span class="status-pill status-pill--req-{{ $request->status }}">
                                                {{ $request->statusLabel() }}
                                            </span>
                                        </td>
                                        <td class="text-center">{{ $request->items->count() }}</td>
                                        <td class="text-center purchase-request-actions-col">
                                            <div class="purchase-request-row-actions">
                                                <a
                                                    href="{{ route('supplies.show', ['module' => $module, 'supply_request' => $request->id]) }}"
                                                    class="cursos-catalogo-page__icon-btn"
                                                    title="Ver detalle"
                                                    aria-label="Ver detalle"
                                                >
                                                    <x-lucide-eye width="16" height="16" aria-hidden="true" />
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted">No tienes solicitudes de insumos registradas.</td>
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
