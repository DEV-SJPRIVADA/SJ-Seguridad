<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    @include('areas.operaciones.exports.partials.pdf-base-styles')
</head>
<body @class(['pdf-portrait' => $pdfPortrait ?? false])>
    <div class="meta">
        <h2>
            @if ($isConsolidadoView ?? false)
                Consolidado — {{ $indicator->code }}
            @else
                Captura — {{ $indicator->code }}
            @endif
        </h2>
        <p><strong>Indicador:</strong> {{ $indicator->code }} — {{ $indicator->name }}</p>
        <p><strong>Periodo:</strong> {{ ($months[$selectedMonth] ?? $month ?? $selectedMonth) }} {{ $selectedYear ?? $year }}</p>
        <p><strong>Capturador:</strong> {{ $captureUserName ?? ($user->name ?? 'N/A') }}</p>
        @if ($isConsolidadoView ?? false)
            <p><strong>Vista:</strong> {{ empty($exportUserId) ? 'Todos los capturadores (consolidado)' : 'Captura individual' }}</p>
        @endif
    </div>

    @include('areas.operaciones.exports.partials.capture-panel-pdf')
</body>
</html>
