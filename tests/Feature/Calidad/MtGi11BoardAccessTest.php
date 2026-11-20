<?php

namespace Tests\Feature\Calidad;

use App\Models\Calidad\MtGi11CargoResponsable;
use App\Models\Calidad\MtGi11Estado;
use App\Models\Calidad\MtGi11Proceso;
use App\Models\Calidad\MtGi11TipoDocumento;
use App\Models\Calidad\MtGi11TipoSolicitud;
use App\Models\User;
use App\Services\Access\MtGi11AccessService;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MtGi11BoardAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        PermissionCatalog::sync();
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWith(array $permissions): User
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo($permissions);

        return $user;
    }

    public function test_permissions_and_board_are_configured_for_calidad_only(): void
    {
        $names = PermissionCatalog::configuredNames();

        $this->assertTrue($names->contains('view.board.calidad.mt_gi_11'));
        $this->assertTrue($names->contains('mt_gi_11.matriz.view'));
        $this->assertTrue($names->contains('mt_gi_11.matriz.edit'));
        $this->assertTrue($names->contains('mt_gi_11.view_all'));
        $this->assertTrue($names->contains('mt_gi_11.parameters.edit'));

        $this->assertFalse($names->contains('view.board.gestion_humana.mt_gi_11'));

        $this->assertSame('MT-GI-11', config('access.boards.mt_gi_11'));
        $this->assertSame('calidad', config('access.board_canonical_areas.mt_gi_11.home'));
        $this->assertSame(['dashboard', 'matriz', 'catalogos'], array_keys(config('access.mt_gi_11_tabs')));
        $this->assertSame('MT-GI-11 Matrices', config('audit.modules.mt_gi_11.label'));
    }

    public function test_catalogs_are_seeded_with_colors(): void
    {
        $this->assertSame(13, MtGi11Proceso::query()->count());
        $this->assertSame(14, MtGi11TipoDocumento::query()->count());
        $this->assertSame(4, MtGi11TipoSolicitud::query()->count());
        $this->assertSame(0, MtGi11CargoResponsable::query()->count());

        $this->assertSame(4, MtGi11Estado::query()->count());
        $this->assertSame(
            'warning',
            MtGi11Estado::query()->where('code', 'PENDIENTE')->value('color'),
        );
        $this->assertSame(
            'APROBADA',
            MtGi11Estado::query()->where('color', 'success')->value('name'),
        );
    }

    public function test_user_without_board_permission_is_blocked(): void
    {
        $user = $this->userWith(['view.dashboard']);

        $this->actingAs($user)->get(route('calidad.mt-gi-11.matriz'))->assertForbidden();
        $this->actingAs($user)->get(route('calidad.mt-gi-11.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('calidad.mt-gi-11.catalogos'))->assertForbidden();
    }

    public function test_view_permission_opens_dashboard_and_matriz_but_not_catalogs(): void
    {
        $user = $this->userWith([
            'view.dashboard',
            'view.board.calidad.mt_gi_11',
            'mt_gi_11.matriz.view',
        ]);

        $this->actingAs($user)->get(route('calidad.mt-gi-11.dashboard'))->assertOk();
        $this->actingAs($user)->get(route('calidad.mt-gi-11.matriz'))->assertOk();
        $this->actingAs($user)->get(route('calidad.mt-gi-11.catalogos'))->assertForbidden();
    }

    public function test_edit_implies_view(): void
    {
        $user = $this->userWith([
            'view.dashboard',
            'view.board.calidad.mt_gi_11',
            'mt_gi_11.matriz.edit',
        ]);

        $access = app(MtGi11AccessService::class);

        $this->assertTrue($access->canView($user));
        $this->assertTrue($access->canEdit($user));
    }

    public function test_index_redirects_to_first_visible_tab(): void
    {
        $user = $this->userWith([
            'view.dashboard',
            'view.board.calidad.mt_gi_11',
            'mt_gi_11.matriz.view',
        ]);

        $this->actingAs($user)
            ->get(route('calidad.mt-gi-11.index'))
            ->assertRedirect(route('calidad.mt-gi-11.dashboard'));
    }

    public function test_parameters_permission_opens_catalogs_only(): void
    {
        $user = $this->userWith([
            'view.dashboard',
            'view.board.calidad.mt_gi_11',
            'mt_gi_11.parameters.edit',
        ]);

        $this->actingAs($user)->get(route('calidad.mt-gi-11.catalogos'))->assertOk();
        $this->actingAs($user)->get(route('calidad.mt-gi-11.matriz'))->assertForbidden();
    }

    public function test_admin_bypass_has_full_access(): void
    {
        $user = $this->userWith(['view.dashboard', 'manage.users']);
        $access = app(MtGi11AccessService::class);

        $this->assertTrue($access->canViewBoard($user));
        $this->assertTrue($access->canEdit($user));
        $this->assertTrue($access->canViewAll($user));
        $this->assertTrue($access->canEditParameters($user));
    }
}
