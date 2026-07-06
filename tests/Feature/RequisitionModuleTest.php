<?php

namespace Tests\Feature;

use App\Models\PersonalRequisition;
use App\Models\RequisitionCity;
use App\Models\RequisitionClient;
use App\Models\RequisitionClientType;
use App\Models\RequisitionPosition;
use App\Models\RequisitionProgrammingType;
use App\Models\RequisitionRequestReason;
use App\Models\RequisitionUniform;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequisitionModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_user_can_create_requisition_for_its_own_area(): void
    {
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('view.board.operaciones.requisiciones');

        $response = $this->actingAs($user)->post(route('requisitions.store', ['module' => 'operaciones']), $this->validPayload());

        $response->assertRedirect(route('requisitions.dashboard', ['module' => 'operaciones']));
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

    public function test_user_with_explicit_board_permission_can_create_requisition_for_a_different_area(): void
    {
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('view.board.gestion_humana.requisiciones');

        $response = $this->actingAs($user)->post(route('requisitions.store', ['module' => 'gestion_humana']), $this->validPayload());

        $response->assertRedirect(route('requisitions.dashboard', ['module' => 'gestion_humana']));
        $this->assertDatabaseHas('personal_requisitions', [
            'requested_by' => $user->id,
            'requesting_area_key' => 'gestion_humana',
            'status' => PersonalRequisition::STATUS_SOLICITADA,
        ]);
    }

    public function test_dashboard_redirects_to_first_available_requisition_tab_when_it_is_the_first_authorized_board(): void
    {
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('view.board.operaciones.requisiciones');

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
        $requester->givePermissionTo('view.board.operaciones.requisiciones');
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
        $requester->givePermissionTo('view.board.operaciones.requisiciones');
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
        $manager->givePermissionTo('manage.area.gestion_humana');

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
            'client_id' => RequisitionClient::query()->firstOrFail()->id,
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
