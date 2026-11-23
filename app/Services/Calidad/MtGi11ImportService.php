<?php

namespace App\Services\Calidad;

use App\Exports\MtGi11ImportTemplateExport;
use App\Models\Calidad\MtGi11CargoResponsable;
use App\Models\Calidad\MtGi11Catalog;
use App\Models\Calidad\MtGi11Estado;
use App\Models\Calidad\MtGi11Proceso;
use App\Models\Calidad\MtGi11Solicitud;
use App\Models\Calidad\MtGi11TipoDocumento;
use App\Models\Calidad\MtGi11TipoSolicitud;
use App\Support\ImportFailureRow;
use App\Support\UppercaseFormText;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Alta masiva de solicitudes MT-GI-11 desde Excel/CSV.
 *
 * Reglas acordadas:
 * - El consecutivo NO se valida como único: varias versiones del mismo documento
 *   pueden repetirlo dentro del archivo y contra la base.
 * - Los catálogos se resuelven por nombre (sin distinguir mayúsculas).
 * - Las filas con error se reportan y no se guardan; el resto se importa.
 * - Todo texto libre se normaliza a MAYÚSCULAS antes de persistir.
 */
final class MtGi11ImportService
{
    /**
     * @return array{
     *     imported: int,
     *     skipped: int,
     *     empty_rows: int,
     *     errors: list<string>,
     *     failures: list<array<string, mixed>>
     * }
     */
    public function import(string $path, int $userId): array
    {
        if (! is_readable($path)) {
            throw new \InvalidArgumentException('No se puede leer el archivo: '.$path);
        }

        $stats = [
            'imported' => 0,
            'skipped' => 0,
            'empty_rows' => 0,
            'errors' => [],
            'failures' => [],
        ];

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $headers = $this->readHeaders($sheet);

        if ($headers === []) {
            throw new \RuntimeException('El archivo no tiene encabezados válidos en la fila 1.');
        }

        $required = ['proceso', 'consecutivo', 'tipo_documento', 'nombre_documento', 'tipo_solicitud', 'justificacion', 'responsable_proceso', 'cargo_responsable', 'estado'];

        foreach ($required as $key) {
            if (! array_key_exists($key, $headers)) {
                throw new \RuntimeException("Falta la columna obligatoria \"{$key}\" en la fila 1.");
            }
        }

        $lookups = $this->buildLookups();
        $estadoDefault = $this->estadoIdByCode('PENDIENTE', $lookups);
        $maxRows = (int) config('mt_gi_11.import.max_rows', 5000);
        $highestRow = min((int) $sheet->getHighestDataRow(), $maxRows + 3);

        // La plantilla trae etiquetas en la fila 2; si el archivo no las tiene,
        // los datos empiezan directamente en la fila 2.
        $startRow = $this->isLabelRow($sheet, $headers, 2) ? 3 : 2;

        for ($rowNumber = $startRow; $rowNumber <= $highestRow; $rowNumber++) {
            $raw = $this->readRow($sheet, $headers, $rowNumber);

            if ($this->isEmptyRow($raw)) {
                $stats['empty_rows']++;

                continue;
            }

            try {
                $this->importRow($raw, $headers, $lookups, $estadoDefault, $userId);
                $stats['imported']++;
            } catch (\Throwable $exception) {
                $failure = ImportFailureRow::make(
                    $rowNumber,
                    (string) ($raw['consecutivo'] ?? ''),
                    'Consecutivo',
                    ImportFailureRow::SEVERITY_ERROR,
                    $exception->getMessage(),
                    $raw,
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
    private function readHeaders(Worksheet $sheet): array
    {
        $headers = [];
        $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        for ($col = 1; $col <= $highestColumn; $col++) {
            $value = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($col).'1')->getValue());

            if ($value !== '') {
                $headers[mb_strtolower($value)] = $col;
            }
        }

        return $headers;
    }

    /**
     * Detecta la fila de etiquetas de la plantilla (fila 2) para no importarla
     * como si fueran datos.
     *
     * @param  array<string, int>  $headers
     */
    private function isLabelRow(Worksheet $sheet, array $headers, int $rowNumber): bool
    {
        $labels = array_map('mb_strtolower', array_values(MtGi11ImportTemplateExport::columns()));
        $matches = 0;
        $filled = 0;

        foreach ($headers as $key => $col) {
            $value = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($col).$rowNumber)->getValue());

            if ($value === '') {
                continue;
            }

            $filled++;
            if (in_array(mb_strtolower($value), $labels, true)) {
                $matches++;
            }
        }

        // Etiquetas conocidas en su mayoría y sin fecha válida: es la fila guía.
        return $filled > 0 && $matches >= max(1, (int) floor($filled * 0.5));
    }

