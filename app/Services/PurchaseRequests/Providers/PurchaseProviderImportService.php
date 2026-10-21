<?php

namespace App\Services\PurchaseRequests\Providers;

use App\Models\PurchaseProvider;
use App\Models\RequisitionCity;
use App\Support\SpreadsheetCellReader;
use App\Support\UppercaseFormText;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Import masivo de proveedores desde la plantilla VINE01.
 *
 * Reglas:
 * - Solo se cargan filas con PROVEEDOR = SI (normaliza SI/SÍ/1/TRUE/X).
 * - Upsert por `identificacion` (la actualiza si ya existe).
 * - Columnas: IDENTIFICA, NOMBRE, TELEFONO, CORREO_ELE, CONTACTO, CIUDAD,
 *   FORMA_PAGO, PROVEEDOR, ESTADO, DIAS_PAGO (opcional). CIUDAD debe existir
 *   en el catálogo de Requisiciones si viene llena.
 */
final class PurchaseProviderImportService
{
    private const MAX_ERRORS_REPORTED = 50;

    /**
     * @return array{
     *     created: int,
     *     updated: int,
     *     skipped: int,
     *     skipped_empty: int,
     *     processed: int,
     *     errors: list<string>,
     *     failures: list<array<string, mixed>>,
     *     errors_count: int,
     *     dry_run: bool
     * }
     */
    public function import(string $path, bool $dryRun = false, ?int $limit = null, ?int $userId = null): array
    {
        if (! is_readable($path)) {
            throw new \InvalidArgumentException('No se puede leer el archivo: '.$path);
        }

        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        $maxRows = max(1, (int) config('purchase-requests.providers_import_max_rows', 20000));
        $rowLimit = $limit !== null && $limit > 0 ? min($limit, $maxRows) : $maxRows;

        $spreadsheet = $this->loadSpreadsheet($path);
        $sheet = $spreadsheet->getActiveSheet();
        $headers = $this->readHeaders($sheet);
        $this->assertRequiredHeaders($headers);

        $maxRow = (int) $sheet->getHighestDataRow();

        if (max(0, $maxRow - 1) > $maxRows) {
            $this->releaseSpreadsheet($spreadsheet);

            throw new \RuntimeException(
                'El archivo tiene demasiadas filas. El máximo permitido es '.$maxRows.'.'
            );
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $skippedEmpty = 0;
        $processed = 0;
        $errors = [];
        $failures = [];
        $errorsCount = 0;
        $rows = [];
        $ciudades = $this->ciudadesCatalogo();

        for ($row = 2; $row <= $maxRow; $row++) {
            if ($processed >= $rowLimit) {
                break;
            }

            $data = $this->readRow($sheet, $row, $headers);

            if ($this->rowIsEmpty($data)) {
                $skippedEmpty++;

                continue;
            }

            $processed++;

            // Solo proveedores: CLIENTE=SI sin PROVEEDOR=SI no se carga.
            if (! $this->isYes($data['PROVEEDOR'] ?? null)) {
                $skipped++;

                continue;
            }

            $identificacion = trim((string) ($data['IDENTIFICA'] ?? ''));
            $nombre = trim((string) ($data['NOMBRE'] ?? ''));

            if ($identificacion === '') {
                $this->addFailure($errors, $failures, $row, $identificacion, 'IDENTIFICA', 'la identificación es obligatoria.', $data);
                $errorsCount++;

                continue;
            }

            if ($nombre === '') {
                $this->addFailure($errors, $failures, $row, $identificacion, 'NOMBRE', 'el nombre es obligatorio.', $data);
                $errorsCount++;

                continue;
            }

            $diasPago = $this->parseDiasPago($data['DIAS_PAGO'] ?? null);

            if ($diasPago === false) {
                $this->addFailure($errors, $failures, $row, $identificacion, 'DIAS_PAGO', 'los días de pago deben ser un entero entre 0 y 3650.', $data);
                $errorsCount++;

                continue;
            }

            $attrs = [
                'identificacion' => mb_substr($identificacion, 0, 50),
                'nombre' => mb_substr(UppercaseFormText::uppercaseString($nombre), 0, 255),
                'telefono' => $this->normalizeTelefono($data['TELEFONO'] ?? null),
                'correo' => $this->normalizeCorreo($data['CORREO_ELE'] ?? null),
                'estado' => $this->normalizeEstado($data['ESTADO'] ?? null),
                'dias_pago' => $diasPago,
            ];

            // CONTACTO / CIUDAD / FORMA_PAGO: solo se aplican si el archivo trae la columna.
            if (isset($headers['CONTACTO'])) {
                $attrs['contacto'] = $this->normalizeUpperText($data['CONTACTO'] ?? null, 255);
            }

            if (isset($headers['CIUDAD'])) {
                $ciudad = $this->resolveCiudad($data['CIUDAD'] ?? null, $ciudades);

                if ($ciudad === false) {
                    $this->addFailure($errors, $failures, $row, $identificacion, 'CIUDAD', 'la ciudad no existe en el catálogo de Requisiciones.', $data);
                    $errorsCount++;

                    continue;
                }

                $attrs['ciudad'] = $ciudad;
            }

            if (isset($headers['FORMA_PAGO'])) {
                $attrs['forma_pago'] = $this->normalizeUpperText($data['FORMA_PAGO'] ?? null, 120);
            }

            $rows[] = $attrs;
        }

        $this->releaseSpreadsheet($spreadsheet);

        if ($rows === []) {
            throw new \RuntimeException(
                'El archivo no contiene filas de proveedor válidas (PROVEEDOR = SI).'
            );
        }

        if ($dryRun) {
            $existingKeys = PurchaseProvider::query()->pluck('identificacion')->all();
            $existingKeys = array_flip(array_map('strval', $existingKeys));

            foreach ($rows as $attrs) {
                if (isset($existingKeys[$attrs['identificacion']])) {
                    $updated++;
                } else {
                    $created++;
                }
            }

            return [
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'skipped_empty' => $skippedEmpty,
                'processed' => $processed,
                'errors' => array_slice($errors, 0, self::MAX_ERRORS_REPORTED),
                'failures' => $failures,
                'errors_count' => $errorsCount,
                'dry_run' => true,
            ];
        }

        $existingByKey = PurchaseProvider::query()->get()->keyBy('identificacion');

        DB::transaction(function () use ($rows, &$created, &$updated, &$existingByKey, $userId): void {
            foreach ($rows as $attrs) {
                /** @var PurchaseProvider|null $current */
                $current = $existingByKey->get($attrs['identificacion']);

                if ($current !== null) {
                    $current->fill($attrs)->save();
                    $updated++;

                    continue;
                }

                $new = PurchaseProvider::query()->create([
                    ...$attrs,
                    'created_by' => $userId,
                ]);
                $existingByKey->put($attrs['identificacion'], $new);
                $created++;
            }
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'skipped_empty' => $skippedEmpty,
            'processed' => $processed,
            'errors' => array_slice($errors, 0, self::MAX_ERRORS_REPORTED),
            'failures' => $failures,
            'errors_count' => $errorsCount,
            'dry_run' => false,
        ];
    }

    /**
     * @param  list<string>  $errors
     * @param  list<array<string, mixed>>  $failures
     * @param  array<string, mixed>  $data
     */
    private function addFailure(
        array &$errors,
        array &$failures,
        int $row,
        string $identifier,
        string $field,
        string $reason,
        array $data,
    ): void {
        $errors[] = "Fila {$row}: {$reason}";
        $failures[] = [
            'row' => $row,
            'identifier' => $identifier,
            'severity' => 'error',
            'field' => $field,
            'reason' => $reason,
            'raw' => $data,
        ];
    }

    private function loadSpreadsheet(string $path): Spreadsheet
    {
        $reader = IOFactory::createReaderForFile($path);

        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true);
        }

        if (method_exists($reader, 'setReadEmptyCells')) {
            $reader->setReadEmptyCells(false);
        }

        /** @var IReader $reader */
        return $reader->load($path);
    }

