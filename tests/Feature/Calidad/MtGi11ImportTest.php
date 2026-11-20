<?php

namespace Tests\Feature\Calidad;

use App\Exports\MtGi11ImportTemplateExport;
use App\Models\Calidad\MtGi11CargoResponsable;
use App\Models\Calidad\MtGi11Estado;
use App\Models\Calidad\MtGi11Proceso;
use App\Models\Calidad\MtGi11Solicitud;
use App\Models\Calidad\MtGi11TipoDocumento;
use App\Models\Calidad\MtGi11TipoSolicitud;
use App\Models\User;
use App\Services\Calidad\MtGi11ImportService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Import masivo MT-GI-11: resuelve catálogos por nombre, permite consecutivos
 * repetidos y reporta solo errores reales por fila.
 */
class MtGi11ImportTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->editor = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $this->editor->givePermissionTo([
            'view.board.calidad.mt_gi_11',
            'mt_gi_11.matriz.view',
            'mt_gi_11.matriz.edit',
        ]);

        MtGi11CargoResponsable::query()->create([
            'code' => 'JEFE',
            'name' => 'JEFE DE PROCESO',
            'is_active' => true,
        ]);
    }

    /**
     * Construye un XLSX con la estructura de la plantilla (claves en fila 1).
     *
     * @param  list<array<string, string>>  $rows
     */
    private function makeFile(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $keys = array_keys(MtGi11ImportTemplateExport::columns());

        foreach ($keys as $index => $key) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1).'1', $key);
        }

        $rowNumber = 2;
        foreach ($rows as $row) {
            foreach ($keys as $index => $key) {
                $sheet->setCellValue(
                    Coordinate::stringFromColumnIndex($index + 1).$rowNumber,
                    (string) ($row[$key] ?? ''),
                );
            }
            $rowNumber++;
        }

        $path = tempnam(sys_get_temp_dir(), 'mtgi11').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function baseRow(array $overrides = []): array
    {
        return array_merge([
            'fecha_solicitud' => now()->toDateString(),
            'proceso' => MtGi11Proceso::query()->firstOrFail()->name,
            'consecutivo' => 'MT-GI-11-100',
            'tipo_documento' => MtGi11TipoDocumento::query()->firstOrFail()->name,
            'nombre_documento' => 'documento importado',
            'tipo_solicitud' => MtGi11TipoSolicitud::query()->firstOrFail()->name,
            'codigo_actual' => 'pr-gi-09',
            'version_actual' => '01',
            'justificacion' => 'justificacion de la importacion',
            'responsable_proceso' => 'ana lopez',
            'cargo_responsable' => 'JEFE DE PROCESO',
            'codigo_estandarizacion' => 'pr-gi-09-v2',
            'fecha_estandarizacion' => '',
            'version_actualizada' => '2',
            'estado' => MtGi11Estado::query()->where('code', 'PENDIENTE')->value('name'),
        ], $overrides);
    }

    public function test_import_creates_rows_and_uppercases_text(): void
    {
        $path = $this->makeFile([$this->baseRow()]);

        try {
            $stats = app(MtGi11ImportService::class)->import($path, (int) $this->editor->id);
        } finally {
            @unlink($path);
        }

        $this->assertSame(1, $stats['imported']);
        $this->assertSame(0, $stats['skipped']);

        $solicitud = MtGi11Solicitud::query()->firstOrFail();
        $this->assertSame('DOCUMENTO IMPORTADO', $solicitud->nombre_documento);
        $this->assertSame('ANA LOPEZ', $solicitud->responsable_proceso);
        $this->assertSame('PR-GI-09', $solicitud->codigo_actual);
        $this->assertSame($this->editor->id, $solicitud->created_by);
    }

    public function test_import_allows_repeated_consecutivo(): void
    {
        $path = $this->makeFile([
            $this->baseRow(['nombre_documento' => 'version uno', 'version_actualizada' => '2']),
            $this->baseRow(['nombre_documento' => 'version dos', 'version_actualizada' => '3']),
        ]);

        try {
            $stats = app(MtGi11ImportService::class)->import($path, (int) $this->editor->id);
        } finally {
            @unlink($path);
        }

        $this->assertSame(2, $stats['imported']);
        $this->assertSame(0, $stats['skipped']);
        $this->assertSame(2, MtGi11Solicitud::query()->where('consecutivo', 'MT-GI-11-100')->count());
    }

    public function test_import_reports_unknown_catalog_value_without_saving_row(): void
    {
        $path = $this->makeFile([
            $this->baseRow(['proceso' => 'PROCESO QUE NO EXISTE']),
            $this->baseRow(['consecutivo' => 'MT-GI-11-200']),
        ]);

        try {
            $stats = app(MtGi11ImportService::class)->import($path, (int) $this->editor->id);
        } finally {
            @unlink($path);
        }

        $this->assertSame(1, $stats['imported']);
        $this->assertSame(1, $stats['skipped']);
        $this->assertCount(1, $stats['failures']);
        $this->assertStringContainsString('Proceso no existe en el catálogo', $stats['errors'][0]);

        // Solo se guardó la fila válida.
        $this->assertSame(1, MtGi11Solicitud::query()->count());
        $this->assertSame('MT-GI-11-200', MtGi11Solicitud::query()->firstOrFail()->consecutivo);
    }

    public function test_import_reports_missing_required_justificacion(): void
    {
        $path = $this->makeFile([$this->baseRow(['justificacion' => ''])]);

        try {
            $stats = app(MtGi11ImportService::class)->import($path, (int) $this->editor->id);
        } finally {
            @unlink($path);
        }

        $this->assertSame(0, $stats['imported']);
        $this->assertSame(1, $stats['skipped']);
        $this->assertSame(0, MtGi11Solicitud::query()->count());
    }

    public function test_template_download_requires_edit_permission(): void
    {
        $viewer = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $viewer->givePermissionTo(['view.board.calidad.mt_gi_11', 'mt_gi_11.matriz.view']);

        $this->actingAs($viewer)
            ->get(route('calidad.mt-gi-11.matriz.plantilla'))
            ->assertForbidden();

        $this->actingAs($this->editor)
            ->get(route('calidad.mt-gi-11.matriz.plantilla'))
            ->assertOk();
    }
}
