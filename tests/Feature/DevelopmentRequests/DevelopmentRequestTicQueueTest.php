<?php

namespace Tests\Feature\DevelopmentRequests;

use App\Mail\DevelopmentRequestMessageMail;
use App\Models\DevelopmentRequest;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DevelopmentRequestTicQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_tic_user_can_open_queue_and_transition_to_analisis(): void
    {
        $tic = $this->ticUser();
        $request = $this->radicatedRequest();

        $this->actingAs($tic)
            ->get(route('development-requests.tic-queue', ['module' => 'tic']))
            ->assertOk()
            ->assertSee('Bandeja TIC', false)
            ->assertSee('Listado operativo', false)
            ->assertSee($request->code);

        $this->actingAs($tic)
            ->get(route('development-requests.show', [
                'module' => 'tic',
                'development_request' => $request,
                'from' => 'tic_queue',
            ]))
            ->assertOk()
            ->assertSee('Volver a bandeja TIC', false)
            ->assertSee('Detalle FO-TIC-23', false)
            ->assertSee('Gestion TIC', false);

        $this->actingAs($tic)
            ->patch(route('development-requests.tic.transition', [
                'module' => 'tic',
                'development_request' => $request,
            ]), [
                'to_status' => DevelopmentRequest::STATUS_EN_ANALISIS,
                'tic_viability' => 'aceptado',
                'tic_complexity' => 'media',
                'assigned_programmer_id' => $tic->id,
                'comment' => 'Inicia analisis',
            ])
            ->assertRedirect();

        $request->refresh();
        $this->assertSame(DevelopmentRequest::STATUS_EN_ANALISIS, $request->status);
        $this->assertSame('aceptado', $request->tic_viability);
        $this->assertSame((int) $tic->id, (int) $request->assigned_programmer_id);
        $this->assertTrue($request->statusLogs()->where('to_status', DevelopmentRequest::STATUS_EN_ANALISIS)->exists());
    }

    public function test_return_requires_comment(): void
    {
        $tic = $this->ticUser();
        $request = $this->radicatedRequest();

        $this->actingAs($tic)
            ->from(route('development-requests.show', ['module' => 'tic', 'development_request' => $request]))
            ->patch(route('development-requests.tic.transition', [
                'module' => 'tic',
                'development_request' => $request,
            ]), [
                'to_status' => DevelopmentRequest::STATUS_DEVUELTO,
            ])
            ->assertSessionHasErrors('comment');

        $this->assertSame(DevelopmentRequest::STATUS_RADICADO, $request->fresh()->status);
    }

    public function test_creator_can_accept_uat(): void
    {
        $creator = $this->creatorUser();
        $request = $this->radicatedRequest([
            'created_by' => $creator->id,
            'status' => DevelopmentRequest::STATUS_EN_PRUEBAS,
        ]);

        $this->actingAs($creator)
            ->patch(route('development-requests.uat.update', [
                'module' => 'gestion_humana',
                'development_request' => $request,
            ]), [
                'uat_result' => 'si',
                'uat_notes' => 'Cumple criterios',
            ])
            ->assertRedirect();

        $request->refresh();
        $this->assertSame(DevelopmentRequest::STATUS_ENTREGADO, $request->status);
        $this->assertSame('si', $request->uat_result);
    }

    public function test_message_queues_mail_to_participants(): void
    {
        Mail::fake();

        $creator = $this->creatorUser();
        $leader = $this->leaderUser();
        $tic = $this->ticUser();
        $request = $this->radicatedRequest([
            'created_by' => $creator->id,
            'leader_id' => $leader->id,
            'assigned_programmer_id' => $tic->id,
            'requester_email' => $creator->email,
        ]);

        $this->actingAs($tic)
            ->post(route('development-requests.messages.store', [
                'module' => 'tic',
                'development_request' => $request,
            ]), [
                'body' => 'Necesitamos aclarar el alcance de reportes.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('development_request_messages', [
            'development_request_id' => $request->id,
            'user_id' => $tic->id,
        ]);

        Mail::assertQueued(DevelopmentRequestMessageMail::class);
    }

    public function test_non_tic_cannot_transition(): void
    {
        $creator = $this->creatorUser();
        $request = $this->radicatedRequest(['created_by' => $creator->id]);

        $this->actingAs($creator)
            ->patch(route('development-requests.tic.transition', [
                'module' => 'tic',
                'development_request' => $request,
            ]), [
                'to_status' => DevelopmentRequest::STATUS_EN_ANALISIS,
            ])
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function radicatedRequest(array $overrides = []): DevelopmentRequest
    {
        $creator = $this->creatorUser();
        $leader = $this->leaderUser();

        return DevelopmentRequest::factory()->create(array_merge([
            'created_by' => $creator->id,
            'leader_id' => $leader->id,
            'status' => DevelopmentRequest::STATUS_RADICADO,
            'code' => 'DEV-'.now()->year.'-00099',
            'area_key' => 'gestion_humana',
            'title' => 'Solicitud bandeja TIC',
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
            'radicated_at' => now(),
        ], $overrides));
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
        $user->givePermissionTo([
            'devreq.tab.leader_approval',
            'view.board.tic.solicitudes_desarrollo',
        ]);

        return $user;
    }
}
