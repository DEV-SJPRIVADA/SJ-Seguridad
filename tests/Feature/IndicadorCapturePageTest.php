<?php

namespace Tests\Feature;

use App\Models\Indicator;
use App\Models\OperationsLeader;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicadorCapturePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PermissionCatalog::sync();
        $this->seed(\Database\Seeders\IndicadorSeeder::class);
    }

    public function test_capture_page_renders_vanilla_form_and_scripts(): void
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(['view.dashboard', 'operations.capture']);

        $indicator = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();
        $leader = OperationsLeader::query()->create([
            'name' => 'TATIANA PRECIADO',
            'code' => 'J-1',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('indicadores.show', [
            'indicator' => $indicator->code,
            'operations_leader_id' => $leader->id,
            'year' => 2026,
            'month' => 7,
        ]));

        $response->assertOk();
        $response->assertSee('js-open-improvement-modal', false);
        $response->assertSee('name="form[total_personal]"', false);
        $response->assertSee('indicadores-capture.js', false);
        $response->assertSee('class="indicadores-form ftop01-sheet"', false);
        $response->assertDontSee('wire:model', false);
        $response->assertDontSee('livewire.js', false);
        $response->assertDontSee('@livewire', false);
    }

    public function test_ft_op_03_capture_page_renders_classification_modal(): void
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(['view.dashboard', 'operations.capture']);

        $indicator = Indicator::query()->where('code', 'FT-OP-03')->firstOrFail();
        $leader = OperationsLeader::query()->create([
            'name' => 'Jefe FT03',
            'code' => 'JT03',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('indicadores.show', [
            'indicator' => $indicator->code,
            'operations_leader_id' => $leader->id,
            'year' => 2026,
            'month' => 7,
        ]));

        $response->assertOk();
        $response->assertSee('js-open-classification-modal', false);
        $response->assertSee('ft-op-03-chart-finance', false);
        $response->assertSee('class="indicadores-form ftop03-sheet"', false);
    }

    public function test_store_ft_op_03_with_classification(): void
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(['view.dashboard', 'operations.capture']);

        $indicator = Indicator::query()->where('code', 'FT-OP-03')->firstOrFail();
        $leader = OperationsLeader::query()->create([
            'name' => 'Jefe FT03',
            'code' => 'JT03',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('indicadores.capture.store', $indicator), [
            'year' => 2026,
            'month' => 7,
            'operations_leader_id' => $leader->id,
            'form' => [
                'total_servicios' => 100,
                'total_siniestros' => 2,
                'facturacion_mensual' => 1000000,
                'valor_pagado_siniestros' => 5000,
                'clasificacion_por_tipo' => [
                    ['tipo' => 'Hurto en apartamentos', 'cantidad' => 1],
                    ['tipo' => 'Hurto de vehiculos / motos', 'cantidad' => 1],
                ],
            ],
            'improvement' => [
                'analysis' => 'Analisis FT03',
                'action_taken' => 'Accion tomada',
                'action_defined' => 'Accion definida',
                'improvement_required' => '',
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('indicator_captures', [
            'indicator_id' => $indicator->id,
            'operations_leader_id' => $leader->id,
        ]);
    }
}