    /**
     * @param  array<string, int>  $headers
     * @return array<string, string>
     */
    private function readRow(Worksheet $sheet, array $headers, int $rowNumber): array
    {
        $row = [];

        foreach ($headers as $key => $col) {
            $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($col).$rowNumber);
            $value = $cell->getValue();

            if ($value instanceof \DateTimeInterface) {
                $row[$key] = $value->format('Y-m-d');

                continue;
            }

            // Fechas capturadas como número de serie de Excel.
            if (in_array($key, ['fecha_solicitud', 'fecha_estandarizacion'], true) && is_numeric($value)) {
                $row[$key] = ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');

                continue;
            }

            $row[$key] = trim((string) $value);
        }

        return $row;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, string>  $row
     * @param  array<string, int>  $headers
     * @param  array<string, array<string, int>>  $lookups
     *
     * @throws \InvalidArgumentException
     */
    private function importRow(array $row, array $headers, array $lookups, ?int $estadoDefault, int $userId): void
    {
        // La fecha de solicitud es automática (hoy). El archivo puede traerla
        // para cargas históricas; si viene vacía se usa la fecha del día.
        $fechaSolicitud = $this->parseDate($row['fecha_solicitud'] ?? '') ?? now()->toDateString();

        $consecutivo = trim((string) ($row['consecutivo'] ?? ''));
        if ($consecutivo === '') {
            throw new \InvalidArgumentException('El consecutivo es obligatorio.');
        }

        $procesoId = $this->lookupId($lookups['procesos'], $row['proceso'] ?? '', 'Proceso');
        $tipoDocumentoId = $this->lookupId($lookups['tipos_documento'], $row['tipo_documento'] ?? '', 'Tipo de documento');
        $tipoSolicitudId = $this->lookupId($lookups['tipos_solicitud'], $row['tipo_solicitud'] ?? '', 'Tipo de solicitud');
        $cargoId = $this->lookupId($lookups['cargos_responsable'], $row['cargo_responsable'] ?? '', 'Cargo del responsable');

        $estadoRaw = trim((string) ($row['estado'] ?? ''));
        $estadoId = $estadoRaw === ''
            ? $estadoDefault
            : $this->lookupId($lookups['estados'], $estadoRaw, 'Estado');

        if ($estadoId === null) {
            throw new \InvalidArgumentException('No hay estado PENDIENTE en el catálogo; indique el estado en la fila.');
        }

        // Con estado GESTIONADO la solicitud queda cerrada: la misma regla del
        // formulario exige también los campos de Estandarización.
        if (MtGi11Estado::isGestionadoId($estadoId)) {
            $this->assertGestionadoCompleto($row);
        }

        $nombreDocumento = trim((string) ($row['nombre_documento'] ?? ''));
        if ($nombreDocumento === '') {
            throw new \InvalidArgumentException('El nombre del documento es obligatorio.');
        }

        $justificacion = trim((string) ($row['justificacion'] ?? ''));
        if ($justificacion === '') {
            throw new \InvalidArgumentException('La justificación es obligatoria.');
        }

        if (mb_strlen($justificacion) > 2000) {
            throw new \InvalidArgumentException('La justificación supera 2000 caracteres.');
        }

        $responsable = trim((string) ($row['responsable_proceso'] ?? ''));
        if ($responsable === '') {
            throw new \InvalidArgumentException('El responsable del proceso es obligatorio.');
        }

        $fechaEstandarizacion = $this->parseDate($row['fecha_estandarizacion'] ?? '');
        if ($fechaEstandarizacion !== null && $fechaEstandarizacion < $fechaSolicitud) {
            throw new \InvalidArgumentException('La fecha de estandarización es anterior a la fecha de solicitud.');
        }

        $versionActualizada = null;
        $versionRaw = trim((string) ($row['version_actualizada'] ?? ''));
        if ($versionRaw !== '') {
            if (! is_numeric($versionRaw) || (int) $versionRaw < 0) {
                throw new \InvalidArgumentException('La versión actualizada debe ser un número entero.');
            }
            $versionActualizada = (int) $versionRaw;
        }

        $payload = UppercaseFormText::apply([
            'fecha_solicitud' => $fechaSolicitud,
            'proceso_id' => $procesoId,
            'consecutivo' => $consecutivo,
            'tipo_documento_id' => $tipoDocumentoId,
            'nombre_documento' => $nombreDocumento,
            'tipo_solicitud_id' => $tipoSolicitudId,
            'codigo_actual' => trim((string) ($row['codigo_actual'] ?? '')) ?: null,
            'version_actual' => trim((string) ($row['version_actual'] ?? '')) ?: null,
            'justificacion' => $justificacion,
            'responsable_proceso' => $responsable,
            'cargo_responsable_id' => $cargoId,
            'codigo_estandarizacion' => trim((string) ($row['codigo_estandarizacion'] ?? '')) ?: null,
            'fecha_estandarizacion' => $fechaEstandarizacion,
            'version_actualizada' => $versionActualizada,
            'estado_id' => $estadoId,
        ]);

        DB::transaction(function () use ($payload, $userId): void {
            MtGi11Solicitud::query()->create($payload + [
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        });
    }

    /**
     * Mapas nombre-normalizado => id para resolver catálogos por texto.
     *
     * @return array<string, array<string, int>>
     */
    private function buildLookups(): array
    {
        return [
            'procesos' => $this->nameMap(MtGi11Proceso::class),
            'tipos_documento' => $this->nameMap(MtGi11TipoDocumento::class),
            'tipos_solicitud' => $this->nameMap(MtGi11TipoSolicitud::class),
            'cargos_responsable' => $this->nameMap(MtGi11CargoResponsable::class),
            'estados' => $this->nameMap(MtGi11Estado::class),
        ];
    }

    /**
     * @param  class-string<MtGi11Catalog>  $class
     * @return array<string, int>
     */
    private function nameMap(string $class): array
    {
        return $class::query()
            ->get(['id', 'name'])
            ->mapWithKeys(fn ($item): array => [$this->normalize((string) $item->name) => (int) $item->id])
            ->all();
    }

    /**
     * @param  array<string, int>  $map
     *
     * @throws \InvalidArgumentException
     */
    private function lookupId(array $map, string $raw, string $label): int
    {
        $key = $this->normalize($raw);

        if ($key === '' || ! isset($map[$key])) {
            throw new \InvalidArgumentException(trim($label) === '' ? 'Valor de catálogo inválido.' : "{$label} no existe en el catálogo: \"{$raw}\".");
        }

        return $map[$key];
    }

    /**
     * @param  array<string, array<string, int>>  $lookups
     */
    private function estadoIdByCode(string $code, array $lookups): ?int
    {
        $estado = MtGi11Estado::query()->where('code', $code)->value('id');

        return $estado !== null ? (int) $estado : null;
    }

    private function normalize(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? $value;

        return mb_strtoupper($value, 'UTF-8');
    }

    /**
     * Con estado GESTIONADO todos los campos de la solicitud son obligatorios:
     * en el import se valida antes de crear la fila para no guardarla a medias.
     *
     * @param  array<string, string>  $row
     */
    private function assertGestionadoCompleto(array $row): void
    {
        $required = [
            'consecutivo' => 'El consecutivo',
            'codigo_actual' => 'El código actual',
            'version_actual' => 'La versión actual',
            'codigo_estandarizacion' => 'El código de estandarización',
            'fecha_estandarizacion' => 'La fecha de estandarización',
            'version_actualizada' => 'La versión actualizada',
        ];

        $faltantes = [];

        foreach ($required as $key => $label) {
            if (trim((string) ($row[$key] ?? '')) === '') {
                $faltantes[] = $label;
            }
        }

        if ($faltantes !== []) {
            throw new \InvalidArgumentException(
                'Con estado GESTIONADO son obligatorios: '.implode(', ', $faltantes).'.'
            );
        }
    }

    private function parseDate(string $value): ?string

    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
