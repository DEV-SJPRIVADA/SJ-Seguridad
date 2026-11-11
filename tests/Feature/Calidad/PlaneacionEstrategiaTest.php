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

        $this->actingAs($this->editor)
            ->get(route('calidad.planeacion.estrategia.show', $dofa))
            ->assertOk()
            ->assertSee('MEJORAR LA EFICACIA')
            ->assertSee('AUDITORÍAS INTERNAS');
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
