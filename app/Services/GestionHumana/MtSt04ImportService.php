<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeFichaProfile;
use App\Models\MtSt04Registro;
use App\Support\ImportFailureRow;
use App\Support\SpreadsheetCellReader;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Import upsert MT-ST-04 por cédula: última fila del archivo gana; cédula debe existir en Ficha.
 */
class MtSt04ImportService
{
    public function __construct(
        private readonly MtSt04EstadoCalculator $estadoCalculator,
    ) {}

    /**
     * @return array{
     *     imported: int,
     *     updated: int,
     *     skipped: int,
     *     empty_rows: int,
     *     duplicates_collapsed: int,
     *     errors: list<string>,
     *     failures: list<array<string, mixed>>
     * }
     */
    public function import(string $path, ?int $userId = null): array
    {
        if (! is_readable($path)) {
            throw new \InvalidArgumentException('No se puede leer el archivo: '.$path);
        }

        $stats = [
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'empty_rows' => 0,
            'duplicates_collapsed' => 0,
            'errors' => [],
            'failures' => [],
        ];

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $headers = $this->readHeaders($sheet);

        if ($headers === []) {
            throw new \RuntimeException('El archivo no tiene encabezados válidos en la fila 1.');
        }

        foreach (array_keys(config('mt_st_04.import.columns', [])) as $requiredKey) {
            if (! array_key_exists($requiredKey, $headers)) {
                throw new \RuntimeException("Falta la columna obligatoria \"{$requiredKey}\" en la fila 1.");
            }
        }

        $maxRow = (int) $sheet->getHighestDataRow();

        /** @var array<string, array{row: int, payload: array<string, mixed>, cargo: string}> $pending */
        $pending = [];

        for ($row = 3; $row <= $maxRow; $row++) {
            $data = $this->readRow($sheet, $row, $headers);

            if ($this->rowIsEmpty($data)) {
                $stats['empty_rows']++;

                continue;
            }

            $cedula = trim((string) ($data['document_number'] ?? ''));

            try {
                if ($cedula === '') {
                    throw new \InvalidArgumentException('La cédula es obligatoria.');
                }

                $profile = EmployeeFichaProfile::query()
                    ->where('document_number', $cedula)
                    ->first(['id', 'document_number', 'position_name']);

                if ($profile === null) {
                    throw new \InvalidArgumentException('La cédula no existe en Ficha empleados.');
                }

                $arma = $this->normalizeSiNo($data['arma'] ?? null, 'ARMA');
                $apto = $this->normalizeSiNo($data['apto'] ?? null, 'APTO');
                $fechaExamen1 = $this->parseOptionalDate($data['fecha_examen_1'] ?? null, 'FECHA DE EXAMEN');
                $fechaExamen2 = $this->parseOptionalDate($data['fecha_examen_2'] ?? null, 'FECHA EXAMEN');
                $observaciones1 = $this->nullableTrim($data['observaciones_1'] ?? null);
                $observaciones2 = $this->nullableTrim($data['observaciones_2'] ?? null);

                $payload = [
                    'document_number' => $cedula,
                    'arma' => $arma,
                    'fecha_examen_1' => $fechaExamen1,
                    'apto' => $apto,
                    'observaciones_1' => $observaciones1,
                    'fecha_examen_2' => $fechaExamen2,
                    'observaciones_2' => $observaciones2,
                ];

                if (array_key_exists($cedula, $pending)) {
                    $stats['duplicates_collapsed']++;
                }

                $pending[$cedula] = [
                    'row' => $row,
                    'payload' => $payload,
                    'cargo' => trim((string) ($profile->position_name ?? '')),
                ];
            } catch (\Throwable $e) {
                $failure = ImportFailureRow::make(
                    $row,
                    $cedula !== '' ? $cedula : null,
                    'Cédula',
                    ImportFailureRow::SEVERITY_ERROR,
                    $e->getMessage(),
                    $data,
                );
                $stats['failures'][] = $failure;
                $stats['errors'][] = ImportFailureRow::message($failure);
                $stats['skipped']++;
            }
        }

        foreach ($pending as $item) {
            DB::transaction(function () use ($item, $userId, &$stats): void {
                $payload = $item['payload'];
                $existing = MtSt04Registro::query()
                    ->where('document_number', $payload['document_number'])
                    ->first();

                if ($existing !== null) {
                    $existing->fill([
                        ...$payload,
                        'updated_by' => $userId,
                    ]);
                    $this->estadoCalculator->applyToModel($existing, $item['cargo']);
                    $existing->save();
                    $stats['updated']++;
                } else {
                    $registro = new MtSt04Registro([
                        ...$payload,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                    $this->estadoCalculator->applyToModel($registro, $item['cargo']);
                    $registro->save();
                    $stats['imported']++;
                }
            });
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
        foreach (array_keys(config('mt_st_04.import.columns', [])) as $key) {
            $col = $headers[$key] ?? null;
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

    private function nullableTrim(mixed $value): ?string
    {
        $trimmed = trim((string) ($value ?? ''));

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * Normaliza SI/NO (acepta variantes con tilde / minúsculas). Vacío → null.
     */
    private function normalizeSiNo(mixed $value, string $fieldLabel): ?string
    {
        if ($value === null) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $normalized = mb_strtoupper($raw, 'UTF-8');
        $normalized = strtr($normalized, [
            'Í' => 'I',
        ]);

        if ($normalized === 'SI') {
            return MtSt04Registro::ARMA_SI;
        }

        if ($normalized === 'NO') {
            return MtSt04Registro::ARMA_NO;
        }

        throw new \InvalidArgumentException(
            "{$fieldLabel} debe ser SI o NO.",
        );
    }

    private function parseOptionalDate(mixed $value, string $fieldLabel): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(Date::excelToDateTimeObject((float) $value))->toDateString();
            } catch (\Throwable) {
                throw new \InvalidArgumentException("{$fieldLabel} no es una fecha válida.");
            }
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->toDateString();
        } catch (\Throwable) {
            throw new \InvalidArgumentException("{$fieldLabel} no es una fecha válida.");
        }
    }
}
