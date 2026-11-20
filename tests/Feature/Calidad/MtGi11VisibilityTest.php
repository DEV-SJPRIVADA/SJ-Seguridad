<?php

namespace Tests\Feature\Calidad;

use App\Models\Calidad\MtGi11CargoResponsable;
use App\Models\Calidad\MtGi11Estado;
use App\Models\Calidad\MtGi11Proceso;
use App\Models\Calidad\MtGi11Solicitud;
use App\Models\Calidad\MtGi11TipoDocumento;
use App\Models\Calidad\MtGi11TipoSolicitud;
use App\Models\User;
use App\Services\Calidad\MtGi11DatatableService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alcance de registros MT-GI-11: cada usuario ve lo que solicita;
 * `mt_gi_11.view_all` (Calidad) ve todo.
 */
class MtGi11VisibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    private User $other;

    private User $calidad;

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
            'estado_id' => MtGi11Estado::query()->where('code', 'PENDIENTE')->firstOrFail()->id,
            'cargo_responsable_id' => MtGi11CargoResponsable::query()->create([
                'code' => 'ANALISTA',
                'name' => 'ANALISTA',
                'is_active' => true,
            ])->id,
        ];

        $this->author = $this->makeUser(['mt_gi_11.matriz.view', 'mt_gi_11.matriz.edit']);
        $this->other = $this->makeUser(['mt_gi_11.matriz.view', 'mt_gi_11.matriz.edit']);
        $this->calidad = $this->makeUser(['mt_gi_11.matriz.view', 'mt_gi_11.view_all']);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function makeUser(array $permissions): User
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(array_merge(['view.board.calidad.mt_gi_11'], $permissions));

        return $user;
    }

    private function makeSolicitud(User $owner, string $consecutivo): MtGi11Solicitud
    {
        return MtGi11Solicitud::query()->create(array_merge($this->catalogs, [
            'fecha_solicitud' => now()->toDateString(),
            'consecutivo' => $consecutivo,
            'nombre_documento' => 'DOCUMENTO',
            'justificacion' => 'JUSTIFICACION',
            'responsable_proceso' => 'RESPONSABLE',
            'version_actualizada' => 1,
            'created_by' => $owner->id,
        ]));
    }

    private function datatable(User $user): array
    {
        return $this->actingAs($user)
            ->getJson(route('calidad.mt-gi-11.matriz.datatable', ['draw' => 1, 'start' => 0, 'length' => 25]))
            ->assertOk()
            ->json();
    }

    public function test_user_only_sees_own_solicitudes_in_datatable(): void
    {
        $this->makeSolicitud($this->author, 'MT-GI-11-001');
        $this->makeSolicitud($this->other, 'MT-GI-11-002');

        $data = $this->datatable($this->author);

        $this->assertSame(1, $data['recordsFiltered']);
        $this->assertCount(1, $data['data']);
        $this->assertSame('MT-GI-11-001', $data['data'][0][1]);
    }

    public function test_view_all_sees_every_solicitud(): void
    {
        $this->makeSolicitud($this->author, 'MT-GI-11-001');
        $this->makeSolicitud($this->other, 'MT-GI-11-002');
        $this->makeSolicitud($this->author, 'MT-GI-11-003');

        $data = $this->datatable($this->calidad);

        $this->assertSame(3, $data['recordsFiltered']);
        $this->assertCount(3, $data['data']);
    }

    public function test_user_cannot_open_someone_elses_solicitud(): void
    {
        $solicitud = $this->makeSolicitud($this->other, 'MT-GI-11-009');

        $this->actingAs($this->author)
            ->get(route('calidad.mt-gi-11.matriz.show', $solicitud))
            ->assertForbidden();

        $this->actingAs($this->calidad)
            ->get(route('calidad.mt-gi-11.matriz.show', $solicitud))
            ->assertOk();
    }

    public function test_user_cannot_edit_or_delete_someone_elses_solicitud(): void
    {
        $solicitud = $this->makeSolicitud($this->other, 'MT-GI-11-010');

        $this->actingAs($this->author)
            ->patch(route('calidad.mt-gi-11.matriz.update', $solicitud), array_merge($this->catalogs, [
                'fecha_solicitud' => now()->toDateString(),
                'consecutivo' => 'MT-GI-11-010',
                'nombre_documento' => 'CAMBIO',
                'justificacion' => 'CAMBIO',
                'responsable_proceso' => 'CAMBIO',
                'estado_id' => $this->catalogs['estado_id'],
            ]))
            ->assertForbidden();

        $this->actingAs($this->author)
            ->delete(route('calidad.mt-gi-11.matriz.destroy', $solicitud))
            ->assertForbidden();

        $this->assertNotSoftDeleted('calidad_mt_gi_11_solicitudes', ['id' => $solicitud->id]);
    }

    public function test_dashboard_counts_respect_scope(): void
    {
        $this->makeSolicitud($this->author, 'MT-GI-11-001');
        $this->makeSolicitud($this->other, 'MT-GI-11-002');

        $own = $this->actingAs($this->author)
            ->get(route('calidad.mt-gi-11.dashboard'))
            ->assertOk()
            ->viewData('metrics');

        $all = $this->actingAs($this->calidad)
            ->get(route('calidad.mt-gi-11.dashboard'))
            ->assertOk()
            ->viewData('metrics');

        $this->assertSame(1, $own['kpis']['total']);
        $this->assertSame(2, $all['kpis']['total']);
    }

    public function test_export_query_respects_scope_and_returns_file(): void
    {
        $this->makeSolicitud($this->author, 'MT-GI-11-AAA');
        $this->makeSolicitud($this->other, 'MT-GI-11-BBB');

        // El export usa la misma query del listado: sin view_all solo trae lo propio.
        $datatable = app(MtGi11DatatableService::class);
        $ownQuery = $datatable->filteredQuery([], (int) $this->author->id, false, ordered: false);
        $allQuery = $datatable->filteredQuery([], (int) $this->author->id, true, ordered: false);

        $this->assertSame(1, $ownQuery->count());
        $this->assertSame(2, $allQuery->count());

        // El archivo se genera correctamente (respuesta binaria XLSX).
        $this->actingAs($this->author)
            ->get(route('calidad.mt-gi-11.matriz.export'))
            ->assertOk();
    }
}
