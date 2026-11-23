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

    public function test_admin_can_open_the_matrix_builder(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('matrices.create', ['module' => 'calidad']));

        $response->assertOk();
        $response->assertSee('Diseña tu matriz');
        $response->assertSee('Guardar borrador');
        $response->assertSee('Agregar campo');
    }

    public function test_admin_can_open_the_builder_to_edit_a_matrix(): void
    {
        $admin = $this->admin();

        $matrix = app(MatrixDefinitionService::class)->create($admin, [
            'name' => 'Matriz editable',
            'description' => 'Definición de prueba',
            'area_key' => 'calidad',
            'fields' => [
                ['label' => 'Nombre', 'type' => 'text', 'required' => true, 'col_span' => 6, 'show_in_table' => true],
            ],
        ]);

        $response = $this->actingAs($admin)->get(route('matrices.edit', [
            'module' => 'calidad',
            'matrix' => $matrix->id,
        ]));

        $response->assertOk();
        $response->assertSee('Matriz editable');
        $response->assertSee('Publicar');
        $response->assertSee('Estructura del formulario');
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

        // Tras guardar se vuelve al listado de registros, no al detalle.
        $response->assertRedirect(route('matrices.submissions.index', ['module' => 'calidad', 'matrix' => $matrix->id]));
        $this->assertDatabaseHas('matrix_submissions', ['matrix_id' => $matrix->id]);
    }

    /**
     * Regresión: el formulario de alta recibía `values` como array y llamaba `->get()`.
     */
    public function test_create_submission_form_renders_without_values(): void
    {
        $admin = $this->admin();

        $service = app(MatrixDefinitionService::class);
        $matrix = $service->create($admin, [
            'name' => 'Matriz form',
            'description' => null,
            'area_key' => 'calidad',
            'fields' => [
                ['label' => 'Nombre', 'type' => 'text', 'required' => true, 'col_span' => 6, 'show_in_table' => true],
                ['label' => 'Observaciones', 'type' => 'textarea', 'required' => false, 'col_span' => 12, 'show_in_table' => false],
            ],
        ]);
        $service->publish($matrix, $admin);
        $matrix->refresh();

        $response = $this->actingAs($admin)->get(route('matrices.submissions.create', [
            'module' => 'calidad',
            'matrix' => $matrix->id,
        ]));

        $response->assertOk();
        $response->assertSee('Guardar registro');
    }

    /**
     * El listado expone Ver, Editar y Archivar por fila (el detalle deja de ser el único lugar).
     */
    public function test_datatable_row_exposes_archive_action(): void
    {
        $admin = $this->admin();

        $service = app(MatrixDefinitionService::class);
        $matrix = $service->create($admin, [
            'name' => 'Matriz acciones',
            'description' => null,
            'area_key' => 'calidad',
            'fields' => [
                ['label' => 'Nombre', 'type' => 'text', 'required' => true, 'col_span' => 12, 'show_in_table' => true],
            ],
        ]);
        $service->publish($matrix, $admin);
        $matrix->refresh();

        $fieldKey = $matrix->currentVersion->fields()->first()->field_key;

        $this->actingAs($admin)->post(route('matrices.submissions.store', [
            'module' => 'calidad',
            'matrix' => $matrix->id,
        ]), [
            'values' => [$fieldKey => 'Registro para archivar'],
        ]);

        $response = $this->actingAs($admin)->getJson(route('matrices.submissions.datatable', [
            'module' => 'calidad',
            'matrix' => $matrix->id,
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]));

        $response->assertOk();

        $actionsHtml = (string) json_encode($response->json('data'), JSON_UNESCAPED_UNICODE);

        $this->assertStringContainsString('Archivar', $actionsHtml);
        $this->assertStringContainsString('Editar', $actionsHtml);
        $this->assertStringContainsString('matrices-row-actions__btn--danger', $actionsHtml);
    }
}
