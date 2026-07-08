<?php

namespace Tests\Feature;

use App\Models\SupplySite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplySiteManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    public function test_admin_can_list_supply_sites_as_json(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->getJson(route('admin.supply-sites.index'));

        $response->assertOk();
        $response->assertJsonStructure(['sites']);
    }

    public function test_admin_can_create_supply_site_from_user_management(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->postJson(route('admin.supply-sites.store'), [
            'utilization' => 'Sede Norte',
            'city' => 'Bogota',
            'is_active' => true,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('supply_sites', [
            'utilization' => 'Sede Norte',
            'city' => 'Bogota',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_supply_site(): void
    {
        $admin = $this->adminUser();
        $site = SupplySite::query()->create([
            'name' => 'test_site',
            'utilization' => 'Sede Test',
            'city' => 'Cali',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patchJson(route('admin.supply-sites.update', $site), [
            'utilization' => 'Sede Test Actualizada',
            'city' => 'Cali',
            'is_active' => false,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('supply_sites', [
            'id' => $site->id,
            'utilization' => 'Sede Test Actualizada',
            'is_active' => false,
        ]);
    }

    public function test_admin_cannot_delete_supply_site_with_users(): void
    {
        $admin = $this->adminUser();
        $site = SupplySite::query()->create([
            'name' => 'used_site',
            'utilization' => 'Sede Usada',
            'city' => 'Cali',
            'is_active' => true,
        ]);

        User::factory()->create([
            'sede_id' => $site->id,
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($admin)->deleteJson(route('admin.supply-sites.destroy', $site));

        $response->assertStatus(422);
        $this->assertDatabaseHas('supply_sites', ['id' => $site->id]);
    }

    public function test_user_create_form_shows_manage_sites_button(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->get(route('admin.users.create'));

        $response->assertOk();
        $response->assertSee('Gestionar sedes físicas', false);
        $response->assertSee('id="open-sites-modal"', false);
    }

    private function adminUser(): User
    {
        $admin = User::where('email', env('ADMIN_EMAIL', 'admin@sjseguridad.local'))->firstOrFail();
        $admin->update(['must_change_password' => false]);

        return $admin;
    }
}
