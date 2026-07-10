@php
    $semaforoClass = fn (?string $semaforo) => match ($semaforo) {
        'VERDE' => 'status-pill--req-contratado',
        'AMARILLO' => 'status-pill--req-en_gestion',
        default => 'status-pill--req-cancelada',
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        @include('areas.operaciones.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:1rem; flex-wrap:wrap;">
                        <div>
                            <h3 class="panel-title">{{ $leader->code }} — {{ $leader->name }}</h3>
                            <p class="panel-text">{{ $dashboard['headline'] ?? 'Dashboard del jefe de operaciones.' }}</p>
                        </div>
                        <form method="GET" style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:flex-end;">
                            <div>
                                <label class="form-label">Ano</label>
                                <select name="year" onchange="this.form.submit()" class="supply-input supply-select">
                                    @foreach ($years as $year)
                                        <option value="{{ $year }}" @selected($selectedYear === (int) $year)>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Mes</label>
                                <select name="month" onchange="this.form.submit()" class="supply-input supply-select">
                                    @foreach ($months as $monthNumber => $monthName)
                                        <option value="{{ $monthNumber }}" @selected($selectedMonth === (int) $monthNumber)>{{ $monthName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="panel__body">
                    <div class="dashboard-stat-grid">
                        <div class="card kpi-card">
                            <p class="text-caption">Score del periodo</p>
                            <p class="kpi-value">{{ number_format($dashboard['summary']['score'] ?? 0, 2) }}%</p>
                        </div>
                        <div class="card kpi-card">
                            <p class="text-caption">Cobertura</p>
                            <p class="kpi-value">{{ number_format($dashboard['summary']['coverage'] ?? 0, 2) }}%</p>
                        </div>
                        <div class="card kpi-card">
                            <p class="text-caption">Indicadores en atencion</p>
                            <p class="kpi-value">{{ $dashboard['summary']['attention_count'] ?? 0 }}</p>
                        </div>
                    </div>

                    <table class="supply-table" style="margin-top:1.5rem;">
                        <thead>
                            <tr>
                                <th>Indicador</th>
                                <th>Resultado</th>
                                <th>Meta</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dashboard['cards'] as $card)
                                <tr>
                                    <td>{{ $card['indicator']->code }} — {{ $card['indicator']->name }}</td>
                                    <td>{{ $card['result_label'] ?? 'Sin registro' }}</td>
                                    <td>{{ $card['meta_label'] ?? '-' }}</td>
                                    <td><span class="status-pill {{ $semaforoClass($card['semaforo'] ?? 'ROJO') }}">{{ $card['semaforo'] ?? 'ROJO' }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
