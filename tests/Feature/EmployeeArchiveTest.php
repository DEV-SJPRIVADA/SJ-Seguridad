<?php

namespace Tests\Feature;

use App\Models\EmployeeArchiveConsultation;
use App\Models\EmployeeFichaProfile;
use App\Models\PersonalRequisitionFichaEntry;
use App\Models\User;
use App\Services\Access\ArchivoAccessService;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class EmployeeArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_employee_ficha_profiles_table_has_archive_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('employee_ficha_profiles', ['archive_shelf', 'archive_box']));
    }

    public function test_archivo_access_service_manage_implies_view(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('archivo.manage');

        $service = app(ArchivoAccessService::class);

        $this->assertTrue($service->canView($user));
        $this->assertTrue($service->canManage($user));
    }

    public function test_archivo_index_requires_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('gestion-humana.archivo.index'))
            ->assertForbidden();
    }

    public function test_archivo_manager_can_update_shelf_and_box(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo(['archivo.manage', 'view.board.gestion_humana.archivo']);

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => null,
            'hired_document' => '9988776655',
            'hired_full_name' => 'ARCHIVO TEST USER',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $manager->id,
            'created_by' => $manager->id,
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => $entry->hired_document,
            'full_name' => $entry->hired_full_name,
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        $this->actingAs($manager)
            ->patch(route('gestion-humana.archivo.update', $entry), [
                'archive_shelf' => 'A-01',
                'archive_box' => 'Caja 15',
            ])
            ->assertRedirect(route('gestion-humana.archivo.index'));

        $this->assertDatabaseHas('employee_ficha_profiles', [
            'personal_requisition_ficha_entry_id' => $entry->id,
            'archive_shelf' => 'A-01',
            'archive_box' => 'Caja 15',
        ]);
    }

    public function test_export_archive_template_includes_archive_columns(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(['ficha_empleados.view', 'view.board.gestion_humana.ficha_empleados']);

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => null,
            'hired_document' => '1122334455',
            'hired_full_name' => 'EXPORT ARCHIVO USER',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $viewer->id,
            'created_by' => $viewer->id,
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => $entry->hired_document,
            'full_name' => $entry->hired_full_name,
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'archive_shelf' => 'B-02',
            'archive_box' => 'Caja 7',
        ]);

        $response = $this->actingAs($viewer)
            ->get(route('gestion-humana.ficha-empleados.employees.export-archive-template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $tempPath = tempnam(sys_get_temp_dir(), 'archive-export-').'.xlsx';
        file_put_contents($tempPath, $response->streamedContent());

        $sheet = IOFactory::load($tempPath)->getActiveSheet();

        $importHeaders = array_keys(config('employee_ficha.import_columns', []));
        $archiveHeaders = array_keys(config('employee_ficha.archive_export_extra_columns', []));
        $allHeaders = array_merge($importHeaders, $archiveHeaders);

        $this->assertSame('cedula', $sheet->getCell('A1')->getValue());
        $this->assertSame('estantes', $sheet->getCell(Coordinate::stringFromColumnIndex(count($allHeaders) - 1).'1')->getValue());
        $this->assertSame('cajas', $sheet->getCell(Coordinate::stringFromColumnIndex(count($allHeaders)).'1')->getValue());
        $this->assertSame('B-02', $sheet->getCell(Coordinate::stringFromColumnIndex(count($allHeaders) - 1).'3')->getValue());
        $this->assertSame('Caja 7', $sheet->getCell(Coordinate::stringFromColumnIndex(count($allHeaders)).'3')->getValue());

        @unlink($tempPath);
    }

    public function test_import_columns_config_does_not_include_archive_fields(): void
    {
        $importColumns = array_keys(config('employee_ficha.import_columns', []));

        $this->assertNotContains('estantes', $importColumns);
        $this->assertNotContains('cajas', $importColumns);
    }

    public function test_archivo_import_updates_only_shelf_and_box(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo(['archivo.manage', 'view.board.gestion_humana.archivo']);

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => null,
            'hired_document' => '5566778899',
            'hired_full_name' => 'IMPORT ARCHIVO USER',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $manager->id,
            'created_by' => $manager->id,
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => $entry->hired_document,
            'full_name' => $entry->hired_full_name,
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'eps_code' => 'EPS-KEEP',
            'archive_shelf' => null,
            'archive_box' => null,
        ]);

        $path = $this->createArchiveImportSpreadsheet([
            ['cedula' => '5566778899', 'estantes' => 'C-10', 'cajas' => 'Caja 99'],
        ]);

        $response = $this->actingAs($manager)->post(route('gestion-humana.archivo.import'), [
            'import_file' => new UploadedFile($path, 'archivo.xlsx', null, null, true),
        ]);

        $response->assertRedirect(route('gestion-humana.archivo.index'));
        $response->assertSessionHas('import_result', function (array $result): bool {
            return ($result['updated'] ?? 0) === 1;
        });

        $this->assertDatabaseHas('employee_ficha_profiles', [
            'document_number' => '5566778899',
            'archive_shelf' => 'C-10',
            'archive_box' => 'Caja 99',
            'eps_code' => 'EPS-KEEP',
        ]);

        @unlink($path);
    }

    public function test_archivo_import_requires_manage_permission(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->givePermissionTo(['archivo.view', 'view.board.gestion_humana.archivo']);

        $path = $this->createArchiveImportSpreadsheet([
            ['cedula' => '1111111111', 'estantes' => 'A-1', 'cajas' => '1'],
        ]);

        $this->actingAs($viewer)
            ->post(route('gestion-humana.archivo.import'), [
                'import_file' => new UploadedFile($path, 'archivo.xlsx', null, null, true),
            ])
            ->assertForbidden();

        @unlink($path);
    }

    public function test_archivo_consultation_registers_and_filters_entries(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->givePermissionTo(['archivo.view', 'view.board.gestion_humana.archivo']);

        $matchedEntry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => null,
            'hired_document' => '1010101010',
            'hired_full_name' => 'CONSULTA MATCH',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $viewer->id,
            'created_by' => $viewer->id,
        ]);

        PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => null,
            'hired_document' => '2020202020',
            'hired_full_name' => 'CONSULTA OTHER',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $viewer->id,
            'created_by' => $viewer->id,
        ]);

        $response = $this->actingAs($viewer)->post(route('gestion-humana.archivo.consult'), [
            'documents' => "1010101010\n9999999999",
            'consultation_types' => ['juridico', 'gerencia'],
        ]);

        $response->assertRedirect();

        $consultationId = EmployeeArchiveConsultation::query()->value('id');

        $this->assertNotNull($consultationId);
        $this->assertDatabaseHas('employee_archive_consultations', [
            'id' => $consultationId,
            'user_id' => $viewer->id,
            'documents_requested' => 2,
            'documents_matched' => 1,
        ]);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.archivo.index', ['consultation' => $consultationId]))
            ->assertOk()
            ->assertSee('CONSULTA MATCH')
            ->assertDontSee('CONSULTA OTHER');
    }

    public function test_archivo_consultation_requires_types(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->givePermissionTo(['archivo.view', 'view.board.gestion_humana.archivo']);

        $this->actingAs($viewer)
            ->post(route('gestion-humana.archivo.consult'), [
                'documents' => '1010101010',
            ])
            ->assertSessionHasErrors([
                'consultation_types' => 'Debe seleccionar un motivo de consulta.',
            ]);
    }

    /**
     * @param  list<array{cedula: string, estantes?: string, cajas?: string}>  $rows
     */
    private function createArchiveImportSpreadsheet(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'cedula');
        $sheet->setCellValue('B1', 'estantes');
        $sheet->setCellValue('C1', 'cajas');
        $sheet->setCellValue('A2', 'Cedula');
        $sheet->setCellValue('B2', 'Estantes');
        $sheet->setCellValue('C2', 'Cajas');

        $row = 3;
        foreach ($rows as $data) {
            $sheet->setCellValue('A'.$row, $data['cedula']);
            if (array_key_exists('estantes', $data)) {
                $sheet->setCellValue('B'.$row, $data['estantes']);
            }
            if (array_key_exists('cajas', $data)) {
                $sheet->setCellValue('C'.$row, $data['cajas']);
            }
            $row++;
        }

        $path = tempnam(sys_get_temp_dir(), 'archivo-import-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}
