<?php

namespace Tests\Feature\GestionHumana;

use App\Models\CursoTipo;
use App\Models\EmployeeCurso;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CursosBulkMarkSolicitadoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_bulk_mark_solicitado_requires_edit_permission(): void
    {
        $viewer = $this->viewerUser();
        $tipo = CursoTipo::factory()->create();
        $curso = EmployeeCurso::factory()->create([
            'curso_tipo_id' => $tipo->id,
            'estado' => EmployeeCurso::ESTADO_PENDIENTE,
        ]);

        $this->actingAs($viewer)
            ->post(route('gestion-humana.cursos.registros.bulk-mark-solicitado'), [
                'ids' => [$curso->id],
                'confirmed' => '1',
            ])
            ->assertForbidden();

        $this->assertSame(EmployeeCurso::ESTADO_PENDIENTE, $curso->fresh()->estado);
    }

    public function test_bulk_mark_solicitado_requires_confirmation_and_selection(): void
    {
        $editor = $this->editorUser();
        $tipo = CursoTipo::factory()->create();
        $curso = EmployeeCurso::factory()->create([
            'curso_tipo_id' => $tipo->id,
            'estado' => EmployeeCurso::ESTADO_PENDIENTE,
        ]);

        $this->actingAs($editor)
            ->from(route('gestion-humana.cursos.registros'))
            ->post(route('gestion-humana.cursos.registros.bulk-mark-solicitado'), [
                'ids' => [$curso->id],
            ])
            ->assertRedirect(route('gestion-humana.cursos.registros'))
            ->assertSessionHasErrors('confirmed');

        $this->actingAs($editor)
            ->from(route('gestion-humana.cursos.registros'))
            ->post(route('gestion-humana.cursos.registros.bulk-mark-solicitado'), [
                'confirmed' => '1',
            ])
            ->assertRedirect(route('gestion-humana.cursos.registros'))
            ->assertSessionHasErrors('ids');

        $this->assertSame(EmployeeCurso::ESTADO_PENDIENTE, $curso->fresh()->estado);
    }

    public function test_bulk_mark_solicitado_updates_selected_rows(): void
    {
        $editor = $this->editorUser();
        $tipo = CursoTipo::factory()->create(['tipo_curso' => 'ALTURAS']);

        $pendiente = EmployeeCurso::factory()->create([
            'curso_tipo_id' => $tipo->id,
            'estado' => EmployeeCurso::ESTADO_PENDIENTE,
            'document_number' => '1001',
        ]);
        $actualizado = EmployeeCurso::factory()->create([
            'curso_tipo_id' => $tipo->id,
            'estado' => EmployeeCurso::ESTADO_ACTUALIZADO,
            'document_number' => '1002',
        ]);
        $yaSolicitado = EmployeeCurso::factory()->create([
            'curso_tipo_id' => $tipo->id,
            'estado' => EmployeeCurso::ESTADO_SOLICITADO,
            'document_number' => '1003',
        ]);
        $noSeleccionado = EmployeeCurso::factory()->create([
            'curso_tipo_id' => $tipo->id,
            'estado' => EmployeeCurso::ESTADO_PENDIENTE,
            'document_number' => '1004',
        ]);

        $this->actingAs($editor)
            ->get(route('gestion-humana.cursos.registros'))
            ->assertOk()
            ->assertSee('Marcar SOLICITADO', false)
            ->assertSee('cursos-registros-page__select-checkbox', false);

        $this->actingAs($editor)
            ->post(route('gestion-humana.cursos.registros.bulk-mark-solicitado'), [
                'ids' => [$pendiente->id, $actualizado->id, $yaSolicitado->id],
                'confirmed' => '1',
                'estado' => EmployeeCurso::ESTADO_PENDIENTE,
            ])
            ->assertRedirect(route('gestion-humana.cursos.registros', [
                'estado' => EmployeeCurso::ESTADO_PENDIENTE,
            ]))
            ->assertSessionHas('status');

        $this->assertSame(EmployeeCurso::ESTADO_SOLICITADO, $pendiente->fresh()->estado);
        $this->assertSame(EmployeeCurso::ESTADO_SOLICITADO, $actualizado->fresh()->estado);
        $this->assertSame(EmployeeCurso::ESTADO_SOLICITADO, $yaSolicitado->fresh()->estado);
        $this->assertSame(EmployeeCurso::ESTADO_PENDIENTE, $noSeleccionado->fresh()->estado);
        $this->assertSame((int) $editor->id, (int) $pendiente->fresh()->updated_by);
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.cursos',
            'cursos.view',
        ]);

        return $user;
    }

    private function editorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.cursos',
            'cursos.view',
            'cursos.edit',
        ]);

        return $user;
    }
}
