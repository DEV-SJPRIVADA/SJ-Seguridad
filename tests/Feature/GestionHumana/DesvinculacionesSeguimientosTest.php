<?php

namespace Tests\Feature\GestionHumana;

use App\Models\AuditLog;
use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\EmployeeFichaProfile;
use App\Models\EmployeeTerminationFollowup;
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
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DesvinculacionesSeguimientosTest extends TestCase
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

    public function test_ok_todo_is_false_with_seven_checks_and_true_with_eight(): void
    {
        $followup = $this->createFollowup();

        $fields = EmployeeTerminationFollowup::CHECK_FIELDS;
        foreach ($fields as $index => $field) {
            $followup->{$field} = $index < 7;
        }
        $followup->save();
        $followup->refresh();

        $this->assertFalse($followup->isOkTodo());
        $this->assertFalse($followup->ok_todo);

        $followup->{end($fields)} = true;
        $followup->save();
        $followup->refresh();

        $this->assertTrue($followup->isOkTodo());
        $this->assertTrue($followup->ok_todo);
    }

    public function test_seguimientos_datatable_returns_rows_ordered_by_id_desc(): void
    {
        $viewer = $this->viewerUser();
        $older = $this->createFollowup(['document_number' => '1111111111', 'full_name' => 'Primero']);
        $newer = $this->createFollowup(['document_number' => '2222222222', 'full_name' => 'Segundo']);

        $response = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.desvinculaciones.seguimientos.datatable', [
                'status' => 'todos',
            ]));

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertSame([$newer->id, $older->id], array_slice($ids, 0, 2));
    }

    public function test_seguimientos_datatable_filters_ok_todo_and_search(): void
    {
        $viewer = $this->viewerUser();
        $incomplete = $this->createFollowup([
            'document_number' => '3333333333',
            'full_name' => 'Incompleto Perez',
        ]);
        $complete = $this->createFollowup([
            'document_number' => '4444444444',
            'full_name' => 'Completo Gomez',
        ]);
        $complete->forceFill(array_fill_keys(EmployeeTerminationFollowup::CHECK_FIELDS, true))->save();

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.desvinculaciones.seguimientos.datatable', [
                'status' => 'ok_todo',
            ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.id', $complete->id);

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.desvinculaciones.seguimientos.datatable', [
                'q' => 'Incompleto',
                'status' => 'todos',
            ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.id', $incomplete->id);
    }

    public function test_patch_autosave_persists_check_and_audits(): void
    {
        $editor = $this->editorUser();
        $followup = $this->createFollowup();

        $this->assertFalse($followup->check_enviado);

        $this->actingAs($editor)
            ->patchJson(route('gestion-humana.desvinculaciones.seguimientos.update', $followup), [
                'check_enviado' => true,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('followup.checks.check_enviado', true)
            ->assertJsonPath('followup.ok_todo', false);

        $followup->refresh();
        $this->assertTrue($followup->check_enviado);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'desvinculaciones',
            'event_type' => 'termination_followup',
            'action' => 'update',
        ]);

        $audit = AuditLog::query()
            ->where('event_type', 'termination_followup')
            ->where('action', 'update')
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame(false, data_get($audit->old_values, 'check_enviado'));
        $this->assertSame(true, data_get($audit->new_values, 'check_enviado'));
    }

    public function test_patch_payroll_delivered_at_persists(): void
    {
        $editor = $this->editorUser();
        $followup = $this->createFollowup();
        $date = now()->toDateString();

        $this->actingAs($editor)
            ->patchJson(route('gestion-humana.desvinculaciones.seguimientos.update', $followup), [
                'payroll_delivered_at' => $date,
            ])
            ->assertOk()
            ->assertJsonPath('followup.payroll_delivered_at', $date);

        $followup->refresh();
        $this->assertSame($date, $followup->payroll_delivered_at?->toDateString());
    }

    public function test_patch_without_edit_permission_is_forbidden(): void
    {
        $viewer = $this->viewerUser();
        $followup = $this->createFollowup();

        $this->actingAs($viewer)
            ->patchJson(route('gestion-humana.desvinculaciones.seguimientos.update', $followup), [
                'check_enviado' => true,
            ])
            ->assertForbidden();

        $followup->refresh();
        $this->assertFalse($followup->check_enviado);
    }

    public function test_seguimientos_view_is_readonly_without_edit_permission(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.desvinculaciones.seguimientos'))
            ->assertOk()
            ->assertSee('Vista de solo lectura', false);
    }

    public function test_revert_reactivates_employee_deletes_followup_and_letter(): void
    {
        Storage::fake('local');

        $editor = $this->editorUser();
        $followup = $this->createFollowup();
        $period = EmployeeFichaEmploymentPeriod::query()->findOrFail($followup->employee_ficha_employment_period_id);
        $entry = PersonalRequisitionFichaEntry::query()->findOrFail($followup->personal_requisition_ficha_entry_id);

        $letterPath = 'termination-letters/test-revert.docx';
        Storage::disk('local')->put($letterPath, 'fake-letter');
        $period->forceFill([
            'termination_letter_path' => $letterPath,
            'termination_letter_type' => 'docx',
        ])->save();
        $followup->forceFill(['letter_generated' => true])->save();

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.desvinculaciones.seguimientos.revert', $followup), [
                'reason' => 'Error de digitacion en la causal',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseMissing('employee_termination_followups', ['id' => $followup->id]);

        $period->refresh();
        $this->assertSame(EmployeeFichaEmploymentPeriod::STATUS_ACTIVO, $period->status);
        $this->assertNull($period->termination_date);
        $this->assertNull($period->termination_letter_path);
        $this->assertNull($period->closed_by);

        $entry->load('profile');
        $this->assertSame(EmployeeFichaProfile::STATUS_ACTIVO, $entry->profile?->employment_status);
        $this->assertNull($entry->profile?->termination_date);

        Storage::disk('local')->assertMissing($letterPath);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'desvinculaciones',
            'event_type' => 'termination_followup',
            'action' => 'revert',
        ]);

        $audit = AuditLog::query()
            ->where('event_type', 'termination_followup')
            ->where('action', 'revert')
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame('Error de digitacion en la causal', $audit->reason);
    }

    public function test_revert_without_edit_permission_is_forbidden(): void
    {
        $viewer = $this->viewerUser();
        $followup = $this->createFollowup();

        $this->actingAs($viewer)
            ->postJson(route('gestion-humana.desvinculaciones.seguimientos.revert', $followup), [
                'reason' => 'Intento sin permiso suficiente',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('employee_termination_followups', ['id' => $followup->id]);
    }

    public function test_revert_requires_reason(): void
    {
        $editor = $this->editorUser();
        $followup = $this->createFollowup();

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.desvinculaciones.seguimientos.revert', $followup), [
                'reason' => '',
            ])
            ->assertStatus(422);

        $this->assertDatabaseHas('employee_termination_followups', ['id' => $followup->id]);
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'desvinculaciones.view',
            'view.board.gestion_humana.desvinculaciones',
        ]);

        return $user;
    }

    private function editorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'desvinculaciones.view',
            'desvinculaciones.seguimientos.edit',
            'view.board.gestion_humana.desvinculaciones',
        ]);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createFollowup(array $attributes = []): EmployeeTerminationFollowup
    {
        $entry = $this->createActiveEntry($attributes['full_name'] ?? 'Empleado Seguimiento');
        $this->terminateViaFicha($entry);

        $period = EmployeeFichaEmploymentPeriod::query()
            ->where('personal_requisition_ficha_entry_id', $entry->id)
            ->where('status', EmployeeFichaEmploymentPeriod::STATUS_CERRADO)
            ->firstOrFail();

        $followup = EmployeeTerminationFollowup::query()
            ->where('employee_ficha_employment_period_id', $period->id)
            ->firstOrFail();

        if ($attributes !== []) {
            $followup->fill($attributes)->save();
        }

        return $followup->fresh();
    }

    private function terminatorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo(['ficha_empleados.manage', 'ficha_empleados.terminate']);

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

    private function createActiveEntry(string $fullName = 'Empleado Seguimiento'): PersonalRequisitionFichaEntry
    {
        $requisition = $this->createRequisition('REQ-SEG-'.uniqid());
        $mover = User::factory()->create(['must_change_password' => false]);

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => '20'.random_int(10000000, 99999999),
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
