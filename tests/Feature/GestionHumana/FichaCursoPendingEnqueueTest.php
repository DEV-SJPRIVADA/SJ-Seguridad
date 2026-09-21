<?php

namespace Tests\Feature\GestionHumana;

use App\Models\EmployeeCurso;
use App\Models\EmployeeCursoPending;
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
use App\Services\GestionHumana\EmployeeFichaImportService;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Support\EmployeeFichaMasivosPayload;
use Tests\TestCase;

class FichaCursoPendingEnqueueTest extends TestCase
{
    use EmployeeFichaMasivosPayload;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
        $this->seedFe028CatalogFixtures();
    }

    public function test_store_with_ficha_entry_id_enqueues_pending_curso(): void
    {
        $manager = $this->managerUser();
        $entry = $this->createPendingEntry('910000101', 'Cola Desde Pendiente');

        $this->actingAs($manager)->post(route('gestion-humana.ficha-empleados.employees.store'), array_merge(
            $this->masivosCorePayload([
                'first_surname' => 'Desde',
                'first_name' => 'Pendiente',
            ]),
            [
                'ficha_entry_id' => $entry->id,
                'hired_document' => '910000101',
                'hired_full_name' => 'Desde Pendiente',
                'document_type' => 'C',
            ],
        ))->assertRedirect();

        $entry->refresh();
        $this->assertNotNull($entry->moved_to_ficha_at);
        $this->assertNotNull($entry->profile);

        $this->assertDatabaseHas('employee_curso_pending', [
            'document_number' => '910000101',
            'status' => EmployeeCursoPending::STATUS_PENDING,
            'employee_ficha_profile_id' => $entry->profile->id,
            'personal_requisition_ficha_entry_id' => $entry->id,
            'enqueued_by' => $manager->id,
        ]);
    }

    public function test_manual_store_enqueues_pending_curso(): void
    {
        $manager = $this->managerUser();

        $this->actingAs($manager)->post(route('gestion-humana.ficha-empleados.employees.store'), array_merge(
            $this->masivosCorePayload([
                'first_surname' => 'Manual',
                'first_name' => 'Cola',
            ]),
            [
                'hired_document' => '910000102',
                'hired_full_name' => 'Manual Cola',
                'document_type' => 'C',
            ],
        ))->assertRedirect();

        $entry = PersonalRequisitionFichaEntry::query()
            ->where('hired_document', '910000102')
            ->first();

        $this->assertNotNull($entry);
        $this->assertNotNull($entry->profile);

        $this->assertDatabaseHas('employee_curso_pending', [
            'document_number' => '910000102',
            'status' => EmployeeCursoPending::STATUS_PENDING,
            'employee_ficha_profile_id' => $entry->profile->id,
            'personal_requisition_ficha_entry_id' => $entry->id,
            'enqueued_by' => $manager->id,
        ]);
    }

    public function test_store_does_not_enqueue_when_document_has_curso_history(): void
    {
        $manager = $this->managerUser();

        EmployeeCurso::factory()->create([
            'document_number' => '910000103',
            'full_name' => 'Con Historial',
        ]);

        $entry = $this->createPendingEntry('910000103', 'Con Historial');

        $this->actingAs($manager)->post(route('gestion-humana.ficha-empleados.employees.store'), array_merge(
            $this->masivosCorePayload([
                'first_surname' => 'Con',
                'first_name' => 'Historial',
            ]),
            [
                'ficha_entry_id' => $entry->id,
                'hired_document' => '910000103',
                'hired_full_name' => 'Con Historial',
                'document_type' => 'C',
            ],
        ))->assertRedirect();

        $this->assertDatabaseMissing('employee_curso_pending', [
            'document_number' => '910000103',
        ]);
    }

    public function test_import_enqueues_on_first_entry_to_ficha_but_not_on_subsequent_update(): void
    {
        $manager = $this->managerUser();
        $importer = app(EmployeeFichaImportService::class);

        $firstPath = $this->makeImportSpreadsheet([
            'cedula' => '910000104',
            'nombre' => 'IMPORT COLA NUEVO',
            'fecha_ingreso' => '2026-01-15',
        ]);

        $firstStats = $importer->import($firstPath, false, $manager->id);
        $this->assertSame(1, $firstStats['imported']);
        $this->assertSame(0, $firstStats['updated']);

        $this->assertDatabaseHas('employee_curso_pending', [
            'document_number' => '910000104',
            'status' => EmployeeCursoPending::STATUS_PENDING,
            'enqueued_by' => $manager->id,
        ]);
        $this->assertDatabaseCount('employee_curso_pending', 1);

        $secondPath = $this->makeImportSpreadsheet([
            'cedula' => '910000104',
            'nombre' => 'IMPORT COLA ACTUALIZADO',
            'fecha_ingreso' => '2026-02-01',
        ]);

        $secondStats = $importer->import($secondPath, false, $manager->id);
        $this->assertSame(0, $secondStats['imported']);
        $this->assertSame(1, $secondStats['updated']);

        $this->assertDatabaseCount('employee_curso_pending', 1);
        $this->assertDatabaseHas('employee_ficha_profiles', [
            'document_number' => '910000104',
            'full_name' => 'IMPORT COLA ACTUALIZADO',
        ]);
    }

    public function test_import_enqueues_when_pending_entry_moves_to_ficha(): void
    {
        $manager = $this->managerUser();
        $entry = $this->createPendingEntry('910000105', 'Pendiente Import');

        $this->assertNull($entry->moved_to_ficha_at);

        $path = $this->makeImportSpreadsheet([
            'cedula' => '910000105',
            'nombre' => 'Pendiente Import Movido',
            'fecha_ingreso' => '2026-03-01',
        ]);

        $stats = app(EmployeeFichaImportService::class)->import($path, false, $manager->id);
        $this->assertSame(1, $stats['imported']);

        $entry->refresh();
        $this->assertNotNull($entry->moved_to_ficha_at);

        $this->assertDatabaseHas('employee_curso_pending', [
            'document_number' => '910000105',
            'status' => EmployeeCursoPending::STATUS_PENDING,
            'personal_requisition_ficha_entry_id' => $entry->id,
            'enqueued_by' => $manager->id,
        ]);
    }

    private function managerUser(): User
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->assignRole('usuario');
        $manager->givePermissionTo('ficha_empleados.manage');

        return $manager;
    }

    private function createPendingEntry(string $document, string $name): PersonalRequisitionFichaEntry
    {
        $requisition = $this->createRequisition('REQ-ENQ-'.substr($document, -4));

        return PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => $document,
            'hired_full_name' => $name,
        ]);
    }

    private function createRequisition(string $code, array $overrides = []): PersonalRequisition
    {
        $requester = User::factory()->create(['must_change_password' => false]);

        return PersonalRequisition::query()->create(array_merge([
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
            'required_profile' => 'Perfil de prueba enqueue cursos.',
            'service_structure' => 'Turno de prueba enqueue cursos.',
            'cost_center' => 'CC-ENQ',
            'status' => PersonalRequisition::STATUS_CONTRATADO,
            'status_changed_at' => now(),
        ], $overrides));
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

        $path = tempnam(sys_get_temp_dir(), 'ficha-curso-enq-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}
