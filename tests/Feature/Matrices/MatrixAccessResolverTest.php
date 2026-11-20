<?php

namespace Tests\Feature\Matrices;

use App\Models\Matrices\Matrix;
use App\Models\Matrices\MatrixAreaAccess;
use App\Models\Matrices\MatrixUserAccess;
use App\Models\User;
use App\Services\Matrices\MatrixAccessResolver;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class MatrixAccessResolverTest extends TestCase
{
    use RefreshDatabase;

    private MatrixAccessResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->resolver = app(MatrixAccessResolver::class);
    }

    public function test_owner_has_full_access(): void
    {
        $owner = User::factory()->create(['area_key' => 'calidad']);
        $matrix = $this->makeMatrix($owner);

        $access = $this->resolver->resolve($owner, $matrix);

        $this->assertTrue($access->canView);
        $this->assertTrue($access->canRegister);
        $this->assertTrue($access->canViewOthers);
        $this->assertTrue($access->canEditOthers);
        $this->assertTrue($access->canManageDefinition);
    }

    public function test_user_with_transversal_permission_has_full_access(): void
    {
        $manager = User::factory()->create(['area_key' => 'tic']);
        $manager->givePermissionTo('matrices.manage');

        $owner = User::factory()->create(['area_key' => 'calidad']);
        $matrix = $this->makeMatrix($owner);

        $access = $this->resolver->resolve($manager, $matrix);

        $this->assertTrue($access->canViewOthers);
        $this->assertTrue($access->canEditOthers);
        $this->assertTrue($access->canManageDefinition);
    }

    public function test_area_grant_defaults_to_own_records_scope(): void
    {
        $owner = User::factory()->create(['area_key' => 'calidad']);
        $member = User::factory()->create(['area_key' => 'calidad']);
        $matrix = $this->makeMatrix($owner);

        MatrixAreaAccess::create([
            'matrix_id' => $matrix->id,
            'area_key' => 'calidad',
            'can_register' => true,
        ]);

        $access = $this->resolver->resolve($member, $matrix);

        $this->assertTrue($access->canView);
        $this->assertTrue($access->canRegister);
        $this->assertFalse($access->canViewOthers);
        $this->assertFalse($access->canEditOthers);
        $this->assertFalse($access->canManageDefinition);
    }

    public function test_area_grant_can_edit_everything_when_flagged(): void
    {
        $owner = User::factory()->create(['area_key' => 'calidad']);
        $member = User::factory()->create(['area_key' => 'calidad']);
        $matrix = $this->makeMatrix($owner);

        MatrixAreaAccess::create([
            'matrix_id' => $matrix->id,
            'area_key' => 'calidad',
            'can_register' => true,
            'can_edit_others' => true,
        ]);

        $access = $this->resolver->resolve($member, $matrix);

        $this->assertTrue($access->canViewOthers, 'editar a otros debe implicar ver a otros');
        $this->assertTrue($access->canEditOthers);
    }

    public function test_user_grant_defaults_to_own_records_scope(): void
    {
        $owner = User::factory()->create(['area_key' => 'calidad']);
        $guest = User::factory()->create(['area_key' => 'comercial']);
        $matrix = $this->makeMatrix($owner);

        MatrixUserAccess::create([
            'matrix_id' => $matrix->id,
            'user_id' => $guest->id,
            'can_register' => true,
        ]);

        $access = $this->resolver->resolve($guest, $matrix);

        $this->assertTrue($access->canView);
        $this->assertTrue($access->canRegister);
        $this->assertFalse($access->canViewOthers);
        $this->assertFalse($access->canEditOthers);
    }

    public function test_user_grant_can_view_others_without_editing(): void
    {
        $owner = User::factory()->create(['area_key' => 'calidad']);
        $guest = User::factory()->create(['area_key' => 'comercial']);
        $matrix = $this->makeMatrix($owner);

        MatrixUserAccess::create([
            'matrix_id' => $matrix->id,
            'user_id' => $guest->id,
            'can_register' => false,
            'can_view_others' => true,
        ]);

        $access = $this->resolver->resolve($guest, $matrix);

        $this->assertTrue($access->canViewOthers);
        $this->assertFalse($access->canEditOthers);
        $this->assertFalse($access->canRegister);
    }

    public function test_grants_are_union_and_most_permissive_wins(): void
    {
        $owner = User::factory()->create(['area_key' => 'calidad']);
        $member = User::factory()->create(['area_key' => 'calidad']);
        $matrix = $this->makeMatrix($owner);

        MatrixAreaAccess::create([
            'matrix_id' => $matrix->id,
            'area_key' => 'calidad',
            'can_register' => true,
        ]);

        MatrixUserAccess::create([
            'matrix_id' => $matrix->id,
            'user_id' => $member->id,
            'can_view_others' => true,
        ]);

        $access = $this->resolver->resolve($member, $matrix);

        $this->assertTrue($access->canRegister, 'el grant de área aporta registrar');
        $this->assertTrue($access->canViewOthers, 'el grant individual aporta ver a otros');
        $this->assertFalse($access->canEditOthers);
    }

    public function test_expired_grants_are_ignored(): void
    {
        $owner = User::factory()->create(['area_key' => 'calidad']);
        $guest = User::factory()->create(['area_key' => 'comercial']);
        $matrix = $this->makeMatrix($owner);

        MatrixUserAccess::create([
            'matrix_id' => $matrix->id,
            'user_id' => $guest->id,
            'can_register' => true,
            'expires_at' => now()->subDay(),
        ]);

        $access = $this->resolver->resolve($guest, $matrix);

        $this->assertFalse($access->canView);
    }

    public function test_area_grant_only_applies_to_members_of_that_area(): void
    {
        $owner = User::factory()->create(['area_key' => 'calidad']);
        $outsider = User::factory()->create(['area_key' => 'comercial']);
        $matrix = $this->makeMatrix($owner);

        MatrixAreaAccess::create([
            'matrix_id' => $matrix->id,
            'area_key' => 'calidad',
            'can_register' => true,
        ]);

        $access = $this->resolver->resolve($outsider, $matrix);

        $this->assertFalse($access->canView);
    }

    public function test_user_without_grants_has_no_access(): void
    {
        $owner = User::factory()->create(['area_key' => 'calidad']);
        $stranger = User::factory()->create(['area_key' => 'comercial']);
        $matrix = $this->makeMatrix($owner);

        $access = $this->resolver->resolve($stranger, $matrix);

        $this->assertFalse($access->canView);
        $this->assertFalse($access->canRegister);
    }

    public function test_policy_enforces_definition_and_view_abilities(): void
    {
        $owner = User::factory()->create(['area_key' => 'calidad']);
        $member = User::factory()->create(['area_key' => 'calidad']);
        $stranger = User::factory()->create(['area_key' => 'comercial']);
        $matrix = $this->makeMatrix($owner);

        MatrixAreaAccess::create([
            'matrix_id' => $matrix->id,
            'area_key' => 'calidad',
            'can_register' => true,
        ]);

        $this->assertTrue(Gate::forUser($owner)->allows('updateDefinition', $matrix));
        $this->assertTrue(Gate::forUser($owner)->allows('registerSubmission', $matrix));

        $this->assertFalse(Gate::forUser($member)->allows('updateDefinition', $matrix));
        $this->assertTrue(Gate::forUser($member)->allows('view', $matrix));
        $this->assertTrue(Gate::forUser($member)->allows('registerSubmission', $matrix));

        $this->assertFalse(Gate::forUser($stranger)->allows('view', $matrix));
        $this->assertFalse(Gate::forUser($stranger)->allows('registerSubmission', $matrix));
    }

    private function makeMatrix(User $owner): Matrix
    {
        return Matrix::create([
            'name' => 'MATRIZ DE PRUEBA',
            'area_key' => $owner->area_key ?? 'calidad',
            'owner_user_id' => $owner->id,
            'status' => Matrix::STATUS_PUBLISHED,
        ]);
    }
}
