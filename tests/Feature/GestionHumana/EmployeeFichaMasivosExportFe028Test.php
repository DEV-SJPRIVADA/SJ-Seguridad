<?php

namespace Tests\Feature\GestionHumana;

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
use App\Services\GestionHumana\EmployeeFichaImportRowMapper;
use App\Services\GestionHumana\PlantillaMasivosMapper;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\EmployeeFichaMasivosPayload;
use Tests\TestCase;

class EmployeeFichaMasivosExportFe028Test extends TestCase
{
    use EmployeeFichaMasivosPayload;
    use RefreshDatabase;

    private const COL_NIT = 25;

    private const COL_WORK_CENTER_NAME = 26;

    private const COL_SALARY = 27;

    private const COL_RESIDENCE_CITY_NAME = 12;

    private const COL_COST_CENTER = 53;

    private const COL_WORKDAY = 46;

    private const COL_DOCUMENT_TYPE = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
        $this->seedFe028CatalogFixtures();
    }

    public function test_plantilla_masivos_mapper_never_exports_requisition_fallbacks(): void
    {
        $entry = $this->createInFichaEntryWithRequisition([
            'cost_center' => 'CC-REQ-FALLBACK',
            'base_salary' => 9999999,
            'hiring_date' => '2020-01-01',
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => $entry->hired_document,
            'full_name' => $entry->hired_full_name,
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        $row = app(PlantillaMasivosMapper::class)->mapRow($entry->fresh(['profile', 'requisition']));

        $this->assertNull($row[self::COL_RESIDENCE_CITY_NAME]);
        $this->assertNull($row[self::COL_WORK_CENTER_NAME]);
        $this->assertNull($row[self::COL_SALARY]);
        $this->assertNull($row[self::COL_COST_CENTER]);
        $this->assertNull($row[18]);
        $this->assertNull($row[44]);
    }

    public function test_plantilla_masivos_mapper_exports_only_saved_profile_and_payroll_extra(): void
    {
        $entry = $this->createInFichaEntryWithRequisition([
            'cost_center' => 'CC-REQ-IGNORE',
            'base_salary' => 5000000,
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => $entry->hired_document,
            'full_name' => $entry->hired_full_name,
            'document_type' => 'CE',
            'salary' => 2800000,
            'cost_center_code' => 'CC01',
            'cost_center_name' => 'Centro Guardado',
            'work_center_name' => 'Centro Trabajo Guardado',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'payroll_extra' => [
                'work_center_code' => 'WC01',
                'ccf_code' => 'CCF01',
                'workday' => '2',
                'withholding_type' => '2',
                'expense_type' => '3',
                'exclude_overtime' => '1',
            ],
        ]);

        $row = app(PlantillaMasivosMapper::class)->mapRow($entry->fresh(['profile', 'requisition']));

        $this->assertSame('CE', $row[self::COL_DOCUMENT_TYPE]);
        $this->assertSame(2800000.0, (float) $row[self::COL_SALARY]);
        $this->assertSame('CC01', $row[self::COL_COST_CENTER]);
        $this->assertSame('WC01', $row[24]);
        $this->assertSame('Centro Trabajo Guardado', $row[self::COL_WORK_CENTER_NAME]);
        $this->assertSame('2', $row[self::COL_WORKDAY]);
        $this->assertSame('2', $row[47]);
        $this->assertSame('3', $row[48]);
        $this->assertSame('1', $row[61]);
    }

    public function test_plantilla_masivos_mapper_always_exports_null_nit_column(): void
    {
        $entry = $this->createInFichaEntryWithRequisition();

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => $entry->hired_document,
            'full_name' => $entry->hired_full_name,
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'payroll_extra' => ['work_center_nit' => '900123456'],
        ]);

        $row = app(PlantillaMasivosMapper::class)->mapRow($entry->fresh(['profile']));

        $this->assertNull($row[self::COL_NIT]);
    }

    public function test_plantilla_masivos_mapper_does_not_default_document_type_or_payroll_extra_numbers(): void
    {
        $entry = $this->createInFichaEntry('555666777', 'Sin Defaults Test');

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => '555666777',
            'full_name' => 'Sin Defaults Test',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        $row = app(PlantillaMasivosMapper::class)->mapRow($entry->fresh(['profile']));

        $this->assertNull($row[self::COL_DOCUMENT_TYPE]);
        $this->assertNull($row[self::COL_WORKDAY]);
        $this->assertNull($row[47]);
        $this->assertNull($row[48]);
        $this->assertNull($row[61]);
    }

    public function test_import_row_mapper_does_not_use_requisition_fallbacks(): void
    {
        $entry = $this->createInFichaEntryWithRequisition([
            'cost_center' => 'CC-IMPORT-FALLBACK',
            'base_salary' => 7777777,
            'hiring_date' => '2019-06-01',
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => $entry->hired_document,
            'full_name' => $entry->hired_full_name,
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        $row = app(EmployeeFichaImportRowMapper::class)->mapRow($entry->fresh(['profile', 'requisition']));

        $this->assertNull($row['fecha_ingreso']);
        $this->assertNull($row['ccosto']);
        $this->assertNull($row['nombre_centro_trabajo']);
        $this->assertNull($row['nombre_cargo']);
        $this->assertNull($row['salario']);
        $this->assertArrayHasKey('codigo_ciudad_trabajo', $row);
        $this->assertArrayHasKey('ciudad_trabajo', $row);
        $this->assertNull($row['ciudad_trabajo']);
    }

    public function test_import_row_mapper_exports_work_city_fields(): void
    {
        $entry = $this->createInFichaEntryWithRequisition();

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => $entry->hired_document,
            'full_name' => $entry->hired_full_name,
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'work_city_code' => '05001',
            'work_city_name' => 'Medellin',
        ]);

        $row = app(EmployeeFichaImportRowMapper::class)->mapRow($entry->fresh(['profile']));

        $this->assertSame('05001', $row['codigo_ciudad_trabajo']);
        $this->assertSame('Medellin', $row['ciudad_trabajo']);
    }

    public function test_plantilla_masivos_mapper_column_count_unchanged_by_work_city(): void
    {
        $entry = $this->createInFichaEntryWithRequisition();

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => $entry->hired_document,
            'full_name' => $entry->hired_full_name,
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'work_city_code' => '05001',
            'work_city_name' => 'Medellin',
            'residence_city_code' => '11001',
            'residence_city_name' => 'Bogota',
        ]);

        $row = app(PlantillaMasivosMapper::class)->mapRow($entry->fresh(['profile']));

        $this->assertCount(count(config('employee_ficha.plantilla_masivos_columns')), $row);
        $this->assertSame('Bogota', $row[self::COL_RESIDENCE_CITY_NAME]);
        $this->assertNotContains('Medellin', $row);
    }

    public function test_store_profile_and_export_plantilla_masivos_round_trip(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo(['ficha_empleados.manage', 'ficha_empleados.view']);

        $document = '88'.random_int(10000000, 99999999);

        $this->actingAs($manager)->post(route('gestion-humana.ficha-empleados.employees.store'), array_merge(
            $this->masivosCorePayload([
                'document_type' => 'CE',
                'payroll_extra' => [
                    'ccf_code' => 'CCF01',
                    'work_center_code' => 'WC01',
                    'workday' => '1',
                ],
            ]),
            [
                'hired_document' => $document,
                'hired_full_name' => 'Round Trip Export Test',
            ],
        ))->assertRedirect();

        $entry = PersonalRequisitionFichaEntry::query()
            ->where('hired_document', $document)
            ->firstOrFail();

        $response = $this->actingAs($manager)->get(route('gestion-humana.ficha-empleados.employees.export'));
        $response->assertOk();

        $temp = tempnam(sys_get_temp_dir(), 'plantilla-roundtrip-');
        file_put_contents($temp, $response->streamedContent());
        $sheet = IOFactory::load($temp)->getActiveSheet();

        $cedulas = [];
        for ($row = 3; $row <= 50; $row++) {
            $value = trim((string) $sheet->getCell('A'.$row)->getValue());
            if ($value === $document) {
                $cedulas[] = $value;
                $this->assertSame('CE', (string) $sheet->getCell('B'.$row)->getValue());
                $this->assertSame('Round Trip Export Test', $sheet->getCell('C'.$row)->getValue());
                $this->assertSame('', (string) $sheet->getCell('Z'.$row)->getValue());
                $this->assertSame('WC01', (string) $sheet->getCell('Y'.$row)->getValue());
                $this->assertSame('CCF01', (string) $sheet->getCell('AL'.$row)->getValue());
            }
        }

        $this->assertCount(1, $cedulas);
    }

    /**
     * @param  array<string, mixed>  $requisitionOverrides
     */
    private function createInFichaEntryWithRequisition(array $requisitionOverrides = []): PersonalRequisitionFichaEntry
    {
        $requester = User::factory()->create(['must_change_password' => false]);
        $client = RequisitionClient::query()->firstOrFail();
        $city = RequisitionCity::query()->firstOrFail();

        $requisition = PersonalRequisition::query()->create(array_merge([
            'code' => 'REQ-MASIVOS-'.uniqid(),
            'requested_by' => $requester->id,
            'request_date' => now()->toDateString(),
            'leader_name' => $requester->name,
            'requesting_area_key' => 'gestion_humana',
            'position_id' => RequisitionPosition::query()->firstOrFail()->id,
            'sex' => 'masculino',
            'quantity' => 1,
            'operating_area_key' => 'gestion_humana',
            'request_reason_id' => RequisitionRequestReason::query()->firstOrFail()->id,
            'client_id' => $client->id,
            'city_id' => $city->id,
            'client_type_id' => RequisitionClientType::query()->firstOrFail()->id,
            'programming_type_id' => RequisitionProgrammingType::query()->firstOrFail()->id,
            'uniform_id' => RequisitionUniform::query()->firstOrFail()->id,
            'required_profile' => 'Perfil export test.',
            'service_structure' => 'Turno export test.',
            'cost_center' => 'CC-REQ-BASE',
            'status' => PersonalRequisition::STATUS_CONTRATADO,
            'status_changed_at' => now(),
            'hiring_date' => now()->subMonth()->toDateString(),
            'base_salary' => 3000000,
        ], $requisitionOverrides));

        $mover = User::factory()->create(['must_change_password' => false]);

        return PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => '66'.random_int(10000000, 99999999),
            'hired_full_name' => 'Export Masivos FE028',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $mover->id,
            'created_by' => $mover->id,
        ]);
    }

    private function createInFichaEntry(string $document, string $name): PersonalRequisitionFichaEntry
    {
        $mover = User::factory()->create(['must_change_password' => false]);

        return PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => null,
            'hired_document' => $document,
            'hired_full_name' => $name,
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $mover->id,
            'created_by' => $mover->id,
        ]);
    }
}
