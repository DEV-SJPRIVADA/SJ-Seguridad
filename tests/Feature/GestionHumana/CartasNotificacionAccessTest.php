<?php

namespace Tests\Feature\GestionHumana;

use App\Models\User;
use App\Models\WordDocumentType;
use App\Services\Access\CartasNotificacionAccessService;
use App\Services\Navigation\SidebarVisibilityService;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartasNotificacionAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_cartas_notificacion_permissions_exist_in_catalog(): void
    {
        $names = PermissionCatalog::configuredNames();

        $this->assertTrue($names->contains('cartas_notificacion.edit'));
        $this->assertTrue($names->contains('view.board.gestion_humana.cartas_notificacion'));
        $this->assertFalse($names->contains('cartas_notificacion.view'));
        $this->assertFalse($names->contains('view.board.operaciones.cartas_notificacion'));
    }

    public function test_word_document_type_cartas_notificacion_is_seeded(): void
    {
        $type = WordDocumentType::query()->where('code', 'cartas_notificacion')->first();

        $this->assertNotNull($type);
        $this->assertSame('Cartas Notificación', $type->name);
        $this->assertTrue((bool) $type->is_active);
        $this->assertSame(
            'cartas_notificacion',
            config('employee_ficha.word_document_type_codes.cartas_notificacion')
        );
        $this->assertSame(500, (int) config('cartas_notificacion.max_rows'));
    }

    public function test_edit_permission_implies_board_visibility(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('cartas_notificacion.edit');

        $access = app(CartasNotificacionAccessService::class);
        $sidebar = app(SidebarVisibilityService::class);

        $this->assertTrue($access->canEdit($user));
        $this->assertTrue($access->canViewBoard($user));
        $this->assertTrue($sidebar->shouldShowBoard($user, 'gestion_humana', 'cartas_notificacion'));
        $this->assertFalse($sidebar->shouldShowBoard($user, 'operaciones', 'cartas_notificacion'));
    }

    public function test_board_only_sees_sidebar_but_index_forbidden(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('view.board.gestion_humana.cartas_notificacion');

        $access = app(CartasNotificacionAccessService::class);
        $sidebar = app(SidebarVisibilityService::class);

        $this->assertTrue($access->canViewBoard($user));
        $this->assertFalse($access->canEdit($user));
        $this->assertTrue($sidebar->shouldShowBoard($user, 'gestion_humana', 'cartas_notificacion'));

        $this->actingAs($user)
            ->get(route('gestion-humana.cartas-notificacion.index'))
            ->assertForbidden()
            ->assertSee('Cartas Notificación: Generar', false);
    }

    public function test_index_requires_edit_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.cartas-notificacion.index'))
            ->assertForbidden();
    }

    public function test_index_allows_edit_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'cartas_notificacion.edit',
            'view.board.gestion_humana.cartas_notificacion',
        ]);

        $this->actingAs($user)
            ->get(route('gestion-humana.cartas-notificacion.index'))
            ->assertOk()
            ->assertSee('Cartas Notificación', false)
            ->assertSee('Agregar fila', false)
            ->assertSee('Duración contrato', false)
            ->assertSee('cartasNotificacionGrid', false)
            ->assertDontSee('cartas_notificacion.view', false);
    }

    public function test_edit_alone_allows_index_without_board_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('cartas_notificacion.edit');

        $this->actingAs($user)
            ->get(route('gestion-humana.cartas-notificacion.index'))
            ->assertOk();
    }

    public function test_manage_users_bypass_allows_index(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('manage.users');

        $this->actingAs($user)
            ->get(route('gestion-humana.cartas-notificacion.index'))
            ->assertOk();
    }

    public function test_default_board_url_points_to_index(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->assertSame(
            route('gestion-humana.cartas-notificacion.index'),
            $user->defaultCartasNotificacionBoardUrl()
        );
    }

    public function test_administrador_does_not_receive_cartas_notificacion_by_default(): void
    {
        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->assignRole('administrador');

        $this->assertFalse($admin->can('cartas_notificacion.edit'));
        $this->assertFalse($admin->can('view.board.gestion_humana.cartas_notificacion'));
    }
}
