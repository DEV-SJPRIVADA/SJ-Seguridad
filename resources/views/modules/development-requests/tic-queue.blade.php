<x-app-layout>
    <x-slot name="header">
        @include('modules.development-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="dashboard-stat-grid bottom-spaced">
                <div class="panel panel--stat">
                    <p class="panel-text panel-text--compact">Recibidos (radicados)</p>
                    <p class="panel-title">{{ $kpis['recibidos'] }}</p>
                </div>
                <div class="panel panel--stat">
                    <p class="panel-text panel-text--compact">En curso</p>
                    <p class="panel-title">{{ $kpis['en_curso'] }}</p>
                </div>
                <div class="panel panel--stat">
                    <p class="panel-text panel-text--compact">Entregados / cerrados</p>
                    <p class="panel-title">{{ $kpis['entregados'] }}</p>
                </div>
                <div class="panel panel--stat">
                    <p class="panel-text panel-text--compact">Vencidos SLA analisis</p>
                    <p class="panel-title">{{ $kpis['vencidos_sla'] }}</p>
                </div>
            </div>

            @if (count($overdue) > 0)
                <div class="panel bottom-spaced">
                    <div class="panel__header">
                        <h3 class="panel-title">SLA analisis vencido</h3>
                        <p class="panel-text">Urgente 2d · Importante/Mejora 5d · Soporte 3d (desde radicacion).</p>
                    </div>
                    <div class="panel__body">
                        <ul>
                            @foreach (array_slice($overdue, 0, 10) as $item)
                                <li>
                                    <a href="{{ route('development-requests.show', ['module' => $module, 'development_request' => $item['id'], 'from' => 'tic_queue']) }}">
                                        {{ $item['code'] ?: '#'.$item['id'] }}
                                    </a>
                                    — {{ $item['title'] }}
                                    ({{ $item['days_open'] }}d / SLA {{ $item['sla_days'] }}d · {{ $item['priority'] }})
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <div class="panel">
                <div class="panel__header panel__header--compact">
                    <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap;">
                        <div>
                            <h3 class="panel-title">Bandeja TIC</h3>
                            <p class="panel-text panel-text--compact">Solicitudes radicadas y en curso. Abra el detalle para transicionar estado, UAT y chat.</p>
                        </div>
                        <x-export-excel route="{{ route('development-requests.tic-queue.export', ['module' => $module, ...request()->query()]) }}" />
                    </div>
                </div>
                <div class="panel__body">
                    <form method="GET" action="{{ route('development-requests.tic-queue', ['module' => $module]) }}" class="bottom-spaced" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(12rem,1fr));gap:.75rem;align-items:end;">
                        <div class="form-field">
                            <label class="form-label">Estado</label>
                            <x-searchable-select name="status" :options="$statusOptions" :value="$filters['status']" placeholder="Todos" :allowClear="true" />
                        </div>
                        <div class="form-field">
                            <label class="form-label">Area</label>
                            <x-searchable-select name="area_key" :options="$areaOptions" :value="$filters['area_key']" placeholder="Todas" :allowClear="true" />
                        </div>
                        <div class="form-field">
                            <label class="form-label">Prioridad</label>
                            <x-searchable-select name="priority" :options="$priorityOptions" :value="$filters['priority']" placeholder="Todas" :allowClear="true" />
                        </div>
                        <div>
                            <button type="submit" class="btn btn--secondary btn--sm">Filtrar</button>
                        </div>
                    </form>

                    <div class="data-table-wrap">
                        <table class="supply-table js-datatable" style="width:100%" data-dt-compact="true">
                            <thead>
                                <tr>
                                    <th>Codigo</th>
                                    <th>Titulo</th>
                                    <th>Estado</th>
                                    <th>Prioridad</th>
                                    <th>Solicitante</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($requests as $item)
                                    <tr>
                                        <td>{{ $item->code }}</td>
                                        <td>{{ $item->title }}</td>
                                        <td>{{ $item->estadoLabel() }}</td>
                                        <td>{{ $item->prioridadLabel() }}</td>
                                        <td>{{ $item->requester_name }}</td>
                                        <td>
                                            <a class="btn btn--secondary btn--sm" href="{{ route('development-requests.show', ['module' => $module, 'development_request' => $item, 'from' => 'tic_queue']) }}">Ver</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6">Sin items en bandeja.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
