<?php

namespace App\Services\Indicadores\ManagementReport;

class ManagementReportChartSanitizer
{
    public function sanitize(string $chartXml): string
    {
        $chartXml = preg_replace('/<c:externalData\b[^>]*\/>/s', '', $chartXml) ?? $chartXml;
        $chartXml = preg_replace('/<c:externalData\b.*?<\/c:externalData>/s', '', $chartXml) ?? $chartXml;
        $chartXml = preg_replace('/<c:f>.*?<\/c:f>/s', '', $chartXml) ?? $chartXml;
        $chartXml = preg_replace('/<[^>\s]+:formulaRef\b.*?<\/[^>\s]+:formulaRef>/s', '', $chartXml) ?? $chartXml;
        $chartXml = preg_replace('/<c:extLst>.*?formulaRef.*?<\/c:extLst>/s', '', $chartXml) ?? $chartXml;
        $chartXml = preg_replace('/<[^>\s]+:filteredBarSeries\b.*?<\/[^>\s]+:filteredBarSeries>/s', '', $chartXml) ?? $chartXml;
        $chartXml = preg_replace('/<c:ext uri="\{02D57815-91ED-43cb-92C2-25804820EDAC\}">.*?<\/c:ext>/s', '', $chartXml) ?? $chartXml;
        $chartXml = preg_replace('/<[^>\s]+:AlternateContent\b.*?<[^>\s]+:Fallback>(.*?)<\/[^>\s]+:Fallback>.*?<\/[^>\s]+:AlternateContent>/s', '$1', $chartXml) ?? $chartXml;
        $chartXml = preg_replace('/<c:axId val="0"\s*\/>/s', '', $chartXml) ?? $chartXml;
        $chartXml = $this->removeEmptyExtensionLists($chartXml);

        return $chartXml;
    }

    private function removeEmptyExtensionLists(string $chartXml): string
    {
        do {
            $before = $chartXml;
            $chartXml = preg_replace('/<c:extLst>\s*<\/c:extLst>/s', '', $chartXml) ?? $chartXml;
        } while ($before !== $chartXml);

        return $chartXml;
    }
}
