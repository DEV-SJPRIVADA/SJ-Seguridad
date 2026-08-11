<?php

namespace Tests\Feature\Requisitions;

use App\Models\AuditLog;
use App\Models\CommercialClient;
use App\Models\PersonalRequisition;
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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RequisitionAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        Config::set('audit.enabled', true);
        Config::set('audit.queue', false);
        Mail::fake();
    }

    public function test_store_writes_single_create_audit_event_for_batch(): void
    {
        $user = $this->requesterUser();

        $payload = $this->validPayload();
        $payload['quantity'] = 3;

        $this->actingAs($user)->post(route('requisitions.store', ['module' => 'operaciones']), $payload);

        $this->assertSame(1, AuditLog::query()
            ->where('module', 'requisitions')
            ->where('event_type', 'requisition')
            ->where('action', 'create')
            ->count());

        $log = AuditLog::query()
            ->where('module', 'requisitions')
            ->where('action', 'create')
            ->firstOrFail();

        $this->assertSame('gestion_humana', $log->area);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame(3, $log->metadata['batch_size']);
        $this->assertSame('operaciones', $log->metadata['requesting_area_key']);
        $this->assertSame(PersonalRequisition::STATUS_SOLICITADA, $log->metadata['initial_status']);
        $this->assertCount(3, $log->metadata['codes']);
        $this->assertSame(PersonalRequisition::class, $log->auditable_type);
    }

    public function test_update_status_change_writes_audit_event(): void
    {
        [$manager, $requisition] = $this->createManagerAndRequisition('REQ-2026-AUD-01');
        $requisition->statusLogs()->create([
            'from_status' => null,
            'to_status' => PersonalRequisition::STATUS_SOLICITADA,
            'changed_by' => $requisition->requested_by,
        ]);

        $this->actingAs($manager)->patch(
            route('requisitions.update', ['module' => 'operaciones', 'requisition' => $requisition]),
            array_merge($this->validPayload(), [
                'status' => PersonalRequisition::STATUS_EN_GESTION,
                'human_resources_observation' => 'En gestion.',
            ])
        );

        $log = AuditLog::query()
            ->where('module', 'requisitions')
            ->where('event_type', 'requisition')
            ->where('action', 'status_change')
            ->firstOrFail();

        $this->assertSame(['status' => PersonalRequisition::STATUS_SOLICITADA], $log->old_values);
        $this->assertSame(['status' => PersonalRequisition::STATUS_EN_GESTION], $log->new_values);
        $this->assertSame($requisition->code, $log->metadata['code']);
        $this->assertSame('Solicitada', $log->metadata['from_label']);
        $this->assertSame('En gestion', $log->metadata['to_label']);
    }

    public function test_update_without_status_change_does_not_write_status_change_audit(): void
    {
        [$manager, $requisition] = $this->createManagerAndRequisition('REQ-2026-AUD-02');

        $this->actingAs($manager)->patch(
            route('requisitions.update', ['module' => 'operaciones', 'requisition' => $requisition]),
            array_merge($this->validPayload(), [
                'status' => PersonalRequisition::STATUS_SOLICITADA,
                'quantity' => 4,
                'human_resources_observation' => 'Solo ajuste de cantidad.',
            ])
        );

        $this->assertDatabaseMissing('audit_logs', [
            'module' => 'requisitions',
            'event_type' => 'requisition',
            'action' => 'status_change',
        ]);
    }

    public function test_update_without_status_change_still_writes_change_logs(): void
    {
        [$manager, $requisition] = $this->createManagerAndRequisition('REQ-2026-AUD-03');

        $this->actingAs($manager)->patch(
            route('requisitions.update', ['module' => 'operaciones', 'requisition' => $requisition]),
            array_merge($this->validPayload(), [
                'status' => PersonalRequisition::STATUS_SOLICITADA,
                'quantity' => 5,
            ])
        );

        $this->assertDatabaseHas('personal_requisition_change_logs', [
            'personal_requisition_id' => $requisition->id,
            'field_key' => 'quantity',
            'old_value' => '1',
            'new_value' => '5',
            'changed_by' => $manager->id,
        ]);
        $this->assertDatabaseCount('personal_requisition_status_logs', 0);
    }

    public function test_update_status_change_still_writes_status_logs(): void
    {
        [$manager, $requisition] = $this->createManagerAndRequisition('REQ-2026-AUD-04');
        $requisition->statusLogs()->create([
            'from_status' => null,
            'to_status' => PersonalRequisition::STATUS_SOLICITADA,
            'changed_by' => $requisition->requested_by,
        ]);

        $this->actingAs($manager)->patch(
            route('requisitions.update', ['module' => 'operaciones', 'requisition' => $requisition]),
            array_merge($this->validPayload(), [
                'status' => PersonalRequisition::STATUS_EN_GESTION,
            ])
        );

        $this->assertDatabaseHas('personal_requisition_status_logs', [
            'personal_requisition_id' => $requisition->id,
            'from_status' => PersonalRequisition::STATUS_SOLICITADA,
            'to_status' => PersonalRequisition::STATUS_EN_GESTION,
            'changed_by' => $manager->id,
        ]);
    }

    public function test_export_excel_writes_manage_excel_audit(): void
    {
        $requester = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $requester->assignRole('usuario');

        $manager = User::factory()->create([
            'area_key' => 'gestion_humana',
            'must_change_password' => false,
        ]);
        $manager->assignRole('usuario');
        $manager->givePermissionTo([
            'view.board.operaciones.requisiciones',
            'requisitions.tab.gestion',
        ]);

        PersonalRequisition::create($this->requisitionAttributes($requester, 'REQ-2026-AUD-EXP-1', 'operaciones', 'Export A'));
        PersonalRequisition::create(array_merge(
            $this->requisitionAttributes($requester, 'REQ-2026-AUD-EXP-2', 'operaciones', 'Export B'),
            ['status' => PersonalRequisition::STATUS_EN_GESTION]
        ));

        $this->actingAs($manager)->get(route('requisitions.export', [
            'module' => 'operaciones',
            'status' => PersonalRequisition::STATUS_SOLICITADA,
        ]))->assertOk();

        $log = AuditLog::query()
            ->where('module', 'requisitions')
            ->where('event_type', 'export')
            ->where('action', 'manage_excel')
            ->firstOrFail();

        $this->assertSame('operaciones', $log->metadata['module']);
        $this->assertSame(1, $log->metadata['row_count']);
        $this->assertSame(PersonalRequisition::STATUS_SOLICITADA, $log->metadata['filters']['status']);
        $this->assertNotEmpty($log->metadata['filter_hash']);
    }

    public function test_tracking_export_writes_tracking_excel_audit_with_mine_only(): void
    {
        $requester = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $requester->assignRole('usuario');
        $requester->givePermissionTo('requisitions.tab.seguimiento');

        $otherUser = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $otherUser->assignRole('usuario');

        PersonalRequisition::create($this->requisitionAttributes($requester, 'REQ-2026-AUD-TRK-1', 'operaciones', 'Mine'));
        PersonalRequisition::create($this->requisitionAttributes($otherUser, 'REQ-2026-AUD-TRK-2', 'operaciones', 'Other'));

        $this->actingAs($requester)->get(route('requisitions.tracking.export', [
            'module' => 'operaciones',
            'mine_only' => 1,
        ]))->assertOk();

        $log = AuditLog::query()
            ->where('module', 'requisitions')
            ->where('event_type', 'export')
            ->where('action', 'tracking_excel')
            ->firstOrFail();

        $this->assertSame('operaciones', $log->metadata['module']);
        $this->assertSame(1, $log->metadata['row_count']);
        $this->assertTrue($log->metadata['filters']['mine_only']);
    }

    public function test_management_approval_web_approve_writes_audit(): void
    {
        PermissionCatalog::sync();

        $approver = User::factory()->create([
            'area_key' => 'gestion_humana',
            'must_change_password' => false,
        ]);
        $approver->assignRole('administrador');

        $requester = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $requester->assignRole('usuario');

        $requisition = PersonalRequisition::create(array_merge(
            $this->requisitionAttributes($requester, 'REQ-2026-AUD-APP-1', 'operaciones', 'Approve audit'),
            ['status' => PersonalRequisition::STATUS_PENDIENTE_AUTORIZACION_GERENCIA]
        ));

        $this->actingAs($approver)->post(route('requisitions.management-approval.decide', [
            'module' => 'gestion_humana',
            'requisition' => $requisition,
        ]), [
            'action' => 'approve',
            'comment' => 'Autorizado por gerencia.',
        ])->assertRedirect();

        $log = AuditLog::query()
            ->where('module', 'requisitions')
            ->where('event_type', 'management_approval')
            ->where('action', 'approve')
            ->firstOrFail();

        $this->assertSame($approver->id, $log->user_id);
        $this->assertSame($requisition->code, $log->metadata['code']);
        $this->assertSame('web', $log->metadata['channel']);
        $this->assertStringContainsString('Autorizado por gerencia', $log->metadata['comment']);
    }

    public function test_management_approval_web_reject_writes_audit(): void
    {
        PermissionCatalog::sync();

        $approver = User::factory()->create([
            'area_key' => 'gestion_humana',
            'must_change_password' => false,
        ]);
        $approver->assignRole('administrador');

        $requester = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $requester->assignRole('usuario');

        $requisition = PersonalRequisition::create(array_merge(
            $this->requisitionAttributes($requester, 'REQ-2026-AUD-REJ-1', 'operaciones', 'Reject audit'),
            ['status' => PersonalRequisition::STATUS_PENDIENTE_AUTORIZACION_GERENCIA]
        ));

        $this->actingAs($approver)->post(route('requisitions.management-approval.decide', [
            'module' => 'gestion_humana',
            'requisition' => $requisition,
        ]), [
            'action' => 'reject',
            'comment' => 'Sin presupuesto.',
        ])->assertRedirect();

        $log = AuditLog::query()
            ->where('module', 'requisitions')
            ->where('event_type', 'management_approval')
            ->where('action', 'reject')
            ->firstOrFail();

        $this->assertSame('web', $log->metadata['channel']);
        $this->assertSame('Sin presupuesto.', $log->metadata['comment']);
    }

    public function test_management_approval_email_writes_audit_with_resolved_user(): void
    {
        PermissionCatalog::sync();

        $approver = User::factory()->create([
            'area_key' => 'gestion_humana',
            'must_change_password' => false,
        ]);
        $approver->assignRole('administrador');
        $approver->givePermissionTo('requisitions.approve.management');

        $requester = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $requester->assignRole('usuario');

        $requisition = PersonalRequisition::create(array_merge(
            $this->requisitionAttributes($requester, 'REQ-2026-AUD-EML-1', 'operaciones', 'Email audit'),
            ['status' => PersonalRequisition::STATUS_PENDIENTE_AUTORIZACION_GERENCIA]
        ));

        $this->post($this->emailManagementApprovalUpdateUrl($requisition), [
            'action' => 'approve',
        ])->assertOk();

        $log = AuditLog::query()
            ->where('module', 'requisitions')
            ->where('event_type', 'management_approval')
            ->where('action', 'approve')
            ->firstOrFail();

        $this->assertSame('email', $log->metadata['channel']);
        $this->assertSame($approver->id, $log->user_id);
    }

    private function requesterUser(): User
    {
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('requisitions.tab.solicitar');

        return $user;
    }

    /**
     * @return array{0: User, 1: PersonalRequisition}
     */
    private function createManagerAndRequisition(string $code): array
    {
        $requester = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $requester->assignRole('usuario');

        $manager = User::factory()->create([
            'area_key' => 'gestion_humana',
            'must_change_password' => false,
        ]);
        $manager->assignRole('usuario');
        $manager->givePermissionTo([
            'view.board.operaciones.requisiciones',
            'requisitions.tab.gestion',
        ]);

        $requisition = PersonalRequisition::create($this->requisitionAttributes(
            $requester,
            $code,
            'operaciones',
            'Perfil audit test'
        ));

        return [$manager, $requisition];
    }

    private function commercialClient(): CommercialClient
    {
        return CommercialClient::query()->firstOrCreate(
            ['nit' => '900123456-1'],
            [
                'name' => 'Constructora Solanillas SAS',
                'city' => 'Cali',
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        $reason = RequisitionRequestReason::query()->whereRaw('LOWER(name) = ?', ['reemplazo'])->firstOrFail();

        return [
            'position_id' => RequisitionPosition::query()->firstOrFail()->id,
            'sex' => 'masculino',
            'quantity' => 1,
            'replacement_document' => 'Servicio nuevo',
            'replacement_name' => 'Servicio nuevo',
            'operating_area_key' => 'operaciones',
            'request_reason_id' => $reason->id,
            'commercial_client_id' => $this->commercialClient()->id,
            'city_id' => RequisitionCity::query()->firstOrFail()->id,
            'client_type_id' => RequisitionClientType::query()->whereRaw('LOWER(name) = ?', ['externo'])->firstOrFail()->id,
            'programming_type_id' => RequisitionProgrammingType::query()->firstOrFail()->id,
            'required_profile' => 'Control de ingreso y vigilancia perimetral.',
            'uniform_id' => RequisitionUniform::query()->firstOrFail()->id,
            'service_structure' => 'Turno 6x2, descanso dominical y posta fija en porteria.',
            'cost_center' => 'CC-001',
            'requester_observation' => 'Observacion inicial del solicitante.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function requisitionAttributes(User $requester, string $code, string $areaKey, string $profile): array
    {
        return [
            'code' => $code,
            'requested_by' => $requester->id,
            'request_date' => now()->toDateString(),
            'leader_name' => $requester->name,
            'requesting_area_key' => $areaKey,
            'position_id' => RequisitionPosition::query()->firstOrFail()->id,
            'sex' => 'masculino',
            'quantity' => 1,
            'operating_area_key' => $areaKey,
            'request_reason_id' => RequisitionRequestReason::query()->firstOrFail()->id,
            'client_id' => RequisitionClient::query()->firstOrFail()->id,
            'city_id' => RequisitionCity::query()->firstOrFail()->id,
            'client_type_id' => RequisitionClientType::query()->firstOrFail()->id,
            'programming_type_id' => RequisitionProgrammingType::query()->firstOrFail()->id,
            'uniform_id' => RequisitionUniform::query()->firstOrFail()->id,
            'required_profile' => $profile,
            'service_structure' => 'Horarios, descansos y condiciones del puesto a tener en cuenta.',
            'cost_center' => 'CC-TRACK',
            'status' => PersonalRequisition::STATUS_SOLICITADA,
            'status_changed_at' => now(),
        ];
    }

    private function emailManagementApprovalUpdateUrl(PersonalRequisition $requisition): string
    {
        return URL::temporarySignedRoute(
            'requisitions.email-approval.update',
            now()->addHour(),
            ['requisition' => $requisition->id],
        );
    }
}
