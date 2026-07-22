<?php

namespace App\Services\Indicadores\ManagementReport;

use RuntimeException;

class ManagementReportChartInjector
{
    public function __construct(
        private readonly ManagementReportPptxArchive $archive,
    ) {
    }

    public function prototypeDirectory(): string
    {
        return storage_path('app/templates/operaciones/chart-prototype');
    }

    /**
     * @return array{0: string, 1: string, 2: string} Slide XML, chart XML and updated [Content_Types].xml.
     */
    public function ensureChartInWorkDir(
        string $workDir,
        int $chartNumber,
        string $slidePath,
        string $slideXml,
        string $contentTypesXml,
    ): array {
        $chartPath = 'ppt/charts/chart'.$chartNumber.'.xml';

        if ($this->archive->exists($workDir, $chartPath)) {
            return [$slideXml, $this->archive->read($workDir, $chartPath), $contentTypesXml];
        }

        $prototypeDir = $this->prototypeDirectory();
        $required = ['chart.xml', 'style.xml', 'colors.xml', 'chart.rels.xml', 'graphic-frame.xml'];

        foreach ($required as $file) {
            if (! is_file($prototypeDir.DIRECTORY_SEPARATOR.$file)) {
                throw new RuntimeException('Falta el prototipo de grafico: '.$file.'. Ejecute python tools/extract_chart_prototype.py');
            }
        }

        $chartXml = (string) file_get_contents($prototypeDir.'/chart.xml');
        $stylePath = 'ppt/charts/style'.$chartNumber.'.xml';
        $colorsPath = 'ppt/charts/colors'.$chartNumber.'.xml';
        $chartRelsPath = 'ppt/charts/_rels/chart'.$chartNumber.'.xml.rels';

        $this->archive->write($workDir, $chartPath, $chartXml);
        $this->archive->write($workDir, $stylePath, (string) file_get_contents($prototypeDir.'/style.xml'));
        $this->archive->write($workDir, $colorsPath, (string) file_get_contents($prototypeDir.'/colors.xml'));

        $rels = str_replace(
            ['style1.xml', 'colors1.xml'],
            ['style'.$chartNumber.'.xml', 'colors'.$chartNumber.'.xml'],
            (string) file_get_contents($prototypeDir.'/chart.rels.xml')
        );
        $this->archive->write($workDir, $chartRelsPath, $rels);

        $contentTypesXml = $this->appendChartContentTypes($contentTypesXml, $chartNumber);
        $slideXml = $this->injectGraphicFrame($workDir, $slidePath, $chartNumber, $slideXml);

        return [$slideXml, $chartXml, $contentTypesXml];
    }

    private function injectGraphicFrame(string $workDir, string $slidePath, int $chartNumber, string $slideXml): string
    {
        if (str_contains($slideXml, 'graphicFrame')) {
            return $slideXml;
        }

        $slideRelsPath = str_replace('ppt/slides/', 'ppt/slides/_rels/', $slidePath).'.rels';
        $relsXml = $this->archive->read($workDir, $slideRelsPath);
        $chartTarget = '../charts/chart'.$chartNumber.'.xml';
        $chartRelId = $this->chartRelationshipId($relsXml, $chartNumber)
            ?? $this->nextRelationshipId($relsXml);

        $frame = strtr((string) file_get_contents($this->prototypeDirectory().'/graphic-frame.xml'), [
            '{{FRAME_ID}}' => (string) (100 + $chartNumber),
            '{{FRAME_NAME}}' => 'Grafico FT-OP '.$chartNumber,
            '{{CHART_REL_ID}}' => $chartRelId,
        ]);

        $slideXml = str_replace('</p:spTree>', $frame.'</p:spTree>', $slideXml);

        if (! str_contains($relsXml, $chartTarget)) {
            $chartRel = '<Relationship Id="'.$chartRelId.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/chart" Target="'.$chartTarget.'"/>';
            $relsXml = str_replace('</Relationships>', $chartRel.'</Relationships>', $relsXml);
            $this->archive->write($workDir, $slideRelsPath, $relsXml);
        }

        return $slideXml;
    }

    private function chartRelationshipId(string $relsXml, int $chartNumber): ?string
    {
        $pattern = '/<Relationship Id="(rId\d+)"[^>]+Target="\.\.\/charts\/chart'.$chartNumber.'\.xml"/';

        if (preg_match($pattern, $relsXml, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    private function nextRelationshipId(string $relsXml): string
    {
        preg_match_all('/\bId="rId(\d+)"/', $relsXml, $matches);
        $max = 0;
        foreach ($matches[1] ?? [] as $id) {
            $max = max($max, (int) $id);
        }

        return 'rId'.($max + 1);
    }

    private function appendChartContentTypes(string $contentTypesXml, int $chartNumber): string
    {
        $overrides = [
            '/ppt/charts/chart'.$chartNumber.'.xml' => 'application/vnd.openxmlformats-officedocument.drawingml.chart+xml',
            '/ppt/charts/style'.$chartNumber.'.xml' => 'application/vnd.openxmlformats-officedocument.drawingml.chartStyle+xml',
            '/ppt/charts/colors'.$chartNumber.'.xml' => 'application/vnd.openxmlformats-officedocument.drawingml.chartColorStyle+xml',
        ];

        foreach ($overrides as $partName => $contentType) {
            if (str_contains($contentTypesXml, 'PartName="'.$partName.'"')) {
                continue;
            }

            $override = '<Override PartName="'.$partName.'" ContentType="'.$contentType.'"/>';
            $contentTypesXml = str_replace('</Types>', $override.'</Types>', $contentTypesXml);
        }

        return $contentTypesXml;
    }
}
