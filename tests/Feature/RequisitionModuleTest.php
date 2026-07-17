<?php

namespace Tests\Feature;

use App\Mail\PersonalRequisitionNotification;
use App\Mail\PersonalRequisitionStatusChangedMail;
use App\Models\CommercialClient;
use App\Models\PersonalRequisition;
use App\Services\Requisitions\CommercialClientBridge;
use App\Models\RequisitionCity;
use App\Models\RequisitionClient;
use App\Models\RequisitionClientType;
use App\Models\RequisitionNotificationEmail;
use App\Models\RequisitionPosition;
use App\Models\RequisitionProgrammingType;
use App\Models\RequisitionRecruiter;
use App\Models\RequisitionRequestReason;
use App\Models\RequisitionUniform;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RequisitionModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);
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

        $response = $this->actingAs($user)->post(route('requisitions.store', ['module' => 'operaciones']), $this->validPayload());

        $response->assertRedirect(route('requisitions.create', ['module' => 'operaciones']));
        $this->assertDatabaseHas('personal_requisitions', [
            'requested_by' => $user->id,
            'requesting_area_key' => 'operaciones',
            'status' => PersonalRequisition::STATUS_SOLICITADA,
        ]);
        $this->assertDatabaseHas('personal_requisition_status_logs', [
            'to_status' => PersonalRequisition::STATUS_SOLICITADA,
            'changed_by' => $user->id,
        ]);
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

    public function test_store_queues_notification_to_active_emails(): void
    {
        Mail::fake();

        RequisitionNotificationEmail::query()->create([
            'name' => 'gh.notify@example.com',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('requisitions.tab.solicitar');

        $this->actingAs($user)->post(route('requisitions.store', ['module' => 'operaciones']), $this->validPayload());

        Mail::assertQueued(PersonalRequisitionNotification::class, function (PersonalRequisitionNotification $mail) {
            return $mail->hasTo('gh.notify@example.com') && $mail->totalQuantity === 3;
        });
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

        $recruiter = RequisitionRecruiter::query()->create([
            'name' => 'Ana Seleccion',
            'is_active' => true,
        ]);

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
            'client_type_id' => RequisitionClientType::query()->firstOrFail()->id,
            'programming_type_id' => RequisitionProgrammingType::query()->firstOrFail()->id,
            'required_profile' => 'Control de ingreso, verificacion de herramientas y vigilancia perimetral.',
            'uniform_id' => RequisitionUniform::query()->firstOrFail()->id,
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
            'cost_center' => 'CC-TRACK',
            'status' => PersonalRequisition::STATUS_SOLICITADA,
            'status_changed_at' => now(),
        ];
    }
}
