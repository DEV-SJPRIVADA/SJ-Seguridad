<?php

namespace Tests\Feature\PurchaseRequests;

use App\Models\AuditLog;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestComment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PurchaseRequestCommentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        Config::set('audit.enabled', true);
        Config::set('audit.queue', false);
    }

    public function test_owner_can_add_multiple_comments_on_show(): void
    {
        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();
        $purchaseRequest = $this->createRequest($requester, $director);

        $this->actingAs($requester)
            ->from(route('purchase-requests.show', ['module' => 'operaciones', 'purchase_request' => $purchaseRequest->id]))
            ->post(route('purchase-requests.comments.store', ['module' => 'operaciones', 'purchase_request' => $purchaseRequest->id]), [
                'body' => 'El producto Cascos ya no es necesario.',
            ])
            ->assertRedirect(route('purchase-requests.show', ['module' => 'operaciones', 'purchase_request' => $purchaseRequest->id]).'#purchase-request-comments')
            ->assertSessionHas('status');

        $this->actingAs($requester)
            ->post(route('purchase-requests.comments.store', ['module' => 'operaciones', 'purchase_request' => $purchaseRequest->id]), [
                'body' => 'Confirmo: solo continuar con Guantes.',
            ])
            ->assertRedirect();

        $this->assertSame(2, $purchaseRequest->comments()->count());

        $this->actingAs($requester)
            ->get(route('purchase-requests.show', ['module' => 'operaciones', 'purchase_request' => $purchaseRequest->id]))
            ->assertOk()
            ->assertSee('El producto Cascos ya no es necesario.')
            ->assertSee('Confirmo: solo continuar con Guantes.')
            ->assertSee('Comentarios de la solicitud');
    }

    public function test_director_and_compras_can_comment_and_outsider_cannot(): void
    {
        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();
        $compras = $this->comprasUser();
        $outsider = User::factory()->create([
            'area_key' => 'comercial',
            'must_change_password' => false,
        ]);
        $outsider->assignRole('usuario');
        $outsider->givePermissionTo(['purchase.tab.my_requests', 'view.board.comercial.solicitudes_compra']);

        $purchaseRequest = $this->createRequest($requester, $director, PurchaseRequest::ESTADO_APROBADO);

        $this->actingAs($director)
            ->post(route('purchase-requests.comments.store', ['module' => 'compras', 'purchase_request' => $purchaseRequest->id]), [
                'body' => 'Enterado, Compras tomara nota.',
            ])
            ->assertRedirect();

        $this->actingAs($compras)
            ->post(route('purchase-requests.comments.store', ['module' => 'compras', 'purchase_request' => $purchaseRequest->id]), [
                'body' => 'Ok, omitimos ese item en el pedido.',
            ])
            ->assertRedirect();

        $this->actingAs($outsider)
            ->post(route('purchase-requests.comments.store', ['module' => 'comercial', 'purchase_request' => $purchaseRequest->id]), [
                'body' => 'No deberia poder comentar.',
            ])
            ->assertForbidden();

        $this->assertSame(2, PurchaseRequestComment::query()->where('purchase_request_id', $purchaseRequest->id)->count());
    }

    public function test_comment_is_audited_without_storing_full_body(): void
    {
        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();
        $purchaseRequest = $this->createRequest($requester, $director);
        $body = 'Nota sensible de cancelacion parcial del pedido.';

        $this->actingAs($requester)
            ->post(route('purchase-requests.comments.store', ['module' => 'operaciones', 'purchase_request' => $purchaseRequest->id]), [
                'body' => $body,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'purchase_requests',
            'area' => 'compras',
            'event_type' => 'purchase_request',
            'action' => 'comment',
        ]);

        $log = AuditLog::query()->where('action', 'comment')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame(mb_strlen($body), data_get($log->metadata, 'body_length'));
        $this->assertStringNotContainsString($body, json_encode($log->metadata ?? []));
    }

    public function test_super_admin_cannot_comment_when_compras_completado(): void
    {
        $superAdmin = User::factory()->create([
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $superAdmin->assignRole('super-admin');

        $director = $this->director();
        $purchaseRequest = $this->createRequest($superAdmin, $director, PurchaseRequest::ESTADO_APROBADO);
        $purchaseRequest->update([
            'estado_compras' => PurchaseRequest::COMPRAS_COMPLETADO,
        ]);

        $this->actingAs($superAdmin)
            ->post(route('purchase-requests.comments.store', ['module' => 'compras', 'purchase_request' => $purchaseRequest->id]), [
                'body' => 'Super-admin tampoco debe poder comentar.',
            ])
            ->assertForbidden();

        $this->actingAs($superAdmin)
            ->get(route('purchase-requests.show', [
                'module' => 'compras',
                'purchase_request' => $purchaseRequest->id,
                'from' => 'mis_solicitudes',
            ]))
            ->assertOk()
            ->assertSee('no se pueden agregar mas comentarios')
            ->assertDontSee('name="body"', false);
    }

    public function test_comment_blocked_when_compras_completado(): void
    {
        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();
        $purchaseRequest = $this->createRequest($requester, $director, PurchaseRequest::ESTADO_APROBADO);
        $purchaseRequest->update([
            'estado_compras' => PurchaseRequest::COMPRAS_COMPLETADO,
        ]);

        $this->actingAs($requester)
            ->post(route('purchase-requests.comments.store', ['module' => 'operaciones', 'purchase_request' => $purchaseRequest->id]), [
                'body' => 'Ya no deberia poder comentar.',
            ])
            ->assertForbidden();

        $this->actingAs($requester)
            ->get(route('purchase-requests.show', ['module' => 'operaciones', 'purchase_request' => $purchaseRequest->id, 'from' => 'mis_solicitudes']))
            ->assertOk()
            ->assertSee('no se pueden agregar mas comentarios')
            ->assertDontSee('Agregar comentario', false);
    }

    public function test_owner_show_keeps_mis_solicitudes_tab_active_for_super_admin(): void
    {
        $superAdmin = User::factory()->create([
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $superAdmin->assignRole('super-admin');

        $director = $this->director();
        $purchaseRequest = $this->createRequest($superAdmin, $director);

        $html = $this->actingAs($superAdmin)
            ->get(route('purchase-requests.show', [
                'module' => 'compras',
                'purchase_request' => $purchaseRequest->id,
                'from' => 'mis_solicitudes',
            ]))
            ->assertOk()
            ->getContent();

        preg_match_all('/<a href="[^"]+" class="module-tab([^"]*)">\s*([^<]+)\s*<\/a>/', $html, $matches, PREG_SET_ORDER);
        $byLabel = [];
        foreach ($matches as $match) {
            $byLabel[trim($match[2])] = str_contains($match[1], 'module-tab--active');
        }

        $this->assertTrue($byLabel['Mis solicitudes'] ?? false, 'Mis solicitudes debe quedar activa');
        $this->assertFalse($byLabel['Pendientes autorizacion'] ?? false, 'Pendientes autorizacion no debe quedar activa');
    }

    public function test_comment_validation_requires_body(): void
    {
        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();
        $purchaseRequest = $this->createRequest($requester, $director);

        $this->actingAs($requester)
            ->from(route('purchase-requests.show', ['module' => 'operaciones', 'purchase_request' => $purchaseRequest->id]))
            ->post(route('purchase-requests.comments.store', ['module' => 'operaciones', 'purchase_request' => $purchaseRequest->id]), [
                'body' => 'ab',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('body');
    }

    private function createRequest(User $requester, User $director, string $estado = PurchaseRequest::ESTADO_PENDIENTE): PurchaseRequest
    {
        return PurchaseRequest::query()->create([
            'numero_solicitud' => (int) (PurchaseRequest::query()->max('numero_solicitud') ?? 0) + 1,
            'user_id' => $requester->id,
            'area_key' => $requester->area_key,
            'fecha_solicitud' => now()->toDateString(),
            'cantidad' => 1,
            'solicitud_para' => 'Interno',
            'urgente' => false,
            'aprobador_id' => $director->id,
            'estado' => $estado,
            'estado_compras' => $estado === PurchaseRequest::ESTADO_APROBADO ? PurchaseRequest::COMPRAS_PENDIENTE : null,
            'fecha_aprobacion' => $estado === PurchaseRequest::ESTADO_APROBADO ? now()->toDateString() : null,
        ]);
    }

    private function purchaseRequester(string $area, string $email = 'requester-comments@test.local'): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'area_key' => $area,
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo([
            'purchase.tab.create',
            'purchase.tab.my_requests',
            "view.board.{$area}.solicitudes_compra",
        ]);

        return $user;
    }

    private function director(string $email = 'director-comments@test.local'): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo(['purchase.tab.approval', 'view.board.compras.solicitudes_compra']);

        return $user;
    }

    private function comprasUser(string $email = 'compras-comments@test.local'): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo(['purchase.tab.processing', 'view.board.compras.bandeja_compras']);

        return $user;
    }
}
