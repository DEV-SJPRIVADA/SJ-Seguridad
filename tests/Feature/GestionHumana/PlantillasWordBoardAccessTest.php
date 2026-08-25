<?php

namespace Tests\Feature\GestionHumana;

use App\Models\User;
use App\Services\GestionHumana\PlantillasWordAccessService;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlantillasWordBoardAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_plantillas_word_permissions_exist_in_catalog(): void
    {
        $names = PermissionCatalog::configuredNames();

        $this->assertTrue($names->contains('plantillas_word.view'));
        $this->assertTrue($names->contains('plantillas_word.manage'));
        $this->assertTrue($names->contains('view.board.gestion_humana.plantillas_word'));
        $this->assertFalse($names->contains('view.board.operaciones.plantillas_word'));
    }

    public function test_plantillas_word_access_service_manage_implies_view(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('plantillas_word.manage');

        $service = app(PlantillasWordAccessService::class);

        $this->assertTrue($service->canView($user));
        $this->assertTrue($service->canManage($user));
    }

    public function test_plantillas_word_index_requires_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.plantillas-word.index'))
            ->assertForbidden();
    }

    public function test_plantillas_word_index_allows_view_permission(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->givePermissionTo([
            'plantillas_word.view',
            'view.board.gestion_humana.plantillas_word',
        ]);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.plantillas-word.index'))
            ->assertOk()
            ->assertSee('Plantillas Word', false);
    }

    public function test_plantillas_word_index_allows_manage_permission(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo([
            'plantillas_word.manage',
            'view.board.gestion_humana.plantillas_word',
        ]);

        $this->actingAs($manager)
            ->get(route('gestion-humana.plantillas-word.index'))
            ->assertOk();
    }

    public function test_administrador_role_receives_plantillas_word_defaults(): void
    {
        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->assignRole('administrador');

        $this->assertTrue($admin->can('view.board.gestion_humana.plantillas_word'));
        $this->assertTrue($admin->can('plantillas_word.view'));
        $this->assertTrue($admin->can('plantillas_word.manage'));
    }

    public function test_word_document_type_code_config_is_stable(): void
    {
        $this->assertSame(
            'desvinculacion',
            config('employee_ficha.word_document_type_codes.desvinculacion')
        );
        $this->assertNull(config('employee_ficha.termination_letter_packs'));
        $this->assertNull(config('employee_ficha.termination_letter_supported_causes'));
    }
}
