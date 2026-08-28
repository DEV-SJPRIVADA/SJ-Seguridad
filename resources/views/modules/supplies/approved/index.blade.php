<x-app-layout>
    <x-slot name="header">
        @include('modules.supplies.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            @if (session('warning'))
                <div class="alert alert--warning block-spaced">{{ session('warning') }}</div>
            @endif

            <div class="panel block-spaced">
                <div class="panel__header">
                    <h3 class="panel-title">Insumos aprobados</h3>
                    <p class="panel-text">Solicitudes aprobadas por calidad. Descargue el reporte FO-AD-44 por solicitud</p>
                    <div style="margin-top:0.5rem;">
                        <x-export-excel route="{{ route('supplies.approved.export-all', ['module' => $module, ...request()->query()]) }}" label="Exportar lista a Excel" />
                    </div>
                </div>

                <div class="panel__body">
                    <form method="GET" action="{{ route('supplies.approved.index', ['module' => $module]) }}" class="approved-filters bottom-spaced">
                        <x-searchable-select
                            id="approved-filter-sede"
                            name="sede_id"
                            :options="collect($sites)->map(fn($s) => ['value' => (string) $s->id, 'label' => $s->utilization . ' (' . $s->city . ')'])->all()"
                            :value="$filters['sede_id'] ?? ''"
                            placeholder="Todas las sedes"
                            searchPlaceholder="Buscar sede…"
                            class="approved-filters__control"
                        />

                        <input type="date" name="date_from" class="form-input approved-filters__control" value="{{ $filters['date_from'] ?? '' }}" title="Desde">

                        <input type="date" name="date_to" class="form-input approved-filters__control" value="{{ $filters['date_to'] ?? '' }}" title="Hasta">

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
                            class="approved-filters__control"
                            :allowClear="false"
                        />

                        <input type="text" name="requester" class="form-input approved-filters__control approved-filters__search" value="{{ $filters['requester'] ?? '' }}" placeholder="Solicitante">

                        <button type="submit" class="btn btn--secondary btn--sm">Filtrar</button>
                        <a href="{{ route('supplies.approved.index', ['module' => $module]) }}" class="btn btn--secondary btn--sm">Limpiar</a>
                    </form>

                    <table class="supply-table js-datatable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Solicitante</th>
                                <th>Área</th>
                                <th>Sede</th>
                                <th>Ítems</th>
                                <th>Exportación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($requests as $request)
                                <tr>
                                    <td>#{{ $request->id }}</td>
                                    <td><x-date-table :value="$request->created_at" datetime /></td>
                                    <td>{{ $request->user->name }}</td>
                                    <td>{{ config("access.areas.{$request->area_key}") }}</td>
                                    <td>{{ $request->site_utilization ?? '—' }}</td>
                                    <td>{{ $request->approved_items_count }}</td>
                                    <td>
                                        @if ($request->exported_at)
                                            <span class="status-pill status-pill--success">Exportada</span>
                                        @else
                                            <span class="status-pill status-pill--warning">Pendiente</span>
                                        @endif
                                    </td>
                                    <td class="table-actions">
                                        @if ($request->approved_items_count > 0)
                                            <a href="{{ route('supplies.approved.export', ['module' => $module, 'supply_request' => $request->id]) }}" class="btn btn--primary btn--sm">
                                                Descargar FO-AD-44
                                            </a>
                                        @else
                                            <span class="text-muted text-small">Sin ítems</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-muted">No hay solicitudes aprobadas con los filtros seleccionados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if ($requests->hasPages())
                        <div class="pagination-wrap">
                            {{ $requests->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        .approved-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }

        .approved-filters__control {
            min-height: var(--control-height-chrome);
            min-width: 0;
            padding: 0.35rem 0.65rem;
            font-size: var(--control-font-size);
            line-height: var(--control-line-height);
        }

        .approved-filters__search {
            flex: 1 1 160px;
            max-width: 220px;
        }

        .approved-filters select.approved-filters__control {
            flex: 0 1 150px;
            max-width: 180px;
        }

        .approved-filters input[type="date"].approved-filters__control {
            flex: 0 1 130px;
            width: 130px;
        }
    </style>
</x-app-layout>
