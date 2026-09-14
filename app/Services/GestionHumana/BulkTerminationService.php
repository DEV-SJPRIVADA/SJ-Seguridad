<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\EmployeeFichaProfile;
use App\Models\PersonalRequisitionFichaEntry;
use App\Models\User;
use App\Services\GestionHumana\TerminationLetter\TerminationLetterPackGeneratorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;
use ZipArchive;

class BulkTerminationService
{
    public function __construct(
        private readonly EmployeeFichaEmploymentPeriodService $periodService,
        private readonly EmployeeTerminationFollowupService $followupService,
        private readonly TerminationLetterPackGeneratorService $letterPackGenerator,
        private readonly DesvinculacionesAuditLogService $auditLogService,
    ) {}

    /**
     * Busca empleado activo con periodo abierto por cedula.
     *
     * @return array{ok: bool, message?: string, ficha_entry_id?: int, document_number?: string, full_name?: string}
     */
    public function lookupActiveByDocument(string $documentNumber): array
    {
        $documentNumber = trim($documentNumber);

        if ($documentNumber === '') {
            return [
                'ok' => false,
                'message' => 'Ingrese un numero de cedula.',
            ];
        }

        $entry = PersonalRequisitionFichaEntry::query()
            ->with(['profile'])
            ->where('hired_document', $documentNumber)
            ->whereHas('profile', static function ($query): void {
                $query->where('employment_status', EmployeeFichaProfile::STATUS_ACTIVO);
            })
            ->whereHas('employmentPeriods', static function ($query): void {
                $query->where('status', EmployeeFichaEmploymentPeriod::STATUS_ACTIVO);
            })
            ->first();

        if ($entry === null) {
            return [
                'ok' => false,
                'message' => 'No se encontro un empleado activo con periodo abierto para esa cedula.',
            ];
        }

        return [
            'ok' => true,
            'ficha_entry_id' => $entry->id,
            'document_number' => (string) $entry->hired_document,
            'full_name' => (string) ($entry->hired_full_name ?: $entry->profile?->full_name),
        ];
    }

    /**
     * Procesa el lote: TX por empleado (close + sync + followup); carta fuera de TX.
     * Continua ante fallos. ZIP solo con cartas exitosas.
     *
     * HTTP ZIP contract: el controlador responde JSON con ok/failed + download_token;
     * GET descarga usa Content-Disposition sobre el ZIP temporal cacheado.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array{
     *     ok: list<array<string, mixed>>,
     *     failed: list<array<string, mixed>>,
     *     summary: array{total: int, ok: int, failed: int, letters: int},
     *     zip_absolute_path: ?string,
     *     zip_download_name: ?string
     * }
     */
    public function process(array $rows, User $actor): array
    {
        $ok = [];
        $failed = [];
        $letterFiles = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 1;
            $documentNumber = trim((string) ($row['document_number'] ?? ''));

            try {
                $result = $this->processRow($row, $actor);
                $ok[] = [
                    'row' => $rowNumber,
                    'document_number' => $documentNumber,
                    'full_name' => $result['full_name'],
                    'followup_id' => $result['followup_id'],
                    'period_id' => $result['period_id'],
                    'letter_generated' => $result['letter_generated'],
                ];

                if ($result['letter_file'] !== null) {
                    $letterFiles[] = $result['letter_file'];
                }

                if ($result['letter_error'] !== null) {
                    $failed[] = [
                        'row' => $rowNumber,
                        'document_number' => $documentNumber,
                        'type' => 'letter',
                        'message' => $result['letter_error'],
                    ];
                }
            } catch (Throwable $e) {
                $failed[] = [
                    'row' => $rowNumber,
                    'document_number' => $documentNumber,
                    'type' => 'termination',
                    'message' => $this->failureMessage($e),
                ];
            }
        }

        $zipAbsolutePath = null;
        $zipDownloadName = null;

        if ($letterFiles !== []) {
            [$zipAbsolutePath, $zipDownloadName] = $this->buildBatchZip($letterFiles);
        }

        $this->auditLogService->logEvent(
            eventType: 'bulk_termination',
            action: 'process',
            metadata: [
                'total' => count($rows),
                'ok_count' => count($ok),
                'failed_count' => count($failed),
                'letter_count' => count($letterFiles),
                'ok_documents' => array_values(array_map(
                    static fn (array $item): string => (string) $item['document_number'],
                    $ok,
                )),
                'failed_documents' => array_values(array_map(
                    static fn (array $item): string => (string) $item['document_number'],
                    $failed,
                )),
                'template_ids' => array_values(array_unique(array_filter(array_map(
                    static fn (array $row): ?int => isset($row['template_id']) ? (int) $row['template_id'] : null,
                    $rows,
                )))),
            ],
            userId: $actor->id,
        );

