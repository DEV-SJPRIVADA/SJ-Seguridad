@props(['importResult' => null, 'downloadRoute' => null])

@if (is_array($importResult) && (($importResult['failures_count'] ?? 0) > 0 || ($importResult['report_token'] ?? null)))
    <div class="panel import-failure-result bottom-spaced">
        <div class="panel__header">
            <h3 class="panel-title">Detalle de filas no importadas</h3>
            <p class="panel-text text-small text-muted">
                @if (($importResult['failures_count'] ?? 0) > 0)
                    {{ number_format($importResult['failures_count']) }} fila(s) con error u omision.
                @endif
                @if (($importResult['empty_rows'] ?? 0) > 0)
                    {{ number_format($importResult['empty_rows']) }} fila(s) vacia(s) ignorada(s).
                @endif
            </p>
        </div>
        <div class="panel__body">
            @if (($importResult['errors'] ?? []) !== [])
                <ul class="import-failure-result__errors text-small">
                    @foreach (array_slice($importResult['errors'], 0, 20) as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                @if (count($importResult['errors']) > 20)
                    <p class="text-small text-muted">Mostrando 20 de {{ count($importResult['errors']) }} avisos. Descargue el reporte completo.</p>
                @endif
            @endif

            @if ($importResult['report_token'] ?? null)
                <div class="import-failure-result__actions">
                    <a href="{{ route($downloadRoute, $importResult['report_token']) }}" class="btn btn--secondary btn--sm">
                        Descargar reporte de filas fallidas (.xlsx)
                    </a>
                </div>
            @endif
        </div>
    </div>
@endif
