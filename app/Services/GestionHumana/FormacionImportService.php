<?php

namespace App\Services\GestionHumana;

use App\Models\FormacionRegistro;
use App\Support\SpreadsheetCellReader;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FormacionImportService
{
    private const CHUNK_SIZE = 500;

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
     * @return array{
     *     deleted_before: int,
     *     imported: int,
     *     skipped_empty: int,
     *     errors_count: int
     * }
     */
    public function import(string $path, ?int $userId = null): array
    {
        if (! is_readable($path)) {
            throw new \InvalidArgumentException('No se puede leer el archivo: '.$path);
        }

        @set_time_limit(300);

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $headers = $this->readHeaders($sheet);
        $this->assertRequiredHeaders($headers);

        $maxRow = (int) $sheet->getHighestRow();
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

        if ($errors !== []) {
            $preview = implode(' ', array_slice($errors, 0, 5));
            $suffix = count($errors) > 5 ? ' …' : '';

            throw new \RuntimeException(
                'Import rechazado: '.count($errors).' fila(s) con errores. Dataset sin cambios. '.$preview.$suffix
            );
        }

        $deletedBefore = FormacionRegistro::query()->count();
        $now = now();

        DB::transaction(function () use ($rows, $now): void {
            FormacionRegistro::query()->delete();

            foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
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
        });

        $stats = [
            'deleted_before' => $deletedBefore,
            'imported' => count($rows),
            'skipped_empty' => $skippedEmpty,
            'errors_count' => 0,
        ];

        app(FormacionAuditLogService::class)->logEvent(
            eventType: 'import',
            action: 'import_replace',
            metadata: $stats,
            userId: $userId,
        );

        return $stats;
    }

    /**
     * Parsea fecha Excel serial, Y-m-d o texto ES tipo "jueves, 4 de junio de 2026, 00:00".
     */
    public function parseFechaInicio(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
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

    /**
     * @return array<string, int> header label => column index (1-based)
     */
    private function readHeaders(Worksheet $sheet): array
    {
        $headers = [];
        $maxCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());

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
            $data[$key] = $col !== null
                ? SpreadsheetCellReader::value($sheet, $col, $row)
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
