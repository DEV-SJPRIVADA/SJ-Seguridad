<?php

namespace Tests\Feature\GestionHumana;

use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\EmployeeFichaProfile;
use App\Models\PayrollCatalogItem;
use App\Models\PersonalRequisition;
use App\Models\PersonalRequisitionFichaEntry;
use App\Models\ReportesNovedadesVacacion;
use App\Models\RequisitionCity;
use App\Models\RequisitionClient;
use App\Models\RequisitionClientType;
use App\Models\RequisitionPosition;
use App\Models\RequisitionProgrammingType;
use App\Models\RequisitionRequestReason;
use App\Models\RequisitionUniform;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TerminationNovedadesConflictTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_terminate_blocked_when_vacaciones_overlap(): void
    {
        $terminator = $this->terminatorUser();
        $entry = $this->createActiveEntry('9001001001', 'Empleado Cruce Vac');

        ReportesNovedadesVacacion::query()->create([
            'document_number' => '9001001001',
            'employee_name' => 'Empleado Cruce Vac',
            'novedad' => 'VACACIONES DISF',
            'dias_novedad' => 5,
            'fecha_inicio' => '2026-10-01',
            'fecha_fin' => '2026-10-05',
        ]);

        $this->actingAs($terminator)
            ->from(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry))
            ->post(route('gestion-humana.ficha-empleados.employees.ficha.terminate', $entry), [
                'termination_cause_code' => 'RENUNCIA',
                'is_rehireable' => '1',
                'last_work_day' => '2026-10-03',
                'termination_date' => '2026-10-03',
            ])
            ->assertRedirect(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry))
            ->assertSessionHasErrors('novedades_conflict');

        $entry->refresh();
        $this->assertSame(EmployeeFichaProfile::STATUS_ACTIVO, $entry->profile?->employment_status);
        $this->assertNotNull(
            EmployeeFichaEmploymentPeriod::query()
                ->where('personal_requisition_ficha_entry_id', $entry->id)
                ->where('status', EmployeeFichaEmploymentPeriod::STATUS_ACTIVO)
                ->first()
        );
    }

    public function test_terminate_allowed_when_vacaciones_do_not_overlap(): void
    {
        $terminator = $this->terminatorUser();
        $entry = $this->createActiveEntry('9001001002', 'Empleado Sin Cruce');

        ReportesNovedadesVacacion::query()->create([
            'document_number' => '9001001002',
            'employee_name' => 'Empleado Sin Cruce',
            'novedad' => 'VACACIONES DISF',
            'dias_novedad' => 5,
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-01-05',
        ]);

        $this->seedTerminationCause('RENUNCIA');

        $this->actingAs($terminator)
            ->post(route('gestion-humana.ficha-empleados.employees.ficha.terminate', $entry), [
                'termination_cause_code' => 'RENUNCIA',
                'is_rehireable' => '1',
                'last_work_day' => '2026-10-03',
                'termination_date' => '2026-10-03',
            ])
            ->assertRedirect(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry))
            ->assertSessionHasNoErrors();

        $entry->refresh();
        $this->assertSame(EmployeeFichaProfile::STATUS_DESVINCULADO, $entry->profile?->employment_status);
    }

    public function test_vacacion_without_fecha_fin_blocks_after_start(): void
    {
        $terminator = $this->terminatorUser();
        $entry = $this->createActiveEntry('9001001003', 'Empleado Vac abierta');

        ReportesNovedadesVacacion::query()->create([
            'document_number' => '9001001003',
            'employee_name' => 'Empleado Vac abierta',
            'novedad' => 'VACACIONES DISF',
            'dias_novedad' => 10,
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => null,
        ]);

        $this->actingAs($terminator)
            ->from(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry))
            ->post(route('gestion-humana.ficha-empleados.employees.ficha.terminate', $entry), [
                'termination_cause_code' => 'RENUNCIA',
                'is_rehireable' => '0',
                'last_work_day' => '2026-10-01',
                'termination_date' => '2026-10-01',
            ])
            ->assertSessionHasErrors('novedades_conflict');
    }

    public function test_super_admin_can_force_despite_conflict(): void
    {
        $super = User::factory()->create(['must_change_password' => false]);
        $super->assignRole('super-admin');

        $entry = $this->createActiveEntry('9001001004', 'Empleado Force');
        $this->seedTerminationCause('RENUNCIA');

        ReportesNovedadesVacacion::query()->create([
            'document_number' => '9001001004',
            'employee_name' => 'Empleado Force',
            'novedad' => 'VACACIONES COMP',
            'dias_novedad' => 3,
            'fecha_inicio' => '2026-10-01',
            'fecha_fin' => '2026-10-03',
        ]);

        $this->actingAs($super)
            ->post(route('gestion-humana.ficha-empleados.employees.ficha.terminate', $entry), [
                'termination_cause_code' => 'RENUNCIA',
                'is_rehireable' => '1',
                'last_work_day' => '2026-10-02',
                'termination_date' => '2026-10-02',
                'force_novedades_conflict' => '1',
            ])
            ->assertRedirect(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry))
            ->assertSessionHasNoErrors();

        $entry->refresh();
        $this->assertSame(EmployeeFichaProfile::STATUS_DESVINCULADO, $entry->profile?->employment_status);
    }

    public function test_non_super_admin_force_flag_is_ignored(): void
    {
        $terminator = $this->terminatorUser();
        $entry = $this->createActiveEntry('9001001005', 'Empleado No Force');

        ReportesNovedadesVacacion::query()->create([
            'document_number' => '9001001005',
            'employee_name' => 'Empleado No Force',
            'novedad' => 'VACACIONES DISF',
            'dias_novedad' => 2,
            'fecha_inicio' => '2026-10-01',
            'fecha_fin' => '2026-10-02',
        ]);

        $this->actingAs($terminator)
            ->from(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry))
            ->post(route('gestion-humana.ficha-empleados.employees.ficha.terminate', $entry), [
                'termination_cause_code' => 'RENUNCIA',
                'is_rehireable' => '1',
                'last_work_day' => '2026-10-01',
                'termination_date' => '2026-10-01',
                'force_novedades_conflict' => '1',
            ])
            ->assertSessionHasErrors('novedades_conflict');
    }

    private function terminatorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo(['ficha_empleados.manage', 'ficha_empleados.terminate']);

        return $user;
    }

    private function seedTerminationCause(string $code): void
    {
        PayrollCatalogItem::query()->firstOrCreate(
            ['catalog_type' => 'termination_cause', 'code' => $code],
            ['name' => $code, 'sort_order' => 1, 'is_active' => true],
        );
    }

    private function createActiveEntry(string $document, string $name): PersonalRequisitionFichaEntry
    {
        $this->seedTerminationCause('RENUNCIA');

        $mover = User::factory()->create(['must_change_password' => false]);
        $requisition = $this->createRequisition('REQ-CONF-'.uniqid());

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => $document,
            'hired_full_name' => $name,
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $mover->id,
            'created_by' => $mover->id,
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => $document,
            'full_name' => $name,
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'hire_date' => now()->subMonths(6)->toDateString(),
            'position_name' => 'Vigilante',
        ]);

        EmployeeFichaEmploymentPeriod::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'personal_requisition_id' => $requisition->id,
            'sequence' => 1,
            'status' => EmployeeFichaEmploymentPeriod::STATUS_ACTIVO,
            'hire_date' => now()->subMonths(6)->toDateString(),
            'position_name' => 'Vigilante',
            'salary' => 1500000,
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
            'required_profile' => 'Perfil.',
            'service_structure' => 'Turno.',
            'cost_center' => 'CC-1',
            'status' => PersonalRequisition::STATUS_CONTRATADO,
            'status_changed_at' => now(),
        ], $overrides));
    }
}
