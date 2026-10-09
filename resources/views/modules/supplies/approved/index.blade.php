@php
    $hasActiveFilters = ($filters['sede_id'] ?? '') !== ''
        || ($filters['date_from'] ?? '') !== ''
        || ($filters['date_to'] ?? '') !== ''
        || (($filters['export_status'] ?? 'all') !== 'all' && ($filters['export_status'] ?? '') !== '')
        || ($filters['requester'] ?? '') !== '';
@endphp

<x-app-layout>
    <x-slot name="header">
        @include('modules.supplies.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section purchase-requests-page purchase-requests-page--list req-manage-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success" role="status">{{ session('status') }}</div>
            @endif
            @if (session('warning'))
                <div class="alert alert--warning" role="alert">{{ session('warning') }}</div>
            @endif

            <div class="page-header-inner purchase-requests-page__intro">
                <div class="pur-req-list__toolbar">
                    <div>
                        <h2 class="page-title">Insumos aprobados</h2>
                        <p class="page-subtitle">Solicitudes aprobadas por Calidad. Descarga el reporte FO-AD-44 por solicitud.</p>
                    </div>
                    <div class="pur-req-detail__toolbar-actions">
                        <x-export-excel
                            route="{{ route('supplies.approved.export-all', ['module' => $module, ...request()->query()]) }}"
                            label=""
                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                            title="Exportar lista a Excel"
                            aria-label="Exportar lista a Excel"
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
                                {{ $requests->total() }}
                                {{ $requests->total() === 1 ? 'solicitud' : 'solicitudes' }}
                                segun filtros
                            </p>
                        </div>
                    </div>
                </div>

                <div class="panel__body req-manage-shell">
                    <details class="req-manage-shell__filters req-manage-filters req-manage-filters__panel pur-req-list__filters" @if ($hasActiveFilters) open @endif>
                        <summary class="req-manage-filters__panel-toggle">
                            <span>Filtros</span>
                            @if ($hasActiveFilters)
                                <span class="req-manage-filters__panel-badge">Activos</span>
                            @endif
                        </summary>

                        <div class="req-manage-filters__panel-body">
                            <form method="GET" action="{{ route('supplies.approved.index', ['module' => $module]) }}" class="req-manage-filters">
                                <div class="req-manage-filters__head">
                                    <div class="req-manage-filters__actions">
                                        @if ($hasActiveFilters)
                                            <a
                                                href="{{ route('supplies.approved.index', ['module' => $module]) }}"
                                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                                title="Limpiar filtros"
                                                aria-label="Limpiar filtros"
                                            >
                                                <x-lucide-x width="18" height="18" aria-hidden="true" />
                                            </a>
                                        @endif
                                        <button
                                            type="submit"
                                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                            title="Filtrar"
                                            aria-label="Filtrar"
                                        >
                                            <x-lucide-search width="18" height="18" aria-hidden="true" />
                                        </button>
                                    </div>
                                </div>

                                <div class="cursos-registros-page__filters">
                                    <div class="form-field">
                                        <label class="form-label" for="approved-filter-sede">Sede</label>
                                        <x-searchable-select
                                            id="approved-filter-sede"
                                            name="sede_id"
                                            :options="collect($sites)->map(fn ($s) => ['value' => (string) $s->id, 'label' => $s->utilization.' ('.$s->city.')'])->all()"
                                            :value="$filters['sede_id'] ?? ''"
                                            placeholder="Todas las sedes"
                                            searchPlaceholder="Buscar sede…"
                                            :allowClear="true"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="approved-filter-date-from">Desde</label>
                                        <input
                                            type="date"
                                            id="approved-filter-date-from"
                                            name="date_from"
                                            class="form-input"
                                            value="{{ $filters['date_from'] ?? '' }}"
                                        >
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="approved-filter-date-to">Hasta</label>
                                        <input
                                            type="date"
                                            id="approved-filter-date-to"
                                            name="date_to"
                                            class="form-input"
                                            value="{{ $filters['date_to'] ?? '' }}"
                                        >
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="approved-filter-export-status">Estado exportación</label>
                                        <x-searchable-select
                                            id="approved-filter-export-status"
                                            name="export_status"
                                            :options="[
                                                ['value' => 'all', 'label' => 'Todas'],
                                                ['value' => 'pending', 'label' => 'Pendientes'],
                                                ['value' => 'exported', 'label' => 'Exportadas'],
                                            ]"
                                            :value="$filters['export_status'] ?? 'all'"
                                            placeholder="Estado exportación"
                                            searchPlaceholder="Buscar estado…"
                                            :allowClear="false"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="approved-filter-requester">Solicitante</label>
                                        <input
                                            type="text"
                                            id="approved-filter-requester"
                                            name="requester"
                                            class="form-input"
                                            value="{{ $filters['requester'] ?? '' }}"
                                            placeholder="Nombre…"
                                        >
                                    </div>
                                </div>
                            </form>
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
                                    <th>Área</th>
                                    <th>Sede</th>
                                    <th>Items</th>
                                    <th>Exportación</th>
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
                                        <td class="text-center">{{ $request->approved_items_count }}</td>
                                        <td class="text-center">
                                            @if ($request->exported_at)
                                                <span class="status-pill status-pill--success">Exportada</span>
                                            @else
                                                <span class="status-pill status-pill--warning">Pendiente</span>
                                            @endif
                                        </td>
                                        <td class="text-center purchase-request-actions-col">
                                            <div class="purchase-request-row-actions">
                                                @if ($request->approved_items_count > 0)
                                                    <a
                                                        href="{{ route('supplies.approved.export', ['module' => $module, 'supply_request' => $request->id]) }}"
                                                        class="cursos-catalogo-page__icon-btn"
                                                        title="Descargar FO-AD-44"
                                                        aria-label="Descargar FO-AD-44"
                                                    >
                                                        <x-lucide-download width="16" height="16" aria-hidden="true" />
                                                    </a>
                                                @else
                                                    <span class="text-muted text-small">Sin items</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-muted">No hay solicitudes aprobadas con los filtros seleccionados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($requests->hasPages())
                        <div class="pagination-wrap">
                            {{ $requests->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
