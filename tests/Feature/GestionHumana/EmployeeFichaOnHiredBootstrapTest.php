<?php

namespace Tests\Feature\GestionHumana;

use App\Models\EmployeeAcreditacionPending;
use App\Models\EmployeeCursoPending;
use App\Models\EmployeeFichaEmploymentPeriod;
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
use App\Services\GestionHumana\EmployeeFichaEmploymentPeriodService;
use App\Services\Requisitions\PersonalRequisitionFichaSync;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeFichaOnHiredBootstrapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_contratado_creates_minimal_profile_and_enqueues_queues_without_period(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $requisition = $this->createRequisition('REQ-BOOT-001', [
            'hired_document' => '88001001',
            'hired_full_name' => 'Bootstrap Uno',
            'hired_first_surname' => 'Bootstrap',
            'hired_first_name' => 'Uno',
        ]);

        app(PersonalRequisitionFichaSync::class)->syncOnUpdate(
            $requisition,
            PersonalRequisition::STATUS_CONTRATADO,
            '88001001',
            'Bootstrap Uno',
            false,
            $user->id,
            'Bootstrap',
            null,
            'Uno',
            null,
        );

        $entry = PersonalRequisitionFichaEntry::query()
            ->where('personal_requisition_id', $requisition->id)
            ->firstOrFail();

        $this->assertNull($entry->moved_to_ficha_at);
        $this->assertNotNull($entry->profile);
        $this->assertSame(EmployeeFichaProfile::STATUS_ACTIVO, $entry->profile->employment_status);
        $this->assertTrue($entry->profile->requires_courses);
        $this->assertTrue($entry->profile->requires_acreditacion);
        $this->assertTrue($entry->profile->requires_psicofisicos);
        $this->assertSame(0, $entry->employmentPeriods()->count());

        $this->assertDatabaseHas('employee_curso_pending', [
            'document_number' => '88001001',
            'status' => EmployeeCursoPending::STATUS_PENDING,
            'employee_ficha_profile_id' => $entry->profile->id,
            'personal_requisition_ficha_entry_id' => $entry->id,
        ]);
        $this->assertDatabaseHas('employee_acreditacion_pending', [
            'document_number' => '88001001',
            'status' => EmployeeAcreditacionPending::STATUS_PENDING,
            'employee_ficha_profile_id' => $entry->profile->id,
            'personal_requisition_ficha_entry_id' => $entry->id,
        ]);

        $this->assertSame(
            0,
            PersonalRequisitionFichaEntry::query()->inFicha()->whereKey($entry->id)->count(),
        );
    }

    public function test_resave_contratado_is_idempotent_for_profile_and_pendings(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $requisition = $this->createRequisition('REQ-BOOT-002');

        $sync = app(PersonalRequisitionFichaSync::class);
        $sync->syncOnUpdate(
            $requisition,
            PersonalRequisition::STATUS_CONTRATADO,
            '88001002',
            'Bootstrap Dos',
            false,
            $user->id,
            'Bootstrap',
            null,
            'Dos',
            null,
        );
        $sync->syncOnUpdate(
            $requisition,
            PersonalRequisition::STATUS_CONTRATADO,
            '88001002',
            'Bootstrap Dos Actualizado',
            false,
            $user->id,
            'Bootstrap',
            null,
            'Dos',
            null,
        );

        $this->assertDatabaseCount('personal_requisition_ficha_entries', 1);
        $this->assertDatabaseCount('employee_ficha_profiles', 1);
        $this->assertSame(1, EmployeeCursoPending::query()->where('document_number', '88001002')->count());
        $this->assertSame(1, EmployeeAcreditacionPending::query()->where('document_number', '88001002')->count());
        $this->assertDatabaseHas('personal_requisition_ficha_entries', [
            'hired_document' => '88001002',
            'hired_full_name' => 'Bootstrap Dos Actualizado',
        ]);
        $this->assertDatabaseHas('employee_ficha_profiles', [
            'document_number' => '88001002',
            'full_name' => 'Bootstrap Dos Actualizado',
        ]);
    }

    public function test_leaving_contratado_removes_pending_entry_profile_and_open_queues(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $requisition = $this->createRequisition('REQ-BOOT-003');
        $sync = app(PersonalRequisitionFichaSync::class);

        $sync->syncOnUpdate(
            $requisition,
            PersonalRequisition::STATUS_CONTRATADO,
            '88001003',
            'Bootstrap Tres',
            false,
            $user->id,
            'Bootstrap',
            null,
            'Tres',
            null,
        );

        $entryId = PersonalRequisitionFichaEntry::query()
            ->where('personal_requisition_id', $requisition->id)
            ->value('id');
        $this->assertNotNull($entryId);

        $sync->syncOnUpdate(
            $requisition,
            PersonalRequisition::STATUS_EN_GESTION,
            '88001003',
            'Bootstrap Tres',
            false,
            $user->id,
        );

        $this->assertDatabaseMissing('personal_requisition_ficha_entries', ['id' => $entryId]);
        $this->assertDatabaseMissing('employee_ficha_profiles', ['document_number' => '88001003']);
        $this->assertDatabaseMissing('employee_curso_pending', [
            'document_number' => '88001003',
            'status' => EmployeeCursoPending::STATUS_PENDING,
        ]);
        $this->assertDatabaseMissing('employee_acreditacion_pending', [
            'document_number' => '88001003',
            'status' => EmployeeAcreditacionPending::STATUS_PENDING,
        ]);

        // Re-contratar debe poder re-encolar (no quedó omitted bloqueante).
        $sync->syncOnUpdate(
            $requisition,
            PersonalRequisition::STATUS_CONTRATADO,
            '88001003',
            'Bootstrap Tres',
            false,
            $user->id,
            'Bootstrap',
            null,
            'Tres',
            null,
        );

        $this->assertDatabaseHas('employee_curso_pending', [
            'document_number' => '88001003',
            'status' => EmployeeCursoPending::STATUS_PENDING,
        ]);
    }

    public function test_rehireable_reingreso_reactivates_profile_keeps_pending_and_is_rehire(): void
    {
        $entry = $this->createInFichaEntryWithActivePeriod();
        $periodService = app(EmployeeFichaEmploymentPeriodService::class);
        $actorId = User::factory()->create(['must_change_password' => false])->id;

        $periodService->closeActivePeriod($entry, [
            'termination_cause_code' => 'FIN_CONTRATO',
            'is_rehireable' => true,
            'last_work_day' => now()->subDays(2)->toDateString(),
            'termination_date' => now()->subDay()->toDateString(),
        ], $actorId);
        $periodService->syncProfileAfterTermination($entry);

        $newRequisition = $this->createRequisition('REQ-BOOT-REHIRE', [
            'hired_document' => $entry->hired_document,
            'hired_full_name' => $entry->hired_full_name,
        ]);

        app(PersonalRequisitionFichaSync::class)->syncOnUpdate(
            $newRequisition,
            PersonalRequisition::STATUS_CONTRATADO,
            $entry->hired_document,
            $entry->hired_full_name,
            false,
            $actorId,
        );

        $entry->refresh()->load(['profile', 'employmentPeriods']);

        $this->assertNull($entry->moved_to_ficha_at);
        $this->assertTrue($entry->isRehirePending());
        $this->assertSame(EmployeeFichaProfile::STATUS_ACTIVO, $entry->profile?->employment_status);
        $this->assertSame($newRequisition->id, $entry->personal_requisition_id);
        $this->assertDatabaseHas('employee_curso_pending', [
            'document_number' => $entry->hired_document,
            'status' => EmployeeCursoPending::STATUS_PENDING,
        ]);
    }

    private function createInFichaEntryWithActivePeriod(): PersonalRequisitionFichaEntry
    {
        $requisition = $this->createRequisition('REQ-BOOT-BASE-'.uniqid());
        $mover = User::factory()->create(['must_change_password' => false]);

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => '88'.random_int(1000000, 9999999),
            'hired_full_name' => 'Empleado Reingreso Boot',
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
            'requires_courses' => true,
            'requires_acreditacion' => true,
            'requires_psicofisicos' => true,
        ]);

        EmployeeFichaEmploymentPeriod::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'personal_requisition_id' => $requisition->id,
            'sequence' => 1,
            'status' => EmployeeFichaEmploymentPeriod::STATUS_ACTIVO,
            'hire_date' => now()->subMonth()->toDateString(),
            'opened_by' => $mover->id,
        ]);

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
            'required_profile' => 'Perfil bootstrap contratado.',
            'service_structure' => 'Turno bootstrap.',
            'cost_center' => 'CC-BOOT',
            'status' => PersonalRequisition::STATUS_CONTRATADO,
            'status_changed_at' => now(),
        ], $overrides));
    }
}
