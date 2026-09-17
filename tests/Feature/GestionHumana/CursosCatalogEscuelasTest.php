<?php

namespace Tests\Feature\GestionHumana;

use App\Models\CursoEscuela;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CursosCatalogEscuelasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_editor_can_create_update_and_delete_escuela(): void
    {
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->post(route('gestion-humana.cursos.catalogo.escuelas.store'), [
                'codigo' => '015',
                'nit' => '8050262894',
                'nombre' => 'SNIPER',
                'is_active' => '1',
            ])
            ->assertRedirect(route('gestion-humana.cursos.catalogo', ['catalog' => 'escuelas']));

        $escuela = CursoEscuela::query()->where('codigo', '015')->firstOrFail();
        $this->assertSame('8050262894', $escuela->nit);
        $this->assertSame('SNIPER', $escuela->nombre);

        $this->actingAs($editor)
            ->patch(route('gestion-humana.cursos.catalogo.escuelas.update', $escuela), [
                'codigo' => '015',
                'nit' => '8050262894',
                'nombre' => 'SNIPER ACTUALIZADO',
                'is_active' => '1',
            ])
            ->assertRedirect(route('gestion-humana.cursos.catalogo', ['catalog' => 'escuelas']));

        $this->assertSame('SNIPER ACTUALIZADO', $escuela->fresh()->nombre);

        $this->actingAs($editor)
            ->delete(route('gestion-humana.cursos.catalogo.escuelas.destroy', $escuela))
            ->assertRedirect(route('gestion-humana.cursos.catalogo', ['catalog' => 'escuelas']));

        $this->assertDatabaseMissing('curso_escuelas', ['id' => $escuela->id]);
    }

    public function test_catalogo_lists_escuelas_panel(): void
    {
        $editor = $this->editorUser();
        CursoEscuela::factory()->create([
            'codigo' => '411',
            'nit' => '8300211325',
            'nombre' => 'ESC. COLOMBIANA SEG PRIVADA',
        ]);

        $this->actingAs($editor)
            ->get(route('gestion-humana.cursos.catalogo'))
            ->assertOk()
            ->assertSee('Catalogos de cursos', false)
            ->assertSee('Tipos de curso', false)
            ->assertSee('Escuelas', false)
            ->assertSee('data-catalog-key="escuelas"', false)
            ->assertSee('411', false)
            ->assertSee('ESC. COLOMBIANA SEG PRIVADA', false);
    }

    public function test_codigo_must_be_unique(): void
    {
        $editor = $this->editorUser();
        CursoEscuela::factory()->create(['codigo' => '015']);

        $this->actingAs($editor)
            ->from(route('gestion-humana.cursos.catalogo'))
            ->post(route('gestion-humana.cursos.catalogo.escuelas.store'), [
                'codigo' => '015',
                'nit' => '9999999999',
                'nombre' => 'DUPLICADA',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('codigo');
    }

    public function test_viewer_cannot_manage_escuelas(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->post(route('gestion-humana.cursos.catalogo.escuelas.store'), [
                'codigo' => '100',
                'nit' => '123',
                'nombre' => 'X',
            ])
            ->assertForbidden();
    }

    private function editorUser(): User
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $user->givePermissionTo([
            'cursos.view',
            'cursos.edit',
            'view.board.gestion_humana.cursos',
        ]);

        return $user;
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $user->givePermissionTo([
            'cursos.view',
            'view.board.gestion_humana.cursos',
        ]);

        return $user;
    }
}
