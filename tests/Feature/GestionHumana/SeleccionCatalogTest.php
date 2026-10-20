<?php

namespace Tests\Feature\GestionHumana;

use App\Models\PayrollCatalogItem;
use App\Models\User;
use App\Services\GestionHumana\EmployeeFichaCatalogService;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeleccionCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_managed_catalog_types_whitelist_has_nine_types(): void
    {
        $types = config('seleccion.managed_catalog_types');

        $this->assertSame([
            'city',
            'position',
            'eps',
            'afp',
            'blood_type',
            'marital_status',
            'seleccion_solicitud_status',
            'ingreso_estado',
            'jefe_operativo',
        ], $types);
    }

    public function test_codeless_catalog_types_are_managed(): void
    {
        $this->assertSame([
            'blood_type',
            'marital_status',
            'seleccion_solicitud_status',
            'ingreso_estado',
            'jefe_operativo',
        ], config('seleccion.codeless_catalog_types'));

        foreach (config('seleccion.codeless_catalog_types') as $type) {
            $this->assertContains($type, config('seleccion.managed_catalog_types'));
        }
    }

    public function test_seed_creates_blood_type_marital_and_solicitud_status_rows(): void
    {
        $this->assertDatabaseHas('payroll_catalog_items', [
            'catalog_type' => 'blood_type',
            'code' => 'O+',
            'name' => 'O+',
        ]);

        $this->assertDatabaseHas('payroll_catalog_items', [
            'catalog_type' => 'blood_type',
            'code' => 'AB-',
        ]);

        $this->assertSame(8, PayrollCatalogItem::query()->ofType('blood_type')->count());

        $this->assertDatabaseHas('payroll_catalog_items', [
            'catalog_type' => 'marital_status',
            'code' => 'SOLTERO',
            'name' => 'Soltero/a',
        ]);

        $this->assertSame(5, PayrollCatalogItem::query()->ofType('marital_status')->count());

        $this->assertDatabaseHas('payroll_catalog_items', [
            'catalog_type' => 'seleccion_solicitud_status',
            'code' => 'EN_PROCESO',
            'name' => 'EN PROCESO',
        ]);

        $this->assertDatabaseHas('payroll_catalog_items', [
            'catalog_type' => 'seleccion_solicitud_status',
            'code' => 'DXEMO',
        ]);

        $this->assertSame(11, PayrollCatalogItem::query()->ofType('seleccion_solicitud_status')->count());
        $this->assertSame(1, PayrollCatalogItem::query()
            ->ofType('seleccion_solicitud_status')
            ->where('code', 'DXEMO')
            ->count());
    }

    public function test_ficha_admin_excludes_seleccion_only_catalog_types(): void
    {
        $excluded = config('employee_ficha.ficha_admin_excluded_catalog_types', []);

        $this->assertContains('blood_type', $excluded);
        $this->assertContains('marital_status', $excluded);
        $this->assertContains('seleccion_solicitud_status', $excluded);

        $service = app(EmployeeFichaCatalogService::class);

        $this->assertTrue($service->isValidType('blood_type'));
        $this->assertTrue($service->isValidType('marital_status'));
        $this->assertTrue($service->isValidType('seleccion_solicitud_status'));

        $adminKeys = collect($service->catalogsForAdmin())->pluck('key')->all();

        $this->assertNotContains('blood_type', $adminKeys);
        $this->assertNotContains('marital_status', $adminKeys);
        $this->assertNotContains('seleccion_solicitud_status', $adminKeys);
        $this->assertContains('eps', $adminKeys);
    }

    public function test_ficha_catalogs_ui_does_not_list_blood_type(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.catalogs.index'))
            ->assertOk()
            ->assertDontSee('data-catalog-key="blood_type"', false)
            ->assertDontSee('data-catalog-key="marital_status"', false)
            ->assertDontSee('data-catalog-key="seleccion_solicitud_status"', false)
            ->assertSee('data-catalog-key="eps"', false);
    }

    public function test_editor_can_open_catalogos_and_see_whitelist_cards(): void
    {
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->get(route('gestion-humana.seleccion.catalogos'))
            ->assertOk()
            ->assertSee('data-catalog-key="city"', false)
            ->assertSee('data-catalog-key="blood_type"', false)
            ->assertSee('data-catalog-key="seleccion_solicitud_status"', false)
            ->assertSee('RH / Grupo sanguíneo', false);
    }

    public function test_store_rejects_type_outside_whitelist(): void
    {
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->post(route('gestion-humana.seleccion.catalogos.store', ['type' => 'bank']), [
                'code' => 'X1',
                'name' => 'Banco inventado',
                'is_active' => '1',
                'sort_order' => 1,
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('payroll_catalog_items', [
            'catalog_type' => 'bank',
            'code' => 'X1',
        ]);
    }

    public function test_store_with_code_and_order_for_coded_catalog_type(): void
    {
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->post(route('gestion-humana.seleccion.catalogos.store', ['type' => 'eps']), [
                'code' => 'EPS_TEST',
                'name' => 'EPS de prueba',
                'is_active' => '1',
                'sort_order' => 99,
            ])
            ->assertRedirect(route('gestion-humana.seleccion.catalogos', ['catalog' => 'eps']));

        $this->assertDatabaseHas('payroll_catalog_items', [
            'catalog_type' => 'eps',
            'code' => 'EPS_TEST',
            'name' => 'EPS de prueba',
            'is_active' => true,
            'sort_order' => 99,
        ]);
    }

    public function test_store_codeless_catalog_generates_code_and_appends_order(): void
    {
        $editor = $this->editorUser();
        $maxOrder = (int) PayrollCatalogItem::query()->ofType('blood_type')->max('sort_order');

        $this->actingAs($editor)
            ->post(route('gestion-humana.seleccion.catalogos.store', ['type' => 'blood_type']), [
                'code' => 'IGNORADO',
                'name' => 'Rh nulo+',
                'is_active' => '1',
                'sort_order' => 0,
            ])
            ->assertRedirect(route('gestion-humana.seleccion.catalogos', ['catalog' => 'blood_type']));

        $this->assertDatabaseHas('payroll_catalog_items', [
            'catalog_type' => 'blood_type',
            'code' => 'RH_NULO+',
            'name' => 'Rh nulo+',
            'sort_order' => $maxOrder + 1,
        ]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.seleccion.catalogos.store', ['type' => 'marital_status']), [
                'name' => 'Separado de hecho',
                'is_active' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payroll_catalog_items', [
            'catalog_type' => 'marital_status',
            'code' => 'SEPARADO_DE_HECHO',
        ]);
    }

    public function test_store_codeless_catalog_rejects_duplicate_name(): void
    {
        $editor = $this->editorUser();

        PayrollCatalogItem::query()->create([
            'catalog_type' => 'jefe_operativo',
            'code' => 'JUAN_PEREZ',
            'name' => 'JUAN PEREZ',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.seleccion.catalogos.store', ['type' => 'jefe_operativo']), [
                'name' => 'JUAN PEREZ',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, PayrollCatalogItem::query()->ofType('jefe_operativo')->where('name', 'JUAN PEREZ')->count());
    }

    public function test_codeless_catalog_ui_hides_code_and_order_fields(): void
    {
        $editor = $this->editorUser();

        $html = $this->actingAs($editor)
            ->get(route('gestion-humana.seleccion.catalogos'))
            ->assertOk()
            ->assertDontSee('id="code_new_ingreso_estado"', false)
            ->assertDontSee('id="sort_new_ingreso_estado"', false)
            ->assertDontSee('id="code_new_jefe_operativo"', false)
            ->assertSee('id="code_new_eps"', false)
            ->assertSee('id="sort_new_eps"', false)
            ->getContent();

        $this->assertStringContainsString('data-codeless="1"', $html);
    }

    public function test_update_and_destroy_managed_catalog_item(): void
    {
        $editor = $this->editorUser();

        $item = PayrollCatalogItem::query()->create([
            'catalog_type' => 'marital_status',
            'code' => 'TEMP_STATUS',
            'name' => 'Temporal',
            'is_active' => true,
            'sort_order' => 50,
        ]);

        $this->actingAs($editor)
            ->patch(route('gestion-humana.seleccion.catalogos.update', ['type' => 'marital_status', 'item' => $item->id]), [
                'code' => 'OTRO_CODIGO',
                'name' => 'Temporal editado',
                'is_active' => '0',
                'sort_order' => 51,
            ])
            ->assertRedirect(route('gestion-humana.seleccion.catalogos', ['catalog' => 'marital_status']));

        // Catálogo sin Código/Orden: se conservan los valores existentes.
        $this->assertDatabaseHas('payroll_catalog_items', [
            'id' => $item->id,
            'code' => 'TEMP_STATUS',
            'name' => 'Temporal editado',
            'is_active' => false,
            'sort_order' => 50,
        ]);

        $this->actingAs($editor)
            ->delete(route('gestion-humana.seleccion.catalogos.destroy', ['type' => 'marital_status', 'item' => $item->id]))
            ->assertRedirect(route('gestion-humana.seleccion.catalogos', ['catalog' => 'marital_status']));

        $this->assertDatabaseMissing('payroll_catalog_items', [
            'id' => $item->id,
        ]);
    }

    public function test_permission_catalog_sync_keeps_seleccion_permissions(): void
    {
        PermissionCatalog::sync();

        $names = PermissionCatalog::configuredNames();

        $this->assertTrue($names->contains('seleccion.view'));
        $this->assertTrue($names->contains('seleccion.edit'));
        $this->assertTrue($names->contains('view.board.gestion_humana.seleccion'));
    }

    private function editorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.seleccion',
            'seleccion.view',
            'seleccion.edit',
        ]);

        return $user;
    }
}
