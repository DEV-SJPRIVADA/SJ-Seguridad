<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\EmployeeFichaProfile;
use App\Models\EmployeeTerminationFollowup;
use App\Models\PayrollCatalogItem;
use App\Models\PersonalRequisitionFichaEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

/**
 * Importa histórico de la hoja NOVEDADES (plantilla Excel desvinculaciones)
 * hacia Seguimientos + periodos cerrados + Retiros, sin generar cartas.
 */
final class HistoricalTerminationImportService
{
    public const SHEET_NAME = 'NOVEDADES';

    public const MIN_TERMINATION_DATE = '2025-05-01';

    /**
     * Alias Excel → código catálogo Ficha (termination_cause).
     *
     * @var array<string, string>
     */
    private const CAUSE_CODE_ALIASES = [
        'RENUNCIA' => 'RENUNCIA',
        'TERMINACION DE CONTRATO' => 'FIN_CONTRATO',
        'TERMINACIÓN DE CONTRATO' => 'FIN_CONTRATO',
        'PERIODO DE PRUEBA' => 'PERIODO_PRUEBA',
        'PERÍODO DE PRUEBA' => 'PERIODO_PRUEBA',
        'SIN JUSTA CAUSA' => 'SIN_JUSTA_CAUSA',
        'CON JUSTA CAUSA' => 'CON_JUSTA_CAUSA',
        'FALLECIMIENTO' => 'FALLECIMIENTO',
        'LEGALIZACION INASISTENCIAS' => 'LEGALIZACION_INASISTENCIAS',
        'LEGALIZACIÓN INASISTENCIAS' => 'LEGALIZACION_INASISTENCIAS',
    ];

    /**
     * Columnas check Excel (H–O) → campos followup.
     *
     * @var array<string, string>
     */
    private const CHECK_COLUMNS = [
        'H' => 'check_orden_examenes',
        'I' => 'check_enviado',
        'J' => 'check_control_roll',
        'K' => 'check_retiro_arl',
        'L' => 'check_retiro_cesantias',
        'M' => 'check_recibido',
        'N' => 'check_paz_y_salvo',
        'O' => 'check_reporte_noved',
    ];

    public function __construct(
        private readonly EmployeeFichaEmploymentPeriodService $periodService,
        private readonly ReportesNovedadesRetiroSyncService $retiroSyncService,
    ) {}

