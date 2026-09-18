<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeFichaProfile;
use App\Models\PayrollCatalogItem;
use App\Models\PersonalRequisition;
use App\Models\PersonalRequisitionFichaEntry;
use App\Support\ColombianCurrencyParser;
use App\Support\ImportFailureRow;
use App\Support\SpreadsheetCellReader;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeFichaImportService
{
    public function __construct(
        private readonly EmployeeFichaProfileCatalogSync $profileCatalogSync,
        private readonly EmployeeFichaImportValueNormalizer $valueNormalizer,
    ) {}

    /**
     * @return array{imported: int, updated: int, skipped: int, empty_rows: int, errors: list<string>, failures: list<array<string, mixed>>}
     */
    public function import(string $path, bool $dryRun = false, ?int $userId = null): array
    {
        if (! is_readable($path)) {
            throw new \InvalidArgumentException('No se puede leer el archivo: '.$path);
        }

        $stats = ['imported' => 0, 'updated' => 0, 'skipped' => 0, 'empty_rows' => 0, 'errors' => [], 'failures' => []];
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $headers = $this->readHeaders($sheet);

        if ($headers === []) {
            throw new \RuntimeException('El archivo no tiene encabezados validos en la fila 1.');
        }

        $maxRow = (int) $sheet->getHighestRow();

        for ($row = 3; $row <= $maxRow; $row++) {
            $data = $this->readRow($sheet, $row, $headers);
            $cedula = trim((string) ($data['cedula'] ?? ''));

            if ($cedula === '') {
                $stats['empty_rows']++;
                $stats['failures'][] = ImportFailureRow::make(
                    $row,
                    null,
                    'Cedula',
                    ImportFailureRow::SEVERITY_EMPTY,
                    'Fila sin cedula (ignorada).',
                    $data,
                );

                continue;
            }

            try {
                if ($dryRun) {
                    $stats['imported']++;

                    continue;
                }

                DB::transaction(function () use ($data, $cedula, $userId, &$stats): void {
                    $existing = EmployeeFichaProfile::query()->where('document_number', $cedula)->first();
                    $payload = $this->mapImportPayload($data, $cedula);
                    $entry = $this->resolveFichaEntry($cedula, $data, $userId);

                    if ($entry !== null) {
                        $payload['personal_requisition_ficha_entry_id'] = $entry->id;
                    }

                    if ($existing !== null) {
                        $existingExtra = is_array($existing->payroll_extra) ? $existing->payroll_extra : [];
                        $payload['payroll_extra'] = array_merge(
                            $existingExtra,
                            is_array($payload['payroll_extra'] ?? null) ? $payload['payroll_extra'] : [],
                        );
                        $existing->update($payload);
                        $existing->syncEmploymentStatusFromTerminationDate();
                        $this->profileCatalogSync->syncAndSave($existing);
                        $stats['updated']++;
                    } else {
                        $profile = EmployeeFichaProfile::query()->create($payload);
                        $profile->syncEmploymentStatusFromTerminationDate();
                        $this->profileCatalogSync->syncAndSave($profile);
                        $stats['imported']++;
                    }
                });
            } catch (\Throwable $e) {
                $failure = ImportFailureRow::make(
                    $row,
                    $cedula,
                    'Cedula',
                    ImportFailureRow::SEVERITY_ERROR,
                    $e->getMessage(),
                    $data,
                );
                $stats['failures'][] = $failure;
                $stats['errors'][] = ImportFailureRow::message($failure);
                $stats['skipped']++;
            }
        }

        return $stats;
    }

    /**
     * @return array<string, int>
     */
    private function readHeaders(Worksheet $sheet): array
    {
        $headers = [];
        $maxCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        for ($col = 1; $col <= $maxCol; $col++) {
            $key = trim((string) SpreadsheetCellReader::rawValue($sheet, $col, 1));
            if ($key !== '') {
                $headers[$key] = $col;
            }
        }

        return $headers;
    }

    /**
     * @param  array<string, int>  $headers
     * @return array<string, mixed>
     */
    private function readRow(Worksheet $sheet, int $row, array $headers): array
    {
        $data = [];
        foreach ($headers as $key => $col) {
            $data[$key] = SpreadsheetCellReader::value($sheet, $col, $row);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mapImportPayload(array $data, string $cedula): array
    {
        $nameParts = $this->resolveImportNameParts($data);

        $documentType = $this->valueNormalizer->documentType($data['tipo_documento'] ?? null);
        $sex = $this->valueNormalizer->sex($data['sexo'] ?? null);
        $accountType = $this->valueNormalizer->accountType($data['tipo_de_cuenta'] ?? null);
        $riskLevel = $this->valueNormalizer->riskLevel($data['nivel_riesgo_arp'] ?? null);
        $salaryType = $this->valueNormalizer->salaryType($data['tipo_salario'] ?? null);
        $contractType = $this->valueNormalizer->contractType($data['tipo_contrato'] ?? null);
        $paymentMethod = $this->valueNormalizer->paymentMethod($data['forma_pago'] ?? null);
        $bank = $this->valueNormalizer->bank($data['banco'] ?? null);
        $linkageType = $this->valueNormalizer->linkageType($data['tipo_vinculacion'] ?? null);

        $payload = [
            'document_number' => $cedula,
            'full_name' => $nameParts['full_name'],
            'first_surname' => $nameParts['first_surname'],
            'second_surname' => $nameParts['second_surname'],
            'first_name' => $nameParts['first_name'],
            'second_name' => $nameParts['second_name'],
            'document_type' => $documentType,
            'birth_date' => $this->parseDate($data['fecha_nac'] ?? null),
            'expedition_city_code' => $this->stringOrNull($data['codigo_lugar_exp_cedula'] ?? null),
            'expedition_city_name' => $this->stringOrNull($data['lugar_exp_cedula'] ?? null),
            'expedition_date' => $this->parseDate($data['fecha_expedicion'] ?? null),
            'residence_city_code' => $this->stringOrNull($data['codigo_lugar_residencia'] ?? null),
            'residence_city_name' => $this->stringOrNull($data['lugar_residencia'] ?? null),
            'work_city_code' => $this->stringOrNull($data['codigo_ciudad_trabajo'] ?? null),
            'work_city_name' => $this->stringOrNull($data['ciudad_trabajo'] ?? null),
            'address' => $this->stringOrNull($data['direccion'] ?? null),
            'phone' => $this->stringOrNull($data['telefono'] ?? null),
            'blood_type' => $this->stringOrNull($data['tipo_sangre'] ?? null),
            'sex' => $sex,
            'salary' => $this->numericOrNull($data['salario'] ?? null),
            'education_level' => $this->stringOrNull($data['escolaridad'] ?? null),
            'marital_status' => $this->stringOrNull($data['estado_civil'] ?? null),
            'children_count' => $this->intOrNull($data['numero_hijos'] ?? null),
            'email' => $this->stringOrNull($data['email'] ?? null),
            'linkage_type' => $linkageType,
            'hire_date' => $this->parseDate($data['fecha_ingreso'] ?? null),
            'contract_end_date' => $this->parseDate($data['fecha_vencimiento_contrato'] ?? null),
            'termination_date' => $this->parseDate($data['fecha_retiro'] ?? null),
            'work_center_name' => $this->stringOrNull($data['nombre_centro_trabajo'] ?? null),
            'cost_center_code' => $this->stringOrNull($data['ccosto'] ?? null),
            'cost_center_name' => $this->stringOrNull($data['nombre_ccosto'] ?? null),
            'position_code' => $this->stringOrNull($data['cargo'] ?? null),
            'position_name' => $this->stringOrNull($data['nombre_cargo'] ?? null),
            'salary_type_code' => $salaryType,
            'contract_type_code' => $contractType,
            'eps_code' => $this->stringOrNull($data['codigo_eps'] ?? null),
            'eps_name' => $this->stringOrNull($data['nombre_eps'] ?? null),
            'afp_code' => $this->stringOrNull($data['codigo_afp'] ?? null),
            'afp_name' => $this->stringOrNull($data['nombre_afp'] ?? null),
            'arp_name' => $this->stringOrNull($data['nombre_arp'] ?? null),
            'risk_level' => $riskLevel,
            'compensation_fund_name' => $this->stringOrNull($data['nombre_caja_compensacion'] ?? null),
            'bank_code' => $bank,
            'account_type' => $accountType,
            'account_number' => $this->stringOrNull($data['cuenta'] ?? null),
            'payment_method_code' => $paymentMethod,
            'economic_activity_code' => $this->stringOrNull($data['actividad_economica'] ?? null),
            'economic_activity_name' => $this->stringOrNull($data['nombre_actividad_economica'] ?? null),
            'payroll_extra' => $this->mapPayrollExtraFromImport($data),
        ];

        $this->seedCatalogPairsFromRow($data, $payload);

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mapPayrollExtraFromImport(array $data): array
    {
        $extra = [];

        $age = $this->intOrNull($data['edad'] ?? null);
        if ($age !== null) {
            $extra['age'] = $age;
        }

        $contributorType = $this->stringOrNull($data['tipo_cotizante'] ?? null);
        if ($contributorType !== null) {
            $extra['contributor_type'] = $contributorType;
        }

        $scale = $this->stringOrNull($data['escala'] ?? null);
        if ($scale !== null) {
            $extra['salary_scale'] = $scale;
        }

        $lastVacationPeriod = $this->parseDate($data['ultimo_periodo_vacaciones'] ?? null);
        if ($lastVacationPeriod !== null) {
            $extra['last_vacation_period'] = $lastVacationPeriod;
        }

        foreach ([
            'total_dias_vacaciones' => 'total_vacation_days',
            'periodos_pendientes_vacaciones' => 'pending_vacation_periods',
            'excedente_pdte_vacaciones' => 'vacation_excess_days',
            'dias_pendientes_a_disfrutar' => 'vacation_days_to_enjoy',
            'valor_pendiente_de_vacaciones' => 'pending_vacation_value',
        ] as $importKey => $extraKey) {
            $numeric = $this->numericOrNull($data[$importKey] ?? null);
            if ($numeric !== null) {
                $extra[$extraKey] = $numeric;
            }
        }

        return $extra;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveFichaEntry(string $cedula, array $data, ?int $userId): ?PersonalRequisitionFichaEntry
    {
        $entry = PersonalRequisitionFichaEntry::query()
            ->where('hired_document', $cedula)
            ->first();

        if ($entry !== null) {
            if ($entry->moved_to_ficha_at === null) {
                $entry->update([
                    'moved_to_ficha_at' => now(),
                    'moved_to_ficha_by' => $userId,
                ]);
            }

            return $entry;
        }

        $requisitionId = null;
        $code = trim((string) ($data['codigo_requisicion'] ?? ''));

        if ($code !== '') {
            $requisitionId = PersonalRequisition::query()->where('code', $code)->value('id');
        }

        $nameParts = $this->resolveImportNameParts($data);

        return PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisitionId,
            'hired_document' => $cedula,
            'hired_full_name' => $nameParts['full_name'] !== '' ? $nameParts['full_name'] : $cedula,
            'first_surname' => $nameParts['first_surname'],
            'second_surname' => $nameParts['second_surname'],
            'first_name' => $nameParts['first_name'],
            'second_name' => $nameParts['second_name'],
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $userId,
            'created_by' => $userId,
        ]);
    }

    /**
     * Prefiere columnas partido; si faltan, deriva desde `nombre` (plantillas antiguas).
     *
     * @param  array<string, mixed>  $data
     * @return array{full_name: string, first_surname: ?string, second_surname: ?string, first_name: ?string, second_name: ?string}
     */
    private function resolveImportNameParts(array $data): array
    {
        $firstSurname = $this->stringOrNull($data['primer_apellido'] ?? null);
        $secondSurname = $this->stringOrNull($data['segundo_apellido'] ?? null);
        $firstName = $this->stringOrNull($data['primer_nombre'] ?? null);
        $secondName = $this->stringOrNull($data['segundo_nombre'] ?? null);
        $nombre = trim((string) ($data['nombre'] ?? ''));
        $hasParts = $firstSurname !== null || $secondSurname !== null || $firstName !== null || $secondName !== null;

        if ($hasParts) {
            $composed = implode(' ', array_filter(
                [$firstSurname, $secondSurname, $firstName, $secondName],
                fn (?string $part): bool => $part !== null && $part !== '',
            ));

            return [
                'full_name' => $composed !== '' ? $composed : $nombre,
                'first_surname' => $firstSurname,
                'second_surname' => $secondSurname,
                'first_name' => $firstName,
                'second_name' => $secondName,
            ];
        }

        $parsed = EmployeeFichaNameParser::parse($nombre);

        return [
            'full_name' => $parsed['full_name'] !== '' ? $parsed['full_name'] : $nombre,
            'first_surname' => $parsed['first_surname'],
            'second_surname' => $parsed['second_surname'],
            'first_name' => $parsed['first_name'],
            'second_name' => $parsed['second_name'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $payload
     */
    private function seedCatalogPairsFromRow(array $data, array $payload): void
    {
        PayrollCatalogItem::upsertPair('document_type', $payload['document_type'] ?? null, $payload['document_type'] ?? null);
        PayrollCatalogItem::upsertPair('city', $this->stringOrNull($data['codigo_lugar_residencia'] ?? null), $this->stringOrNull($data['lugar_residencia'] ?? null));
        PayrollCatalogItem::upsertPair('city', $this->stringOrNull($data['codigo_ciudad_trabajo'] ?? null), $this->stringOrNull($data['ciudad_trabajo'] ?? null));
        PayrollCatalogItem::upsertPair('position', $this->stringOrNull($data['cargo'] ?? null), $this->stringOrNull($data['nombre_cargo'] ?? null));
        PayrollCatalogItem::upsertPair('cost_center', $this->stringOrNull($data['ccosto'] ?? null), $this->stringOrNull($data['nombre_ccosto'] ?? null));
        PayrollCatalogItem::upsertPair('eps', $this->stringOrNull($data['codigo_eps'] ?? null), $this->stringOrNull($data['nombre_eps'] ?? null));
        PayrollCatalogItem::upsertPair('afp', $this->stringOrNull($data['codigo_afp'] ?? null), $this->stringOrNull($data['nombre_afp'] ?? null));
        PayrollCatalogItem::upsertPair('arp', null, $this->stringOrNull($data['nombre_arp'] ?? null));
        PayrollCatalogItem::upsertPair('bank', $payload['bank_code'] ?? null, $this->stringOrNull($data['banco'] ?? null));
        PayrollCatalogItem::upsertPair('payment_method', $payload['payment_method_code'] ?? null, $this->stringOrNull($data['forma_pago'] ?? null));
        PayrollCatalogItem::upsertPair('contract_type', $payload['contract_type_code'] ?? null, $this->stringOrNull($data['tipo_contrato'] ?? null));
        PayrollCatalogItem::upsertPair('salary_type', $payload['salary_type_code'] ?? null, $this->stringOrNull($data['tipo_salario'] ?? null));
        PayrollCatalogItem::upsertPair('account_type', $payload['account_type'] ?? null, $payload['account_type'] ?? null);
        PayrollCatalogItem::upsertPair('risk_level', $payload['risk_level'] ?? null, $this->stringOrNull($data['nivel_riesgo_arp'] ?? null));
        PayrollCatalogItem::upsertPair('linkage_type', $payload['linkage_type'] ?? null, $this->stringOrNull($data['tipo_vinculacion'] ?? null));
        PayrollCatalogItem::upsertPair('economic_activity', $this->stringOrNull($data['actividad_economica'] ?? null), $this->stringOrNull($data['nombre_actividad_economica'] ?? null));
    }

    private function stringOrNull(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function numericOrNull(mixed $value): ?float
    {
        return ColombianCurrencyParser::parse($value);
    }

    private function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(Date::excelToDateTimeObject((float) $value))->toDateString();
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
