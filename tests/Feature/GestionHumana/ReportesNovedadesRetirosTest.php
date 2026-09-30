<?php

namespace Tests\Feature\GestionHumana;

use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\EmployeeFichaProfile;
use App\Models\EmployeeTerminationFollowup;
use App\Models\PayrollCatalogItem;
use App\Models\PersonalRequisition;
use App\Models\PersonalRequisitionFichaEntry;
use App\Models\ReportesNovedadesRetiro;
use App\Models\RequisitionCity;
use App\Models\RequisitionClient;
use App\Models\RequisitionClientType;
use App\Models\RequisitionPosition;
use App\Models\RequisitionProgrammingType;
use App\Models\RequisitionRequestReason;
use App\Models\RequisitionUniform;
use App\Models\User;
use App\Services\GestionHumana\EmployeeTerminationFollowupService;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ReportesNovedadesRetirosTest extends TestCase
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

    public function test_ensure_for_closed_period_creates_retiro_idempotently(): void
    {
        $entry = $this->createActiveEntry('Retiro Auto');
        $this->terminateViaFicha($entry);

        $period = EmployeeFichaEmploymentPeriod::query()
            ->where('personal_requisition_ficha_entry_id', $entry->id)
            ->where('status', EmployeeFichaEmploymentPeriod::STATUS_CERRADO)
            ->firstOrFail();

        $followup = EmployeeTerminationFollowup::query()
            ->where('employee_ficha_employment_period_id', $period->id)
            ->firstOrFail();

        $retiro = ReportesNovedadesRetiro::query()
            ->where('employee_termination_followup_id', $followup->id)
            ->first();

        $this->assertNotNull($retiro);
        $this->assertSame((string) $followup->document_number, $retiro->document_number);
        $this->assertSame((string) $followup->full_name, $retiro->employee_name);
        $this->assertSame('RETIRO', $retiro->novedad);
        $this->assertSame('RENUNCIA', $retiro->motivo_retiro);
        $this->assertNull($retiro->observacion_nomina);

        app(EmployeeTerminationFollowupService::class)->ensureForClosedPeriod(
            $period,
            $entry->fresh(),
            null,
        );

        $this->assertSame(1, ReportesNovedadesRetiro::query()
            ->where('employee_termination_followup_id', $followup->id)
            ->count());
    }

    public function test_revert_soft_deletes_linked_retiro(): void
    {
        $entry = $this->createActiveEntry('Retiro Revert');
        $this->terminateViaFicha($entry);

        $followup = EmployeeTerminationFollowup::query()
            ->where('document_number', $entry->hired_document)
            ->firstOrFail();

        $retiro = ReportesNovedadesRetiro::query()
            ->where('employee_termination_followup_id', $followup->id)
            ->firstOrFail();

        $editor = User::factory()->create(['must_change_password' => false]);
        $editor->givePermissionTo([
            'desvinculaciones.view',
            'desvinculaciones.seguimientos.edit',
            'view.board.gestion_humana.desvinculaciones',
        ]);

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.desvinculaciones.seguimientos.revert', $followup), [
                'reason' => 'Corrección de prueba FEAT-040',
            ])
            ->assertOk();

        $this->assertSoftDeleted('reportes_novedades_retiros', ['id' => $retiro->id]);
        $this->assertSame(0, ReportesNovedadesRetiro::query()->count());

        $viewer = $this->editorUser();
        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.reportes-novedades.retiros.datatable'))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 0);
    }

    public function test_manual_crud_review_ownership_and_export(): void
    {
        $editor = $this->editorUser();
        $reviewer = $this->reviewerUser();

        $this->actingAs($editor)
            ->get(route('gestion-humana.reportes-novedades.retiros'))
            ->assertOk()
            ->assertSee('Retiros', false)
            ->assertDontSee('listado y formularios en preparación', false);

        $this->actingAs($editor)
            ->post(route('gestion-humana.reportes-novedades.retiros.store'), [
                'document_number' => '7007007007',
                'employee_name' => 'Manual Retiro',
                'cargo' => 'Guardia',
                'destino' => 'Cliente C',
                'novedad' => 'RETIRO',
                'fecha_retiro' => '2026-09-20',
                'motivo_retiro' => 'RENUNCIA',
                'observaciones' => 'Manual',
            ])
            ->assertRedirect(route('gestion-humana.reportes-novedades.retiros'));

        $row = ReportesNovedadesRetiro::query()->where('document_number', '7007007007')->firstOrFail();
        $this->assertNull($row->employee_termination_followup_id);
        $this->assertNull($row->observacion_nomina);

        $this->actingAs($editor)
            ->patch(route('gestion-humana.reportes-novedades.retiros.update', $row), [
                'document_number' => '7007007007',
                'employee_name' => 'Manual Editado',
                'novedad' => 'RETIRO',
                'fecha_retiro' => '2026-09-21',
                'motivo_retiro' => 'FALLECIMIENTO',
                'observacion_nomina' => 'No debe',
            ])
            ->assertRedirect();

        $row->refresh();
        $this->assertSame('Manual Editado', $row->employee_name);
        $this->assertSame('FALLECIMIENTO', $row->motivo_retiro);
        $this->assertNull($row->observacion_nomina);

        $this->actingAs($reviewer)
            ->patch(route('gestion-humana.reportes-novedades.retiros.review', $row), [
                'observacion_nomina' => 'OK',
                'employee_name' => 'Hack',
            ])
            ->assertRedirect();

        $row->refresh();
        $this->assertSame('OK', $row->observacion_nomina);
        $this->assertSame('Manual Editado', $row->employee_name);

        $this->actingAs($reviewer)
            ->get(route('gestion-humana.reportes-novedades.retiros.export'))
            ->assertOk();

        $this->actingAs($reviewer)
            ->post(route('gestion-humana.reportes-novedades.retiros.store'), [
                'document_number' => '1',
                'employee_name' => 'X',
                'novedad' => 'RETIRO',
            ])
            ->assertForbidden();

        $this->actingAs($editor)
            ->delete(route('gestion-humana.reportes-novedades.retiros.destroy', $row))
            ->assertRedirect();

        $this->assertSoftDeleted('reportes_novedades_retiros', ['id' => $row->id]);
    }

    public function test_invalid_motivo_retiro_is_rejected(): void
    {
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->from(route('gestion-humana.reportes-novedades.retiros'))
            ->post(route('gestion-humana.reportes-novedades.retiros.store'), [
                'document_number' => '8008008008',
                'employee_name' => 'Bad Motivo',
                'novedad' => 'RETIRO',
                'motivo_retiro' => 'MOTIVO_FALSO',
            ])
            ->assertSessionHasErrors('motivo_retiro');
    }

    private function editorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('reportes_novedades.retiros.edit');

        return $user;
    }

    private function reviewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('reportes_novedades.retiros.review');

        return $user;
    }

    private function terminateViaFicha(PersonalRequisitionFichaEntry $entry): void
    {
        $this->actingAs($this->terminatorUser())
            ->post(route('gestion-humana.ficha-empleados.employees.ficha.terminate', $entry), [
                'termination_cause_code' => 'RENUNCIA',
                'is_rehireable' => '1',
                'last_work_day' => now()->subDay()->toDateString(),
                'termination_date' => now()->toDateString(),
            ])
            ->assertRedirect();
    }

    private function terminatorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo(['ficha_empleados.manage', 'ficha_empleados.terminate']);

        return $user;
    }

    private function createActiveEntry(string $fullName = 'Empleado Retiro'): PersonalRequisitionFichaEntry
    {
        $requisition = $this->createRequisition('REQ-RN-'.uniqid());
        $mover = User::factory()->create(['must_change_password' => false]);

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => '30'.random_int(10000000, 99999999),
            'hired_full_name' => $fullName,
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

        EmployeeFichaEmploymentPeriod::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'personal_requisition_id' => $requisition->id,
            'sequence' => 1,
            'status' => EmployeeFichaEmploymentPeriod::STATUS_ACTIVO,
            'hire_date' => now()->subMonth()->toDateString(),
            'position_name' => 'Vigilante',
            'salary' => 1500000,
            'contract_type_name' => 'Termino indefinido',
            'work_center_name' => 'Bogota',
            'opened_by' => $mover->id,
        ]);

        foreach (config('employee_ficha.termination_cause_defaults', []) as $index => $row) {
            $code = (string) ($row['code'] ?? '');
            if ($code === '') {
                continue;
            }

            PayrollCatalogItem::query()->firstOrCreate(
                ['catalog_type' => 'termination_cause', 'code' => $code],
                [
                    'name' => (string) ($row['name'] ?? $code),
                    'sort_order' => (int) ($row['sort_order'] ?? ($index + 1)),
                    'is_active' => true,
                ],
            );
        }

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
            'cost_center' => 'CC-SEG',
            'status' => PersonalRequisition::STATUS_CONTRATADO,
            'status_changed_at' => now(),
        ], $overrides));
    }
}
