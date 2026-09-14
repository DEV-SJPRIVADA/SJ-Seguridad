<?php

namespace App\Services\PurchaseRequests;

use App\Support\SpreadsheetCellReader;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseRequestItemsImportService
{
    /**
     * @return array{items: list<array{cantidad: int, descripcion: string, referencia: string, utilizacion: string, ubicacion: string}>, skipped: int, warnings: list<string>}
     */
    public function parse(string $path): array
    {
        if (! is_readable($path)) {
            throw new \InvalidArgumentException('No se puede leer el archivo subido.');
        }

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $headers = $this->readHeaders($sheet);

        if ($headers === []) {
            throw new \RuntimeException('El archivo no tiene encabezados validos en la fila 1.');
        }

        foreach (array_keys(config('purchase-requests.items_import_columns', [])) as $requiredKey) {
            if (! array_key_exists($requiredKey, $headers)) {
                throw new \RuntimeException("Falta la columna obligatoria \"{$requiredKey}\" en la fila 1.");
            }
        }

        $maxRows = (int) config('purchase-requests.items_import_max_rows', 200);
        $maxRow = (int) $sheet->getHighestRow();
        $items = [];
        $skipped = 0;
        $warnings = [];

        for ($row = 3; $row <= $maxRow; $row++) {
            $data = $this->readRow($sheet, $row, $headers);

            if ($this->rowIsEmpty($data)) {
                $skipped++;

                continue;
            }

            if (count($items) >= $maxRows) {
                $warnings[] = "Se alcanzo el maximo de {$maxRows} filas; el resto se omitio.";

                break;
            }

            $cantidad = $this->parseCantidad($data['cantidad'] ?? null);

            if ($cantidad === null) {
                $warnings[] = "Fila {$row}: cantidad invalida; se uso 1.";
                $cantidad = 1;
            }

            $descripcion = $this->stringValue($data['descripcion'] ?? null);
            $referencia = $this->stringValue($data['referencia'] ?? null);
            $utilizacion = $this->stringValue($data['utilizacion'] ?? null);
            $ubicacion = $this->stringValue($data['ubicacion'] ?? null);

            if ($descripcion === '' && $referencia === '') {
                $skipped++;
                $warnings[] = "Fila {$row}: sin descripcion ni referencia (omitida).";

                continue;
            }

            $items[] = [
                'cantidad' => $cantidad,
                'descripcion' => $descripcion,
                'referencia' => $referencia,
                'utilizacion' => $utilizacion,
                'ubicacion' => $ubicacion,
            ];
        }

        if ($items === []) {
            throw new \RuntimeException('No se encontraron filas de productos para cargar (datos desde la fila 3).');
        }

        return [
            'items' => $items,
            'skipped' => $skipped,
            'warnings' => $warnings,
        ];
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

    private function parseCantidad(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return 1;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $cantidad = (int) $value;

        if ($cantidad < 1 || $cantidad > 99999) {
            return null;
        }

        return $cantidad;
    }

    private function stringValue(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }
}
