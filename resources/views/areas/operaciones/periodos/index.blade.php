<x-app-layout>
    <x-slot name="header">
        @include('areas.operaciones.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel" style="margin-bottom:1.5rem;">
                <div class="panel__header"><h3 class="panel-title">Crear periodo</h3></div>
                <div class="panel__body">
                    <form method="POST" action="{{ route('indicadores.admin.periods.store') }}" class="form-stack">
                        @csrf
                        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:1rem;">
                            <div>
                                <label class="form-label">Ano</label>
                                <select name="year" class="supply-input supply-select" required>
                                    @foreach ($years as $year)
                                        <option value="{{ $year }}">{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Mes</label>
                                <select name="month" class="supply-input supply-select" required>
                                    @foreach ($months as $num => $label)
                                        <option value="{{ $num }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Estado inicial</label>
                                <select name="status" class="supply-input supply-select">
                                    <option value="open">Abierto</option>
                                    <option value="closed">Cerrado</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn--primary">Crear periodo</button>
                    </form>
                </div>
            </div>

            @if ($errors->has('close'))
                <div class="panel" style="margin-bottom:1.5rem; border-color:#f43f5e;">
                    <div class="panel__body">
                        <p>{{ $errors->first('close') }}</p>
                        @if (session('pending_improvements'))
                            <ul>
                                @foreach (session('pending_improvements') as $item)
                                    <li>{{ $item['indicator'] }} / {{ $item['leader'] }} — {{ $item['result'] }}%</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @endif

            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Periodos de captura</h3>
                    <p class="panel-text">Control de apertura y cierre mensual.</p>
                </div>
                <div class="panel__body">
                    <table class="supply-table js-datatable">
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
                                        @if ($period->isClosed())
                                            <form method="POST" action="{{ route('indicadores.admin.periods.reopen', $period) }}" class="form-stack" style="max-width:320px;">
                                                @csrf
                                                <input type="text" name="reason" class="supply-input" placeholder="Motivo de reapertura" required>
                                                <button type="submit" class="btn btn--secondary btn--sm">Reabrir</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('indicadores.admin.periods.close', $period) }}" class="form-stack" style="max-width:320px;">
                                                @csrf
                                                <input type="text" name="reason" class="supply-input" placeholder="Motivo de cierre" required>
                                                <button type="submit" class="btn btn--secondary btn--sm">Cerrar</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{ $periods->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