    private function releaseSpreadsheet(Spreadsheet $spreadsheet): void
    {
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    }

    /**
     * @return array<string, int> header (mayúsculas) => columna (1-based)
     */
    private function readHeaders(Worksheet $sheet): array
    {
        $headers = [];
        $maxCol = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        for ($col = 1; $col <= $maxCol; $col++) {
            $label = mb_strtoupper(trim(SpreadsheetCellReader::stringValue($sheet, $col, 1)), 'UTF-8');

            if ($label !== '') {
                $headers[$label] = $col;
            }
        }

        return $headers;
    }

    /**
     * @param  array<string, int>  $headers
     */
    private function assertRequiredHeaders(array $headers): void
    {
        if ($headers === []) {
            throw new \RuntimeException('El archivo no tiene encabezados válidos en la fila 1.');
        }

        /** @var list<string> $required */
        $required = config('purchase-requests.providers_import_required_columns', ['IDENTIFICA', 'NOMBRE', 'PROVEEDOR']);

        foreach ($required as $label) {
            if (! array_key_exists(mb_strtoupper($label, 'UTF-8'), $headers)) {
                throw new \RuntimeException(
                    "Falta la columna obligatoria \"{$label}\" en la fila 1."
                );
            }
        }
    }

    /**
     * @param  array<string, int>  $headers
     * @return array<string, mixed>
     */
    private function readRow(Worksheet $sheet, int $row, array $headers): array
    {
        $data = [];

        /** @var array<string, string> $columns */
        $columns = config('purchase-requests.providers_import_columns', []);

        foreach (array_keys($columns) as $header) {
            $col = $headers[$header] ?? null;
            $data[$header] = $col !== null
                ? SpreadsheetCellReader::stringValue($sheet, $col, $row)
                : '';
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function rowIsEmpty(array $data): bool
    {
        foreach ($data as $value) {
            if (trim((string) ($value ?? '')) !== '') {
                return false;
            }
        }

        return true;
    }

    private function isYes(mixed $value): bool
    {
        $raw = mb_strtoupper(trim((string) ($value ?? '')), 'UTF-8');
        $raw = str_replace(['Í', 'Á', 'É', 'Ó', 'Ú'], ['I', 'A', 'E', 'O', 'U'], $raw);

        return in_array($raw, ['SI', 'S', '1', 'X', 'TRUE', 'VERDADERO', 'YES'], true);
    }

    private function normalizeTelefono(mixed $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) ($value ?? ''));

        return $digits === '' || $digits === null ? null : mb_substr($digits, 0, 20);
    }