        return [
            'ok' => $ok,
            'failed' => $failed,
            'summary' => [
                'total' => count($rows),
                'ok' => count($ok),
                'failed' => count($failed),
                'letters' => count($letterFiles),
            ],
            'zip_absolute_path' => $zipAbsolutePath,
            'zip_download_name' => $zipDownloadName,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{
     *     full_name: string,
     *     followup_id: int,
     *     period_id: int,
     *     letter_generated: bool,
     *     letter_file: ?array{absolute: string, name: string},
     *     letter_error: ?string
     * }
     */
    private function processRow(array $row, User $actor): array
    {
        $documentNumber = trim((string) ($row['document_number'] ?? ''));
        $lookup = $this->lookupActiveByDocument($documentNumber);

        if (! $lookup['ok']) {
            throw ValidationException::withMessages([
                'document_number' => $lookup['message'] ?? 'Empleado no procesable.',
            ]);
        }

        $entry = PersonalRequisitionFichaEntry::query()
            ->with('profile')
            ->findOrFail((int) $lookup['ficha_entry_id']);

        $terminationDate = (string) $row['termination_date'];
        $causeCode = trim((string) ($row['termination_cause_code'] ?? ''));
        $notes = trim((string) ($row['termination_notes'] ?? ''));

        $rehireable = null;
        if (array_key_exists('is_rehireable', $row) && $row['is_rehireable'] !== null && $row['is_rehireable'] !== '') {
            $rehireable = filter_var($row['is_rehireable'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($rehireable === null) {
                $rehireable = (bool) $row['is_rehireable'];
            }
        }

        $period = DB::transaction(function () use ($entry, $actor, $terminationDate, $causeCode, $notes, $rehireable): EmployeeFichaEmploymentPeriod {
            $closed = $this->periodService->closeActivePeriod($entry, [
                'termination_cause_code' => $causeCode !== '' ? $causeCode : null,
                'is_rehireable' => $rehireable,
                'last_work_day' => $terminationDate,
                'termination_date' => $terminationDate,
                'termination_notes' => $notes !== '' ? $notes : null,
            ], $actor->id);

            $this->periodService->syncProfileAfterTermination($entry);

            $this->followupService->ensureForClosedPeriod($closed, $entry->fresh(), $actor->id);

            return $closed->fresh();
        });

        $followup = $period->terminationFollowup
            ?? $this->followupService->ensureForClosedPeriod($period, $entry->fresh(), $actor->id);

        $letterGenerated = false;
        $letterFile = null;
        $letterError = null;

        try {
            $pack = $this->letterPackGenerator->generate(
                $period,
                $entry->fresh(['profile']),
                [(int) $row['template_id']],
                (int) $row['signatory_id'],
            );

            $this->followupService->markLetterGenerated($period, $entry->fresh(), $actor->id);
            $letterGenerated = true;

            $absolute = Storage::disk('local')->path($pack['storage_path']);
            $documentSlug = preg_replace('/\D+/', '', $documentNumber) ?: 'empleado';
            $letterFile = [
                'absolute' => $absolute,
                'name' => sprintf('%s_%s.docx', $documentSlug, Str::slug(pathinfo($pack['download_name'], PATHINFO_FILENAME), '_')),
            ];

            if (! is_file($absolute)) {
                throw new RuntimeException('No se encontro el archivo de carta generado.');
            }

            // Si el pack fue zip (no deberia con 1 plantilla), renombrar extension.
            if (($pack['output_type'] ?? '') === 'zip') {
                $letterFile['name'] = sprintf('%s_cartas.zip', $documentSlug);
            }
        } catch (Throwable $e) {
            $letterError = $this->failureMessage($e);
            $followup->refresh();
            if ($followup->letter_generated) {
                $followup->letter_generated = false;
                $followup->save();
            }
        }

        return [
            'full_name' => (string) ($lookup['full_name'] ?? $entry->hired_full_name),
            'followup_id' => (int) $followup->id,
            'period_id' => (int) $period->id,
            'letter_generated' => $letterGenerated,
            'letter_file' => $letterFile,
            'letter_error' => $letterError,
        ];
    }

    /**
     * @param  list<array{absolute: string, name: string}>  $letterFiles
     * @return array{0: string, 1: string}
     */
    private function buildBatchZip(array $letterFiles): array
    {
        $downloadName = 'desvinculaciones_'.now()->format('Ymd_His').'.zip';
        $absolutePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'desvinculaciones-batch-'.Str::uuid()->toString().'.zip';

        $zip = new ZipArchive;
        if ($zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el ZIP del lote.');
        }

        $usedNames = [];
        foreach ($letterFiles as $file) {
            $name = $file['name'];
            if (isset($usedNames[$name])) {
                $usedNames[$name]++;
                $name = pathinfo($name, PATHINFO_FILENAME).'_'.$usedNames[$file['name']].'.'.pathinfo($name, PATHINFO_EXTENSION);
            } else {
                $usedNames[$name] = 1;
            }

            $contents = @file_get_contents($file['absolute']);
            if ($contents === false) {
                $zip->close();
                @unlink($absolutePath);
                throw new RuntimeException('No se pudo leer una carta para el ZIP del lote.');
            }

            $zip->addFromString($name, $contents);
        }

        $zip->close();

        return [$absolutePath, $downloadName];
    }

    private function failureMessage(Throwable $e): string
    {
        if ($e instanceof ValidationException) {
            $messages = collect($e->errors())->flatten()->filter()->values();

            return $messages->isNotEmpty()
                ? (string) $messages->first()
                : 'Validacion fallida.';
        }

        return $e->getMessage() !== ''
            ? $e->getMessage()
            : 'Error al procesar la fila.';
    }
}
