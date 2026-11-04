<?php

namespace Tests\Feature\GestionHumana;

use App\Models\AuditLog;
use App\Models\PayrollCatalogItem;
use App\Models\ReportesNovedadesPermiso;
use App\Models\ReportesNovedadesVacacion;
use App\Models\TerminationLetterDocumentTemplate;
use App\Models\User;
use App\Models\WordDocumentType;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

class ReportesNovedadesVacacionesPermisosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
        Config::set('audit.enabled', true);
        Config::set('audit.queue', false);
    }

    public function test_edit_implies_view_for_vacaciones_datatable(): void
    {
        $editor = $this->editorUser('vacaciones');

        $this->actingAs($editor)
            ->getJson(route('gestion-humana.reportes-novedades.vacaciones.datatable'))
            ->assertOk()
            ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    }

    public function test_vacaciones_datatable_filters_by_mes_quincena_on_fecha_inicio(): void
    {
        $editor = $this->editorUser('vacaciones');

        ReportesNovedadesVacacion::query()->create($this->vacacionAttributes([
            'document_number' => '1111111111',
            'employee_name' => 'Q1',
            'fecha_inicio' => '2026-09-10',
            'created_by' => $editor->id,
            'updated_by' => $editor->id,
        ]));
        ReportesNovedadesVacacion::query()->create($this->vacacionAttributes([
            'document_number' => '2222222222',
            'employee_name' => 'Q2',
            'fecha_inicio' => '2026-09-20',
            'created_by' => $editor->id,
            'updated_by' => $editor->id,
        ]));
        ReportesNovedadesVacacion::query()->create($this->vacacionAttributes([
            'document_number' => '3333333333',
            'employee_name' => 'OtroMes',
            'fecha_inicio' => '2026-08-05',
            'created_by' => $editor->id,
            'updated_by' => $editor->id,
        ]));

        $q1 = $this->actingAs($editor)
            ->getJson(route('gestion-humana.reportes-novedades.vacaciones.datatable', [
                'mes' => '2026-09',
                'quincena' => '1',
            ]))
            ->assertOk();
        $this->assertSame(1, $q1->json('recordsFiltered'));
        $this->assertStringContainsString('Q1', $q1->json('data.0.1'));

        $range = $this->actingAs($editor)
            ->getJson(route('gestion-humana.reportes-novedades.vacaciones.datatable', [
                'fecha_desde' => '2026-09-15',
                'mes' => '2026-09',
                'quincena' => '1',
            ]))
            ->assertOk();
        $this->assertSame(1, $range->json('recordsFiltered'));
        $this->assertStringContainsString('Q2', $range->json('data.0.1'));
    }

    public function test_vacaciones_store_persists_carta_fields_in_uppercase(): void
    {
        $editor = $this->editorUser('vacaciones');
        $signatory = $this->seedSignatory();

        $this->actingAs($editor)
            ->post(route('gestion-humana.reportes-novedades.vacaciones.store'), $this->vacacionPayload([
                'periodos' => '2024-2025',
                'fecha_reintegro' => '2026-09-06',
                'signatory_id' => $signatory->id,
            ]))
            ->assertRedirect(route('gestion-humana.reportes-novedades.vacaciones'));

        $row = ReportesNovedadesVacacion::query()->where('document_number', '1010101010')->firstOrFail();
        $this->assertSame('2026-09-06', $row->fecha_reintegro?->format('Y-m-d'));
        $this->assertSame('2024-2025', $row->periodos);
        $this->assertSame($signatory->id, $row->signatory_id);
    }

    public function test_vacaciones_download_carta_word_requires_edit(): void
    {
        $reviewer = $this->reviewerUser('vacaciones');
        $row = ReportesNovedadesVacacion::query()->create($this->vacacionAttributes([
            'fecha_reintegro' => '2026-09-06',
            'periodos' => '2024-2025',
            'signatory_id' => $this->seedSignatory()->id,
        ]));

        $this->actingAs($reviewer)
            ->get(route('gestion-humana.reportes-novedades.vacaciones.carta-word', $row))
            ->assertForbidden();
    }

    public function test_vacaciones_download_carta_word_returns_docx(): void
    {
        Storage::fake('local');
        $this->seedExactlyOneCartaTemplate();
        $signatory = $this->seedSignatory();
        $editor = $this->editorUser('vacaciones');

        $row = ReportesNovedadesVacacion::query()->create($this->vacacionAttributes([
            'fecha_reintegro' => '2026-09-06',
            'periodos' => '2024-2025',
            'signatory_id' => $signatory->id,
            'created_by' => $editor->id,
            'updated_by' => $editor->id,
        ]));

        $this->actingAs($editor)
            ->get(route('gestion-humana.reportes-novedades.vacaciones.carta-word', $row))
            ->assertOk()
            ->assertDownload();
    }

    public function test_vacaciones_create_modal_suggests_fecha_fin_from_business_days(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-30'));
        $editor = $this->editorUser('vacaciones');

        $this->actingAs($editor)
            ->get(route('gestion-humana.reportes-novedades.vacaciones'))
            ->assertOk()
            ->assertViewHas('holidayDates', function ($dates): bool {
                return is_array($dates)
                    && in_array('2026-01-01', $dates, true)
                    && in_array('2026-12-25', $dates, true);
            })
            ->assertSee('holidayDates', false)
            ->assertSee('syncFechaFin', false)
            ->assertSee('x-on:input="syncFechaFin()"', false)
            ->assertSee('x-on:change="syncFechaFin()"', false)
            ->assertSee('Sugerida: inicio + días (sin domingos ni festivos). Puede modificarla.', false);

        Carbon::setTestNow();
    }

    public function test_vacaciones_index_defaults_mes_and_quincena(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-30'));
        $editor = $this->editorUser('vacaciones');

        $this->actingAs($editor)
            ->get(route('gestion-humana.reportes-novedades.vacaciones'))
            ->assertOk()
            ->assertViewHas('filters', function (array $filters): bool {
                return $filters['mes'] === '2026-09'
                    && $filters['quincena'] === '2'
                    && $filters['period_active'] === true
                    && $filters['fecha_desde'] === '';
            })
            ->assertSee('name="mes"', false)
            ->assertSee('type="month"', false);

        Carbon::setTestNow();
    }

    public function test_review_can_view_and_export_but_cannot_store_vacaciones(): void
    {
        $reviewer = $this->reviewerUser('vacaciones');

        $this->actingAs($reviewer)
            ->get(route('gestion-humana.reportes-novedades.vacaciones'))
            ->assertOk();

        $this->actingAs($reviewer)
            ->get(route('gestion-humana.reportes-novedades.vacaciones.export'))
            ->assertOk();

        $this->actingAs($reviewer)
            ->post(route('gestion-humana.reportes-novedades.vacaciones.store'), $this->vacacionPayload())
            ->assertForbidden();
    }

    public function test_vacaciones_crud_store_update_destroy(): void
    {
        $editor = $this->editorUser('vacaciones');

        $this->actingAs($editor)
            ->post(route('gestion-humana.reportes-novedades.vacaciones.store'), $this->vacacionPayload([
                'document_number' => '1001001001',
                'employee_name' => 'Ana Vacaciones',
            ]))
            ->assertRedirect(route('gestion-humana.reportes-novedades.vacaciones'));

        $row = ReportesNovedadesVacacion::query()->where('document_number', '1001001001')->first();
        $this->assertNotNull($row);
        $this->assertSame('VACACIONES DISF', $row->novedad);
        $this->assertSame('2026-09-01', optional($row->fecha_inicio)?->format('Y-m-d'));
        $this->assertNull($row->observacion_nomina);

        $this->actingAs($editor)
            ->patch(route('gestion-humana.reportes-novedades.vacaciones.update', $row), $this->vacacionPayload([
                'document_number' => '1001001001',
                'employee_name' => 'Ana Vacaciones Editada',
                'novedad' => 'VACACIONES COMP',
                'dias_novedad' => 10,
            ]))
            ->assertRedirect();

        $row->refresh();
        $this->assertSame('ANA VACACIONES EDITADA', $row->employee_name);
        $this->assertSame('VACACIONES COMP', $row->novedad);
        $this->assertSame(10, $row->dias_novedad);

        $this->actingAs($editor)
            ->delete(route('gestion-humana.reportes-novedades.vacaciones.destroy', $row))
            ->assertRedirect(route('gestion-humana.reportes-novedades.vacaciones'));

        $this->assertDatabaseMissing('reportes_novedades_vacaciones', ['id' => $row->id]);
    }

    public function test_gh_update_does_not_persist_observacion_nomina(): void
    {
        $editor = $this->editorUser('vacaciones');
        $row = ReportesNovedadesVacacion::query()->create($this->vacacionAttributes([
            'observacion_nomina' => 'OK previo',
            'created_by' => $editor->id,
            'updated_by' => $editor->id,
        ]));

        $this->actingAs($editor)
            ->patch(route('gestion-humana.reportes-novedades.vacaciones.update', $row), array_merge(
                $this->vacacionPayload(['employee_name' => 'Sin tocar nomina']),
                ['observacion_nomina' => 'Intento GH']
            ))
            ->assertRedirect();

        $row->refresh();
        $this->assertSame('SIN TOCAR NOMINA', $row->employee_name);
        $this->assertSame('OK previo', $row->observacion_nomina);
    }

    public function test_review_persists_only_observacion_nomina_and_ignores_gh_fields(): void
    {
        $reviewer = $this->reviewerUser('vacaciones');
        $row = ReportesNovedadesVacacion::query()->create($this->vacacionAttributes([
            'employee_name' => 'Original GH',
            'observacion_nomina' => null,
        ]));

        $this->actingAs($reviewer)
            ->patch(route('gestion-humana.reportes-novedades.vacaciones.review', $row), [
                'observacion_nomina' => 'OK Nomina',
                'employee_name' => 'Hackeado',
                'novedad' => 'VACACIONES COMP',
            ])
            ->assertRedirect();

        $row->refresh();
        $this->assertSame('OK Nomina', $row->observacion_nomina);
        $this->assertSame('Original GH', $row->employee_name);
        $this->assertSame('VACACIONES DISF', $row->novedad);
    }

    public function test_review_cannot_destroy_vacaciones(): void
    {
        $reviewer = $this->reviewerUser('vacaciones');
        $row = ReportesNovedadesVacacion::query()->create($this->vacacionAttributes());

        $this->actingAs($reviewer)
            ->delete(route('gestion-humana.reportes-novedades.vacaciones.destroy', $row))
            ->assertForbidden();

        $this->assertDatabaseHas('reportes_novedades_vacaciones', ['id' => $row->id]);
    }

    public function test_invalid_vacaciones_novedad_returns_validation_error(): void
    {
        $editor = $this->editorUser('vacaciones');

        $this->actingAs($editor)
            ->from(route('gestion-humana.reportes-novedades.vacaciones'))
            ->post(route('gestion-humana.reportes-novedades.vacaciones.store'), $this->vacacionPayload([
                'novedad' => 'NOVEDAD INVALIDA',
            ]))
            ->assertRedirect(route('gestion-humana.reportes-novedades.vacaciones'))
            ->assertSessionHasErrors('novedad');

        $this->assertSame(0, ReportesNovedadesVacacion::query()->count());
    }

    public function test_vacaciones_export_ok_with_view_and_review_forbidden_without(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->givePermissionTo('reportes_novedades.vacaciones.view');

        $reviewer = $this->reviewerUser('vacaciones');
        $stranger = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.reportes-novedades.vacaciones.export'))
            ->assertOk();

        $this->actingAs($reviewer)
            ->get(route('gestion-humana.reportes-novedades.vacaciones.export'))
            ->assertOk();

        $this->actingAs($stranger)
            ->get(route('gestion-humana.reportes-novedades.vacaciones.export'))
            ->assertForbidden();
    }

    public function test_vacaciones_historial_returns_events_after_create(): void
    {
        $editor = $this->editorUser('vacaciones');

        $this->actingAs($editor)
            ->post(route('gestion-humana.reportes-novedades.vacaciones.store'), $this->vacacionPayload([
                'document_number' => '2002002002',
            ]))
            ->assertRedirect();

        $row = ReportesNovedadesVacacion::query()->where('document_number', '2002002002')->firstOrFail();

        $this->assertTrue(
            AuditLog::query()
                ->where('module', 'reportes_novedades')
                ->where('event_type', 'vacaciones_novedad')
                ->where('action', 'create')
                ->exists()
        );

        $this->actingAs($editor)
            ->getJson(route('gestion-humana.reportes-novedades.vacaciones.historial', ['id' => $row->id]))
            ->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJsonFragment(['action' => 'create']);
    }

    public function test_vacaciones_sheet_historial_supports_filters(): void
    {
        $editor = $this->editorUser('vacaciones');

        $this->actingAs($editor)
            ->post(route('gestion-humana.reportes-novedades.vacaciones.store'), $this->vacacionPayload([
                'document_number' => '2112112112',
            ]))
            ->assertRedirect();

        $this->actingAs($editor)
            ->post(route('gestion-humana.reportes-novedades.vacaciones.store'), $this->vacacionPayload([
                'document_number' => '3113113113',
            ]))
            ->assertRedirect();

        $matched = $this->actingAs($editor)
            ->getJson(route('gestion-humana.reportes-novedades.vacaciones.historial', [
                'q' => '2112112112',
                'action' => 'create',
            ]))
            ->assertOk()
            ->json('data');

        $this->assertNotEmpty($matched);
        $this->assertTrue(collect($matched)->every(
            fn (array $item): bool => ($item['action'] ?? null) === 'create'
                && str_contains((string) ($item['summary'] ?? ''), '2112112112')
        ));

        $filteredOut = $this->actingAs($editor)
            ->getJson(route('gestion-humana.reportes-novedades.vacaciones.historial', [
                'q' => '2112112112',
                'action' => 'delete',
            ]))
            ->assertOk()
            ->json('data');

        $this->assertSame([], $filteredOut);
    }

    public function test_permisos_smoke_crud_review_export_and_invalid_catalog(): void
    {
        $editor = $this->editorUser('permisos');
        $reviewer = $this->reviewerUser('permisos');

        $this->actingAs($editor)
            ->post(route('gestion-humana.reportes-novedades.permisos.store'), $this->permisoPayload([
                'document_number' => '3003003003',
                'employee_name' => 'Pedro Permiso',
            ]))
            ->assertRedirect(route('gestion-humana.reportes-novedades.permisos'));

        $row = ReportesNovedadesPermiso::query()->where('document_number', '3003003003')->firstOrFail();
        $this->assertFalse((bool) $row->marca_gh);

        $this->actingAs($editor)
            ->from(route('gestion-humana.reportes-novedades.permisos'))
            ->post(route('gestion-humana.reportes-novedades.permisos.store'), $this->permisoPayload([
                'novedad' => 'INVALIDO',
            ]))
            ->assertSessionHasErrors('novedad');

        $this->actingAs($reviewer)
            ->patch(route('gestion-humana.reportes-novedades.permisos.review', $row), [
                'observacion_nomina' => 'Revisado',
                'employee_name' => 'No debe cambiar',
            ])
            ->assertRedirect();

        $row->refresh();
        $this->assertSame('Revisado', $row->observacion_nomina);
        $this->assertSame('Pedro Permiso', $row->employee_name);

        $this->actingAs($reviewer)
            ->get(route('gestion-humana.reportes-novedades.permisos.export'))
            ->assertOk();

        $this->actingAs($reviewer)
            ->post(route('gestion-humana.reportes-novedades.permisos.store'), $this->permisoPayload())
            ->assertForbidden();

        $this->actingAs($editor)
            ->patch(route('gestion-humana.reportes-novedades.permisos.update', $row), array_merge(
                $this->permisoPayload([
                    'document_number' => '3003003003',
                    'employee_name' => 'Pedro Actualizado',
                    'marca_gh' => '1',
                ]),
                ['observacion_nomina' => 'Ignorar']
            ))
            ->assertRedirect();

        $row->refresh();
        $this->assertSame('Pedro Actualizado', $row->employee_name);
        $this->assertTrue((bool) $row->marca_gh);
        $this->assertSame('Revisado', $row->observacion_nomina);

        $this->actingAs($editor)
            ->delete(route('gestion-humana.reportes-novedades.permisos.destroy', $row))
            ->assertRedirect();

        $this->assertDatabaseMissing('reportes_novedades_permisos', ['id' => $row->id]);
    }

    private function editorUser(string $sheet): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo("reportes_novedades.{$sheet}.edit");

        return $user;
    }

    private function reviewerUser(string $sheet): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo("reportes_novedades.{$sheet}.review");

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function vacacionPayload(array $overrides = []): array
    {
        $signatoryId = $overrides['signatory_id'] ?? $this->seedSignatory()->id;

        return array_merge([
            'document_number' => '1010101010',
            'employee_name' => 'Empleado Vacacion',
            'cargo' => 'Guardia',
            'destino' => 'Cliente A',
            'novedad' => 'VACACIONES DISF',
            'dias_novedad' => 5,
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2026-09-05',
            'fecha_reintegro' => '2026-09-06',
            'periodos' => '2024-2025',
            'signatory_id' => $signatoryId,
            'observaciones' => 'Obs GH',
        ], $overrides);
    }

    private function seedSignatory(): PayrollCatalogItem
    {
        return PayrollCatalogItem::query()->firstOrCreate(
            ['catalog_type' => 'firmas', 'code' => 'DIR_GH_RN'],
            [
                'name' => 'Directora GH RN',
                'sort_order' => 1,
                'is_active' => true,
            ],
        );
    }

    private function seedExactlyOneCartaTemplate(string $content = '${CEDULA} ${NOMBRE_COMPLETO}'): TerminationLetterDocumentTemplate
    {
        $type = WordDocumentType::query()->firstOrCreate(
            ['code' => 'cartas_vacaciones'],
            [
                'name' => 'Cartas Vacaciones',
                'is_active' => true,
                'sort_order' => 3,
            ],
        );

        $path = 'ficha-empleados/letter-templates/'.$type->id.'/tpl-cv-rn.docx';
        Storage::disk('local')->put($path, $this->makeDocxBinary($content));

        return TerminationLetterDocumentTemplate::query()->create([
            'word_document_type_id' => $type->id,
            'label' => 'Plantilla CV RN',
            'sort_order' => 1,
            'template_path' => $path,
        ]);
    }

    private function makeDocxBinary(string $content): string
    {
        $temp = tempnam(sys_get_temp_dir(), 'letter-bin-');
        $path = $temp.'.docx';
        @unlink($temp);

        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $section->addText($content);
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($path);

        $binary = (string) file_get_contents($path);
        @unlink($path);

        return $binary;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function vacacionAttributes(array $overrides = []): array
    {
        return $this->vacacionPayload($overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function permisoPayload(array $overrides = []): array
    {
        return array_merge([
            'document_number' => '4040404040',
            'employee_name' => 'Empleado Permiso',
            'tipo' => 'Operativo',
            'cargo' => 'Supervisor',
            'novedad' => 'LUTO',
            'dias_novedad' => 3,
            'fecha_inicio' => '2026-09-10',
            'fecha_fin' => '2026-09-12',
            'marca_gh' => '0',
        ], $overrides);
    }
}
