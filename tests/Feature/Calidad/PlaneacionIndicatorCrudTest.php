<?php

namespace Tests\Feature\Calidad;

use App\Models\QualityPlanning\QualityPlanningIndicator;
use App\Models\QualityPlanning\QualityPlanningIndicatorVersion;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaneacionIndicatorCrudTest extends TestCase
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

    public function test_create_persists_name_in_uppercase_and_valid_formula(): void
    {
        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.store'), $this->payload([
                'name' => 'eficacia del proceso',
                'objective' => 'medir el cumplimiento',
                'insumos' => 'base de datos del indicador',
                'medicion' => 'conteo mensual de personal',
                'fields' => [
                    ['key' => 'campo_1', 'label' => 'numerador', 'decimals' => 2, 'required' => '1'],
                    ['key' => 'campo_2', 'label' => 'denominador', 'decimals' => 2, 'required' => '1'],
                    ['key' => 'campo_3', 'label' => 'factor', 'decimals' => 2, 'required' => '1'],
                ],
            ]))
            ->assertRedirect();

        $indicator = QualityPlanningIndicator::query()->firstOrFail();
        $version = $indicator->versions()->firstOrFail();

        $this->assertSame('IND-001', $indicator->code);
        $this->assertSame('EFICACIA DEL PROCESO', $version->name);
        $this->assertSame('MEDIR EL CUMPLIMIENTO', $version->objective);
        $this->assertSame('BASE DE DATOS DEL INDICADOR', $version->insumos);
        $this->assertSame('CONTEO MENSUAL DE PERSONAL', $version->medicion);
        $this->assertSame('((campo_1 / campo_2) + campo_3) * 100', $version->formula);
        $this->assertSame(QualityPlanningIndicatorVersion::STATUS_DRAFT, $version->status);
        $this->assertSame(1, $version->version_number);
        $this->assertSame('NUMERADOR', $version->fields[0]['label']);
        $this->assertSame('campo_1', $version->fields[0]['key']);
    }

    public function test_publish_then_second_edit_creates_draft_version_two(): void
    {
        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.store'), $this->payload())
            ->assertRedirect();

        $indicator = QualityPlanningIndicator::query()->firstOrFail();

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.publish', $indicator))
            ->assertRedirect(route('calidad.planeacion.indicadores.show', $indicator));

        $indicator->refresh();
        $published = $indicator->currentPublishedVersion;
        $this->assertNotNull($published);
        $this->assertSame(QualityPlanningIndicatorVersion::STATUS_PUBLISHED, $published->status);
        $this->assertSame(1, $published->version_number);

        $this->actingAs($this->editor)
            ->patch(route('calidad.planeacion.indicadores.update', $indicator), $this->payload([
                'name' => 'eficacia actualizada',
            ]))
            ->assertRedirect();

        $indicator->refresh()->load('versions');
        $this->assertCount(2, $indicator->versions);
        $this->assertSame(QualityPlanningIndicatorVersion::STATUS_PUBLISHED, $indicator->currentPublishedVersion->status);
        $this->assertSame('EFICACIA DEL PROCESO', $indicator->currentPublishedVersion->name);

        $draft = $indicator->draftVersion();
        $this->assertNotNull($draft);
        $this->assertSame(2, $draft->version_number);
        $this->assertSame(QualityPlanningIndicatorVersion::STATUS_DRAFT, $draft->status);
        $this->assertSame('EFICACIA ACTUALIZADA', $draft->name);
    }

    public function test_duplicate_code_returns_422(): void
    {
        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.store'), $this->payload())
            ->assertRedirect();

        $this->actingAs($this->editor)
            ->postJson(route('calidad.planeacion.indicadores.store'), $this->payload([
                'code' => 'IND-001',
                'name' => 'otro indicador',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_archive_then_restore_clears_archived_at(): void
    {
        $indicator = $this->makeIndicator();

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.archive', $indicator))
            ->assertRedirect(route('calidad.planeacion.indicadores'));

        $indicator->refresh();
        $this->assertNotNull($indicator->archived_at);

        $this->actingAs($this->editor)
            ->get(route('calidad.planeacion.indicadores.show', $indicator))
            ->assertOk()
            ->assertSee('aria-label="Restablecer indicador"', false);

        $archivedJson = $this->actingAs($this->editor)
            ->getJson(route('calidad.planeacion.indicadores.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 25,
                'status' => 'archived',
            ]))
            ->assertOk()
            ->json();

        $archivedActions = $archivedJson['data'][0][4] ?? '';
        $this->assertStringContainsString('Restablecer indicador', $archivedActions);
        $this->assertStringNotContainsString('js-confirm-archive', $archivedActions);

        $this->actingAs($this->editor)
            ->post(route('calidad.planeacion.indicadores.restore', $indicator))
            ->assertRedirect(route('calidad.planeacion.indicadores.show', $indicator));

        $indicator->refresh();
        $this->assertNull($indicator->archived_at);
    }

    public function test_archive_form_in_list_asks_for_confirmation(): void
    {
        $indicator = $this->makeIndicator();

        $json = $this->actingAs($this->editor)
            ->getJson(route('calidad.planeacion.indicadores.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 25,
            ]))
            ->assertOk()
            ->json();

        $actions = $json['data'][0][4] ?? '';
        $this->assertStringContainsString('js-confirm-archive', $actions);
        $this->assertStringContainsString('Archivar '.$indicator->code, $actions);
    }

    public function test_viewer_cannot_restore_archived_indicator(): void
    {
        $indicator = $this->makeIndicator();
        $indicator->forceFill(['archived_at' => now()])->save();

        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->givePermissionTo([
            'view.board.calidad.planeacion_estrategica',
            'planeacion.indicadores.view',
        ]);

        $this->actingAs($viewer)
            ->post(route('calidad.planeacion.indicadores.restore', $indicator))
            ->assertForbidden();
    }

    public function test_invalid_formula_returns_422(): void
    {
        $this->actingAs($this->editor)
            ->postJson(route('calidad.planeacion.indicadores.store'), $this->payload([
                'formula' => '((campo_1 / campo_2) + campo_3 * 100',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('formula');

        $this->assertSame(0, QualityPlanningIndicator::query()->count());
    }

    private function makeIndicator(string $code = 'IND-ARC'): QualityPlanningIndicator
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

    /**
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function payload(array $override = []): array
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
