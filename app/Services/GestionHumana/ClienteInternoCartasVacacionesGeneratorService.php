<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeFichaProfile;
use App\Models\TerminationLetterDocumentTemplate;
use App\Models\WordDocumentType;
use App\Services\GestionHumana\Letter\LetterVariableBuilder;
use App\Services\GestionHumana\TerminationLetter\TerminationLetterDocxRenderer;
use App\Services\GestionHumana\TerminationLetter\TerminationLetterTemplateManager;
use App\Support\WordTempDirectory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use ZipArchive;

/**
 * Genera cartas de vacaciones en lote (Cliente interno): 1→docx, N→zip, sin persistencia.
 */
class ClienteInternoCartasVacacionesGeneratorService
{
    public function __construct(
        private readonly TerminationLetterTemplateManager $templateManager,
        private readonly LetterVariableBuilder $variableBuilder,
        private readonly TerminationLetterDocxRenderer $docxRenderer,
    ) {}

    /**
     * Lookup de ficha por cédula(s). Activo autollena nombre; inactivo/no encontrado avisos.
     *
     * @param  list<string>  $documentNumbers
     * @return list<array{
     *     cedula: string,
     *     nombre_completo: string,
     *     status: string,
     *     warning: string|null,
     *     profile_id: int|null
     * }>
     */
    public function lookup(array $documentNumbers): array
    {
        $results = [];

        foreach ($documentNumbers as $raw) {
            $cedula = $this->normalizeDocument((string) $raw);
            if ($cedula === '') {
                continue;
            }

            $profile = $this->resolveProfile($cedula);

            if ($profile === null) {
                $results[] = [
                    'cedula' => $cedula,
                    'nombre_completo' => '',
                    'status' => 'no_encontrado',
                    'warning' => 'No se encontró ficha para esta cédula. Puede escribir el nombre manualmente.',
                    'profile_id' => null,
                ];

                continue;
            }

            $isActive = (string) $profile->employment_status === EmployeeFichaProfile::STATUS_ACTIVO;

            $results[] = [
                'cedula' => (string) ($profile->document_number ?: $cedula),
                'nombre_completo' => (string) ($profile->full_name ?? ''),
                'status' => $isActive ? 'activo' : 'inactivo',
                'warning' => $isActive
                    ? null
                    : 'La ficha no está activa. Puede generar con el nombre precargado o editarlo.',
                'profile_id' => (int) $profile->id,
            ];
        }

        return $results;
    }

    /**
     * @param  list<array{
     *     cedula: string,
     *     nombre_completo: string,
     *     fecha_inicio: string,
     *     fecha_fin: string,
     *     fecha_reintegro: string,
     *     periodos: string,
     *     dias_disfrutados: string|int|float,
     *     signatory_id: int
     * }>  $rows
     * @return array{
     *     absolute_path: string,
     *     download_name: string,
     *     output_type: string,
     *     row_count: int,
     *     template_id: int
     * }
     */
    public function generate(array $rows): array
    {
        $maxRows = (int) config('cliente_interno.cartas_vacaciones.max_rows', 500);
        if (count($rows) > $maxRows) {
            throw ValidationException::withMessages([
                'rows' => "El lote no puede superar {$maxRows} filas.",
            ]);
        }

        if ($rows === []) {
            throw ValidationException::withMessages([
                'rows' => 'Debe incluir al menos una fila para generar.',
            ]);
        }

        $template = $this->resolveExactlyOneTemplate();
        $templatePath = $this->templateManager->absolutePath($template->template_path);

        if ($templatePath === null || ! is_file($templatePath)) {
            throw ValidationException::withMessages([
                'template' => 'No hay plantilla activa de Cartas Vacaciones. Cargue una en Plantillas Word.',
            ]);
        }

        $workDir = WordTempDirectory::uniqueDir('cartas-vacaciones-');
        $generatedFiles = [];
        $finalPath = null;

        try {
            foreach ($rows as $index => $row) {
                // Recalcular fin/reintegro en servidor (misma regla que la grilla: sin domingos).
                $row = $this->applyComputedVacationDates($row);
                $variables = $this->variableBuilder->buildForCartasVacaciones($row);
                $cedulaSlug = preg_replace('/\D+/', '', (string) ($row['cedula'] ?? '')) ?: 'fila'.($index + 1);
                $outputName = sprintf(
                    '%02d_carta_vacaciones_%s.docx',
                    $index + 1,
                    Str::slug((string) $cedulaSlug, '_')
                );
                $outputPath = $workDir.DIRECTORY_SEPARATOR.$outputName;

                $this->docxRenderer->render($templatePath, $variables, $outputPath);
                $generatedFiles[] = [
                    'absolute' => $outputPath,
                    'name' => $outputName,
                ];
            }

            $outputType = count($generatedFiles) === 1 ? 'docx' : 'zip';
            $downloadName = $this->downloadFileName($outputType, count($generatedFiles));
            // Archivo de descarga fuera del workDir para poder limpiar temps y stream con deleteFileAfterSend.
            $finalPath = WordTempDirectory::path()
                .DIRECTORY_SEPARATOR
                .'cartas-vacaciones-dl-'
                .uniqid('', true)
                .'.'
                .$outputType;

            if ($outputType === 'docx') {
                if (! @copy($generatedFiles[0]['absolute'], $finalPath)) {
                    throw new RuntimeException('No se pudo preparar el archivo Word generado.');
                }
            } else {
                $this->createZip($generatedFiles, $finalPath);
            }

            return [
                'absolute_path' => $finalPath,
                'download_name' => $downloadName,
                'output_type' => $outputType,
                'row_count' => count($generatedFiles),
                'template_id' => (int) $template->id,
            ];
        } catch (\Throwable $e) {
            if (is_string($finalPath) && is_file($finalPath)) {
                @unlink($finalPath);
            }
            throw $e;
        } finally {
            $this->deleteDirectory($workDir);
        }
    }

