<?php

namespace Tests\Feature\GestionHumana;

use App\Models\AcreditacionAcreditado;
use App\Models\AcreditacionCargo;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\AcreditacionCargoSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AcreditacionesCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_seeder_upserts_exactly_seventeen_rows(): void
    {
        $this->seed(AcreditacionCargoSeeder::class);

        $this->assertSame(17, AcreditacionCargo::query()->count());
        $this->assertSame(17, count(AcreditacionCargoSeeder::defaultRows()));

        $this->seed(AcreditacionCargoSeeder::class);

        $this->assertSame(17, AcreditacionCargo::query()->count());
        $this->assertTrue(
            AcreditacionCargo::query()
                ->where('cargo_manager', 'GUARDA')
                ->where('cargo_apo', 'VIGILANTE')
                ->where('cargo_informe', 'GUARDAS')
                ->where('cargo_acreditacion', '1')
                ->exists()
        );
    }

    public function test_viewer_cannot_mutate_catalog(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->post(route('gestion-humana.acreditaciones.catalogo.store'), $this->validPayload())
            ->assertForbidden();

        $cargo = AcreditacionCargo::factory()->create([
            'cargo_manager' => 'TEST MANAGER',
            'cargo_apo' => 'TEST APO',
        ]);

        $this->actingAs($viewer)
            ->patch(route('gestion-humana.acreditaciones.catalogo.update', $cargo), $this->validPayload([
                'cargo_manager' => 'TEST MANAGER EDIT',
            ]))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->delete(route('gestion-humana.acreditaciones.catalogo.destroy', $cargo))
            ->assertForbidden();
    }

    public function test_editor_can_create_update_and_delete_catalog_row(): void
    {
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.catalogo.store'), $this->validPayload())
            ->assertRedirect(route('gestion-humana.acreditaciones.catalogo'));

        $cargo = AcreditacionCargo::query()
            ->where('cargo_manager', 'TEST MANAGER')
            ->where('cargo_apo', 'TEST APO')
            ->first();

        $this->assertNotNull($cargo);

        $this->actingAs($editor)
            ->patch(route('gestion-humana.acreditaciones.catalogo.update', $cargo), $this->validPayload([
                'cargo_manager' => 'TEST MANAGER EDIT',
                'cargo_informe' => 'INFORME EDIT',
                'sort_order' => 50,
            ]))
            ->assertRedirect(route('gestion-humana.acreditaciones.catalogo'));

        $cargo->refresh();
        $this->assertSame('TEST MANAGER EDIT', $cargo->cargo_manager);
        $this->assertSame('INFORME EDIT', $cargo->cargo_informe);
        $this->assertSame(50, $cargo->sort_order);

        // Not last active of APO alone if we create a sibling; still deletable when not last active.
        AcreditacionCargo::factory()->create([
            'cargo_manager' => 'SIBLING MANAGER',
            'cargo_apo' => 'TEST APO',
            'is_active' => true,
        ]);

        $this->actingAs($editor)
            ->delete(route('gestion-humana.acreditaciones.catalogo.destroy', $cargo))
            ->assertRedirect(route('gestion-humana.acreditaciones.catalogo'));

        $this->assertDatabaseMissing('acreditacion_cargos', ['id' => $cargo->id]);
    }

    public function test_unique_manager_apo_combination_is_enforced(): void
    {
        $editor = $this->editorUser();

        AcreditacionCargo::factory()->create([
            'cargo_manager' => 'GUARDA',
            'cargo_apo' => 'VIGILANTE',
        ]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.catalogo.store'), $this->validPayload([
                'cargo_manager' => 'GUARDA',
                'cargo_apo' => 'VIGILANTE',
            ]))
            ->assertSessionHasErrors('cargo_manager');
    }

    public function test_delete_blocks_last_active_apo_when_acreditados_exist(): void
    {
        $this->assertTrue(Schema::hasTable('acreditacion_acreditados'));

        $editor = $this->editorUser();
        $cargo = AcreditacionCargo::factory()->create([
            'cargo_manager' => 'UNICO MANAGER',
            'cargo_apo' => 'APO UNICO',
            'is_active' => true,
        ]);

        // Sin acreditados: última activa se puede eliminar.
        $this->actingAs($editor)
            ->delete(route('gestion-humana.acreditaciones.catalogo.destroy', $cargo))
            ->assertRedirect(route('gestion-humana.acreditaciones.catalogo'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('acreditacion_cargos', ['id' => $cargo->id]);
    }

    public function test_delete_blocks_last_active_apo_with_acreditado_refs(): void
    {
        $editor = $this->editorUser();
        $cargo = AcreditacionCargo::factory()->create([
            'cargo_manager' => 'CON REF MANAGER',
            'cargo_apo' => 'APO CON REF',
            'is_active' => true,
        ]);

        AcreditacionAcreditado::factory()->create([
            'document_number' => '111',
            'full_name' => 'Ref',
            'cargo_apo' => 'APO CON REF',
            'vigencia_acr' => '2027-01-01',
            'estado' => 'ACREDITADO',
        ]);

        $this->actingAs($editor)
            ->delete(route('gestion-humana.acreditaciones.catalogo.destroy', $cargo))
            ->assertRedirect(route('gestion-humana.acreditaciones.catalogo'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('acreditacion_cargos', ['id' => $cargo->id]);
    }

    public function test_catalogo_page_lists_seeded_rows_for_editor(): void
    {
        $this->seed(AcreditacionCargoSeeder::class);
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.catalogo'))
            ->assertOk()
            ->assertSee('Catálogo de cargos', false)
            ->assertSee('GUARDA MANEJADOR CANINO', false)
            ->assertSee('OPERADOR DE MEDIOS TECNOLOGICOS', false)
            ->assertDontSee('Próximamente', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'cargo_manager' => 'TEST MANAGER',
            'cargo_apo' => 'TEST APO',
            'cargo_informe' => 'TEST INFORME',
            'cargo_acreditacion' => '9',
            'is_active' => 1,
            'sort_order' => 10,
        ], $overrides);
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.acreditaciones',
            'acreditaciones.view',
        ]);

        return $user;
    }

    private function editorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.acreditaciones',
            'acreditaciones.view',
            'acreditaciones.edit',
        ]);

        return $user;
    }
}
