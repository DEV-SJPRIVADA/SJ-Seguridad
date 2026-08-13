<?php

namespace App\Services\GestionHumana\TerminationLetter;

use PhpOffice\PhpWord\TemplateProcessor;
use RuntimeException;

class TerminationLetterDocxRenderer
{
    /**
     * @param  array<string, string>  $variables
     */
    public function render(string $templateAbsolutePath, array $variables, string $outputAbsolutePath): void
    {
        if (! is_file($templateAbsolutePath)) {
            throw new RuntimeException('No se encontro la plantilla Word.');
        }

        $processor = new TemplateProcessor($templateAbsolutePath);
        $processor->setMacroChars('[', ']');

        foreach ($variables as $key => $value) {
            $processor->setValue($key, $value);
        }

        $processor->saveAs($outputAbsolutePath);
    }
}
