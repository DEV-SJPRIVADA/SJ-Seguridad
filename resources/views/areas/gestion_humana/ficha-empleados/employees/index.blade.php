<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.partials.ficha-empleados-subnav', ['subTabs' => $subTabs])
        <div class="app-container ficha-empleados-page__workspace-header">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">Empleados</h2>
                <p class="panel-text">Gestion humana — lista de espera y ficha de empleados contratados</p>
            </div>
        </div>
    </x-slot>

    @php
        $hasActiveFilters = ($filters['q'] ?? '') !== '';

        $entriesQuery = fn (array $overrides = []) => array_filter([
            'q' => array_key_exists('q', $overrides) ? $overrides['q'] : ($filters['q'] ?: null),
            'estado' => array_key_exists('estado', $overrides) ? $overrides['estado'] : ($filters['estado'] ?: null),
        ], fn ($value) => $value !== null && $value !== '');
    @endphp

    <div class="page-section ficha-empleados-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success ficha-empleados-page__alert">{{ session('status') }}</div>
            @endif

            <div class="panel ficha-empleados-panel">
                <div class="panel__body panel__body--compact">
                    <div class="req-manage-filters ficha-empleados-filters">
                        <div class="req-manage-filters__head">
                            <div class="panel-heading-row panel-heading-row--wrap">
                                <h3 class="panel-title">Listado de empleados</h3>
                                <p class="panel-text">Cedula, nombre o codigo de requisicion</p>
                            </div>
                            <div class="req-manage-filters__actions ficha-empleados-filters__actions">
                                <x-export-excel route="{{ route('gestion-humana.ficha-empleados.employees.export', $entriesQuery()) }}" />
                                @if ($hasActiveFilters)
                                    <a href="{{ route('gestion-humana.ficha-empleados.employees.index', ['estado' => $filters['estado']]) }}" class="btn btn--secondary btn--sm">Limpiar filtros</a>
                                @endif
                            </div>
                        </div>

                        <div class="req-manage-filters__toolbar ficha-empleados-filters__toolbar">
                            <form method="GET" class="ficha-empleados-filters__form req-manage-filters__search-col">
                                @if ($filters['estado'] ?? '')
                                    <input type="hidden" name="estado" value="{{ $filters['estado'] }}">
                                @endif
                                <label class="req-manage-filters__label" for="ficha-search-input">Buscar</label>
                                <div class="req-manage-filters__search-group ficha-empleados-filters__search-group">
                                    <input
                                        id="ficha-search-input"
                                        type="search"
                                        name="q"
                                        class="form-input"
                                        value="{{ $filters['q'] }}"
                                        placeholder="Cedula, nombre o codigo de requisicion"
                                    >
                                    <button type="submit" class="btn btn--primary">Buscar</button>
                                </div>
                            </form>

                            <div class="req-manage-filters__status-col ficha-empleados-filters__status-col">
                                <p class="req-manage-filters__status-label">Estado</p>
                                <div class="req-manage-filters__pills">
                                    @foreach ($estadoLabels as $estadoKey => $estadoLabel)
                                        <a
                                            href="{{ route('gestion-humana.ficha-empleados.employees.index', $entriesQuery(['estado' => $estadoKey])) }}"
                                            class="req-manage-filters__pill {{ $estadoKey === 'en_ficha' ? 'status-pill--success' : 'status-pill--warning' }} {{ ($filters['estado'] ?? 'pendientes') === $estadoKey ? 'is-active' : '' }}"
                                        >{{ $estadoLabel }}</a>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <p class="req-manage-filters__meta ficha-empleados-filters__meta">
                            <strong>{{ number_format($entries->count()) }}</strong>
                            {{ $entries->count() === 1 ? 'registro' : 'registros' }}
                            @if ($filters['q'] ?? '')
                                · Busqueda: <strong>{{ $filters['q'] }}</strong>
                            @endif
                        </p>
                    </div>

                    <div class="data-table-wrap ficha-empleados-page__table-wrap">
                        <table class="data-table js-datatable" data-dt-responsive="false" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Codigo requisicion</th>
                                    <th>Cedula</th>
                                    <th>Nombre completo</th>
                                    <th>Cargo</th>
                                    <th>Cliente</th>
                                    <th>Ciudad</th>
                                    <th>Fecha contratacion</th>
                                    @if (($filters['estado'] ?? 'pendientes') === 'en_ficha')
                                        <th>Agregado a ficha</th>
                                        <th>Agregado por</th>
                                    @else
                                        <th>Acciones</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($entries as $entry)
                                    <tr>
                                        <td>{{ $entry->requisitionCode() ?: '—' }}</td>
                                        <td>{{ $entry->hired_document }}</td>
                                        <td>{{ $entry->hired_full_name }}</td>
                                        <td>{{ $entry->positionName() ?: '—' }}</td>
                                        <td>{{ $entry->clientName() ?: '—' }}</td>
                                        <td>{{ $entry->cityName() ?: '—' }}</td>
                                        <td><x-date-table :value="$entry->requisition?->hiring_date" /></td>
                                        @if ($entry->moved_to_ficha_at !== null)
                                            <td><x-date-table :value="$entry->moved_to_ficha_at" datetime /></td>
                                            <td>{{ $entry->movedBy?->name ?: '—' }}</td>
                                        @else
                                            <td class="table-actions">
                                                @if ($canManage)
                                                    <form
                                                        method="POST"
                                                        action="{{ route('gestion-humana.ficha-empleados.employees.promote', $entry) }}"
                                                        class="js-promote-ficha-entry"
                                                        data-confirm-title="Agregar a ficha empleados"
                                                        data-confirm-text="¿Agregar a {{ $entry->hired_full_name }} a Ficha empleados? El registro dejara de aparecer en Pendientes."
                                                    >
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn--primary btn--sm">Agregar a ficha empleados</button>
                                                    </form>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">No hay registros para este filtro.</td>
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
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof Swal === 'undefined') {
                    return;
                }

                document.querySelectorAll('.js-promote-ficha-entry').forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (form.dataset.confirmed === '1') {
                            return;
                        }

                        event.preventDefault();

                        Swal.fire({
                            icon: 'question',
                            title: form.dataset.confirmTitle || 'Confirmar accion',
                            text: form.dataset.confirmText || '¿Continuar?',
                            showCancelButton: true,
                            confirmButtonText: 'Agregar',
                            cancelButtonText: 'Cancelar',
                        }).then(function (result) {
                            if (result.isConfirmed) {
                                form.dataset.confirmed = '1';
                                form.submit();
                            }
                        });
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
