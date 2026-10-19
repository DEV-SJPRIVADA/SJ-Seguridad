<?php

namespace Tests\Feature\PurchaseRequests;

use App\Models\PurchaseProvider;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseProviderAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('purchase-requests.providers.index', ['module' => 'compras']))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_permissions_cannot_access_catalog(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('purchase-requests.providers.index', ['module' => 'compras']))
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson(route('purchase-requests.providers.datatable', ['module' => 'compras']))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('purchase-requests.providers.export', ['module' => 'compras']))
            ->assertForbidden();
    }

    public function test_view_only_user_can_list_and_export_but_not_mutate(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('purchase.providers.view');

        $provider = PurchaseProvider::factory()->create();

        $this->actingAs($viewer)
            ->get(route('purchase-requests.providers.index', ['module' => 'compras']))
            ->assertOk();

        $this->actingAs($viewer)
            ->getJson(route('purchase-requests.providers.datatable', ['module' => 'compras']))
            ->assertOk();

        $this->actingAs($viewer)
            ->get(route('purchase-requests.providers.export', ['module' => 'compras']))
            ->assertOk();

        $this->actingAs($viewer)
            ->get(route('purchase-requests.providers.create', ['module' => 'compras']))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->get(route('purchase-requests.providers.edit', [
                'module' => 'compras',
                'purchase_provider' => $provider,
            ]))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('purchase-requests.providers.store', ['module' => 'compras']), $this->payload())
            ->assertForbidden();

        $this->actingAs($viewer)
            ->patch(route('purchase-requests.providers.update', [
                'module' => 'compras',
                'purchase_provider' => $provider,
            ]), $this->payload(['nombre' => 'NO DEBE CAMBIAR']))
            ->assertForbidden();

        $this->assertNotSame('NO DEBE CAMBIAR', $provider->fresh()->nombre);
    }

    public function test_edit_permission_implies_view_and_allows_create_and_update(): void
    {
        $editor = User::factory()->create();
        $editor->givePermissionTo('purchase.providers.edit');

        // edit implica view: la pestaña y el listado son accesibles.
        $this->actingAs($editor)
            ->get(route('purchase-requests.providers.index', ['module' => 'compras']))
            ->assertOk();

        $this->actingAs($editor)
            ->post(route('purchase-requests.providers.store', ['module' => 'compras']), $this->payload([
                'identificacion' => '800111222-3',
            ]))
            ->assertRedirect(route('purchase-requests.providers.index', ['module' => 'compras']));

        $provider = PurchaseProvider::query()->where('identificacion', '800111222-3')->firstOrFail();

        $this->actingAs($editor)
            ->get(route('purchase-requests.providers.edit', [
                'module' => 'compras',
                'purchase_provider' => $provider,
            ]))
            ->assertOk();

        $this->actingAs($editor)
            ->patch(route('purchase-requests.providers.update', [
                'module' => 'compras',
                'purchase_provider' => $provider,
            ]), $this->payload([
                'identificacion' => '800111222-3',
                'nombre' => 'proveedor actualizado',
            ]))
            ->assertRedirect(route('purchase-requests.providers.index', ['module' => 'compras']));

        $this->assertSame('PROVEEDOR ACTUALIZADO', $provider->fresh()->nombre);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'identificacion' => '900123456-1',
            'nombre' => 'proveedor de prueba',
            'telefono' => '3001234567',
            'correo' => 'Contacto@Proveedor.com',
            'es_cliente' => '0',
            'es_proveedor' => '1',
            'estado' => 'activo',
            'dias_pago' => '30',
            'contacto' => 'juan perez',
            'direccion' => 'calle 1 # 2-3',
            'ciudad' => 'medellín',
            'forma_pago' => 'transferencia',
        ], $overrides);
    }
}
