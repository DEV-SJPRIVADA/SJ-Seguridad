<?php

namespace App\Services\GestionHumana\TerminationLetter;

use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\PersonalRequisitionFichaEntry;
use App\Models\TerminationLetterDocumentTemplate;
use Illuminate\Support\Collection;
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
     * @param  list<int|string>  $templateIds
     * @return array{
     *     storage_path: string,
     *     download_name: string,
     *     document_count: int,
     *     output_type: string,
     *     template_ids: list<int>
     * }
     */
    public function generate(
        EmployeeFichaEmploymentPeriod $period,
        PersonalRequisitionFichaEntry $entry,
        array $templateIds,
    ): array {
        $this->assertCanGenerate($period);

        $normalizedIds = $this->normalizeTemplateIds($templateIds);
        $templates = $this->resolveTemplates($normalizedIds);

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

            $outputType = count($generatedFiles) === 1 ? 'docx' : 'zip';
            $downloadName = $this->downloadFileName($entry, $outputType);
            $outputAbsolutePath = $workDir.DIRECTORY_SEPARATOR.$downloadName;

            if ($outputType === 'docx') {
                if (! @copy($generatedFiles[0]['absolute'], $outputAbsolutePath)) {
                    throw new RuntimeException('No se pudo preparar el archivo Word generado.');
                }
            } else {
                $this->createZip($generatedFiles, $outputAbsolutePath);
            }

            $storageRelativePath = $this->persistOutput($period, $outputAbsolutePath, $outputType);

            return [
                'storage_path' => $storageRelativePath,
                'download_name' => $downloadName,
                'document_count' => count($generatedFiles),
                'output_type' => $outputType,
                'template_ids' => $normalizedIds,
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
    }

    /**
     * @param  list<int|string>  $templateIds
     * @return list<int>
     */
    private function normalizeTemplateIds(array $templateIds): array
    {
        $normalized = array_values(array_unique(array_map(
            static fn (int|string $id): int => (int) $id,
            $templateIds,
        )));

        if ($normalized === []) {
            throw ValidationException::withMessages([
                'template_ids' => 'Debe seleccionar al menos una plantilla.',
            ]);
        }

        return $normalized;
    }

    /**
     * @param  list<int>  $templateIds
     * @return Collection<int, TerminationLetterDocumentTemplate>
     */
    private function resolveTemplates(array $templateIds): Collection
    {
        $typeCode = (string) config('employee_ficha.word_document_type_codes.desvinculacion');

        $templates = TerminationLetterDocumentTemplate::query()
            ->with('type')
            ->whereIn('id', $templateIds)
            ->ordered()
            ->get();

        if ($templates->count() !== count($templateIds)) {
            throw ValidationException::withMessages([
                'template_ids' => 'Una o mas plantillas seleccionadas no existen.',
            ]);
        }

        $invalidType = $templates->first(
            static fn (TerminationLetterDocumentTemplate $template): bool => $template->type?->code !== $typeCode,
        );

        if ($invalidType !== null) {
            throw ValidationException::withMessages([
                'template_ids' => 'Solo se pueden generar cartas con plantillas de tipo desvinculacion.',
            ]);
        }

        $missingFiles = $templates->filter(
            static function (TerminationLetterDocumentTemplate $template): bool {
                return ! $template->hasTemplateFile()
                    || ! Storage::disk('local')->exists((string) $template->template_path);
            },
        );

        if ($missingFiles->isNotEmpty()) {
            $labels = $missingFiles
                ->map(static fn (TerminationLetterDocumentTemplate $template): string => $template->label)
                ->implode(', ');

            throw ValidationException::withMessages([
                'template_ids' => 'Faltan archivos Word en las plantillas: '.$labels.'.',
            ]);
        }

        return $templates->values();
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

    private function persistOutput(
        EmployeeFichaEmploymentPeriod $period,
        string $absolutePath,
        string $outputType,
    ): string {
        if ($period->termination_letter_path && Storage::disk('local')->exists($period->termination_letter_path)) {
            Storage::disk('local')->delete($period->termination_letter_path);
        }

        $relativePath = 'ficha-empleados/termination-letters/'.$period->id.'/'.basename($absolutePath);
        Storage::disk('local')->makeDirectory(dirname($relativePath));
        Storage::disk('local')->put($relativePath, (string) file_get_contents($absolutePath));

        EmployeeFichaEmploymentPeriod::query()
            ->whereKey($period->getKey())
            ->update([
                'termination_letter_path' => $relativePath,
                'termination_letter_type' => $outputType,
            ]);

        $period->refresh();

        return $relativePath;
    }

    private function downloadFileName(PersonalRequisitionFichaEntry $entry, string $outputType): string
    {
        $document = preg_replace('/\D+/', '', (string) $entry->hired_document) ?: 'empleado';
        $date = now()->format('Y-m-d');

        return sprintf('Cartas_Desvinculacion_%s_%s.%s', $document, $date, $outputType);
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
