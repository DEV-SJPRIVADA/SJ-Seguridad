<?php

namespace Tests\Feature;

use App\Models\EmployeeArchiveConsultation;
use App\Models\EmployeeArchiveConsultationItem;
use App\Models\EmployeeFichaProfile;
use App\Models\PersonalRequisitionFichaEntry;
use App\Models\User;
use App\Services\Access\ArchivoAccessService;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
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

    public function test_archivo_labor_histories_requires_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('gestion-humana.archivo.labor-histories.index'))
            ->assertForbidden();
    }

    public function test_archivo_index_redirects_to_labor_histories(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->givePermissionTo(['archivo.view', 'view.board.gestion_humana.archivo']);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.archivo.index'))
            ->assertRedirect(route('gestion-humana.archivo.labor-histories.index'));
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
            ->assertRedirect(route('gestion-humana.archivo.labor-histories.index'));

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

        $response->assertRedirect(route('gestion-humana.archivo.labor-histories.index'));
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
            'delivered_to' => 'Area Juridica',
        ]);

        $response->assertRedirect(route('gestion-humana.archivo.labor-histories.index', ['consultation' => EmployeeArchiveConsultation::query()->value('id')]));

        $consultationId = EmployeeArchiveConsultation::query()->value('id');

        $this->assertNotNull($consultationId);
        $this->assertDatabaseHas('employee_archive_consultations', [
            'id' => $consultationId,
            'user_id' => $viewer->id,
            'documents_requested' => 2,
            'documents_matched' => 1,
            'delivered_to' => 'Area Juridica',
        ]);

        $this->assertDatabaseCount('employee_archive_consultation_items', 2);
        $this->assertDatabaseHas('employee_archive_consultation_items', [
            'employee_archive_consultation_id' => $consultationId,
            'document_number' => '1010101010',
            'full_name' => 'CONSULTA MATCH',
            'delivered_to' => 'Area Juridica',
            'received' => false,
        ]);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.archivo.labor-histories.index', ['consultation' => $consultationId]))
            ->assertOk()
            ->assertViewHas('datatableUrl')
            ->assertSee('Consulta activa #'.$consultationId, false)
            ->assertDontSee('CONSULTA MATCH')
            ->assertDontSee('CONSULTA OTHER');

        $datatable = $this->getArchivoLaborHistoriesDatatable($viewer, ['consultation' => $consultationId]);
        $datatable->assertOk()
            ->assertJsonPath('recordsFiltered', 1);

        $rowText = $this->datatableRowTexts($datatable->json());
        $this->assertStringContainsString('CONSULTA MATCH', $rowText);
        $this->assertStringNotContainsString('CONSULTA OTHER', $rowText);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.archivo.consultation-history.index'))
            ->assertOk()
            ->assertSee('Area Juridica')
            ->assertSee('CONSULTA MATCH');
    }

    public function test_archivo_consultation_history_item_can_be_updated(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->givePermissionTo(['archivo.view', 'view.board.gestion_humana.archivo']);

        $consultation = EmployeeArchiveConsultation::query()->create([
            'user_id' => $viewer->id,
            'document_numbers' => ['1010101010'],
            'consultation_types' => ['juridico'],
            'documents_requested' => 1,
            'documents_matched' => 1,
            'delivered_to' => 'Gerencia',
        ]);

        $item = EmployeeArchiveConsultationItem::query()->create([
            'employee_archive_consultation_id' => $consultation->id,
            'document_number' => '1010101010',
            'full_name' => 'CONSULTA MATCH',
            'concept' => 'Juridico',
            'delivered_to' => 'Gerencia',
            'received' => false,
            'observation' => null,
            'week_of_month' => 1,
            'month_number' => 8,
            'month_label' => 'Agosto',
        ]);

        $this->actingAs($viewer)
            ->patch(route('gestion-humana.archivo.consultation-history.update', $item), [
                'received' => '1',
                'observation' => 'Entregado en sobre manila',
            ])
            ->assertRedirect(route('gestion-humana.archivo.consultation-history.index'));

        $this->assertDatabaseHas('employee_archive_consultation_items', [
            'id' => $item->id,
            'received' => true,
            'observation' => 'Entregado en sobre manila',
        ]);
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

    public function test_archivo_labor_histories_index_does_not_embed_rows(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->givePermissionTo(['archivo.view', 'view.board.gestion_humana.archivo']);

        PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => null,
            'hired_document' => '3030303030',
            'hired_full_name' => 'ARCHIVO INDEX SIN FILAS',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $viewer->id,
            'created_by' => $viewer->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.archivo.labor-histories.index'))
            ->assertOk()
            ->assertViewHas('datatableUrl')
            ->assertSee('js-archivo-labor-histories-datatable', false)
            ->assertDontSee('ARCHIVO INDEX SIN FILAS');
    }

    public function test_archivo_labor_histories_datatable_forbidden_without_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->getJson(route('gestion-humana.archivo.labor-histories.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]))
            ->assertForbidden();
    }

    public function test_archivo_labor_histories_datatable_search_filters_by_name(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->givePermissionTo(['archivo.view', 'view.board.gestion_humana.archivo']);

        PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => null,
            'hired_document' => '4040404040',
            'hired_full_name' => 'Ana Datatable Archivo',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $viewer->id,
            'created_by' => $viewer->id,
        ]);

        PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => null,
            'hired_document' => '5050505050',
            'hired_full_name' => 'Otro Empleado Archivo',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $viewer->id,
            'created_by' => $viewer->id,
        ]);

        $byName = $this->getArchivoLaborHistoriesDatatable($viewer, [
            'search' => ['value' => 'Ana Datatable'],
        ]);

        $byName->assertOk()->assertJsonPath('recordsFiltered', 1);
        $this->assertStringContainsString('Ana Datatable Archivo', $this->datatableRowTexts($byName->json()));
        $this->assertStringNotContainsString('Otro Empleado Archivo', $this->datatableRowTexts($byName->json()));
    }

    public function test_archivo_labor_histories_datatable_includes_inline_edit_for_managers(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo(['archivo.manage', 'view.board.gestion_humana.archivo']);

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => null,
            'hired_document' => '6060606060',
            'hired_full_name' => 'Manager Inline Archivo',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $manager->id,
            'created_by' => $manager->id,
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => $entry->hired_document,
            'full_name' => $entry->hired_full_name,
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'archive_shelf' => 'E-9',
            'archive_box' => 'Caja 3',
        ]);

        $payload = $this->getArchivoLaborHistoriesDatatable($manager)->assertOk()->json();
        $rowText = $this->datatableRowTexts($payload);

        $this->assertStringContainsString('Manager Inline Archivo', $rowText);
        $this->assertStringContainsString('name="archive_shelf"', $rowText);
        $this->assertStringContainsString('name="archive_box"', $rowText);
        $this->assertStringContainsString('E-9', $rowText);
        $this->assertStringContainsString('Actualizar', $rowText);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function getArchivoLaborHistoriesDatatable(User $user, array $query = []): TestResponse
    {
        return $this->actingAs($user)->getJson(route('gestion-humana.archivo.labor-histories.datatable', array_merge([
            'draw' => 1,
            'start' => 0,
            'length' => 100,
        ], $query)));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function datatableRowTexts(array $payload): string
    {
        return collect($payload['data'] ?? [])
            ->flatten()
            ->implode(' ');
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
