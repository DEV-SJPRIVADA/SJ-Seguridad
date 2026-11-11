<?php

namespace Tests\Feature\Calidad;

use App\Models\QualityPlanning\QualityPlanningCapture;
use App\Models\QualityPlanning\QualityPlanningIndicator;
use App\Models\QualityPlanning\QualityPlanningIndicatorSubstitution;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PlaneacionSubstitutionTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->editor = User::factory()->create(['must_change_password' => false]);
        $this->editor->givePermissionTo([
            'view.board.calidad.planeacion_estrategica',
            'planeacion.indicadores.edit',
        ]);
    }

    public function test_substitute_captures_on_behalf_of_holder(): void
    {
        $this->travelTo(Carbon::parse('2026-04-10 09:00:00'));

        $indicator = $this->publishedIndicator();
        $holder = $this->assignedUser($indicator, 'Titular Uno');
        $substitute = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.substitutions.store', $indicator), [
                'holder_user_id' => $holder->id,
                'substitute_user_id' => $substitute->id,
                'starts_on' => '2026-04-01',
                'ends_on' => '2026-04-30',
            ])
            ->assertRedirect();

        $substitute->refresh();
        $this->assertTrue($substitute->can('planeacion.captura.edit'));
        $this->assertFalse($substitute->can('planeacion.indicadores.view'));

        $this->actingAs($substitute)
            ->get(route('calidad.planeacion.captura.show', [
                'indicator' => $indicator,
                'year' => 2026,
                'month' => 4,
            ]))
            ->assertOk()
            ->assertSee('suplente')
            ->assertSee($holder->name);

        $this->actingAs($substitute)
            ->post(route('calidad.planeacion.captura.store', $indicator), $this->capturePayload())
            ->assertRedirect();

        $capture = QualityPlanningCapture::query()->firstOrFail();
        $this->assertSame($holder->id, (int) $capture->user_id);
        $this->assertSame($substitute->id, (int) $capture->created_by_user_id);
        $this->assertSame($substitute->id, (int) $capture->updated_by_user_id);
    }

    public function test_substitute_outside_range_is_forbidden(): void
    {
        $this->travelTo(Carbon::parse('2026-05-10 09:00:00'));

        $indicator = $this->publishedIndicator();
        $holder = $this->assignedUser($indicator, 'Titular Dos');
        $substitute = User::factory()->create(['must_change_password' => false]);

        QualityPlanningIndicatorSubstitution::query()->create([
            'indicator_id' => $indicator->id,
            'holder_user_id' => $holder->id,
            'substitute_user_id' => $substitute->id,
            'starts_on' => '2026-04-01',
            'ends_on' => '2026-04-30',
            'created_by_user_id' => $this->editor->id,
        ]);

        $this->actingAs($substitute)
            ->get(route('calidad.planeacion.captura.show', [
                'indicator' => $indicator,
                'year' => 2026,
                'month' => 5,
            ]))
            ->assertForbidden();
    }

    public function test_substitution_requires_holder_assigned(): void
    {
        $indicator = $this->publishedIndicator();
        $outsider = User::factory()->create(['must_change_password' => false]);
        $substitute = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.substitutions.store', $indicator), [
                'holder_user_id' => $outsider->id,
                'substitute_user_id' => $substitute->id,
                'starts_on' => '2026-04-01',
                'ends_on' => '2026-04-30',
            ])
            ->assertSessionHasErrors('holder_user_id');
    }

    public function test_deleting_substitution_revokes_capture_permission(): void
    {
        $this->travelTo(Carbon::parse('2026-04-10 09:00:00'));

        $indicator = $this->publishedIndicator();
        $holder = $this->assignedUser($indicator, 'Titular Tres');
        $substitute = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.substitutions.store', $indicator), [
                'holder_user_id' => $holder->id,
                'substitute_user_id' => $substitute->id,
                'starts_on' => '2026-04-01',
                'ends_on' => '2026-04-30',
            ])
            ->assertRedirect();

        $substitution = QualityPlanningIndicatorSubstitution::query()->firstOrFail();

        $this->actingAs($this->editor)
            ->delete(route('calidad.planeacion.indicadores.substitutions.destroy', [$indicator, $substitution->id]))
            ->assertRedirect();

        $this->assertNull(QualityPlanningIndicatorSubstitution::query()->find($substitution->id));
        $substitute->refresh();
        $this->assertFalse($substitute->can('planeacion.captura.edit'));
    }

    public function test_share_modal_lists_substitutions(): void
    {
        $indicator = $this->publishedIndicator();
        $holder = $this->assignedUser($indicator, 'Titular Cuatro');
        $substitute = User::factory()->create(['must_change_password' => false]);

        QualityPlanningIndicatorSubstitution::query()->create([
            'indicator_id' => $indicator->id,
            'holder_user_id' => $holder->id,
            'substitute_user_id' => $substitute->id,
            'starts_on' => '2026-04-01',
            'ends_on' => '2026-04-30',
            'created_by_user_id' => $this->editor->id,
        ]);

        $this->actingAs($this->editor)
            ->get(route('calidad.planeacion.indicadores.show', $indicator))
            ->assertOk()
            ->assertSee('Suplencias')
            ->assertSee('01/04/2026')
            ->assertSee($substitute->name);
    }

    private function publishedIndicator(): QualityPlanningIndicator
    {
        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.store'), $this->indicatorPayload())
            ->assertRedirect();

        $indicator = QualityPlanningIndicator::query()->firstOrFail();

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.publish', $indicator))
            ->assertRedirect();

        return $indicator->fresh(['versions', 'currentPublishedVersion']);
    }

    private function assignedUser(QualityPlanningIndicator $indicator, string $name): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'must_change_password' => false,
        ]);

        $assignees = $indicator->assignees()
            ->get()
            ->map(fn ($row): array => [
                'user_id' => $row->user_id,
                'can_consult' => (bool) $row->can_consult,
            ])
            ->all();
        $assignees[] = ['user_id' => $user->id, 'can_consult' => false];

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.share', $indicator), [
                'assignees' => $assignees,
            ])
            ->assertRedirect();

        $indicator->load('assignees');

        return $user->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function capturePayload(): array
    {
        return [
            'year' => 2026,
            'month' => 4,
            'input_data' => [
                'campo_1' => 22,
                'campo_2' => 25,
                'campo_3' => 0.02,
            ],
            'analysis_text' => 'captura de suplencia',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function indicatorPayload(): array
    {
        return [
            'code' => 'IND-001',
            'name' => 'eficacia del proceso',
            'objective' => 'medir el cumplimiento',
            'process_key' => 'gestion_gerencial',
            'unit' => 'percentage',
            'frequency' => 'monthly',
            'target_value' => 90,
            'target_operator' => '>=',
            'critical_value' => 70,
            'formula' => '((campo_1 / campo_2) + campo_3) * 100',
            'fields' => [
                ['key' => 'campo_1', 'label' => 'numerador', 'decimals' => 2, 'required' => true],
                ['key' => 'campo_2', 'label' => 'denominador', 'decimals' => 2, 'required' => true],
                ['key' => 'campo_3', 'label' => 'factor', 'decimals' => 2, 'required' => false],
            ],
        ];
    }
}
