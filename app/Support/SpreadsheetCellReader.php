<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SpreadsheetCellReader
{
    public static function cell(Worksheet $sheet, int $columnIndex, int $row): Cell
    {
        return $sheet->getCell(Coordinate::stringFromColumnIndex($columnIndex).$row);
    }

    public static function value(Worksheet $sheet, int $columnIndex, int $row): mixed
    {
        return self::normalize(self::cell($sheet, $columnIndex, $row)->getCalculatedValue());
    }

    public static function rawValue(Worksheet $sheet, int $columnIndex, int $row): mixed
    {
        return self::normalize(self::cell($sheet, $columnIndex, $row)->getValue());
    }

    /**
     * Valor de celda como texto (headers y campos string).
     */
    public static function stringValue(Worksheet $sheet, int $columnIndex, int $row): string
    {
        $value = self::rawValue($sheet, $columnIndex, $row);

        if ($value === null || $value === '') {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value)) {
            return trim((string) $value);
        }

        return '';
    }

    private static function normalize(mixed $value): mixed
    {
        if ($value instanceof RichText) {
            return $value->getPlainText();
        }

        return $value;
    }
}
