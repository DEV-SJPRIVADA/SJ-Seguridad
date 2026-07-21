<div class="indicadores-subpanel">
    <h4 class="indicadores-subpanel__title">Crear periodo</h4>
    <form method="POST" action="{{ route('indicadores.admin.periods.store') }}" class="indicadores-inline-form">
        @csrf
        <div class="indicadores-filter-bar">
            <div class="indicadores-field indicadores-field--xs">
                <label class="form-label">Ano</label>
                <select name="year" class="supply-input supply-select" required>
                    @foreach ($years as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="indicadores-field indicadores-field--sm">
                <label class="form-label">Mes</label>
                <select name="month" class="supply-input supply-select" required>
                    @foreach ($months as $num => $label)
                        <option value="{{ $num }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="indicadores-field indicadores-field--sm">
                <label class="form-label">Estado inicial</label>
                <select name="status" class="supply-input supply-select">
                    <option value="open">Abierto</option>
                    <option value="closed">Cerrado</option>
                </select>
            </div>
            <div class="indicadores-field indicadores-field--action">
                <button type="submit" class="btn btn--primary btn--sm">Crear periodo</button>
            </div>
        </div>
    </form>
</div>

@if ($errors->has('close'))
    <div class="panel indicadores-alert indicadores-alert--error" style="margin-top:1rem;">
        <div class="panel__body">
            <p>{{ $errors->first('close') }}</p>
            @if (session('pending_improvements'))
                <ul class="indicadores-list-compact">
                    @foreach (session('pending_improvements') as $item)
                        <li>{{ $item['indicator'] }} / {{ $item['user'] ?? $item['leader'] ?? '-' }} — {{ $item['result'] }}%</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endif

<div class="indicadores-subpanel" style="margin-top:1.25rem;">
    <h4 class="indicadores-subpanel__title">Periodos de captura</h4>
    <p class="indicadores-subpanel__text">Control de apertura y cierre mensual.</p>

    <div class="indicadores-table-wrap">
        <table class="supply-table js-datatable indicadores-table indicadores-table--periods" data-no-excel data-server-pagination>
            <thead>
                <tr>
                    <th>Periodo</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($periods as $period)
                    <tr>
                        <td>{{ $months[$period->month] ?? $period->month }} {{ $period->year }}</td>
                        <td>
                            <span class="status-pill {{ $period->isClosed() ? 'status-pill--req-cancelada' : 'status-pill--req-contratado' }}">
                                {{ $period->isClosed() ? 'Cerrado' : 'Abierto' }}
                            </span>
                        </td>
                        <td>
                            <form method="POST"
                                  action="{{ $period->isClosed()
                                      ? route('indicadores.admin.periods.reopen', $period)
                                      : route('indicadores.admin.periods.close', $period) }}"
                                  class="indicadores-row-form">
                                @csrf
                                <input type="text" name="reason" class="supply-input indicadores-input-inline" placeholder="Motivo" required>
                                <button type="submit" class="btn btn--secondary btn--sm">
                                    {{ $period->isClosed() ? 'Reabrir' : 'Cerrar' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $periods->links() }}
</div>
