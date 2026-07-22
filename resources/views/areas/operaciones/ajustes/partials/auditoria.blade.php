<div class="indicadores-subpanel">
    <h4 class="indicadores-subpanel__title">Auditoria</h4>
    <p class="indicadores-subpanel__text">Registro de acciones sobre capturas, periodos y configuracion.</p>

    <form method="GET" action="{{ route('indicadores.admin.ajustes') }}" class="indicadores-inline-form">
        <input type="hidden" name="section" value="auditoria">
        <div class="indicadores-filter-bar">
            <div class="indicadores-field indicadores-field--md">
                <label class="form-label">Tipo de evento</label>
                <select name="event_type" class="supply-input supply-select">
                    <option value="">Todos</option>
                    @foreach ($eventTypes as $type)
                        <option value="{{ $type }}" @selected(request('event_type') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="indicadores-field indicadores-field--md">
                <label class="form-label">Accion</label>
                <select name="action" class="supply-input supply-select">
                    <option value="">Todas</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                    @endforeach
                </select>
            </div>
            <div class="indicadores-field indicadores-field--action">
                <button type="submit" class="btn btn--secondary btn--sm">Filtrar</button>
            </div>
        </div>
    </form>

    <div class="indicadores-table-wrap" style="margin-top:1rem;">
        <table class="supply-table js-datatable indicadores-table indicadores-table--audit" data-no-excel data-server-pagination>
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
                        <td class="indicadores-cell-wrap">{{ $log->reason }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $logs->links() }}
</div>
