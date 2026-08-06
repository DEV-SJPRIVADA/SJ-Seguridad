@php
    $semaforoClass = fn (?string $semaforo) => match ($semaforo) {
        'VERDE' => 'status-pill--req-contratado',
        'AMARILLO', 'ATENCION' => 'status-pill--req-en_gestion',
        default => 'status-pill--req-cancelada',
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        @include('areas.operaciones.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container indicadores-board">
            <div class="panel">
                <div class="panel__header">
                    <div class="indicadores-panel-header">
                        <div>
                            <h3 class="panel-title">Vista previa — Informe de gestion FO-GI-39</h3>
                            <p class="panel-text">Revisa y edita las narrativas antes de descargar el PowerPoint.</p>
                        </div>
                        <div class="indicadores-filter-bar" style="margin:0;">
                            <a href="{{ route('indicadores.export.management.pptx', ['year' => $year, 'month' => $month]) }}" class="btn btn--secondary btn--sm">
                                Descargar PPTX
                            </a>
                        </div>
                    </div>
                </div>

                <div class="panel__body">
                    @if (session('status'))
                        <div class="alert alert--success" style="margin-bottom:1rem;">{{ session('status') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert--danger" style="margin-bottom:1rem;">
                            <ul style="margin:0; padding-left:1.25rem;">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="GET" action="{{ route('indicadores.export.management.preview') }}" class="indicadores-inline-form">
                        <div class="indicadores-filter-bar">
                            <div class="indicadores-field indicadores-field--xs">
                                <label class="form-label">Ano</label>
                                <select name="year" class="supply-input supply-select">
                                    @foreach ($years as $yearOption)
                                        <option value="{{ $yearOption }}" @selected($year === (int) $yearOption)>{{ $yearOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="indicadores-field indicadores-field--sm">
                                <label class="form-label">Mes</label>
                                <select name="month" class="supply-input supply-select">
                                    @foreach ($months as $monthNumber => $monthName)
                                        <option value="{{ $monthNumber }}" @selected($month === (int) $monthNumber)>{{ $monthName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="indicadores-field indicadores-field--action">
                                <button type="submit" class="btn btn--primary btn--sm">Aplicar</button>
                            </div>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('indicadores.export.management.draft.store') }}">
                        @csrf
                        <input type="hidden" name="year" value="{{ $year }}">
                        <input type="hidden" name="month" value="{{ $month }}">

                        <div class="panel indicadores-nested-panel" style="margin-top:1.25rem;">
                            <div class="panel__header"><h4 class="panel-title">Portada</h4></div>
                            <div class="panel__body">
                                <div class="indicadores-field">
                                    <label class="form-label">Titulo del informe</label>
                                    <input
                                        type="text"
                                        name="report_title"
                                        maxlength="255"
                                        class="supply-input"
                                        value="{{ old('report_title', $report['report_title']) }}"
                                    >
                                    <p class="text-caption" style="margin-top:0.35rem;">{{ $report['month_name'] }} {{ $report['year'] }}</p>
                                </div>
                            </div>
                        </div>

                        <h4 class="indicadores-subpanel__title" style="margin:1.25rem 0 0.75rem;">Narrativas por indicador</h4>

                        @foreach ($report['indicators'] as $code => $indicator)
                            <div class="panel indicadores-nested-panel" style="margin-bottom:1rem;">
                                <div class="panel__header">
                                    <h4 class="panel-title">{{ $code }} — {{ $indicator['title'] }}</h4>
                                </div>
                                <div class="panel__body">
                                    <div class="dashboard-stat-grid">
                                        <div class="card kpi-card">
                                            <p class="text-caption">Resultado</p>
                                            <p class="kpi-value">{{ $indicator['result'] !== null ? number_format((float) $indicator['result'], 2).'%' : '-' }}</p>
                                        </div>
                                        <div class="card kpi-card">
                                            <p class="text-caption">Meta</p>
                                            <p class="kpi-value">{{ $indicator['meta'] }}</p>
                                        </div>
                                        <div class="card kpi-card">
                                            <p class="text-caption">Estado</p>
                                            <p class="kpi-value">
                                                <span class="status-pill {{ $semaforoClass($indicator['semaforo']) }}">{{ $indicator['semaforo'] }}</span>
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        id="mgmt-chart-{{ strtolower($code) }}"
                                        class="indicadores-mgmt-report-chart"
                                        aria-hidden="true"
                                    ></div>

                                    <div class="indicadores-field" style="margin-top:0.75rem;">
                                        <label class="form-label">Narrativa</label>
                                        <textarea
                                            name="narratives[{{ $code }}]"
                                            class="supply-textarea"
                                            rows="4"
                                        >{{ old('narratives.'.$code, $indicator['narrative']) }}</textarea>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <div class="indicadores-filter-bar" style="margin-top:0.5rem;">
                            <button type="submit" class="btn btn--primary btn--sm">Guardar borrador</button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('indicadores.export.management.draft.regenerate') }}" style="margin-top:0.75rem;">
                        @csrf
                        <input type="hidden" name="year" value="{{ $year }}">
                        <input type="hidden" name="month" value="{{ $month }}">
                        <button type="submit" class="btn btn--secondary btn--sm">
                            Regenerar textos
                        </button>
                        <span class="text-caption">Elimina el borrador guardado y vuelve a los textos generados automaticamente.</span>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @php
        $chartPayload = [
            'year' => $year,
            'indicators' => collect($report['indicators'] ?? [])
                ->mapWithKeys(fn (array $indicator, string $code): array => [$code => $indicator['chart_series'] ?? []])
                ->all(),
        ];
    @endphp
    <script type="application/json" id="management-report-chart-data">@json($chartPayload)</script>
    @vite(['resources/js/management-report-preview-charts.js'])
</x-app-layout>
