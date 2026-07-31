<?php

namespace Tests\Feature;

use App\Models\EmployeeFichaProfile;
use App\Models\PersonalRequisition;
use App\Models\PersonalRequisitionFichaEntry;
use App\Models\RequisitionCity;
use App\Models\RequisitionClient;
use App\Models\RequisitionClientType;
use App\Models\RequisitionPosition;
use App\Models\RequisitionProgrammingType;
use App\Models\RequisitionRequestReason;
use App\Models\RequisitionUniform;
use App\Models\User;
use App\Services\GestionHumana\EmployeeFichaNameParser;
use App\Services\GestionHumana\PlantillaMasivosMapper;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class EmployeeFichaPlantillasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_name_parser_splits_four_part_name(): void
    {
        $parsed = EmployeeFichaNameParser::parse('AGUILAR MARTHEY JOSE ORLANDO');

        $this->assertSame('AGUILAR', $parsed['first_surname']);
        $this->assertSame('MARTHEY', $parsed['second_surname']);
        $this->assertSame('JOSE', $parsed['first_name']);
        $this->assertSame('ORLANDO', $parsed['second_name']);
    }

    public function test_plantilla_masivos_mapper_maps_document_and_salary(): void
    {
        $entry = $this->createInFichaEntry('111111111', 'Juan Perez');
        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => '111111111',
            'full_name' => 'Juan Perez',
            'salary' => 1500000,
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        $row = app(PlantillaMasivosMapper::class)->mapRow($entry->fresh(['profile', 'requisition']));

        $this->assertSame('111111111', $row[0]);
        $this->assertSame('Juan Perez', $row[2]);
        $this->assertSame(1500000.0, (float) $row[27]);
    }

    public function test_export_plantilla_masivos_only_includes_active_en_ficha_without_date_range(): void
    {
        $manager = $this->managerUser();

        $active = $this->createInFichaEntry('222222222', 'Activo Uno');
        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $active->id,
            'document_number' => '222222222',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        $inactive = $this->createInFichaEntry('333333333', 'Inactivo Uno');
        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $inactive->id,
            'document_number' => '333333333',
            'employment_status' => EmployeeFichaProfile::STATUS_DESVINCULADO,
            'termination_date' => now()->subDay()->toDateString(),
        ]);

        $response = $this->actingAs($manager)->get(route('gestion-humana.ficha-empleados.employees.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $temp = tempnam(sys_get_temp_dir(), 'plantilla-export-');
        file_put_contents($temp, $response->streamedContent());
        $sheet = IOFactory::load($temp)->getActiveSheet();
        $cedulas = [];
        for ($row = 3; $row <= 10; $row++) {
            $value = trim((string) $sheet->getCell('A'.$row)->getValue());
            if ($value !== '') {
                $cedulas[] = $value;
            }
        }

        $this->assertContains('222222222', $cedulas);
        $this->assertNotContains('333333333', $cedulas);
    }

    public function test_export_redirects_when_no_active_records(): void
    {
        $manager = $this->managerUser();

        $response = $this->actingAs($manager)->get(route('gestion-humana.ficha-empleados.employees.export'));

        $response->assertRedirect(route('gestion-humana.ficha-empleados.employees.index', ['estado' => 'en_ficha']));
        $response->assertSessionHasErrors('export');
    }

    public function test_import_template_download_requires_manage_permission(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->assignRole('usuario');
        $viewer->givePermissionTo('ficha_empleados.view');

        $this->actingAs($viewer)
            ->get(route('gestion-humana.ficha-empleados.employees.import-template'))
            ->assertForbidden();

        $manager = $this->managerUser();

        $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.import-template'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_import_creates_profile_and_ficha_entry(): void
    {
        $manager = $this->managerUser();
        $path = $this->makeImportSpreadsheet([
            'cedula' => '99887766',
            'nombre' => 'IMPORT TEST USER',
            'fecha_ingreso' => '2026-01-15',
            'codigo_eps' => 'EPS01',
            'nombre_eps' => 'EPS Prueba',
        ]);

        $response = $this->actingAs($manager)->post(route('gestion-humana.ficha-empleados.employees.import'), [
            'import_file' => new UploadedFile($path, 'import.xlsx', null, null, true),
        ]);

        $response->assertRedirect(route('gestion-humana.ficha-empleados.employees.index', ['estado' => 'en_ficha']));

        $this->assertDatabaseHas('employee_ficha_profiles', [
            'document_number' => '99887766',
            'full_name' => 'IMPORT TEST USER',
            'eps_code' => 'EPS01',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        $this->assertDatabaseHas('personal_requisition_ficha_entries', [
            'hired_document' => '99887766',
        ]);
    }

    public function test_edit_ficha_form_accessible_for_manager(): void
    {
        $manager = $this->managerUser();
        $entry = $this->createPendingEntry('555555555', 'Edit Ficha Test');

        $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry))
            ->assertOk()
            ->assertSee('Completar ficha');
    }

    public function test_seed_catalogs_command_dry_run(): void
    {
        if (! is_file(base_path('docs/Contratacion/EMPLEADOS.xlsx'))) {
            $this->markTestSkipped('EMPLEADOS.xlsx no disponible en docs/Contratacion.');
        }

        $this->artisan('employee-ficha:seed-catalogs', ['--dry-run' => true])
            ->assertSuccessful();
    }

    private function managerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('usuario');
        $user->givePermissionTo(['ficha_empleados.view', 'ficha_empleados.manage']);

        return $user;
    }

    private function createPendingEntry(string $document, string $name): PersonalRequisitionFichaEntry
    {
        $requisition = $this->createRequisition('REQ-PLT-'.substr($document, -4));

        return PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => $document,
            'hired_full_name' => $name,
        ]);
    }

    private function createInFichaEntry(string $document, string $name): PersonalRequisitionFichaEntry
    {
        $mover = User::factory()->create(['must_change_password' => false]);
        $entry = $this->createPendingEntry($document, $name);
        $entry->update([
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $mover->id,
        ]);

        return $entry->fresh();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function makeImportSpreadsheet(array $row): string
    {
        $columns = array_keys(config('employee_ficha.import_columns'));
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($columns as $index => $key) {
            $col = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($col.'1', $key);
            $sheet->setCellValue($col.'2', $key);
        }

        foreach ($columns as $index => $key) {
            if (! array_key_exists($key, $row)) {
                continue;
            }
            $col = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($col.'3', $row[$key]);
        }

        $path = tempnam(sys_get_temp_dir(), 'ficha-import-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    private function createRequisition(string $code): PersonalRequisition
    {
        $requester = User::factory()->create(['must_change_password' => false]);

        return PersonalRequisition::query()->create([
            'code' => $code,
            'requested_by' => $requester->id,
            'request_date' => now()->toDateString(),
            'leader_name' => $requester->name,
            'requesting_area_key' => 'gestion_humana',
            'position_id' => RequisitionPosition::query()->firstOrFail()->id,
            'sex' => 'masculino',
            'quantity' => 1,
            'operating_area_key' => 'gestion_humana',
            'request_reason_id' => RequisitionRequestReason::query()->firstOrFail()->id,
            'client_id' => RequisitionClient::query()->firstOrFail()->id,
            'city_id' => RequisitionCity::query()->firstOrFail()->id,
            'client_type_id' => RequisitionClientType::query()->firstOrFail()->id,
            'programming_type_id' => RequisitionProgrammingType::query()->firstOrFail()->id,
            'uniform_id' => RequisitionUniform::query()->firstOrFail()->id,
            'required_profile' => 'Perfil test plantillas.',
            'service_structure' => 'Turno test.',
            'cost_center' => 'CC-TEST',
            'hiring_date' => now()->toDateString(),
            'base_salary' => 1800000,
            'status' => PersonalRequisition::STATUS_CONTRATADO,
            'status_changed_at' => now(),
        ]);
    }
}
