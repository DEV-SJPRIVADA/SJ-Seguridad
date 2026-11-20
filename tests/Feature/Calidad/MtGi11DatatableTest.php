<?php

namespace Tests\Feature\Calidad;

use App\Models\Calidad\MtGi11CargoResponsable;
use App\Models\Calidad\MtGi11Estado;
use App\Models\Calidad\MtGi11Proceso;
use App\Models\Calidad\MtGi11Solicitud;
use App\Models\Calidad\MtGi11TipoDocumento;
use App\Models\Calidad\MtGi11TipoSolicitud;
use App\Models\User;
use App\Services\Calidad\MtGi11DashboardService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MtGi11DatatableTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /** @var array<string, int> */
    private array $catalogs = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->catalogs = [
            'proceso_id' => MtGi11Proceso::query()->firstOrFail()->id,
            'tipo_documento_id' => MtGi11TipoDocumento::query()->firstOrFail()->id,
            'tipo_solicitud_id' => MtGi11TipoSolicitud::query()->firstOrFail()->id,
            'estado_id' => MtGi11Estado::query()->where('code', 'PENDIENTE')->value('id'),
            'cargo_responsable_id' => MtGi11CargoResponsable::query()->create([
                'code' => 'JEFE',
                'name' => 'JEFE',
            ])->id,
        ];

        $this->user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $this->user->givePermissionTo([
            'view.board.calidad.mt_gi_11',
            'mt_gi_11.matriz.view',
            'mt_gi_11.matriz.edit',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeSolicitud(array $overrides = []): MtGi11Solicitud
    {
        return MtGi11Solicitud::query()->create(array_merge($this->catalogs, [
            'fecha_solicitud' => now()->toDateString(),
            'consecutivo' => 'MT-GI-11-001',
            'nombre_documento' => 'PROCEDIMIENTO',
            'justificacion' => 'JUSTIFICACION',
            'responsable_proceso' => 'RESPONSABLE',
            'version_actualizada' => 1,
            'created_by' => $this->user->id,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function datatable(array $query = []): array
    {
        return $this->actingAs($this->user)
            ->getJson(route('calidad.mt-gi-11.matriz.datatable', array_merge([
                'draw' => 1,
                'start' => 0,
                'length' => 25,
            ], $query)))
            ->assertOk()
            ->json();
    }

    public function test_datatable_returns_expected_shape(): void
    {
        $this->makeSolicitud();

        $data = $this->datatable();

        $this->assertSame(1, $data['draw']);
        $this->assertSame(1, $data['recordsTotal']);
        $this->assertSame(1, $data['recordsFiltered']);
        $this->assertCount(1, $data['data']);
        $this->assertCount(17, $data['data'][0]);
    }

    public function test_global_search_matches_consecutivo_and_document_name(): void
    {
        $this->makeSolicitud(['consecutivo' => 'MT-GI-11-AAA', 'nombre_documento' => 'PROCEDIMIENTO DE COMPRAS']);
        $this->makeSolicitud(['consecutivo' => 'MT-GI-11-BBB', 'nombre_documento' => 'INSTRUCTIVO DE BODEGA']);

        $byConsecutivo = $this->datatable(['search' => ['value' => 'AAA']]);
        $this->assertSame(1, $byConsecutivo['recordsFiltered']);

        $byNombre = $this->datatable(['search' => ['value' => 'INSTRUCTIVO']]);
        $this->assertSame(1, $byNombre['recordsFiltered']);
    }

    public function test_global_search_matches_catalog_names(): void
    {
        $proceso = MtGi11Proceso::query()->firstOrFail();
        $this->makeSolicitud(['proceso_id' => $proceso->id]);

        $data = $this->datatable(['search' => ['value' => mb_substr((string) $proceso->name, 0, 8)]]);

        $this->assertSame(1, $data['recordsFiltered']);
    }

    public function test_filter_by_consecutivo_is_partial(): void
    {
        $this->makeSolicitud(['consecutivo' => 'MT-GI-11-001']);
        $this->makeSolicitud(['consecutivo' => 'MT-GI-11-002']);

        $data = $this->datatable(['consecutivo' => '11-00']);

        $this->assertSame(2, $data['recordsFiltered']);
    }

    public function test_filter_by_estado(): void
    {
        $aprobada = MtGi11Estado::query()->where('code', 'APROBADA')->value('id');

        $this->makeSolicitud();
        $this->makeSolicitud(['consecutivo' => 'MT-GI-11-002', 'estado_id' => $aprobada]);

        $data = $this->datatable(['estado_id' => (string) $aprobada]);

        $this->assertSame(1, $data['recordsFiltered']);
    }

    public function test_filter_by_date_range(): void
    {
        $this->makeSolicitud(['consecutivo' => 'MT-GI-11-OLD', 'fecha_solicitud' => '2026-01-15']);
        $this->makeSolicitud(['consecutivo' => 'MT-GI-11-NEW', 'fecha_solicitud' => '2026-11-01']);

        $data = $this->datatable(['fecha_desde' => '2026-10-01']);

        $this->assertSame(1, $data['recordsFiltered']);
    }

    public function test_length_is_capped_at_100(): void
    {
        for ($i = 1; $i <= 120; $i++) {
            $this->makeSolicitud(['consecutivo' => 'MT-GI-11-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT)]);
        }

        $data = $this->datatable(['length' => 500]);

        // Tope duro: nunca devuelve más de 100 filas por página.
        $this->assertCount(100, $data['data']);
        $this->assertSame(120, $data['recordsTotal']);
    }

    public function test_ordering_by_consecutivo(): void
    {
        $this->makeSolicitud(['consecutivo' => 'MT-GI-11-ZZZ']);
        $this->makeSolicitud(['consecutivo' => 'MT-GI-11-AAA']);

        $data = $this->datatable(['order' => [['column' => 1, 'dir' => 'asc']]]);

        $this->assertSame('MT-GI-11-AAA', $data['data'][0][1]);
    }

    public function test_dashboard_returns_kpis_and_series(): void
    {
        $this->makeSolicitud();

        $metrics = $this->actingAs($this->user)
            ->get(route('calidad.mt-gi-11.dashboard'))
            ->assertOk()
            ->viewData('metrics');

        $this->assertArrayHasKey('kpis', $metrics);
        $this->assertArrayHasKey('estados', $metrics);
        $this->assertArrayHasKey('proceso', $metrics);
        $this->assertArrayHasKey('tipo_documento', $metrics);
        $this->assertSame(1, $metrics['kpis']['total']);
        $this->assertCount(4, $metrics['estados']);
    }

    /**
     * Regresión: la agregación del dashboard no debe arrastrar `select *` ni el
     * subquery de withCount. MySQL con only_full_group_by rechaza esa mezcla
     * (SQLite no lo detecta), así que se inspecciona el SQL generado.
     */
    public function test_dashboard_aggregate_sql_has_no_select_star(): void
    {
        $this->makeSolicitud();

        $service = app(MtGi11DashboardService::class);
        $method = new \ReflectionMethod($service, 'aggregateQuery');
        $method->setAccessible(true);

        /** @var Builder $base */
        $base = $method->invoke($service, [], (int) $this->user->id, true);

        $byEstado = (clone $base)
            ->reorder()
            ->selectRaw('estado_id, COUNT(*) as total')
            ->groupBy('estado_id');

        $sql = $byEstado->toSql();

        // Sin `select *`: la lista de columnas debe ser solo la agregación.
        $this->assertStringNotContainsString('select *', mb_strtolower($sql));
        $this->assertStringNotContainsString('calidad_mt_gi_11_solicitudes`.*', $sql);
        $this->assertStringNotContainsString('adjuntos_count', $sql);
        $this->assertStringContainsString('group by', mb_strtolower($sql));

        // Y la agregación devuelve el conteo correcto.
        $this->assertSame(1, (int) $byEstado->toBase()->pluck('total')->sum());
    }
}
