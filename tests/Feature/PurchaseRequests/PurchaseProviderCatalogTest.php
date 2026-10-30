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

        $response = $this->actingAs($this->editor)
            ->getJson($this->datatableUrl())
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.1', 'PROVEEDOR ACTIVO');

        $this->assertSame((string) $activo->identificacion, $response->json('data.0.0'));
    }

    public function test_datatable_filters_by_estado(): void
    {
        PurchaseProvider::factory()->create(['nombre' => 'PROVEEDOR ACTIVO']);
        PurchaseProvider::factory()->inactivo()->create(['nombre' => 'PROVEEDOR INACTIVO']);

        $this->actingAs($this->editor)
            ->getJson($this->datatableUrl(['estado' => 'todos']))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 2);

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

        // Buscador de DataTables (`search[value]`).
        $this->actingAs($this->editor)
            ->getJson($this->datatableUrl(['search' => ['value' => 'BUSCADO']]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 2);

        // Compatibilidad con el término en `q` (export/URL).
        $this->actingAs($this->editor)
            ->getJson($this->datatableUrl(['q' => 'NIT-BUSCADO']))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1);
    }

    public function test_datatable_includes_direccion_and_searches_it(): void
    {
        PurchaseProvider::factory()->create([
            'identificacion' => 'DIR-1',
            'nombre' => 'PROVEEDOR DIRECCION',
            'direccion' => 'AVENIDA SIEMPRE VIVA 123',
        ]);

        $this->actingAs($this->editor)
            ->getJson($this->datatableUrl(['search' => ['value' => 'SIEMPRE VIVA']]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath(
                'data.0.6',
                '<span class="purchase-providers-page__address" title="AVENIDA SIEMPRE VIVA 123">AVENIDA SIEMPRE VIVA 123</span>',
            );
    }

    public function test_store_persists_free_text_in_uppercase_except_catalog_and_codes(): void
    {
        $this->actingAs($this->editor)
            ->post(route('purchase-requests.providers.store', ['module' => 'compras']), $this->payload([
                'identificacion' => 'nit-900123',
                'ciudad' => 'Cali',
            ]))
            ->assertRedirect(route('purchase-requests.providers.index', ['module' => 'compras']));

        $provider = PurchaseProvider::query()->where('identificacion', 'nit-900123')->firstOrFail();

        $this->assertSame('PROVEEDOR DE PRUEBA', $provider->nombre);
        $this->assertSame('JUAN PEREZ', $provider->contacto);
        $this->assertSame('CALLE 1 # 2-3', $provider->direccion);
        // La ciudad viene del catálogo de Requisiciones: conserva su forma.
        $this->assertSame('Cali', $provider->ciudad);
        $this->assertSame('TRANSFERENCIA', $provider->forma_pago);
        // Email y códigos técnicos conservan el valor original.
        $this->assertSame('Contacto@Proveedor.com', $provider->correo);
        $this->assertSame('nit-900123', $provider->identificacion);
        $this->assertSame(PurchaseProvider::ESTADO_ACTIVO, $provider->estado);
        $this->assertSame(30, $provider->dias_pago);
    }

    public function test_store_validates_phone_email_and_city_catalog(): void
    {
        $this->actingAs($this->editor)
            ->from(route('purchase-requests.providers.create', ['module' => 'compras']))
            ->post(route('purchase-requests.providers.store', ['module' => 'compras']), $this->payload([
                'telefono' => 'ABC-123',
                'correo' => 'no-es-correo',
                'ciudad' => 'Ciudad Inexistente',
            ]))
            ->assertSessionHasErrors(['telefono', 'correo', 'ciudad']);
    }

    public function test_views_use_icon_only_actions_and_city_catalog(): void
    {
        $this->actingAs($this->editor)
            ->get(route('purchase-requests.providers.index', ['module' => 'compras']))
            ->assertOk()
            ->assertSee('title="Nuevo proveedor"', false)
            ->assertDontSee('>Nuevo proveedor<')
            ->assertDontSee('Solicitudes de compra')
            // La clasificación cliente/proveedor ya no se gestiona en el catálogo.
            ->assertDontSee('Solo proveedores')
            ->assertDontSee('Es cliente')
            // El buscador es el de DataTables.
            ->assertSee('providers-datatable', false)
            ->assertSee('req-manage-dt-top', false)
            // Importación / plantilla VINE01 (T3).
            ->assertSee('title="Descargar plantilla VINE01"', false)
            ->assertSee('title="Importar VINE01"', false)
            ->assertSee('provider-import', false);

        $this->actingAs($this->editor)
            ->get(route('purchase-requests.providers.create', ['module' => 'compras']))
            ->assertOk()
            ->assertSee('name="contacto"', false)
            ->assertSee('name="ciudad"', false)
            ->assertSee('name="forma_pago"', false)
            ->assertSee('type="tel"', false)
            ->assertSee('Cali')
            ->assertSee('aria-label="Crear proveedor"', false)
            ->assertDontSee('Clasificación')
            ->assertDontSee('name="es_proveedor"', false);
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
        ]);

        $this->actingAs($this->editor)
            ->patch($updateUrl, $this->payload([
                'identificacion' => 'ACT-100',
                'nombre' => 'nombre editado',
                'ciudad' => 'Puerto Tejada',
            ]))
            ->assertRedirect(route('purchase-requests.providers.index', [
                'module' => 'compras',
                'q' => 'ACT',
                'estado' => 'inactivo',
            ]));

        $provider->refresh();
        $this->assertSame('NOMBRE EDITADO', $provider->nombre);
        $this->assertSame('Puerto Tejada', $provider->ciudad);
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
            'estado' => 'activo',
            'dias_pago' => '30',
            'contacto' => 'juan perez',
            'direccion' => 'calle 1 # 2-3',
            'ciudad' => 'Cali',
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
