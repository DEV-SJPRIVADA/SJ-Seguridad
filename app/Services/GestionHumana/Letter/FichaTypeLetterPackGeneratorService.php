<?php

namespace App\Services\GestionHumana\Letter;

use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\PersonalRequisitionFichaEntry;
use App\Models\TerminationLetterDocumentTemplate;
use App\Models\WordDocumentType;
use App\Services\GestionHumana\TerminationLetter\TerminationLetterDocxRenderer;
use App\Services\GestionHumana\TerminationLetter\TerminationLetterTemplateManager;
use App\Support\WordTempDirectory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use ZipArchive;

/**
 * Genera cartas Word para tipos del catálogo distintos de contratación/desvinculación.
 * No sobrescribe termination_letter_path (preserva packs de desvinculación/contratación).
 */
class FichaTypeLetterPackGeneratorService
{
    public function __construct(
        private readonly TerminationLetterTemplateManager $templateManager,
        private readonly LetterVariableBuilder $variableBuilder,
        private readonly TerminationLetterDocxRenderer $docxRenderer,
    ) {}

    /**
     * @param  list<int|string>  $templateIds
     * @return array{
     *     storage_path: string,
     *     download_name: string,
     *     document_count: int,
     *     output_type: string,
     *     template_ids: list<int>,
     *     type_code: string
     * }
     */
    public function generate(
        EmployeeFichaEmploymentPeriod $period,
        PersonalRequisitionFichaEntry $entry,
        WordDocumentType $type,
        array $templateIds,
        ?int $signatoryId = null,
    ): array {
        $this->assertCanGenerate($period, $type);

        $normalizedIds = $this->normalizeTemplateIds($templateIds);
        $templates = $this->resolveTemplates($normalizedIds, (string) $type->code);

        $entry->loadMissing('profile', 'requisition');
        $variables = $this->variableBuilder->build($period, $entry, $entry->profile, null, $signatoryId);

        $workDir = WordTempDirectory::uniqueDir('ficha-type-letter-');
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
            $downloadName = $this->downloadFileName($entry, $type, $outputType);
            $outputAbsolutePath = $workDir.DIRECTORY_SEPARATOR.$downloadName;

            if ($outputType === 'docx') {
                if (! @copy($generatedFiles[0]['absolute'], $outputAbsolutePath)) {
                    throw new RuntimeException('No se pudo preparar el archivo Word generado.');
                }
            } else {
                $this->createZip($generatedFiles, $outputAbsolutePath);
            }

            $storageRelativePath = $this->persistOutput($period, $type, $outputAbsolutePath);

            return [
                'storage_path' => $storageRelativePath,
                'download_name' => $downloadName,
                'document_count' => count($generatedFiles),
                'output_type' => $outputType,
                'template_ids' => $normalizedIds,
                'type_code' => (string) $type->code,
            ];
        } finally {
            $this->deleteDirectory($workDir);
        }
    }

    public function assertCanGenerate(EmployeeFichaEmploymentPeriod $period, WordDocumentType $type): void
    {
        $this->assertNotReservedType($type);

        if (! $type->is_active) {
            throw ValidationException::withMessages([
                'type' => 'El tipo de documento no está activo.',
            ]);
        }

        $status = (string) $period->status;
        $allowed = [
            EmployeeFichaEmploymentPeriod::STATUS_ACTIVO,
            EmployeeFichaEmploymentPeriod::STATUS_CERRADO,
        ];

        if (! in_array($status, $allowed, true)) {
            throw ValidationException::withMessages([
                'period' => 'Solo se pueden generar cartas con un vínculo laboral activo o cerrado.',
            ]);
        }
    }

    public function assertNotReservedType(WordDocumentType $type): void
    {
        $reserved = array_values(array_map(
            static fn (mixed $code): string => (string) $code,
            config('employee_ficha.word_document_type_codes', []),
        ));

        if (in_array((string) $type->code, $reserved, true)) {
            throw ValidationException::withMessages([
                'type' => 'Use el flujo específico de contratación o desvinculación para este tipo.',
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
    private function resolveTemplates(array $templateIds, string $typeCode): Collection
    {
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
                'template_ids' => 'Solo se pueden generar cartas con plantillas del tipo seleccionado.',
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
        WordDocumentType $type,
        string $absolutePath,
    ): string {
        $typeSlug = Str::slug((string) $type->code, '_') ?: 'tipo';
        $relativePath = 'ficha-empleados/type-letters/'.$typeSlug.'/'.$period->id.'/'.basename($absolutePath);

        Storage::disk('local')->makeDirectory(dirname($relativePath));
        Storage::disk('local')->put($relativePath, (string) file_get_contents($absolutePath));

        return $relativePath;
    }

    private function downloadFileName(
        PersonalRequisitionFichaEntry $entry,
        WordDocumentType $type,
        string $outputType,
    ): string {
        $document = preg_replace('/\D+/', '', (string) $entry->hired_document) ?: 'empleado';
        $typeSlug = Str::slug((string) $type->name, '_') ?: 'cartas';
        $date = now()->format('Y-m-d');

        return sprintf('Cartas_%s_%s_%s.%s', $typeSlug, $document, $date, $outputType);
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
