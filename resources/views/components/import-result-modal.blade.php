@props([
    'name' => 'import-result',
    'downloadRoute' => null,
    'status' => null,
    'importResult' => null,
    'importFailures' => null,
    'importReportToken' => null,
    'importDone' => null,
])

@php
    $status = $status ?? session('status');
    $importResult = $importResult ?? session('import_result');
    $importFailures = $importFailures ?? session('import_failures');
    $importReportToken = $importReportToken ?? session('import_report_token');
    $importDone = $importDone ?? session('import_done');

    $hasStructuredResult = is_array($importResult);
    $hasFailureRows = is_array($importFailures) && $importFailures !== [];
    $token = $importReportToken
        ?? ($hasStructuredResult ? ($importResult['report_token'] ?? null) : null);

    $shouldShow = (bool) $importDone
        || $hasStructuredResult
        || $hasFailureRows
        || filled($token);

    $errorRows = [];
    if ($hasFailureRows) {
        foreach ($importFailures as $failure) {
            if (! is_array($failure)) {
                continue;
            }
            $errorRows[] = [
                'row' => $failure['row'] ?? '—',
                'identifier' => (string) ($failure['identifier'] ?? ''),
                'reason' => (string) ($failure['reason'] ?? ''),
            ];
        }
    } elseif ($hasStructuredResult && is_array($importResult['errors'] ?? null)) {
        foreach ($importResult['errors'] as $error) {
            if (is_string($error)) {
                $errorRows[] = [
                    'row' => '—',
                    'identifier' => '',
                    'reason' => $error,
                ];
                continue;
            }
            if (! is_array($error)) {
                continue;
            }
            $errorRows[] = [
                'row' => $error['row'] ?? '—',
                'identifier' => (string) ($error['identifier'] ?? ''),
                'reason' => (string) ($error['reason'] ?? ''),
            ];
        }
    }

    $failuresCount = (int) (
        ($hasStructuredResult ? ($importResult['failures_count'] ?? $importResult['failed'] ?? null) : null)
        ?? ($hasFailureRows ? count($importFailures) : count($errorRows))
    );

    $emptyRows = (int) ($hasStructuredResult ? ($importResult['empty_rows'] ?? 0) : 0);
    $imported = $hasStructuredResult ? ($importResult['imported'] ?? $importResult['clients_created'] ?? null) : null;
    $updated = $hasStructuredResult ? ($importResult['updated'] ?? $importResult['clients_updated'] ?? null) : null;
    $servicesCreated = $hasStructuredResult ? ($importResult['services_created'] ?? null) : null;
    $servicesUpdated = $hasStructuredResult ? ($importResult['services_updated'] ?? null) : null;
    $skipped = $hasStructuredResult ? ($importResult['skipped'] ?? null) : null;

    $errorsTruncated = $hasStructuredResult && ! empty($importResult['errors_truncated']);
    $errorsTotal = (int) ($hasStructuredResult ? ($importResult['errors_total'] ?? $failuresCount) : $failuresCount);
    $shownErrors = count($errorRows);
    $tone = $failuresCount > 0 ? 'warning' : 'success';

    $downloadUrl = null;
    if (filled($token) && filled($downloadRoute)) {
        $downloadUrl = route($downloadRoute, $token);
    }
@endphp

