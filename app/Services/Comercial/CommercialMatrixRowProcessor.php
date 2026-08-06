<?php

namespace App\Services\Comercial;

use App\Models\CommercialClient;
use App\Models\CommercialClientType;
use App\Models\CommercialSector;
use App\Models\CommercialService;
use App\Models\CommercialServiceType;
use App\Support\CommercialDocumentCatalog;
use App\Support\ImportFailureRow;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class CommercialMatrixRowProcessor
{
    /**
     * @param  array<string, mixed>  $raw
     * @return array{
     *     client_created: bool,
     *     client_updated: bool,
     *     service_created: bool,
     *     service_updated: bool,
     *     skipped: bool,
     *     empty_row: bool,
     *     error: string|null,
     *     nit: string|null,
     *     failure: array<string, mixed>|null
     * }
     */
    public function processRow(array $raw, int $rowNumber, ?int $userId = null): array
    {
        $result = [
            'client_created' => false,
            'client_updated' => false,
            'service_created' => false,
            'service_updated' => false,
            'skipped' => false,
            'empty_row' => false,
            'error' => null,
            'nit' => null,
            'failure' => null,
        ];

        if ($this->isEmptyRow($raw)) {
            $result['empty_row'] = true;
            $result['failure'] = ImportFailureRow::make(
                $rowNumber,
                null,
                'NIT',
                ImportFailureRow::SEVERITY_EMPTY,
                'Fila vacia (sin datos).',
                $raw,
            );

            return $result;
        }

        try {
            $nit = CommercialClient::normalizeNit((string) ($raw['nit'] ?? ''));
            $name = trim((string) ($raw['client_name'] ?? ''));

            if ($nit === '' || $name === '' || ! preg_match('/\d/', $nit)) {
                $result['skipped'] = true;
                $result['failure'] = ImportFailureRow::make(
                    $rowNumber,
                    $nit !== '' ? $nit : null,
                    'NIT',
                    ImportFailureRow::SEVERITY_SKIPPED,
                    $nit === '' ? 'NIT obligatorio vacio.' : ($name === '' ? 'Nombre de cliente obligatorio vacio.' : 'NIT invalido (debe contener digitos).'),
                    $raw,
                );

                return $result;
            }

            $portfolio = $this->resolvePortfolio($raw['portfolio'] ?? null);
            if ($portfolio === null) {
                $result['skipped'] = true;
                $result['error'] = "Fila {$rowNumber} (NIT {$nit}): portafolio invalido o vacio.";
                $result['failure'] = ImportFailureRow::make(
                    $rowNumber,
                    $nit,
                    'NIT',
                    ImportFailureRow::SEVERITY_ERROR,
                    'Portafolio invalido o vacio.',
                    $raw,
                );

                return $result;
            }

            $client = CommercialClient::query()->firstOrNew(['nit' => $nit]);
            $wasNewClient = ! $client->exists;

            $client->fill([
                'name' => $name,
                'phone' => $this->nullableString($raw['phone'] ?? null),
                'address' => $this->nullableString($raw['address'] ?? null),
                'city' => $this->nullableString($raw['city'] ?? null),
                'legal_rep_name' => $this->nullableString($raw['legal_rep_name'] ?? null),
                'legal_rep_doc' => $this->nullableString($raw['legal_rep_doc'] ?? null),
            ]);

            $documentationExpiresOn = $this->parseDate($raw['documentation_expires_on'] ?? null);
            if ($documentationExpiresOn !== null) {
                $client->documentation_expires_on = $documentationExpiresOn;
            }

            $alertDays = $this->parseAlertDays($raw['alert_days_before'] ?? null);
            if ($alertDays !== null) {
                $client->alert_days_before = $alertDays;
            }

            if ($userId !== null) {
                if ($wasNewClient) {
                    $client->created_by = $userId;
                }
                $client->updated_by = $userId;
            }

            $client->save();

            $result['nit'] = $nit;
            $result['client_created'] = $wasNewClient;
            $result['client_updated'] = ! $wasNewClient;

            $contractNumber = $this->nullableString($raw['contract_number'] ?? null);
            $serviceQuery = CommercialService::query()
                ->where('commercial_client_id', $client->id)
                ->where('portfolio', $portfolio);

            if ($contractNumber) {
                $serviceQuery->where('contract_number', $contractNumber);
            } else {
                $serviceQuery->whereNull('contract_number')
                    ->where('service_description', $this->nullableString($raw['service_description'] ?? null));
            }

            $service = $serviceQuery->first() ?? new CommercialService([
                'commercial_client_id' => $client->id,
                'portfolio' => $portfolio,
            ]);

            $wasNewService = ! $service->exists;

            $statusByKey = [];
            foreach (CommercialDocumentCatalog::documentKeys() as $documentKey) {
                $mapped = $this->mapDocStatus($raw[$documentKey] ?? null);
                if ($mapped !== null) {
                    $statusByKey[$documentKey] = $mapped;
                }
            }

            $expiryDates = collect();
            foreach (CommercialDocumentCatalog::documentKeys() as $documentKey) {
                $expiresKey = $documentKey.'_expires_on';
                $expiresOn = $this->parseDate($raw[$expiresKey] ?? null);
                if ($expiresOn !== null) {
                    $expiryDates->push(Carbon::parse($expiresOn));
                }
            }

            if ($documentationExpiresOn !== null) {
                $expiryDates->push(Carbon::parse($documentationExpiresOn));
            }

            $service->fill([
                'portfolio' => $portfolio,
                'contract_number' => $contractNumber,
                'advisor_name' => $this->nullableString($raw['advisor_name'] ?? null),
                'commercial_sector_id' => $this->resolveCatalogId(CommercialSector::class, $raw['sector'] ?? null),
                'commercial_client_type_id' => $this->resolveCatalogId(CommercialClientType::class, $raw['client_type'] ?? null),
                'commercial_service_type_id' => $this->resolveCatalogId(CommercialServiceType::class, $raw['service_type'] ?? null),
                'service_description' => $this->nullableString($raw['service_description'] ?? null),
                'contact_name' => $this->nullableString($raw['contact_name'] ?? null),
                'contact_role' => $this->nullableString($raw['contact_role'] ?? null),
                'contact_phone' => $this->nullableString($raw['contact_phone'] ?? null),
                'contact_email' => $this->nullableString($raw['contact_email'] ?? null),
                'contract_start' => $this->parseDate($raw['contract_start'] ?? null),
                'contract_end' => $this->parseDate($raw['contract_end'] ?? null),
                'duration_months' => $this->parseDuration($raw['duration_months'] ?? null),
                'is_active' => $this->parseBoolean($raw['is_active'] ?? null, $service->exists ? (bool) $service->is_active : true),
            ]);

            if ($userId !== null) {
                if ($wasNewService) {
                    $service->created_by = $userId;
                }
                $service->updated_by = $userId;
            }

            $service->commercial_client_id = $client->id;
            $service->save();

            $checklistService = app(CommercialClientChecklistService::class);
            if ($statusByKey !== []) {
                $checklistService->applyImportedDocumentStatuses($client, $statusByKey, null);
            }
            if ($expiryDates->isNotEmpty()) {
                $checklistService->mergeClientDocumentationExpiry($client, $expiryDates);
            }

            $result['service_created'] = $wasNewService;
            $result['service_updated'] = ! $wasNewService;
        } catch (Throwable $e) {
            $nitLabel = CommercialClient::normalizeNit((string) ($raw['nit'] ?? ''));
            $result['skipped'] = true;
            $result['error'] = "Fila {$rowNumber}".($nitLabel !== '' ? " (NIT {$nitLabel})" : '').': '.$e->getMessage();
            $result['failure'] = ImportFailureRow::make(
                $rowNumber,
                $nitLabel !== '' ? $nitLabel : null,
                'NIT',
                ImportFailureRow::SEVERITY_ERROR,
                $e->getMessage(),
                $raw,
            );
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private function isEmptyRow(array $raw): bool
    {
        foreach ($raw as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function resolvePortfolio(mixed $value): ?string
    {
        $string = $this->nullableString($value);
        if ($string === null) {
            return null;
        }

        $normalized = mb_strtolower(trim($string));
        $normalized = strtr($normalized, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
        ]);

        $aliases = config('commercial_matrix.portfolio_aliases', []);
        if (isset($aliases[$normalized])) {
            return $aliases[$normalized];
        }

        $portfolios = CommercialService::portfolios();
        if (array_key_exists($normalized, $portfolios)) {
            return $normalized;
        }

        foreach ($portfolios as $key => $label) {
            if (mb_strtolower($label) === $normalized) {
                return $key;
            }
        }

        return null;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);
        if ($string === '' || str_starts_with($string, '=')) {
            return null;
        }

        return mb_substr($string, 0, 2000);
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                $date = ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } else {
                $string = trim((string) $value);
                if ($string === '') {
                    return null;
                }

                $date = date('Y-m-d', strtotime($string));
            }

            return $this->isInvalidImportedDate($date) ? null : $date;
        } catch (Throwable) {
            return null;
        }
    }

    private function isInvalidImportedDate(string $date): bool
    {
        $year = (int) substr($date, 0, 4);

        return $year > 0 && $year < 1980;
    }

    private function parseDuration(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $parsed = null;

        if (is_numeric($value)) {
            $parsed = (int) round((float) $value);
        } elseif (preg_match('/(\d+)/', (string) $value, $matches)) {
            $parsed = (int) $matches[1];
        }

        if ($parsed === null || $parsed < 0 || $parsed > 600) {
            return null;
        }

        return $parsed;
    }

    private function parseAlertDays(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $parsed = (int) round((float) $value);

        return ($parsed >= 0 && $parsed <= 365) ? $parsed : null;
    }

    private function parseBoolean(mixed $value, bool $default): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value !== 0;
        }

        $raw = mb_strtolower(trim((string) $value));

        return match (true) {
            in_array($raw, ['1', 'si', 'sí', 'yes', 'true', 'activo', 's'], true) => true,
            in_array($raw, ['0', 'no', 'false', 'inactivo', 'n'], true) => false,
            default => $default,
        };
    }

    private function mapDocStatus(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = mb_strtolower(trim((string) $value));

        return match (true) {
            in_array($raw, ['ok', 'si', 'sí', 'p'], true) => CommercialDocumentCatalog::DOC_OK,
            in_array($raw, ['x', 'no'], true) => CommercialDocumentCatalog::DOC_X,
            in_array($raw, ['o', 'n/a', 'na'], true) => CommercialDocumentCatalog::DOC_NA,
            in_array($raw, ['i', 'inc', 'incompleto'], true) => CommercialDocumentCatalog::DOC_INCOMPLETE,
            in_array($raw, ['pendiente', 'pending'], true) => CommercialDocumentCatalog::DOC_PENDING,
            default => CommercialDocumentCatalog::DOC_PENDING,
        };
    }

    /**
     * @param  class-string<CommercialSector|CommercialClientType|CommercialServiceType>  $modelClass
     */
    private function resolveCatalogId(string $modelClass, mixed $value): ?int
    {
        $name = $this->nullableString($value);
        if ($name === null) {
            return null;
        }

        $name = mb_strtoupper($name);

        $model = $modelClass::query()->firstOrCreate(
            ['name' => $name],
            ['is_active' => true, 'sort_order' => 999]
        );

        return $model->id;
    }
}
