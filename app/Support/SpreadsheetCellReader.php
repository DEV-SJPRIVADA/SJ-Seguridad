<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SpreadsheetCellReader
{
    public static function cell(Worksheet $sheet, int $columnIndex, int $row): Cell
    {
        return $sheet->getCell(Coordinate::stringFromColumnIndex($columnIndex).$row);
    }

    public static function value(Worksheet $sheet, int $columnIndex, int $row): mixed
    {
        return self::cell($sheet, $columnIndex, $row)->getCalculatedValue();
    }

    public static function rawValue(Worksheet $sheet, int $columnIndex, int $row): mixed
    {
        return self::cell($sheet, $columnIndex, $row)->getValue();
    }
}
