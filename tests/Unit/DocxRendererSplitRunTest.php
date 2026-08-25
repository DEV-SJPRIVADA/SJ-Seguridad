<?php

namespace Tests\Unit;

use App\Services\GestionHumana\TerminationLetter\TerminationLetterDocxRenderer;
use Tests\TestCase;
use ZipArchive;

class DocxRendererSplitRunTest extends TestCase
{
    public function test_replaces_placeholder_in_single_run(): void
    {
        $templatePath = $this->createDocxFromXml($this->singleRunXml('${NOMBRE}'));
        $outputPath = sys_get_temp_dir().'/docx-test-'.bin2hex(random_bytes(8)).'.docx';

        $renderer = new TerminationLetterDocxRenderer;
        $renderer->render($templatePath, ['NOMBRE' => 'Juan Perez'], $outputPath);

        $text = $this->extractDocumentText($outputPath);
        $this->assertStringContainsString('Juan Perez', $text);
        $this->assertStringNotContainsString('${NOMBRE}', $text);

        @unlink($templatePath);
        @unlink($outputPath);
    }

    public function test_replaces_placeholder_fragmented_across_multiple_runs(): void
    {
        $templatePath = $this->createDocxFromXml($this->splitRunXml('${NOMBRE}', 3));
        $outputPath = sys_get_temp_dir().'/docx-test-'.bin2hex(random_bytes(8)).'.docx';

        $renderer = new TerminationLetterDocxRenderer;
        $renderer->render($templatePath, ['NOMBRE' => 'Juan Perez'], $outputPath);

        $text = $this->extractDocumentText($outputPath);
        $this->assertStringContainsString('Juan Perez', $text);
        $this->assertStringNotContainsString('${NOMBRE}', $text);

        @unlink($templatePath);
        @unlink($outputPath);
    }

    public function test_replaces_multiple_placeholders_some_fragmented(): void
    {
        $xml = $this->splitRunXml('${NOMBRE}', 2).'  '.$this->splitRunXml('${CEDULA}', 2);
        $templatePath = $this->createDocxFromXml($xml);
        $outputPath = sys_get_temp_dir().'/docx-test-'.bin2hex(random_bytes(8)).'.docx';

        $renderer = new TerminationLetterDocxRenderer;
        $renderer->render($templatePath, [
            'NOMBRE' => 'Juan Perez',
            'CEDULA' => '1234567890',
        ], $outputPath);

        $text = $this->extractDocumentText($outputPath);
        $this->assertStringContainsString('Juan Perez', $text);
        $this->assertStringContainsString('1234567890', $text);
        $this->assertStringNotContainsString('${NOMBRE}', $text);
        $this->assertStringNotContainsString('${CEDULA}', $text);

        @unlink($templatePath);
        @unlink($outputPath);
    }

    public function test_does_not_match_w_tab(): void
    {
        $xml = '<w:r><w:t>Text before </w:t></w:r><w:r><w:tab/></w:r><w:r><w:t xml:space="preserve">[${NOMBRE}]</w:t></w:r>';
        $templatePath = $this->createDocxFromXml($xml);
        $outputPath = sys_get_temp_dir().'/docx-test-'.bin2hex(random_bytes(8)).'.docx';

        $renderer = new TerminationLetterDocxRenderer;
        $renderer->render($templatePath, ['NOMBRE' => 'Juan Perez'], $outputPath);

        $text = $this->extractDocumentText($outputPath);
        $this->assertStringContainsString('Juan Perez', $text);
        $this->assertStringNotContainsString('${NOMBRE}', $text);

        @unlink($templatePath);
        @unlink($outputPath);
    }

    private function singleRunXml(string $content): string
    {
        return '<w:r><w:t xml:space="preserve">'.$content.'</w:t></w:r>';
    }

    private function splitRunXml(string $placeholder, int $parts): string
    {
        $len = mb_strlen($placeholder);
        $partSize = (int) ceil($len / $parts);
        $xml = '';

        for ($i = 0; $i < $parts; $i++) {
            $part = mb_substr($placeholder, $i * $partSize, $partSize);
            $xml .= '<w:r><w:t xml:space="preserve">'.$part.'</w:t></w:r>';
        }

        return $xml;
    }

    private function createDocxFromXml(string $runsXml): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'.
            '<w:body>'.
            '<w:p><w:pPr/>'.$runsXml.'</w:p>'.
            '<w:sectPr>'.
            '<w:pgSz w:orient="portrait" w:w="11906" w:h="16838"/>'.
            '<w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="720" w:footer="720" w:gutter="0"/>'.
            '<w:cols w:num="1" w:space="720"/>'.
            '</w:sectPr>'.
            '</w:body>'.
            '</w:document>';

        $temp = tempnam(sys_get_temp_dir(), 'tpl-').'.docx';

        $zip = new ZipArchive;
        $zip->open($temp, ZipArchive::CREATE);
        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->relsXml());
        $zip->addFromString('word/_rels/document.xml.rels', $this->documentRelsXml());
        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        return $temp;
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'.
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'.
            '<Default Extension="xml" ContentType="application/xml"/>'.
            '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'.
            '</Types>';
    }

    private function relsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'.
            '</Relationships>';
    }

    private function documentRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.
            '</Relationships>';
    }

    private function extractDocumentText(string $docxPath): string
    {
        $zip = new ZipArchive;
        $zip->open($docxPath);

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false || $xml === '') {
            return '';
        }

        $text = '';

        if (preg_match_all('/<w:t(?:\s[^>]*)>(.*?)<\/w:t>/s', $xml, $matches)) {
            foreach ($matches[1] as $tContent) {
                $text .= html_entity_decode($tContent, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            }
        }

        return $text;
    }
}
