<?php

namespace Tests\Feature\Matrices;

use App\Models\Matrices\Matrix;
use App\Models\User;
use App\Services\Matrices\MatrixDefinitionService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatrixSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function admin(): User
    {
        $admin = User::where('email', env('ADMIN_EMAIL', 'admin@sjseguridad.local'))->firstOrFail();
        $admin->update([
            'area_key' => 'calidad',
            'must_change_password' => false,
        ]);

        return $admin;
    }

    public function test_admin_can_open_matrices_board(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('matrices.index', ['module' => 'calidad']));

        $response->assertOk();
    }

    public function test_admin_can_create_and_publish_a_matrix(): void
    {
        $admin = $this->admin();

        $payload = [
            'name' => 'Matriz de prueba',
            'description' => 'Descripción de prueba',
            'area_key' => 'calidad',
            'fields' => [
                [
                    'label' => 'Nombre',
                    'type' => 'text',
                    'required' => true,
                    'col_span' => 6,
                    'show_in_table' => true,
                ],
                [
                    'label' => 'Estado',
                    'type' => 'select',
                    'required' => false,
                    'col_span' => 6,
                    'show_in_table' => true,
                    'filterable' => true,
                    'options' => [
                        ['label' => 'Activo'],
                        ['label' => 'Inactivo'],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin)->post(route('matrices.store', ['module' => 'calidad']), $payload);

        $response->assertRedirect();

        $matrix = Matrix::query()->firstOrFail();
        $this->assertSame('Matriz de prueba', $matrix->name);

        $publish = $this->actingAs($admin)->post(route('matrices.publish', ['module' => 'calidad', 'matrix' => $matrix->id]));
        $publish->assertRedirect();

        $matrix->refresh();
        $this->assertTrue($matrix->isPublished());
    }

    public function test_admin_can_register_a_submission(): void
    {
        $admin = $this->admin();

        $service = app(MatrixDefinitionService::class);
        $matrix = $service->create($admin, [
            'name' => 'Matriz reg',
            'description' => null,
            'area_key' => 'calidad',
            'fields' => [
                ['label' => 'Nombre', 'type' => 'text', 'required' => true, 'col_span' => 12, 'show_in_table' => true],
            ],
        ]);
        $service->publish($matrix, $admin);
        $matrix->refresh();

        $fieldKey = $matrix->currentVersion->fields()->first()->field_key;

        $response = $this->actingAs($admin)->post(route('matrices.submissions.store', ['module' => 'calidad', 'matrix' => $matrix->id]), [
            'values' => [$fieldKey => 'Juan'],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('matrix_submissions', ['matrix_id' => $matrix->id]);
    }
}
