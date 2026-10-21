<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section purchase-requests-page purchase-requests-page--list req-manage-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success" role="alert">{{ session('status') }}</div>
            @endif

            @if (session('warning'))
                <div class="alert alert--warning" role="alert">{{ session('warning') }}</div>
            @endif

            <div class="panel purchase-requests-page__panel">
                <div class="panel__header panel__header--compact">
                    <div class="pur-req-list__header-row">
                        <div>
                            <h3 class="panel-title">Listado</h3>
                            <p class="panel-text panel-text--compact">
                                {{ $purchaseRequests->count() }}
                                {{ $purchaseRequests->count() === 1 ? 'solicitud' : 'solicitudes' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="panel__body req-manage-shell">
                    {{-- Filtros GET de Mis solicitudes: se aplican al cambiar un campo. --}}
                    <details class="req-manage-shell__filters req-manage-filters req-manage-filters__panel req-manage-filters--bandeja pur-req-list__filters" @if ($hasActiveFilters) open @endif>
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
                                        <a
                                            href="{{ route('purchase-requests.index', ['module' => $module]) }}"
                                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                            title="Limpiar filtros"
                                            aria-label="Limpiar filtros"
                                        >
                                            <x-lucide-x width="18" height="18" aria-hidden="true" />
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <div class="req-manage-filters__toolbar req-manage-filters__toolbar--bandeja">
                                <form method="GET" id="mis-solicitudes-filters-form" class="req-manage-filters__fields-col">
                                    <div class="req-manage-filters__query-row req-manage-filters__row--bandeja">
                                        <div class="req-manage-filters__query-field req-manage-filters__field--date">
                                            <label class="req-manage-filters__label" for="mis-solicitudes-date-from">Desde</label>
                                            <input type="date" id="mis-solicitudes-date-from" name="date_from" class="form-input" value="{{ $filters['date_from'] ?? '' }}">
                                        </div>
                                        <div class="req-manage-filters__query-field req-manage-filters__field--date">
                                            <label class="req-manage-filters__label" for="mis-solicitudes-date-to">Hasta</label>
                                            <input type="date" id="mis-solicitudes-date-to" name="date_to" class="form-input" value="{{ $filters['date_to'] ?? '' }}">
                                        </div>
                                        <div class="req-manage-filters__query-field req-manage-filters__field--compact">
                                            <label class="req-manage-filters__label" for="mis-solicitudes-filter-etapa">Estado</label>
                                            <x-searchable-select
                                                id="mis-solicitudes-filter-etapa"
                                                name="etapa"
                                                :options="$etapas"
                                                :value="$filters['etapa'] ?? ''"
                                                placeholder="Todos los estados"
                                                searchPlaceholder="Buscar estado…"
                                            />
                                        </div>
                                        <div class="req-manage-filters__query-field req-manage-filters__field--compact">
                                            <label class="req-manage-filters__label" for="mis-solicitudes-filter-urgente">Urgencia</label>
                                            <x-searchable-select
                                                id="mis-solicitudes-filter-urgente"
                                                name="urgente"
                                                :options="['1' => 'Urgente', '0' => 'No urgente']"
                                                :value="$filters['urgente'] ?? ''"
                                                placeholder="Todas"
                                                searchPlaceholder="Buscar…"
                                            />
                                        </div>
                                        <div class="req-manage-filters__query-field req-manage-filters__field--compact">
                                            <label class="req-manage-filters__label" for="mis-solicitudes-filter-area">Área</label>
                                            <x-searchable-select
                                                id="mis-solicitudes-filter-area"
                                                name="area_key"
                                                :options="$areas"
                                                :value="$filters['area_key'] ?? ''"
                                                placeholder="Todas las áreas"
                                                searchPlaceholder="Buscar área…"
                                            />
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </details>

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
                                    <th>Director aprobador</th>
                                    <th>Fecha de aprobación</th>
                                    <th>Productos</th>
                                    <th>Estado</th>
                                    <th class="purchase-request-actions-col">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchaseRequests as $purchaseRequest)
                                    <tr>
                                        <td class="text-center">
                                            <span class="pur-req-list__folio">{{ $purchaseRequest->folioVisible() }}</span>
                                        </td>
                                        <td class="text-center"><x-date-table :value="$purchaseRequest->fecha_solicitud ?? $purchaseRequest->created_at" /></td>
                                        <td>{{ $purchaseRequest->user?->name ?? '—' }}</td>
                                        <td>{{ $purchaseRequest->aprobador?->name ?? '—' }}</td>
                                        <td class="text-center">
                                            @if ($purchaseRequest->fecha_aprobacion)
                                                <x-date-table :value="$purchaseRequest->fecha_aprobacion" />
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $purchaseRequest->items->count() }}</td>
                                        <td class="text-center">
                                            {{-- Etapa común: decisión del director y avance de Compras en una sola pastilla. --}}
                                            <div class="pur-req-list__pills">
                                                <x-pedido-etapa :etapa="$purchaseRequest->etapa()" />
                                                @if ($purchaseRequest->urgente)
                                                    <span class="status-pill status-pill--warning">Urgente</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center purchase-request-actions-col">
                                            <div class="purchase-request-row-actions">
                                                <a
                                                    href="{{ route('purchase-requests.show', ['module' => $module, 'purchase_request' => $purchaseRequest->id, 'from' => 'mis_solicitudes']) }}"
                                                    class="btn btn--secondary btn--sm"
                                                >Ver detalle</a>
                                                @can('resubmit', $purchaseRequest)
                                                    <a
                                                        href="{{ route('purchase-requests.edit', ['module' => $module, 'purchase_request' => $purchaseRequest->id]) }}"
                                                        class="btn btn--secondary btn--sm purchase-request-resubmit-btn"
                                                        title="Reabrir y editar"
                                                        aria-label="Reabrir y editar"
                                                    >
                                                        <x-ri-issues-reopen-fill width="18" height="18" aria-hidden="true" />
                                                    </a>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-muted">
                                            @if ($hasActiveFilters)
                                                No hay solicitudes con los filtros seleccionados.
                                            @else
                                                No tienes solicitudes de compra registradas.
                                            @endif
                                        </td>
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
                const filterForm = document.getElementById('mis-solicitudes-filters-form');
                if (!filterForm) {
                    return;
                }

                let submitTimer = null;
                filterForm.addEventListener('change', () => {
                    if (submitTimer) {
                        clearTimeout(submitTimer);
                    }
                    submitTimer = setTimeout(() => {
                        filterForm.submit();
                    }, 20);
                });
            });
        </script>
    @endpush
</x-app-layout>
