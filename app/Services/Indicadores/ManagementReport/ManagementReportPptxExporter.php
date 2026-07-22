<?php

namespace App\Services\Indicadores\ManagementReport;

use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManagementReportPptxExporter
{
    public function __construct(
        private readonly ManagementReportChartUpdater $chartUpdater,
        private readonly ManagementReportChartInjector $chartInjector,
        private readonly ManagementReportChartSanitizer $chartSanitizer,
        private readonly ManagementReportPptxArchive $archive,
    ) {
    }

    public function downloadResponse(array $report): BinaryFileResponse
    {
        $templatePath = storage_path('app/'.config('indicators.management_report.template'));

        if (! is_file($templatePath)) {
            throw new RuntimeException('No se encontro la plantilla FO-GI-39 en storage.');
        }

        $workDir = $this->archive->extract($templatePath);

        try {
            $cover = config('indicators.management_report.cover.placeholders', []);
            $this->replaceInWorkDir($workDir, 'ppt/slides/slide1.xml', [
                $cover['report_title'] ?? '{{REPORT_TITLE}}' => (string) $report['report_title'],
                $cover['month_name'] ?? '{{MONTH_NAME}}' => strtoupper((string) $report['month_name']),
                $cover['year'] ?? '{{YEAR}}' => (string) $report['year'],
            ]);

            $contentTypesXml = $this->archive->read($workDir, '[Content_Types].xml');

            foreach ($report['indicators'] as $indicator) {
                $token = str_replace('-', '_', $indicator['code']);
                $slidePath = 'ppt/slides/slide'.(int) $indicator['slide'].'.xml';
                $chartNumber = (int) $indicator['chart'];
                $chartPath = 'ppt/charts/chart'.$chartNumber.'.xml';

                $slideXml = strtr($this->archive->read($workDir, $slidePath), [
                    '{{INDICATOR_TITLE_'.$token.'}}' => (string) $indicator['title'],
                    '{{INDICATOR_NARRATIVE_'.$token.'}}' => (string) $indicator['narrative'],
                ]);

                [$slideXml, $chartXml, $contentTypesXml] = $this->chartInjector->ensureChartInWorkDir(
                    $workDir,
                    $chartNumber,
                    $slidePath,
                    $slideXml,
                    $contentTypesXml
                );

                $updatedChart = $this->chartSanitizer->sanitize(
                    $this->chartUpdater->apply($chartXml, $indicator['chart_series'])
                );

                $this->archive->write($workDir, $slidePath, $slideXml);
                $this->archive->write($workDir, $chartPath, $updatedChart);
            }

            $this->archive->write($workDir, '[Content_Types].xml', $contentTypesXml);
            $outputPath = $this->archive->pack($workDir);
        } finally {
            $this->archive->deleteDirectory($workDir);
        }

        $filename = sprintf(
            'informe-gestion-operaciones-%d-%02d.pptx',
            (int) $report['year'],
            (int) $report['month']
        );

        return response()->download(
            $outputPath,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation']
        )->deleteFileAfterSend(true);
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function replaceInWorkDir(string $workDir, string $path, array $replacements): void
    {
        $xml = strtr($this->archive->read($workDir, $path), $replacements);
        $this->archive->write($workDir, $path, $xml);
    }
}
