<?php

namespace Tests\Feature\PurchaseRequests;

use App\Models\PurchaseProvider;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseProviderCatalogTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->editor = User::factory()->create();
        $this->editor->givePermissionTo('purchase.providers.edit');
    }

    public function test_datatable_defaults_to_active_providers_only(): void
    {
        $activo = PurchaseProvider::factory()->create(['nombre' => 'PROVEEDOR ACTIVO']);
        PurchaseProvider::factory()->inactivo()->create(['nombre' => 'PROVEEDOR INACTIVO']);
        PurchaseProvider::factory()->cliente()->create(['nombre' => 'CLIENTE SOLO']);

        $response = $this->actingAs($this->editor)
            ->getJson($this->datatableUrl())
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.1', 'PROVEEDOR ACTIVO');

        $this->assertSame((string) $activo->identificacion, $response->json('data.0.0'));
    }

    public function test_datatable_filters_by_estado_and_catalog_scope(): void
    {
        PurchaseProvider::factory()->create(['nombre' => 'PROVEEDOR ACTIVO']);
        PurchaseProvider::factory()->inactivo()->create(['nombre' => 'PROVEEDOR INACTIVO']);
        PurchaseProvider::factory()->cliente()->create(['nombre' => 'CLIENTE SOLO']);

        $this->actingAs($this->editor)
            ->getJson($this->datatableUrl(['estado' => 'todos']))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 2);

        $this->actingAs($this->editor)
            ->getJson($this->datatableUrl(['estado' => 'todos', 'solo_proveedores' => '0']))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 3);

        $this->actingAs($this->editor)
            ->getJson($this->datatableUrl(['estado' => 'inactivo']))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.1', 'PROVEEDOR INACTIVO');
    }

    public function test_datatable_search_matches_identificacion_and_nombre(): void
    {
        PurchaseProvider::factory()->create([
            'identificacion' => 'NIT-BUSCADO',
            'nombre' => 'OTRO PROVEEDOR',
        ]);
        PurchaseProvider::factory()->create([
            'identificacion' => 'NIT-OTRO',
            'nombre' => 'PROVEEDOR BUSCADO',
        ]);

        $this->actingAs($this->editor)
            ->getJson($this->datatableUrl(['q' => 'BUSCADO']))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 2);
    }

    public function test_store_persists_free_text_in_uppercase_except_email_and_codes(): void
    {
        $this->actingAs($this->editor)
            ->post(route('purchase-requests.providers.store', ['module' => 'compras']), $this->payload([
                'identificacion' => 'nit-900123',
            ]))
            ->assertRedirect(route('purchase-requests.providers.index', ['module' => 'compras']));

        $provider = PurchaseProvider::query()->where('identificacion', 'nit-900123')->firstOrFail();

        $this->assertSame('PROVEEDOR DE PRUEBA', $provider->nombre);
        $this->assertSame('JUAN PEREZ', $provider->contacto);
        $this->assertSame('CALLE 1 # 2-3', $provider->direccion);
        $this->assertSame('MEDELLÍN', $provider->ciudad);
        $this->assertSame('TRANSFERENCIA', $provider->forma_pago);
        // Email y códigos técnicos conservan el valor original.
        $this->assertSame('Contacto@Proveedor.com', $provider->correo);
        $this->assertSame('nit-900123', $provider->identificacion);
        $this->assertSame(PurchaseProvider::ESTADO_ACTIVO, $provider->estado);
        $this->assertTrue($provider->es_proveedor);
        $this->assertFalse($provider->es_cliente);
        $this->assertSame(30, $provider->dias_pago);
    }

    public function test_store_validates_unique_identificacion_and_required_name(): void
    {
        PurchaseProvider::factory()->create(['identificacion' => 'DUP-1']);

        $this->actingAs($this->editor)
            ->from(route('purchase-requests.providers.create', ['module' => 'compras']))
            ->post(route('purchase-requests.providers.store', ['module' => 'compras']), $this->payload([
                'identificacion' => 'DUP-1',
            ]))
            ->assertSessionHasErrors('identificacion');

        $this->actingAs($this->editor)
            ->from(route('purchase-requests.providers.create', ['module' => 'compras']))
            ->post(route('purchase-requests.providers.store', ['module' => 'compras']), $this->payload([
                'identificacion' => 'NUEVO-2',
                'nombre' => '',
            ]))
            ->assertSessionHasErrors('nombre');
    }

    public function test_update_returns_to_active_filters_and_uppercases_text(): void
    {
        $provider = PurchaseProvider::factory()->create([
            'identificacion' => 'ACT-100',
            'nombre' => 'ORIGINAL',
        ]);

        $updateUrl = route('purchase-requests.providers.update', [
            'module' => 'compras',
            'purchase_provider' => $provider,
            'estado' => 'inactivo',
            'q' => 'ACT',
            'solo_proveedores' => '0',
        ]);

        $this->actingAs($this->editor)
            ->patch($updateUrl, $this->payload([
                'identificacion' => 'ACT-100',
                'nombre' => 'nombre editado',
                'ciudad' => 'bogotá',
            ]))
            ->assertRedirect(route('purchase-requests.providers.index', [
                'module' => 'compras',
                'q' => 'ACT',
                'estado' => 'inactivo',
                'solo_proveedores' => '0',
            ]));

        $provider->refresh();
        $this->assertSame('NOMBRE EDITADO', $provider->nombre);
        $this->assertSame('BOGOTÁ', $provider->ciudad);
    }

    /**
     * @param  array<string, mixed>  $overrides
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

    /**
     * @param  array<string, mixed>  $query
     */
    private function datatableUrl(array $query = []): string
    {
        return route('purchase-requests.providers.datatable', array_merge([
            'module' => 'compras',
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'order' => [['column' => 1, 'dir' => 'asc']],
        ], $query));
    }
}
