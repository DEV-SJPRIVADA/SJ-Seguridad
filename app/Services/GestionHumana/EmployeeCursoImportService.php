<?php

namespace App\Services\GestionHumana;

use App\Models\CursoEscuela;
use App\Models\CursoTipo;
use App\Models\EmployeeCurso;
use App\Models\EmployeeFichaProfile;
use App\Support\ImportFailureRow;
use App\Support\SpreadsheetCellReader;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeCursoImportService
{
    public function __construct(
        private readonly EmployeeCursoEstadoSyncService $estadoSyncService,
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

        foreach (array_keys(config('cursos.import.columns', [])) as $requiredKey) {
            if (! array_key_exists($requiredKey, $headers)) {
                throw new \RuntimeException("Falta la columna obligatoria \"{$requiredKey}\" en la fila 1.");
            }
        }

        $tipoIndex = $this->buildTipoCursoIndex();
        $escuelaIndex = $this->buildEscuelaCodigoIndex();
        $maxRow = (int) $sheet->getHighestRow();

        for ($row = 3; $row <= $maxRow; $row++) {
            $data = $this->readRow($sheet, $row, $headers);

            if ($this->rowIsEmpty($data)) {
                $stats['empty_rows']++;

                continue;
            }

            $cedula = trim((string) ($data['cedula'] ?? ''));
            $numeroCurso = trim((string) ($data['numero_curso'] ?? ''));

            try {
                if ($cedula === '') {
                    throw new \InvalidArgumentException('La cedula es obligatoria.');
                }

                if ($numeroCurso === '') {
                    throw new \InvalidArgumentException('El numero de curso es obligatorio.');
                }

                $profile = EmployeeFichaProfile::query()
                    ->where('document_number', $cedula)
                    ->first(['id', 'full_name']);

                if ($profile === null) {
                    throw new \InvalidArgumentException('La cedula no existe en Ficha empleados.');
                }

                $fullName = trim((string) ($profile->full_name ?? ''));
                if ($fullName === '') {
                    throw new \InvalidArgumentException('La ficha de esta cedula no tiene nombre completo.');
                }

                $tipoLabel = trim((string) ($data['tipo_curso'] ?? ''));
                if ($tipoLabel === '') {
                    throw new \InvalidArgumentException('El tipo de curso es obligatorio.');
                }

                $tipoId = $tipoIndex[mb_strtolower($tipoLabel)] ?? null;
                if ($tipoId === null) {
                    throw new \InvalidArgumentException("Tipo de curso no existe en catalogo: {$tipoLabel}");
                }

                $fecha = $this->parseDate($data['fecha_expedicion'] ?? null);
                if ($fecha === null) {
                    throw new \InvalidArgumentException('La fecha de expedicion es obligatoria o invalida.');
                }

                $escuelaCodigo = CursoEscuela::extractCodigoFromNumeroCurso($numeroCurso);
                if ($escuelaCodigo === null) {
                    throw new \InvalidArgumentException(
                        'No se pudo identificar el codigo de escuela en No.CURSO (formato esperado: PREFIJOCODIGO-RESTO, ej. ECSP0015-M256412).',
                    );
                }

                $escuela = $escuelaIndex[$escuelaCodigo] ?? null;
                if ($escuela === null) {
                    throw new \InvalidArgumentException(
                        "No hay escuela activa en catalogo con codigo {$escuelaCodigo} (extraido de No.CURSO).",
                    );
                }

                $observaciones = trim((string) ($data['observaciones'] ?? ''));
                $observaciones = $observaciones === '' ? null : $observaciones;

                $numeroAnterior = trim((string) ($data['numero_curso_anterior'] ?? ''));
                $profileId = (int) $profile->id;

                $estadoProbe = new EmployeeCurso(['fecha_expedicion' => $fecha]);
                $estado = $this->estadoSyncService->resolveEstadoFromVigencia($estadoProbe);

                DB::transaction(function () use (
                    $cedula,
                    $numeroCurso,
                    $numeroAnterior,
                    $fullName,
                    $tipoId,
                    $escuela,
                    $fecha,
                    $estado,
                    $observaciones,
                    $profileId,
                    $userId,
                    &$stats,
                ): void {
                    $existing = null;

                    if ($numeroAnterior !== '') {
                        $existing = EmployeeCurso::query()
                            ->where('document_number', $cedula)
                            ->where('numero_curso', $numeroAnterior)
                            ->first();

                        if ($existing === null) {
                            throw new \InvalidArgumentException(
                                'No se encontro el curso a renovar (cedula + No.CURSO ANTERIOR). No se crea duplicado.',
                            );
                        }

                        if ($numeroCurso !== $numeroAnterior) {
                            $collision = EmployeeCurso::query()
                                ->where('document_number', $cedula)
                                ->where('numero_curso', $numeroCurso)
                                ->where('id', '!=', $existing->id)
                                ->exists();

                            if ($collision) {
                                throw new \InvalidArgumentException(
                                    'Ya existe otro curso con ese No.CURSO para la cedula. No se crea duplicado.',
                                );
                            }
                        }
                    } else {
                        $existing = EmployeeCurso::query()
                            ->where('document_number', $cedula)
                            ->where('numero_curso', $numeroCurso)
                            ->first();
                    }

                    $payload = [
                        'document_number' => $cedula,
                        'full_name' => $fullName,
                        'curso_tipo_id' => $tipoId,
                        'curso_escuela_id' => $escuela->id,
                        'escuela_codigo' => $escuela->codigo,
                        'escuela_nit' => $escuela->nit,
                        'escuela_nombre' => $escuela->nombre,
                        'fecha_expedicion' => $fecha,
                        'numero_curso' => $numeroCurso,
                        'estado' => $estado,
                        'observaciones' => $observaciones,
                        'employee_ficha_profile_id' => $profileId,
                        'updated_by' => $userId,
                    ];

                    if ($existing !== null) {
                        // No tocar document_* en update por import.
                        $existing->update($payload);
                        $stats['updated']++;
                    } else {
                        EmployeeCurso::query()->create([
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
     * @return array<string, int>
     */
    private function buildTipoCursoIndex(): array
    {
        $index = [];

        foreach (CursoTipo::query()->get(['id', 'tipo_curso']) as $tipo) {
            $index[mb_strtolower(trim((string) $tipo->tipo_curso))] = (int) $tipo->id;
        }

        return $index;
    }

    /**
     * @return array<string, CursoEscuela>
     */
    private function buildEscuelaCodigoIndex(): array
    {
        $index = [];

        foreach (CursoEscuela::query()->active()->get(['id', 'codigo', 'nit', 'nombre', 'is_active']) as $escuela) {
            $key = CursoEscuela::normalizeCodigo((string) $escuela->codigo);
            if ($key !== '') {
                $index[$key] = $escuela;
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