@if ($shouldShow)
    <x-modal :name="$name" maxWidth="lg" :show="true" focusable>
        <div class="modal-card ficha-empleados-masivos-modal import-result-modal import-result-modal--{{ $tone }}">
            <div class="ficha-empleados-masivos-modal__header">
                <div class="ficha-empleados-masivos-modal__heading">
                    <span class="ficha-empleados-masivos-modal__heading-icon import-result-modal__heading-icon" aria-hidden="true">
                        @if ($tone === 'success')
                            <x-lucide-circle-check width="18" height="18" aria-hidden="true" />
                        @else
                            <x-lucide-triangle-alert width="18" height="18" aria-hidden="true" />
                        @endif
                    </span>
                    <div>
                        <h3 class="ficha-empleados-masivos-modal__title">Resultado de carga masiva</h3>
                        <p class="ficha-empleados-masivos-modal__lead">
                            {{ $status ?: ($tone === 'success' ? 'La importación finalizó correctamente.' : 'La importación finalizó con observaciones.') }}
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    class="ficha-empleados-masivos-modal__close"
                    aria-label="Cerrar"
                    x-on:click="$dispatch('close-modal', '{{ $name }}')"
                >
                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                </button>
            </div>

            <div class="import-result-modal__body">
                @if (
                    $imported !== null
                    || $updated !== null
                    || $servicesCreated !== null
                    || $servicesUpdated !== null
                    || $skipped !== null
                    || $emptyRows > 0
                    || $failuresCount > 0
                )
                    <div class="import-result-modal__stats" role="list">
                        @if ($imported !== null)
                            <div class="import-result-modal__stat" role="listitem">
                                <span class="import-result-modal__stat-value">{{ number_format((int) $imported) }}</span>
                                <span class="import-result-modal__stat-label">Nuevos</span>
                            </div>
                        @endif
                        @if ($updated !== null)
                            <div class="import-result-modal__stat" role="listitem">
                                <span class="import-result-modal__stat-value">{{ number_format((int) $updated) }}</span>
                                <span class="import-result-modal__stat-label">Actualizados</span>
                            </div>
                        @endif
                        @if ($servicesCreated !== null)
                            <div class="import-result-modal__stat" role="listitem">
                                <span class="import-result-modal__stat-value">{{ number_format((int) $servicesCreated) }}</span>
                                <span class="import-result-modal__stat-label">Servicios nuevos</span>
                            </div>
                        @endif
                        @if ($servicesUpdated !== null)
                            <div class="import-result-modal__stat" role="listitem">
                                <span class="import-result-modal__stat-value">{{ number_format((int) $servicesUpdated) }}</span>
                                <span class="import-result-modal__stat-label">Servicios act.</span>
                            </div>
                        @endif
                        @if ($emptyRows > 0)
                            <div class="import-result-modal__stat" role="listitem">
                                <span class="import-result-modal__stat-value">{{ number_format($emptyRows) }}</span>
                                <span class="import-result-modal__stat-label">Vacías</span>
                            </div>
                        @endif
                        @if ($failuresCount > 0 || (int) $skipped > 0)
                            <div class="import-result-modal__stat import-result-modal__stat--danger" role="listitem">
                                <span class="import-result-modal__stat-value">{{ number_format($failuresCount > 0 ? $failuresCount : (int) $skipped) }}</span>
                                <span class="import-result-modal__stat-label">Con error</span>
                            </div>
                        @endif
                    </div>
                @endif

                @if ($errorRows !== [])
                    <div class="import-result-modal__errors">
                        <div class="import-result-modal__errors-head">
                            <h4 class="import-result-modal__errors-title">Detalle de filas con error</h4>
                            <p class="import-result-modal__errors-meta">
                                Mostrando {{ number_format($shownErrors) }}
                                @if ($errorsTotal > $shownErrors || $errorsTruncated)
                                    de {{ number_format(max($errorsTotal, $shownErrors)) }}
                                @endif
                            </p>
                        </div>
                        <div class="import-result-modal__table-wrap">
                            <table class="import-result-modal__table">
                                <thead>
                                    <tr>
                                        <th scope="col">Fila</th>
                                        <th scope="col">Identificador</th>
                                        <th scope="col">Motivo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($errorRows as $row)
                                        <tr>
                                            <td>{{ $row['row'] }}</td>
                                            <td>{{ $row['identifier'] !== '' ? $row['identifier'] : '—' }}</td>
                                            <td>{{ $row['reason'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @elseif ($tone === 'success')
                    <div class="import-result-modal__ok">
                        <x-lucide-circle-check width="20" height="20" aria-hidden="true" />
                        <p>No se detectaron filas con error en esta carga.</p>
                    </div>
                @endif
            </div>

            <div class="import-result-modal__footer">
                @if ($downloadUrl)
                    <a href="{{ $downloadUrl }}" class="btn btn--secondary btn--sm">
                        <x-lucide-download width="16" height="16" aria-hidden="true" />
                        Descargar reporte (.xlsx)
                    </a>
                @endif
                <button
                    type="button"
                    class="btn btn--primary btn--sm"
                    x-on:click="$dispatch('close-modal', '{{ $name }}')"
                >
                    Entendido
                </button>
            </div>
        </div>
    </x-modal>
@endif
