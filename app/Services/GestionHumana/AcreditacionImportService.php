<?php

namespace App\Services\GestionHumana;

use App\Models\AcreditacionAcreditado;
use App\Models\AcreditacionCargo;
use App\Models\EmployeeFichaProfile;
use App\Support\ImportFailureRow;
use App\Support\SpreadsheetCellReader;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AcreditacionImportService
{
    public function __construct(
        private readonly AcreditacionEstadoCalculator $estadoCalculator,
    ) {}

    /**
     * @return array{
     *     imported: int,
     *     updated: int,
     *     skipped: int,
     *     empty_rows: int,
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
            'errors' => [],
            'failures' => [],
        ];

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $headers = $this->readHeaders($sheet);

        if ($headers === []) {
            throw new \RuntimeException('El archivo no tiene encabezados validos en la fila 1.');
        }

        foreach (array_keys(config('acreditaciones.import.columns', [])) as $requiredKey) {
            if (! array_key_exists($requiredKey, $headers)) {
                throw new \RuntimeException("Falta la columna obligatoria \"{$requiredKey}\" en la fila 1.");
            }
        }

        $cargoApoIndex = $this->buildCargoApoIndex();
        $maxRow = (int) $sheet->getHighestRow();

        for ($row = 3; $row <= $maxRow; $row++) {
            $data = $this->readRow($sheet, $row, $headers);

            if ($this->rowIsEmpty($data)) {
                $stats['empty_rows']++;

                continue;
            }

            $cedula = trim((string) ($data['document_number'] ?? ''));

            try {
                if ($cedula === '') {
                    throw new \InvalidArgumentException('La cedula es obligatoria.');
                }

                $profile = EmployeeFichaProfile::query()
                    ->where('document_number', $cedula)
                    ->first(['id', 'document_number', 'full_name']);

                if ($profile === null) {
                    throw new \InvalidArgumentException('La cedula no existe en Ficha empleados.');
                }

                $fullName = trim((string) ($profile->full_name ?? ''));
                if ($fullName === '') {
                    throw new \InvalidArgumentException('La ficha de esta cedula no tiene nombre completo.');
                }

                $cargo = trim((string) ($data['cargo'] ?? ''));
                if ($cargo === '') {
                    throw new \InvalidArgumentException('El cargo es obligatorio.');
                }

                $cargoApoInput = trim((string) ($data['cargo_apo'] ?? ''));
                if ($cargoApoInput === '') {
                    throw new \InvalidArgumentException('El CARGO APO es obligatorio.');
                }

                $cargoApoCanonical = $cargoApoIndex[mb_strtolower($cargoApoInput)] ?? null;
                if ($cargoApoCanonical === null) {
                    throw new \InvalidArgumentException(
                        'El CARGO APO no existe como valor activo en el catalogo.',
                    );
                }

                $vigencia = $this->resolveVigenciaAcr($data['vigencia_acr'] ?? null);
                $fechaSolicitud = $this->parseDate($data['fecha_solicitud'] ?? null);

                $observaciones = trim((string) ($data['observaciones'] ?? ''));
                $observaciones = $observaciones === '' ? null : $observaciones;

                $vigenciaCarbon = $vigencia !== null ? Carbon::parse($vigencia) : null;
                $solicitudCarbon = $fechaSolicitud !== null ? Carbon::parse($fechaSolicitud) : null;
                $estado = $this->estadoCalculator->calculate($solicitudCarbon, $vigenciaCarbon);

                DB::transaction(function () use (
                    $cedula,
                    $fullName,
                    $cargo,
                    $cargoApoCanonical,
                    $vigencia,
                    $fechaSolicitud,
                    $estado,
                    $observaciones,
                    $userId,
                    &$stats,
                ): void {
                    $existing = AcreditacionAcreditado::query()
                        ->where('document_number', $cedula)
                        ->forCargoApo($cargoApoCanonical)
                        ->first();

                    $payload = [
                        'document_number' => $cedula,
                        'full_name' => $fullName,
                        'cargo' => $cargo,
                        'cargo_apo' => $cargoApoCanonical,
                        'vigencia_acr' => $vigencia,
                        'fecha_solicitud' => $fechaSolicitud,
                        'estado' => $estado,
                        'observaciones' => $observaciones,
                        'updated_by' => $userId,
                    ];

                    if ($existing !== null) {
                        $existing->update($payload);
                        $stats['updated']++;
                    } else {
                        AcreditacionAcreditado::query()->create([
                            ...$payload,
                            'created_by' => $userId,
                        ]);
                        $stats['imported']++;
                    }
                });
            } catch (\Throwable $e) {
                $failure = ImportFailureRow::make(
                    $row,
                    $cedula !== '' ? $cedula : null,
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
     * @return array<string, string> lowercase cargo_apo => canonical catalog value
     */
    private function buildCargoApoIndex(): array
    {
        $index = [];

        foreach (AcreditacionCargo::query()->active()->get(['cargo_apo']) as $cargo) {
            $canonical = trim((string) $cargo->cargo_apo);
            if ($canonical === '') {
                continue;
            }

            $key = mb_strtolower($canonical);
            if (! array_key_exists($key, $index)) {
                $index[$key] = $canonical;
            }
        }

        return $index;
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
     * VIGEN.ACR en Excel: fecha, vacío o marcador «en proceso» (y variantes).
     * Vacío / en proceso → null (estado EN_PROCESO vía calculador).
     */
    private function resolveVigenciaAcr(mixed $value): ?string
    {
        if ($this->isEnProcesoVigenciaValue($value)) {
            return null;
        }

        $parsed = $this->parseDate($value);

        if ($parsed === null) {
            throw new \InvalidArgumentException(
                'VIGEN.ACR no es una fecha válida. Use una fecha, déjelo vacío o indique «en proceso».',
            );
        }

        return $parsed;
    }

    /**
     * Celda vacía o texto de trámite (en proceso / EN PROCESO / EN_PROCESO, sin importar mayúsculas).
     */
    private function isEnProcesoVigenciaValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_numeric($value)) {
            return false;
        }

        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return true;
        }

        $normalized = mb_strtolower($trimmed);
        $normalized = preg_replace('/[\s_\-\.]+/u', '', $normalized) ?? $normalized;
        $normalized = strtr($normalized, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
        ]);

        return $normalized === 'enproceso';
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
}
