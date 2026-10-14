<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeFichaProfile;
use App\Models\InasistenciaCarta;
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
use Illuminate\Support\Facades\DB;
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
 * Genera cartas de inasistencia en lote: 1→docx, N→zip, y persiste histórico (sin archivo).
 */
class InasistenciasGeneratorService
{
    public function __construct(
        private readonly TerminationLetterTemplateManager $templateManager,
        private readonly LetterVariableBuilder $variableBuilder,
        private readonly TerminationLetterDocxRenderer $docxRenderer,
    ) {}

    /**
     * Días calendario inclusivos desde fecha inicio aus hasta hoy
     * (ej. 12 → 14 = 3; mismo día = 1).
     */
    public static function computeDias(string|Carbon $fechaIniAus): int
    {
        $start = Carbon::parse($fechaIniAus)->startOfDay();
        $end = now()->startOfDay();

        // Inclusivo: diffInDays + 1 (12→14 = 3).
        return (int) max(1, $start->diffInDays($end) + 1);
    }

    /**
     * @param  list<string>  $documentNumbers
     * @return list<array{
     *     cedula: string,
     *     nombre_completo: string,
     *     cargo: string,
     *     ciudad: string,
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
                    'cargo' => '',
                    'ciudad' => '',
                    'status' => 'no_encontrado',
                    'warning' => 'No se encontró ficha para esta cédula. Puede completar nombre, cargo y ciudad manualmente.',
                    'profile_id' => null,
                ];

                continue;
            }

            $isActive = (string) $profile->employment_status === EmployeeFichaProfile::STATUS_ACTIVO;

            $results[] = [
                'cedula' => (string) ($profile->document_number ?: $cedula),
                'nombre_completo' => (string) ($profile->full_name ?? ''),
                'cargo' => (string) ($profile->position_name ?? ''),
                'ciudad' => $profile->displayCityName(),
                'status' => $isActive ? 'activo' : 'inactivo',
                'warning' => $isActive
                    ? null
                    : 'La ficha no está activa. Puede generar con los datos precargados o editarlos.',
                'profile_id' => (int) $profile->id,
            ];
        }

        return $results;
    }

    /**
     * @param  list<array{
     *     cedula: string,
     *     nombre_completo: string,
     *     cargo: string,
     *     ciudad: string,
     *     fecha_ini_aus: string,
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
    public function generate(array $rows, ?int $userId = null): array
    {
        $maxRows = (int) config('inasistencias.max_rows', 500);
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
                'template' => 'No hay plantilla activa de Inasistencias. Cargue una en Plantillas Word.',
            ]);
        }

        $workDir = WordTempDirectory::uniqueDir('inasistencias-');
        $generatedFiles = [];
        $finalPath = null;
        $firmaCache = [];

        try {
            foreach ($rows as $index => $row) {
                $dias = self::computeDias((string) $row['fecha_ini_aus']);
                $rowWithDias = [...$row, 'dias' => $dias];
                $variables = $this->variableBuilder->buildForInasistencias($rowWithDias);
                $cedulaSlug = preg_replace('/\D+/', '', (string) ($row['cedula'] ?? '')) ?: 'fila'.($index + 1);
                $outputName = sprintf(
                    '%02d_inasistencia_%s.docx',
                    $index + 1,
                    Str::slug((string) $cedulaSlug, '_')
                );
                $outputPath = $workDir.DIRECTORY_SEPARATOR.$outputName;

                $this->docxRenderer->render($templatePath, $variables, $outputPath);
                $generatedFiles[] = [
                    'absolute' => $outputPath,
                    'name' => $outputName,
                ];

                $signatoryId = (int) $row['signatory_id'];
                if (! isset($firmaCache[$signatoryId])) {
                    $firmaCache[$signatoryId] = [
                        'name' => (string) ($variables['FIRMA'] ?? ''),
                        'code' => (string) ($variables['CARGO_FIRMA'] ?? ''),
                    ];
                }
            }

            $outputType = count($generatedFiles) === 1 ? 'docx' : 'zip';
            $downloadName = $this->downloadFileName($outputType, count($generatedFiles));
            $finalPath = WordTempDirectory::path()
                .DIRECTORY_SEPARATOR
                .'inasistencias-dl-'
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

            // Persistir histórico (registro, no el archivo Word).
            DB::transaction(function () use ($rows, $firmaCache, $template, $userId): void {
                foreach ($rows as $row) {
                    $signatoryId = (int) $row['signatory_id'];
                    $firma = $firmaCache[$signatoryId] ?? ['name' => '', 'code' => ''];
                    $dias = self::computeDias((string) $row['fecha_ini_aus']);

                    InasistenciaCarta::query()->create([
                        'document_number' => trim((string) $row['cedula']),
                        'full_name' => trim((string) ($row['nombre_completo'] ?? '')),
                        'cargo' => trim((string) ($row['cargo'] ?? '')),
                        'ciudad' => trim((string) ($row['ciudad'] ?? '')),
                        'fecha_ini_aus' => (string) $row['fecha_ini_aus'],
                        'dias' => $dias,
                        'firma_name' => $firma['name'],
                        'cargo_firma' => $firma['code'],
                        'signatory_id' => $signatoryId,
                        'generated_by' => $userId,
                        'generated_at' => now(),
                        'word_template_id' => (int) $template->id,
                    ]);
                }
            });

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
     * @return array{
     *     rows: list<array{
     *         cedula: string,
     *         nombre_completo: string,
     *         cargo: string,
     *         ciudad: string,
     *         fecha_ini_aus: string,
     *         dias: int|null,
     *         signatory_id: int|null,
     *         firma_label: string,
     *         status: string,
     *         warning: string|null
     *     }>,
     *     summary: array{total: int, with_warning: int, truncated: bool}
     * }
     */
    public function previewImport(UploadedFile $file): array
    {
        $maxRows = (int) config('inasistencias.max_rows', 500);
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

            $nombreExcel = '';
            if (isset($headers['NOMBRE'])) {
                $nombreExcel = SpreadsheetCellReader::stringValue($sheet, $headers['NOMBRE'], $rowNum);
            } elseif (isset($headers['NOMBRE_COMPLETO'])) {
                $nombreExcel = SpreadsheetCellReader::stringValue($sheet, $headers['NOMBRE_COMPLETO'], $rowNum);
            }

            $fechaRaw = isset($headers['FECHA_INI_AUS'])
                ? SpreadsheetCellReader::rawValue($sheet, $headers['FECHA_INI_AUS'], $rowNum)
                : null;
            $firmaRaw = isset($headers['FIRMA'])
                ? SpreadsheetCellReader::stringValue($sheet, $headers['FIRMA'], $rowNum)
                : '';

            $fecha = $this->parseExcelDate($fechaRaw);
            $firmaMatch = $this->matchSignatory($firmaRaw, $signatories);

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
                $fechaWarning = 'FECHA_INI_AUS inválida; corríjala en la grilla.';
                $warning = $warning !== null && $warning !== ''
                    ? $warning.' '.$fechaWarning
                    : $fechaWarning;
            }

