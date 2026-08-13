<x-app-layout>
    <x-slot name="header">
        @include('modules.requisitions.partials.subnav', ['moduleLabel' => $moduleLabel, 'subTabs' => $subTabs])
    </x-slot>

    @php
        $hasActiveFilters = ($filters['q'] ?? '') !== ''
            || ($filters['status'] ?? '') !== ''
            || ($filters['date_from'] ?? null)
            || ($filters['date_to'] ?? null);

        $hasDateFilters = ($filters['date_from'] ?? null) || ($filters['date_to'] ?? null);

        $manageQuery = fn (array $overrides = []) => array_filter([
            'q' => array_key_exists('q', $overrides) ? $overrides['q'] : ($filters['q'] ?: null),
            'status' => array_key_exists('status', $overrides) ? $overrides['status'] : ($filters['status'] ?: null),
            'date_from' => array_key_exists('date_from', $overrides) ? $overrides['date_from'] : ($filters['date_from'] ?: null),
            'date_to' => array_key_exists('date_to', $overrides) ? $overrides['date_to'] : ($filters['date_to'] ?: null),
        ], fn ($value) => $value !== null && $value !== '');
    @endphp

    <div class="page-section req-manage-page">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header panel__header--compact">
                    <h3 class="panel-title">Gestion de requisiciones</h3>
                    <p class="panel-text panel-text--compact">Seguimiento centralizado para actualizacion de datos y cambios de estado.</p>
                </div>

                <div class="panel__body req-manage-shell">
                    <details class="req-manage-shell__filters req-manage-filters req-manage-filters__panel" @if ($hasActiveFilters) open @endif>
                        <summary class="req-manage-filters__panel-toggle">
                            <span>Filtros</span>
                            @if ($hasActiveFilters)
                                <span class="req-manage-filters__panel-badge">Activos</span>
                            @endif
                        </summary>

                        <div class="req-manage-filters__panel-body">


                            <div class="req-manage-filters__toolbar">
                                <form method="GET" id="manage-filters-form" class="req-manage-filters__search-col">
                                    @if ($filters['status'] ?? '')
                                        <input type="hidden" name="status" value="{{ $filters['status'] }}">
                                    @endif

                                    <div class="req-manage-filters__query-row">
                                        <div class="req-manage-filters__query-field req-manage-filters__query-field--search">
                                            <label class="req-manage-filters__label" for="manage-search-input">Buscar</label>
                                            <input
                                                id="manage-search-input"
                                                type="search"
                                                name="q"
                                                class="form-input"
                                                value="{{ $filters['q'] }}"
                                                placeholder="Codigo, lider, cargo..."
                                            >
                                        </div>
                                        <div class="req-manage-filters__query-field req-manage-filters__query-field--date">
                                            <label class="req-manage-filters__label" for="manage-date-from">Desde</label>
                                            <input type="date" id="manage-date-from" name="date_from" class="form-input" value="{{ $filters['date_from'] ?? '' }}">
                                        </div>
                                        <div class="req-manage-filters__query-field req-manage-filters__query-field--date">
                                            <label class="req-manage-filters__label" for="manage-date-to">Hasta</label>
                                            <input type="date" id="manage-date-to" name="date_to" class="form-input" value="{{ $filters['date_to'] ?? '' }}">
                                        </div>
                                        <div class="req-manage-filters__query-submit">
                                            <span class="req-manage-filters__label req-manage-filters__label--spacer" aria-hidden="true">&nbsp;</span>
                                            <button type="submit" class="btn btn--primary">Buscar</button>
                                        </div>
                                    </div>
                                </form>

                                <div class="req-manage-filters__status-row">
                                    <p class="req-manage-filters__status-label">Estado</p>
                                    <div class="req-manage-filters__pills req-manage-filters__pills--scroll">
                                        <a
                                            href="{{ route('requisitions.manage', ['module' => $moduleKey, ...$manageQuery(['status' => null])]) }}"
                                            class="req-manage-filters__pill {{ ($filters['status'] ?? '') === '' ? 'is-active' : '' }}"
                                        >Todos</a>
                                        @foreach ($statusLabels as $statusKey => $statusLabel)
                                            <a
                                                href="{{ route('requisitions.manage', ['module' => $moduleKey, ...$manageQuery(['status' => $statusKey])]) }}"
                                                class="req-manage-filters__pill status-pill--req-{{ $statusKey }} {{ ($filters['status'] ?? '') === $statusKey ? 'is-active' : '' }}"
                                            >{{ $statusLabel }}</a>
                                        @endforeach
                                    </div>
                                    <div class="req-manage-filters__head">
                                        <div class="req-manage-filters__actions">
                                            <x-export-excel route="{{ route('requisitions.export', ['module' => $moduleKey, ...request()->query()]) }}" />
                                            @if ($hasActiveFilters)
                                                <a href="{{ route('requisitions.manage', ['module' => $moduleKey]) }}" class="btn btn--secondary btn--sm">Limpiar filtros</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <p class="req-manage-filters__meta req-manage-filters__meta--compact" title="{{ number_format($requisitions->count()) }} {{ $requisitions->count() === 1 ? 'requisicion encontrada' : 'requisiciones encontradas' }}">
                                <strong>{{ number_format($requisitions->count()) }}</strong>
                                {{ $requisitions->count() === 1 ? 'requisicion encontrada' : 'requisiciones encontradas' }}
                                @if ($filters['status'] ?? '')
                                    · Estado: <strong>{{ $statusLabels[$filters['status']] ?? $filters['status'] }}</strong>
                                @endif
                                @if ($filters['q'] ?? '')
                                    · Busqueda: <strong>{{ $filters['q'] }}</strong>
                                @endif
                                @if ($hasDateFilters)
                                    · Fecha solicitud:
                                    <strong>{{ $filters['date_from'] ?? '…' }}</strong>
                                    —
                                    <strong>{{ $filters['date_to'] ?? '…' }}</strong>
                                @endif
                                · El Excel exporta el detalle completo segun estos filtros
                            </p>
                        </div>
                    </details>

                    <div class="data-table-wrap req-manage-shell__table">
                        <table
                            class="data-table js-datatable"
                            style="width:100%"
                            data-order='[[0, "desc"]]'
                            data-dt-responsive="false"
                            data-dt-compact="true"
                            data-dt-body-scroll="true"
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
                                            <td><x-date-table :value="$requisition->request_date" /></td>
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
