<?php

namespace App\Services\GestionHumana;

use App\Models\ClienteInternoEstado;
use App\Models\ClienteInternoSolicitud;
use App\Models\ClienteInternoTipoSolicitud;
use App\Support\SpreadsheetCellReader;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Import masivo replace-por-periodo (opción B):
 * valida todo → DELETE solo anio+mes → INSERT todas las filas válidas (spillover OK).
 */
class ClienteInternoImportService
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

    public function __construct(
        private readonly ClienteInternoBusinessDaysService $businessDaysService,
        private readonly ClienteInternoAuditLogService $auditLogService,
    ) {}

    public function countInPeriod(int $anio, int $mes): int
    {
        return ClienteInternoSolicitud::query()
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->count();
    }

    /**
     * @return array{
     *     anio: int,
     *     mes: int,
     *     deleted_in_period: int,
     *     imported: int,
     *     accepted_outside_period: int,
     *     skipped_empty: int,
     *     errors_count: int,
     *     tipos_created: int,
     *     estados_created: int
     * }
     */
    public function import(string $path, int $anio, int $mes, ?int $userId = null): array
    {
        if (! is_readable($path)) {
            throw new \InvalidArgumentException('No se puede leer el archivo: '.$path);
        }

        if ($mes < 1 || $mes > 12) {
            throw new \InvalidArgumentException('El mes del periodo debe estar entre 1 y 12.');
        }

        $memoryLimit = (string) config('cliente_interno.import.memory_limit', '512M');
        $timeLimit = (int) config('cliente_interno.import.time_limit', 300);
        $chunkSize = max(1, (int) config('cliente_interno.import.chunk_size', 500));
        $maxRows = max(1, (int) config('cliente_interno.import.max_rows', 50000));

        @ini_set('memory_limit', $memoryLimit);
        @set_time_limit($timeLimit);

        $spreadsheet = $this->loadSpreadsheet($path);
        $sheet = $spreadsheet->getActiveSheet();
        $headers = $this->readHeaders($sheet);
        $this->assertRequiredHeaders($headers);

        $maxRow = (int) $sheet->getHighestDataRow();
        $dataRowCount = max(0, $maxRow - 1);

        if ($dataRowCount > $maxRows) {
            $this->releaseSpreadsheet($spreadsheet);

            throw new \RuntimeException(
                "El archivo tiene demasiadas filas de datos ({$dataRowCount}). El máximo permitido es {$maxRows}. Dataset sin cambios."
            );
        }

        $tipoLookup = $this->buildCatalogLookup(ClienteInternoTipoSolicitud::query()->get(['id', 'code', 'name']));
        $estadoLookup = $this->buildCatalogLookup(ClienteInternoEstado::query()->get(['id', 'code', 'name']));
        $tiposCreated = 0;
        $estadosCreated = 0;

        $rows = [];
        $skippedEmpty = 0;
        $errors = [];
        $acceptedOutsidePeriod = 0;

        for ($row = 2; $row <= $maxRow; $row++) {
            $data = $this->readRow($sheet, $row, $headers);

            if ($this->rowIsEmpty($data)) {
                $skippedEmpty++;

                continue;
            }

            try {
                $mapped = $this->mapValidatedRow(
                    $data,
                    $tipoLookup,
                    $estadoLookup,
                    $tiposCreated,
                    $estadosCreated,
                );
                if ((int) $mapped['anio'] !== $anio || (int) $mapped['mes'] !== $mes) {
                    $acceptedOutsidePeriod++;
                }
                $rows[] = $mapped;
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

        $deletedInPeriod = $this->countInPeriod($anio, $mes);
        $now = now();

        DB::transaction(function () use ($rows, $now, $chunkSize, $anio, $mes, $userId): void {
            ClienteInternoSolicitud::query()
                ->where('anio', $anio)
                ->where('mes', $mes)
                ->delete();

            foreach (array_chunk($rows, $chunkSize) as $chunk) {
                $payload = array_map(
                    static function (array $row) use ($now, $userId): array {
                        return [
                            ...$row,
                            'created_by' => $userId,
                            'updated_by' => $userId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    },
                    $chunk,
                );

                ClienteInternoSolicitud::query()->insert($payload);
            }
        });

        $stats = [
            'anio' => $anio,
            'mes' => $mes,
            'deleted_in_period' => $deletedInPeriod,
            'imported' => count($rows),
            'accepted_outside_period' => $acceptedOutsidePeriod,
            'skipped_empty' => $skippedEmpty,
            'errors_count' => 0,
            'tipos_created' => $tiposCreated,
            'estados_created' => $estadosCreated,
        ];

        $this->auditLogService->logEvent(
            eventType: 'import',
            action: 'import_replace_period',
            metadata: $stats,
            userId: $userId,
        );

        return $stats;
    }

    /**
     * Parsea fecha Excel serial, Y-m-d o texto ES tipo "jueves, 4 de junio de 2026, 00:00".
     */
    public function parseFecha(mixed $value): ?Carbon
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

        // Excel regional: 12/1/2025 o 10/06/22026 (año con typo de 5 dígitos).
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4,5})$/', $raw, $matches) === 1) {
            $a = (int) $matches[1];
            $b = (int) $matches[2];
            $year = $this->normalizeImportYear((int) $matches[3]);

            try {
                if ($a > 12 && $b <= 12) {
                    return Carbon::createFromDate($year, $b, $a)->startOfDay();
                }
                if ($b > 12 && $a <= 12) {
                    return Carbon::createFromDate($year, $a, $b)->startOfDay();
                }

                // Ambiguo (ambos ≤12): día/mes (formato Colombia / digitación manual en Excel).
                return Carbon::createFromDate($year, $b, $a)->startOfDay();
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
        $columns = config('cliente_interno.import.columns', []);

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
        $columns = config('cliente_interno.import.columns', []);
        $data = [];

        foreach ($columns as $key => $label) {
            $col = $headers[$label] ?? null;
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
     * @param  array<string, int>  $tipoLookup
     * @param  array<string, int>  $estadoLookup
     * @return array{
     *     fecha_solicitud: string,
     *     anio: int,
     *     mes: int,
     *     nombre_apellidos: string,
     *     cedula: string,
     *     correo_electronico: string|null,
     *     tipo_solicitud_id: int,
     *     fecha_respuesta: string|null,
     *     estado_id: int|null,
     *     novedad: string|null,
     *     dias_respuesta: int|null,
     *     dias_respuesta_manual: int
     * }
     */
    private function mapValidatedRow(
        array $data,
        array &$tipoLookup,
        array &$estadoLookup,
        int &$tiposCreated,
        int &$estadosCreated,
    ): array {
        $nombre = trim((string) ($data['nombre_apellidos'] ?? ''));
        $cedula = trim((string) ($data['cedula'] ?? ''));
        $correo = trim((string) ($data['correo_electronico'] ?? ''));
        $solicitudRaw = trim((string) ($data['solicitud'] ?? ''));
        $estadoRaw = trim((string) ($data['estado'] ?? ''));
        $novedad = trim((string) ($data['novedad'] ?? ''));
        $diasRaw = trim((string) ($data['dias_respuesta'] ?? ''));

        if ($nombre === '') {
            throw new \InvalidArgumentException('El nombre y apellidos son obligatorios.');
        }

        if ($cedula === '') {
            throw new \InvalidArgumentException('La cédula es obligatoria.');
        }

        if ($solicitudRaw === '') {
            throw new \InvalidArgumentException('La solicitud (tipo) es obligatoria.');
        }

        $fechaSolicitud = $this->parseFecha($data['fecha_solicitud'] ?? null);
        if ($fechaSolicitud === null) {
            throw new \InvalidArgumentException('La fecha de solicitud es obligatoria o inválida.');
        }

        // Primera carga: crea en catálogo los tipos que vengan en el Excel y no existan.
        $tipoId = $this->resolveOrCreateTipoId($solicitudRaw, $tipoLookup, $tiposCreated);

        $fechaRespuesta = null;
        $fechaRespuestaRaw = $data['fecha_respuesta'] ?? null;
        if (trim((string) ($fechaRespuestaRaw ?? '')) !== '') {
            $fechaRespuesta = $this->parseFecha($fechaRespuestaRaw);
            if ($fechaRespuesta === null) {
                throw new \InvalidArgumentException('La fecha de respuesta es inválida.');
            }
        }

        $estadoId = null;
        if ($estadoRaw !== '') {
            // Igual que tipos: alta automática si el estado del Excel no está en catálogo.
            $estadoId = $this->resolveOrCreateEstadoId($estadoRaw, $estadoLookup, $estadosCreated);
        }

        // Días: vacío → auto; número → persistir como manual.
        if ($diasRaw === '') {
            $diasRespuesta = $this->businessDaysService->count($fechaSolicitud, $fechaRespuesta);
            $diasManual = false;
        } else {
            if (! is_numeric($diasRaw) || (float) $diasRaw < 0 || floor((float) $diasRaw) != (float) $diasRaw) {
                throw new \InvalidArgumentException('Los días de respuesta deben ser un número entero ≥ 0.');
            }
            $diasRespuesta = (int) $diasRaw;
            $diasManual = true;
        }

        return [
            'fecha_solicitud' => $fechaSolicitud->toDateString(),
            'anio' => (int) $fechaSolicitud->year,
            'mes' => (int) $fechaSolicitud->month,
            'nombre_apellidos' => mb_substr($nombre, 0, 255),
            'cedula' => mb_substr($cedula, 0, 50),
            'correo_electronico' => $correo === '' ? null : mb_substr($correo, 0, 150),
            'tipo_solicitud_id' => $tipoId,
            'fecha_respuesta' => $fechaRespuesta?->toDateString(),
            'estado_id' => $estadoId,
            'novedad' => $novedad === '' ? null : $novedad,
            'dias_respuesta' => $diasRespuesta,
            // insert() raw: bool → int para sqlite/mysql.
            'dias_respuesta_manual' => $diasManual ? 1 : 0,
        ];
    }

    /**
     * @param  array<string, int>  $tipoLookup
     */
    private function resolveOrCreateTipoId(string $raw, array &$tipoLookup, int &$tiposCreated): int
    {
        $existing = $this->resolveCatalogId($raw, $tipoLookup);
        if ($existing !== null) {
            return $existing;
        }

        $name = mb_substr(trim($raw), 0, 150);
        $code = $this->makeUniqueCatalogCode($name, ClienteInternoTipoSolicitud::class);
        $sortOrder = ((int) ClienteInternoTipoSolicitud::query()->max('sort_order')) + 1;

        $tipo = ClienteInternoTipoSolicitud::query()->create([
            'code' => $code,
            'name' => $name,
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);

        $this->registerCatalogInLookup($tipoLookup, (int) $tipo->id, $code, $name);
        $tiposCreated++;

        return (int) $tipo->id;
    }

    /**
     * @param  array<string, int>  $estadoLookup
     */
    private function resolveOrCreateEstadoId(string $raw, array &$estadoLookup, int &$estadosCreated): int
    {
        $existing = $this->resolveCatalogId($raw, $estadoLookup);
        if ($existing !== null) {
            return $existing;
        }

        $name = mb_substr(trim($raw), 0, 100);
        $code = $this->makeUniqueCatalogCode($name, ClienteInternoEstado::class);
        $sortOrder = ((int) ClienteInternoEstado::query()->max('sort_order')) + 1;

        $estado = ClienteInternoEstado::query()->create([
            'code' => $code,
            'name' => $name,
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);

        $this->registerCatalogInLookup($estadoLookup, (int) $estado->id, $code, $name);
        $estadosCreated++;

        return (int) $estado->id;
    }

    /**
     * @param  class-string<ClienteInternoEstado|ClienteInternoTipoSolicitud>  $modelClass
     */
    private function makeUniqueCatalogCode(string $name, string $modelClass): string
    {
        $slug = Str::upper((string) Str::slug($name, '_'));
        $slug = (string) preg_replace('/[^A-Z0-9_]/', '', $slug);
        $base = $slug !== '' ? mb_substr($slug, 0, 45) : 'ITEM';
        $code = $base;
        $i = 2;

        while ($modelClass::query()->where('code', $code)->exists()) {
            $suffix = '_'.$i;
            $code = mb_substr($base, 0, 50 - strlen($suffix)).$suffix;
            $i++;
        }

        return $code;
    }

    /**
     * @param  array<string, int>  $lookup
     */
    private function registerCatalogInLookup(array &$lookup, int $id, string $code, string $name): void
    {
        $codeKey = $this->normalizeLookupKey($code);
        $nameKey = $this->normalizeLookupKey($name);

        if ($codeKey !== '') {
            $lookup[$codeKey] = $id;
        }
        if ($nameKey !== '') {
            $lookup[$nameKey] = $id;
        }
    }

    /**
     * @param  Collection<int, ClienteInternoEstado|ClienteInternoTipoSolicitud>  $items
     * @return array<string, int>
     */
    private function buildCatalogLookup($items): array
    {
        $lookup = [];

        foreach ($items as $item) {
            $id = (int) $item->id;
            $codeKey = $this->normalizeLookupKey((string) $item->code);
            $nameKey = $this->normalizeLookupKey((string) $item->name);

            if ($codeKey !== '') {
                $lookup[$codeKey] = $id;
            }
            if ($nameKey !== '') {
                $lookup[$nameKey] = $id;
            }
        }

        return $lookup;
    }

    /**
     * @param  array<string, int>  $lookup
     */
    private function resolveCatalogId(string $raw, array $lookup): ?int
    {
        $key = $this->normalizeLookupKey($raw);

        return $key !== '' && isset($lookup[$key]) ? $lookup[$key] : null;
    }

    private function normalizeLookupKey(string $value): string
    {
        return mb_strtolower(trim($value), 'UTF-8');
    }

    /**
     * Corrige años mal digitados en Excel (p. ej. 22026 → 2026).
     */
    private function normalizeImportYear(int $year): int
    {
        if ($year >= 22000 && $year <= 22999) {
            return $year - 20000;
        }

        $digits = (string) $year;
        if (strlen($digits) === 5 && str_starts_with($digits, '2')) {
            $candidate = (int) substr($digits, 1);
            if ($candidate >= 2000 && $candidate <= 2100) {
                return $candidate;
            }
        }

        return $year;
    }

    private function parseSpanishDate(string $raw): ?Carbon
    {
        $normalized = mb_strtolower($raw, 'UTF-8');
        $normalized = str_replace(["\xc2\xa0", '  '], ' ', $normalized);
        $normalized = trim($normalized);

        $normalized = (string) preg_replace(
            '/^(lunes|martes|miércoles|miercoles|jueves|viernes|sábado|sabado|domingo)\s*,\s*/u',
            '',
            $normalized,
        );

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