    private function normalizeCorreo(mixed $value): ?string
    {
        $correo = trim((string) ($value ?? ''));

        return $correo === '' ? null : mb_substr($correo, 0, 255);
    }

    private function normalizeEstado(mixed $value): string
    {
        $raw = mb_strtoupper(trim((string) ($value ?? '')), 'UTF-8');

        if (str_contains($raw, 'INACT') || in_array($raw, ['0', 'NO', 'N', 'FALSE', 'FALSO'], true)) {
            return PurchaseProvider::ESTADO_INACTIVO;
        }

        return PurchaseProvider::ESTADO_ACTIVO;
    }

    /**
     * Catálogo activo: clave en mayúsculas → nombre oficial (ej. CALI => Cali).
     *
     * @return array<string, string>
     */
    private function ciudadesCatalogo(): array
    {
        $map = [];

        foreach (RequisitionCity::query()->where('is_active', true)->orderBy('name')->get(['name']) as $city) {
            $map[mb_strtoupper((string) $city->name, 'UTF-8')] = (string) $city->name;
        }

        return $map;
    }

    /**
     * @param  array<string, string>  $ciudades
     * @return string|null|false null = vacío; false = no está en el catálogo
     */
    private function resolveCiudad(mixed $value, array $ciudades): string|null|false
    {
        $raw = trim((string) ($value ?? ''));

        if ($raw === '') {
            return null;
        }

        return $ciudades[mb_strtoupper($raw, 'UTF-8')] ?? false;
    }

    private function normalizeUpperText(mixed $value, int $max): ?string
    {
        $raw = trim((string) ($value ?? ''));

        if ($raw === '') {
            return null;
        }

        return mb_substr(UppercaseFormText::uppercaseString($raw), 0, $max);
    }

    /**
     * @return int|null|false null = vacío; false = inválido
     */
    private function parseDiasPago(mixed $value): int|null|false
    {
        $raw = trim((string) ($value ?? ''));

        if ($raw === '') {
            return null;
        }

        if (! is_numeric($raw) || (float) $raw < 0 || (float) $raw > 3650 || floor((float) $raw) != (float) $raw) {
            return false;
        }

        return (int) $raw;
    }
}
