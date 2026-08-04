<?php

namespace App\Services\Indicadores\ManagementReport;

class ManagementReportPptxTextFormatter
{
    public function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
