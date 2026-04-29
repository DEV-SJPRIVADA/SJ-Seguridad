<?php

namespace Tests\Feature;

use App\Models\PersonalRequisition;
use App\Models\RequisitionCity;
use App\Models\RequisitionClient;
use App\Models\RequisitionClientType;
use App\Models\RequisitionPosition;
use App\Models\RequisitionProgrammingType;
use App\Models\RequisitionRequestReason;
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

    public function test_user_cannot_create_requisition_for_a_different_area(): void
    {
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('view.board.gestion_humana.requisiciones');

        $response = $this->actingAs($user)->post(route('requisitions.store', ['module' => 'gestion_humana']), $this->validPayload());

        $response->assertForbidden();
    }

    public function test_dashboard_redirects_to_requisition_module_when_it_is_the_first_authorized_board(): void
    {
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('view.board.operaciones.requisiciones');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('requisitions.dashboard', ['module' => 'operaciones']));
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
            'required_uniform' => 'Overol',
            'cost_center' => 'CC-001',
            'requester_observation' => 'Observacion inicial del solicitante.',
        ];
    }
}
