<?php

namespace Tests\Feature\GestionHumana;

use App\Models\ReportesNovedadesIncapacidad;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportesNovedadesIncapacidadesTest extends TestCase
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

    public function test_incapacidades_crud_review_ownership_and_export(): void
    {
        $editor = $this->editorUser();
        $reviewer = $this->reviewerUser();

        $this->actingAs($editor)
            ->get(route('gestion-humana.reportes-novedades.incapacidades'))
            ->assertOk()
            ->assertSee('Incapacidades', false)
            ->assertSee('Extemporánea', false)
            ->assertSee('name="extemporanea"', false)
            ->assertDontSee('listado y formularios en preparación', false);

        $this->actingAs($editor)
            ->post(route('gestion-humana.reportes-novedades.incapacidades.store'), $this->payload([
                'document_number' => '5005005005',
                'employee_name' => 'Incap GH',
            ]))
            ->assertRedirect(route('gestion-humana.reportes-novedades.incapacidades'));

        $row = ReportesNovedadesIncapacidad::query()->where('document_number', '5005005005')->firstOrFail();
        $this->assertSame('EG', $row->tipo_incapacidad);
        $this->assertNull($row->observacion_nomina);
        $this->assertSame(
            (int) Carbon::parse('2026-09-01')->startOfDay()->diffInDays(now()->startOfDay(), false),
            $row->dias_entrega
        );

        $this->actingAs($editor)
            ->from(route('gestion-humana.reportes-novedades.incapacidades'))
            ->post(route('gestion-humana.reportes-novedades.incapacidades.store'), $this->payload([
                'tipo_incapacidad' => 'INVALIDO',
            ]))
            ->assertSessionHasErrors('tipo_incapacidad');

        $this->actingAs($editor)
            ->patch(route('gestion-humana.reportes-novedades.incapacidades.update', $row), array_merge(
                $this->payload([
                    'document_number' => '5005005005',
                    'employee_name' => 'Incap Editada',
                    'tipo_incapacidad' => 'ARL',
                    'fecha_envio_final' => '2026-09-05',
                ]),
                [
                    'observacion_nomina' => 'No debe',
                    'dias_entrega' => 9,
                ]
            ))
            ->assertRedirect();

        $row->refresh();
        $this->assertSame('Incap Editada', $row->employee_name);
        $this->assertSame('ARL', $row->tipo_incapacidad);
        $this->assertNull($row->observacion_nomina);
        $this->assertSame(4, $row->dias_entrega);

        $this->actingAs($reviewer)
            ->patch(route('gestion-humana.reportes-novedades.incapacidades.review', $row), [
                'observacion_nomina' => 'OK Nomina',
                'dias_entrega' => 2,
                'employee_name' => 'Hack',
                'tipo_incapacidad' => 'LM',
            ])
            ->assertRedirect();

        $row->refresh();
        $this->assertSame('OK Nomina', $row->observacion_nomina);
        $this->assertSame(4, $row->dias_entrega);
        $this->assertSame('Incap Editada', $row->employee_name);
        $this->assertSame('ARL', $row->tipo_incapacidad);

        $this->actingAs($reviewer)
            ->post(route('gestion-humana.reportes-novedades.incapacidades.store'), $this->payload())
            ->assertForbidden();

        $this->actingAs($reviewer)
            ->delete(route('gestion-humana.reportes-novedades.incapacidades.destroy', $row))
            ->assertForbidden();

        $this->actingAs($reviewer)
            ->get(route('gestion-humana.reportes-novedades.incapacidades.export'))
            ->assertOk();

        $this->actingAs($editor)
            ->getJson(route('gestion-humana.reportes-novedades.incapacidades.historial', ['id' => $row->id]))
            ->assertOk()
            ->assertJsonStructure(['data']);

        $this->actingAs($editor)
            ->delete(route('gestion-humana.reportes-novedades.incapacidades.destroy', $row))
            ->assertRedirect();

        $this->assertDatabaseMissing('reportes_novedades_incapacidades', ['id' => $row->id]);
    }

    public function test_incapacidades_adjuntos_upload_consult_and_delete_with_permissions(): void
    {
        Storage::fake('local');

        $editor = $this->editorUser();
        $reviewer = $this->reviewerUser();
        $viewer = $this->viewerUser();

        $this->actingAs($editor)
            ->post(route('gestion-humana.reportes-novedades.incapacidades.store'), $this->payload([
                'document_number' => '5005005006',
                'employee_name' => 'Incap Adjuntos',
            ]))
            ->assertRedirect();

        $row = ReportesNovedadesIncapacidad::query()->where('document_number', '5005005006')->firstOrFail();

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.reportes-novedades.incapacidades.attachments.store', $row), [
                'attachments' => [
                    UploadedFile::fake()->create('soporte.pdf', 120, 'application/pdf'),
                    UploadedFile::fake()->image('foto.jpg'),
                ],
            ])
            ->assertOk()
            ->assertJsonPath('can_edit', true)
            ->assertJsonCount(2, 'data');

        $pdf = $row->adjuntos()->where('original_name', 'soporte.pdf')->firstOrFail();
        $jpg = $row->adjuntos()->where('original_name', 'foto.jpg')->firstOrFail();
        $this->assertSame($editor->id, $pdf->uploaded_by);
        Storage::disk('local')->assertExists($pdf->stored_path);
        Storage::disk('local')->assertExists($jpg->stored_path);

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.reportes-novedades.incapacidades.attachments.store', $row), [
                'attachments' => [
                    UploadedFile::fake()->create('nota.txt', 10, 'text/plain'),
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('attachments.0');

        $this->actingAs($reviewer)
            ->postJson(route('gestion-humana.reportes-novedades.incapacidades.attachments.store', $row), [
                'attachments' => [UploadedFile::fake()->create('nomina.pdf', 20, 'application/pdf')],
            ])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->postJson(route('gestion-humana.reportes-novedades.incapacidades.attachments.store', $row), [
                'attachments' => [UploadedFile::fake()->create('consulta.pdf', 20, 'application/pdf')],
            ])
            ->assertForbidden();

        $this->actingAs($reviewer)
            ->getJson(route('gestion-humana.reportes-novedades.incapacidades.attachments.index', $row))
            ->assertOk()
            ->assertJsonPath('can_edit', false)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.delete_url', null)
            ->assertJsonPath('data.0.preview_url', route('gestion-humana.reportes-novedades.incapacidades.attachments.preview', [$row, $pdf]));

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.reportes-novedades.incapacidades.attachments.index', $row))
            ->assertOk()
            ->assertJsonPath('can_edit', false);

        $this->actingAs($reviewer)
            ->get(route('gestion-humana.reportes-novedades.incapacidades.attachments.download', [$row, $pdf]))
            ->assertOk()
            ->assertDownload('soporte.pdf');

        $this->actingAs($viewer)
            ->get(route('gestion-humana.reportes-novedades.incapacidades.attachments.download', [$row, $jpg]))
            ->assertOk()
            ->assertDownload('foto.jpg');

        $previewPdf = $this->actingAs($reviewer)
            ->get(route('gestion-humana.reportes-novedades.incapacidades.attachments.preview', [$row, $pdf]));
        $previewPdf->assertOk();
        $this->assertStringContainsString('inline', (string) $previewPdf->headers->get('content-disposition'));

        $previewJpg = $this->actingAs($viewer)
            ->get(route('gestion-humana.reportes-novedades.incapacidades.attachments.preview', [$row, $jpg]));
        $previewJpg->assertOk();
        $this->assertStringContainsString('inline', (string) $previewJpg->headers->get('content-disposition'));

        $this->actingAs($reviewer)
            ->deleteJson(route('gestion-humana.reportes-novedades.incapacidades.attachments.destroy', [$row, $pdf]))
            ->assertForbidden();

        $this->actingAs($editor)
            ->post(route('gestion-humana.reportes-novedades.incapacidades.store'), $this->payload([
                'document_number' => '5005005007',
                'employee_name' => 'Otra Incap',
            ]))
            ->assertRedirect();

        $other = ReportesNovedadesIncapacidad::query()->where('document_number', '5005005007')->firstOrFail();

        $this->actingAs($editor)
            ->get(route('gestion-humana.reportes-novedades.incapacidades.attachments.download', [$other, $pdf]))
            ->assertNotFound();

        $this->actingAs($editor)
            ->get(route('gestion-humana.reportes-novedades.incapacidades.attachments.preview', [$other, $pdf]))
            ->assertNotFound();

        $this->actingAs($editor)
            ->deleteJson(route('gestion-humana.reportes-novedades.incapacidades.attachments.destroy', [$row, $pdf]))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertDatabaseMissing('reportes_novedades_incapacidad_adjuntos', ['id' => $pdf->id]);
        Storage::disk('local')->assertMissing($pdf->stored_path);

        $jpgPath = $jpg->stored_path;

        $this->actingAs($editor)
            ->delete(route('gestion-humana.reportes-novedades.incapacidades.destroy', $row))
            ->assertRedirect();

        $this->assertDatabaseMissing('reportes_novedades_incapacidad_adjuntos', ['id' => $jpg->id]);
        Storage::disk('local')->assertMissing($jpgPath);
    }

    public function test_incapacidades_datatable_filters_by_extemporanea(): void
    {
        $editor = $this->editorUser();

        ReportesNovedadesIncapacidad::query()->create([
            'document_number' => '6116116116',
            'employee_name' => 'NO EXTEMP',
            'tipo_incapacidad' => 'EG',
            'dias' => 3,
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2026-09-03',
            'extemporanea' => false,
            'created_by' => $editor->id,
            'updated_by' => $editor->id,
        ]);
        ReportesNovedadesIncapacidad::query()->create([
            'document_number' => '6226226226',
            'employee_name' => 'SI EXTEMP',
            'tipo_incapacidad' => 'EG',
            'dias' => 3,
            'fecha_inicio' => '2026-09-10',
            'fecha_fin' => '2026-09-12',
            'extemporanea' => true,
            'created_by' => $editor->id,
            'updated_by' => $editor->id,
        ]);

        $range = [
            'fecha_desde' => '2026-09-01',
            'fecha_hasta' => '2026-09-30',
        ];

        $all = $this->actingAs($editor)
            ->getJson(route('gestion-humana.reportes-novedades.incapacidades.datatable', $range))
            ->assertOk();
        $this->assertSame(2, $all->json('recordsFiltered'));

        $onlyYes = $this->actingAs($editor)
            ->getJson(route('gestion-humana.reportes-novedades.incapacidades.datatable', array_merge($range, [
                'extemporanea' => '1',
            ])))
            ->assertOk();
        $this->assertSame(1, $onlyYes->json('recordsFiltered'));
        $this->assertStringContainsString('SI EXTEMP', (string) $onlyYes->json('data.0.1'));
        $this->assertStringContainsString('Sí', (string) $onlyYes->json('data.0.10'));

        $onlyNo = $this->actingAs($editor)
            ->getJson(route('gestion-humana.reportes-novedades.incapacidades.datatable', array_merge($range, [
                'extemporanea' => '0',
            ])))
            ->assertOk();
        $this->assertSame(1, $onlyNo->json('recordsFiltered'));
        $this->assertStringContainsString('NO EXTEMP', (string) $onlyNo->json('data.0.1'));

        $this->actingAs($editor)
            ->get(route('gestion-humana.reportes-novedades.incapacidades', array_merge($range, [
                'extemporanea' => '1',
            ])))
            ->assertOk()
            ->assertSee('extemporanea=1', false)
            ->assertSee('Activos', false);
    }

    private function editorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('reportes_novedades.incapacidades.edit');

        return $user;
    }

    private function reviewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('reportes_novedades.incapacidades.review');

        return $user;
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('reportes_novedades.incapacidades.view');

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'document_number' => '5050505050',
            'employee_name' => 'Empleado Incapacidad',
            'cargo' => 'Guardia',
            'destino' => 'Cliente B',
            'tipo_incapacidad' => 'EG',
            'dias' => 3,
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2026-09-03',
            'fecha_recepcion' => '2026-09-01',
            'extemporanea' => '0',
            'observaciones' => 'Obs',
        ], $overrides);
    }
}
