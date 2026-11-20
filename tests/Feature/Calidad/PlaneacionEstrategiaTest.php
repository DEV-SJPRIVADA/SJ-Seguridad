<?php

namespace Tests\Feature\Calidad;

use App\Models\QualityPlanning\QualityPlanningDofa;
use App\Models\QualityPlanning\QualityPlanningDofaItem;
use App\Models\QualityPlanning\QualityPlanningObjective;
use App\Models\QualityPlanning\QualityPlanningStrategy;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaneacionEstrategiaTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        PermissionCatalog::sync();

        $this->editor = User::factory()->create(['must_change_password' => false]);
        $this->editor->givePermissionTo([
            'view.board.calidad.planeacion_estrategica',
            'planeacion.estrategia.edit',
        ]);
    }

    public function test_estrategia_permissions_are_registered_for_calidad(): void
    {
        $names = PermissionCatalog::configuredNames();

        $this->assertTrue($names->contains('planeacion.estrategia.view'));
        $this->assertTrue($names->contains('planeacion.estrategia.edit'));
        $this->assertSame('Estrategia', config('access.planeacion_tabs.estrategia'));

        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->assignRole('administrador');
        $this->assertTrue($admin->fresh()->can('planeacion.estrategia.view'));
        $this->assertTrue($admin->fresh()->can('planeacion.estrategia.edit'));
    }

    public function test_estrategia_tab_requires_permission(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->givePermissionTo([
            'view.board.calidad.planeacion_estrategica',
            'planeacion.estrategia.view',
        ]);

        $stranger = User::factory()->create(['must_change_password' => false]);
        $stranger->givePermissionTo(['view.board.calidad.planeacion_estrategica']);

        $this->actingAs($viewer)->get(route('calidad.planeacion.estrategia'))->assertOk();
        $this->actingAs($stranger)->get(route('calidad.planeacion.estrategia'))->assertForbidden();
    }

    public function test_view_only_user_does_not_see_edit_actions(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->givePermissionTo([
            'view.board.calidad.planeacion_estrategica',
            'planeacion.estrategia.view',
        ]);

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.estrategia.store'), $this->dofaPayload())
            ->assertRedirect();

        $dofa = QualityPlanningDofa::query()->firstOrFail();

        $this->actingAs($viewer)
            ->get(route('calidad.planeacion.estrategia'))
            ->assertOk()
            ->assertDontSee('aria-label="Nuevo DOFA"', false);

        $this->actingAs($viewer)
            ->get(route('calidad.planeacion.estrategia.show', $dofa))
            ->assertOk()
            ->assertDontSee('aria-label="Archivar DOFA"', false);

        $this->actingAs($viewer)
            ->post(route('calidad.planeacion.estrategia.store'), $this->dofaPayload())
            ->assertForbidden();
    }

    public function test_creates_dofa_with_free_period_and_uppercase_quadrants(): void
    {
        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.estrategia.store'), $this->dofaPayload([
                'title' => 'dofa institucional',
                'period_label' => '2026-2028',
                'starts_on' => '2026-01-01',
                'ends_on' => '2028-12-31',
                'quadrants' => [
                    'fortaleza' => ['equipo certificado'],
                    'oportunidad' => ['nuevos contratos'],
                    'debilidad' => ['rotación alta'],
                    'amenaza' => ['competencia'],
                ],
            ]))
            ->assertRedirect();

        $dofa = QualityPlanningDofa::query()->firstOrFail();
        $this->assertSame('DOFA INSTITUCIONAL', $dofa->title);
        $this->assertSame('2026-2028', $dofa->period_label);
        $this->assertSame('2028-12-31', $dofa->ends_on->toDateString());
        $this->assertSame(4, QualityPlanningDofaItem::query()->count());
        $this->assertDatabaseHas((new QualityPlanningDofaItem)->getTable(), [
            'dofa_id' => $dofa->id,
            'quadrant' => 'fortaleza',
            'description' => 'EQUIPO CERTIFICADO',
        ]);
    }

    public function test_quarter_period_is_allowed(): void
    {
        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.estrategia.store'), $this->dofaPayload([
                'period_label' => 'T1 2026',
                'starts_on' => '2026-01-01',
                'ends_on' => '2026-03-31',
            ]))
            ->assertRedirect();

        $this->assertSame('T1 2026', QualityPlanningDofa::query()->value('period_label'));
    }

    public function test_objectives_and_strategies_are_chained(): void
    {
        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.estrategia.store'), $this->dofaPayload())
            ->assertRedirect();

        $dofa = QualityPlanningDofa::query()->firstOrFail();

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.estrategia.objectives.store', $dofa), [
                'title' => 'mejorar la eficacia',
                'starts_on' => '2026-01-01',
                'ends_on' => '2026-06-30',
            ])
            ->assertRedirect();

        $objective = QualityPlanningObjective::query()->firstOrFail();
        $this->assertSame($dofa->id, (int) $objective->dofa_id);
        $this->assertSame('MEJORAR LA EFICACIA', $objective->title);

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.estrategia.strategies.store', $objective), [
                'title' => 'auditorías internas',
                'responsible' => 'calidad',
                'progress' => 25,
            ])
            ->assertRedirect();

        $strategy = QualityPlanningStrategy::query()->firstOrFail();
        $this->assertSame($objective->id, (int) $strategy->objective_id);
        $this->assertSame('AUDITORÍAS INTERNAS', $strategy->title);
        $this->assertSame('CALIDAD', $strategy->responsible);
        $this->assertSame(25, (int) $strategy->progress);

        // Las subpestañas separan matriz, objetivos y estrategias: cada texto vive en la suya.
        $this->actingAs($this->editor)
            ->get(route('calidad.planeacion.estrategia.show', ['dofa' => $dofa, 'tab' => 'objetivos']))
            ->assertOk()
            ->assertSee('MEJORAR LA EFICACIA');

        $this->actingAs($this->editor)
            ->get(route('calidad.planeacion.estrategia.show', ['dofa' => $dofa, 'tab' => 'estrategias']))
            ->assertOk()
            ->assertSee('AUDITORÍAS INTERNAS');
    }

    public function test_show_splits_content_into_dofa_objectives_and_strategies_tabs(): void
    {
        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.estrategia.store'), $this->dofaPayload([
                'quadrants' => ['fortaleza' => ['equipo certificado']],
            ]))
            ->assertRedirect();

        $dofa = QualityPlanningDofa::query()->firstOrFail();

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.estrategia.objectives.store', $dofa), ['title' => 'objetivo del tab'])
            ->assertRedirect(route('calidad.planeacion.estrategia.show', ['dofa' => $dofa, 'tab' => 'objetivos']));

        $objective = QualityPlanningObjective::query()->firstOrFail();

        // La matriz solo aparece en la subpestaña DOFA.
        $this->actingAs($this->editor)
            ->get(route('calidad.planeacion.estrategia.show', ['dofa' => $dofa, 'tab' => 'dofa']))
            ->assertOk()
            ->assertSee('EQUIPO CERTIFICADO')
            ->assertSee('planeacion-estrategia-matrix__cell', false)
            ->assertSee('Agregar línea en Fortalezas')
            ->assertSee('js-confirm-delete', false)
            ->assertDontSee('planeacion-estrategia-group', false);

        // Objetivos y estrategias viven en pestañas separadas: sin la matriz de cuadrantes.
        $this->actingAs($this->editor)
            ->get(route('calidad.planeacion.estrategia.show', ['dofa' => $dofa, 'tab' => 'objetivos']))
            ->assertOk()
            ->assertSee('OBJETIVO DEL TAB')
            ->assertDontSee('planeacion-estrategia-matrix__cell', false)
            ->assertDontSee('planeacion-estrategia-group', false);

        $this->actingAs($this->editor)
            ->get(route('calidad.planeacion.estrategia.show', ['dofa' => $dofa, 'tab' => 'estrategias']))
            ->assertOk()
            ->assertSee('OBJETIVO DEL TAB')
            ->assertSee('Sin estrategias todavía');

        // Un valor inválido de tab cae en DOFA en lugar de fallar.
        $this->actingAs($this->editor)
            ->get(route('calidad.planeacion.estrategia.show', ['dofa' => $dofa, 'tab' => 'inventado']))
            ->assertOk()
            ->assertSee('EQUIPO CERTIFICADO');

        $this->assertSame($dofa->id, (int) $objective->dofa_id);
    }

    public function test_dofa_lines_are_added_and_removed_from_the_matrix(): void
    {
        // El modal ya no envía cuadrantes: el DOFA nace sin líneas.
        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.estrategia.store'), $this->dofaPayload([
                'quadrants' => [],
            ]))
            ->assertRedirect();

        $dofa = QualityPlanningDofa::query()->firstOrFail();
        $this->assertSame(0, QualityPlanningDofaItem::query()->count());

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.estrategia.items.store', $dofa), [
                'quadrant' => 'debilidad',
                'description' => 'rotación de personal',
            ])
            ->assertRedirect(route('calidad.planeacion.estrategia.show', ['dofa' => $dofa, 'tab' => 'dofa']));

        $item = QualityPlanningDofaItem::query()->firstOrFail();
        $this->assertSame('DEBILIDAD', mb_strtoupper($item->quadrant));
        $this->assertSame('ROTACIÓN DE PERSONAL', $item->description);

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.estrategia.items.store', $dofa), [
                'quadrant' => 'fortaleza',
                'description' => 'equipo certificado',
            ])
            ->assertRedirect();

        $this->assertSame(2, QualityPlanningDofaItem::query()->count());

        // Eliminar una línea no toca las demás.
        $this->actingAs($this->editor)
            ->delete(route('calidad.planeacion.estrategia.items.destroy', $item))
            ->assertRedirect(route('calidad.planeacion.estrategia.show', ['dofa' => $dofa, 'tab' => 'dofa']));

        $this->assertSame(1, QualityPlanningDofaItem::query()->count());
        $this->assertDatabaseHas((new QualityPlanningDofaItem)->getTable(), [
            'dofa_id' => $dofa->id,
            'quadrant' => 'fortaleza',
        ]);
    }

    public function test_line_requires_a_valid_quadrant_and_description(): void
    {
        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.estrategia.store'), $this->dofaPayload(['quadrants' => []]))
            ->assertRedirect();

        $dofa = QualityPlanningDofa::query()->firstOrFail();

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.estrategia.items.store', $dofa), [
                'quadrant' => 'inventado',
                'description' => '',
            ])
            ->assertSessionHasErrors(['quadrant', 'description']);

        $this->assertSame(0, QualityPlanningDofaItem::query()->count());
    }

    public function test_line_keeps_line_breaks_written_by_the_user(): void
    {
        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.estrategia.store'), $this->dofaPayload(['quadrants' => []]))
            ->assertRedirect();

        $dofa = QualityPlanningDofa::query()->firstOrFail();

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.estrategia.items.store', $dofa), [
                'quadrant' => 'amenaza',
                'description' => "competencia con precios bajos\nrotación de personal clave",
            ])
            ->assertRedirect();

        $item = QualityPlanningDofaItem::query()->firstOrFail();
        $this->assertStringContainsString("\n", $item->description);
        $this->assertSame("COMPETENCIA CON PRECIOS BAJOS\nROTACIÓN DE PERSONAL CLAVE", $item->description);

        // La matriz conserva el salto de línea al renderizar (white-space: pre-line).
        $this->actingAs($this->editor)
            ->get(route('calidad.planeacion.estrategia.show', ['dofa' => $dofa, 'tab' => 'dofa']))
            ->assertOk()
            ->assertSee('COMPETENCIA CON PRECIOS BAJOS')
            ->assertSee('ROTACIÓN DE PERSONAL CLAVE');
    }

    public function test_updating_dofa_from_modal_keeps_matrix_lines(): void
    {
        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.estrategia.store'), $this->dofaPayload())
            ->assertRedirect();

        $dofa = QualityPlanningDofa::query()->firstOrFail();
        $this->assertSame(4, QualityPlanningDofaItem::query()->count());

        // Edición del modal: solo identificación, periodo, misión, visión y resumen (sin cuadrantes).
        $this->actingAs($this->editor)
            ->patch(route('calidad.planeacion.estrategia.update', $dofa), [
                'title' => 'dofa actualizado',
                'period_label' => '2026-2027',
                'starts_on' => '2026-01-01',
                'ends_on' => '2027-12-31',
                'status' => QualityPlanningDofa::STATUS_DRAFT,
                'mission' => 'prestar servicios de seguridad privada',
                'vision' => 'ser referente nacional en 2028',
                'summary' => 'resumen ampliado del análisis',
            ])
            ->assertRedirect(route('calidad.planeacion.estrategia.show', ['dofa' => $dofa, 'tab' => 'dofa']));

        $dofa->refresh();
        $this->assertSame('DOFA ACTUALIZADO', $dofa->title);
        $this->assertSame('PRESTAR SERVICIOS DE SEGURIDAD PRIVADA', $dofa->mission);
        $this->assertSame('SER REFERENTE NACIONAL EN 2028', $dofa->vision);
        $this->assertSame('RESUMEN AMPLIADO DEL ANÁLISIS', $dofa->summary);
        $this->assertSame(4, QualityPlanningDofaItem::query()->count());

        // Misión y visión se muestran en la cabecera del detalle.
        $this->actingAs($this->editor)
            ->get(route('calidad.planeacion.estrategia.show', ['dofa' => $dofa, 'tab' => 'dofa']))
            ->assertOk()
            ->assertSee('PRESTAR SERVICIOS DE SEGURIDAD PRIVADA')
            ->assertSee('SER REFERENTE NACIONAL EN 2028');
    }

    public function test_creates_dofa_with_mission_and_vision(): void
    {
        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.estrategia.store'), $this->dofaPayload([
                'mission' => 'proteger personas y bienes',
                'vision' => 'cobertura nacional en 2030',
            ]))
            ->assertRedirect();

        $dofa = QualityPlanningDofa::query()->firstOrFail();
        $this->assertSame('PROTEGER PERSONAS Y BIENES', $dofa->mission);
        $this->assertSame('COBERTURA NACIONAL EN 2030', $dofa->vision);
    }

    public function test_archiving_dofa_keeps_children_and_hides_it_from_default_list(): void
    {
        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.estrategia.store'), $this->dofaPayload())
            ->assertRedirect();

        $dofa = QualityPlanningDofa::query()->firstOrFail();

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.estrategia.objectives.store', $dofa), ['title' => 'objetivo uno'])
            ->assertRedirect();

        $objective = QualityPlanningObjective::query()->firstOrFail();
        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.estrategia.strategies.store', $objective), ['title' => 'estrategia uno'])
            ->assertRedirect();

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.estrategia.archive', $dofa))
            ->assertRedirect();

        $this->assertNotNull($dofa->fresh()->archived_at);
        $this->assertSame(1, QualityPlanningObjective::query()->count());
        $this->assertSame(1, QualityPlanningStrategy::query()->count());

        $this->actingAs($this->editor)
            ->get(route('calidad.planeacion.estrategia'))
            ->assertOk()
            ->assertDontSee('DOFA DEL PERIODO');

        $this->actingAs($this->editor)
            ->get(route('calidad.planeacion.estrategia', ['status' => 'archived']))
            ->assertOk()
            ->assertSee('DOFA DEL PERIODO');
    }

    public function test_deleting_objective_removes_its_strategies(): void
    {
        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.estrategia.store'), $this->dofaPayload())
            ->assertRedirect();

        $dofa = QualityPlanningDofa::query()->firstOrFail();

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.estrategia.objectives.store', $dofa), ['title' => 'objetivo'])
            ->assertRedirect();

        $objective = QualityPlanningObjective::query()->firstOrFail();

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.estrategia.strategies.store', $objective), ['title' => 'estrategia'])
            ->assertRedirect();

        $this->actingAs($this->editor)
            ->delete(route('calidad.planeacion.estrategia.objectives.destroy', $objective))
            ->assertRedirect();

        $this->assertSame(0, QualityPlanningObjective::query()->count());
        $this->assertSame(0, QualityPlanningStrategy::query()->count());
    }

    /**
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function dofaPayload(array $override = []): array
    {
        return array_merge([
            'title' => 'DOFA del periodo',
            'period_label' => '2026',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
            'status' => QualityPlanningDofa::STATUS_DRAFT,
            'quadrants' => [
                'fortaleza' => ['fortaleza uno'],
                'oportunidad' => ['oportunidad uno'],
                'debilidad' => ['debilidad uno'],
                'amenaza' => ['amenaza uno'],
            ],
        ], $override);
    }
}
