<?php

namespace Tests\Feature\Matrices;

use App\Models\Matrices\Matrix;
use App\Models\Matrices\MatrixAreaAccess;
use App\Models\Matrices\MatrixUserAccess;
use App\Models\User;
use App\Services\Matrices\MatrixDefinitionService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Visibilidad del tablero Matrices:
 * - Las tarjetas de administración son solo para `matrices.manage`.
 * - Quien solo fue compartido entra directo a su primera matriz.
 * - Cada matriz propia o compartida aparece como pestaña.
 */
class MatrixVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function manager(): User
    {
        $user = User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => 'calidad',
        ]);
        $user->givePermissionTo('matrices.manage');

        return $user;
    }

    /**
     * Usuario compartido: sin `matrices.manage`, solo con otorgamiento por usuario.
     */
    private function sharedUser(string $areaKey = 'calidad'): User
    {
        return User::factory()->create([
            'is_active' => true,
            'must_change_password' => false,
            'area_key' => $areaKey,
        ]);
    }

    private function makeMatrix(User $owner, string $name = 'Matriz de prueba'): Matrix
    {
        $service = app(MatrixDefinitionService::class);

        $matrix = $service->create($owner, [
            'name' => $name,
            'description' => null,
            'area_key' => 'calidad',
            'fields' => [
                ['label' => 'Nombre', 'type' => 'text', 'required' => true, 'col_span' => 12, 'show_in_table' => true],
            ],
        ]);

        $service->publish($matrix, $owner);

        return $matrix->refresh();
    }

    public function test_manager_sees_admin_cards(): void
    {
        $manager = $this->manager();
        $this->makeMatrix($manager);

        $this->actingAs($manager)
            ->get(route('matrices.index', ['module' => 'calidad']))
            ->assertOk()
            ->assertSee('Nueva matriz')
            ->assertSee('matrices-grid', false);
    }

    public function test_shared_user_is_redirected_to_first_matrix(): void
    {
        $manager = $this->manager();
        $matrix = $this->makeMatrix($manager);

        $shared = $this->sharedUser();
        MatrixUserAccess::query()->create([
            'matrix_id' => $matrix->id,
            'user_id' => $shared->id,
            'can_register' => true,
            'granted_by_user_id' => $manager->id,
        ]);

        $this->actingAs($shared)
            ->get(route('matrices.index', ['module' => 'calidad']))
            ->assertRedirect(route('matrices.submissions.index', [
                'module' => 'calidad',
                'matrix' => $matrix->id,
            ]));
    }

    public function test_shared_user_without_matrices_cannot_open_the_board(): void
    {
        $shared = $this->sharedUser();

        $this->actingAs($shared)
            ->get(route('matrices.index', ['module' => 'calidad']))
            ->assertForbidden();
    }

    public function test_shared_user_sees_mine_page_with_simple_list(): void
    {
        $manager = $this->manager();
        $matrix = $this->makeMatrix($manager, 'Matriz compartida');

        $shared = $this->sharedUser();
        MatrixUserAccess::query()->create([
            'matrix_id' => $matrix->id,
            'user_id' => $shared->id,
            'can_register' => true,
            'granted_by_user_id' => $manager->id,
        ]);

        $response = $this->actingAs($shared)->get(route('matrices.mine', ['module' => 'calidad']));

        $response->assertOk();
        $response->assertSee('Mis matrices');
        $response->assertSee('Matriz compartida');
        // La lista simple no muestra las tarjetas de administración.
        $response->assertDontSee('Nueva matriz');
        $response->assertDontSee('matrices-grid', false);
    }

    public function test_shared_user_sees_each_matrix_as_a_tab(): void
    {
        $manager = $this->manager();
        $first = $this->makeMatrix($manager, 'Matriz Alfa');
        $second = $this->makeMatrix($manager, 'Matriz Beta');

        $shared = $this->sharedUser();

        foreach ([$first, $second] as $matrix) {
            MatrixUserAccess::query()->create([
                'matrix_id' => $matrix->id,
                'user_id' => $shared->id,
                'can_register' => true,
                'granted_by_user_id' => $manager->id,
            ]);
        }

        $response = $this->actingAs($shared)->get(route('matrices.submissions.index', [
            'module' => 'calidad',
            'matrix' => $first->id,
        ]));

        $response->assertOk();
        // Ambas matrices aparecen como pestaña; la actual va activa.
        $response->assertSee('Matriz Alfa');
        $response->assertSee('Matriz Beta');
        $response->assertSee('module-tab--active', false);
        // Un compartido no debe ver las pestañas de administración.
        $response->assertDontSee('Definición');
    }

    public function test_manager_tabs_only_include_own_or_shared_matrices(): void
    {
        $manager = $this->manager();
        $own = $this->makeMatrix($manager, 'Matriz Propia');

        // Matriz de otro usuario, no compartida con el manager.
        $otherOwner = $this->manager();
        $foreign = $this->makeMatrix($otherOwner, 'Matriz Ajena');

        $response = $this->actingAs($manager)->get(route('matrices.submissions.index', [
            'module' => 'calidad',
            'matrix' => $own->id,
        ]));

        $response->assertOk();
        $response->assertSee('Matriz Propia');
        // La matriz ajena no aparece como pestaña, pero sí en las tarjetas del índice.
        $response->assertDontSee('Matriz Ajena');
    }

    public function test_area_grant_gives_access_and_tab(): void
    {
        $manager = $this->manager();
        $matrix = $this->makeMatrix($manager, 'Matriz De Area');

        MatrixAreaAccess::query()->create([
            'matrix_id' => $matrix->id,
            'area_key' => 'calidad',
            'can_register' => true,
            'granted_by_user_id' => $manager->id,
        ]);

        $areaUser = $this->sharedUser('calidad');

        $this->actingAs($areaUser)
            ->get(route('matrices.index', ['module' => 'calidad']))
            ->assertRedirect(route('matrices.submissions.index', [
                'module' => 'calidad',
                'matrix' => $matrix->id,
            ]));
    }

    public function test_expired_grant_hides_board(): void
    {
        $manager = $this->manager();
        $matrix = $this->makeMatrix($manager);

        $shared = $this->sharedUser();
        MatrixUserAccess::query()->create([
            'matrix_id' => $matrix->id,
            'user_id' => $shared->id,
            'can_register' => true,
            'granted_by_user_id' => $manager->id,
            'expires_at' => now()->subDay(),
        ]);

        $this->actingAs($shared)
            ->get(route('matrices.index', ['module' => 'calidad']))
            ->assertForbidden();
    }

    public function test_mine_page_exposes_search_and_filters(): void
    {
        $manager = $this->manager();
        $this->makeMatrix($manager, 'Matriz visible');

        $response = $this->actingAs($manager)->get(route('matrices.mine', ['module' => 'calidad']));

        $response->assertOk();
        $response->assertSee('Filtros');
        $response->assertSee('Buscar');
        $response->assertSee('Área');
        $response->assertSee('Creador');
        $response->assertSee('Estado');
        $response->assertSee($manager->name);
    }

    public function test_mine_filters_narrow_the_list(): void
    {
        $manager = $this->manager();
        $alfa = $this->makeMatrix($manager, 'Matriz Alfa');

        // Beta se queda como borrador (el helper publica las matrices).
        $beta = app(MatrixDefinitionService::class)->create($manager, [
            'name' => 'Matriz Beta',
            'description' => null,
            'area_key' => 'calidad',
            'fields' => [
                ['label' => 'Nombre', 'type' => 'text', 'required' => true, 'col_span' => 12, 'show_in_table' => true],
            ],
        ]);

        $byName = $this->actingAs($manager)
            ->get(route('matrices.mine', ['module' => 'calidad', 'q' => 'Alfa']));
        $byName->assertOk();
        $byName->assertViewHas('rows', fn ($rows) => $rows->count() === 1 && $rows->first()['matrix']->is($alfa));

        $byStatus = $this->actingAs($manager)
            ->get(route('matrices.mine', ['module' => 'calidad', 'status' => 'draft']));
        $byStatus->assertOk();
        $byStatus->assertViewHas('rows', fn ($rows) => $rows->count() === 1 && $rows->first()['matrix']->is($beta));

        $byArea = $this->actingAs($manager)
            ->get(route('matrices.mine', ['module' => 'calidad', 'area' => 'calidad']));
        $byArea->assertOk();
        $byArea->assertViewHas('rows', fn ($rows) => $rows->count() === 2);

        // Un creador ajeno se ve solo si compartió su matriz con el usuario.
        $otherOwner = $this->manager();
        $foreign = $this->makeMatrix($otherOwner, 'Matriz Ajena');
        MatrixUserAccess::query()->create([
            'matrix_id' => $foreign->id,
            'user_id' => $manager->id,
            'can_register' => false,
            'granted_by_user_id' => $otherOwner->id,
        ]);

        $byOwner = $this->actingAs($manager)
            ->get(route('matrices.mine', ['module' => 'calidad', 'owner' => $otherOwner->id]));
        $byOwner->assertOk();
        $byOwner->assertViewHas('rows', fn ($rows) => $rows->count() === 1 && $rows->first()['matrix']->is($foreign));
    }

    public function test_mine_shows_who_edited_the_matrix_last(): void
    {
        $owner = $this->manager();
        $editor = $this->manager();
        $matrix = $this->makeMatrix($owner, 'Matriz con historial');

        app(MatrixDefinitionService::class)->update($matrix, [
            'name' => 'Matriz con historial',
            'description' => null,
            'area_key' => 'calidad',
            'fields' => [
                ['label' => 'Nombre', 'type' => 'text', 'required' => true, 'col_span' => 12, 'show_in_table' => true],
            ],
        ], $editor);

        $response = $this->actingAs($owner)->get(route('matrices.mine', ['module' => 'calidad']));

        $response->assertOk();
        $response->assertSee($editor->name);
        $response->assertViewHas('rows', fn ($rows) => $rows->first()['matrix']->updatedBy?->is($editor) === true);
    }

    public function test_archived_matrix_hides_board_when_it_is_the_only_grant(): void
    {
        $manager = $this->manager();
        $matrix = $this->makeMatrix($manager);

        $shared = $this->sharedUser();
        MatrixUserAccess::query()->create([
            'matrix_id' => $matrix->id,
            'user_id' => $shared->id,
            'can_register' => true,
            'granted_by_user_id' => $manager->id,
        ]);

        // Se archiva la única matriz del usuario compartido.
        $matrix->update(['status' => Matrix::STATUS_ARCHIVED, 'archived_at' => now()]);

        $this->actingAs($shared)
            ->get(route('matrices.index', ['module' => 'calidad']))
            ->assertForbidden();
    }
}
