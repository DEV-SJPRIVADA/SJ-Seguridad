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

    <div class="page-section audit-page">
        <div class="app-container page-stack">
            <div class="panel audit-panel">
                <div class="panel__header audit-panel__header">
                    <div>
                        <p class="eyebrow">Administracion</p>
                        <h2 class="panel-title">Auditoria del sistema</h2>
                        <p class="panel-text audit-panel__intro">
                            Registro central de acciones por modulo, area y usuario.
                        </p>
                    </div>
                </div>

                <div class="panel__body panel__body--compact">
                    <form method="GET" action="{{ route('admin.audit.index') }}" class="audit-filters">
                        <div class="audit-filters__head">
                            <div class="audit-filters__head-copy">
                                <p class="audit-filters__title">Filtros de consulta</p>
                                <p class="audit-filters__hint">Opciones basadas en los ultimos {{ $lookbackDays }} dias</p>
                            </div>
                            @if ($hasActiveFilters)
                                <div class="audit-filters__head-actions">
                                    <a href="{{ route('admin.audit.index') }}" class="btn btn--secondary btn--sm">Limpiar filtros</a>
                                </div>
                            @endif
                        </div>

                        <div class="audit-filters__body">
                            <div class="audit-filters__row">
                                <div class="audit-filters__field">
                                    <label class="audit-filters__label" for="audit-module">Modulo</label>
                                    <select id="audit-module" name="module" class="form-select audit-filters__control">
                                        <option value="">Todos</option>
                                        @foreach ($modules as $module)
                                            <option value="{{ $module }}" @selected(request('module') === $module)>
                                                {{ $moduleLabels[$module] ?? $module }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="audit-filters__field">
                                    <label class="audit-filters__label" for="audit-area">Area</label>
                                    <select id="audit-area" name="area" class="form-select audit-filters__control">
                                        <option value="">Todas</option>
                                        @foreach ($areas as $area)
                                            <option value="{{ $area }}" @selected(request('area') === $area)>
                                                {{ $areaLabels[$area] ?? $area }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="audit-filters__field">
                                    <label class="audit-filters__label" for="audit-event-type">Evento</label>
                                    <select id="audit-event-type" name="event_type" class="form-select audit-filters__control">
                                        <option value="">Todos</option>
                                        @foreach ($eventTypes as $type)
                                            <option value="{{ $type }}" @selected(request('event_type') === $type)>{{ $type }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="audit-filters__field">
                                    <label class="audit-filters__label" for="audit-action">Accion</label>
                                    <select id="audit-action" name="action" class="form-select audit-filters__control">
                                        <option value="">Todas</option>
                                        @foreach ($actions as $action)
                                            <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="audit-filters__field audit-filters__field--wide">
                                    <label class="audit-filters__label" for="audit-user">Usuario</label>
                                    <select id="audit-user" name="user_id" class="form-select audit-filters__control">
                                        <option value="">Todos</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="audit-filters__row audit-filters__row--footer">
                                <div class="audit-filters__dates">
                                    <div class="audit-filters__field audit-filters__field--date">
                                        <label class="audit-filters__label" for="audit-date-from">Desde</label>
                                        <input id="audit-date-from" type="date" name="date_from" class="form-input audit-filters__control" value="{{ request('date_from') }}">
                                    </div>
                                    <div class="audit-filters__field audit-filters__field--date">
                                        <label class="audit-filters__label" for="audit-date-to">Hasta</label>
                                        <input id="audit-date-to" type="date" name="date_to" class="form-input audit-filters__control" value="{{ request('date_to') }}">
                                    </div>
                                </div>

                                <div class="audit-filters__extras">
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

                                <div class="audit-filters__submit">
                                    <button type="submit" class="btn btn--primary">Aplicar filtros</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="audit-results">
                        <div class="audit-results__head">
                            <p class="audit-results__count">
                                {{ $logs->total() }} {{ $logs->total() === 1 ? 'registro' : 'registros' }}
                            </p>
                        </div>

                        <div class="indicadores-table-wrap">
                            <table class="supply-table indicadores-table indicadores-table--audit">
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
                                            <td><x-date-table :value="$log->created_at" datetime /></td>
                                            <td>{{ $log->user?->name ?? 'Sistema' }}</td>
                                            <td>{{ $moduleLabels[$log->module] ?? $log->module }}</td>
                                            <td>{{ $log->area ? ($areaLabels[$log->area] ?? $log->area) : '—' }}</td>
                                            <td>{{ $log->event_type }}</td>
                                            <td>{{ $log->action }}</td>
                                            <td class="indicadores-cell-wrap">
                                                @if ($log->auditable_type)
                                                    {{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="indicadores-cell-wrap">{{ $log->reason }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-muted">Sin registros para los filtros seleccionados.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
