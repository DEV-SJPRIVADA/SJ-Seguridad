<?php

namespace App\Services\GestionHumana;

use App\Models\AcreditacionReporteDiarioCarga;
use App\Models\AcreditacionReporteDiarioFila;
use App\Support\ImportFailureRow;
use App\Support\SpreadsheetCellReader;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AcreditacionReporteDiarioImportService
{
    public const ORIGEN_PROCESO = AcreditacionReporteDiarioFila::ORIGEN_PROCESO;

    public const ORIGEN_ACREDITADO = AcreditacionReporteDiarioFila::ORIGEN_ACREDITADO;

    /**
     * @param  array{
     *     fecha_reporte: string,
     *     file_proceso?: UploadedFile|null,
     *     file_acreditado?: UploadedFile|null,
     *     confirm_replace?: bool
     * }  $input
     * @return array{
     *     carga: AcreditacionReporteDiarioCarga,
     *     replaced: bool,
     *     origins: list<string>,
     *     by_origen: array<string, array{ok: int, fail: int, empty_rows: int, file_name: string}>,
     *     failures: list<array<string, mixed>>,
     *     errors: list<string>,
     *     skipped: int,
     *     empty_rows: int,
     *     imported: int
     * }
     */
    public function import(array $input, ?int $userId = null): array
    {
        $fecha = Carbon::parse((string) $input['fecha_reporte'], config('app.timezone'))->toDateString();
        $today = Carbon::now(config('app.timezone'))->toDateString();

        if ($fecha > $today) {
            throw ValidationException::withMessages([
                'fecha_reporte' => 'La fecha de reporte no puede ser futura.',
            ]);
        }

        /** @var array<string, UploadedFile> $filesByOrigen */
        $filesByOrigen = [];

        if (($input['file_proceso'] ?? null) instanceof UploadedFile) {
            $filesByOrigen[self::ORIGEN_PROCESO] = $input['file_proceso'];
        }

        if (($input['file_acreditado'] ?? null) instanceof UploadedFile) {
            $filesByOrigen[self::ORIGEN_ACREDITADO] = $input['file_acreditado'];
        }

        if ($filesByOrigen === []) {
            throw ValidationException::withMessages([
                'file_proceso' => 'Debe subir al menos un archivo (En proceso o Acreditado APO).',
            ]);
        }

        $carga = AcreditacionReporteDiarioCarga::query()
            ->whereDate('fecha_reporte', $fecha)
            ->first();

        $originsNeedingReplace = [];
        if ($carga !== null) {
            foreach (array_keys($filesByOrigen) as $origen) {
                if ($carga->origenHasPriorData($origen)) {
                    $originsNeedingReplace[] = $origen;
                }
            }
        }

        $confirmReplace = (bool) ($input['confirm_replace'] ?? false);
        $replaced = $originsNeedingReplace !== [];

        // Fallar antes de parsear Excel: evita espera larga y deja claro el reemplazo.
        if ($replaced && ! $confirmReplace) {
            $labels = array_map(fn (string $o): string => $this->origenLabel($o), $originsNeedingReplace);

            throw ValidationException::withMessages([
                'confirm_replace' => 'Ya existe carga para esta fecha en: '.implode(', ', $labels)
                    .'. Marque «Confirmar reemplazo» y vuelva a seleccionar el/los archivo(s) (el navegador no conserva archivos tras un error).',
            ]);
        }

        /** @var array<string, array{rows: list<array<string, mixed>>, ok: int, fail: int, empty_rows: int, failures: list<array<string, mixed>>, file_name: string}> $parsed */
        $parsed = [];

        foreach ($filesByOrigen as $origen => $file) {
            $path = $file->getRealPath();
            if ($path === false || $path === null || ! is_readable($path)) {
                throw ValidationException::withMessages([
                    $this->fileFieldForOrigen($origen) => 'No se puede leer el archivo de '.$this->origenLabel($origen).'.',
                ]);
            }

            try {
                $parsed[$origen] = $this->parseFile($path, $origen, (string) $file->getClientOriginalName());
            } catch (\RuntimeException $e) {
                throw ValidationException::withMessages([
                    $this->fileFieldForOrigen($origen) => $e->getMessage(),
                ]);
            }
        }

        $allFailures = [];
        $allErrors = [];
        $totalSkipped = 0;
        $totalEmpty = 0;
        $totalImported = 0;
        $byOrigen = [];

        DB::transaction(function () use (
            $fecha,
            $parsed,
            $userId,
            &$allFailures,
            &$allErrors,
            &$totalSkipped,
            &$totalEmpty,
            &$totalImported,
            &$byOrigen,
            &$carga,
        ): void {
            if ($carga === null) {
                $carga = AcreditacionReporteDiarioCarga::query()->create([
                    'fecha_reporte' => $fecha,
                ]);
            }

            $now = now();

            foreach ($parsed as $origen => $result) {
                AcreditacionReporteDiarioFila::query()
                    ->where('carga_id', $carga->id)
                    ->where('origen', $origen)
                    ->delete();

                $rows = $result['rows'];
                foreach (array_chunk($rows, 250) as $chunk) {
                    $payload = [];
                    foreach ($chunk as $row) {
                        $payload[] = [
                            ...$row,
                            'carga_id' => $carga->id,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                    if ($payload !== []) {
                        AcreditacionReporteDiarioFila::query()->insert($payload);
                    }
                }

                $meta = [
                    $this->fileNameColumn($origen) => $result['file_name'],
                    $this->rowsOkColumn($origen) => $result['ok'],
                    $this->rowsFailColumn($origen) => $result['fail'],
                    $this->loadedAtColumn($origen) => $now,
                    $this->loadedByColumn($origen) => $userId,
                ];
                $carga->forceFill($meta)->save();

                $byOrigen[$origen] = [
                    'ok' => $result['ok'],
                    'fail' => $result['fail'],
                    'empty_rows' => $result['empty_rows'],
                    'file_name' => $result['file_name'],
                ];

                $allFailures = array_merge($allFailures, $result['failures']);
                foreach ($result['failures'] as $failure) {
                    $allErrors[] = ImportFailureRow::message($failure);
                }
                $totalSkipped += $result['fail'];
                $totalEmpty += $result['empty_rows'];
                $totalImported += $result['ok'];
            }

            $carga->refresh();
        });

        return [
            'carga' => $carga,
            'replaced' => $replaced,
            'origins' => array_keys($parsed),
            'by_origen' => $byOrigen,
            'failures' => $allFailures,
            'errors' => $allErrors,
            'skipped' => $totalSkipped,
            'empty_rows' => $totalEmpty,
            'imported' => $totalImported,
        ];
    }

    /**
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     ok: int,
     *     fail: int,
     *     empty_rows: int,
     *     failures: list<array<string, mixed>>,
     *     file_name: string
     * }
     */
    private function parseFile(string $path, string $origen, string $fileName): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $headerMap = $this->mapHeaders($sheet, $origen);

        $rows = [];
        $failures = [];
        $ok = 0;
        $fail = 0;
        $emptyRows = 0;
        $maxRow = (int) $sheet->getHighestRow();

        for ($row = 3; $row <= $maxRow; $row++) {
            $raw = $this->readMappedRow($sheet, $row, $headerMap);

            if ($this->rowIsEmpty($raw)) {
                $emptyRows++;

                continue;
            }

            $documentNumber = trim((string) ($raw['document_number'] ?? ''));

            try {
                if ($documentNumber === '') {
                    throw new \InvalidArgumentException('El IdNum (cédula) es obligatorio.');
                }

                $apellido1 = $this->nullableString($raw['apellido1'] ?? null, 100);
                $apellido2 = $this->nullableString($raw['apellido2'] ?? null, 100);
                $nombre1 = $this->nullableString($raw['nombre1'] ?? null, 100);
                $nombre2 = $this->nullableString($raw['nombre2'] ?? null, 100);
                $cargo = $this->nullableString($raw['cargo'] ?? null, 255);
                $fullName = $this->composeFullName($apellido1, $apellido2, $nombre1, $nombre2);

                $estadoApo = null;
                $vigenciaAcr = null;

                if ($origen === self::ORIGEN_PROCESO) {
                    $estadoApo = $this->nullableString($raw['estado_apo'] ?? null, 100);
                } else {
                    $vigenciaRaw = $raw['vigencia_acr'] ?? null;
                    $vigenciaTrimmed = trim((string) ($vigenciaRaw ?? ''));
                    if ($vigenciaTrimmed !== '') {
                        $parsedVigencia = $this->parseDate($vigenciaRaw);
                        if ($parsedVigencia === null) {
                            throw new \InvalidArgumentException(
                                'Vigen.Acr no es una fecha válida.',
                            );
                        }
                        $vigenciaAcr = $parsedVigencia;
                        // Con vigencia APO el estado mostrado/persistido es ACREDITADO.
                        $estadoApo = 'ACREDITADO';
                    }
                }

                $rows[] = [
                    'origen' => $origen,
                    'apellido1' => $apellido1,
                    'apellido2' => $apellido2,
                    'nombre1' => $nombre1,
                    'nombre2' => $nombre2,
                    'full_name' => $fullName !== '' ? $fullName : $documentNumber,
                    'document_number' => mb_substr($documentNumber, 0, 50),
                    'cargo' => $cargo,
                    'estado_apo' => $estadoApo,
                    'vigencia_acr' => $vigenciaAcr,
                    'source_row' => $row,
                ];
                $ok++;
            } catch (\Throwable $e) {
                $failure = ImportFailureRow::make(
                    $row,
                    $documentNumber !== '' ? $documentNumber : null,
                    'IdNum',
                    ImportFailureRow::SEVERITY_ERROR,
                    $e->getMessage(),
                    $raw,
                );
                $failures[] = $failure;
                $fail++;
            }
        }

        return [
            'rows' => $rows,
            'ok' => $ok,
            'fail' => $fail,
            'empty_rows' => $emptyRows,
            'failures' => $failures,
            'file_name' => mb_substr($fileName, 0, 255),
        ];
    }

    /**
     * @return array<string, int> field => column index
     */
    private function mapHeaders(Worksheet $sheet, string $origen): array
    {
        /** @var array<string, list<string>> $expected */
        $expected = config('acreditaciones.reporte_diario.headers.'.$origen, []);

        if ($expected === []) {
            throw new \RuntimeException('Configuración de headers no encontrada para '.$this->origenLabel($origen).'.');
        }

        $rawHeaders = [];
        $maxCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        // Limitar exploración: archivos APO reales pueden reportar columnas lejanas vacías.
        $maxCol = min($maxCol, 40);

        for ($col = 1; $col <= $maxCol; $col++) {
            $key = SpreadsheetCellReader::stringValue($sheet, $col, 2);
            if ($key !== '') {
                $rawHeaders[$col] = $key;
            }
        }

        if ($rawHeaders === []) {
            throw new \RuntimeException(
                'El archivo de '.$this->origenLabel($origen).' no tiene encabezados válidos en la fila 2 (fila 1 = título APO, fila 2 = nombres de columnas).',
            );
        }

        $normalizedToCol = [];
        foreach ($rawHeaders as $col => $label) {
            $normalizedToCol[$this->normalizeHeader($label)] = $col;
        }

        $mapped = [];
        $missing = [];

        foreach ($expected as $field => $aliases) {
            $foundCol = null;
            foreach ($aliases as $alias) {
                $norm = $this->normalizeHeader($alias);
                if (isset($normalizedToCol[$norm])) {
                    $foundCol = $normalizedToCol[$norm];
                    break;
                }
            }

            if ($foundCol === null) {
                $missing[] = $aliases[0] ?? $field;
            } else {
                $mapped[$field] = $foundCol;
            }
        }

        if ($missing !== []) {
            throw new \RuntimeException(
                'Faltan columnas obligatorias en '.$this->origenLabel($origen).': '.implode(', ', $missing)
                .'. Encabezados encontrados en fila 2: '.implode(', ', array_values($rawHeaders)).'.',
            );
        }

        return $mapped;
    }

    /**
     * @param  array<string, int>  $headerMap
     * @return array<string, mixed>
     */
    private function readMappedRow(Worksheet $sheet, int $row, array $headerMap): array
    {
        $data = [];
        foreach ($headerMap as $field => $col) {
            $data[$field] = SpreadsheetCellReader::value($sheet, $col, $row);
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

    private function normalizeHeader(string $value): string
    {
        $normalized = mb_strtolower(trim($value));
        $normalized = strtr($normalized, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n',
        ]);
        $normalized = preg_replace('/[^a-z0-9]+/u', '', $normalized) ?? $normalized;

        return $normalized;
    }

    private function composeFullName(?string ...$parts): string
    {
        $tokens = [];
        foreach ($parts as $part) {
            $trimmed = trim((string) ($part ?? ''));
            if ($trimmed !== '') {
                $tokens[] = $trimmed;
            }
        }

        $name = implode(' ', $tokens);
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

        return mb_substr(trim($name), 0, 255);
    }

    private function nullableString(mixed $value, int $max): ?string
    {
        $trimmed = trim((string) ($value ?? ''));
        if ($trimmed === '') {
            return null;
        }

        return mb_substr($trimmed, 0, $max);
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
            return Carbon::parse(trim((string) $value))->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function origenLabel(string $origen): string
    {
        /** @var array<string, string> $labels */
        $labels = config('acreditaciones.reporte_diario.origenes', []);

        return $labels[$origen] ?? $origen;
    }

    private function fileFieldForOrigen(string $origen): string
    {
        return $origen === self::ORIGEN_PROCESO ? 'file_proceso' : 'file_acreditado';
    }

    private function fileNameColumn(string $origen): string
    {
        return $origen === self::ORIGEN_PROCESO ? 'proceso_file_name' : 'acreditado_file_name';
    }

    private function rowsOkColumn(string $origen): string
    {
        return $origen === self::ORIGEN_PROCESO ? 'proceso_rows_ok' : 'acreditado_rows_ok';
    }

    private function rowsFailColumn(string $origen): string
    {
        return $origen === self::ORIGEN_PROCESO ? 'proceso_rows_fail' : 'acreditado_rows_fail';
    }

    private function loadedAtColumn(string $origen): string
    {
        return $origen === self::ORIGEN_PROCESO ? 'proceso_loaded_at' : 'acreditado_loaded_at';
    }

    private function loadedByColumn(string $origen): string
    {
        return $origen === self::ORIGEN_PROCESO ? 'proceso_loaded_by' : 'acreditado_loaded_by';
    }
}
