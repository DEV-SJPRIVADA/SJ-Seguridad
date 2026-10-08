<?php

namespace Tests\Feature\GestionHumana;

use App\Models\AuditLog;
use App\Models\EmployeeFichaProfile;
use App\Models\PayrollCatalogItem;
use App\Models\TerminationLetterDocumentTemplate;
use App\Models\User;
use App\Models\WordDocumentType;
use App\Services\GestionHumana\Letter\LetterVariableBuilder;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;
use ZipArchive;

class CartasNotificacionGenerateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        PermissionCatalog::sync();
        Storage::fake('local');
        Config::set('audit.enabled', true);
        Config::set('audit.queue', false);
    }

    public function test_board_only_user_gets_403_on_lookup_generate_and_import(): void
    {
        $user = $this->boardOnlyUser();

        $this->actingAs($user)
            ->postJson(route('gestion-humana.cartas-notificacion.lookup'), [
                'document_number' => '123',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson(route('gestion-humana.cartas-notificacion.generate'), [
                'rows' => [$this->validRow()],
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('gestion-humana.cartas-notificacion.import-template'))
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson(route('gestion-humana.cartas-notificacion.import-preview'), [
                'file' => UploadedFile::fake()->create('x.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            ])
            ->assertForbidden();
    }

    public function test_edit_user_lookup_active_inactive_not_found(): void
    {
        $editor = $this->editorUser();
        $this->createProfile('1098765432', 'Ana Activa', EmployeeFichaProfile::STATUS_ACTIVO);
        $this->createProfile('1098765433', 'Bruno Inactivo', EmployeeFichaProfile::STATUS_DESVINCULADO);

        $this->actingAs($editor)
            ->get(route('gestion-humana.cartas-notificacion.index'))
            ->assertOk()
            ->assertSee('Agregar varias cédulas', false)
            ->assertSee('Generar cartas', false)
            ->assertSee('Descargar plantilla Excel', false);

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.cartas-notificacion.lookup'), [
                'document_numbers' => ['1098765432', '1098765433', '9999999999'],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('results.0.status', 'activo')
            ->assertJsonPath('results.0.nombre_completo', 'Ana Activa')
            ->assertJsonPath('results.0.warning', null)
            ->assertJsonPath('results.1.status', 'inactivo')
            ->assertJsonPath('results.1.nombre_completo', 'Bruno Inactivo')
            ->assertJsonPath('results.2.status', 'no_encontrado')
            ->assertJsonPath('results.2.nombre_completo', '');
    }

    public function test_generate_validates_required_fields_and_duplicate_cedula(): void
    {
        $editor = $this->editorUser();
        $signatory = $this->seedSignatory();
        $this->seedExactlyOneTemplate();

        $base = $this->validRow($signatory->id);

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.cartas-notificacion.generate'), [
                'rows' => [[
                    ...$base,
                    'nombre_completo' => '',
                    'duracion_contrato' => 9,
                    'fecha_terminacion' => null,
                ]],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'rows.0.nombre_completo',
                'rows.0.duracion_contrato',
                'rows.0.fecha_terminacion',
            ]);

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.cartas-notificacion.generate'), [
                'rows' => [
                    $base,
                    [...$base, 'nombre_completo' => 'Otra persona'],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rows.1.cedula']);
    }

    public function test_generate_blocks_zero_or_multiple_templates(): void
    {
        $editor = $this->editorUser();
        $signatory = $this->seedSignatory();
        $row = $this->validRow($signatory->id);

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.cartas-notificacion.generate'), [
                'rows' => [$row],
            ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'No hay plantilla activa de Cartas Notificación. Cargue una en Plantillas Word.',
            ]);

        $this->seedTemplates(2);

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.cartas-notificacion.generate'), [
                'rows' => [$row],
            ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'Hay más de una plantilla activa de Cartas Notificación. Deje solo una activa.',
            ]);
    }

    public function test_generate_one_row_returns_docx_and_audits_without_massive_pii(): void
    {
        $editor = $this->editorUser();
        $signatory = $this->seedSignatory();
        $template = $this->seedExactlyOneTemplate(
            '${CEDULA} ${NOMBRE_COMPLETO} ${FECHA_TERMINACION} ${FIRMA} ${CARGO_FIRMA}'
        );

        $row = $this->validRow($signatory->id, '1099000111', 'Carla Notificación');

        $response = $this->actingAs($editor)
            ->post(route('gestion-humana.cartas-notificacion.generate'), [
                'rows' => [$row],
            ]);

        $response->assertOk();
        $response->assertDownload();
        $this->assertStringContainsString('.docx', (string) $response->headers->get('content-disposition'));

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'cartas_notificacion',
            'event_type' => 'cartas_notificacion_generate',
            'action' => 'generate',
        ]);

        $audit = AuditLog::query()
            ->where('event_type', 'cartas_notificacion_generate')
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);
        $metadata = $audit->metadata ?? [];
        $this->assertSame(1, $metadata['row_count'] ?? null);
        $this->assertSame('docx', $metadata['output_type'] ?? null);
        $this->assertSame($template->id, $metadata['template_id'] ?? null);
        $this->assertArrayNotHasKey('cedulas', $metadata);
        $this->assertStringNotContainsString('1099000111', json_encode($metadata) ?: '');
    }

    public function test_generate_multiple_rows_returns_zip(): void
    {
        $editor = $this->editorUser();
        $signatory = $this->seedSignatory();
        $this->seedExactlyOneTemplate('${CEDULA} ${NOMBRE_COMPLETO}');

        $rows = [
            $this->validRow($signatory->id, '2011111111', 'Persona Uno'),
            $this->validRow($signatory->id, '2022222222', 'Persona Dos'),
        ];

        $response = $this->actingAs($editor)
            ->post(route('gestion-humana.cartas-notificacion.generate'), [
                'rows' => $rows,
            ]);

        $response->assertOk();
        $response->assertDownload();
        $this->assertStringContainsString('.zip', (string) $response->headers->get('content-disposition'));

        $temp = tempnam(sys_get_temp_dir(), 'cn-zip-');
        $this->assertNotFalse($temp);
        file_put_contents($temp, $response->streamedContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($temp) === true);
        $this->assertSame(2, $zip->numFiles);
        $zip->close();
        @unlink($temp);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'cartas_notificacion',
            'event_type' => 'cartas_notificacion_generate',
            'action' => 'generate',
        ]);
    }

    public function test_build_for_cartas_notificacion_fecha_terminacion_long_es_and_firma(): void
    {
        $this->createProfile('1099007788', 'Laura Pérez', EmployeeFichaProfile::STATUS_ACTIVO, [
            'position_name' => 'Supervisora de seguridad',
            'residence_city_name' => 'Bucaramanga',
        ]);

        $builder = app(LetterVariableBuilder::class);
        $signatory = $this->seedSignatory();

        $variables = $builder->buildForCartasNotificacion([
            'cedula' => '1099007788',
            'nombre_completo' => 'Laura Pérez (manual)',
            'duracion_contrato' => 12,
            'fecha_terminacion' => '2026-06-15',
            'signatory_id' => $signatory->id,
        ]);

        $this->assertSame('1099007788', $variables['CEDULA']);
        $this->assertSame('1099007788', $variables['DOCUMENTO']);
        $this->assertSame('Laura Pérez (manual)', $variables['NOMBRE_COMPLETO']);
        $this->assertSame('12', $variables['DURACION_CONTRATO']);
        $this->assertSame('15 DE JUNIO DEL 2026', $variables['FECHA_TERMINACION']);
        $this->assertSame('Directora de GH', $variables['FIRMA']);
        $this->assertSame('DIR_GH', $variables['CARGO_FIRMA']);
        $this->assertSame('Supervisora de seguridad', $variables['CARGO']);
        $this->assertSame('Bucaramanga', $variables['CIUDAD']);
    }

    public function test_import_template_downloads_xlsx(): void
    {
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->get(route('gestion-humana.cartas-notificacion.import-template'))
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );
    }

    public function test_import_preview_hydrates_rows_without_persisting(): void
    {
        $editor = $this->editorUser();
        $signatory = $this->seedSignatory();
        $this->createProfile('3011111111', 'Excel Activo', EmployeeFichaProfile::STATUS_ACTIVO);

        $path = $this->makeImportXlsx([
            ['3011111111', '', '6', '2026-07-01', $signatory->name],
            ['3022222222', 'Manual Nombre', '12', '2026-08-01', 'NO_EXISTE'],
        ]);

        $profilesBefore = EmployeeFichaProfile::query()->count();

        $response = $this->actingAs($editor)
            ->post(route('gestion-humana.cartas-notificacion.import-preview'), [
                'file' => new UploadedFile($path, 'lote.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
            ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('rows.0.cedula', '3011111111')
            ->assertJsonPath('rows.0.nombre_completo', 'Excel Activo')
            ->assertJsonPath('rows.0.duracion_contrato', 6)
            ->assertJsonPath('rows.0.signatory_id', $signatory->id)
            ->assertJsonPath('rows.0.fecha_terminacion', '2026-07-01')
            ->assertJsonPath('rows.1.cedula', '3022222222')
            ->assertJsonPath('rows.1.nombre_completo', 'Manual Nombre')
            ->assertJsonPath('rows.1.duracion_contrato', 12)
            ->assertJsonPath('rows.1.signatory_id', null);

        $this->assertSame($profilesBefore, EmployeeFichaProfile::query()->count());
        @unlink($path);
    }

    /**
     * @return array<string, mixed>
     */
    private function validRow(?int $signatoryId = null, string $cedula = '1099000001', string $nombre = 'Empleado Notificación'): array
    {
        return [
            'cedula' => $cedula,
            'nombre_completo' => $nombre,
            'duracion_contrato' => 6,
            'fecha_terminacion' => '2026-06-15',
            'signatory_id' => $signatoryId ?? 1,
        ];
    }

    private function boardOnlyUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.cartas_notificacion',
        ]);

        return $user;
    }

    private function editorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.cartas_notificacion',
            'cartas_notificacion.edit',
        ]);

        return $user;
    }

    private function seedSignatory(): PayrollCatalogItem
    {
        return PayrollCatalogItem::query()->firstOrCreate(
            ['catalog_type' => 'firmas', 'code' => 'DIR_GH'],
            [
                'name' => 'Directora de GH',
                'sort_order' => 1,
                'is_active' => true,
            ],
        );
    }

    private function seedExactlyOneTemplate(string $content = '${CEDULA} ${NOMBRE_COMPLETO}'): TerminationLetterDocumentTemplate
    {
        return $this->seedTemplates(1, $content)[0];
    }

    /**
     * @return list<TerminationLetterDocumentTemplate>
     */
    private function seedTemplates(int $count, string $content = '${CEDULA} ${NOMBRE_COMPLETO}'): array
    {
        $type = WordDocumentType::query()->firstOrCreate(
            ['code' => 'cartas_notificacion'],
            [
                'name' => 'Cartas Notificación',
                'is_active' => true,
                'sort_order' => 4,
            ],
        );

        $templates = [];
        for ($i = 1; $i <= $count; $i++) {
            $path = 'ficha-empleados/letter-templates/'.$type->id.'/tpl-cn-'.$i.'.docx';
            Storage::disk('local')->put($path, $this->makeDocxBinary($content));

            $templates[] = TerminationLetterDocumentTemplate::query()->create([
                'word_document_type_id' => $type->id,
                'label' => 'Plantilla CN '.$i,
                'sort_order' => $i,
                'template_path' => $path,
            ]);
        }

        return $templates;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function createProfile(string $document, string $fullName, string $status, array $extra = []): EmployeeFichaProfile
    {
        return EmployeeFichaProfile::query()->create(array_merge([
            'document_number' => $document,
            'full_name' => $fullName,
            'employment_status' => $status,
            'hire_date' => now()->subYear()->toDateString(),
            'position_name' => 'Vigilante',
        ], $extra));
    }

    /**
     * @param  list<list<string>>  $dataRows
     */
    private function makeImportXlsx(array $dataRows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['CEDULA', 'NOMBRE_COMPLETO', 'DURACION_CONTRATO', 'FECHA_TERMINACION', 'FIRMA'], null, 'A1');
        $rowNum = 2;
        foreach ($dataRows as $row) {
            $sheet->fromArray($row, null, 'A'.$rowNum);
            $rowNum++;
        }

        $temp = tempnam(sys_get_temp_dir(), 'cn-xlsx-');
        $path = $temp.'.xlsx';
        @unlink($temp);
        (new Xlsx($spreadsheet))->save($path);

        return $path;
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
}
