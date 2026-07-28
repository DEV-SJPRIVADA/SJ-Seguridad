<?php

namespace Tests\Feature;

use App\Exports\PersonalRequisitionFullExport;
use App\Mail\PersonalRequisitionManagementApprovalMail;
use App\Mail\PersonalRequisitionNotification;
use App\Mail\PersonalRequisitionStatusChangedMail;
use App\Models\CommercialClient;
use App\Models\PersonalRequisition;
use App\Models\RequisitionCity;
use App\Models\RequisitionClient;
use App\Models\RequisitionClientType;
use App\Models\RequisitionNotificationEmail;
use App\Models\RequisitionNotificationType;
use App\Models\RequisitionPosition;
use App\Models\RequisitionProgrammingType;
use App\Models\RequisitionRequestReason;
use App\Models\RequisitionUniform;
use App\Models\User;
use App\Services\Requisitions\CommercialClientBridge;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class RequisitionModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_user_can_search_commercial_clients_for_requisition_form(): void
    {
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('requisitions.tab.solicitar');

        CommercialClient::query()->create([
            'nit' => '901360444-1',
            'name' => 'MADEMAX',
            'city' => 'Cali',
        ]);

        $response = $this->actingAs($user)->getJson(route('requisitions.clients.search', [
            'module' => 'operaciones',
            'q' => 'MADE',
        ]));

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'MADEMAX');
    }

    public function test_clients_parameter_type_is_no_longer_manageable(): void
    {
        $user = User::factory()->create([
            'area_key' => 'gestion_humana',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo([
            'manage.requisition.parameters',
            'view.board.gestion_humana.requisiciones',
        ]);

        $this->actingAs($user)
            ->post(route('requisitions.parameters.store', ['module' => 'gestion_humana', 'type' => 'clients']), [
                'name' => 'Cliente manual',
                'is_active' => 1,
            ])
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('requisitions.parameters', ['module' => 'gestion_humana']))
            ->assertOk()
            ->assertDontSee('Gestionar: Clientes', false);
    }

    public function test_user_can_create_requisition_for_its_own_area(): void
    {
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('requisitions.tab.solicitar');

        $reason = RequisitionRequestReason::query()->whereRaw('LOWER(name) = ?', ['reemplazo'])->firstOrFail();
        $payload = $this->validPayload();
        $payload['request_reason_id'] = $reason->id;

        $response = $this->actingAs($user)->post(route('requisitions.store', ['module' => 'operaciones']), $payload);

        $response->assertRedirect(route('requisitions.create', ['module' => 'operaciones']));
        $this->assertDatabaseHas('personal_requisitions', [
            'requested_by' => $user->id,
            'requesting_area_key' => 'operaciones',
            'status' => PersonalRequisition::STATUS_SOLICITADA,
            'service_structure' => 'Turno 6x2, descanso dominical y posta fija en porteria.',
        ]);
        $this->assertDatabaseHas('personal_requisition_status_logs', [
            'to_status' => PersonalRequisition::STATUS_SOLICITADA,
            'changed_by' => $user->id,
        ]);
    }

    public function test_create_requires_service_structure(): void
    {
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('requisitions.tab.solicitar');

        $payload = $this->validPayload();
        unset($payload['service_structure']);

        $response = $this->actingAs($user)->post(route('requisitions.store', ['module' => 'operaciones']), $payload);

        $response->assertSessionHasErrors(['service_structure']);
        $this->assertDatabaseMissing('personal_requisitions', [
            'requested_by' => $user->id,
        ]);
    }

    public function test_create_form_shows_service_structure_field(): void
    {
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('requisitions.tab.solicitar');

        $this->actingAs($user)
            ->get(route('requisitions.create', ['module' => 'operaciones']))
            ->assertOk()
            ->assertSee('Estructura del servicio', false)
            ->assertSee('name="service_structure"', false)
            ->assertSee('Horarios, descansos y condiciones del puesto a tener en cuenta.', false);
    }

    public function test_movimiento_interno_requires_replacement_person_fields(): void
    {
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('requisitions.tab.solicitar');

        $movimientoInterno = RequisitionRequestReason::query()->firstOrCreate(
            ['name' => 'Movimiento interno'],
            ['is_active' => true, 'sort_order' => 10]
        );

        $payload = $this->validPayload();
        $payload['request_reason_id'] = $movimientoInterno->id;
        $payload['quantity'] = 1;
        unset($payload['replacement_document'], $payload['replacement_name']);

        $response = $this->actingAs($user)->post(route('requisitions.store', ['module' => 'operaciones']), $payload);

        $response->assertSessionHasErrors(['replacement_document', 'replacement_name']);
    }

    public function test_movimiento_interno_persists_replacement_person_fields(): void
    {
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('requisitions.tab.solicitar');

        $movimientoInterno = RequisitionRequestReason::query()->firstOrCreate(
            ['name' => 'Movimiento interno'],
            ['is_active' => true, 'sort_order' => 10]
        );

        $payload = $this->validPayload();
        $payload['request_reason_id'] = $movimientoInterno->id;
        $payload['quantity'] = 1;
        $payload['replacement_document'] = '1098765432';
        $payload['replacement_name'] = 'Ana Movimiento';

        $response = $this->actingAs($user)->post(route('requisitions.store', ['module' => 'operaciones']), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('personal_requisitions', [
            'requested_by' => $user->id,
            'request_reason_id' => $movimientoInterno->id,
            'replacement_document' => '1098765432',
            'replacement_name' => 'Ana Movimiento',
        ]);
    }

    public function test_create_form_includes_movimiento_interno_in_replacement_reason_ids(): void
    {
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('requisitions.tab.solicitar');

        $movimientoInterno = RequisitionRequestReason::query()->firstOrCreate(
            ['name' => 'Movimiento interno'],
            ['is_active' => true, 'sort_order' => 10]
        );

        $this->actingAs($user)
            ->get(route('requisitions.create', ['module' => 'operaciones']))
            ->assertOk()
            ->assertSee('data-replacement-ids=', false)
            ->assertSee((string) $movimientoInterno->id, false);
    }

    public function test_gestion_can_update_service_structure(): void
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
            'REQ-2026-0601',
            'operaciones',
            'Perfil estructura servicio'
        ));
        $requisition->statusLogs()->create([
            'from_status' => null,
            'to_status' => PersonalRequisition::STATUS_SOLICITADA,
            'changed_by' => $requester->id,
        ]);

        $updatedStructure = 'Turno 12x36, descanso intermedio y cobertura en dos postas.';

        $response = $this->actingAs($manager)->patch(route('requisitions.update', ['module' => 'operaciones', 'requisition' => $requisition]), array_merge(
            $this->validPayload(),
            [
                'status' => PersonalRequisition::STATUS_EN_GESTION,
                'service_structure' => $updatedStructure,
                'human_resources_observation' => 'Actualiza estructura del servicio.',
            ]
        ));

        $response->assertRedirect(route('requisitions.edit', ['module' => 'operaciones', 'requisition' => $requisition]));
        $this->assertDatabaseHas('personal_requisitions', [
            'id' => $requisition->id,
            'service_structure' => $updatedStructure,
        ]);
        $this->assertDatabaseHas('personal_requisition_change_logs', [
            'personal_requisition_id' => $requisition->id,
            'field_key' => 'service_structure',
            'field_label' => 'Estructura del servicio',
            'new_value' => $updatedStructure,
            'changed_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->get(route('requisitions.edit', ['module' => 'operaciones', 'requisition' => $requisition]))
            ->assertOk()
            ->assertSee('Estructura del servicio', false)
            ->assertSee($updatedStructure, false);
    }

    public function test_user_can_create_internal_requisition_without_commercial_client(): void
    {
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('requisitions.tab.solicitar');

        $internalType = RequisitionClientType::query()
            ->whereRaw('LOWER(name) = ?', ['interno'])
            ->firstOrFail();

        $payload = $this->validPayload();
        $payload['client_type_id'] = $internalType->id;
        unset($payload['commercial_client_id']);

        $response = $this->actingAs($user)->post(route('requisitions.store', ['module' => 'operaciones']), $payload);

        $response->assertRedirect(route('requisitions.create', ['module' => 'operaciones']));

        $internalClientId = RequisitionClient::query()
            ->where('name', CommercialClientBridge::INTERNAL_REQUISITION_CLIENT_NAME)
            ->value('id');

        $this->assertNotNull($internalClientId);
        $this->assertDatabaseHas('personal_requisitions', [
            'requested_by' => $user->id,
            'client_id' => $internalClientId,
            'client_type_id' => $internalType->id,
        ]);
    }

    public function test_externo_client_type_requires_commercial_client(): void
    {
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('requisitions.tab.solicitar');

        $externoType = RequisitionClientType::query()
            ->whereRaw('LOWER(name) = ?', ['externo'])
            ->firstOrFail();

        $payload = $this->validPayload();
        $payload['client_type_id'] = $externoType->id;
        unset($payload['commercial_client_id']);

        $this->actingAs($user)
            ->post(route('requisitions.store', ['module' => 'operaciones']), $payload)
            ->assertSessionHasErrors('commercial_client_id');
    }

    public function test_grupo_client_type_requires_commercial_client(): void
    {
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('requisitions.tab.solicitar');

        $grupoType = RequisitionClientType::query()->firstOrCreate(
            ['name' => 'Grupo'],
            ['is_active' => true, 'sort_order' => 99]
        );

        $payload = $this->validPayload();
        $payload['client_type_id'] = $grupoType->id;
        unset($payload['commercial_client_id']);

        $this->actingAs($user)
            ->post(route('requisitions.store', ['module' => 'operaciones']), $payload)
            ->assertSessionHasErrors('commercial_client_id');
    }

    public function test_store_sends_notification_to_type_assigned_emails(): void
    {
        Mail::fake();

        $email = RequisitionNotificationEmail::query()->create([
            'name' => 'gh.notify@example.com',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $newType = RequisitionNotificationType::query()->where('slug', RequisitionNotificationType::SLUG_NEW_REQUISITION)->firstOrFail();
        $newType->notificationEmails()->sync([$email->id]);

        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('requisitions.tab.solicitar');

        $reason = RequisitionRequestReason::query()->whereRaw('LOWER(name) = ?', ['reemplazo'])->firstOrFail();

        $payload = $this->validPayload();
        $payload['request_reason_id'] = $reason->id;

        $this->actingAs($user)->post(route('requisitions.store', ['module' => 'operaciones']), $payload);

        Mail::assertQueued(PersonalRequisitionNotification::class, function (PersonalRequisitionNotification $mail) {
            return $mail->hasTo('gh.notify@example.com') && $mail->totalQuantity === 3;
        });
    }

    public function test_cargo_nuevo_creates_pending_status_and_sends_management_mail(): void
    {
        Mail::fake();
        PermissionCatalog::sync();

        $ghEmail = RequisitionNotificationEmail::query()->create([
            'name' => 'gerencia@example.com',
            'is_active' => true,
        ]);
        $newType = RequisitionNotificationType::query()->where('slug', RequisitionNotificationType::SLUG_NEW_REQUISITION)->firstOrFail();
        $mgmtType = RequisitionNotificationType::query()->where('slug', RequisitionNotificationType::SLUG_MANAGEMENT_APPROVAL_CARGO_NUEVO)->firstOrFail();
        $newType->notificationEmails()->sync([$ghEmail->id]);
        $mgmtType->notificationEmails()->sync([$ghEmail->id]);

        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('requisitions.tab.solicitar');

        $cargoNuevo = RequisitionRequestReason::query()->whereRaw('LOWER(name) = ?', ['cargo nuevo'])->firstOrFail();
        $payload = $this->validPayload();
        $payload['request_reason_id'] = $cargoNuevo->id;
        $payload['quantity'] = 1;

        $this->actingAs($user)->post(route('requisitions.store', ['module' => 'operaciones']), $payload);

        $this->assertDatabaseHas('personal_requisitions', [
            'requested_by' => $user->id,
            'status' => PersonalRequisition::STATUS_PENDIENTE_AUTORIZACION_GERENCIA,
        ]);

        Mail::assertQueued(PersonalRequisitionNotification::class);
        Mail::assertSent(PersonalRequisitionManagementApprovalMail::class, fn ($mail) => $mail->hasTo('gerencia@example.com'));
    }

    public function test_management_approval_flow(): void
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
            $this->requisitionAttributes($requester, 'REQ-2026-0801', 'operaciones', 'Nuevo puesto'),
            ['status' => PersonalRequisition::STATUS_PENDIENTE_AUTORIZACION_GERENCIA]
        ));

        $this->actingAs($approver)
            ->get(route('requisitions.management-approval.index', ['module' => 'gestion_humana']))
            ->assertOk()
            ->assertSee($requisition->code);

        $this->actingAs($approver)
            ->post(route('requisitions.management-approval.decide', [
                'module' => 'gestion_humana',
                'requisition' => $requisition,
            ]), [
                'action' => 'approve',
            ])
            ->assertRedirect(route('requisitions.management-approval.index', ['module' => 'gestion_humana']));

        $this->assertDatabaseHas('personal_requisitions', [
            'id' => $requisition->id,
            'status' => PersonalRequisition::STATUS_SOLICITADA,
        ]);

        $this->actingAs($approver)
            ->get(route('requisitions.management-approval.index', ['module' => 'gestion_humana']))
            ->assertOk()
            ->assertSee('No hay requisiciones pendientes de autorizacion');
    }

    public function test_gestion_cannot_edit_pending_management_approval_requisition(): void
    {
        PermissionCatalog::sync();

        $manager = User::factory()->create([
            'area_key' => 'gestion_humana',
            'must_change_password' => false,
        ]);
        $manager->assignRole('usuario');
        $manager->givePermissionTo([
            'view.board.operaciones.requisiciones',
            'requisitions.tab.gestion',
        ]);

        $requester = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $requester->assignRole('usuario');

        $requisition = PersonalRequisition::create(array_merge(
            $this->requisitionAttributes($requester, 'REQ-2026-0802', 'operaciones', 'Pendiente'),
            ['status' => PersonalRequisition::STATUS_PENDIENTE_AUTORIZACION_GERENCIA]
        ));

        $this->actingAs($manager)
            ->get(route('requisitions.edit', ['module' => 'operaciones', 'requisition' => $requisition]))
            ->assertForbidden();
    }

    public function test_gestion_humana_can_persist_recruiter_id(): void
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

        $recruiter = User::factory()->create([
            'area_key' => 'gestion_humana',
            'must_change_password' => false,
            'name' => 'Ana Seleccion',
        ]);
        $recruiter->assignRole('usuario');
        $recruiter->givePermissionTo('requisitions.selection_officer');

        $requisition = PersonalRequisition::create($this->requisitionAttributes($requester, 'REQ-2026-0301', 'operaciones', 'Perfil con reclutador'));
        $requisition->statusLogs()->create([
            'from_status' => null,
            'to_status' => PersonalRequisition::STATUS_SOLICITADA,
            'changed_by' => $requester->id,
        ]);

        $response = $this->actingAs($manager)->patch(route('requisitions.update', ['module' => 'operaciones', 'requisition' => $requisition]), array_merge(
            $this->validPayload(),
            [
                'status' => PersonalRequisition::STATUS_EN_GESTION,
                'recruiter_id' => $recruiter->id,
                'human_resources_observation' => 'Asignado a seleccion.',
            ]
        ));

        $response->assertRedirect(route('requisitions.edit', ['module' => 'operaciones', 'requisition' => $requisition]));
        $this->assertDatabaseHas('personal_requisitions', [
            'id' => $requisition->id,
            'recruiter_id' => $recruiter->id,
        ]);
    }

    public function test_manage_view_shows_recruiter_column(): void
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

        $recruiter = User::factory()->create([
            'area_key' => 'gestion_humana',
            'must_change_password' => false,
            'name' => 'Reclutador Visible',
        ]);
        $recruiter->assignRole('usuario');
        $recruiter->givePermissionTo('requisitions.selection_officer');

        $withoutRecruiter = PersonalRequisition::create($this->requisitionAttributes($requester, 'REQ-2026-0302', 'operaciones', 'Sin reclutador'));
        $withRecruiter = PersonalRequisition::create(array_merge(
            $this->requisitionAttributes($requester, 'REQ-2026-0303', 'operaciones', 'Con reclutador'),
            ['recruiter_id' => $recruiter->id]
        ));

        $this->actingAs($manager)
            ->get(route('requisitions.manage', ['module' => 'operaciones']))
            ->assertOk()
            ->assertSee('Reclutador')
            ->assertSee('sin asignar')
            ->assertSee($withoutRecruiter->code)
            ->assertSee('Reclutador Visible')
            ->assertSee($withRecruiter->code);
    }

    public function test_user_cannot_create_requisition_outside_base_area_even_with_foreign_board(): void
    {
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo([
            'view.board.gestion_humana.requisiciones',
            'requisitions.tab.solicitar',
        ]);

        $response = $this->actingAs($user)->post(route('requisitions.store', ['module' => 'gestion_humana']), $this->validPayload());

        $response->assertForbidden();
        $this->assertDatabaseMissing('personal_requisitions', [
            'requested_by' => $user->id,
            'requesting_area_key' => 'gestion_humana',
        ]);
    }

    public function test_dashboard_redirects_to_first_available_requisition_tab_when_it_is_the_first_authorized_board(): void
    {
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('requisitions.tab.solicitar');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('requisitions.create', ['module' => 'operaciones']));
    }

    public function test_requester_can_view_tracking_for_its_own_area(): void
    {
        $requester = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $requester->assignRole('usuario');
        $requester->givePermissionTo('requisitions.tab.seguimiento');

        $sameAreaUser = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $sameAreaUser->assignRole('usuario');

        $otherAreaUser = User::factory()->create([
            'area_key' => 'comercial',
            'must_change_password' => false,
        ]);
        $otherAreaUser->assignRole('usuario');

        PersonalRequisition::create($this->requisitionAttributes($requester, 'REQ-2026-0101', 'operaciones', 'Perfil operaciones'));
        PersonalRequisition::create($this->requisitionAttributes($sameAreaUser, 'REQ-2026-0102', 'operaciones', 'Perfil compartido'));
        PersonalRequisition::create($this->requisitionAttributes($otherAreaUser, 'REQ-2026-0103', 'comercial', 'Perfil oculto'));

        $response = $this->actingAs($requester)->get(route('requisitions.tracking', ['module' => 'operaciones']));

        $response->assertOk();
        $response->assertSee('REQ-2026-0101');
        $response->assertSee('REQ-2026-0102');
        $response->assertDontSee('REQ-2026-0103');
    }

    public function test_requester_can_filter_tracking_to_only_own_requests(): void
    {
        $requester = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $requester->assignRole('usuario');
        $requester->givePermissionTo('requisitions.tab.seguimiento');

        $sameAreaUser = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $sameAreaUser->assignRole('usuario');

        PersonalRequisition::create($this->requisitionAttributes($requester, 'REQ-2026-0201', 'operaciones', 'Perfil propio'));
        PersonalRequisition::create($this->requisitionAttributes($sameAreaUser, 'REQ-2026-0202', 'operaciones', 'Perfil ajeno'));

        $response = $this->actingAs($requester)->get(route('requisitions.tracking', ['module' => 'operaciones', 'mine_only' => 1]));

        $response->assertOk();
        $response->assertSee('REQ-2026-0201');
        $response->assertDontSee('REQ-2026-0202');
    }

    public function test_requester_cannot_view_tracking_for_a_different_area(): void
    {
        $requester = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $requester->assignRole('usuario');
        $requester->givePermissionTo('view.board.comercial.requisiciones');
        $requester->givePermissionTo('requisitions.tab.seguimiento');

        $response = $this->actingAs($requester)->get(route('requisitions.tracking', ['module' => 'comercial']));

        $response->assertForbidden();
    }

    public function test_tracking_tab_is_hidden_when_user_is_browsing_a_different_area_module(): void
    {
        $requester = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $requester->assignRole('usuario');
        $requester->givePermissionTo('view.board.comercial.requisiciones');
        $requester->givePermissionTo('requisitions.tab.seguimiento');

        $tabs = $requester->requisitionBoardTabsFor('comercial');

        $this->assertFalse($tabs->contains('seguimiento'));
    }

    public function test_manage_lists_requisitions_by_request_date_desc_and_filters_by_status(): void
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

        $older = PersonalRequisition::create(array_merge(
            $this->requisitionAttributes($requester, 'REQ-2026-0100', 'operaciones', 'Perfil A'),
            ['request_date' => '2026-01-10', 'status' => PersonalRequisition::STATUS_SOLICITADA]
        ));
        $newer = PersonalRequisition::create(array_merge(
            $this->requisitionAttributes($requester, 'REQ-2026-0101', 'operaciones', 'Perfil B'),
            ['request_date' => '2026-03-15', 'status' => PersonalRequisition::STATUS_EN_GESTION]
        ));

        $this->actingAs($manager)
            ->get(route('requisitions.manage', ['module' => 'operaciones']))
            ->assertOk()
            ->assertSeeInOrder([$newer->code, $older->code]);

        $this->actingAs($manager)
            ->get(route('requisitions.manage', ['module' => 'operaciones', 'status' => PersonalRequisition::STATUS_EN_GESTION]))
            ->assertOk()
            ->assertSee($newer->code)
            ->assertDontSee($older->code);
    }

    public function test_gestion_update_logs_field_changes_without_status_change(): void
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
            'REQ-2026-0501',
            'operaciones',
            'Perfil inicial'
        ));

        $response = $this->actingAs($manager)->patch(route('requisitions.update', ['module' => 'operaciones', 'requisition' => $requisition]), array_merge(
            $this->validPayload(),
            [
                'status' => PersonalRequisition::STATUS_SOLICITADA,
                'quantity' => 5,
                'human_resources_observation' => 'Se ajusta cantidad por nueva necesidad.',
            ]
        ));

        $response->assertRedirect(route('requisitions.edit', ['module' => 'operaciones', 'requisition' => $requisition]));

        $this->assertDatabaseHas('personal_requisition_change_logs', [
            'personal_requisition_id' => $requisition->id,
            'field_key' => 'quantity',
            'old_value' => '1',
            'new_value' => '5',
            'changed_by' => $manager->id,
        ]);
        $this->assertDatabaseHas('personal_requisition_change_logs', [
            'personal_requisition_id' => $requisition->id,
            'field_key' => 'human_resources_observation',
            'old_value' => null,
            'new_value' => 'Se ajusta cantidad por nueva necesidad.',
            'changed_by' => $manager->id,
        ]);
        $this->assertDatabaseCount('personal_requisition_status_logs', 0);
    }

    public function test_edit_view_shows_change_log_history(): void
    {
        $requester = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $requester->assignRole('usuario');

        $manager = User::factory()->create([
            'area_key' => 'gestion_humana',
            'name' => 'Analista GH',
            'must_change_password' => false,
        ]);
        $manager->assignRole('usuario');
        $manager->givePermissionTo([
            'view.board.operaciones.requisiciones',
            'requisitions.tab.gestion',
        ]);

        $requisition = PersonalRequisition::create($this->requisitionAttributes(
            $requester,
            'REQ-2026-0502',
            'operaciones',
            'Perfil inicial'
        ));

        $requisition->changeLogs()->create([
            'change_batch' => (string) Str::uuid(),
            'field_key' => 'quantity',
            'field_label' => 'Cantidad',
            'old_value' => '1',
            'new_value' => '3',
            'changed_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->get(route('requisitions.edit', ['module' => 'operaciones', 'requisition' => $requisition]))
            ->assertOk()
            ->assertSee('Historial de cambios')
            ->assertSee('Cantidad')
            ->assertSee('Analista GH');
    }

    public function test_gestion_humana_user_can_update_status_and_create_status_log(): void
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

        $requisition = PersonalRequisition::create([
            'code' => 'REQ-2026-0001',
            'requested_by' => $requester->id,
            'request_date' => now()->toDateString(),
            'leader_name' => $requester->name,
            'requesting_area_key' => 'operaciones',
            'position_id' => RequisitionPosition::query()->firstOrFail()->id,
            'sex' => 'masculino',
            'quantity' => 2,
            'operating_area_key' => 'operaciones',
            'request_reason_id' => RequisitionRequestReason::query()->firstOrFail()->id,
            'client_id' => RequisitionClient::query()->firstOrFail()->id,
            'city_id' => RequisitionCity::query()->firstOrFail()->id,
            'client_type_id' => RequisitionClientType::query()->firstOrFail()->id,
            'programming_type_id' => RequisitionProgrammingType::query()->firstOrFail()->id,
            'required_profile' => 'Perfil inicial',
            'status' => PersonalRequisition::STATUS_SOLICITADA,
            'status_changed_at' => now(),
        ]);

        $requisition->statusLogs()->create([
            'from_status' => null,
            'to_status' => PersonalRequisition::STATUS_SOLICITADA,
            'changed_by' => $requester->id,
        ]);

        $response = $this->actingAs($manager)->patch(route('requisitions.update', ['module' => 'operaciones', 'requisition' => $requisition]), array_merge(
            $this->validPayload(),
            [
                'status' => PersonalRequisition::STATUS_EN_GESTION,
                'human_resources_observation' => 'Se toma la requisicion para gestion.',
            ]
        ));

        $response->assertRedirect(route('requisitions.edit', ['module' => 'operaciones', 'requisition' => $requisition]));
        $this->assertDatabaseHas('personal_requisitions', [
            'id' => $requisition->id,
            'status' => PersonalRequisition::STATUS_EN_GESTION,
            'managed_by' => $manager->id,
        ]);
        $this->assertDatabaseHas('personal_requisition_status_logs', [
            'personal_requisition_id' => $requisition->id,
            'from_status' => PersonalRequisition::STATUS_SOLICITADA,
            'to_status' => PersonalRequisition::STATUS_EN_GESTION,
            'changed_by' => $manager->id,
        ]);
    }

    public function test_status_change_queues_mail_to_requester(): void
    {
        Mail::fake();

        $requester = User::factory()->create([
            'area_key' => 'operaciones',
            'email' => 'solicitante.ops@example.com',
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
            'REQ-2026-0401',
            'operaciones',
            'Perfil para aviso de estado'
        ));
        $requisition->statusLogs()->create([
            'from_status' => null,
            'to_status' => PersonalRequisition::STATUS_SOLICITADA,
            'changed_by' => $requester->id,
        ]);

        $this->actingAs($manager)->patch(route('requisitions.update', ['module' => 'operaciones', 'requisition' => $requisition]), array_merge(
            $this->validPayload(),
            [
                'status' => PersonalRequisition::STATUS_EN_GESTION,
                'human_resources_observation' => 'En gestion por GH.',
            ]
        ));

        Mail::assertQueued(PersonalRequisitionStatusChangedMail::class, function (PersonalRequisitionStatusChangedMail $mail) use ($requester) {
            return $mail->hasTo($requester->email)
                && $mail->fromStatus === PersonalRequisition::STATUS_SOLICITADA
                && $mail->toStatus === PersonalRequisition::STATUS_EN_GESTION;
        });
    }

    public function test_update_without_status_change_does_not_queue_status_mail(): void
    {
        Mail::fake();

        $requester = User::factory()->create([
            'area_key' => 'operaciones',
            'email' => 'solicitante.silent@example.com',
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
            'REQ-2026-0402',
            'operaciones',
            'Perfil sin cambio de estado'
        ));
        $requisition->update(['status' => PersonalRequisition::STATUS_EN_GESTION]);
        $requisition->statusLogs()->create([
            'from_status' => PersonalRequisition::STATUS_SOLICITADA,
            'to_status' => PersonalRequisition::STATUS_EN_GESTION,
            'changed_by' => $manager->id,
        ]);

        $this->actingAs($manager)->patch(route('requisitions.update', ['module' => 'operaciones', 'requisition' => $requisition]), array_merge(
            $this->validPayload(),
            [
                'status' => PersonalRequisition::STATUS_EN_GESTION,
                'human_resources_observation' => 'Solo actualiza observaciones.',
            ]
        ));

        Mail::assertNotQueued(PersonalRequisitionStatusChangedMail::class);
    }

    public function test_manage_lists_all_areas_for_gestion_users(): void
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
            'view.board.gestion_humana.requisiciones',
            'requisitions.tab.gestion',
        ]);

        $operacionesReq = PersonalRequisition::create($this->requisitionAttributes($requester, 'REQ-2026-0501', 'operaciones', 'Perfil ops'));
        $comercialReq = PersonalRequisition::create($this->requisitionAttributes($requester, 'REQ-2026-0502', 'comercial', 'Perfil comercial'));

        $response = $this->actingAs($manager)
            ->get(route('requisitions.manage', ['module' => 'gestion_humana']));

        $response->assertOk();
        $response->assertSee($operacionesReq->code);
        $response->assertSee($comercialReq->code);
    }

    public function test_dashboard_in_gestion_humana_aggregates_all_areas_and_shows_canceladas_kpi(): void
    {
        $operacionesRequester = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $operacionesRequester->assignRole('usuario');

        $comercialRequester = User::factory()->create([
            'area_key' => 'comercial',
            'must_change_password' => false,
        ]);
        $comercialRequester->assignRole('usuario');

        $viewer = User::factory()->create([
            'area_key' => 'gestion_humana',
            'must_change_password' => false,
        ]);
        $viewer->assignRole('usuario');
        $viewer->givePermissionTo([
            'view.board.gestion_humana.requisiciones',
            'requisitions.tab.dashboard',
        ]);

        PersonalRequisition::create(array_merge(
            $this->requisitionAttributes($operacionesRequester, 'REQ-2026-DASH-OP', 'operaciones', 'Perfil OP'),
            ['status' => PersonalRequisition::STATUS_SOLICITADA]
        ));
        PersonalRequisition::create(array_merge(
            $this->requisitionAttributes($comercialRequester, 'REQ-2026-DASH-COM', 'comercial', 'Perfil COM'),
            ['status' => PersonalRequisition::STATUS_CANCELADA]
        ));
        PersonalRequisition::create(array_merge(
            $this->requisitionAttributes($viewer, 'REQ-2026-DASH-GH', 'gestion_humana', 'Perfil GH'),
            ['status' => PersonalRequisition::STATUS_EN_GESTION]
        ));

        $response = $this->actingAs($viewer)->get(route('requisitions.dashboard', [
            'module' => 'gestion_humana',
            'year' => now()->year,
        ]));

        $response->assertOk()
            ->assertSee('Canceladas', false)
            ->assertSee('todas las areas', false)
            ->assertViewHas('dashboardGlobalScope', true)
            ->assertViewHas('stats', function (array $stats): bool {
                return (int) $stats['total'] >= 3
                    && (int) $stats['cancelada'] >= 1
                    && (int) $stats['solicitada'] >= 1
                    && (int) $stats['en_gestion'] >= 1;
            });
    }

    public function test_manage_filters_by_request_date_range(): void
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

        PersonalRequisition::create(array_merge($this->requisitionAttributes($requester, 'REQ-2026-DATE-OLD', 'operaciones', 'Perfil'), [
            'request_date' => '2026-01-15',
        ]));
        PersonalRequisition::create(array_merge($this->requisitionAttributes($requester, 'REQ-2026-DATE-NEW', 'operaciones', 'Perfil'), [
            'request_date' => '2026-06-20',
        ]));

        $this->actingAs($manager)
            ->get(route('requisitions.manage', [
                'module' => 'operaciones',
                'date_from' => '2026-06-01',
                'date_to' => '2026-06-30',
            ]))
            ->assertOk()
            ->assertSee('REQ-2026-DATE-NEW')
            ->assertDontSee('REQ-2026-DATE-OLD');
    }

    public function test_gestion_export_uses_full_export_columns(): void
    {
        $labels = collect(PersonalRequisitionFullExport::columns())->pluck('label');

        $this->assertTrue($labels->contains('Salario base'));
        $this->assertTrue($labels->contains('Estructura del servicio'));
        $this->assertGreaterThan(35, $labels->count());
    }

    public function test_personnel_admin_sees_operaciones_base_tabs_and_gestion_only_in_gh(): void
    {
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->syncPermissions([
            'view.board.gestion_humana.requisiciones',
            'requisitions.tab.solicitar',
            'requisitions.tab.seguimiento',
            'requisitions.tab.gestion',
        ]);

        $operacionesTabs = $user->requisitionBoardTabsFor('operaciones');
        $ghTabs = $user->requisitionBoardTabsFor('gestion_humana');

        $this->assertTrue($operacionesTabs->contains('solicitar'));
        $this->assertTrue($operacionesTabs->contains('seguimiento'));
        $this->assertFalse($operacionesTabs->contains('gestion'));
        $this->assertFalse($operacionesTabs->contains('dashboard'));

        $this->assertTrue($ghTabs->contains('gestion'));
        $this->assertFalse($ghTabs->contains('solicitar'));
        $this->assertFalse($ghTabs->contains('seguimiento'));

        $this->actingAs($user)
            ->get(route('requisitions.create', ['module' => 'operaciones']))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('requisitions.manage', ['module' => 'gestion_humana']))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('requisitions.create', ['module' => 'gestion_humana']))
            ->assertForbidden();
    }

    public function test_recruiter_schema_uses_users_and_drops_recruiters_catalog(): void
    {
        $this->assertFalse(Schema::hasTable('requisition_recruiters'));

        $foreignKeys = collect(Schema::getForeignKeys('personal_requisitions'))
            ->filter(fn (array $fk): bool => in_array('recruiter_id', $fk['columns'] ?? [], true));

        $this->assertCount(1, $foreignKeys);
        $this->assertSame('users', $foreignKeys->first()['foreign_table'] ?? null);
    }

    public function test_selection_officer_toggle_grants_and_revokes_permission(): void
    {
        PermissionCatalog::sync();

        $admin = User::factory()->create([
            'area_key' => 'gestion_humana',
            'must_change_password' => false,
        ]);
        $admin->assignRole('usuario');
        $admin->givePermissionTo([
            'manage.requisition.parameters',
            'view.board.gestion_humana.requisiciones',
        ]);

        $officer = User::factory()->create([
            'area_key' => 'gestion_humana',
            'must_change_password' => false,
        ]);
        $officer->assignRole('usuario');

        $this->actingAs($admin)
            ->patch(route('requisitions.selection-officers.update', ['module' => 'gestion_humana', 'user' => $officer]), [
                'enabled' => true,
            ])
            ->assertRedirect(route('requisitions.parameters', ['module' => 'gestion_humana']));

        $officer->refresh();
        $this->assertTrue($officer->can('requisitions.selection_officer'));

        $this->actingAs($admin)
            ->patch(route('requisitions.selection-officers.update', ['module' => 'gestion_humana', 'user' => $officer]), [
                'enabled' => false,
            ])
            ->assertRedirect(route('requisitions.parameters', ['module' => 'gestion_humana']));

        $officer->refresh();
        $this->assertFalse($officer->can('requisitions.selection_officer'));

        $operacionesUser = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $operacionesUser->assignRole('usuario');

        $this->actingAs($admin)
            ->from(route('requisitions.parameters', ['module' => 'gestion_humana']))
            ->patch(route('requisitions.selection-officers.update', ['module' => 'gestion_humana', 'user' => $operacionesUser]), [
                'enabled' => true,
            ])
            ->assertRedirect(route('requisitions.parameters', ['module' => 'gestion_humana']))
            ->assertSessionHasErrors('selection_officer');
    }

    public function test_gestion_humana_parameters_includes_selection_officers_section(): void
    {
        $ghAdmin = User::factory()->create([
            'area_key' => 'gestion_humana',
            'must_change_password' => false,
            'name' => 'Admin Parametros GH',
        ]);
        $ghAdmin->assignRole('usuario');
        $ghAdmin->givePermissionTo([
            'manage.requisition.parameters',
            'view.board.gestion_humana.requisiciones',
        ]);

        $this->actingAs($ghAdmin)
            ->get(route('requisitions.parameters', ['module' => 'gestion_humana']))
            ->assertOk()
            ->assertSee('Encargados de seleccion', false)
            ->assertSee('toggle-switch', false);

        $opsAdmin = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $opsAdmin->assignRole('usuario');
        $opsAdmin->givePermissionTo([
            'manage.requisition.parameters',
            'view.board.operaciones.requisiciones',
        ]);

        $this->actingAs($opsAdmin)
            ->get(route('requisitions.parameters', ['module' => 'operaciones']))
            ->assertOk()
            ->assertDontSee('Encargados de seleccion', false);
    }

    public function test_recruiter_select_only_enabled_gh_users(): void
    {
        PermissionCatalog::sync();

        $enabled = User::factory()->create([
            'area_key' => 'gestion_humana',
            'name' => 'Reclutador Habilitado',
            'must_change_password' => false,
        ]);
        $enabled->assignRole('usuario');
        $enabled->givePermissionTo('requisitions.selection_officer');

        User::factory()->create([
            'area_key' => 'gestion_humana',
            'name' => 'GH Sin Permiso',
            'must_change_password' => false,
        ])->assignRole('usuario');

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
            'REQ-2026-SEL-01',
            'operaciones',
            'Perfil select reclutador'
        ));

        $this->actingAs($manager)
            ->get(route('requisitions.edit', ['module' => 'operaciones', 'requisition' => $requisition]))
            ->assertOk()
            ->assertSee('Reclutador Habilitado', false)
            ->assertDontSee('GH Sin Permiso', false);
    }

    public function test_assigned_recruiter_visible_after_toggle_off(): void
    {
        PermissionCatalog::sync();

        $recruiter = User::factory()->create([
            'area_key' => 'gestion_humana',
            'name' => 'Encargado Asignado',
            'must_change_password' => false,
        ]);
        $recruiter->assignRole('usuario');
        $recruiter->givePermissionTo('requisitions.selection_officer');

        $requester = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $requester->assignRole('usuario');

        $requisition = PersonalRequisition::create(array_merge(
            $this->requisitionAttributes($requester, 'REQ-2026-SEL-02', 'operaciones', 'Perfil asignado'),
            ['recruiter_id' => $recruiter->id]
        ));

        $admin = User::factory()->create([
            'area_key' => 'gestion_humana',
            'must_change_password' => false,
        ]);
        $admin->assignRole('usuario');
        $admin->givePermissionTo([
            'manage.requisition.parameters',
            'view.board.gestion_humana.requisiciones',
            'requisitions.tab.gestion',
            'view.board.operaciones.requisiciones',
        ]);

        $this->actingAs($admin)
            ->patch(route('requisitions.selection-officers.update', ['module' => 'gestion_humana', 'user' => $recruiter]), [
                'enabled' => false,
            ])
            ->assertRedirect();

        $recruiter->refresh();
        $this->assertFalse($recruiter->can('requisitions.selection_officer'));

        $requisition->refresh()->load('recruiter');
        $this->assertSame('Encargado Asignado', $requisition->displayRecruiterName());

        $manager = $admin;
        $this->actingAs($manager)
            ->get(route('requisitions.edit', ['module' => 'operaciones', 'requisition' => $requisition]))
            ->assertOk()
            ->assertSee('Encargado Asignado', false);
    }

    public function test_update_rejects_recruiter_id_without_permission(): void
    {
        PermissionCatalog::sync();

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

        $notOfficer = User::factory()->create([
            'area_key' => 'gestion_humana',
            'must_change_password' => false,
        ]);
        $notOfficer->assignRole('usuario');

        $requisition = PersonalRequisition::create($this->requisitionAttributes(
            $requester,
            'REQ-2026-SEL-03',
            'operaciones',
            'Perfil rechazo reclutador'
        ));
        $requisition->statusLogs()->create([
            'from_status' => null,
            'to_status' => PersonalRequisition::STATUS_SOLICITADA,
            'changed_by' => $requester->id,
        ]);

        $this->actingAs($manager)->patch(route('requisitions.update', ['module' => 'operaciones', 'requisition' => $requisition]), array_merge(
            $this->validPayload(),
            [
                'status' => PersonalRequisition::STATUS_EN_GESTION,
                'recruiter_id' => $notOfficer->id,
            ]
        ))->assertSessionHasErrors('recruiter_id');
    }

    public function test_change_logger_resolves_recruiter_id_to_user_name(): void
    {
        PermissionCatalog::sync();

        $recruiter = User::factory()->create([
            'area_key' => 'gestion_humana',
            'name' => 'Logger Reclutador',
            'must_change_password' => false,
        ]);
        $recruiter->assignRole('usuario');
        $recruiter->givePermissionTo('requisitions.selection_officer');

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
            'REQ-2026-SEL-04',
            'operaciones',
            'Perfil logger'
        ));
        $requisition->statusLogs()->create([
            'from_status' => null,
            'to_status' => PersonalRequisition::STATUS_SOLICITADA,
            'changed_by' => $requester->id,
        ]);

        $this->actingAs($manager)->patch(route('requisitions.update', ['module' => 'operaciones', 'requisition' => $requisition]), array_merge(
            $this->validPayload(),
            [
                'status' => PersonalRequisition::STATUS_EN_GESTION,
                'recruiter_id' => $recruiter->id,
            ]
        ))->assertRedirect();

        $this->assertDatabaseHas('personal_requisition_change_logs', [
            'personal_requisition_id' => $requisition->id,
            'field_key' => 'recruiter_id',
            'new_value' => 'Logger Reclutador',
        ]);
    }

    public function test_parameters_recruiters_crud_routes_return_404(): void
    {
        $user = User::factory()->create([
            'area_key' => 'gestion_humana',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo([
            'manage.requisition.parameters',
            'view.board.gestion_humana.requisiciones',
        ]);

        $this->actingAs($user)
            ->post(route('requisitions.parameters.store', ['module' => 'gestion_humana', 'type' => 'recruiters']), [
                'name' => 'Legacy',
                'is_active' => 1,
            ])
            ->assertNotFound();

        $this->actingAs($user)
            ->patch(route('requisitions.parameters.update', ['module' => 'gestion_humana', 'type' => 'recruiters', 'parameterId' => 1]), [
                'name' => 'Legacy',
                'is_active' => 1,
            ])
            ->assertNotFound();

        $this->actingAs($user)
            ->delete(route('requisitions.parameters.destroy', ['module' => 'gestion_humana', 'type' => 'recruiters', 'parameterId' => 1]))
            ->assertNotFound();
    }

    public function test_selection_officer_patch_returns_404_outside_gestion_humana_module(): void
    {
        $admin = User::factory()->create([
            'area_key' => 'gestion_humana',
            'must_change_password' => false,
        ]);
        $admin->assignRole('usuario');
        $admin->givePermissionTo([
            'manage.requisition.parameters',
            'view.board.operaciones.requisiciones',
        ]);

        $target = User::factory()->create([
            'area_key' => 'gestion_humana',
            'must_change_password' => false,
        ]);
        $target->assignRole('usuario');

        $this->actingAs($admin)
            ->patch(route('requisitions.selection-officers.update', ['module' => 'operaciones', 'user' => $target]), [
                'enabled' => true,
            ])
            ->assertNotFound();
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
        return [
            'position_id' => RequisitionPosition::query()->firstOrFail()->id,
            'sex' => 'masculino',
            'quantity' => 3,
            'replacement_document' => 'Servicio nuevo',
            'replacement_name' => 'Servicio nuevo',
            'operating_area_key' => 'operaciones',
            'request_reason_id' => RequisitionRequestReason::query()->firstOrFail()->id,
            'commercial_client_id' => $this->commercialClient()->id,
            'city_id' => RequisitionCity::query()->firstOrFail()->id,
            'client_type_id' => RequisitionClientType::query()->whereRaw('LOWER(name) = ?', ['externo'])->firstOrFail()->id,
            'programming_type_id' => RequisitionProgrammingType::query()->firstOrFail()->id,
            'required_profile' => 'Control de ingreso, verificacion de herramientas y vigilancia perimetral.',
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
}
