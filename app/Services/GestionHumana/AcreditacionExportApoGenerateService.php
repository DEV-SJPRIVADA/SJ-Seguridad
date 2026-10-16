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
     *     export_mode: string,
     * }
     */
    public function generate(
        array $ids,
        string $vigenciaPolicy,
        bool $includeNovedades,
        ?int $userId = null,
        string $exportMode = AcreditacionExportApoXlsExport::MODE_ACREDITAR,
    ): array {
        $policy = $this->rowResolver->normalizePolicy($vigenciaPolicy);
        $exportMode = $this->xlsExport->normalizeMode($exportMode);
        $requestedIds = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if ($requestedIds === []) {
            throw ValidationException::withMessages([
                'ids' => 'Seleccione al menos un candidato.',
            ]);
        }

        // Retirar: permite IDs fuera del universo Export Apo (p. ej. ACREDITADO).
        if ($exportMode === AcreditacionExportApoXlsExport::MODE_RETIRAR) {
            return $this->generateRetiros($requestedIds, $policy, $userId);
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

        return $this->finishGenerate(
            exportRows: $exportRows,
            exportMode: AcreditacionExportApoXlsExport::MODE_ACREDITAR,
            userId: $userId,
            vigenciaPolicy: $policy,
            includeNovedades: $includeNovedades,
            rowsSelected: count($requestedIds),
            rowsOk: $rowsOk,
            rowsNovedad: $rowsNovedad,
            rowsBlocked: $rowsBlocked,
        );
    }

    /**
     * Generación Manual: filas ya editadas en UI, sin Valida/universo/novedades.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array{
     *     response: StreamedResponse,
     *     run: AcreditacionExportApoRun,
     *     export_mode: string,
     *     source: string,
     * }
     */
    public function generateFromManualRows(
        array $rows,
        string $exportMode,
        ?int $userId = null,
    ): array {
        $exportMode = $this->xlsExport->normalizeMode($exportMode);
        $normalized = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $document = trim((string) ($row['document_number'] ?? ''));
            if ($document === '') {
                continue;
            }

            if ($exportMode === AcreditacionExportApoXlsExport::MODE_RETIRAR) {
                $normalized[] = [
                    'document_number' => $document,
                ];

                continue;
            }

            $normalized[] = [
                'document_number' => $document,
                'nombre1' => trim((string) ($row['nombre1'] ?? '')),
                'nombre2' => trim((string) ($row['nombre2'] ?? '')),
                'apellido1' => trim((string) ($row['apellido1'] ?? '')),
                'apellido2' => trim((string) ($row['apellido2'] ?? '')),
                'fecha_nacimiento' => $this->normalizeManualApoDate((string) ($row['fecha_nacimiento'] ?? '')),
                'genero' => trim((string) ($row['genero'] ?? '')),
                'cargo' => trim((string) ($row['cargo'] ?? '')),
                'fecha_vinculacion' => $this->normalizeManualApoDate((string) ($row['fecha_vinculacion'] ?? '')),
                'codigo_curso' => trim((string) ($row['codigo_curso'] ?? '')),
                'nit_escuela' => trim((string) ($row['nit_escuela'] ?? '')),
                'nro' => trim((string) ($row['nro'] ?? '')),
            ];
        }

        if ($normalized === []) {
            throw ValidationException::withMessages([
                'rows' => 'Agregue al menos una fila con cédula para generar el archivo.',
            ]);
        }

        $result = $this->finishGenerate(
            exportRows: $normalized,
            exportMode: $exportMode,
            userId: $userId,
            vigenciaPolicy: AcreditacionExportApoRowResolver::POLICY_VIGENTE,
            includeNovedades: false,
            rowsSelected: count($normalized),
            rowsOk: count($normalized),
            rowsNovedad: 0,
            rowsBlocked: 0,
        );

        $result['source'] = 'manual';

        return $result;
    }

    /**
     * Retiros: carga por IDs sin filtro de universo; no aplica bloqueos de curso/ficha Export Apo.
     *
     * @param  list<int>  $requestedIds
     * @return array{
     *     response: StreamedResponse,
     *     run: AcreditacionExportApoRun,
     *     export_mode: string,
     * }
     */
    private function generateRetiros(array $requestedIds, string $policy, ?int $userId): array
    {
        $acreditados = $this->candidateService->findByIds($requestedIds);

        if ($acreditados->isEmpty()) {
            throw ValidationException::withMessages([
                'ids' => 'No se encontraron acreditados para retirar con los IDs seleccionados.',
            ]);
        }

        $exportRows = $acreditados
            ->map(fn (AcreditacionAcreditado $row): array => [
                'document_number' => (string) $row->document_number,
                'cargo_apo' => (string) $row->cargo_apo,
                'acreditado_id' => (int) $row->id,
            ])
            ->values()
            ->all();

        return $this->finishGenerate(
            exportRows: $exportRows,
            exportMode: AcreditacionExportApoXlsExport::MODE_RETIRAR,
            userId: $userId,
            vigenciaPolicy: $policy,
            includeNovedades: false,
            rowsSelected: count($requestedIds),
            rowsOk: count($exportRows),
            rowsNovedad: 0,
            rowsBlocked: max(0, count($requestedIds) - count($exportRows)),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $exportRows
     * @return array{
     *     response: StreamedResponse,
     *     run: AcreditacionExportApoRun,
     *     export_mode: string,
     * }
     */
    private function finishGenerate(
        array $exportRows,
        string $exportMode,
        ?int $userId,
        string $vigenciaPolicy,
        bool $includeNovedades,
        int $rowsSelected,
        int $rowsOk,
        int $rowsNovedad,
        int $rowsBlocked,
    ): array {
        $settings = AcreditacionExportApoSetting::singleton();
        $exportDate = Carbon::now('America/Bogota')->toDateString();
        $ymd = Carbon::parse($exportDate, 'America/Bogota')->format('Ymd');
        $seqPad = (int) config('acreditaciones.export_apo.seq_pad', 2);

        $run = $this->persistRunWithSeq(
            exportDate: $exportDate,
            ymd: $ymd,
            seqPad: $seqPad,
            nit: (string) $settings->nit,
            userId: $userId,
            vigenciaPolicy: $vigenciaPolicy,
            includeNovedades: $includeNovedades,
            rowsSelected: $rowsSelected,
            rowsOk: $rowsOk,
            rowsNovedad: $rowsNovedad,
            rowsBlocked: $rowsBlocked,
            rowsExported: count($exportRows),
        );

        $response = $this->xlsExport->download($exportRows, $settings, $run->file_name, $exportMode);

        return [
            'response' => $response,
            'run' => $run,
            'export_mode' => $exportMode,
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

                    // Tope alineado a seq_pad=2 (01…99).
                    if ($seq > 99) {
                        throw ValidationException::withMessages([
                            'ids' => 'Se alcanzó el límite diario de secuencias Export Apo (99).',
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

    /**
     * Manual UI usa YYYY-MM-DD; el .xls APO exige dd/mm/yyyy.
     */
    private function normalizeManualApoDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value) === 1) {
            return $value;
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (Throwable) {
            return $value;
        }
    }

    private function isUniqueConstraintViolation(Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'unique')
            || str_contains($message, 'duplicate')
            || $e instanceof UniqueConstraintViolationException;
    }
}
