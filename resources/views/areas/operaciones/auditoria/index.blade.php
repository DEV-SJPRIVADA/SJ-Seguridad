<x-app-layout>
    <x-slot name="header">
        @include('areas.operaciones.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Auditoria de indicadores</h3>
                    <p class="panel-text">Registro de acciones sobre capturas, periodos y configuracion.</p>
                </div>
                <div class="panel__body">
                    <form method="GET" class="form-stack" style="margin-bottom:1rem;">
                        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:0.75rem;">
                            <div>
                                <label class="form-label">Tipo de evento</label>
                                <select name="event_type" class="supply-input supply-select">
                                    <option value="">Todos</option>
                                    @foreach ($eventTypes as $type)
                                        <option value="{{ $type }}" @selected(request('event_type') === $type)>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Accion</label>
                                <select name="action" class="supply-input supply-select">
                                    <option value="">Todas</option>
                                    @foreach ($actions as $action)
                                        <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div style="display:flex; align-items:flex-end;">
                                <button type="submit" class="btn btn--secondary btn--sm">Filtrar</button>
                            </div>
                        </div>
                    </form>

                    <table class="supply-table js-datatable">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Evento</th>
                                <th>Accion</th>
                                <th>Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($logs as $log)
                                <tr>
                                    <td>{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                                    <td>{{ $log->user?->name ?? 'Sistema' }}</td>
                                    <td>{{ $log->event_type }}</td>
                                    <td>{{ $log->action }}</td>
                                    <td>{{ $log->reason }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
