<x-app-layout>
    <x-slot name="header">
        @include('modules.development-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section development-requests-page">
        <div class="app-container">
            <div class="dashboard-stat-grid dashboard-stat-grid--requisition-kpis development-requests-kpis">
                <div class="req-dashboard-kpi req-dashboard-kpi--total">
                    <p class="req-dashboard-kpi__label">Recibidos</p>
                    <p class="req-dashboard-kpi__value">{{ $kpis['recibidos'] }}</p>
                </div>
                <div class="req-dashboard-kpi development-requests-kpi--course">
                    <p class="req-dashboard-kpi__label">En curso</p>
                    <p class="req-dashboard-kpi__value">{{ $kpis['en_curso'] }}</p>
                </div>
                <div class="req-dashboard-kpi development-requests-kpi--done">
                    <p class="req-dashboard-kpi__label">Entregados / cerrados</p>
                    <p class="req-dashboard-kpi__value">{{ $kpis['entregados'] }}</p>
                </div>
                <div class="req-dashboard-kpi development-requests-kpi--sla">
                    <p class="req-dashboard-kpi__label">Vencidos SLA analisis</p>
                    <p class="req-dashboard-kpi__value">{{ $kpis['vencidos_sla'] }}</p>
                </div>
            </div>

            @if (count($overdue) > 0)
                <div class="panel">
                    <div class="panel__header panel__header--compact">
                        <h3 class="panel-title">SLA analisis vencido</h3>
                        <p class="panel-text panel-text--compact">Urgente 2d · Importante/Mejora 5d · Soporte 3d (desde radicacion).</p>
                    </div>
                    <div class="panel__body panel__body--compact">
                        <ul class="development-requests-overdue-list">
                            @foreach (array_slice($overdue, 0, 10) as $item)
                                <li>
                                    <a href="{{ route('development-requests.show', ['module' => $module, 'development_request' => $item['id'], 'from' => 'tic_queue']) }}">
                                        {{ $item['code'] ?: '#'.$item['id'] }}
                                    </a>
                                    — {{ $item['title'] }}
                                    <span class="text-caption">({{ $item['days_open'] }}d / SLA {{ $item['sla_days'] }}d · {{ $item['priority'] }})</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <div class="panel development-requests-page__panel">
                <div class="panel__header panel__header--compact">
                    <div class="development-requests-page__header-row">
                        <div>
                            <h3 class="panel-title">Bandeja TIC</h3>
                            <p class="panel-text panel-text--compact">Solicitudes radicadas y en curso. Abra el detalle para transicionar estado, UAT y chat.</p>
                        </div>
                        <x-export-excel route="{{ route('development-requests.tic-queue.export', ['module' => $module, ...request()->query()]) }}" />
                    </div>
                </div>
                <div class="panel__body">
                    <form method="GET" action="{{ route('development-requests.tic-queue', ['module' => $module]) }}" class="development-requests-filters bottom-spaced">
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
                        <div class="development-requests-filters__actions">
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
