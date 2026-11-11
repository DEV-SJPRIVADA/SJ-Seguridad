<?php

namespace Tests\Feature\Calidad;

use App\Models\QualityPlanning\QualityPlanningIndicator;
use App\Models\QualityPlanning\QualityPlanningIndicatorAreaAssignee;
use App\Models\QualityPlanning\QualityPlanningIndicatorAssignee;
use App\Models\QualityPlanning\QualityPlanningIndicatorVersion;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaneacionAssignmentTest extends TestCase
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

    public function test_share_ui_is_icon_button_and_modal(): void
    {
        $indicator = $this->makeIndicator();

        $this->actingAs($this->editor)
            ->get(route('calidad.planeacion.indicadores.show', $indicator))
            ->assertOk()
            ->assertSee('aria-label="Compartir indicador"', false)
            ->assertSee("open-modal', 'planeacion-share'", false)
            ->assertSee('Guardar asignaciones')
            ->assertDontSee('class="panel planeacion-share"', false);

        $this->actingAs($this->editor)
            ->get(route('calidad.planeacion.indicadores.edit', $indicator))
            ->assertOk()
            ->assertSee('aria-label="Compartir indicador"', false)
            ->assertSee('Guardar asignaciones');
    }

    public function test_share_grants_capture_permissions(): void
    {
        $indicator = $this->makeIndicator();
        $capturer = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.share', $indicator), [
                'assignees' => [
                    ['user_id' => $capturer->id, 'can_consult' => false],
                ],
            ])
            ->assertRedirect();

        $capturer->refresh();
        $this->assertTrue($capturer->can('view.board.calidad.planeacion_estrategica'));
        $this->assertTrue($capturer->can('planeacion.captura.view'));
        $this->assertTrue($capturer->can('planeacion.captura.edit'));
        $this->assertFalse($capturer->can('planeacion.indicadores.view'));
        $this->assertFalse($capturer->can('planeacion.indicadores.edit'));

        $this->assertDatabaseHas((new QualityPlanningIndicatorAssignee)->getTable(), [
            'indicator_id' => $indicator->id,
            'user_id' => $capturer->id,
            'can_capture' => 1,
            'can_consult' => 0,
        ]);
    }

    public function test_consultant_sees_only_assigned_indicators(): void
    {
        $own = $this->makeIndicator('IND-OWN');
        $other = $this->makeIndicator('IND-OTH');
        $consultant = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.share', $own), [
                'assignees' => [
                    ['user_id' => $consultant->id, 'can_consult' => '1'],
                ],
            ])
            ->assertRedirect();

        $consultant->refresh();
        $this->assertTrue($consultant->can('planeacion.indicadores.view'));
        $this->assertTrue($consultant->can('planeacion.dashboard.view'));

        $this->actingAs($consultant)
            ->get(route('calidad.planeacion.indicadores'))
            ->assertOk();

        $json = $this->actingAs($consultant)
            ->getJson(route('calidad.planeacion.indicadores.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 25,
            ]))
            ->assertOk()
            ->json();

        $this->assertSame(1, $json['recordsFiltered']);
        $this->assertStringContainsString('IND-OWN', $json['data'][0][0]);
        $this->assertStringNotContainsString('IND-OTH', json_encode($json['data']));

        $this->actingAs($consultant)
            ->get(route('calidad.planeacion.indicadores.show', $own))
            ->assertOk()
            ->assertDontSee('aria-label="Compartir indicador"', false);

        $this->actingAs($consultant)
            ->get(route('calidad.planeacion.indicadores.show', $other))
            ->assertForbidden();
    }

    public function test_capturer_cannot_open_indicators_tab(): void
    {
        $indicator = $this->makeIndicator();
        $capturer = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.share', $indicator), [
                'assignees' => [
                    ['user_id' => $capturer->id, 'can_consult' => false],
                ],
            ])
            ->assertRedirect();

        $capturer->refresh();

        $this->actingAs($capturer)
            ->get(route('calidad.planeacion.indicadores'))
            ->assertForbidden();

        $this->actingAs($capturer)
            ->get(route('calidad.planeacion.captura'))
            ->assertOk();
    }

    public function test_removing_last_assignment_revokes_capture_edit(): void
    {
        $indicator = $this->makeIndicator();
        $capturer = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.share', $indicator), [
                'assignees' => [
                    ['user_id' => $capturer->id, 'can_consult' => false],
                ],
            ])
            ->assertRedirect();

        $capturer->refresh();
        $this->assertTrue($capturer->can('planeacion.captura.edit'));

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.share', $indicator), [
                'assignees' => [],
            ])
            ->assertRedirect();

        $capturer->refresh();
        $this->assertFalse($capturer->can('planeacion.captura.edit'));
        $this->assertFalse($capturer->can('planeacion.captura.view'));
        $this->assertTrue($this->editor->fresh()->can('planeacion.indicadores.edit'));
    }

    public function test_area_assignment_grants_capture_and_lists_indicator(): void
    {
        $indicator = $this->makeIndicator();
        $version = $indicator->versions->first();
        $version->update([
            'status' => QualityPlanningIndicatorVersion::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        $indicator->update(['current_published_version_id' => $version->id]);
        $indicator = $indicator->fresh(['versions', 'currentPublishedVersion']);
        $capturer = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $outside = User::factory()->create([
            'area_key' => 'comercial',
            'must_change_password' => false,
        ]);

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.share', $indicator), [
                'assignees' => [],
                'area_assignees' => [
                    ['area_key' => 'operaciones', 'can_consult' => false],
                ],
            ])
            ->assertRedirect();

        $capturer->refresh();
        $this->assertTrue($capturer->can('view.board.calidad.planeacion_estrategica'));
        $this->assertTrue($capturer->can('planeacion.captura.edit'));
        $this->assertFalse($capturer->can('planeacion.indicadores.view'));

        $this->assertDatabaseHas((new QualityPlanningIndicatorAreaAssignee)->getTable(), [
            'indicator_id' => $indicator->id,
            'area_key' => 'operaciones',
            'can_capture' => 1,
            'can_consult' => 0,
        ]);

        $json = $this->actingAs($capturer)
            ->getJson(route('calidad.planeacion.captura.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 25,
            ]))
            ->assertOk()
            ->json();

        $this->assertSame(1, $json['recordsFiltered']);
        $this->assertStringContainsString('IND-001', $json['data'][0][0]);

        $this->actingAs($outside)
            ->get(route('calidad.planeacion.captura.show', $indicator))
            ->assertForbidden();
    }

    public function test_area_assignment_can_grant_consult(): void
    {
        $own = $this->makeIndicator('IND-OWN');
        $other = $this->makeIndicator('IND-OTH');
        $consultant = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.share', $own), [
                'assignees' => [],
                'area_assignees' => [
                    ['area_key' => 'operaciones', 'can_consult' => '1'],
                ],
            ])
            ->assertRedirect();

        $consultant->refresh();
        $this->assertTrue($consultant->can('planeacion.indicadores.view'));
        $this->assertTrue($consultant->can('planeacion.dashboard.view'));

        $json = $this->actingAs($consultant)
            ->getJson(route('calidad.planeacion.indicadores.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 25,
            ]))
            ->assertOk()
            ->json();

        $this->assertSame(1, $json['recordsFiltered']);
        $this->assertStringContainsString('IND-OWN', $json['data'][0][0]);
        $this->assertStringNotContainsString('IND-OTH', json_encode($json['data']));
    }

    private function makeIndicator(string $code = 'IND-001'): QualityPlanningIndicator
    {
        $indicator = QualityPlanningIndicator::factory()->create([
            'code' => $code,
            'created_by_user_id' => $this->editor->id,
        ]);

        QualityPlanningIndicatorVersion::factory()->create([
            'indicator_id' => $indicator->id,
        ]);

        return $indicator;
    }
}
