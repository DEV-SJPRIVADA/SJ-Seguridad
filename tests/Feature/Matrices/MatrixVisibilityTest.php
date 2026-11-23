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
