<div class="indicadores-subpanel">
    <h4 class="indicadores-subpanel__title">Auditoria</h4>
    <p class="indicadores-subpanel__text">Registro de acciones sobre capturas, periodos y configuracion.</p>

    <form method="GET" action="{{ route('indicadores.admin.ajustes') }}" class="indicadores-inline-form">
        <input type="hidden" name="section" value="auditoria">
        <div class="indicadores-filter-bar">
            <div class="indicadores-field indicadores-field--md">
                <label class="form-label">Tipo de evento</label>
                <x-searchable-select
                    id="ajustes-audit-event-type"
                    name="event_type"
                    :options="collect($eventTypes)->map(fn($t) => ['value' => $t, 'label' => $t])->all()"
                    :value="request('event_type')"
                    placeholder="Todos los eventos"
                    searchPlaceholder="Buscar evento…"
                />
            </div>
            <div class="indicadores-field indicadores-field--md">
                <label class="form-label">Accion</label>
                <x-searchable-select
                    id="ajustes-audit-action"
                    name="action"
                    :options="collect($actions)->map(fn($ac) => ['value' => $ac, 'label' => $ac])->all()"
                    :value="request('action')"
                    placeholder="Todas las acciones"
                    searchPlaceholder="Buscar accion…"
                />
            </div>
            <div class="indicadores-field indicadores-field--action">
                <button type="submit" class="btn btn--secondary btn--sm">Filtrar</button>
            </div>
        </div>
    </form>

    <div class="indicadores-table-wrap" style="margin-top:1rem;">
        <table class="supply-table js-datatable indicadores-table indicadores-table--audit" data-server-pagination>
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
                        <td><x-date-table :value="$log->created_at" datetime /></td>
                        <td>{{ $log->user?->name ?? 'Sistema' }}</td>
                        <td>{{ $log->event_type }}</td>
                        <td>{{ $log->action }}</td>
                        <td class="indicadores-cell-wrap">{{ $log->reason }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if ($logs->hasPages())
        <div class="pagination-wrap indicadores-subpanel__pagination">
            {{ $logs->links() }}
        </div>
    @endif
</div>
