<?php

namespace Tests\Feature\GestionHumana;

use App\Models\EmployeeFichaProfile;
use App\Models\MtSt04Registro;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MtSt04DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_dashboard_and_metrics_require_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.mt-st-04.dashboard'))
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson(route('gestion-humana.mt-st-04.dashboard.metrics'))
            ->assertForbidden();
    }

    public function test_dashboard_renders_kpis_and_metrics_payload(): void
    {
        $viewer = $this->viewerUser();

        $this->createFicha('1002003001', 'Ana Activa', 'SUPERVISOR');
        $this->createFicha('1002003002', 'Luis Guarda', 'GUARDA');
        $this->createFicha('1002003003', 'Pedro Op', 'OPERADOR');
        $this->createFicha('1002003004', 'Maria Retiro', 'SUPERVISOR', EmployeeFichaProfile::STATUS_DESVINCULADO);

        MtSt04Registro::factory()->create([
            'document_number' => '1002003001',
            'estado_1' => MtSt04Registro::ESTADO_VIGENTE,
            'estado_2' => MtSt04Registro::ESTADO_VENCERA,
            'apto' => MtSt04Registro::APTO_SI,
        ]);
        MtSt04Registro::factory()->create([
            'document_number' => '1002003002',
            'estado_1' => MtSt04Registro::ESTADO_VENCIDO,
            'estado_2' => MtSt04Registro::ESTADO_NO_APLICA,
            'apto' => MtSt04Registro::APTO_NO,
        ]);
        MtSt04Registro::factory()->create([
            'document_number' => '1002003003',
            'estado_1' => MtSt04Registro::ESTADO_VENCERA,
            'estado_2' => MtSt04Registro::ESTADO_NO_APLICA,
            'apto' => null,
        ]);
        MtSt04Registro::factory()->create([
            'document_number' => '1002003004',
            'estado_1' => MtSt04Registro::ESTADO_VIGENTE,
            'estado_2' => MtSt04Registro::ESTADO_VIGENTE,
            'apto' => MtSt04Registro::APTO_SI,
        ]);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.mt-st-04.dashboard'))
            ->assertOk()
            ->assertSee('Examen psicofísico', false)
            ->assertSee('Examen psicosensométrico', false)
            ->assertSee('Estados examen 1', false)
            ->assertSee('mt-st-04-chart-examen1', false)
            ->assertSee('mt-st-04-dashboard-charts', false)
            ->assertDontSee('select2', false)
            ->assertDontSee('excelHtml5', false);

        $metrics = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.mt-st-04.dashboard.metrics'))
            ->assertOk()
            ->assertJsonPath('filters.ficha_estado', EmployeeFichaProfile::STATUS_ACTIVO)
            ->assertJsonStructure([
                'kpis' => [
                    'examen1' => ['total', 'vigente', 'vencera', 'vencido'],
                    'examen2' => ['total', 'vigente', 'vencera', 'vencido', 'no_aplica'],
                    'apto' => ['si', 'no', 'sin_dato'],
                ],
                'charts' => [
                    'examen1_estados',
                    'examen2_estados',
                    'aptos',
                ],
                'filters',
                'labels',
            ])
            ->json();

        // Universo default = 3 activos (desvinculado excluido).
        $this->assertSame(3, $metrics['kpis']['examen1']['total']);
        $this->assertSame(1, $metrics['kpis']['examen1']['vigente']);
        $this->assertSame(1, $metrics['kpis']['examen1']['vencera']);
        $this->assertSame(1, $metrics['kpis']['examen1']['vencido']);

        // Examen 2: Total2 excluye NO APLICA (2 filas GUARDA/OPERADOR).
        $this->assertSame(1, $metrics['kpis']['examen2']['total']);
        $this->assertSame(0, $metrics['kpis']['examen2']['vigente']);
        $this->assertSame(1, $metrics['kpis']['examen2']['vencera']);
        $this->assertSame(0, $metrics['kpis']['examen2']['vencido']);
        $this->assertSame(2, $metrics['kpis']['examen2']['no_aplica']);

        $this->assertSame(1, $metrics['kpis']['apto']['si']);
        $this->assertSame(1, $metrics['kpis']['apto']['no']);
        $this->assertSame(1, $metrics['kpis']['apto']['sin_dato']);

        $this->assertSame(['VIGENTE', 'VENCERA', 'VENCIDO'], $metrics['charts']['examen1_estados']['labels']);
        $this->assertSame([1, 1, 1], $metrics['charts']['examen1_estados']['data']);
        $this->assertSame([0, 1, 0], $metrics['charts']['examen2_estados']['data']);
    }

    public function test_metrics_no_aplica_excluded_from_examen2_total(): void
    {
        $viewer = $this->viewerUser();

        $this->createFicha('1002003010', 'Guarda Exacto', 'GUARDA');
        $this->createFicha('1002003011', 'Guarda Compuesto', 'GUARDA SJ');
        $this->createFicha('1002003012', 'Supervisor', 'SUPERVISOR');

        MtSt04Registro::factory()->create([
            'document_number' => '1002003010',
            'estado_1' => MtSt04Registro::ESTADO_VIGENTE,
            'estado_2' => MtSt04Registro::ESTADO_NO_APLICA,
        ]);
        MtSt04Registro::factory()->create([
            'document_number' => '1002003011',
            'estado_1' => MtSt04Registro::ESTADO_VIGENTE,
            'estado_2' => MtSt04Registro::ESTADO_VIGENTE,
        ]);
        MtSt04Registro::factory()->create([
            'document_number' => '1002003012',
            'estado_1' => MtSt04Registro::ESTADO_VENCIDO,
            'estado_2' => MtSt04Registro::ESTADO_VENCIDO,
        ]);

        $metrics = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.mt-st-04.dashboard.metrics'))
            ->assertOk()
            ->json();

        $this->assertSame(3, $metrics['kpis']['examen1']['total']);
        $this->assertSame(2, $metrics['kpis']['examen2']['total']);
        $this->assertSame(1, $metrics['kpis']['examen2']['vigente']);
        $this->assertSame(1, $metrics['kpis']['examen2']['vencido']);
        $this->assertSame(1, $metrics['kpis']['examen2']['no_aplica']);
    }

    public function test_metrics_filter_ficha_estado_todos_incluye_desvinculados(): void
    {
        $viewer = $this->viewerUser();

        $this->createFicha('1002003020', 'Activo', 'SUPERVISOR');
        $this->createFicha('1002003021', 'Retiro', 'SUPERVISOR', EmployeeFichaProfile::STATUS_DESVINCULADO);

        MtSt04Registro::factory()->create([
            'document_number' => '1002003020',
            'estado_1' => MtSt04Registro::ESTADO_VIGENTE,
            'estado_2' => MtSt04Registro::ESTADO_VIGENTE,
        ]);
        MtSt04Registro::factory()->create([
            'document_number' => '1002003021',
            'estado_1' => MtSt04Registro::ESTADO_VENCIDO,
            'estado_2' => MtSt04Registro::ESTADO_VENCIDO,
        ]);

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.mt-st-04.dashboard.metrics', [
                'ficha_estado' => 'todos',
            ]))
            ->assertOk()
            ->assertJsonPath('kpis.examen1.total', 2)
            ->assertJsonPath('filters.ficha_estado', 'todos');
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function createFicha(
        string $documentNumber,
        string $fullName,
        string $positionName = 'SUPERVISOR',
        string $employmentStatus = EmployeeFichaProfile::STATUS_ACTIVO,
        array $extra = [],
    ): EmployeeFichaProfile {
        return EmployeeFichaProfile::query()->create(array_merge([
            'document_number' => $documentNumber,
            'full_name' => $fullName,
            'position_name' => $positionName,
            'employment_status' => $employmentStatus,
        ], $extra));
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.mt_st_04',
            'mt_st_04.view',
        ]);

        return $user;
    }
}
