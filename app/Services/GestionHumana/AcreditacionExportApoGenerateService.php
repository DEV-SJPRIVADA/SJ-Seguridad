<?php

namespace App\Services\GestionHumana;

use App\Exports\AcreditacionExportApoXlsExport;
use App\Models\AcreditacionAcreditado;
use App\Models\AcreditacionExportApoRun;
use App\Models\AcreditacionExportApoSetting;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class AcreditacionExportApoGenerateService
{
    public function __construct(
        private readonly AcreditacionExportApoCandidateService $candidateService,
        private readonly AcreditacionExportApoRowResolver $rowResolver,
        private readonly AcreditacionExportApoXlsExport $xlsExport,
    ) {}

    /**
     * @param  list<int>  $ids
     * @return array{
     *     response: StreamedResponse,
     *     run: AcreditacionExportApoRun,
     * }
     */
    public function generate(
        array $ids,
        string $vigenciaPolicy,
        bool $includeNovedades,
        ?int $userId = null,
    ): array {
        $policy = $this->rowResolver->normalizePolicy($vigenciaPolicy);
        $requestedIds = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if ($requestedIds === []) {
            throw ValidationException::withMessages([
                'ids' => 'Seleccione al menos un candidato.',
            ]);
        }

        $candidates = $this->candidateService->findCandidatesByIds($requestedIds);

        if ($candidates->isEmpty()) {
            throw ValidationException::withMessages([
                'ids' => 'Ningún candidato seleccionado es exportable (fuera del universo o sin ficha activa).',
            ]);
        }

        $resolved = $candidates
            ->map(fn (AcreditacionAcreditado $row): array => $this->rowResolver->resolve($row, $policy))
            ->values()
            ->all();

        $rowsOk = 0;
        $rowsNovedad = 0;
        $rowsBlocked = 0;
        $exportRows = [];

        foreach ($resolved as $row) {
            if ($row['hard_block'] === true) {
                $rowsBlocked++;

                continue;
            }

            if ($row['soft_novedad'] === true) {
                $rowsNovedad++;
                if ($includeNovedades) {
                    $exportRows[] = $row;
                }

                continue;
            }

            $rowsOk++;
            $exportRows[] = $row;
        }

        if ($exportRows === []) {
            throw ValidationException::withMessages([
                'ids' => $includeNovedades
                    ? 'No hay filas exportables: todas tienen bloqueo duro (ficha incompleta) o no se resolvieron.'
                    : 'No hay filas válidas para exportar. Active «incluir novedades» o corrija las filas con novedad/bloqueo.',
            ]);
        }

        $settings = AcreditacionExportApoSetting::singleton();
        $exportDate = Carbon::now('America/Bogota')->toDateString();
        $ymd = Carbon::parse($exportDate, 'America/Bogota')->format('Ymd');
        $seqPad = (int) config('acreditaciones.export_apo.seq_pad', 3);

        $run = $this->persistRunWithSeq(
            exportDate: $exportDate,
            ymd: $ymd,
            seqPad: $seqPad,
            nit: (string) $settings->nit,
            userId: $userId,
            vigenciaPolicy: $policy,
            includeNovedades: $includeNovedades,
            rowsSelected: count($requestedIds),
            rowsOk: $rowsOk,
            rowsNovedad: $rowsNovedad,
            rowsBlocked: $rowsBlocked,
            rowsExported: count($exportRows),
        );

        $response = $this->xlsExport->download($exportRows, $settings, $run->file_name);

        return [
            'response' => $response,
            'run' => $run,
        ];
    }

    private function persistRunWithSeq(
        string $exportDate,
        string $ymd,
        int $seqPad,
        string $nit,
        ?int $userId,
        string $vigenciaPolicy,
        bool $includeNovedades,
        int $rowsSelected,
        int $rowsOk,
        int $rowsNovedad,
        int $rowsBlocked,
        int $rowsExported,
    ): AcreditacionExportApoRun {
        $attempts = 0;
        $lastException = null;

        while ($attempts < 5) {
            $attempts++;

            try {
                return DB::transaction(function () use (
                    $exportDate,
                    $ymd,
                    $seqPad,
                    $nit,
                    $userId,
                    $vigenciaPolicy,
                    $includeNovedades,
                    $rowsSelected,
                    $rowsOk,
                    $rowsNovedad,
                    $rowsBlocked,
                    $rowsExported,
                ): AcreditacionExportApoRun {
                    $maxSeq = (int) AcreditacionExportApoRun::query()
                        ->whereDate('export_date', $exportDate)
                        ->lockForUpdate()
                        ->max('seq');

                    $seq = $maxSeq + 1;

                    if ($seq > 999) {
                        throw ValidationException::withMessages([
                            'ids' => 'Se alcanzó el límite diario de secuencias Export Apo (999).',
                        ]);
                    }

                    $fileName = sprintf(
                        'APO%s%s%s.xls',
                        $nit,
                        $ymd,
                        str_pad((string) $seq, $seqPad, '0', STR_PAD_LEFT),
                    );

                    return AcreditacionExportApoRun::query()->create([
                        'export_date' => $exportDate,
                        'seq' => $seq,
                        'file_name' => $fileName,
                        'user_id' => $userId,
                        'vigencia_policy' => $vigenciaPolicy,
                        'include_novedades' => $includeNovedades,
                        'rows_selected' => $rowsSelected,
                        'rows_ok' => $rowsOk,
                        'rows_novedad' => $rowsNovedad,
                        'rows_blocked' => $rowsBlocked,
                        'rows_exported' => $rowsExported,
                    ]);
                });
            } catch (ValidationException $e) {
                throw $e;
            } catch (Throwable $e) {
                $lastException = $e;
                // Unique (export_date, seq) conflict → reintentar
                if (! $this->isUniqueConstraintViolation($e)) {
                    throw $e;
                }
            }
        }

        report($lastException);

        throw ValidationException::withMessages([
            'ids' => 'No se pudo asignar la secuencia del archivo. Intente de nuevo.',
        ]);
    }

    private function isUniqueConstraintViolation(Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'unique')
            || str_contains($message, 'duplicate')
            || $e instanceof UniqueConstraintViolationException;
    }
}
