<?php

namespace Tests\Feature\GestionHumana;

use App\Models\AuditLog;
use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\EmployeeFichaProfile;
use App\Models\PayrollCatalogItem;
use App\Models\PersonalRequisition;
use App\Models\PersonalRequisitionFichaEntry;
use App\Models\ReportesNovedadesIncapacidad;
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
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class TerminationNovedadesConflictTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
        Config::set('audit.enabled', true);
        Config::set('audit.queue', false);
    }

    public function test_terminate_asks_approval_when_vacaciones_overlap(): void
    {
        $terminator = $this->terminatorUser();
        $entry = $this->createActiveEntry('9001001001', 'Empleado Cruce Vac');
        $this->seedVacacion('9001001001', 'Empleado Cruce Vac', '2026-10-01', '2026-10-05');

        $this->actingAs($terminator)
            ->from(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry))
            ->post(route('gestion-humana.ficha-empleados.employees.ficha.terminate', $entry), [
                'termination_cause_code' => 'RENUNCIA',
                'is_rehireable' => '1',
                'last_work_day' => '2026-10-03',
                'termination_date' => '2026-10-03',
            ])
            ->assertRedirect(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry))
            ->assertSessionHasErrors('novedades_conflict_ack');

        // Ya no bloquea por sí solo el cruce: sin aprobación no se cierra el vínculo.
        $entry->refresh();
        $this->assertSame(EmployeeFichaProfile::STATUS_ACTIVO, $entry->profile?->employment_status);
        $this->assertNotNull($this->activePeriod($entry));
    }

    public function test_terminate_allowed_when_vacaciones_do_not_overlap(): void
    {
        $terminator = $this->terminatorUser();
        $entry = $this->createActiveEntry('9001001002', 'Empleado Sin Cruce');
        $this->seedVacacion('9001001002', 'Empleado Sin Cruce', '2026-01-01', '2026-01-05');

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

        // Sin cruce no se exige aprobación ni se registra el evento dedicado.
        $this->assertDatabaseMissing('audit_logs', [
            'event_type' => 'termination_novedades',
            'action' => 'approve',
        ]);
    }

    public function test_vacacion_without_fecha_fin_also_requires_approval(): void
    {
        $terminator = $this->terminatorUser();
        $entry = $this->createActiveEntry('9001001003', 'Empleado Vac abierta');
        $this->seedVacacion('9001001003', 'Empleado Vac abierta', '2026-09-01', null);

        $this->actingAs($terminator)
            ->from(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry))
            ->post(route('gestion-humana.ficha-empleados.employees.ficha.terminate', $entry), [
                'termination_cause_code' => 'RENUNCIA',
                'is_rehireable' => '0',
                'last_work_day' => '2026-10-01',
                'termination_date' => '2026-10-01',
            ])
            ->assertSessionHasErrors('novedades_conflict_ack');
    }

    public function test_incapacidad_overlap_requires_approval(): void
    {
        $terminator = $this->terminatorUser();
        $entry = $this->createActiveEntry('9001001006', 'Empleado Incap');

        ReportesNovedadesIncapacidad::query()->create([
            'document_number' => '9001001006',
            'employee_name' => 'Empleado Incap',
            'tipo_incapacidad' => 'ACCIDENTE LABORAL',
            'dias' => 10,
            'fecha_inicio' => '2026-10-01',
            'fecha_fin' => '2026-10-10',
        ]);

        $this->actingAs($terminator)
            ->from(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry))
            ->post(route('gestion-humana.ficha-empleados.employees.ficha.terminate', $entry), [
                'termination_cause_code' => 'RENUNCIA',
                'is_rehireable' => '1',
                'last_work_day' => '2026-10-05',
                'termination_date' => '2026-10-05',
            ])
            ->assertSessionHasErrors('novedades_conflict_ack');
    }

    public function test_approval_with_reason_closes_link_and_audits_approver(): void
    {
        $terminator = $this->terminatorUser();
        $entry = $this->createActiveEntry('9001001004', 'Empleado Aprobado');
        $this->seedVacacion('9001001004', 'Empleado Aprobado', '2026-10-01', '2026-10-03');

        $this->actingAs($terminator)
            ->post(route('gestion-humana.ficha-empleados.employees.ficha.terminate', $entry), [
                'termination_cause_code' => 'RENUNCIA',
                'is_rehireable' => '1',
                'last_work_day' => '2026-10-02',
                'termination_date' => '2026-10-02',
                'novedades_conflict_ack' => '1',
                'novedades_conflict_reason' => 'Se confirmó con GH y Nómina el caso.',
            ])
            ->assertRedirect(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry))
            ->assertSessionHasNoErrors();

        $entry->refresh();
        $this->assertSame(EmployeeFichaProfile::STATUS_DESVINCULADO, $entry->profile?->employment_status);

        // Traza dedicada con quién aprobó y qué novedades estaban cruzadas.
        $log = AuditLog::query()
            ->where('event_type', 'termination_novedades')
            ->where('action', 'approve')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($terminator->id, $log->user_id);
        $this->assertSame('SE CONFIRMÓ CON GH Y NÓMINA EL CASO.', $log->reason);
        $this->assertSame($terminator->id, $log->metadata['approved_by_user_id']);
        $this->assertSame($terminator->name, $log->metadata['approved_by_name']);
        $this->assertSame(1, $log->metadata['novedades_conflict_count']);
        $this->assertSame('vacaciones', $log->metadata['novedades_conflicts'][0]['sheet']);
    }

    public function test_live_check_endpoint_reports_overlap_without_blocking(): void
    {
        $terminator = $this->terminatorUser();
        $entry = $this->createActiveEntry('9001001007', 'Empleado Consulta');
        $this->seedVacacion('9001001007', 'Empleado Consulta', '2026-10-01', '2026-10-05');

        $this->actingAs($terminator)
            ->getJson(route('gestion-humana.ficha-empleados.employees.ficha.terminate.novedades', $entry).'?last_work_day=2026-10-03&termination_date=2026-10-03')
            ->assertOk()
            ->assertJsonPath('has_conflicts', true)
            ->assertJsonPath('conflicts.0.sheet', 'vacaciones');

        // Sin solape, el aviso queda vacío.
        $this->actingAs($terminator)
            ->getJson(route('gestion-humana.ficha-empleados.employees.ficha.terminate.novedades', $entry).'?last_work_day=2026-12-01&termination_date=2026-12-01')
            ->assertOk()
            ->assertJsonPath('has_conflicts', false)
            ->assertJsonCount(0, 'conflicts');
    }

    public function test_live_check_endpoint_requires_terminate_permission(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->givePermissionTo(['ficha_empleados.view']);

        $entry = $this->createActiveEntry('9001001008', 'Empleado Sin Permiso');

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.ficha-empleados.employees.ficha.terminate.novedades', $entry).'?last_work_day=2026-10-03&termination_date=2026-10-03')
            ->assertForbidden();
    }

    public function test_approval_without_reason_is_rejected(): void
    {
        $terminator = $this->terminatorUser();
        $entry = $this->createActiveEntry('9001001005', 'Empleado Sin Motivo');
        $this->seedVacacion('9001001005', 'Empleado Sin Motivo', '2026-10-01', '2026-10-02');

        $this->actingAs($terminator)
            ->from(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry))
            ->post(route('gestion-humana.ficha-empleados.employees.ficha.terminate', $entry), [
                'termination_cause_code' => 'RENUNCIA',
                'is_rehireable' => '1',
                'last_work_day' => '2026-10-01',
                'termination_date' => '2026-10-01',
                'novedades_conflict_ack' => '1',
                'novedades_conflict_reason' => '   ',
            ])
            ->assertSessionHasErrors('novedades_conflict_reason');

        $entry->refresh();
        $this->assertSame(EmployeeFichaProfile::STATUS_ACTIVO, $entry->profile?->employment_status);
    }

    private function seedVacacion(string $document, string $name, string $start, ?string $end): void
    {
        ReportesNovedadesVacacion::query()->create([
            'document_number' => $document,
            'employee_name' => $name,
            'novedad' => 'VACACIONES DISF',
            'dias_novedad' => 5,
            'fecha_inicio' => $start,
            'fecha_fin' => $end,
        ]);
    }

    private function activePeriod(PersonalRequisitionFichaEntry $entry): ?EmployeeFichaEmploymentPeriod
    {
        return EmployeeFichaEmploymentPeriod::query()
            ->where('personal_requisition_ficha_entry_id', $entry->id)
            ->where('status', EmployeeFichaEmploymentPeriod::STATUS_ACTIVO)
            ->first();
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
