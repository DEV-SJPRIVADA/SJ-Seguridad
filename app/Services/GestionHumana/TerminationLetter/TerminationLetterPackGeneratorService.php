<?php

namespace App\Services\GestionHumana\TerminationLetter;

use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\PersonalRequisitionFichaEntry;
use App\Models\TerminationLetterDocumentTemplate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use ZipArchive;

class TerminationLetterPackGeneratorService
{
    public function __construct(
        private readonly TerminationLetterTemplateManager $templateManager,
        private readonly TerminationLetterVariableBuilder $variableBuilder,
        private readonly TerminationLetterDocxRenderer $docxRenderer,
    ) {}

    /**
     * @return array{storage_path: string, download_name: string, document_count: int}
     */
    public function generate(
        EmployeeFichaEmploymentPeriod $period,
        PersonalRequisitionFichaEntry $entry,
    ): array {
        $this->assertCanGenerate($period);

        $causeCode = (string) $period->termination_cause_code;
        $templates = $this->requiredTemplates($causeCode);
        $this->assertTemplatesReady($templates);

        $entry->loadMissing('profile');
        $variables = $this->variableBuilder->build($period, $entry, $entry->profile);

        $workDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ficha-letters-'.Str::uuid()->toString();
        if (! mkdir($workDir) && ! is_dir($workDir)) {
            throw new RuntimeException('No se pudo crear el directorio temporal.');
        }

        $generatedFiles = [];

        try {
            foreach ($templates as $index => $template) {
                $templatePath = $this->templateManager->absolutePath($template->template_path);
                $outputName = sprintf('%02d_%s.docx', $index + 1, Str::slug($template->label, '_'));
                $outputPath = $workDir.DIRECTORY_SEPARATOR.$outputName;

                $this->docxRenderer->render((string) $templatePath, $variables, $outputPath);
                $generatedFiles[] = [
                    'absolute' => $outputPath,
                    'name' => $outputName,
                ];
            }

            $zipAbsolutePath = $workDir.DIRECTORY_SEPARATOR.$this->zipFileName($entry);
            $this->createZip($generatedFiles, $zipAbsolutePath);

            $storageRelativePath = $this->persistZip($period, $zipAbsolutePath);

            return [
                'storage_path' => $storageRelativePath,
                'download_name' => basename($zipAbsolutePath),
                'document_count' => count($generatedFiles),
            ];
        } finally {
            $this->deleteDirectory($workDir);
        }
    }

    public function assertCanGenerate(EmployeeFichaEmploymentPeriod $period): void
    {
        if ($period->status !== EmployeeFichaEmploymentPeriod::STATUS_CERRADO) {
            throw ValidationException::withMessages([
                'period' => 'Solo se pueden generar cartas para vinculos cerrados.',
            ]);
        }

        $causeCode = (string) $period->termination_cause_code;

        if (! in_array($causeCode, config('employee_ficha.termination_letter_supported_causes', []), true)) {
            throw ValidationException::withMessages([
                'termination_cause_code' => 'La generacion de cartas para esta causal aun no esta disponible.',
            ]);
        }
    }

    /**
     * @return list<TerminationLetterDocumentTemplate>
     */
    private function requiredTemplates(string $causeCode): array
    {
        return array_values(array_filter(
            $this->templateManager->templatesForCause($causeCode),
            static fn (TerminationLetterDocumentTemplate $template): bool => $template->is_required,
        ));
    }

    /**
     * @param  list<TerminationLetterDocumentTemplate>  $templates
     */
    private function assertTemplatesReady(array $templates): void
    {
        if ($templates === []) {
            throw ValidationException::withMessages([
                'templates' => 'No hay plantillas configuradas para esta causal.',
            ]);
        }

        $missing = array_values(array_filter(
            $templates,
            static fn (TerminationLetterDocumentTemplate $template): bool => ! $template->hasTemplateFile(),
        ));

        if ($missing !== []) {
            $labels = implode(', ', array_map(static fn ($template) => $template->label, $missing));

            throw ValidationException::withMessages([
                'templates' => 'Faltan plantillas Word por subir: '.$labels.'.',
            ]);
        }
    }

    /**
     * @param  list<array{absolute: string, name: string}>  $generatedFiles
     */
    private function createZip(array $generatedFiles, string $zipAbsolutePath): void
    {
        $zip = new ZipArchive;

        if ($zip->open($zipAbsolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el archivo ZIP.');
        }

        foreach ($generatedFiles as $file) {
            $zip->addFile($file['absolute'], $file['name']);
        }

        $zip->close();
    }

    private function persistZip(EmployeeFichaEmploymentPeriod $period, string $zipAbsolutePath): string
    {
        if ($period->termination_letter_path && Storage::disk('local')->exists($period->termination_letter_path)) {
            Storage::disk('local')->delete($period->termination_letter_path);
        }

        $relativePath = 'ficha-empleados/termination-letters/'.$period->id.'/'.basename($zipAbsolutePath);
        Storage::disk('local')->makeDirectory(dirname($relativePath));
        Storage::disk('local')->put($relativePath, (string) file_get_contents($zipAbsolutePath));

        EmployeeFichaEmploymentPeriod::query()
            ->whereKey($period->getKey())
            ->update([
                'termination_letter_path' => $relativePath,
                'termination_letter_type' => 'zip',
            ]);

        $period->refresh();

        return $relativePath;
    }

    private function zipFileName(PersonalRequisitionFichaEntry $entry): string
    {
        return sprintf(
            'Cartas_Renuncia_%s_%s.zip',
            preg_replace('/\D+/', '', (string) $entry->hired_document) ?: 'empleado',
            now()->format('Y-m-d'),
        );
    }

    private function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$item;

            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}
