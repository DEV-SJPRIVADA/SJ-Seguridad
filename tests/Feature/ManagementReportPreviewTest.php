<?php

namespace Tests\Feature;

use App\Models\Indicator;
use App\Models\ManagementReportDraft;
use App\Models\User;
use App\Services\Indicadores\ManagementReport\ManagementReportDataBuilder;
use App\Support\PermissionCatalog;
use Database\Seeders\DashboardWeightSeeder;
use Database\Seeders\IndicadorDemoDataSeeder;
use Database\Seeders\IndicadorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagementReportPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PermissionCatalog::sync();
        $this->seed(IndicadorSeeder::class);
        $this->seed(DashboardWeightSeeder::class);
        $this->seed(IndicadorDemoDataSeeder::class);
    }

    public function test_export_user_can_view_management_report_preview(): void
    {
        $user = $this->exportUser();
        $year = (int) config('indicators.base_year', now()->year);

        $this->actingAs($user)
            ->get(route('indicadores.export.management.preview', ['year' => $year, 'month' => 7]))
            ->assertOk()
            ->assertSee('Vista previa — Informe de gestion FO-GI-39')
            ->assertSee('FT-OP-01')
            ->assertSee('Guardar borrador')
            ->assertSee('management-report-chart-data', false)
            ->assertSee('mgmt-chart-ft-op-01', false);
    }

    public function test_user_without_export_permission_cannot_view_preview(): void
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(['view.dashboard', 'operations.view']);

        $this->actingAs($user)
            ->get(route('indicadores.export.management.preview'))
            ->assertForbidden();
    }

    public function test_saving_draft_persists_report_title_and_narratives(): void
    {
        $user = $this->exportUser();
        $year = (int) config('indicators.base_year', now()->year);
        $month = 7;

        $narratives = [
            'FT-OP-01' => 'Narrativa personalizada para personal capacitado.',
            'FT-OP-02' => 'Narrativa personalizada para servicios no conformes.',
        ];

        $this->actingAs($user)
            ->post(route('indicadores.export.management.draft.store'), [
                'year' => $year,
                'month' => $month,
                'report_title' => 'Informe personalizado de gestion',
                'narratives' => $narratives,
            ])
            ->assertRedirect(route('indicadores.export.management.preview', ['year' => $year, 'month' => $month]));

        $draft = ManagementReportDraft::query()->where(['year' => $year, 'month' => $month])->firstOrFail();

        $this->assertSame('Informe personalizado de gestion', $draft->report_title);
        $this->assertSame('Narrativa personalizada para personal capacitado.', $draft->narratives['FT-OP-01']);
        $this->assertSame($user->id, $draft->updated_by_user_id);

        $this->actingAs($user)
            ->get(route('indicadores.export.management.preview', ['year' => $year, 'month' => $month]))
            ->assertOk()
            ->assertSee('Narrativa personalizada para personal capacitado.');
    }

    public function test_draft_store_ignores_unknown_narrative_keys(): void
    {
        $user = $this->exportUser();
        $year = (int) config('indicators.base_year', now()->year);
        $month = 7;

        $this->actingAs($user)
            ->post(route('indicadores.export.management.draft.store'), [
                'year' => $year,
                'month' => $month,
                'narratives' => [
                    'FT-OP-01' => 'Narrativa valida.',
                    'NOT-A-CODE' => 'No deberia guardarse.',
                ],
            ])
            ->assertRedirect();

        $draft = ManagementReportDraft::query()->where(['year' => $year, 'month' => $month])->firstOrFail();

        $this->assertArrayHasKey('FT-OP-01', $draft->narratives);
        $this->assertArrayNotHasKey('NOT-A-CODE', $draft->narratives);
    }

    public function test_build_applies_saved_draft_to_report(): void
    {
        $year = (int) config('indicators.base_year', now()->year);
        $month = 7;

        ManagementReportDraft::query()->create([
            'year' => $year,
            'month' => $month,
            'report_title' => 'Titulo de borrador',
            'narratives' => ['FT-OP-01' => 'Narrativa desde el borrador guardado.'],
        ]);

        Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();

        $report = app(ManagementReportDataBuilder::class)->build($year, $month);

        $this->assertSame('Titulo de borrador', $report['report_title']);
        $this->assertSame('Narrativa desde el borrador guardado.', $report['indicators']['FT-OP-01']['narrative']);
        $this->assertNotSame('Narrativa desde el borrador guardado.', $report['indicators']['FT-OP-02']['narrative']);
    }

    public function test_regenerate_clears_saved_draft(): void
    {
        $user = $this->exportUser();
        $year = (int) config('indicators.base_year', now()->year);
        $month = 7;

        ManagementReportDraft::query()->create([
            'year' => $year,
            'month' => $month,
            'report_title' => 'Titulo de borrador',
            'narratives' => ['FT-OP-01' => 'Narrativa desde el borrador guardado.'],
        ]);

        $this->actingAs($user)
            ->post(route('indicadores.export.management.draft.regenerate'), [
                'year' => $year,
                'month' => $month,
            ])
            ->assertRedirect(route('indicadores.export.management.preview', ['year' => $year, 'month' => $month]));

        $this->assertDatabaseMissing('indicator_management_report_drafts', [
            'year' => $year,
            'month' => $month,
        ]);

        $report = app(ManagementReportDataBuilder::class)->build($year, $month);
        $this->assertNotSame('Titulo de borrador', $report['report_title']);
    }

    private function exportUser(): User
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(['view.dashboard', 'operations.export', 'operations.view']);

        return $user;
    }
}
