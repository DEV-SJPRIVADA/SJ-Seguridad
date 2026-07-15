<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AdminFinancieroAreaMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_config_uses_admin_financiero_instead_of_legacy_areas(): void
    {
        $areas = config('access.areas');

        $this->assertArrayHasKey('admin_financiero', $areas);
        $this->assertSame('Admin y Financiero', $areas['admin_financiero']);
        $this->assertArrayNotHasKey('remuneraciones', $areas);
        $this->assertArrayNotHasKey('facturacion', $areas);
    }

    public function test_migration_merges_legacy_area_keys_and_permissions(): void
    {
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

        Permission::findOrCreate('view.area.remuneraciones', 'web');
        Permission::findOrCreate('manage.area.facturacion', 'web');
        Permission::findOrCreate('view.board.remuneraciones.suministros', 'web');
        Permission::findOrCreate('view.board.facturacion.requisiciones', 'web');

        $user = User::factory()->create([
            'area_key' => 'facturacion',
            'must_change_password' => false,
        ]);
        $user->givePermissionTo([
            'view.area.remuneraciones',
            'view.board.facturacion.requisiciones',
        ]);

        \Illuminate\Support\Facades\DB::table('migrations')
            ->where('migration', '2026_07_10_120000_merge_remuneraciones_facturacion_into_admin_financiero')
            ->delete();

        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_07_10_120000_merge_remuneraciones_facturacion_into_admin_financiero.php',
            '--force' => true,
        ])->assertSuccessful();

        $user->refresh();

        $this->assertSame('admin_financiero', $user->area_key);
        $this->assertTrue($user->can('view.area.admin_financiero'));
        $this->assertTrue($user->can('view.board.admin_financiero.requisiciones'));
        $this->assertFalse(Permission::query()->where('name', 'view.area.remuneraciones')->exists());
        $this->assertFalse(Permission::query()->where('name', 'manage.area.facturacion')->exists());
    }
}