            $dias = $fecha !== null ? self::computeDias($fecha) : null;

            $parsed[] = [
                'cedula' => (string) ($lookup['cedula'] ?? $cedula),
                'nombre_completo' => $nombre,
                'cargo' => (string) ($lookup['cargo'] ?? ''),
                'ciudad' => (string) ($lookup['ciudad'] ?? ''),
                'fecha_ini_aus' => $fecha ?? '',
                'dias' => $dias,
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

    public function resolveExactlyOneTemplate(): TerminationLetterDocumentTemplate
    {
        $typeCode = (string) config('employee_ficha.word_document_type_codes.inasistencias', 'inasistencias');

        $type = WordDocumentType::query()
            ->forCode($typeCode)
            ->active()
            ->first();

        if ($type === null) {
            throw ValidationException::withMessages([
                'template' => 'No hay plantilla activa de Inasistencias. Cargue una en Plantillas Word.',
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
                'template' => 'No hay plantilla activa de Inasistencias. Cargue una en Plantillas Word.',
            ]);
        }

        if ($templates->count() > 1) {
            throw ValidationException::withMessages([
                'template' => 'Hay más de una plantilla activa de Inasistencias. Deje solo una activa.',
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
     * @return array<string, int>
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
            return sprintf('Carta_Inasistencia_%s.%s', $date, $outputType);
        }

        return sprintf('Cartas_Inasistencia_%d_%s.%s', $rowCount, $date, $outputType);
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
