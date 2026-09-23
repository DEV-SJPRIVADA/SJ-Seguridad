<?php

namespace Tests\Feature\DevelopmentRequests;

use App\Models\DevelopmentRequest;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DevelopmentRequestIntakeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_creator_can_submit_for_leader_approval(): void
    {
        $creator = $this->creatorUser();
        $leader = $this->leaderUser();

        $this->actingAs($creator)
            ->post(route('development-requests.store', ['module' => 'gestion_humana']), $this->validPayload($leader->id, 'submit'))
            ->assertRedirect();

        $request = DevelopmentRequest::query()->firstOrFail();
        $this->assertSame(DevelopmentRequest::STATUS_PENDIENTE_APROBACION_LIDER, $request->status);
        $this->assertNull($request->code);
        $this->assertSame((int) $leader->id, (int) $request->leader_id);
    }

    public function test_leader_self_submit_radicates_immediately(): void
    {
        $leader = $this->leaderUser();
        $leader->givePermissionTo(['devreq.tab.create', 'devreq.tab.my_requests', 'view.board.gestion_humana.solicitudes_desarrollo']);

        $this->actingAs($leader)
            ->post(route('development-requests.store', ['module' => 'gestion_humana']), $this->validPayload($leader->id, 'submit'))
            ->assertRedirect();

        $request = DevelopmentRequest::query()->firstOrFail();
        $this->assertSame(DevelopmentRequest::STATUS_RADICADO, $request->status);
        $this->assertNotNull($request->code);
        $this->assertStringStartsWith('DEV-'.now()->year.'-', $request->code);
    }

    public function test_leader_can_approve_pending_request(): void
    {
        $creator = $this->creatorUser();
        $leader = $this->leaderUser();

        $request = DevelopmentRequest::factory()->create([
            'created_by' => $creator->id,
            'leader_id' => $leader->id,
            'status' => DevelopmentRequest::STATUS_PENDIENTE_APROBACION_LIDER,
            'area_key' => 'gestion_humana',
            'title' => 'Req prueba',
            'request_type' => DevelopmentRequest::TYPE_MEJORA,
            'suggested_priority' => DevelopmentRequest::PRIORITY_IMPORTANTE,
            'description' => 'd',
            'current_process_problem' => 'p',
            'desired_steps' => 's',
            'users_description' => 'u',
            'restrictions' => 'r',
            'scope_in' => 'in',
            'acceptance_criteria' => 'ok',
            'requester_name' => 'A',
            'requester_email' => 'a@example.com',
        ]);

        $this->actingAs($leader)
            ->patch(route('development-requests.leader.update', [
                'module' => 'tic',
                'development_request' => $request,
            ]), [
                'decision' => 'approve',
                'notes' => 'OK institucional',
            ])
            ->assertRedirect(route('development-requests.leader-approval', ['module' => 'tic']));

        $request->refresh();
        $this->assertSame(DevelopmentRequest::STATUS_RADICADO, $request->status);
        $this->assertNotNull($request->code);
    }

    public function test_store_with_attachment(): void
    {
        Storage::fake('local');
        $creator = $this->creatorUser();
        $leader = $this->leaderUser();
        $file = UploadedFile::fake()->create('evidencia.pdf', 100, 'application/pdf');

        $this->actingAs($creator)
            ->post(route('development-requests.store', ['module' => 'gestion_humana']), array_merge(
                $this->validPayload($leader->id, 'draft'),
                ['attachments' => [$file]],
            ))
            ->assertRedirect();

        $request = DevelopmentRequest::query()->firstOrFail();
        $this->assertCount(1, $request->attachments);
        Storage::disk('local')->assertExists($request->attachments->first()->stored_path);
    }

    public function test_show_forbidden_for_other_area_user(): void
    {
        $creator = $this->creatorUser();
        $leader = $this->leaderUser();
        $stranger = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'comercial',
            'is_active' => true,
        ]);
        $stranger->givePermissionTo(['devreq.tab.my_requests', 'view.board.comercial.solicitudes_desarrollo']);

        $request = DevelopmentRequest::factory()->create([
            'created_by' => $creator->id,
            'leader_id' => $leader->id,
            'status' => DevelopmentRequest::STATUS_RADICADO,
            'code' => 'DEV-2026-00001',
            'area_key' => 'gestion_humana',
        ]);

        $this->actingAs($stranger)
            ->get(route('development-requests.show', [
                'module' => 'comercial',
                'development_request' => $request,
            ]))
            ->assertForbidden();
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(int $leaderId, string $action): array
    {
        return [
            'action' => $action,
            'area_key' => 'gestion_humana',
            'request_type' => 'mejora',
            'title' => 'Automatizar reporte mensual',
            'requester_name' => 'Usuario Prueba',
            'requester_email' => 'usuario@example.com',
            'leader_id' => $leaderId,
            'description' => 'Necesito un reporte',
            'current_process_problem' => 'Hoy se hace en Excel',
            'desired_steps' => 'Entrar, filtrar, exportar',
            'users_description' => 'Analistas GH',
            'restrictions' => 'No ver datos de otra area',
            'scope_in' => 'Listado y export',
            'scope_out' => 'No incluye edicion',
            'acceptance_criteria' => 'Puede exportar Excel filtrado',
            'suggested_priority' => 'importante',
        ];
    }

    private function creatorUser(): User
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'gestion_humana',
            'is_active' => true,
        ]);
        $user->givePermissionTo([
            'devreq.tab.create',
            'devreq.tab.my_requests',
            'view.board.gestion_humana.solicitudes_desarrollo',
        ]);

        return $user;
    }

    private function leaderUser(): User
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'gestion_humana',
            'is_active' => true,
        ]);
        $user->assignRole('director');
        $user->givePermissionTo([
            'devreq.tab.leader_approval',
            'view.board.tic.solicitudes_desarrollo',
        ]);

        return $user;
    }
}
