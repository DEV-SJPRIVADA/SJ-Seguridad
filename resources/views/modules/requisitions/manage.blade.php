<x-app-layout>
    <x-slot name="header">
        @include('modules.requisitions.partials.subnav', ['moduleLabel' => $moduleLabel, 'subTabs' => $subTabs])
    </x-slot>

    @php
        $hasActiveFilters = ($filters['q'] ?? '') !== ''
            || ($filters['status'] ?? '') !== ''
            || ($filters['date_from'] ?? null)
            || ($filters['date_to'] ?? null);
    @endphp

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header" style="display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;">
                    <div>
                        <h3 class="panel-title">Gestion de requisiciones</h3>
                        <p class="panel-text">Seguimiento centralizado para actualizacion de datos y cambios de estado.</p>
                    </div>
                    <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
                        <x-export-excel route="{{ route('requisitions.export', ['module' => $moduleKey, ...request()->query()]) }}" />
                        @if ($hasActiveFilters)
                            <a href="{{ route('requisitions.manage', ['module' => $moduleKey]) }}" class="btn btn--secondary">Limpiar filtros</a>
                        @endif
                    </div>
                </div>

                <div class="panel__body">
                    <form method="GET" class="permission-filter-bar permission-filter-bar--manage bottom-spaced">
                        <input
                            type="search"
                            name="q"
                            class="form-input permission-filter-bar__search"
                            value="{{ $filters['q'] }}"
                            placeholder="Codigo, lider, cargo..."
                        >
                        <input
                            type="date"
                            name="date_from"
                            class="form-input permission-filter-bar__select"
                            value="{{ $filters['date_from'] ?? '' }}"
                            title="Fecha inicio"
                            aria-label="Fecha inicio"
                        >
                        <input
                            type="date"
                            name="date_to"
                            class="form-input permission-filter-bar__select"
                            value="{{ $filters['date_to'] ?? '' }}"
                            title="Fecha fin"
                            aria-label="Fecha fin"
                        >
                        <select name="status" class="form-select permission-filter-bar__select">
                            <option value="">Estado: todos</option>
                            @foreach ($statusLabels as $statusKey => $statusLabel)
                                <option value="{{ $statusKey }}" @selected(($filters['status'] ?? '') === $statusKey)>{{ $statusLabel }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn--secondary permission-filter-bar__submit">Filtrar</button>
                    </form>

                    <p class="panel-text bottom-spaced" style="margin-top:0;">
                        <strong>{{ number_format($requisitions->count()) }}</strong>
                        {{ $requisitions->count() === 1 ? 'requisicion' : 'requisiciones' }}
                        @if ($filters['status'] ?? '')
                            · {{ $statusLabels[$filters['status']] ?? $filters['status'] }}
                        @endif
                        @if (($filters['date_from'] ?? null) || ($filters['date_to'] ?? null))
                            · {{ $filters['date_from'] ?? '…' }} — {{ $filters['date_to'] ?? '…' }}
                        @endif
                        · Excel segun filtros · busqueda de tabla para filas visibles
                    </p>

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
                                    <th>Reclutador</th>
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
                                            @if ($requisition->recruiter_id || filled($requisition->recruiter_name))
                                                {{ $requisition->displayRecruiterName() }}
                                            @else
                                                sin asignar
                                            @endif
                                        </td>
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
                                        <td colspan="10">No hay requisiciones con los filtros seleccionados.</td>
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
