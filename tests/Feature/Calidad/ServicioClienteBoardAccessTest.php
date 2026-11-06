<?php

namespace Tests\Feature\Calidad;

use App\Models\ServicioClienteCategoria;
use App\Models\ServicioClienteFuente;
use App\Models\ServicioClienteResponsable;
use App\Models\ServicioClienteTipologia;
use App\Models\User;
use App\Services\Access\ServicioClienteAccessService;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicioClienteBoardAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_permissions_exist_only_for_calidad(): void
    {
        $names = PermissionCatalog::configuredNames();

        $this->assertTrue($names->contains('view.board.calidad.servicio_cliente'));
        $this->assertTrue($names->contains('servicio_cliente.fo_gi_22.view'));
        $this->assertTrue($names->contains('servicio_cliente.fo_gi_22.edit'));
        $this->assertTrue($names->contains('servicio_cliente.parameters.edit'));
        $this->assertFalse($names->contains('view.board.gestion_humana.servicio_cliente'));
        $this->assertSame('Servicio al cliente', config('access.boards.servicio_cliente'));
        $this->assertSame('calidad', config('access.board_canonical_areas.servicio_cliente.home'));
        $this->assertSame('Servicio al cliente', config('audit.modules.servicio_cliente.label'));
    }

    public function test_catalog_seed(): void
    {
        $this->assertSame(4, ServicioClienteTipologia::query()->count());
        $this->assertTrue(ServicioClienteTipologia::query()->where('code', 'PETICION')->where('name', 'PETICIÓN')->exists());
        $this->assertSame(5, ServicioClienteFuente::query()->count());
        $this->assertSame(0, ServicioClienteResponsable::query()->count());
        $this->assertSame(0, ServicioClienteCategoria::query()->count());
    }

    public function test_guest_routes_are_forbidden_without_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)->get(route('calidad.servicio-cliente.index'))->assertForbidden();
        $this->actingAs($user)->get(route('calidad.servicio-cliente.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('calidad.servicio-cliente.fo-gi-22.index'))->assertForbidden();
        $this->actingAs($user)->get(route('calidad.servicio-cliente.catalogos'))->assertForbidden();
    }

    public function test_viewer_sees_dashboard_and_list_not_catalogs(): void
    {
        $viewer = $this->userWith([
            'view.board.calidad.servicio_cliente',
            'servicio_cliente.fo_gi_22.view',
        ]);

        $this->assertSame(['dashboard', 'fo_gi_22'], app(ServicioClienteAccessService::class)->visibleTabsFor($viewer));
        $this->assertSame(route('calidad.servicio-cliente.dashboard'), $viewer->defaultServicioClienteBoardUrl());

        $this->actingAs($viewer)->get(route('calidad.servicio-cliente.dashboard'))->assertOk();
        $this->actingAs($viewer)->get(route('calidad.servicio-cliente.fo-gi-22.index'))->assertOk();
        $this->actingAs($viewer)->get(route('calidad.servicio-cliente.fo-gi-22.create'))->assertForbidden();
        $this->actingAs($viewer)->get(route('calidad.servicio-cliente.catalogos'))->assertForbidden();
    }

    public function test_administrador_role_receives_the_package(): void
    {
        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->assignRole('administrador');

        $this->assertTrue($admin->can('view.board.calidad.servicio_cliente'));
        $this->assertTrue($admin->can('servicio_cliente.fo_gi_22.edit'));
        $this->assertTrue($admin->can('servicio_cliente.parameters.edit'));
    }

    public function test_catalog_screen_lists_id_and_name_and_creates_without_code(): void
    {
        $user = $this->userWith([
            'view.board.calidad.servicio_cliente',
            'servicio_cliente.parameters.edit',
        ]);

        $this->actingAs($user)
            ->get(route('calidad.servicio-cliente.catalogos', ['catalog' => 'tipologias']))
            ->assertOk()
            ->assertSee('Id')
            ->assertSee('Nombre')
            ->assertSee('PETICIÓN')
            ->assertDontSee('Código')
            ->assertDontSee('>Orden<', false);

        $this->actingAs($user)
            ->post(route('calidad.servicio-cliente.catalogos.store', 'responsables'), [
                'name' => 'coordinador de calidad',
                'is_active' => '1',
            ])
            ->assertRedirect(route('calidad.servicio-cliente.catalogos', ['catalog' => 'responsables']));

        $item = ServicioClienteResponsable::query()->where('name', 'COORDINADOR DE CALIDAD')->first();
        $this->assertNotNull($item);
        $code = $item->code;
        $this->assertNotSame('', $code);

        $this->actingAs($user)
            ->patch(route('calidad.servicio-cliente.catalogos.update', ['responsables', $item]), [
                'name' => 'coordinador nacional',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $item->refresh();
        $this->assertSame('COORDINADOR NACIONAL', $item->name);
        $this->assertSame($code, $item->code);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWith(array $permissions): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo($permissions);

        return $user;
    }
}
