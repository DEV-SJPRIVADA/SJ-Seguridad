<?php

namespace App\Services\GestionHumana;

use App\Models\FormacionRegistro;
use App\Support\SpreadsheetCellReader;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FormacionImportService
{
    /**
     * @var array<string, int>
     */
    private const MESES_ES = [
        'enero' => 1,
        'febrero' => 2,
        'marzo' => 3,
        'abril' => 4,
        'mayo' => 5,
        'junio' => 6,
        'julio' => 7,
        'agosto' => 8,
        'septiembre' => 9,
        'setiembre' => 9,
        'octubre' => 10,
        'noviembre' => 11,
        'diciembre' => 12,
    ];

    /**
     * @param  'all'|'period'  $mode
     * @return array{
     *     deleted_before: int,
     *     imported: int,
     *     skipped_empty: int,
     *     errors_count: int,
     *     mode: string,
     *     anio: int|null,
     *     mes: int|null
     * }
     */
    public function import(
        string $path,
        ?int $userId = null,
        string $mode = 'all',
        ?int $anio = null,
        ?int $mes = null,
    ): array {
        if (! in_array($mode, ['all', 'period'], true)) {
            throw new \InvalidArgumentException('Modo de importación no válido.');
        }

        if ($mode === 'period') {
            if ($anio === null || $mes === null || $mes < 1 || $mes > 12) {
                throw new \InvalidArgumentException('Para importar un mes debe indicar año y mes válidos.');
            }
        }

        $parsed = $this->parseImportFile($path);
        $rows = $parsed['rows'];
        $skippedEmpty = $parsed['skipped_empty'];

        if ($mode === 'period') {
            $this->assertRowsMatchPeriod($rows, (int) $anio, (int) $mes);
        }

        $chunkSize = max(1, (int) config('formacion.import.chunk_size', 500));
        $now = now();

        if ($mode === 'all') {
            $deletedBefore = FormacionRegistro::query()->count();

            DB::transaction(function () use ($rows, $now, $chunkSize): void {
                FormacionRegistro::query()->delete();
                $this->insertChunks($rows, $now, $chunkSize);
            });

            $action = 'import_replace';
        } else {
            $deletedBefore = FormacionRegistro::query()
                ->where('anio', $anio)
                ->where('mes', $mes)
                ->count();

            DB::transaction(function () use ($rows, $now, $chunkSize, $anio, $mes): void {
                FormacionRegistro::query()
                    ->where('anio', $anio)
                    ->where('mes', $mes)
                    ->delete();
                $this->insertChunks($rows, $now, $chunkSize);
            });

            $action = 'import_replace_period';
        }

        $stats = [
            'deleted_before' => $deletedBefore,
            'imported' => count($rows),
            'skipped_empty' => $skippedEmpty,
            'errors_count' => 0,
            'mode' => $mode,
            'anio' => $mode === 'period' ? (int) $anio : null,
            'mes' => $mode === 'period' ? (int) $mes : null,
        ];

        app(FormacionAuditLogService::class)->logEvent(
            eventType: 'import',
            action: $action,
            metadata: $stats,
            userId: $userId,
        );

        return $stats;
    }

    /**
     * @return array{
     *     rows: list<array{
     *         numero_id: string,
     *         nombre_completo: string,
     *         fecha_inicio: string,
     *         mes: int,
     *         anio: int,
     *         nombre_curso: string,
     *         calificacion: string|null,
     *         categoria: string
     *     }>,
     *     skipped_empty: int
     * }
     */
    private function parseImportFile(string $path): array
    {
        if (! is_readable($path)) {
            throw new \InvalidArgumentException('No se puede leer el archivo: '.$path);
        }

        $memoryLimit = (string) config('formacion.import.memory_limit', '1024M');
        $timeLimit = (int) config('formacion.import.time_limit', 600);
        $maxRows = max(1, (int) config('formacion.import.max_rows', 150000));

        @ini_set('memory_limit', $memoryLimit);
        @set_time_limit($timeLimit);

        $spreadsheet = $this->loadSpreadsheet($path);
        $sheet = $spreadsheet->getActiveSheet();
        $headers = $this->readHeaders($sheet);
        $this->assertRequiredHeaders($headers);

        // getHighestRow() puede reportar filas “fantasma” por formato; usar datos reales.
        $maxRow = (int) $sheet->getHighestDataRow();
        $dataRowCount = max(0, $maxRow - 1);

        if ($dataRowCount > $maxRows) {
            $this->releaseSpreadsheet($spreadsheet);

            throw new \RuntimeException(
                "El archivo tiene demasiadas filas de datos ({$dataRowCount}). El máximo permitido es {$maxRows}. Dataset sin cambios."
            );
        }

        $rows = [];
        $skippedEmpty = 0;
        $errors = [];

        for ($row = 2; $row <= $maxRow; $row++) {
            $data = $this->readRow($sheet, $row, $headers);

            if ($this->rowIsEmpty($data)) {
                $skippedEmpty++;

                continue;
            }

            try {
                $rows[] = $this->mapValidatedRow($data);
            } catch (\Throwable $e) {
                $errors[] = "Fila {$row}: ".$e->getMessage();
            }
        }

        $this->releaseSpreadsheet($spreadsheet);

        if ($errors !== []) {
            $preview = implode(' ', array_slice($errors, 0, 5));
            $suffix = count($errors) > 5 ? ' …' : '';

            throw new \RuntimeException(
                'Import rechazado: '.count($errors).' fila(s) con errores. Dataset sin cambios. '.$preview.$suffix
            );
        }

        if ($rows === []) {
            throw new \RuntimeException(
                'Import rechazado: el archivo no contiene filas de datos válidas. Dataset sin cambios.'
            );
        }

        return [
            'rows' => $rows,
            'skipped_empty' => $skippedEmpty,
        ];
    }

    /**
     * @param  list<array{mes: int, anio: int, fecha_inicio: string}>  $rows
     */
    private function assertRowsMatchPeriod(array $rows, int $anio, int $mes): void
    {
        $mismatched = [];

        foreach ($rows as $index => $row) {
            if ((int) $row['anio'] !== $anio || (int) $row['mes'] !== $mes) {
                $mismatched[] = sprintf(
                    'fila %d (%s → %02d/%d)',
                    $index + 2,
                    $row['fecha_inicio'],
                    (int) $row['mes'],
                    (int) $row['anio'],
                );
            }
        }

        if ($mismatched === []) {
            return;
        }

        $preview = implode('; ', array_slice($mismatched, 0, 5));
        $suffix = count($mismatched) > 5 ? ' …' : '';
        $mesLabel = $this->monthLabel($mes);

        $count = count($mismatched);

        throw new \RuntimeException(
            "Import rechazado: {$count} fila(s) no pertenecen a {$mesLabel} {$anio}. "
            .'Todas las fechas del Excel deben ser de ese mes. Dataset sin cambios. '
            ."Ejemplos: {$preview}{$suffix}"
        );
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function insertChunks(array $rows, mixed $now, int $chunkSize): void
    {
        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            $payload = array_map(
                static function (array $row) use ($now): array {
                    return [
                        ...$row,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                },
                $chunk,
            );

            FormacionRegistro::query()->insert($payload);
        }
    }

    private function monthLabel(int $mes): string
    {
        $labels = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];

        return $labels[$mes] ?? (string) $mes;
    }

    /**
     * Parsea fecha Excel serial, Y-m-d o texto ES tipo "jueves, 4 de junio de 2026, 00:00".
     */
    public function parseFechaInicio(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::parse($value)->startOfDay();
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(Date::excelToDateTimeObject((float) $value))->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) === 1) {
            try {
                return Carbon::createFromFormat('Y-m-d', $raw)->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        }

        $spanish = $this->parseSpanishDate($raw);
        if ($spanish !== null) {
            return $spanish;
        }

        try {
            return Carbon::parse($raw)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
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
     * @return array<string, int> header label => column index (1-based)
     */
    private function readHeaders(Worksheet $sheet): array
    {
        $headers = [];
        $maxCol = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        for ($col = 1; $col <= $maxCol; $col++) {
            $label = trim((string) SpreadsheetCellReader::rawValue($sheet, $col, 1));
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

        /** @var array<string, string> $columns */
        $columns = config('formacion.import.columns', []);

        foreach ($columns as $label) {
            if (! array_key_exists($label, $headers)) {
                throw new \RuntimeException(
                    "Falta la columna obligatoria \"{$label}\" en la fila 1. Dataset sin cambios."
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
        /** @var array<string, string> $columns */
        $columns = config('formacion.import.columns', []);
        $data = [];

        foreach ($columns as $key => $label) {
            $col = $headers[$label] ?? null;
            // rawValue evita getCalculatedValue() (muy costoso en ~80k filas).
            $data[$key] = $col !== null
                ? SpreadsheetCellReader::rawValue($sheet, $col, $row)
                : null;
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

    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     numero_id: string,
     *     nombre_completo: string,
     *     fecha_inicio: string,
     *     mes: int,
     *     anio: int,
     *     nombre_curso: string,
     *     calificacion: string|null,
     *     categoria: string
     * }
     */
    private function mapValidatedRow(array $data): array
    {
        $numeroId = trim((string) ($data['numero_id'] ?? ''));
        $nombreCompleto = trim((string) ($data['nombre_completo'] ?? ''));
        $nombreCurso = trim((string) ($data['nombre_curso'] ?? ''));
        $categoria = trim((string) ($data['categoria'] ?? ''));
        $calificacion = trim((string) ($data['calificacion'] ?? ''));

        if ($numeroId === '') {
            throw new \InvalidArgumentException('El número de ID es obligatorio.');
        }

        if ($nombreCompleto === '') {
            throw new \InvalidArgumentException('El nombre completo es obligatorio.');
        }

        if ($nombreCurso === '') {
            throw new \InvalidArgumentException('El nombre del curso es obligatorio.');
        }

        if ($categoria === '') {
            throw new \InvalidArgumentException('La categoría es obligatoria.');
        }

        $fecha = $this->parseFechaInicio($data['fecha_inicio'] ?? null);
        if ($fecha === null) {
            throw new \InvalidArgumentException('La fecha de inicio es obligatoria o inválida.');
        }

        return [
            'numero_id' => mb_substr($numeroId, 0, 50),
            'nombre_completo' => mb_substr($nombreCompleto, 0, 255),
            'fecha_inicio' => $fecha->toDateString(),
            'mes' => (int) $fecha->month,
            'anio' => (int) $fecha->year,
            'nombre_curso' => mb_substr($nombreCurso, 0, 255),
            'calificacion' => $calificacion === '' ? null : mb_substr($calificacion, 0, 50),
            'categoria' => mb_substr($categoria, 0, 255),
        ];
    }

    private function parseSpanishDate(string $raw): ?Carbon
    {
        $normalized = mb_strtolower($raw, 'UTF-8');
        $normalized = str_replace(["\xc2\xa0", '  '], ' ', $normalized);
        $normalized = trim($normalized);

        // Quitar día de la semana al inicio: "jueves, 4 de junio..."
        $normalized = (string) preg_replace(
            '/^(lunes|martes|miércoles|miercoles|jueves|viernes|sábado|sabado|domingo)\s*,\s*/u',
            '',
            $normalized,
        );

        // Quitar hora al final: ", 00:00" / " 00:00:00"
        $normalized = (string) preg_replace('/,?\s*\d{1,2}:\d{2}(?::\d{2})?\s*$/u', '', $normalized);
        $normalized = trim($normalized);

        if (preg_match('/^(\d{1,2})\s+de\s+([a-záéíóúñ]+)\s+de\s+(\d{4})$/u', $normalized, $matches) !== 1) {
            return null;
        }

        $day = (int) $matches[1];
        $monthName = $matches[2];
        $year = (int) $matches[3];
        $month = self::MESES_ES[$monthName] ?? null;

        if ($month === null || $day < 1 || $day > 31 || $year < 1900) {
            return null;
        }

        try {
            return Carbon::createFromDate($year, $month, $day)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
