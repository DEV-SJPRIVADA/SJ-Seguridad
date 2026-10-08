<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeFichaProfile;
use App\Models\PayrollCatalogItem;
use App\Models\TerminationLetterDocumentTemplate;
use App\Models\WordDocumentType;
use App\Services\GestionHumana\Letter\LetterVariableBuilder;
use App\Services\GestionHumana\TerminationLetter\TerminationLetterDocxRenderer;
use App\Services\GestionHumana\TerminationLetter\TerminationLetterTemplateManager;
use App\Support\SpreadsheetCellReader;
use App\Support\WordTempDirectory;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;
use ZipArchive;

/**
 * Genera cartas de notificación en lote: 1→docx, N→zip, sin persistencia.
 * Import Excel solo hidrata grilla en memoria.
 */
class CartasNotificacionGeneratorService
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
     *     fecha_terminacion: string,
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
        $maxRows = (int) config('cartas_notificacion.max_rows', 500);
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
                'template' => 'No hay plantilla activa de Cartas Notificación. Cargue una en Plantillas Word.',
            ]);
        }

        $workDir = WordTempDirectory::uniqueDir('cartas-notificacion-');
        $generatedFiles = [];
        $finalPath = null;

        try {
            foreach ($rows as $index => $row) {
                $variables = $this->variableBuilder->buildForCartasNotificacion($row);
                $cedulaSlug = preg_replace('/\D+/', '', (string) ($row['cedula'] ?? '')) ?: 'fila'.($index + 1);
                $outputName = sprintf(
                    '%02d_carta_notificacion_%s.docx',
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
            $finalPath = WordTempDirectory::path()
                .DIRECTORY_SEPARATOR
                .'cartas-notificacion-dl-'
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

    /**
     * Parsea Excel a filas JSON (lookup ficha + match FIRMA). No escribe BD.
     *
     * @return array{
     *     rows: list<array{
     *         cedula: string,
     *         nombre_completo: string,
     *         fecha_terminacion: string,
     *         signatory_id: int|null,
     *         firma_label: string,
     *         status: string,
     *         warning: string|null
     *     }>,
     *     summary: array{
     *         total: int,
     *         with_warning: int,
     *         truncated: bool
     *     }
     * }
     */
    public function previewImport(UploadedFile $file): array
    {
        $maxRows = (int) config('cartas_notificacion.max_rows', 500);
        $path = $file->getRealPath();

        if ($path === false || ! is_readable($path)) {
            throw ValidationException::withMessages([
                'file' => 'No se pudo leer el archivo Excel.',
            ]);
        }

        try {
            $spreadsheet = IOFactory::load($path);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'file' => 'El archivo no es un Excel válido (.xlsx / .xls).',
            ]);
        }

        $sheet = $spreadsheet->getActiveSheet();
        $headers = $this->readImportHeaders($sheet);

        if (! isset($headers['CEDULA'])) {
            throw ValidationException::withMessages([
                'file' => 'Falta la columna CEDULA en la primera fila del Excel.',
            ]);
        }

        $signatories = $this->signatoryLookupMap();
        $highestRow = (int) $sheet->getHighestDataRow();
        $parsed = [];
        $truncated = false;

        for ($rowNum = 2; $rowNum <= $highestRow; $rowNum++) {
            $cedulaRaw = SpreadsheetCellReader::stringValue($sheet, $headers['CEDULA'], $rowNum);
            $cedula = $this->normalizeDocument($cedulaRaw);

            if ($cedula === '') {
                continue;
            }

            if (count($parsed) >= $maxRows) {
                $truncated = true;
                break;
            }

            $nombreExcel = isset($headers['NOMBRE_COMPLETO'])
                ? SpreadsheetCellReader::stringValue($sheet, $headers['NOMBRE_COMPLETO'], $rowNum)
                : '';
            $duracionRaw = isset($headers['DURACION_CONTRATO'])
                ? SpreadsheetCellReader::stringValue($sheet, $headers['DURACION_CONTRATO'], $rowNum)
                : '';
            $fechaRaw = isset($headers['FECHA_TERMINACION'])
                ? SpreadsheetCellReader::rawValue($sheet, $headers['FECHA_TERMINACION'], $rowNum)
                : null;
            $firmaRaw = isset($headers['FIRMA'])
                ? SpreadsheetCellReader::stringValue($sheet, $headers['FIRMA'], $rowNum)
                : '';

            $fecha = $this->parseExcelDate($fechaRaw);
            $firmaMatch = $this->matchSignatory($firmaRaw, $signatories);
            $duracion = $this->parseDuracionContrato($duracionRaw);

            $lookup = $this->lookup([$cedula])[0] ?? null;
            $nombre = $nombreExcel !== ''
                ? $nombreExcel
                : (string) ($lookup['nombre_completo'] ?? '');
            $warning = $lookup['warning'] ?? null;
            $status = $lookup['status'] ?? 'no_encontrado';

            if ($firmaRaw !== '' && $firmaMatch === null) {
                $firmaWarning = 'FIRMA no coincide con el catálogo (name o code). Seleccione la firma en la grilla.';
                $warning = $warning !== null && $warning !== ''
                    ? $warning.' '.$firmaWarning
                    : $firmaWarning;
            }

            if ($fecha === null && $fechaRaw !== null && $fechaRaw !== '') {
                $fechaWarning = 'FECHA_TERMINACION inválida; corríjala en la grilla.';
                $warning = $warning !== null && $warning !== ''
                    ? $warning.' '.$fechaWarning
                    : $fechaWarning;
            }

            if ($duracionRaw !== '' && $duracion === null) {
                $duracionWarning = 'DURACION_CONTRATO inválida (use 6 o 12); corríjala en la grilla.';
                $warning = $warning !== null && $warning !== ''
                    ? $warning.' '.$duracionWarning
                    : $duracionWarning;
            }

            $parsed[] = [
                'cedula' => (string) ($lookup['cedula'] ?? $cedula),
                'nombre_completo' => $nombre,
                'duracion_contrato' => $duracion,
                'fecha_terminacion' => $fecha ?? '',
                'signatory_id' => $firmaMatch['id'] ?? null,
                'firma_label' => $firmaMatch['label'] ?? '',
                'status' => $status,
                'warning' => $warning,
            ];
        }

        $withWarning = count(array_filter(
            $parsed,
            static fn (array $row): bool => filled($row['warning'] ?? null)
        ));

        return [
            'rows' => $parsed,
            'summary' => [
                'total' => count($parsed),
                'with_warning' => $withWarning,
                'truncated' => $truncated,
            ],
        ];
    }

    public function normalizeDocument(string $documentNumber): string
    {
        return trim($documentNumber);
    }

    /**
     * Plantillas del tipo con archivo en disco y tipo activo. Debe ser exactamente 1.
     */
    public function resolveExactlyOneTemplate(): TerminationLetterDocumentTemplate
    {
        $typeCode = (string) config('employee_ficha.word_document_type_codes.cartas_notificacion', 'cartas_notificacion');

        $type = WordDocumentType::query()
            ->forCode($typeCode)
            ->active()
            ->first();

        if ($type === null) {
            throw ValidationException::withMessages([
                'template' => 'No hay plantilla activa de Cartas Notificación. Cargue una en Plantillas Word.',
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
                'template' => 'No hay plantilla activa de Cartas Notificación. Cargue una en Plantillas Word.',
            ]);
        }

        if ($templates->count() > 1) {
            throw ValidationException::withMessages([
                'template' => 'Hay más de una plantilla activa de Cartas Notificación. Deje solo una activa.',
            ]);
        }

        return $templates->first();
    }

    private function resolveProfile(string $cedula): ?EmployeeFichaProfile
    {
        $find = static function (string $document): ?EmployeeFichaProfile {
            return EmployeeFichaProfile::query()
                ->where('document_number', $document)
                ->orderByRaw(
                    'CASE WHEN employment_status = ? THEN 0 ELSE 1 END',
                    [EmployeeFichaProfile::STATUS_ACTIVO]
                )
                ->orderByDesc('id')
                ->first();
        };

        $profile = $find($cedula);
        if ($profile !== null) {
            return $profile;
        }

        $digits = preg_replace('/\D+/', '', $cedula) ?? '';
        if ($digits !== '' && $digits !== $cedula) {
            return $find($digits);
        }

        return null;
    }

    /**
     * @return array<string, int> header key upper → column index (1-based)
     */
    private function readImportHeaders(Worksheet $sheet): array
    {
        $highestCol = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $map = [];

        for ($col = 1; $col <= $highestCol; $col++) {
            $header = mb_strtoupper(SpreadsheetCellReader::stringValue($sheet, $col, 1));
            $header = preg_replace('/\s+/', '_', $header) ?? $header;
            if ($header !== '') {
                $map[$header] = $col;
            }
        }

        return $map;
    }

    /**
     * @return array<string, array{id: int, label: string, name: string, code: string}>
     */
    private function signatoryLookupMap(): array
    {
        $map = [];

        $items = PayrollCatalogItem::query()
            ->ofType('firmas')
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        foreach ($items as $item) {
            $entry = [
                'id' => (int) $item->id,
                'label' => $item->name.' — '.$item->code,
                'name' => (string) $item->name,
                'code' => (string) $item->code,
            ];
            $map[mb_strtolower(trim((string) $item->name))] = $entry;
            $map[mb_strtolower(trim((string) $item->code))] = $entry;
        }

        return $map;
    }

    /**
     * @param  array<string, array{id: int, label: string, name: string, code: string}>  $map
     * @return array{id: int, label: string, name: string, code: string}|null
     */
    private function matchSignatory(string $raw, array $map): ?array
    {
        $key = mb_strtolower(trim($raw));
        if ($key === '') {
            return null;
        }

        return $map[$key] ?? null;
    }

    /**
     * Acepta 6 / 12 (también "6 meses", "12.").
     */
    private function parseDuracionContrato(string $raw): ?int
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/\d+/', $raw, $matches) !== 1) {
            return null;
        }

        $value = (int) $matches[0];
        $allowed = array_map('intval', config('cartas_notificacion.duracion_contrato_options', [6, 12]));

        return in_array($value, $allowed, true) ? $value : null;
    }

    private function parseExcelDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
            }

            return Carbon::parse(trim((string) $value))->toDateString();
        } catch (\Throwable) {
            return null;
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

    private function downloadFileName(string $outputType, int $rowCount): string
    {
        $date = now()->format('Y-m-d');

        if ($outputType === 'docx') {
            return sprintf('Carta_Notificacion_%s.%s', $date, $outputType);
        }

        return sprintf('Cartas_Notificacion_%d_%s.%s', $rowCount, $date, $outputType);
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
