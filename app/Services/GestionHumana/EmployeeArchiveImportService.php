<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeFichaProfile;
use App\Models\PersonalRequisitionFichaEntry;
use App\Support\ImportFailureRow;
use App\Support\SpreadsheetCellReader;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeArchiveImportService
{
    /**
     * @return array{updated: int, skipped: int, empty_rows: int, errors: list<string>, failures: list<array<string, mixed>>}
     */
    public function import(string $path): array
    {
        if (! is_readable($path)) {
            throw new \InvalidArgumentException('No se puede leer el archivo: '.$path);
        }

        $stats = ['updated' => 0, 'skipped' => 0, 'empty_rows' => 0, 'errors' => [], 'failures' => []];
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $headers = $this->readHeaders($sheet);

        if (! array_key_exists('cedula', $headers)) {
            throw new \RuntimeException('El archivo debe incluir la columna "cedula" en la fila 1.');
        }

        $maxRow = (int) $sheet->getHighestRow();

        for ($row = 3; $row <= $maxRow; $row++) {
            $data = $this->readRow($sheet, $row, $headers);
            $cedula = trim((string) ($data['cedula'] ?? ''));

            if ($cedula === '') {
                $stats['empty_rows']++;
                $stats['failures'][] = ImportFailureRow::make(
                    $row,
                    null,
                    'Cedula',
                    ImportFailureRow::SEVERITY_EMPTY,
                    'Fila sin cedula (ignorada).',
                    $this->filterArchiveRow($data),
                );

                continue;
            }

            $shelf = $this->nullableString($data['estantes'] ?? null);
            $box = $this->nullableString($data['cajas'] ?? null);

            if ($shelf === null && $box === null) {
                $stats['skipped']++;
                $stats['failures'][] = ImportFailureRow::make(
                    $row,
                    $cedula,
                    'Cedula',
                    ImportFailureRow::SEVERITY_SKIPPED,
                    'Sin datos de estantes ni cajas (fila omitida).',
                    $this->filterArchiveRow($data),
                );

                continue;
            }

            try {
                DB::transaction(function () use ($cedula, $shelf, $box, &$stats): void {
                    $entry = $this->resolveInFichaEntry($cedula);

                    if ($entry === null) {
                        throw new \RuntimeException('No hay empleado en ficha con cedula '.$cedula.'.');
                    }

                    $profile = $entry->profile;

                    if ($profile === null) {
                        $profile = new EmployeeFichaProfile([
                            'personal_requisition_ficha_entry_id' => $entry->id,
                            'document_number' => $cedula,
                            'full_name' => $entry->hired_full_name,
                            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
                        ]);
                    }

                    if ($shelf !== null) {
                        $profile->archive_shelf = $shelf;
                    }

                    if ($box !== null) {
                        $profile->archive_box = $box;
                    }

                    $profile->save();
                    $stats['updated']++;
                });
            } catch (\Throwable $e) {
                $failure = ImportFailureRow::make(
                    $row,
                    $cedula,
                    'Cedula',
                    ImportFailureRow::SEVERITY_ERROR,
                    $e->getMessage(),
                    $this->filterArchiveRow($data),
                );
                $stats['failures'][] = $failure;
                $stats['errors'][] = ImportFailureRow::message($failure);
                $stats['skipped']++;
            }
        }

        return $stats;
    }

    private function resolveInFichaEntry(string $cedula): ?PersonalRequisitionFichaEntry
    {
        $profile = EmployeeFichaProfile::query()
            ->where('document_number', $cedula)
            ->first();

        if ($profile?->personal_requisition_ficha_entry_id !== null) {
            $entry = PersonalRequisitionFichaEntry::query()
                ->inFicha()
                ->find($profile->personal_requisition_ficha_entry_id);

            if ($entry !== null) {
                return $entry;
            }
        }

        return PersonalRequisitionFichaEntry::query()
            ->inFicha()
            ->where('hired_document', $cedula)
            ->first();
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
     * @return array<string, mixed>
     */
    private function filterArchiveRow(array $data): array
    {
        $keys = ['cedula', 'nombre', 'estantes', 'cajas'];

        return array_intersect_key($data, array_flip($keys));
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : mb_substr($value, 0, 100);
    }
}
