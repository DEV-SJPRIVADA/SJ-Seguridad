<?php

namespace Tests\Feature\DevelopmentRequests;

use App\Models\DevelopmentRequest;
use App\Models\User;
use App\Services\DevelopmentRequests\DevelopmentRequestDashboardService;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevelopmentRequestDashboardExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_tic_queue_shows_kpis_and_export_downloads(): void
    {
        $tic = $this->ticUser();
        DevelopmentRequest::factory()->create([
            'status' => DevelopmentRequest::STATUS_RADICADO,
            'code' => 'DEV-'.now()->year.'-00101',
            'area_key' => 'gestion_humana',
            'suggested_priority' => DevelopmentRequest::PRIORITY_URGENTE,
            'radicated_at' => now()->subDays(5),
            'title' => 'KPI vencido',
            'request_type' => DevelopmentRequest::TYPE_MEJORA,
            'description' => 'd',
            'current_process_problem' => 'p',
            'desired_steps' => 's',
            'users_description' => 'u',
            'restrictions' => 'r',
            'scope_in' => 'in',
            'acceptance_criteria' => 'ok',
            'requester_name' => 'A',
            'requester_email' => 'a@example.com',
            'created_by' => $tic->id,
            'leader_id' => $tic->id,
        ]);

        $this->actingAs($tic)
            ->get(route('development-requests.tic-queue', ['module' => 'tic']))
            ->assertOk()
            ->assertSee('Recibidos', false)
            ->assertSee('Vencidos SLA', false);

        $this->actingAs($tic)
            ->get(route('development-requests.tic-queue.export', ['module' => 'tic']))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_sla_marks_urgent_after_two_days(): void
    {
        $service = app(DevelopmentRequestDashboardService::class);
        $request = DevelopmentRequest::factory()->make([
            'status' => DevelopmentRequest::STATUS_RADICADO,
            'suggested_priority' => DevelopmentRequest::PRIORITY_URGENTE,
            'tic_confirmed_priority' => null,
            'radicated_at' => now()->subDays(3),
        ]);

        $this->assertTrue($service->isOverdueForAnalysis($request));
        $this->assertSame(2, $service->slaDaysFor('urgente'));
    }

    public function test_export_forbidden_without_tic_queue(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'gestion_humana',
            'is_active' => true,
        ]);
        $user->givePermissionTo([
            'devreq.tab.my_requests',
            'view.board.gestion_humana.solicitudes_desarrollo',
        ]);

        $this->actingAs($user)
            ->get(route('development-requests.tic-queue.export', ['module' => 'tic']))
            ->assertForbidden();
    }

    private function ticUser(): User
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'tic',
            'is_active' => true,
        ]);
        $user->givePermissionTo([
            'devreq.tab.tic_queue',
            'view.board.tic.solicitudes_desarrollo',
        ]);

        return $user;
    }
}
