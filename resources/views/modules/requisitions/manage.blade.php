<x-app-layout>
    <x-slot name="header">
        @include('modules.requisitions.partials.subnav', ['moduleLabel' => $moduleLabel, 'subTabs' => $subTabs])
    </x-slot>

    @php
        $hasActiveFilters = ($filters['q'] ?? '') !== '' || ($filters['status'] ?? '') !== '';
    @endphp

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Gestion de requisiciones</h3>
                    <p class="panel-text">Seguimiento centralizado para actualizacion de datos y cambios de estado.</p>
                </div>

                <div class="panel__body">
                    <div class="req-manage-filters">
                        <div class="req-manage-filters__head">
                            <h4 class="req-manage-filters__title">Filtros</h4>
                            <div class="req-manage-filters__actions">
                                <x-export-excel route="{{ route('requisitions.export', ['module' => $moduleKey, ...request()->query()]) }}" />
                                @if ($hasActiveFilters)
                                    <a href="{{ route('requisitions.manage', ['module' => $moduleKey]) }}" class="btn btn--secondary btn--sm">Limpiar filtros</a>
                                @endif
                            </div>
                        </div>

                        <div class="req-manage-filters__toolbar">
                            <form method="GET" id="manage-filters-form" class="req-manage-filters__search-col">
                                @if ($filters['status'] ?? '')
                                    <input type="hidden" name="status" value="{{ $filters['status'] }}">
                                @endif

                                <label class="req-manage-filters__label" for="manage-search-input">Buscar</label>
                                <div class="req-manage-filters__search-group">
                                    <input
                                        id="manage-search-input"
                                        type="search"
                                        name="q"
                                        class="form-input"
                                        value="{{ $filters['q'] }}"
                                        placeholder="Codigo, lider, cargo..."
                                    >
                                    <button type="submit" class="btn btn--primary">Buscar</button>
                                </div>
                            </form>

                            <div class="req-manage-filters__status-col">
                                <p class="req-manage-filters__status-label">Estado</p>
                                <div class="req-manage-filters__pills">
                                    <a
                                        href="{{ route('requisitions.manage', array_filter(['module' => $moduleKey, 'q' => $filters['q'] ?: null])) }}"
                                        class="req-manage-filters__pill {{ ($filters['status'] ?? '') === '' ? 'is-active' : '' }}"
                                    >Todos</a>
                                    @foreach ($statusLabels as $statusKey => $statusLabel)
                                        <a
                                            href="{{ route('requisitions.manage', array_filter([
                                                'module' => $moduleKey,
                                                'q' => $filters['q'] ?: null,
                                                'status' => $statusKey,
                                            ])) }}"
                                            class="req-manage-filters__pill status-pill--req-{{ $statusKey }} {{ ($filters['status'] ?? '') === $statusKey ? 'is-active' : '' }}"
                                        >{{ $statusLabel }}</a>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <p class="req-manage-filters__meta">
                            <strong>{{ number_format($requisitions->count()) }}</strong>
                            {{ $requisitions->count() === 1 ? 'requisicion encontrada' : 'requisiciones encontradas' }}
                            @if ($filters['status'] ?? '')
                                · Estado: <strong>{{ $statusLabels[$filters['status']] ?? $filters['status'] }}</strong>
                            @endif
                            @if ($filters['q'] ?? '')
                                · Busqueda servidor: <strong>{{ $filters['q'] }}</strong>
                            @endif
                            · Use la busqueda de la tabla para filtrar filas visibles
                        </p>
                    </div>

                    <div class="data-table-wrap">
                        <table
                            class="data-table js-datatable"
                            data-no-excel
                            data-order='[[1, "desc"]]'
                        >
                            <thead>
                                <tr>
                                    <th>Codigo</th>
                                    <th>Fecha</th>
                                    <th>Lider</th>
                                    <th>Cargo</th>
                                    <th>Cliente</th>
                                    <th>Ciudad</th>
                                    <th>Reemplaza a</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($requisitions as $requisition)
                                    <tr>
                                        <td>{{ $requisition->code }}</td>
                                        <td>{{ $requisition->request_date?->format('Y-m-d') }}</td>
                                        <td>{{ $requisition->leader_name }}</td>
                                        <td>{{ $requisition->position?->name }}</td>
                                        <td>{{ $requisition->client?->name }}</td>
                                        <td>{{ $requisition->city?->name }}</td>
                                        <td>{{ $requisition->replacement_name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="status-pill status-pill--req-{{ $requisition->status }}">
                                                {{ $statusLabels[$requisition->status] ?? $requisition->status }}
                                            </span>
                                        </td>
                                        <td class="table-actions">
                                            <a href="{{ route('requisitions.edit', ['module' => $moduleKey, 'requisition' => $requisition]) }}" class="btn btn--secondary btn--sm">Abrir</a>
                                            <a href="{{ route('requisitions.print', ['module' => $moduleKey, 'requisition' => $requisition]) }}" target="_blank" class="btn btn--secondary btn--sm" title="Previsualizar e Imprimir">
                                                Imprimir
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9">No hay requisiciones con los filtros seleccionados.</td>
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
