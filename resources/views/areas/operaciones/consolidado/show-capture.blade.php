<x-app-layout>
    <x-slot name="header">
        @include('areas.operaciones.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container indicadores-board">
            <div class="panel">
                <div class="panel__header">
                    <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:1rem; flex-wrap:wrap;">
                        <div>
                            <h3 class="panel-title">Consolidado — {{ $indicator->code }} — {{ $indicator->name }}</h3>
                            <p class="panel-text">Vista consolidada del indicador con filtro por capturador.</p>
                        </div>
                        <form method="GET" action="{{ route('indicadores.admin.consolidado.show', $indicator) }}" style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:flex-end;">
                            <div>
                                <label class="form-label">Ano</label>
                                <select name="year" onchange="this.form.submit()" class="supply-input supply-select">
                                    @foreach ($headerFilters['years'] as $yearOption)
                                        <option value="{{ $yearOption }}" @selected($headerFilters['selectedYear'] === (int) $yearOption)>{{ $yearOption }}</option>
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
                            <div style="min-width: 180px;">
                                <label class="form-label">Capturador</label>
                                <x-searchable-select
                                    id="consolidado-capturador-select"
                                    name="user_id"
                                    :options="collect($headerFilters['capturableUsers'])->map(fn($c) => ['value' => (string) $c->id, 'label' => $c->name])->all()"
                                    :value="$headerFilters['selectedCapturadorId'] ?? ''"
                                    placeholder="Todos los capturadores"
                                    searchPlaceholder="Buscar capturador…"
                                    onchange="this.form.submit()"
                                />
                            </div>
                            <div>
                                <span class="status-pill {{ $headerFilters['isPeriodClosed'] ? 'status-pill--req-cancelada' : 'status-pill--req-contratado' }}">
                                    {{ $headerFilters['isPeriodClosed'] ? 'Periodo cerrado' : 'Periodo abierto' }}
                                </span>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="panel__body">
                    @if ($fieldsView)
                        <div id="indicadores-consolidado-view">
                            @if ($indicator->code === 'FT-OP-03')
                                @include('areas.operaciones.indicadores.capture-form-03')
                            @else
                                @include('areas.operaciones.indicadores.capture-form')
                            @endif
                        </div>
                    @else
                        <div class="panel-text">Indicador no implementado.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @vite(['resources/js/indicadores-capture.js'])
</x-app-layout>
