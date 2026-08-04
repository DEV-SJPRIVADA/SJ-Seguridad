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
                            <h3 class="panel-title">{{ $indicator->code }} — {{ $indicator->name }}</h3>
                            <p class="panel-text">Captura mensual del indicador.</p>
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
                            @if ($headerFilters['showCapturadorSelector'])
                                <div>
                                    <label class="form-label">Capturador</label>
                                    <select name="capturador_id" onchange="this.form.submit()" class="supply-input supply-select">
                                        @foreach ($headerFilters['capturableUsers'] as $capturador)
                                            <option value="{{ $capturador->id }}" @selected($headerFilters['selectedCapturadorId'] === $capturador->id)>{{ $capturador->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @else
                                <div>
                                    <label class="form-label">Usuario</label>
                                    <p class="panel-text" style="margin:0.35rem 0 0;">{{ $headerFilters['captureUserName'] ?? auth()->user()?->name }}</p>
                                </div>
                            @endif
                            <div>
                                <span class="status-pill {{ $headerFilters['isPeriodClosed'] ? 'status-pill--req-cancelada' : 'status-pill--req-contratado' }}">
                                    {{ $headerFilters['isPeriodClosed'] ? 'Periodo cerrado' : 'Periodo abierto' }}
                                </span>
                            </div>
                            @if ($headerFilters['isDelegatedCapture'])
                                <div>
                                    <span class="status-pill status-pill--info">Captura por suplencia</span>
                                </div>
                            @endif
                        </form>
                    </div>
                </div>
                <div class="panel__body">
                    @if ($fieldsView)
                        <form
                            method="POST"
                            action="{{ route('indicadores.capture.store', $indicator) }}"
                            id="indicadores-capture-form"
                        >
                            @csrf
                            <input type="hidden" name="year" value="{{ $selectedYear }}">
                            <input type="hidden" name="month" value="{{ $selectedMonth }}">
                            <input type="hidden" name="capturador_user_id" value="{{ $selectedCapturadorId }}">

                            @if ($indicator->code === 'FT-OP-03')
                                @include('areas.operaciones.indicadores.capture-form-03')
                            @else
                                @include('areas.operaciones.indicadores.capture-form')
                            @endif
                        </form>
                    @else
                        <div class="panel-text">Indicador no implementado.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @vite(['resources/js/indicadores-capture.js'])
</x-app-layout>
