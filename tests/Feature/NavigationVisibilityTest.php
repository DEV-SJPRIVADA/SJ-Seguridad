<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Navigation\NavigationResolver;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class NavigationVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_super_admin_sees_canonical_requisitions_board_once(): void
    {
        $user = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'super-admin'))->firstOrFail();

        $nav = app(NavigationResolver::class)->resolve($user, 'dashboard');

        $this->assertSame(1, $this->countBoardInNavigation($nav, 'requisiciones'));

        $areaKeys = collect($nav['appNavigation'])->pluck('key')->values()->all();

        $this->assertContains('gestion_humana', $areaKeys);
        $this->assertContains('compras', $areaKeys);
        $this->assertNotContains('programacion', $areaKeys);
        $this->assertNotContains('juridico', $areaKeys);
    }

    public function test_director_sees_only_compras_area_for_purchase_approval(): void
    {
        $director = User::factory()->create(['must_change_password' => false]);
        $director->assignRole('director');

        $nav = app(NavigationResolver::class)->resolve($director, 'dashboard');

        $areaKeys = collect($nav['appNavigation'])->pluck('key')->values()->all();

        $this->assertSame(['compras'], $areaKeys);
        $this->assertNotContains('gestion_humana', $areaKeys);

        $comprasBoards = collect(collect($nav['appNavigation'])->firstWhere('key', 'compras')['items'] ?? [])
            ->pluck('label')
            ->all();
        $this->assertContains(config('access.boards.solicitudes_compra'), $comprasBoards);
        $this->assertNotContains(config('access.boards.dashboard'), $comprasBoards);
    }

    public function test_administrador_sees_gestion_humana_requisitions_for_management_approval(): void
    {
        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->assignRole('administrador');

        $nav = app(NavigationResolver::class)->resolve($admin, 'dashboard');

        $areaKeys = collect($nav['appNavigation'])->pluck('key')->values()->all();

        $this->assertContains('gestion_humana', $areaKeys);
        $this->assertNotContains('compras', $areaKeys);

        $ghBoards = collect(collect($nav['appNavigation'])->firstWhere('key', 'gestion_humana')['items'] ?? [])
            ->pluck('label')
            ->all();
        $this->assertContains(config('access.boards.requisiciones'), $ghBoards);
    }

    public function test_operaciones_requester_sees_only_operaciones_area(): void
    {
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->givePermissionTo([
            'requisitions.tab.solicitar',
            'requisitions.tab.seguimiento',
        ]);

        $nav = app(NavigationResolver::class)->resolve($user, 'dashboard');

        $areaKeys = collect($nav['appNavigation'])->pluck('key')->values()->all();

        $this->assertSame(['operaciones'], $areaKeys);
        $this->assertSame(1, $this->countBoardInNavigation($nav, 'requisiciones'));
    }

    public function test_compras_processing_user_sees_compras_boards(): void
    {
        $user = User::factory()->create([
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $user->givePermissionTo([
            'purchase.tab.processing',
            'view.board.compras.suministros',
            'view.board.compras.bandeja_compras',
        ]);

        $nav = app(NavigationResolver::class)->resolve($user, 'dashboard');

        $areaKeys = collect($nav['appNavigation'])->pluck('key')->values()->all();

        $this->assertSame(['compras'], $areaKeys);

        $comprasBoards = collect(collect($nav['appNavigation'])->firstWhere('key', 'compras')['items'] ?? [])
            ->pluck('label')
            ->all();

        $this->assertContains(config('access.boards.suministros'), $comprasBoards);
        $this->assertContains(config('access.boards.solicitudes_compra'), $comprasBoards);
        $this->assertNotContains(config('access.boards.bandeja_compras'), $comprasBoards);
    }

    public function test_compras_analyst_default_purchase_board_url_is_bandeja(): void
    {
        $user = User::factory()->create([
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $user->givePermissionTo([
            'purchase.tab.create',
            'purchase.tab.my_requests',
            'purchase.tab.processing',
            'view.board.compras.solicitudes_compra',
        ]);

        $this->assertSame(
            route('purchase-requests.processing.index', ['module' => 'compras']),
            $user->defaultPurchaseBoardUrl('compras'),
        );
    }

    public function test_gh_operator_does_not_see_requisiciones_in_other_areas(): void
    {
        $user = User::factory()->create([
            'area_key' => 'gestion_humana',
            'must_change_password' => false,
        ]);
        $user->givePermissionTo([
            'requisitions.tab.gestion',
            'view.board.gestion_humana.requisiciones',
            'view.board.operaciones.requisiciones',
            'view.board.comercial.requisiciones',
        ]);

        $nav = app(NavigationResolver::class)->resolve($user, 'dashboard');

        $this->assertSame(1, $this->countBoardInNavigation($nav, 'requisiciones'));
        $this->assertContains('gestion_humana', collect($nav['appNavigation'])->pluck('key')->all());
        $this->assertNotContains('operaciones', collect($nav['appNavigation'])->pluck('key')->all());
    }

    /**
     * @param  array<string, mixed>  $nav
     */
    private function countBoardInNavigation(array $nav, string $boardKey): int
    {
        $label = config("access.boards.{$boardKey}");

        return collect($nav['appNavigation'])
            ->flatMap(function (array $module): array {
                $items = $module['items'] ?? [];

                return $items instanceof Collection ? $items->all() : $items;
            })
            ->filter(fn (array $item): bool => ($item['label'] ?? '') === $label)
            ->count();
    }
}
