<x-app-layout>
    <x-slot name="header">
        @include('modules.development-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section development-requests-page development-requests-page--queue">
        <div class="app-container">
            <div class="page-header-inner development-requests-page__intro">
                <h2 class="page-title">Bandeja TIC</h2>
                <p class="page-subtitle">
                    Solicitudes radicadas y en curso. Filtre, exporte y abra el detalle para transicionar estado, UAT y chat.
                </p>
            </div>

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
                <section class="dev-req-form__section dev-req-queue-sla">
                    <header class="dev-req-form__section-head">
                        <span class="dev-req-form__section-step dev-req-form__section-step--alert" aria-hidden="true">
                            <x-lucide-triangle-alert width="16" height="16" />
                        </span>
                        <div>
                            <h3 class="dev-req-form__section-title">SLA analisis vencido</h3>
                            <p class="dev-req-form__section-desc">Urgente 2d · Importante/Mejora 5d · Soporte 3d (desde radicacion).</p>
                        </div>
                    </header>

                    <ul class="dev-req-queue-sla__list">
                        @foreach (array_slice($overdue, 0, 10) as $item)
                            <li class="dev-req-queue-sla__item">
                                <div class="dev-req-queue-sla__main">
                                    <a
                                        class="dev-req-queue-sla__code"
                                        href="{{ route('development-requests.show', ['module' => $module, 'development_request' => $item['id'], 'from' => 'tic_queue']) }}"
                                    >
                                        {{ $item['code'] ?: '#'.$item['id'] }}
                                    </a>
                                    <span class="dev-req-queue-sla__title">{{ $item['title'] }}</span>
                                </div>
                                <span class="status-pill status-pill--danger">
                                    {{ $item['days_open'] }}d / SLA {{ $item['sla_days'] }}d · {{ $item['priority'] }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <div class="panel development-requests-page__panel">
                <div class="panel__header panel__header--compact">
                    <div class="development-requests-page__header-row">
                        <div>
                            <h3 class="panel-title">Listado operativo</h3>
                            <p class="panel-text panel-text--compact">Resultados segun filtros aplicados.</p>
                        </div>
                        <x-export-excel route="{{ route('development-requests.tic-queue.export', ['module' => $module, ...request()->query()]) }}" />
                    </div>
                </div>
                <div class="panel__body">
                    <form
                        method="GET"
                        action="{{ route('development-requests.tic-queue', ['module' => $module]) }}"
                        class="dev-req-queue-filters"
                    >
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
                        <div class="dev-req-queue-filters__actions">
                            <button type="submit" class="btn btn--secondary btn--sm">Filtrar</button>
                            <a href="{{ route('development-requests.tic-queue', ['module' => $module]) }}" class="btn btn--secondary btn--sm">Limpiar</a>
                        </div>
                    </form>

                    <div class="data-table-wrap">
                        <table class="supply-table js-datatable" data-dt-compact="true">
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
                                    @php
                                        $rowPill = match ($item->status) {
                                            \App\Models\DevelopmentRequest::STATUS_ENTREGADO,
                                            \App\Models\DevelopmentRequest::STATUS_CERRADO => 'status-pill--success',
                                            \App\Models\DevelopmentRequest::STATUS_RECHAZADO,
                                            \App\Models\DevelopmentRequest::STATUS_DEVUELTO => 'status-pill--danger',
                                            \App\Models\DevelopmentRequest::STATUS_PENDIENTE_APROBACION_LIDER,
                                            \App\Models\DevelopmentRequest::STATUS_EN_PRUEBAS => 'status-pill--warning',
                                            \App\Models\DevelopmentRequest::STATUS_BORRADOR => 'status-pill--muted',
                                            default => 'status-pill--info',
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="dev-req-queue-table__code">{{ $item->code }}</span>
                                        </td>
                                        <td>{{ $item->title }}</td>
                                        <td><span class="status-pill {{ $rowPill }}">{{ $item->estadoLabel() }}</span></td>
                                        <td>{{ $item->prioridadLabel() }}</td>
                                        <td>{{ $item->requester_name }}</td>
                                        <td>
                                            <a
                                                class="btn btn--secondary btn--sm"
                                                href="{{ route('development-requests.show', ['module' => $module, 'development_request' => $item, 'from' => 'tic_queue']) }}"
                                            >Ver</a>
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
