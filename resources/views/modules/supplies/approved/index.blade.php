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
                </div>

                <div class="panel__body">
                    <form method="GET" action="{{ route('supplies.approved.index', ['module' => $module]) }}" class="approved-filters bottom-spaced">
                        <select name="sede_id" class="form-select approved-filters__control" title="Sede">
                            <option value="">Todas las sedes</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site->id }}" @selected((string) ($filters['sede_id'] ?? '') === (string) $site->id)>
                                    {{ $site->utilization }} ({{ $site->city }})
                                </option>
                            @endforeach
                        </select>

                        <input type="date" name="date_from" class="form-input approved-filters__control" value="{{ $filters['date_from'] ?? '' }}" title="Desde">

                        <input type="date" name="date_to" class="form-input approved-filters__control" value="{{ $filters['date_to'] ?? '' }}" title="Hasta">

                        <select name="export_status" class="form-select approved-filters__control" title="Estado de exportación">
                            <option value="all" @selected(($filters['export_status'] ?? 'all') === 'all')>Todas</option>
                            <option value="pending" @selected(($filters['export_status'] ?? '') === 'pending')>Pendientes</option>
                            <option value="exported" @selected(($filters['export_status'] ?? '') === 'exported')>Exportadas</option>
                        </select>

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
                                    <td>{{ $request->created_at->format('Y-m-d H:i') }}</td>
                                    <td>{{ $request->user->name }}</td>
                                    <td>{{ config("access.areas.{$request->area_key}") }}</td>
                                    <td>{{ $request->site_utilization ?? '—' }}</td>
                                    <td>{{ $request->approved_items_count }}</td>
                                    <td>
                                        @if ($request->exported_at)
                                            <span class="status-pill status-pill--success">Exportada</span>
                                            <span class="text-small text-muted">{{ $request->exported_at->format('Y-m-d') }}</span>
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

                    <div class="block-spaced">
                        {{ $requests->links() }}
                    </div>
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
            min-height: 38px;
            min-width: 0;
            padding: 0.4rem 0.65rem;
            font-size: 0.875rem;
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
