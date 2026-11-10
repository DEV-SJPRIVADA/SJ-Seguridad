<?php

namespace Tests\Feature\Calidad;

use App\Models\QualityPlanning\QualityPlanningIndicator;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PlaneacionDashboardTest extends TestCase
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
            'planeacion.dashboard.view',
        ]);
    }

    public function test_consultant_does_not_see_unassigned_indicator_kpi(): void
    {
        $this->travelTo(Carbon::parse('2026-04-10 09:00:00'));
        $own = $this->publishedIndicator('IND-OWN');
        $this->publishedIndicator('IND-OTH');
        $consultant = $this->assignedConsultant($own, 'Director Equipo');

        $this->actingAs($consultant)
            ->get(route('calidad.planeacion.dashboard', ['year' => 2026, 'month' => 4]))
            ->assertOk()
            ->assertSee('IND-OWN')
            ->assertDontSee('IND-OTH');
    }

    public function test_editor_sees_all_published_indicators(): void
    {
        $this->travelTo(Carbon::parse('2026-04-10 09:00:00'));
        $this->publishedIndicator('IND-OWN');
        $this->publishedIndicator('IND-OTH');

        $this->actingAs($this->editor)
            ->get(route('calidad.planeacion.dashboard', ['year' => 2026, 'month' => 4]))
            ->assertOk()
            ->assertSee('IND-OWN')
            ->assertSee('IND-OTH')
            ->assertSee('Indicadores')
            ->assertSee('En verde');
    }

    public function test_dashboard_is_forbidden_without_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.calidad.planeacion_estrategica',
            'planeacion.indicadores.edit',
        ]);

        $this->actingAs($user)
            ->get(route('calidad.planeacion.dashboard'))
            ->assertForbidden();
    }

    private function publishedIndicator(string $code): QualityPlanningIndicator
    {
        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.store'), $this->indicatorPayload([
                'code' => $code,
            ]))
            ->assertRedirect();

        $indicator = QualityPlanningIndicator::query()->where('code', $code)->firstOrFail();

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.publish', $indicator))
            ->assertRedirect();

        return $indicator->fresh(['versions', 'currentPublishedVersion', 'assignees']);
    }

    private function assignedConsultant(QualityPlanningIndicator $indicator, string $name): User
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
        $assignees[] = ['user_id' => $user->id, 'can_consult' => true];

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.share', $indicator), [
                'assignees' => $assignees,
            ])
            ->assertRedirect();

        return $user->fresh();
    }

    /**
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function indicatorPayload(array $override = []): array
    {
        return array_merge([
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
                ['key' => 'campo_1', 'label' => 'numerador', 'decimals' => 2, 'required' => '1'],
                ['key' => 'campo_2', 'label' => 'denominador', 'decimals' => 2, 'required' => '1'],
                ['key' => 'campo_3', 'label' => 'factor', 'decimals' => 2, 'required' => '1'],
            ],
        ], $override);
    }
}