    public function normalizeDocument(string $documentNumber): string
    {
        return trim($documentNumber);
    }

    /**
     * Fecha fin = inicio + N días disfrutados (sin contar domingos; el inicio cuenta si no es domingo).
     * Fecha reintegro = día calendario siguiente a la fecha fin.
     *
     * @return array{fecha_fin: string, fecha_reintegro: string}|null
     */
    public function computeVacationDates(string $fechaInicio, int $diasDisfrutados): ?array
    {
        if ($diasDisfrutados < 1) {
            return null;
        }

        try {
            $cursor = Carbon::parse($fechaInicio)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        $counted = 0;
        while ($counted < $diasDisfrutados) {
            if (! $cursor->isSunday()) {
                $counted++;
            }
            if ($counted < $diasDisfrutados) {
                $cursor->addDay();
            }
        }

        $fechaFin = $cursor->toDateString();
        $fechaReintegro = $cursor->copy()->addDay()->toDateString();

        return [
            'fecha_fin' => $fechaFin,
            'fecha_reintegro' => $fechaReintegro,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function applyComputedVacationDates(array $row): array
    {
        $diasRaw = $row['dias_disfrutados'] ?? null;
        $dias = is_numeric($diasRaw) ? (int) $diasRaw : 0;

        $computed = $this->computeVacationDates((string) ($row['fecha_inicio'] ?? ''), $dias);
        if ($computed === null) {
            return $row;
        }

        $row['fecha_fin'] = $computed['fecha_fin'];
        $row['fecha_reintegro'] = $computed['fecha_reintegro'];

        return $row;
    }

    /**
     * Plantillas del tipo con archivo en disco y tipo activo. Debe ser exactamente 1.
     */
    public function resolveExactlyOneTemplate(): TerminationLetterDocumentTemplate
    {
        $typeCode = (string) config('employee_ficha.word_document_type_codes.cartas_vacaciones', 'cartas_vacaciones');

        $type = WordDocumentType::query()
            ->forCode($typeCode)
            ->active()
            ->first();

        if ($type === null) {
            throw ValidationException::withMessages([
                'template' => 'No hay plantilla activa de Cartas Vacaciones. Cargue una en Plantillas Word.',
            ]);
        }

        $templates = TerminationLetterDocumentTemplate::query()
            ->where('word_document_type_id', $type->id)
            ->withFile()
            ->ordered()
            ->get()
            ->filter(static function (TerminationLetterDocumentTemplate $template): bool {
                return Storage::disk('local')->exists((string) $template->template_path);
            })
            ->values();

        if ($templates->isEmpty()) {
            throw ValidationException::withMessages([
                'template' => 'No hay plantilla activa de Cartas Vacaciones. Cargue una en Plantillas Word.',
            ]);
        }

        if ($templates->count() > 1) {
            throw ValidationException::withMessages([
                'template' => 'Hay más de una plantilla activa de Cartas Vacaciones. Deje solo una activa.',
            ]);
        }

        return $templates->first();
    }

    private function resolveProfile(string $cedula): ?EmployeeFichaProfile
    {
        // Preferir perfil activo; si hay varios, el más reciente.
        return EmployeeFichaProfile::query()
            ->where('document_number', $cedula)
            ->orderByRaw(
                'CASE WHEN employment_status = ? THEN 0 ELSE 1 END',
                [EmployeeFichaProfile::STATUS_ACTIVO]
            )
            ->orderByDesc('id')
            ->first();
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

    private function downloadFileName(string $outputType, int $rowCount): string
    {
        $date = now()->format('Y-m-d');

        if ($outputType === 'docx') {
            return sprintf('Carta_Vacaciones_%s.%s', $date, $outputType);
        }

        return sprintf('Cartas_Vacaciones_%d_%s.%s', $rowCount, $date, $outputType);
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
