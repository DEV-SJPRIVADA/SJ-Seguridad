<?php

namespace Tests\Feature;

use App\Models\Indicator;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicadorCaptureModalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PermissionCatalog::sync();
        $this->seed(\Database\Seeders\IndicadorSeeder::class);
    }

    public function test_capture_page_shows_improvement_modal_markup(): void
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(['view.dashboard', 'operations.capture']);

        $indicator = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();

        $response = $this->actingAs($user)->get(route('indicadores.show', [
            'indicator' => $indicator->code,
            'year' => 2026,
            'month' => 7,
        ]));

        $response->assertOk();
        $response->assertSee('Analisis de resultados (obligatorio)', false);
        $response->assertSee('js-open-improvement-modal', false);
        $response->assertSee('id="improvement-modal"', false);
        $response->assertDontSee('Debes seleccionar un jefe de operaciones', false);
    }

    public function test_store_capture_persists_metrics_and_improvement(): void
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(['view.dashboard', 'operations.capture']);

        $indicator = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();

        $response = $this->actingAs($user)->post(route('indicadores.capture.store', $indicator), [
            'year' => 2026,
            'month' => 7,
            'form' => [
                'total_personal' => 100,
                'personal_capacitado' => 50,
            ],
            'improvement' => [
                'analysis' => 'Analisis de prueba',
                'action_taken' => 'Accion tomada de prueba',
                'action_defined' => 'Accion definida de prueba',
                'improvement_required' => 'Mejora requerida de prueba',
            ],
        ]);

        $response->assertRedirect(route('indicadores.show', [
            'indicator' => $indicator->code,
            'year' => 2026,
            'month' => 7,
        ]));

        $this->assertDatabaseHas('indicator_captures', [
            'indicator_id' => $indicator->id,
            'user_id' => $user->id,
            'result_percentage' => 50,
        ]);
    }

    public function test_store_requires_improvement_when_not_complying(): void
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(['view.dashboard', 'operations.capture']);

        $indicator = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();

        $response = $this->actingAs($user)->from(route('indicadores.show', $indicator))->post(route('indicadores.capture.store', $indicator), [
            'year' => 2026,
            'month' => 7,
            'form' => [
                'total_personal' => 100,
                'personal_capacitado' => 50,
            ],
            'improvement' => [
                'analysis' => 'Analisis',
                'action_taken' => 'Tomada',
                'action_defined' => 'Definida',
                'improvement_required' => '',
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['improvement.improvement_required']);
    }
}
