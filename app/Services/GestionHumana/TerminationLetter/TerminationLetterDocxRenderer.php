<?php

namespace App\Services\GestionHumana\TerminationLetter;

use PhpOffice\PhpWord\TemplateProcessor;
use RuntimeException;
use ZipArchive;

class TerminationLetterDocxRenderer
{
    private const T_PATTERN = '/<w:t((?:\s[^>]*)?)>(.*?)<\/w:t>/s';

    /**
     * @param  array<string, string>  $variables
     */
    public function render(string $templateAbsolutePath, array $variables, string $outputAbsolutePath): void
    {
        if (! is_file($templateAbsolutePath)) {
            throw new RuntimeException('No se encontro la plantilla Word.');
        }

        $mergedPath = $this->mergeSplitRuns($templateAbsolutePath);

        try {
            $processor = new TemplateProcessor($mergedPath);

            foreach ($variables as $key => $value) {
                $processor->setValue($key, $value);
            }

            $dir = dirname($outputAbsolutePath);
            if (! is_dir($dir) && ! mkdir($dir, 0777, true) && ! is_dir($dir)) {
                throw new RuntimeException('No se pudo crear el directorio de salida.');
            }

            $processor->saveAs($outputAbsolutePath);
        } finally {
            @unlink($mergedPath);
        }
    }

    private function mergeSplitRuns(string $templatePath): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'docx-merge-');

        if ($tmpFile === false) {
            throw new RuntimeException('No se pudo crear archivo temporal.');
        }

        if (! @copy($templatePath, $tmpFile)) {
            @unlink($tmpFile);
            throw new RuntimeException('No se pudo copiar plantilla a archivo temporal.');
        }

        $zip = new ZipArchive;
        $result = $zip->open($tmpFile);

        if ($result !== true) {
            @unlink($tmpFile);
            throw new RuntimeException('No se pudo abrir plantilla como zip.');
        }

        $xmlTargets = [
            'word/document.xml',
            'word/footnotes.xml',
            'word/endnotes.xml',
        ];

        for ($i = 1; $i <= 9; $i++) {
            $xmlTargets[] = 'word/header'.$i.'.xml';
            $xmlTargets[] = 'word/footer'.$i.'.xml';
        }

        foreach ($xmlTargets as $xmlName) {
            if ($zip->locateName($xmlName) === false) {
                continue;
            }

            $xmlContent = $zip->getFromName($xmlName);

            if ($xmlContent === false || $xmlContent === '') {
                continue;
            }

            $merged = $this->mergeSplitRunsInXml($xmlContent);

            if ($merged !== $xmlContent) {
                $zip->deleteName($xmlName);
                $zip->addFromString($xmlName, $merged);
            }
        }

        $zip->close();

        return $tmpFile;
    }

    private function mergeSplitRunsInXml(string $xmlContent): string
    {
        $changed = false;

        $result = preg_replace_callback(
            '/<w:p(?:\s[^>]*)?>.*?<\/w:p>/s',
            function (array $m) use (&$changed): string {
                $processed = $this->processParagraph($m[0]);

                if ($processed !== $m[0]) {
                    $changed = true;
                }

                return $processed;
            },
            $xmlContent,
        );

        if (! $changed) {
            return $xmlContent;
        }

        return $result ?? $xmlContent;
    }

    private function processParagraph(string $paragraphXml): string
    {
        if (preg_match_all(self::T_PATTERN, $paragraphXml, $tMatches) === 0) {
            return $paragraphXml;
        }

        $fullText = '';
        foreach ($tMatches[2] as $tContent) {
            $fullText .= html_entity_decode($tContent, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        }

        if (! preg_match('/\$\{/', $fullText)) {
            return $paragraphXml;
        }

        $hasSplit = false;
        $currentPlaceholder = '';

        foreach ($tMatches[2] as $tContent) {
            $decoded = html_entity_decode($tContent, ENT_XML1 | ENT_QUOTES, 'UTF-8');

            if (preg_match('/\$\{([^}]*)$/', $decoded, $openMatch)) {
                $currentPlaceholder = $openMatch[0];
            } elseif ($currentPlaceholder !== '' && preg_match('/^[^$]*\}/', $decoded, $closeMatch)) {
                $currentPlaceholder .= $closeMatch[0];
                $hasSplit = true;
                $currentPlaceholder = '';
            } elseif ($currentPlaceholder !== '') {
                $currentPlaceholder .= $decoded;
            }
        }

        if (! $hasSplit) {
            return $paragraphXml;
        }

        $escapedText = htmlspecialchars($fullText, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $first = true;

        return preg_replace_callback(
            self::T_PATTERN,
            function (array $m) use (&$first, $escapedText): string {
                $rawAttrs = $m[1] ?? '';
                $attrs = preg_replace('/\s+xml:space\s*=\s*["\'][^"\']*["\']/', '', $rawAttrs);
                $attrs = $attrs !== '' ? ' '.ltrim($attrs) : '';

                if ($first) {
                    $first = false;

                    return '<w:t'.$attrs.' xml:space="preserve">'.$escapedText.'</w:t>';
                }

                return '<w:t'.$attrs.'></w:t>';
            },
            $paragraphXml,
        ) ?? $paragraphXml;
    }
}
