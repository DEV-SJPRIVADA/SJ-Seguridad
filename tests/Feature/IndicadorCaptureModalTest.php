<?php

namespace Tests\Feature;

use App\Livewire\Indicadores\FtOp01Form;
use App\Models\Indicator;
use App\Models\OperationsLeader;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_open_improvement_modal_sets_visible_state(): void
    {
        $indicator = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();
        $leader = OperationsLeader::query()->create([
            'name' => 'Jefe Test',
            'code' => 'JT01',
            'is_active' => true,
        ]);

        Livewire::test(FtOp01Form::class, ['indicator' => $indicator])
            ->set('selectedYear', 2025)
            ->set('selectedMonth', 7)
            ->set('selectedOperationsLeaderId', $leader->id)
            ->call('openImprovementModal')
            ->assertSet('showImprovementModal', true)
            ->assertSee('Analisis de resultados (obligatorio)');
    }

    public function test_open_improvement_modal_without_leader_still_opens_modal(): void
    {
        $indicator = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();

        Livewire::test(FtOp01Form::class, ['indicator' => $indicator])
            ->set('selectedOperationsLeaderId', null)
            ->call('openImprovementModal')
            ->assertSet('showImprovementModal', true)
            ->assertSee('Debes seleccionar un jefe de operaciones');
    }

    public function test_form_field_updates_recalculate_result_percentage(): void
    {
        $indicator = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();
        $leader = OperationsLeader::query()->create([
            'name' => 'Jefe Test',
            'code' => 'JT01',
            'is_active' => true,
        ]);

        Livewire::test(FtOp01Form::class, ['indicator' => $indicator])
            ->set('selectedOperationsLeaderId', $leader->id)
            ->set('form.total_personal', 100)
            ->set('form.personal_capacitado', 50)
            ->assertSet('resultPercentage', 50.0);
    }

    public function test_save_from_modal_persists_capture(): void
    {
        $indicator = Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();
        $leader = OperationsLeader::query()->create([
            'name' => 'Jefe Test',
            'code' => 'JT01',
            'is_active' => true,
        ]);

        Livewire::test(FtOp01Form::class, ['indicator' => $indicator])
            ->set('selectedYear', 2026)
            ->set('selectedMonth', 7)
            ->set('selectedOperationsLeaderId', $leader->id)
            ->set('form.total_personal', 100)
            ->set('form.personal_capacitado', 50)
            ->set('improvementAnalysis', 'Analisis de prueba')
            ->set('improvementActionTaken', 'Accion tomada de prueba')
            ->set('improvementActionDefined', 'Accion definida de prueba')
            ->set('improvementRequired', 'Mejora requerida de prueba')
            ->call('save')
            ->assertSet('showImprovementModal', false)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('indicator_captures', [
            'indicator_id' => $indicator->id,
            'operations_leader_id' => $leader->id,
            'result_percentage' => 50,
        ]);
    }
}
