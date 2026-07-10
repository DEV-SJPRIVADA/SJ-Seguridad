@php
    $livewireClass = config('indicators.livewire_forms')[$indicator->code] ?? null;
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
                            <h3 class="panel-title">{{ $indicator->code }} — {{ $indicator->name }}</h3>
                            <p class="panel-text">Captura mensual por jefe de operaciones.</p>
                        </div>
                        <form method="GET" action="{{ route('indicadores.show', $indicator) }}" style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:flex-end;">
                            <div>
                                <label class="form-label">Ano</label>
                                <select name="year" onchange="this.form.submit()" class="supply-input supply-select">
                                    @foreach ($headerFilters['years'] as $year)
                                        <option value="{{ $year }}" @selected($headerFilters['selectedYear'] === (int) $year)>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Mes</label>
                                <select name="month" onchange="this.form.submit()" class="supply-input supply-select">
                                    @foreach ($headerFilters['months'] as $monthNumber => $monthName)
                                        <option value="{{ $monthNumber }}" @selected($headerFilters['selectedMonth'] === (int) $monthNumber)>{{ $monthName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Jefe de operaciones</label>
                                <select name="operations_leader_id" onchange="this.form.submit()" class="supply-input supply-select">
                                    @foreach ($headerFilters['leaders'] as $leader)
                                        <option value="{{ $leader->id }}" @selected($headerFilters['selectedOperationsLeaderId'] === (int) $leader->id)>
                                            {{ $leader->code }} — {{ $leader->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <span class="status-pill {{ $headerFilters['isPeriodClosed'] ? 'status-pill--req-cancelada' : 'status-pill--req-contratado' }}">
                                    {{ $headerFilters['isPeriodClosed'] ? 'Periodo cerrado' : 'Periodo abierto' }}
                                </span>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="panel__body indicadores-livewire-wrap">
                    @if ($livewireClass)
                        @livewire($livewireClass, ['indicator' => $indicator])
                    @else
                        <div class="panel-text">Indicador no implementado.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
