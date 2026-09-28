<?php

namespace Tests\Feature\GestionHumana;

use App\Exports\BaseExport;
use App\Models\AcreditacionAcreditado;
use App\Models\AcreditacionReporteDiarioCarga;
use App\Models\AcreditacionReporteDiarioFila;
use App\Models\EmployeeFichaProfile;
use App\Models\PayrollCatalogItem;
use App\Models\PersonalRequisition;
use App\Models\PersonalRequisitionFichaEntry;
use App\Models\RequisitionCity;
use App\Models\RequisitionClient;
use App\Models\RequisitionClientType;
use App\Models\RequisitionPosition;
use App\Models\RequisitionProgrammingType;
use App\Models\RequisitionRequestReason;
use App\Models\RequisitionUniform;
use App\Models\User;
use App\Services\Access\AcreditacionesAccessService;
use App\Services\GestionHumana\AcreditacionCargoMatchNormalizer;
use App\Services\GestionHumana\AcreditacionValidacionesExportService;
use App\Services\GestionHumana\AcreditacionValidacionesResultStore;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class AcreditacionesValidacionesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
        Cache::flush();
    }

    public function test_viewer_cannot_see_validaciones_tab_and_gets_forbidden(): void
    {
        $viewer = $this->viewerUser();
        $service = app(AcreditacionesAccessService::class);

        $this->assertNotContains('validaciones', $service->visibleTabsFor($viewer));

        $this->actingAs($viewer)
            ->get(route('gestion-humana.acreditaciones.validaciones'))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.acreditaciones.acreditados'))
            ->assertOk()
            ->assertDontSee('Validaciones', false);
    }

    public function test_editor_sees_validaciones_tab_and_shell(): void
    {
        $editor = $this->editorUser();
        $service = app(AcreditacionesAccessService::class);
        $today = Carbon::now(config('app.timezone'))->toDateString();

        $this->assertContains('validaciones', $service->visibleTabsFor($editor));

        $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.validaciones'))
            ->assertOk()
            ->assertSee('Validaciones', false)
            ->assertSee('Fecha reporte', false)
            ->assertSee($today, false)
            ->assertSee('Ejecutar validaciones', false);
    }

    public function test_day_without_carga_shows_gate_message_and_reporte_link(): void
    {
        $editor = $this->editorUser();
        $fecha = '2026-09-20';

        $response = $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.validaciones', [
                'fecha_reporte' => $fecha,
            ]));

        $response->assertOk()
            ->assertSee('No hay carga de Reporte Diario para esta fecha', false)
            ->assertSee(
                route('gestion-humana.acreditaciones.reporte-diario', ['fecha_reporte' => $fecha], false),
                false,
            )
            ->assertDontSee('Gate OK', false);
    }

    public function test_day_with_only_one_origen_shows_missing_origen_message(): void
    {
        $editor = $this->editorUser();
        $fecha = '2026-09-21';

        AcreditacionReporteDiarioCarga::factory()
            ->withProceso($editor->id, 3)
            ->create(['fecha_reporte' => $fecha]);

        $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.validaciones', [
                'fecha_reporte' => $fecha,
            ]))
            ->assertOk()
            ->assertSee('Falta carga Acreditado APO', false)
            ->assertDontSee('Gate OK', false);

        AcreditacionReporteDiarioCarga::query()->whereDate('fecha_reporte', $fecha)->delete();

        AcreditacionReporteDiarioCarga::factory()
            ->withAcreditado($editor->id, 2)
            ->create(['fecha_reporte' => $fecha]);

        $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.validaciones', [
                'fecha_reporte' => $fecha,
            ]))
            ->assertOk()
            ->assertSee('Falta carga En proceso', false)
            ->assertDontSee('Gate OK', false);
    }

    public function test_day_with_both_origins_shows_gate_ok(): void
    {
        $editor = $this->editorUser();
        $fecha = '2026-09-22';

        AcreditacionReporteDiarioCarga::factory()
            ->withProceso($editor->id, 5)
            ->withAcreditado($editor->id, 4)
            ->create(['fecha_reporte' => $fecha]);

        $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.validaciones', [
                'fecha_reporte' => $fecha,
            ]))
            ->assertOk()
            ->assertSee('Gate OK', false)
            ->assertSee('Listo para validar', false)
            ->assertSee('Pulse Ejecutar validaciones', false);
    }

    public function test_future_fecha_reporte_is_clamped_to_today(): void
    {
        $editor = $this->editorUser();
        $today = Carbon::now(config('app.timezone'))->toDateString();
        $future = Carbon::now(config('app.timezone'))->addDays(3)->toDateString();

        $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.validaciones', [
                'fecha_reporte' => $future,
            ]))
            ->assertOk()
            ->assertSee('value="'.$today.'"', false);
    }

    public function test_viewer_forbidden_on_post_ejecutar(): void
    {
        $viewer = $this->viewerUser();
        $fecha = '2026-09-22';

        AcreditacionReporteDiarioCarga::factory()
            ->withProceso($viewer->id, 1)
            ->withAcreditado($viewer->id, 1)
            ->create(['fecha_reporte' => $fecha]);

        $this->actingAs($viewer)
            ->post(route('gestion-humana.acreditaciones.validaciones.run'), [
                'fecha_reporte' => $fecha,
            ])
            ->assertForbidden();
    }

    public function test_gate_fail_does_not_execute(): void
    {
        $editor = $this->editorUser();
        $fecha = '2026-09-18';

        AcreditacionReporteDiarioCarga::factory()
            ->withProceso($editor->id, 2)
            ->create(['fecha_reporte' => $fecha]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.validaciones.run'), [
                'fecha_reporte' => $fecha,
            ])
            ->assertRedirect(route('gestion-humana.acreditaciones.validaciones', [
                'fecha_reporte' => $fecha,
            ]))
            ->assertSessionHas('error');

        $this->assertNull(
            Cache::get(app(AcreditacionValidacionesResultStore::class)->cacheKey(
                $editor->id,
                $fecha,
                'any',
            )),
        );
    }

    public function test_both_origins_execute_returns_token_and_counts(): void
    {
        $editor = $this->editorUser();
        $fecha = '2026-09-17';
        $this->seedBothOriginsCarga($editor->id, $fecha);

        $response = $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.validaciones.run'), [
                'fecha_reporte' => $fecha,
            ]);

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertNotNull($location);
        $this->assertStringContainsString('run_token=', $location);
        $this->assertStringContainsString('fecha_reporte='.$fecha, $location);

        parse_str(parse_url($location, PHP_URL_QUERY) ?: '', $query);
        $token = (string) ($query['run_token'] ?? '');
        $this->assertNotSame('', $token);

        $payload = app(AcreditacionValidacionesResultStore::class)->get($editor->id, $fecha, $token);
        $this->assertNotNull($payload);
        $this->assertArrayHasKey('counts', $payload);
        foreach (AcreditacionValidacionesResultStore::COLAS as $cola) {
            $this->assertArrayHasKey($cola, $payload['counts']);
        }
    }

    public function test_cola_sin_acreditacion_includes_activo_without_acreditado(): void
    {
        $editor = $this->editorUser();
        $fecha = '2026-09-16';
        $this->seedBothOriginsCarga($editor->id, $fecha);

        $this->createFicha('111111', 'Sin Acred', EmployeeFichaProfile::STATUS_ACTIVO, [
            'position_name' => 'Vigilante Ficha',
        ]);
        $this->createFicha('222222', 'Con Acred', EmployeeFichaProfile::STATUS_ACTIVO);
        $this->createFicha('333333', 'Inactivo', EmployeeFichaProfile::STATUS_DESVINCULADO);

        AcreditacionAcreditado::factory()->create([
            'document_number' => '222222',
            'full_name' => 'Con Acred',
            'cargo_apo' => 'VIGILANTE',
        ]);

        $payload = $this->runAndGetPayload($editor, $fecha);
        $rows = collect($payload['colas'][AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION]);
        $docs = $rows->pluck('document_number')->all();

        $this->assertContains('111111', $docs);
        $this->assertNotContains('222222', $docs);
        $this->assertNotContains('333333', $docs);

        $sin = $rows->firstWhere('document_number', '111111');
        $this->assertNotNull($sin);
        $this->assertSame('Vigilante Ficha', $sin['cargo'] ?? null);
    }

    public function test_cola_sin_acreditacion_resolves_personal_tipo_from_requisition_area(): void
    {
        $editor = $this->editorUser();
        $fecha = '2026-09-05';
        $this->seedBothOriginsCarga($editor->id, $fecha);

        $this->createFichaWithRequisitionArea('777001', 'Operativo Uno', 'operaciones', 'Escolta');
        $this->createFichaWithRequisitionArea('777002', 'Admin Uno', 'gestion_humana', 'Analista');

        $payload = $this->runAndGetPayload($editor, $fecha);
        $rows = collect($payload['colas'][AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION])
            ->keyBy('document_number');

        $this->assertSame('OPERATIVO', $rows['777001']['personal_tipo'] ?? null);
        $this->assertSame('Escolta', $rows['777001']['cargo'] ?? null);
        $this->assertSame('ADMINISTRATIVO', $rows['777002']['personal_tipo'] ?? null);
        $this->assertSame('Analista', $rows['777002']['cargo'] ?? null);
    }

    public function test_datatable_filters_sin_acreditacion_by_cargo_and_personal_tipo(): void
    {
        $editor = $this->editorUser();
        $fecha = '2026-09-04';
        $this->seedBothOriginsCarga($editor->id, $fecha);

        PayrollCatalogItem::query()->firstOrCreate(
            ['catalog_type' => 'position', 'code' => 'VIG-AV'],
            ['name' => 'Vigilante', 'is_active' => true, 'sort_order' => 1],
        );
        PayrollCatalogItem::query()->firstOrCreate(
            ['catalog_type' => 'position', 'code' => 'AUX-AV'],
            ['name' => 'Auxiliar', 'is_active' => true, 'sort_order' => 2],
        );

        $this->createFichaWithRequisitionArea('888001', 'Filtro Oper', 'operaciones', 'Vigilante');
        $this->createFichaWithRequisitionArea('888002', 'Filtro Admin', 'gestion_humana', 'Auxiliar');

        $payload = $this->runAndGetPayload($editor, $fecha);
        $token = $payload['run_token'];

        $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.validaciones', [
                'fecha_reporte' => $fecha,
                'run_token' => $token,
            ]))
            ->assertOk()
            ->assertSee('filter_sin_acreditacion_cargo', false)
            ->assertSee('Vigilante', false)
            ->assertSee('Auxiliar', false);

        $this->actingAs($editor)
            ->getJson(route('gestion-humana.acreditaciones.validaciones.datatable', [
                'fecha_reporte' => $fecha,
                'run_token' => $token,
                'cola' => AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION,
                'draw' => 1,
                'start' => 0,
                'length' => 25,
                'personal_tipo' => 'OPERATIVO',
            ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.document_number', '888001')
            ->assertJsonPath('data.0.cargo', 'Vigilante')
            ->assertJsonPath('data.0.personal_tipo', 'OPERATIVO');

        $this->actingAs($editor)
            ->getJson(route('gestion-humana.acreditaciones.validaciones.datatable', [
                'fecha_reporte' => $fecha,
                'run_token' => $token,
                'cola' => AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION,
                'draw' => 2,
                'start' => 0,
                'length' => 25,
                'cargo' => 'Auxiliar',
            ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.document_number', '888002');
    }

    public function test_cola_ausente_reporte_when_pair_missing_from_any_origen(): void
    {
        $editor = $this->editorUser();
        $fecha = '2026-09-15';
        $carga = $this->seedBothOriginsCarga($editor->id, $fecha);

        AcreditacionReporteDiarioFila::factory()->proceso()->create([
            'carga_id' => $carga->id,
            'document_number' => '444444',
            'cargo' => 'VIGILANTE',
        ]);

        $this->createFicha('555555', 'Ausente Par', EmployeeFichaProfile::STATUS_ACTIVO, [
            'position_name' => 'Cargo Desde Ficha',
        ]);

        $ausente = AcreditacionAcreditado::factory()->create([
            'document_number' => '555555',
            'full_name' => 'Ausente Par',
            'cargo' => 'TEXTO VIEJO ACREDITADO',
            'cargo_apo' => 'VIGILANTE',
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
        ]);

        $presente = AcreditacionAcreditado::factory()->create([
            'document_number' => '444444',
            'full_name' => 'Presente Par',
            'cargo_apo' => 'VIGILANTE',
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
        ]);

        $payload = $this->runAndGetPayload($editor, $fecha);
        $rows = collect($payload['colas'][AcreditacionValidacionesResultStore::COLA_AUSENTE_REPORTE]);
        $ids = $rows->pluck('acreditado_id')->all();

        $this->assertContains($ausente->id, $ids);
        $this->assertNotContains($presente->id, $ids);

        $ausenteRow = $rows->firstWhere('acreditado_id', $ausente->id);
        $this->assertNotNull($ausenteRow);
        $this->assertSame('Cargo Desde Ficha', $ausenteRow['cargo'] ?? null);
    }

    public function test_cola_en_proceso_ya_acreditado_only_when_pair_in_origen_acreditado(): void
    {
        $editor = $this->editorUser();
        $fecha = '2026-09-14';
        $carga = $this->seedBothOriginsCarga($editor->id, $fecha);

        AcreditacionReporteDiarioFila::factory()->acreditado()->create([
            'carga_id' => $carga->id,
            'document_number' => '666666',
            'cargo' => 'ESCOLTA',
            'vigencia_acr' => '2027-06-01',
        ]);

        AcreditacionReporteDiarioFila::factory()->proceso()->create([
            'carga_id' => $carga->id,
            'document_number' => '777777',
            'cargo' => 'ESCOLTA',
        ]);

        $match = AcreditacionAcreditado::factory()->enProceso()->create([
            'document_number' => '666666',
            'full_name' => 'Ya Acreditado APO',
            'cargo_apo' => 'ESCOLTA',
        ]);

        $onlyProceso = AcreditacionAcreditado::factory()->enProceso()->create([
            'document_number' => '777777',
            'full_name' => 'Solo Proceso',
            'cargo_apo' => 'ESCOLTA',
        ]);

        $payload = $this->runAndGetPayload($editor, $fecha);
        $rows = collect($payload['colas'][AcreditacionValidacionesResultStore::COLA_EN_PROCESO_YA_ACREDITADO]);
        $ids = $rows->pluck('acreditado_id')->all();

        $this->assertContains($match->id, $ids);
        $this->assertNotContains($onlyProceso->id, $ids);

        $matchRow = $rows->firstWhere('acreditado_id', $match->id);
        $this->assertNotNull($matchRow);
        $this->assertSame('2027-06-01', $matchRow['vigencia_apo'] ?? null);
    }

    public function test_cola_vencidas_includes_desacreditado_and_por_vencer(): void
    {
        $editor = $this->editorUser();
        $fecha = '2026-09-13';
        $this->seedBothOriginsCarga($editor->id, $fecha);

        $des = AcreditacionAcreditado::factory()->desacreditado()->create([
            'document_number' => '888801',
            'cargo_apo' => 'VIGILANTE',
        ]);
        $por = AcreditacionAcreditado::factory()->porVencer()->create([
            'document_number' => '888802',
            'cargo_apo' => 'VIGILANTE',
        ]);
        $ok = AcreditacionAcreditado::factory()->create([
            'document_number' => '888803',
            'cargo_apo' => 'VIGILANTE',
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
        ]);

        $payload = $this->runAndGetPayload($editor, $fecha);
        $ids = collect($payload['colas'][AcreditacionValidacionesResultStore::COLA_VENCIDAS])
            ->pluck('acreditado_id')
            ->all();

        $this->assertContains($des->id, $ids);
        $this->assertContains($por->id, $ids);
        $this->assertNotContains($ok->id, $ids);
    }

    public function test_normalization_different_suffix_does_not_match(): void
    {
        $editor = $this->editorUser();
        $fecha = '2026-09-12';
        $carga = $this->seedBothOriginsCarga($editor->id, $fecha);

        AcreditacionReporteDiarioFila::factory()->proceso()->create([
            'carga_id' => $carga->id,
            'document_number' => '999901',
            'cargo' => 'Vigilante de seguridad',
        ]);

        $acreditado = AcreditacionAcreditado::factory()->create([
            'document_number' => '999901',
            'full_name' => 'Sufijo Distinto',
            'cargo_apo' => 'VIGILANTE',
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
        ]);

        $normalizer = app(AcreditacionCargoMatchNormalizer::class);
        $this->assertFalse(
            $normalizer->cargosMatch('VIGILANTE', 'Vigilante de seguridad'),
        );
        $this->assertTrue(
            $normalizer->cargosMatch('VIGILANTE', 'vigilante '),
        );

        $payload = $this->runAndGetPayload($editor, $fecha);
        $ids = collect($payload['colas'][AcreditacionValidacionesResultStore::COLA_AUSENTE_REPORTE])
            ->pluck('acreditado_id')
            ->all();

        $this->assertContains($acreditado->id, $ids);
    }

    public function test_datatable_returns_rows_from_cache_and_caps_length(): void
    {
        $editor = $this->editorUser();
        $fecha = '2026-09-11';
        $this->seedBothOriginsCarga($editor->id, $fecha);
        $this->createFicha('101010', 'DT Uno', EmployeeFichaProfile::STATUS_ACTIVO);

        $payload = $this->runAndGetPayload($editor, $fecha);
        $token = $payload['run_token'];

        $this->actingAs($editor)
            ->getJson(route('gestion-humana.acreditaciones.validaciones.datatable', [
                'fecha_reporte' => $fecha,
                'run_token' => $token,
                'cola' => AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION,
                'draw' => 1,
                'start' => 0,
                'length' => 500,
            ]))
            ->assertOk()
            ->assertJsonPath('expired', false)
            ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);

        $this->actingAs($editor)
            ->getJson(route('gestion-humana.acreditaciones.validaciones.datatable', [
                'fecha_reporte' => $fecha,
                'run_token' => '00000000-0000-4000-8000-000000000000',
                'cola' => AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION,
                'draw' => 2,
                'start' => 0,
                'length' => 10,
            ]))
            ->assertOk()
            ->assertJsonPath('expired', true)
            ->assertJsonPath('recordsTotal', 0);
    }

    public function test_get_validaciones_does_not_auto_execute(): void
    {
        $editor = $this->editorUser();
        $fecha = '2026-09-10';
        $this->seedBothOriginsCarga($editor->id, $fecha);

        $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.validaciones', [
                'fecha_reporte' => $fecha,
            ]))
            ->assertOk()
            ->assertDontSee('Ficha activa sin acreditación', false)
            ->assertSee('Pulse Ejecutar validaciones', false);
    }

    public function test_export_cola_returns_excel_with_valid_token(): void
    {
        $editor = $this->editorUser();
        $fecha = '2026-09-09';
        $this->seedBothOriginsCarga($editor->id, $fecha);
        $this->createFicha('121212', 'Export Uno', EmployeeFichaProfile::STATUS_ACTIVO);

        $payload = $this->runAndGetPayload($editor, $fecha);
        $token = $payload['run_token'];

        $response = $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.validaciones.export', [
                'fecha_reporte' => $fecha,
                'run_token' => $token,
                'cola' => AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION,
            ]));

        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml.sheet',
            (string) $response->headers->get('Content-Type'),
        );
    }

    public function test_export_cola_applies_document_number_filter(): void
    {
        $editor = $this->editorUser();
        $fecha = '2026-08-28';
        $this->seedBothOriginsCarga($editor->id, $fecha);
        $this->createFichaWithRequisitionArea('991001', 'Export Filtro A', 'operaciones', 'Vigilante');
        $this->createFichaWithRequisitionArea('991002', 'Export Filtro B', 'gestion_humana', 'Auxiliar');

        $payload = $this->runAndGetPayload($editor, $fecha);
        $token = $payload['run_token'];

        $response = $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.validaciones.export', [
                'fecha_reporte' => $fecha,
                'run_token' => $token,
                'cola' => AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION,
                'document_number' => '991001',
            ]));

        $response->assertOk();

        $temp = tempnam(sys_get_temp_dir(), 'valfilt');
        $this->assertNotFalse($temp);
        file_put_contents($temp, $response->streamedContent());

        try {
            $spreadsheet = IOFactory::load($temp);
            $sheet = $spreadsheet->getActiveSheet();
            $values = [];
            foreach ($sheet->toArray(null, true, true, true) as $row) {
                $values[] = implode('|', array_map(static fn ($v): string => trim((string) $v), $row));
            }
            $joined = implode("\n", $values);
            $this->assertStringContainsString('991001', $joined);
            $this->assertStringContainsString('Export Filtro A', $joined);
            $this->assertStringNotContainsString('991002', $joined);
            $this->assertStringNotContainsString('Export Filtro B', $joined);
        } finally {
            @unlink($temp);
        }
    }

    public function test_export_consolidated_returns_excel_with_valid_token(): void
    {
        $editor = $this->editorUser();
        $fecha = '2026-09-08';
        $this->seedBothOriginsCarga($editor->id, $fecha);

        $payload = $this->runAndGetPayload($editor, $fecha);

        $response = $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.validaciones.export-consolidated', [
                'fecha_reporte' => $fecha,
                'run_token' => $payload['run_token'],
            ]));

        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml.sheet',
            (string) $response->headers->get('Content-Type'),
        );
    }

    public function test_export_without_token_fails(): void
    {
        $editor = $this->editorUser();
        $fecha = '2026-09-07';
        $this->seedBothOriginsCarga($editor->id, $fecha);

        $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.validaciones.export', [
                'fecha_reporte' => $fecha,
                'cola' => AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION,
            ]))
            ->assertSessionHasErrors('run_token');

        $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.validaciones.export-consolidated', [
                'fecha_reporte' => $fecha,
                'run_token' => '00000000-0000-4000-8000-000000000000',
            ]))
            ->assertRedirect(route('gestion-humana.acreditaciones.validaciones', [
                'fecha_reporte' => $fecha,
            ]))
            ->assertSessionHas('error');
    }

    public function test_datatable_actions_visible_by_cola(): void
    {
        $editor = $this->editorUser();
        $fecha = '2026-09-06';
        $this->seedBothOriginsCarga($editor->id, $fecha);
        $this->createFicha('131313', 'Acciones Sin', EmployeeFichaProfile::STATUS_ACTIVO);

        $ausente = AcreditacionAcreditado::factory()->create([
            'document_number' => '141414',
            'full_name' => 'Acciones Ausente',
            'cargo_apo' => 'VIGILANTE',
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
        ]);

        $payload = $this->runAndGetPayload($editor, $fecha);
        $token = $payload['run_token'];

        $sin = $this->actingAs($editor)
            ->getJson(route('gestion-humana.acreditaciones.validaciones.datatable', [
                'fecha_reporte' => $fecha,
                'run_token' => $token,
                'cola' => AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION,
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]))
            ->assertOk()
            ->json('data');

        $this->assertNotEmpty($sin);
        $this->assertStringContainsString('js-validaciones-nuevo', (string) ($sin[0]['actions'] ?? ''));
        $this->assertStringNotContainsString('js-validaciones-edit', (string) ($sin[0]['actions'] ?? ''));

        $aus = $this->actingAs($editor)
            ->getJson(route('gestion-humana.acreditaciones.validaciones.datatable', [
                'fecha_reporte' => $fecha,
                'run_token' => $token,
                'cola' => AcreditacionValidacionesResultStore::COLA_AUSENTE_REPORTE,
                'draw' => 1,
                'start' => 0,
                'length' => 50,
            ]))
            ->assertOk()
            ->json('data');

        $ausRow = collect($aus)->first(
            fn (array $row): bool => str_contains((string) ($row['actions'] ?? ''), (string) $ausente->id)
                || str_contains(html_entity_decode((string) ($row['document_number'] ?? '')), '141414'),
        );

        $this->assertNotNull($ausRow);
        $this->assertStringContainsString('js-validaciones-edit', (string) ($ausRow['actions'] ?? ''));
        $this->assertStringNotContainsString('js-validaciones-nuevo', (string) ($ausRow['actions'] ?? ''));
    }

    public function test_abrir_ficha_hidden_without_ficha_manage_permission(): void
    {
        $editor = $this->editorUser();
        $fecha = '2026-09-05';
        $this->seedBothOriginsCarga($editor->id, $fecha);

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'hired_document' => '151515',
            'hired_full_name' => 'Con Ficha Entry',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $editor->id,
            'created_by' => $editor->id,
        ]);

        EmployeeFichaProfile::query()->create([
            'document_number' => '151515',
            'full_name' => 'Con Ficha Entry',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'personal_requisition_ficha_entry_id' => $entry->id,
        ]);

        $payload = $this->runAndGetPayload($editor, $fecha);
        $token = $payload['run_token'];

        $data = $this->actingAs($editor)
            ->getJson(route('gestion-humana.acreditaciones.validaciones.datatable', [
                'fecha_reporte' => $fecha,
                'run_token' => $token,
                'cola' => AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION,
                'draw' => 1,
                'start' => 0,
                'length' => 50,
            ]))
            ->assertOk()
            ->json('data');

        $row = collect($data)->first(
            fn (array $r): bool => str_contains(html_entity_decode((string) ($r['document_number'] ?? '')), '151515'),
        );

        $this->assertNotNull($row);
        $this->assertStringNotContainsString('Abrir Ficha', (string) ($row['actions'] ?? ''));
        $this->assertStringNotContainsString('/ficha-empleados/', (string) ($row['actions'] ?? ''));
        $this->assertStringContainsString('js-validaciones-nuevo', (string) ($row['actions'] ?? ''));

        $editor->givePermissionTo([
            'view.board.gestion_humana.ficha_empleados',
            'ficha_empleados.view',
            'ficha_empleados.manage',
        ]);

        $withPerm = $this->actingAs($editor)
            ->getJson(route('gestion-humana.acreditaciones.validaciones.datatable', [
                'fecha_reporte' => $fecha,
                'run_token' => $token,
                'cola' => AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION,
                'draw' => 2,
                'start' => 0,
                'length' => 50,
            ]))
            ->assertOk()
            ->json('data');

        $rowWith = collect($withPerm)->first(
            fn (array $r): bool => str_contains(html_entity_decode((string) ($r['document_number'] ?? '')), '151515'),
        );

        $this->assertNotNull($rowWith);
        $this->assertStringContainsString('Abrir Ficha', (string) ($rowWith['actions'] ?? ''));
        $this->assertStringContainsString('/ficha-empleados/', (string) ($rowWith['actions'] ?? ''));
    }

    public function test_future_fecha_on_run_is_rejected(): void
    {
        $editor = $this->editorUser();
        $future = Carbon::now(config('app.timezone'))->addDays(2)->toDateString();

        $this->actingAs($editor)
            ->from(route('gestion-humana.acreditaciones.validaciones'))
            ->post(route('gestion-humana.acreditaciones.validaciones.run'), [
                'fecha_reporte' => $future,
            ])
            ->assertSessionHasErrors('fecha_reporte');
    }

    public function test_pair_present_only_in_proceso_is_not_ausente(): void
    {
        $editor = $this->editorUser();
        $fecha = '2026-09-04';
        $carga = $this->seedBothOriginsCarga($editor->id, $fecha);

        AcreditacionReporteDiarioFila::factory()->proceso()->create([
            'carga_id' => $carga->id,
            'document_number' => '161616',
            'cargo' => 'ESCOLTA',
        ]);

        $presenteProceso = AcreditacionAcreditado::factory()->create([
            'document_number' => '161616',
            'full_name' => 'Solo En Proceso',
            'cargo_apo' => 'ESCOLTA',
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
        ]);

        $payload = $this->runAndGetPayload($editor, $fecha);
        $ids = collect($payload['colas'][AcreditacionValidacionesResultStore::COLA_AUSENTE_REPORTE])
            ->pluck('acreditado_id')
            ->all();

        $this->assertNotContains($presenteProceso->id, $ids);
    }

    public function test_normalization_diacritics_and_spaces_match(): void
    {
        $editor = $this->editorUser();
        $fecha = '2026-09-03';
        $carga = $this->seedBothOriginsCarga($editor->id, $fecha);

        AcreditacionReporteDiarioFila::factory()->proceso()->create([
            'carga_id' => $carga->id,
            'document_number' => '171717',
            'cargo' => '  vigilánte  ',
        ]);

        $acreditado = AcreditacionAcreditado::factory()->create([
            'document_number' => '171717',
            'full_name' => 'Con Tildes',
            'cargo_apo' => 'VIGILANTE',
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
        ]);

        $normalizer = app(AcreditacionCargoMatchNormalizer::class);
        $this->assertTrue($normalizer->cargosMatch('VIGILANTE', '  vigilánte  '));

        $payload = $this->runAndGetPayload($editor, $fecha);
        $ids = collect($payload['colas'][AcreditacionValidacionesResultStore::COLA_AUSENTE_REPORTE])
            ->pluck('acreditado_id')
            ->all();

        $this->assertNotContains($acreditado->id, $ids);
    }

    public function test_datatable_length_minus_one_is_capped_at_max(): void
    {
        $editor = $this->editorUser();
        $fecha = '2026-09-02';
        $maxLength = (int) config('acreditaciones.limits.datatable_max_length', 100);
        $store = app(AcreditacionValidacionesResultStore::class);

        $rows = [];
        for ($i = 0; $i < $maxLength + 25; $i++) {
            $rows[] = [
                'document_number' => (string) (300000 + $i),
                'full_name' => 'Cap Row '.$i,
                'ficha_url' => null,
            ];
        }

        $stored = $store->put($editor->id, $fecha, [
            'fecha_reporte' => $fecha,
            'counts' => [
                AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION => count($rows),
                AcreditacionValidacionesResultStore::COLA_AUSENTE_REPORTE => 0,
                AcreditacionValidacionesResultStore::COLA_EN_PROCESO_YA_ACREDITADO => 0,
                AcreditacionValidacionesResultStore::COLA_VENCIDAS => 0,
            ],
            'colas' => [
                AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION => $rows,
                AcreditacionValidacionesResultStore::COLA_AUSENTE_REPORTE => [],
                AcreditacionValidacionesResultStore::COLA_EN_PROCESO_YA_ACREDITADO => [],
                AcreditacionValidacionesResultStore::COLA_VENCIDAS => [],
            ],
        ]);

        $data = $this->actingAs($editor)
            ->getJson(route('gestion-humana.acreditaciones.validaciones.datatable', [
                'fecha_reporte' => $fecha,
                'run_token' => $stored['run_token'],
                'cola' => AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION,
                'draw' => 1,
                'start' => 0,
                'length' => -1,
            ]))
            ->assertOk()
            ->assertJsonPath('recordsTotal', count($rows))
            ->json('data');

        $this->assertCount($maxLength, $data);
    }

    public function test_shell_uses_server_side_dt_export_excel_and_no_select2(): void
    {
        $editor = $this->editorUser();
        $fecha = '2026-09-01';
        $this->seedBothOriginsCarga($editor->id, $fecha);
        $payload = $this->runAndGetPayload($editor, $fecha);

        $html = $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.validaciones', [
                'fecha_reporte' => $fecha,
                'run_token' => $payload['run_token'],
            ]))
            ->assertOk()
            ->assertSee('Ficha activa sin acreditación', false)
            ->assertSee('Acreditado ausente del reporte del día', false)
            ->assertSee('EN PROCESO en sistema / ACREDITADO en APO', false)
            ->assertSee('Vencidas / por vencer', false)
            ->assertSee('Cargo Ficha', false)
            ->assertSee('js-validaciones-cola-filters', false)
            ->assertSee('serverSide: true', false)
            ->assertSee('lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]]', false)
            ->assertDontSee('select2', false)
            ->assertDontSee('excelHtml5', false)
            ->assertSee('validaciones/exportar', false)
            ->assertSee('validaciones/exportar-consolidado', false)
            ->assertSee('Exportar Excel sin filtros (4 colas)', false)
            ->assertSee('Exportar Excel de esta cola con filtros', false)
            ->assertSee('js-validaciones-cola-export', false)
            ->getContent();

        $this->assertStringNotContainsString('lengthMenu: [[10, 25, 50, -1]', $html);
        $this->assertStringNotContainsString('&amp;amp;', $html);
        $this->assertMatchesRegularExpression(
            '/exportar-consolidado\?fecha_reporte=[^"&]+&amp;run_token=/',
            $html,
        );
        $this->assertTrue(class_exists(BaseExport::class));
        $this->assertTrue(method_exists(AcreditacionValidacionesExportService::class, 'exportCola'));
    }

    public function test_viewer_forbidden_on_datatable_and_export(): void
    {
        $viewer = $this->viewerUser();
        $fecha = '2026-08-31';

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.acreditaciones.validaciones.datatable', [
                'fecha_reporte' => $fecha,
                'run_token' => '00000000-0000-4000-8000-000000000000',
                'cola' => AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION,
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.acreditaciones.validaciones.export', [
                'fecha_reporte' => $fecha,
                'run_token' => '00000000-0000-4000-8000-000000000000',
                'cola' => AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION,
            ]))
            ->assertForbidden();
    }

    public function test_export_consolidated_has_four_sheets(): void
    {
        $editor = $this->editorUser();
        $fecha = '2026-08-30';
        $this->seedBothOriginsCarga($editor->id, $fecha);
        $payload = $this->runAndGetPayload($editor, $fecha);

        $response = $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.validaciones.export-consolidated', [
                'fecha_reporte' => $fecha,
                'run_token' => $payload['run_token'],
            ]));

        $response->assertOk();

        $temp = tempnam(sys_get_temp_dir(), 'valxlsx');
        $this->assertNotFalse($temp);
        file_put_contents($temp, $response->streamedContent());

        try {
            $spreadsheet = IOFactory::load($temp);
            $this->assertSame(4, $spreadsheet->getSheetCount());
        } finally {
            @unlink($temp);
        }
    }

    /**
     * @return array{
     *     run_token: string,
     *     fecha_reporte: string,
     *     user_id: int,
     *     created_at: string,
     *     counts: array<string, int>,
     *     colas: array<string, list<array<string, mixed>>>
     * }
     */
    private function runAndGetPayload(User $editor, string $fecha): array
    {
        $response = $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.validaciones.run'), [
                'fecha_reporte' => $fecha,
            ]);

        $response->assertRedirect();
        parse_str(parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY) ?: '', $query);
        $token = (string) ($query['run_token'] ?? '');
        $this->assertNotSame('', $token);

        $payload = app(AcreditacionValidacionesResultStore::class)->get($editor->id, $fecha, $token);
        $this->assertNotNull($payload);

        return $payload;
    }

    private function seedBothOriginsCarga(int $userId, string $fecha): AcreditacionReporteDiarioCarga
    {
        return AcreditacionReporteDiarioCarga::factory()
            ->withProceso($userId, 1)
            ->withAcreditado($userId, 1)
            ->create(['fecha_reporte' => $fecha]);
    }

    private function createFicha(
        string $documentNumber,
        string $fullName,
        string $employmentStatus = EmployeeFichaProfile::STATUS_ACTIVO,
        array $overrides = [],
    ): EmployeeFichaProfile {
        return EmployeeFichaProfile::query()->create(array_merge([
            'document_number' => $documentNumber,
            'full_name' => $fullName,
            'employment_status' => $employmentStatus,
        ], $overrides));
    }

    private function createFichaWithRequisitionArea(
        string $documentNumber,
        string $fullName,
        string $operatingAreaKey,
        string $positionName,
    ): EmployeeFichaProfile {
        $requisition = PersonalRequisition::query()->create([
            'code' => 'REQ-AV-'.uniqid(),
            'requested_by' => User::factory()->create(['must_change_password' => false])->id,
            'request_date' => now()->toDateString(),
            'leader_name' => 'Leader Test',
            'requesting_area_key' => 'gestion_humana',
            'position_id' => RequisitionPosition::query()->firstOrFail()->id,
            'sex' => 'masculino',
            'quantity' => 1,
            'operating_area_key' => $operatingAreaKey,
            'request_reason_id' => RequisitionRequestReason::query()->firstOrFail()->id,
            'client_id' => RequisitionClient::query()->firstOrFail()->id,
            'city_id' => RequisitionCity::query()->firstOrFail()->id,
            'client_type_id' => RequisitionClientType::query()->firstOrFail()->id,
            'programming_type_id' => RequisitionProgrammingType::query()->firstOrFail()->id,
            'uniform_id' => RequisitionUniform::query()->firstOrFail()->id,
            'required_profile' => 'Perfil de prueba.',
            'service_structure' => 'Turno de prueba.',
            'cost_center' => 'CC-AV',
            'status' => PersonalRequisition::STATUS_CONTRATADO,
            'status_changed_at' => now(),
        ]);

        $mover = User::factory()->create(['must_change_password' => false]);
        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => $documentNumber,
            'hired_full_name' => $fullName,
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $mover->id,
            'created_by' => $mover->id,
        ]);

        return EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => $documentNumber,
            'full_name' => $fullName,
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'position_name' => $positionName,
        ]);
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.acreditaciones',
            'acreditaciones.view',
        ]);

        return $user;
    }

    private function editorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.acreditaciones',
            'acreditaciones.view',
            'acreditaciones.edit',
        ]);

        return $user;
    }
}
