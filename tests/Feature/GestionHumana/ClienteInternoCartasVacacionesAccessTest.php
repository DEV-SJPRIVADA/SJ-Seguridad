<?php

namespace Tests\Feature\GestionHumana;

use App\Models\User;
use App\Models\WordDocumentType;
use App\Services\Access\ClienteInternoAccessService;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\WordDocumentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ClienteInternoCartasVacacionesAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_cartas_vacaciones_placeholders_and_word_type_code_are_configured(): void
    {
        $placeholders = config('employee_ficha.letter_placeholders.Cartas vacaciones');

        $this->assertSame('Fecha de inicio de vacaciones (formato largo en MAYÚSCULAS)', $placeholders['FECHA_INICIO']);
        $this->assertSame('Fecha de fin de vacaciones (formato largo en MAYÚSCULAS)', $placeholders['FECHA_FIN']);
        $this->assertSame('Fecha de reintegro laboral (formato largo en MAYÚSCULAS)', $placeholders['FECHA_REINTEGRO']);
        $this->assertSame('Periodos de vacaciones a disfrutar', $placeholders['PERIODOS']);
        $this->assertSame('Días disfrutados de vacaciones', $placeholders['DIAS_DISFRUTADOS']);
        $this->assertSame(
            'cartas_vacaciones',
            config('employee_ficha.word_document_type_codes.cartas_vacaciones')
        );
    }

    public function test_word_document_type_seeder_creates_cartas_vacaciones(): void
    {
        $this->seed(WordDocumentTypeSeeder::class);

        $type = WordDocumentType::query()->where('code', 'cartas_vacaciones')->first();

        $this->assertNotNull($type);
        $this->assertSame('Cartas Vacaciones', $type->name);
        $this->assertTrue((bool) $type->is_active);
        $this->assertSame(3, (int) $type->sort_order);

        $this->seed(WordDocumentTypeSeeder::class);
        $this->assertSame(1, WordDocumentType::query()->where('code', 'cartas_vacaciones')->count());
    }

    public function test_cartas_only_view_sees_tab_without_dashboard(): void
    {
        $user = $this->cartasViewerUser();
        $service = app(ClienteInternoAccessService::class);

        $this->assertFalse($service->canViewDashboard($user));
        $this->assertTrue($service->canViewCartasVacaciones($user));
        $this->assertFalse($service->canEditCartasVacaciones($user));
        $this->assertSame(['cartas_vacaciones'], $service->visibleTabsFor($user));

        $this->actingAs($user)
            ->get(route('gestion-humana.cliente-interno.dashboard'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('gestion-humana.cliente-interno.cartas-vacaciones'))
            ->assertOk()
            ->assertSee('Cartas Vacaciones', false)
            ->assertSee('No puede generar ni descargar cartas', false);
    }

    public function test_cartas_edit_implies_view_without_view_spatie(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.cliente_interno',
            'cliente_interno.cartas_vacaciones.edit',
        ]);

        $service = app(ClienteInternoAccessService::class);

        $this->assertTrue($service->canViewCartasVacaciones($user));
        $this->assertTrue($service->canEditCartasVacaciones($user));
        $this->assertFalse($service->canViewDashboard($user));
        $this->assertSame(['cartas_vacaciones'], $service->visibleTabsFor($user));

        $this->actingAs($user)
            ->get(route('gestion-humana.cliente-interno.cartas-vacaciones'))
            ->assertOk()
            ->assertSee('Generar cartas', false);
    }

    public function test_index_redirects_cartas_only_user_to_cartas_vacaciones(): void
    {
        $user = $this->cartasViewerUser();

        $this->actingAs($user)
            ->get(route('gestion-humana.cliente-interno.index'))
            ->assertRedirect(route('gestion-humana.cliente-interno.cartas-vacaciones'));

        $this->assertSame(
            route('gestion-humana.cliente-interno.cartas-vacaciones'),
            $user->defaultClienteInternoBoardUrl()
        );
    }

    public function test_cartas_vacaciones_requires_view_or_edit_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('view.board.gestion_humana.cliente_interno');

        $this->actingAs($user)
            ->get(route('gestion-humana.cliente-interno.cartas-vacaciones'))
            ->assertForbidden();
    }

    public function test_migration_grants_cartas_view_and_edit_from_solicitudes_edit(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('cliente_interno.solicitudes.edit');

        $role = Role::findOrCreate('ci-cartas-migrate-role', 'web');
        $role->givePermissionTo('cliente_interno.solicitudes.edit');

        foreach (['cliente_interno.cartas_vacaciones.view', 'cliente_interno.cartas_vacaciones.edit'] as $permission) {
            $user->revokePermissionTo($permission);
            $role->revokePermissionTo($permission);
        }

        $user->unsetRelation('permissions');
        $role->unsetRelation('permissions');

        $this->assertFalse($user->fresh()->can('cliente_interno.cartas_vacaciones.view'));
        $this->assertFalse($user->fresh()->can('cliente_interno.cartas_vacaciones.edit'));
        $this->assertFalse($role->fresh()->hasPermissionTo('cliente_interno.cartas_vacaciones.view'));
        $this->assertFalse($role->fresh()->hasPermissionTo('cliente_interno.cartas_vacaciones.edit'));

        $migration = require database_path(
            'migrations/2026_10_07_131244_migrate_cliente_interno_solicitudes_edit_to_cartas_vacaciones_permissions.php'
        );
        $migration->up();

        $user->unsetRelation('permissions');
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->assertTrue($user->fresh()->can('cliente_interno.cartas_vacaciones.view'));
        $this->assertTrue($user->fresh()->can('cliente_interno.cartas_vacaciones.edit'));
        $this->assertTrue($role->fresh()->hasPermissionTo('cliente_interno.cartas_vacaciones.view'));
        $this->assertTrue($role->fresh()->hasPermissionTo('cliente_interno.cartas_vacaciones.edit'));

        $viewId = Permission::query()->where('name', 'cliente_interno.cartas_vacaciones.view')->value('id');
        $editId = Permission::query()->where('name', 'cliente_interno.cartas_vacaciones.edit')->value('id');

        $this->assertSame(
            1,
            DB::table('model_has_permissions')
                ->where('model_type', User::class)
                ->where('model_id', $user->id)
                ->where('permission_id', $viewId)
                ->count()
        );
        $this->assertSame(
            1,
            DB::table('model_has_permissions')
                ->where('model_type', User::class)
                ->where('model_id', $user->id)
                ->where('permission_id', $editId)
                ->count()
        );

        $migration->up();

        $this->assertSame(
            1,
            DB::table('role_has_permissions')
                ->where('role_id', $role->id)
                ->where('permission_id', $viewId)
                ->count()
        );
    }

    private function cartasViewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.cliente_interno',
            'cliente_interno.cartas_vacaciones.view',
        ]);

        return $user;
    }
}
