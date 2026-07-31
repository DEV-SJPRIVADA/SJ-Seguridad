<?php

namespace App\Services\Requisitions;

use App\Models\PersonalRequisition;
use App\Models\PersonalRequisitionFichaEntry;
use App\Models\RequisitionCity;
use App\Models\RequisitionClient;
use App\Models\RequisitionClientType;
use App\Models\RequisitionContractType;
use App\Models\RequisitionPosition;
use App\Models\RequisitionProgrammingType;
use App\Models\RequisitionRequestReason;
use App\Models\RequisitionUniform;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RequisicionesTxtImporter
{
    private const COL_LEGACY_ID = 1;

    private const COL_REQUEST_DATE = 2;

    private const COL_LEADER = 3;

    private const COL_REQUESTING_AREA = 4;

    private const COL_POSITION = 5;

    private const COL_SEX = 6;

    private const COL_QUANTITY = 7;

    private const COL_REPLACEMENT_DOC = 8;

    private const COL_REPLACEMENT_NAME = 9;

    private const COL_CONTRACT_TYPE = 10;

    private const COL_OPERATING_AREA = 11;

    private const COL_REASON = 12;

    private const COL_CONTRACT_DURATION = 13;

    private const COL_BASE_SALARY = 14;

    private const COL_TRANSPORT = 15;

    private const COL_MOBILITY = 16;

    private const COL_STATUTORY_BONUS = 17;

    private const COL_NON_STATUTORY_BONUS = 18;

    private const COL_OTHER_ALLOWANCES = 19;

    private const COL_LEASING = 20;

    private const COL_CLIENT = 21;

    private const COL_CITY = 22;

    private const COL_CLIENT_TYPE = 23;

    private const COL_PROGRAMMING = 24;

    private const COL_PROFILE = 25;

    private const COL_UNIFORM = 26;

    private const COL_STATUS = 27;

    private const COL_RECRUITER = 28;

    private const COL_HIRED_DOC = 35;

    private const COL_HIRED_NAME = 36;

    private const COL_HIRING_DATE = 37;

    private const COL_OBSERVATION = 39;

    /** @var array<string, int> */
    private array $catalogCache = [];

    private ?int $defaultUserId = null;

    /**
     * @return array{imported: int, skipped: int, ficha_entries: int, errors: list<string>}
     */
    public function import(string $path, bool $dryRun = false, ?int $limit = null, bool $fresh = false): array
    {
        if (! is_readable($path)) {
            throw new \InvalidArgumentException('No se puede leer el archivo: '.$path);
        }

        $stats = [
            'imported' => 0,
            'skipped' => 0,
            'ficha_entries' => 0,
            'errors' => [],
        ];

        if ($fresh && ! $dryRun) {
            DB::table('personal_requisition_ficha_entries')->delete();
            PersonalRequisition::query()->where('code', 'like', 'REQ-IMP-%')->delete();
        }

        $userId = $this->defaultUserId();

        foreach ($this->parseRows($path) as $row) {
            if ($limit !== null && $limit <= $stats['imported'] + $stats['skipped']) {
                break;
            }

            try {
                $legacyId = (int) ($row[self::COL_LEGACY_ID] ?? 0);
                if ($legacyId <= 0) {
                    $stats['skipped']++;

                    continue;
                }

                $code = $this->legacyCode($legacyId);

                if (PersonalRequisition::query()->where('code', $code)->exists()) {
                    $stats['skipped']++;

                    continue;
                }

                $payload = $this->mapRow($row, $userId, $code, $legacyId);

                if ($dryRun) {
                    $stats['imported']++;

                    continue;
                }

                DB::transaction(function () use ($payload, &$stats): void {
                    $requisition = PersonalRequisition::query()->create($payload['requisition']);

                    if ($payload['ficha'] !== null) {
                        PersonalRequisitionFichaEntry::query()->create([
                            ...$payload['ficha'],
                            'personal_requisition_id' => $requisition->id,
                        ]);
                        $stats['ficha_entries']++;
                    }

                    $stats['imported']++;
                });
            } catch (\Throwable $e) {
                $legacy = $row[self::COL_LEGACY_ID] ?? '?';
                $stats['errors'][] = "Fila legacy {$legacy}: {$e->getMessage()}";
                $stats['skipped']++;
            }
        }

        return $stats;
    }

    /**
     * @return \Generator<int, array<int, string>>
     */
    private function parseRows(string $path): \Generator
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new \RuntimeException('No se pudo abrir el archivo.');
        }

        try {
            while (($line = fgets($handle)) !== false) {
                $line = $this->normalizeEncoding(trim($line));

                if ($line === '' || str_starts_with($line, '---') || str_contains($line, 'ID_REQUISICI')) {
                    continue;
                }

                if (! str_starts_with($line, '|')) {
                    continue;
                }

                $cells = array_map(
                    fn (string $cell): string => trim($cell),
                    explode('|', $line)
                );

                if (! isset($cells[self::COL_LEGACY_ID]) || ! is_numeric($cells[self::COL_LEGACY_ID])) {
                    continue;
                }

                yield $cells;
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  array<int, string>  $row
     * @return array{requisition: array<string, mixed>, ficha: ?array<string, mixed>}
     */
    private function mapRow(array $row, int $userId, string $code, int $legacyId): array
    {
        $status = $this->mapStatus($this->cell($row, self::COL_STATUS));
        $reasonName = $this->mapReasonName($this->cell($row, self::COL_REASON));
        $replacementDoc = $this->cell($row, self::COL_REPLACEMENT_DOC);
        $replacementName = $this->cell($row, self::COL_REPLACEMENT_NAME);

        [$replacementDocument, $replacementNameValue] = $this->resolveReplacementFields(
            $reasonName,
            $replacementDoc,
            $replacementName
        );

        $requestDate = $this->parseDate($this->cell($row, self::COL_REQUEST_DATE)) ?? now()->toDateString();
        $hiringDate = $this->parseDate($this->cell($row, self::COL_HIRING_DATE));

        $baseSalary = $this->parseMoney($this->cell($row, self::COL_BASE_SALARY));
        $isContratado = $status === PersonalRequisition::STATUS_CONTRATADO;

        $hiredDocument = $this->resolveHiredDocument($row, $legacyId);
        $hiredFullName = $this->resolveHiredFullName($row);

        $observation = $this->cell($row, self::COL_OBSERVATION);
        $humanResourcesObservation = $observation !== ''
            ? "Import legacy #{$legacyId}. {$observation}"
            : "Importado desde archivo legacy (ID {$legacyId}).";

        $requisition = [
            'code' => $code,
            'requested_by' => $userId,
            'managed_by' => $userId,
            'request_date' => $requestDate,
            'leader_name' => $this->titleCase($this->cell($row, self::COL_LEADER)) ?: 'Sin lider',
            'requesting_area_key' => $this->mapAreaKey($this->cell($row, self::COL_REQUESTING_AREA)),
            'position_id' => $this->catalogId(RequisitionPosition::class, $this->normalizeCatalogLabel($this->cell($row, self::COL_POSITION))),
            'sex' => $this->mapSex($this->cell($row, self::COL_SEX)),
            'quantity' => max(1, (int) ($this->cell($row, self::COL_QUANTITY) ?: 1)),
            'replacement_document' => $replacementDocument,
            'replacement_name' => $replacementNameValue,
            'operating_area_key' => $this->mapAreaKey($this->cell($row, self::COL_OPERATING_AREA)),
            'request_reason_id' => $this->catalogId(RequisitionRequestReason::class, $reasonName),
            'client_id' => $this->catalogId(RequisitionClient::class, $this->normalizeCatalogLabel($this->cell($row, self::COL_CLIENT))),
            'city_id' => $this->catalogId(RequisitionCity::class, $this->normalizeCatalogLabel($this->cell($row, self::COL_CITY))),
            'client_type_id' => $this->catalogId(RequisitionClientType::class, $this->mapClientTypeName($this->cell($row, self::COL_CLIENT_TYPE))),
            'programming_type_id' => $this->catalogId(RequisitionProgrammingType::class, $this->normalizeCatalogLabel($this->cell($row, self::COL_PROGRAMMING)) ?: 'N/A'),
            'required_profile' => $this->cell($row, self::COL_PROFILE) ?: 'Perfil importado',
            'uniform_id' => $this->catalogId(RequisitionUniform::class, $this->mapUniformName($this->cell($row, self::COL_UNIFORM))),
            'service_structure' => 'Importado desde archivo legacy (ID '.$legacyId.').',
            'cost_center' => 'IMPORT-LEGACY',
            'requester_observation' => null,
            'human_resources_observation' => $humanResourcesObservation,
            'recruiter_id' => null,
            'recruiter_name' => $this->cell($row, self::COL_RECRUITER) ?: null,
            'contract_type_id' => $isContratado
                ? $this->catalogId(RequisitionContractType::class, $this->mapContractTypeName($this->cell($row, self::COL_CONTRACT_TYPE)))
                : null,
            'contract_duration' => $isContratado ? ($this->cell($row, self::COL_CONTRACT_DURATION) ?: 'N/A') : null,
            'base_salary' => $isContratado ? ($baseSalary ?? 0) : null,
            'transport_allowance' => $isContratado ? ($this->parseMoney($this->cell($row, self::COL_TRANSPORT)) ?? 0) : null,
            'mobility_allowance' => $isContratado ? $this->parseMoney($this->cell($row, self::COL_MOBILITY)) : null,
            'statutory_bonus' => $isContratado ? ($this->parseMoney($this->cell($row, self::COL_STATUTORY_BONUS)) ?? 0) : null,
            'non_statutory_bonus' => $isContratado ? $this->parseMoney($this->cell($row, self::COL_NON_STATUTORY_BONUS)) : null,
            'other_allowances' => $this->cell($row, self::COL_OTHER_ALLOWANCES) ?: null,
            'leasing_contract' => $this->cell($row, self::COL_LEASING) ?: null,
            'hiring_date' => $isContratado ? ($hiringDate ?? $requestDate) : null,
            'hired_document' => $isContratado ? $hiredDocument : null,
            'hired_full_name' => $isContratado ? $hiredFullName : null,
            'status' => $status,
            'status_changed_at' => $requestDate,
            'closed_at' => in_array($status, [PersonalRequisition::STATUS_CONTRATADO, PersonalRequisition::STATUS_CANCELADA], true)
                ? ($hiringDate ?? $requestDate)
                : null,
        ];

        $ficha = $isContratado ? [
            'hired_document' => $hiredDocument,
            'hired_full_name' => $hiredFullName,
            'created_by' => $userId,
        ] : null;

        return ['requisition' => $requisition, 'ficha' => $ficha];
    }

    private function legacyCode(int $legacyId): string
    {
        return 'REQ-IMP-'.str_pad((string) $legacyId, 4, '0', STR_PAD_LEFT);
    }

    private function defaultUserId(): int
    {
        if ($this->defaultUserId !== null) {
            return $this->defaultUserId;
        }

        $user = User::query()->where('is_active', true)->orderBy('id')->first();

        if ($user === null) {
            throw new \RuntimeException('No hay usuarios activos para asignar como solicitante.');
        }

        $this->defaultUserId = $user->id;

        return $this->defaultUserId;
    }

    /**
     * @param  array<int, string>  $row
     */
    private function cell(array $row, int $index): string
    {
        return trim($row[$index] ?? '');
    }

    private function normalizeEncoding(string $value): string
    {
        if (! mb_check_encoding($value, 'UTF-8')) {
            $converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $value);

            return $converted !== false ? $converted : $value;
        }

        return $value;
    }

    private function normalizeCatalogLabel(string $value): string
    {
        $value = $this->normalizeEncoding(trim($value));

        return $value === '' ? 'N/A' : $this->titleCase($value);
    }

    private function titleCase(string $value): string
    {
        return Str::title(mb_strtolower(trim($value)));
    }

    private function mapStatus(string $raw): string
    {
        $key = Str::upper(Str::ascii(trim($raw)));

        return match (true) {
            str_starts_with($key, 'CONTRATAD') => PersonalRequisition::STATUS_CONTRATADO,
            str_starts_with($key, 'CANCELAD') => PersonalRequisition::STATUS_CANCELADA,
            str_contains($key, 'GESTION') => PersonalRequisition::STATUS_EN_GESTION,
            str_contains($key, 'APROBAD') => PersonalRequisition::STATUS_APROBADA,
            default => PersonalRequisition::STATUS_SOLICITADA,
        };
    }

    private function mapSex(string $raw): string
    {
        $key = Str::upper(trim($raw));

        return match ($key) {
            'MASCULINO', 'M' => 'masculino',
            'FEMENINO', 'F' => 'femenino',
            default => 'indiferente',
        };
    }

    private function mapAreaKey(string $raw): string
    {
        $key = Str::upper(Str::ascii(trim($raw)));

        return match (true) {
            str_contains($key, 'OPERAC') || $key === 'OPERATIVO' => 'operaciones',
            str_contains($key, 'PLANEAC') || str_contains($key, 'PROGRAM') => 'programacion',
            str_contains($key, 'GESTION') => 'gestion_humana',
            str_contains($key, 'COMERCIAL') => 'comercial',
            str_contains($key, 'ADMIN') || str_contains($key, 'FINANC') => 'admin_financiero',
            str_contains($key, 'COMPRAS') => 'compras',
            str_contains($key, 'CALIDAD') => 'calidad',
            str_contains($key, 'JURID') => 'juridico',
            default => 'operaciones',
        };
    }

    private function mapReasonName(string $raw): string
    {
        $key = Str::upper(Str::ascii(trim($raw)));

        return match (true) {
            str_contains($key, 'CARGO NUEVO') => 'Cargo nuevo',
            str_contains($key, 'TRASLADO') || str_contains($key, 'MOVIMIENTO') => 'Movimiento interno',
            str_contains($key, 'REEMPLAZ') || str_contains($key, 'RENUNCIA') => 'Reemplazo',
            str_contains($key, 'CLIENTE') || str_contains($key, 'SERVICIO') => 'Servicio nuevo',
            default => 'Cargo nuevo',
        };
    }

    private function mapClientTypeName(string $raw): string
    {
        $key = Str::upper(trim($raw));

        return match (true) {
            str_contains($key, 'EXTERN') => 'Externo',
            str_contains($key, 'INTERN') => 'Interno',
            default => 'Externo',
        };
    }

    private function mapContractTypeName(string $raw): string
    {
        $key = Str::upper(Str::ascii(trim($raw)));

        return match (true) {
            str_contains($key, 'INDEFIN') => 'Indefinido',
            str_contains($key, 'FIJO') => 'Fijo',
            str_contains($key, 'OBRA') || str_contains($key, 'TERMIN') || str_contains($key, 'LABOR') => 'Obra o Labor',
            str_contains($key, 'APREND') => 'Aprendizaje',
            default => 'Obra o Labor',
        };
    }

    private function mapUniformName(string $raw): string
    {
        $key = Str::upper(trim($raw));

        return match (true) {
            str_contains($key, 'OVEROL') => 'Overol + Botas',
            str_contains($key, 'FORMAL') || str_contains($key, 'BONO') => 'Traje Formal',
            str_contains($key, 'SIN') => 'Sin Dotación',
            str_contains($key, 'ESCOLTA') => 'Camisa + Pantalón + Botas',
            str_contains($key, 'ADMIN') => 'Traje Formal',
            default => 'Camisa + Pantalón + Botas',
        };
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function resolveReplacementFields(string $reasonName, string $doc, string $name): array
    {
        if (! in_array($reasonName, ['Reemplazo', 'Movimiento interno'], true)) {
            return [null, null];
        }

        if (! $this->looksLikeDocument($doc)) {
            return [null, null];
        }

        return [$doc, $name !== '' ? $name : null];
    }

    /**
     * @param  array<int, string>  $row
     */
    private function resolveHiredDocument(array $row, int $legacyId): string
    {
        $hired = $this->cell($row, self::COL_HIRED_DOC);

        if ($this->looksLikeDocument($hired)) {
            return $hired;
        }

        return 'IMP-'.$legacyId;
    }

    /**
     * @param  array<int, string>  $row
     */
    private function resolveHiredFullName(array $row): string
    {
        $name = $this->cell($row, self::COL_HIRED_NAME);

        return $name !== '' ? $this->titleCase($name) : 'Pendiente importacion';
    }

    private function looksLikeDocument(string $value): bool
    {
        $value = trim($value);

        if ($value === '' || in_array(Str::upper($value), ['N/A', 'NA', 'SERVICIO', 'CLIENTE', 'CARGO NUEVO'], true)) {
            return false;
        }

        return (bool) preg_match('/^\d{5,12}$/', preg_replace('/\D/', '', $value));
    }

    private function parseMoney(?string $raw): ?float
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $normalized = preg_replace('/[^\d,.-]/', '', $raw) ?? '';

        if ($normalized === '' || $normalized === '0') {
            return null;
        }

        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function parseDate(string $raw): ?string
    {
        $raw = trim($raw);

        if ($raw === '' || $raw === '0') {
            return null;
        }

        foreach (['d/m/Y', 'j/n/Y', 'd-m-Y', 'd/m/y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $raw)->toDateString();
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function catalogId(string $modelClass, string $name): int
    {
        $cacheKey = $modelClass.'|'.$name;

        if (isset($this->catalogCache[$cacheKey])) {
            return $this->catalogCache[$cacheKey];
        }

        $record = $modelClass::query()->firstOrCreate(
            ['name' => $name],
            ['is_active' => true, 'sort_order' => ($modelClass::query()->max('sort_order') ?? 0) + 1]
        );

        $this->catalogCache[$cacheKey] = $record->id;

        return $record->id;
    }
}
