<x-app-layout>
    @php
        $hasActiveFilters = request()->filled('module')
            || request()->filled('area')
            || request()->filled('event_type')
            || request()->filled('action')
            || request()->filled('user_id')
            || request()->filled('date_from')
            || request()->filled('date_to')
            || request()->boolean('show_info');
    @endphp

    <div class="page-section audit-page req-manage-page">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header panel__header--compact">
                    <h3 class="panel-title">Auditoria del sistema</h3>
                    <p class="panel-text panel-text--compact">
                        Registro central de acciones por modulo, area y usuario. Opciones de filtro basadas en los ultimos {{ $lookbackDays }} dias.
                    </p>
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
                            <div class="req-manage-filters__head">
                                <div class="req-manage-filters__actions">
                                    @if ($hasActiveFilters)
                                        <a href="{{ route('admin.audit.index') }}" class="btn btn--secondary btn--sm">Limpiar filtros</a>
                                    @endif
                                </div>
                            </div>

                            <form method="GET" action="{{ route('admin.audit.index') }}" class="req-manage-filters__toolbar">
                                <div class="req-manage-filters__query-row audit-filters__query-row">
                                    <div class="req-manage-filters__query-field audit-filters__field">
                                        <label class="req-manage-filters__label" for="audit-module">Modulo</label>
                                        <x-searchable-select
                                            id="audit-module"
                                            name="module"
                                            :options="collect($modules)->map(fn($m) => ['value' => $m, 'label' => $moduleLabels[$m] ?? $m])->all()"
                                            :value="request('module')"
                                            placeholder="Todos los modulos"
                                            searchPlaceholder="Buscar modulo…"
                                        />
                                    </div>
                                    <div class="req-manage-filters__query-field audit-filters__field">
                                        <label class="req-manage-filters__label" for="audit-area">Area</label>
                                        <x-searchable-select
                                            id="audit-area"
                                            name="area"
                                            :options="collect($areas)->map(fn($a) => ['value' => $a, 'label' => $areaLabels[$a] ?? $a])->all()"
                                            :value="request('area')"
                                            placeholder="Todas las areas"
                                            searchPlaceholder="Buscar area…"
                                        />
                                    </div>
                                    <div class="req-manage-filters__query-field audit-filters__field">
                                        <label class="req-manage-filters__label" for="audit-event-type">Evento</label>
                                        <x-searchable-select
                                            id="audit-event-type"
                                            name="event_type"
                                            :options="collect($eventTypes)->map(fn($t) => ['value' => $t, 'label' => $t])->all()"
                                            :value="request('event_type')"
                                            placeholder="Todos los eventos"
                                            searchPlaceholder="Buscar evento…"
                                        />
                                    </div>
                                    <div class="req-manage-filters__query-field audit-filters__field">
                                        <label class="req-manage-filters__label" for="audit-action">Accion</label>
                                        <x-searchable-select
                                            id="audit-action"
                                            name="action"
                                            :options="collect($actions)->map(fn($ac) => ['value' => $ac, 'label' => $ac])->all()"
                                            :value="request('action')"
                                            placeholder="Todas las acciones"
                                            searchPlaceholder="Buscar accion…"
                                        />
                                    </div>
                                    <div class="req-manage-filters__query-field audit-filters__field audit-filters__field--wide">
                                        <label class="req-manage-filters__label" for="audit-user">Usuario</label>
                                        <x-searchable-select
                                            id="audit-user"
                                            name="user_id"
                                            :options="collect($users)->map(fn($u) => ['value' => (string) $u->id, 'label' => $u->name])->all()"
                                            :value="request('user_id')"
                                            placeholder="Todos los usuarios"
                                            searchPlaceholder="Buscar usuario…"
                                        />
                                    </div>
                                </div>

                                <div class="req-manage-filters__query-row audit-filters__query-row audit-filters__query-row--footer">
                                    <div class="req-manage-filters__query-field req-manage-filters__query-field--date">
                                        <label class="req-manage-filters__label" for="audit-date-from">Desde</label>
                                        <input id="audit-date-from" type="date" name="date_from" class="form-input" value="{{ $dateFrom }}">
                                    </div>
                                    <div class="req-manage-filters__query-field req-manage-filters__query-field--date">
                                        <label class="req-manage-filters__label" for="audit-date-to">Hasta</label>
                                        <input id="audit-date-to" type="date" name="date_to" class="form-input" value="{{ $dateTo }}">
                                    </div>
                                    <div class="req-manage-filters__query-field audit-filters__field audit-filters__field--checkbox">
                                        <span class="req-manage-filters__label req-manage-filters__label--spacer" aria-hidden="true">&nbsp;</span>
                                        <label class="audit-filters__checkbox" for="audit-show-info">
                                            <input
                                                id="audit-show-info"
                                                type="checkbox"
                                                name="show_info"
                                                value="1"
                                                @checked(request()->boolean('show_info'))
                                            >
                                            <span>Incluir consultas informativas</span>
                                        </label>
                                    </div>
                                    <div class="req-manage-filters__query-submit">
                                        <span class="req-manage-filters__label req-manage-filters__label--spacer" aria-hidden="true">&nbsp;</span>
                                        <button type="submit" class="btn btn--primary">Aplicar filtros</button>
                                    </div>
                                </div>
                            </form>

                            <p class="req-manage-filters__meta req-manage-filters__meta--compact">
                                <strong>{{ number_format($logs->total()) }}</strong>
                                {{ $logs->total() === 1 ? 'registro' : 'registros' }}
                                @if (request('module'))
                                    · Modulo: <strong>{{ $moduleLabels[request('module')] ?? request('module') }}</strong>
                                @endif
                                @if (request('area'))
                                    · Area: <strong>{{ $areaLabels[request('area')] ?? request('area') }}</strong>
                                @endif
                                @if (request('event_type'))
                                    · Evento: <strong>{{ request('event_type') }}</strong>
                                @endif
                                @if (request('action'))
                                    · Accion: <strong>{{ request('action') }}</strong>
                                @endif
                                @if (request('user_id'))
                                    · Usuario: <strong>{{ $users->firstWhere('id', (int) request('user_id'))?->name ?? request('user_id') }}</strong>
                                @endif
                                @if ($dateFrom || $dateTo)
                                    · Periodo:
                                    <strong>{{ $dateFrom ?: '…' }}</strong>
                                    —
                                    <strong>{{ $dateTo ?: '…' }}</strong>
                                @endif
                                @if (request()->boolean('show_info'))
                                    · <strong>Incluye consultas informativas</strong>
                                @endif
                            </p>
                        </div>
                    </details>

                    <div class="data-table-wrap req-manage-shell__table">
                        <table
                            class="data-table js-datatable audit-table"
                            style="width:100%"
                            data-order='[[0, "desc"]]'
                            data-dt-responsive="false"
                            data-dt-compact="true"
                            data-dt-body-scroll="true"
                            data-server-pagination
                        >
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Usuario</th>
                                    <th>Modulo</th>
                                    <th>Area</th>
                                    <th>Evento</th>
                                    <th>Accion</th>
                                    <th>Entidad</th>
                                    <th>Motivo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($logs as $log)
                                    <tr>
                                        <td data-order="{{ $log->created_at?->timestamp ?? 0 }}">
                                            <x-date-table :value="$log->created_at" datetime />
                                        </td>
                                        <td>{{ $log->user?->name ?? 'Sistema' }}</td>
                                        <td>{{ $moduleLabels[$log->module] ?? $log->module }}</td>
                                        <td>{{ $log->area ? ($areaLabels[$log->area] ?? $log->area) : '—' }}</td>
                                        <td>{{ $log->event_type }}</td>
                                        <td>{{ $log->action }}</td>
                                        <td>
                                            @if ($log->auditable_type)
                                                {{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $log->reason }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">Sin registros para los filtros seleccionados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($logs->hasPages())
                        <div class="req-manage-shell__pagination pagination-wrap">
                            {{ $logs->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
