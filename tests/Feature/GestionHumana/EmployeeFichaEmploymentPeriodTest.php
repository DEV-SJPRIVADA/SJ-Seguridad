<?php

namespace Tests\Feature\GestionHumana;

use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\EmployeeFichaProfile;
use App\Models\PayrollCatalogItem;
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
use App\Rules\Requisitions\HiredDocumentNotDuplicated;
use App\Services\Access\FichaEmpleadosAccessService;
use App\Services\GestionHumana\EmployeeFichaEmploymentPeriodService;
use App\Services\Requisitions\PersonalRequisitionFichaSync;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeFichaEmploymentPeriodTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_can_terminate_requires_dedicated_permission(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $terminator = User::factory()->create(['must_change_password' => false]);
        $terminator->givePermissionTo(['ficha_empleados.manage', 'ficha_empleados.terminate']);

        $access = app(FichaEmpleadosAccessService::class);

        $this->assertFalse($access->canTerminate($manager));
        $this->assertTrue($access->canTerminate($terminator));
    }

    public function test_terminate_closes_active_period_and_marks_profile_desvinculado(): void
    {
        $terminator = User::factory()->create(['must_change_password' => false]);
        $terminator->givePermissionTo(['ficha_empleados.manage', 'ficha_empleados.terminate']);

        $entry = $this->createInFichaEntry(withActivePeriod: true);

        $this->actingAs($terminator)
            ->post(route('gestion-humana.ficha-empleados.employees.ficha.terminate', $entry), [
                'termination_cause_code' => 'RENUNCIA',
                'is_rehireable' => '1',
                'last_work_day' => now()->subDay()->toDateString(),
                'termination_date' => now()->toDateString(),
                'termination_notes' => 'Prueba desvinculacion',
            ])
            ->assertRedirect(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry));

        $entry->load('profile');

        $this->assertSame(EmployeeFichaProfile::STATUS_DESVINCULADO, $entry->profile->employment_status);
        $this->assertDatabaseMissing('employee_ficha_employment_periods', [
            'personal_requisition_ficha_entry_id' => $entry->id,
            'status' => EmployeeFichaEmploymentPeriod::STATUS_ACTIVO,
        ]);
        $this->assertDatabaseHas('employee_ficha_employment_periods', [
            'personal_requisition_ficha_entry_id' => $entry->id,
            'status' => EmployeeFichaEmploymentPeriod::STATUS_CERRADO,
            'termination_cause_code' => 'RENUNCIA',
            'is_rehireable' => 1,
        ]);
    }

    public function test_manager_without_terminate_permission_cannot_desvincular(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $entry = $this->createInFichaEntry(withActivePeriod: true);

        $this->actingAs($manager)
            ->post(route('gestion-humana.ficha-empleados.employees.ficha.terminate', $entry), [
                'termination_cause_code' => 'RENUNCIA',
                'is_rehireable' => '1',
                'last_work_day' => now()->subDay()->toDateString(),
                'termination_date' => now()->toDateString(),
            ])
            ->assertForbidden();
    }

    public function test_rehire_sync_puts_recontratable_employee_back_to_pending(): void
    {
        $entry = $this->createInFichaEntry(withActivePeriod: true);

        $periodService = app(EmployeeFichaEmploymentPeriodService::class);
        $periodService->closeActivePeriod($entry, [
            'termination_cause_code' => 'FIN_CONTRATO',
            'is_rehireable' => true,
            'last_work_day' => now()->subDays(2)->toDateString(),
            'termination_date' => now()->subDay()->toDateString(),
        ], User::factory()->create()->id);
        $periodService->syncProfileAfterTermination($entry);

        $newRequisition = $this->createRequisition('REQ-REHIRE-001', [
            'hired_document' => $entry->hired_document,
            'hired_full_name' => $entry->hired_full_name,
        ]);

        app(PersonalRequisitionFichaSync::class)->syncOnUpdate(
            $newRequisition,
            PersonalRequisition::STATUS_CONTRATADO,
            $entry->hired_document,
            $entry->hired_full_name,
            false,
            User::factory()->create()->id,
        );

        $entry->refresh();

        $this->assertNull($entry->moved_to_ficha_at);
        $this->assertTrue($entry->isRehirePending());
        $this->assertSame($newRequisition->id, $entry->personal_requisition_id);
    }

    public function test_non_rehireable_employee_blocks_duplicate_document_on_requisition(): void
    {
        $entry = $this->createInFichaEntry(withActivePeriod: true);

        $periodService = app(EmployeeFichaEmploymentPeriodService::class);
        $periodService->closeActivePeriod($entry, [
            'termination_cause_code' => 'DESPIDO',
            'is_rehireable' => false,
            'last_work_day' => now()->subDays(2)->toDateString(),
            'termination_date' => now()->subDay()->toDateString(),
        ], User::factory()->create()->id);
        $periodService->syncProfileAfterTermination($entry);

        $this->createRequisition('REQ-REHIRE-BLOCK', [
            'hired_document' => $entry->hired_document,
            'hired_full_name' => $entry->hired_full_name,
        ]);

        $validator = validator(
            ['hired_document' => $entry->hired_document],
            ['hired_document' => [new HiredDocumentNotDuplicated(999, false, null)]],
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('REHIRE_NOT_ALLOWED', $validator->errors()->first('hired_document'));
    }

    private function createInFichaEntry(bool $withActivePeriod = false): PersonalRequisitionFichaEntry
    {
        $requisition = $this->createRequisition('REQ-PERIOD-'.uniqid());
        $mover = User::factory()->create(['must_change_password' => false]);

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => '10'.random_int(10000000, 99999999),
            'hired_full_name' => 'Empleado Periodo Test',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $mover->id,
            'created_by' => $mover->id,
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => $entry->hired_document,
            'full_name' => $entry->hired_full_name,
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'hire_date' => now()->subMonth()->toDateString(),
            'position_name' => 'Vigilante',
        ]);

        if ($withActivePeriod) {
            EmployeeFichaEmploymentPeriod::query()->create([
                'personal_requisition_ficha_entry_id' => $entry->id,
                'personal_requisition_id' => $requisition->id,
                'sequence' => 1,
                'status' => EmployeeFichaEmploymentPeriod::STATUS_ACTIVO,
                'hire_date' => now()->subMonth()->toDateString(),
                'position_name' => 'Vigilante',
                'opened_by' => $mover->id,
            ]);
        }

        PayrollCatalogItem::query()->firstOrCreate(
            ['catalog_type' => 'termination_cause', 'code' => 'RENUNCIA'],
            ['name' => 'Renuncia voluntaria', 'sort_order' => 1, 'is_active' => true],
        );

        PayrollCatalogItem::query()->firstOrCreate(
            ['catalog_type' => 'termination_cause', 'code' => 'FIN_CONTRATO'],
            ['name' => 'Fin de contrato', 'sort_order' => 2, 'is_active' => true],
        );

        PayrollCatalogItem::query()->firstOrCreate(
            ['catalog_type' => 'termination_cause', 'code' => 'DESPIDO'],
            ['name' => 'Despido', 'sort_order' => 3, 'is_active' => true],
        );

        return $entry->fresh(['profile']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
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
            'required_profile' => 'Perfil de prueba.',
            'service_structure' => 'Turno de prueba.',
            'cost_center' => 'CC-FICHA',
            'status' => PersonalRequisition::STATUS_CONTRATADO,
            'status_changed_at' => now(),
        ], $overrides));
    }
}
