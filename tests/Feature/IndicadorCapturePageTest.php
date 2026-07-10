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

    public function test_capture_page_renders_livewire_bindings_and_scripts(): void
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
        $response->assertSee('openImprovementModal', false);
        $response->assertSee('wire:model', false);
        $response->assertSee('livewire.js', false);
        $response->assertSee('class="indicadores-form ftop01-sheet"', false);
        $response->assertDontSee('<style wire:key', false);
    }
}