    /**
     * @return array{
     *     scanned: int,
     *     created: int,
     *     updated: int,
     *     created_keep_activo: int,
     *     skipped_no_ficha: int,
     *     errors: list<string>
     * }
     */
    public function import(string $path, bool $dryRun = false, ?int $limit = null, ?int $userId = null): array
    {
        $stats = [
            'scanned' => 0,
            'created' => 0,
            'updated' => 0,
            'created_keep_activo' => 0,
            'skipped_no_ficha' => 0,
            'errors' => [],
        ];

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $workbook = $reader->load($path);
        $sheet = $workbook->getSheetByName(self::SHEET_NAME);

        if ($sheet === null) {
            throw new \InvalidArgumentException('No se encontro la hoja '.self::SHEET_NAME.' en el archivo.');
        }

        $highestRow = (int) $sheet->getHighestDataRow();
        $actorId = $userId !== null && $userId > 0 ? $userId : 1;

        for ($row = 2; $row <= $highestRow; $row++) {
            if ($limit !== null && $stats['scanned'] >= $limit) {
                break;
            }

            try {
                $parsed = $this->parseRow($sheet, $row);
            } catch (Throwable $e) {
                $stats['errors'][] = 'Fila '.$row.': '.$e->getMessage();

                continue;
            }

            if ($parsed === null) {
                continue;
            }

            $stats['scanned']++;

            try {
                $result = $dryRun
                    ? $this->previewRow($parsed)
                    : $this->persistRow($parsed, $actorId);

                if (! array_key_exists($result, $stats)) {
                    $stats['errors'][] = 'Fila '.$row.': resultado desconocido '.$result;

                    continue;
                }

                $stats[$result]++;
            } catch (Throwable $e) {
                $stats['errors'][] = 'Fila '.$row.' ('.$parsed['document_number'].'): '.$e->getMessage();
            }
        }

        return $stats;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseRow(Worksheet $sheet, int $row): ?array
    {
        $document = preg_replace('/\D+/', '', (string) $sheet->getCell('B'.$row)->getValue()) ?? '';
        $fullName = trim((string) $sheet->getCell('C'.$row)->getFormattedValue());

        if ($document === '' && $fullName === '') {
            return null;
        }

        if ($document === '') {
            throw new \RuntimeException('CEDULA vacia.');
        }

        $terminationDate = $this->excelDate($sheet->getCell('G'.$row)->getValue())
            ?? $this->excelDate($sheet->getCell('F'.$row)->getValue());

        if ($terminationDate === null) {
            throw new \RuntimeException('Sin FECHA DE RET / FECHA DE CREACION valida.');
        }

        if ($terminationDate < self::MIN_TERMINATION_DATE) {
            return null;
        }

        $registeredAt = $this->excelDateTime($sheet->getCell('F'.$row)->getValue())
            ?? $terminationDate.' 00:00:00';

        $checks = [];
        foreach (self::CHECK_COLUMNS as $col => $field) {
            $checks[$field] = $this->excelBool($sheet->getCell($col.$row)->getValue());
        }

        $causeLabel = trim((string) $sheet->getCell('E'.$row)->getFormattedValue());
        [$causeCode, $causeName] = $this->mapCause($causeLabel);

        return [
            'excel_row' => $row,
            'document_number' => $document,
            'full_name' => $fullName !== '' ? $fullName : $document,
            'position_name' => trim((string) $sheet->getCell('D'.$row)->getFormattedValue()) ?: null,
            'termination_cause_code' => $causeCode,
            'termination_cause_name' => $causeName,
            'termination_date' => $terminationDate,
            'registered_at' => $registeredAt,
            'payroll_delivered_at' => $this->excelDate($sheet->getCell('Q'.$row)->getValue()),
            'termination_notes' => trim((string) $sheet->getCell('X'.$row)->getFormattedValue()) ?: null,
            'checks' => $checks,
        ];
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    private function previewRow(array $parsed): string
    {
        $entry = $this->resolveEntry($parsed['document_number']);
        if ($entry === null) {
            return 'skipped_no_ficha';
        }

        $existing = $this->findExistingFollowup($entry, $parsed['termination_date']);
        if ($existing !== null) {
            return 'updated';
        }

        $entry->loadMissing('profile');
        if ($this->shouldKeepActivo($entry->profile, $entry, $parsed['termination_date'])) {
            return 'created_keep_activo';
        }

        return 'created';
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    private function persistRow(array $parsed, int $userId): string
    {
        return DB::transaction(function () use ($parsed, $userId): string {
            $entry = $this->resolveEntry($parsed['document_number']);
            if ($entry === null) {
                return 'skipped_no_ficha';
            }

            $entry->loadMissing('profile', 'activeEmploymentPeriod');
            $profile = $entry->profile;
            $keepActivo = $this->shouldKeepActivo($profile, $entry, $parsed['termination_date']);

            $this->ensureCauseCatalog($parsed['termination_cause_code'], $parsed['termination_cause_name']);

            $existing = $this->findExistingFollowup($entry, $parsed['termination_date']);
            if ($existing !== null) {
                $this->updateFollowup($existing, $parsed);
                $this->retiroSyncService->ensureFromFollowup($existing->fresh(), $userId);

                return 'updated';
            }

            $period = $this->resolveOrCreateClosedPeriod($entry, $parsed, $userId, $keepActivo);

            EmployeeTerminationFollowup::query()->create([
                'personal_requisition_ficha_entry_id' => $entry->id,
                'employee_ficha_employment_period_id' => $period->id,
                'document_number' => $parsed['document_number'],
                'full_name' => $parsed['full_name'],
                'position_name' => $parsed['position_name'] ?? $period->position_name,
                'termination_cause_code' => $parsed['termination_cause_code'],
                'termination_cause_name' => $parsed['termination_cause_name'],
                'is_rehireable' => null,
                'termination_notes' => $parsed['termination_notes'],
                'termination_date' => $parsed['termination_date'],
                'registered_at' => $parsed['registered_at'],
                'letter_generated' => false,
                'payroll_delivered_at' => $parsed['payroll_delivered_at'],
                'created_by' => $userId,
                ...$parsed['checks'],
            ]);

            $followup = EmployeeTerminationFollowup::query()
                ->where('employee_ficha_employment_period_id', $period->id)
                ->firstOrFail();

            $this->retiroSyncService->ensureFromFollowup($followup, $userId);

            return $keepActivo ? 'created_keep_activo' : 'created';
        });
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    private function updateFollowup(EmployeeTerminationFollowup $followup, array $parsed): void
    {
        $followup->fill([
            'full_name' => $parsed['full_name'],
            'position_name' => $parsed['position_name'] ?? $followup->position_name,
            'termination_cause_code' => $parsed['termination_cause_code'],
            'termination_cause_name' => $parsed['termination_cause_name'],
            'termination_notes' => $parsed['termination_notes'],
            'registered_at' => $parsed['registered_at'],
            'payroll_delivered_at' => $parsed['payroll_delivered_at'],
            // Nunca forzar carta generada en import histórico.
            'letter_generated' => false,
            ...$parsed['checks'],
        ]);
        $followup->save();

        $period = $followup->employmentPeriod;
        if ($period !== null) {
            $period->fill([
                'termination_cause_code' => $parsed['termination_cause_code'],
                'termination_cause_name' => $parsed['termination_cause_name'],
                'termination_date' => $parsed['termination_date'],
                'last_work_day' => $parsed['termination_date'],
                'termination_notes' => $parsed['termination_notes'],
                'position_name' => $parsed['position_name'] ?? $period->position_name,
            ]);
            $period->save();
        }
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    private function resolveOrCreateClosedPeriod(
        PersonalRequisitionFichaEntry $entry,
        array $parsed,
        int $userId,
        bool $keepActivo,
    ): EmployeeFichaEmploymentPeriod {
        $existingPeriod = EmployeeFichaEmploymentPeriod::query()
            ->where('personal_requisition_ficha_entry_id', $entry->id)
            ->where('status', EmployeeFichaEmploymentPeriod::STATUS_CERRADO)
            ->whereDate('termination_date', $parsed['termination_date'])
            ->orderByDesc('id')
            ->first();

        if ($existingPeriod !== null) {
            return $existingPeriod;
        }

        $active = $this->periodService->activePeriod($entry);

        if (! $keepActivo && $active !== null) {
            $closed = $this->periodService->closeActivePeriod($entry, [
                'termination_cause_code' => $parsed['termination_cause_code'],
                'termination_date' => $parsed['termination_date'],
                'last_work_day' => $parsed['termination_date'],
                'termination_notes' => $parsed['termination_notes'],
                'is_rehireable' => null,
            ], $userId);

            // Forzar nombre Excel si el catálogo resolvió otro label.
            $closed->forceFill([
                'termination_cause_name' => $parsed['termination_cause_name'],
                'position_name' => $parsed['position_name'] ?? $closed->position_name,
            ])->save();

            $this->periodService->syncProfileAfterTermination($entry);

            return $closed->fresh();
        }

        $sequence = (int) EmployeeFichaEmploymentPeriod::query()
            ->where('personal_requisition_ficha_entry_id', $entry->id)
            ->max('sequence') + 1;

        $profile = $entry->profile;
        $attrs = $profile !== null
            ? $this->periodService->periodAttributesFromProfile($profile)
            : [];

        // Periodo histórico: no heredar hire_date posterior a la desvinculación.
        if (isset($attrs['hire_date']) && $attrs['hire_date'] !== null) {
            $hire = (string) $attrs['hire_date'];
            if ($hire > $parsed['termination_date']) {
                $attrs['hire_date'] = null;
            }
        }

        $period = EmployeeFichaEmploymentPeriod::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'personal_requisition_id' => $entry->personal_requisition_id,
            'sequence' => max(1, $sequence),
            'status' => EmployeeFichaEmploymentPeriod::STATUS_CERRADO,
            'opened_by' => $userId,
            'closed_by' => $userId,
            'termination_cause_code' => $parsed['termination_cause_code'],
            'termination_cause_name' => $parsed['termination_cause_name'],
            'termination_date' => $parsed['termination_date'],
            'last_work_day' => $parsed['termination_date'],
            'termination_notes' => $parsed['termination_notes'],
            'is_rehireable' => null,
            ...$attrs,
            'position_name' => $parsed['position_name'] ?? ($attrs['position_name'] ?? null),
        ]);

        if (! $keepActivo && $profile !== null) {
            $profile->forceFill([
                'employment_status' => EmployeeFichaProfile::STATUS_DESVINCULADO,
                'termination_date' => $parsed['termination_date'],
            ])->save();
        }

        return $period->fresh();
    }

    private function findExistingFollowup(
        PersonalRequisitionFichaEntry $entry,
        string $terminationDate,
    ): ?EmployeeTerminationFollowup {
        return EmployeeTerminationFollowup::query()
            ->where('personal_requisition_ficha_entry_id', $entry->id)
            ->whereDate('termination_date', $terminationDate)
            ->orderByDesc('id')
            ->first();
    }

    private function resolveEntry(string $documentNumber): ?PersonalRequisitionFichaEntry
    {
        $profile = EmployeeFichaProfile::query()
            ->where('document_number', $documentNumber)
            ->first();

        if ($profile?->personal_requisition_ficha_entry_id) {
            return PersonalRequisitionFichaEntry::query()->find($profile->personal_requisition_ficha_entry_id);
        }

        return PersonalRequisitionFichaEntry::query()
            ->where('hired_document', $documentNumber)
            ->orderByDesc('id')
            ->first();
    }

    private function shouldKeepActivo(
        ?EmployeeFichaProfile $profile,
        PersonalRequisitionFichaEntry $entry,
        string $terminationDate,
    ): bool {
        $hireCandidates = [];

        if ($profile?->hire_date !== null) {
            $hireCandidates[] = $profile->hire_date->toDateString();
        }

        $active = $this->periodService->activePeriod($entry);
        if ($active?->hire_date !== null) {
            $hireCandidates[] = $active->hire_date->toDateString();
        }

        if ($hireCandidates === []) {
            return false;
        }

        $latestHire = max($hireCandidates);

        // Ingreso/contrato actual posterior al retiro del Excel: no desvincular el vínculo vigente.
        return $latestHire > $terminationDate;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function mapCause(string $label): array
    {
        $normalized = mb_strtoupper(trim($label));
        $normalized = str_replace(['Á', 'É', 'Í', 'Ó', 'Ú'], ['A', 'E', 'I', 'O', 'U'], $normalized);

        if ($normalized === '') {
            return ['DESCONOCIDO', 'Sin causal'];
        }

        $code = self::CAUSE_CODE_ALIASES[$normalized]
            ?? self::CAUSE_CODE_ALIASES[$label]
            ?? (preg_replace('/\s+/', '_', $normalized) ?: 'DESCONOCIDO');

        $code = mb_substr((string) $code, 0, 50);

        return [$code, $label !== '' ? $label : $code];
    }

    private function ensureCauseCatalog(string $code, string $name): void
    {
        PayrollCatalogItem::upsertPair('termination_cause', $code, $name);
    }

    private function excelDate(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_numeric($raw) && (float) $raw > 30000) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $raw)->format('Y-m-d');
            } catch (Throwable) {
                return null;
            }
        }

        $text = trim((string) $raw);
        if ($text === '') {
            return null;
        }

        try {
            return Carbon::parse($text)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private function excelDateTime(mixed $raw): ?string
    {
        $date = $this->excelDate($raw);

        return $date !== null ? $date.' 12:00:00' : null;
    }

    private function excelBool(mixed $raw): bool
    {
        if (is_bool($raw)) {
            return $raw;
        }

        if (is_numeric($raw)) {
            return (int) $raw === 1;
        }

        $text = mb_strtoupper(trim((string) $raw));

        return in_array($text, ['1', 'TRUE', 'SI', 'SÍ', 'YES', 'X'], true);
    }
}
