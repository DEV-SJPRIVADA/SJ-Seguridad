<?php

namespace Tests\Feature\GestionHumana;

use App\Models\AuditLog;
use App\Models\EmployeeFichaProfile;
use App\Models\InasistenciaCarta;
use App\Models\PayrollCatalogItem;
use App\Models\TerminationLetterDocumentTemplate;
use App\Models\User;
use App\Models\WordDocumentType;
use App\Services\GestionHumana\Letter\LetterVariableBuilder;
use App\Support\PermissionCatalog;
use Carbon\Carbon;
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

class InasistenciasGenerateTest extends TestCase
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
        Carbon::setTestNow(Carbon::parse('2026-10-14'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_board_only_user_gets_403_on_lookup_generate_and_import(): void
    {
        $user = $this->boardOnlyUser();

        $this->actingAs($user)
            ->postJson(route('gestion-humana.inasistencias.lookup'), [
                'document_number' => '123',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson(route('gestion-humana.inasistencias.generate'), [
                'rows' => [$this->validRow()],
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('gestion-humana.inasistencias.import-template'))
            ->assertForbidden();
    }

    public function test_edit_user_lookup_returns_nombre_cargo_ciudad(): void
    {
        $editor = $this->editorUser();
        $this->createProfile('1098765432', 'Ana Activa', EmployeeFichaProfile::STATUS_ACTIVO, [
            'position_name' => 'Guarda',
            'work_city_name' => 'Cali',
            'residence_city_name' => 'Palmira',
        ]);

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.inasistencias.lookup'), [
                'document_numbers' => ['1098765432', '9999999999'],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('results.0.status', 'activo')
            ->assertJsonPath('results.0.nombre_completo', 'Ana Activa')
            ->assertJsonPath('results.0.cargo', 'Guarda')
            ->assertJsonPath('results.0.ciudad', 'Cali')
            ->assertJsonPath('results.1.status', 'no_encontrado');
    }

    public function test_generate_validates_required_fields_and_duplicate_cedula(): void
    {
        $editor = $this->editorUser();
        $signatory = $this->seedSignatory();
        $this->seedExactlyOneTemplate();

        $base = $this->validRow($signatory->id);

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.inasistencias.generate'), [
                'rows' => [[
                    ...$base,
                    'nombre_completo' => '',
                    'fecha_ini_aus' => null,
                ]],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'rows.0.nombre_completo',
                'rows.0.fecha_ini_aus',
            ]);

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.inasistencias.generate'), [
                'rows' => [
                    $base,
                    [...$base, 'nombre_completo' => 'Otra persona'],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rows.1.cedula']);
    }

    public function test_generate_one_row_returns_docx_persists_historico_without_file_and_audits(): void
    {
        $editor = $this->editorUser();
        $signatory = $this->seedSignatory();
        $template = $this->seedExactlyOneTemplate(
            '${CEDULA} ${NOMBRE} ${CARGO} ${CIUDAD} ${FECHA_INI_AUS} ${DIAS} ${FIRMA} ${CARGO_FIRMA}'
        );

        $row = $this->validRow($signatory->id, '1099000111', 'Carla Inasistencia');

        $response = $this->actingAs($editor)
            ->post(route('gestion-humana.inasistencias.generate'), [
                'rows' => [$row],
            ]);

        $response->assertOk();
        $response->assertDownload();
        $this->assertStringContainsString('.docx', (string) $response->headers->get('content-disposition'));

        $this->assertDatabaseHas('inasistencia_cartas', [
            'document_number' => '1099000111',
            'full_name' => 'Carla Inasistencia',
            'dias' => 14,
            'firma_name' => 'Directora de GH',
            'cargo_firma' => 'DIR_GH',
            'word_template_id' => $template->id,
            'generated_by' => $editor->id,
        ]);

        $stored = InasistenciaCarta::query()->where('document_number', '1099000111')->first();
        $this->assertNotNull($stored);
        $this->assertSame('2026-10-01', optional($stored->fecha_ini_aus)?->toDateString());
        $this->assertSame(1, InasistenciaCarta::query()->count());

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'inasistencias',
            'event_type' => 'inasistencias_generate',
            'action' => 'generate',
        ]);

        $audit = AuditLog::query()
            ->where('event_type', 'inasistencias_generate')
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);
        $metadata = $audit->metadata ?? [];
        $this->assertSame(1, $metadata['row_count'] ?? null);
        $this->assertSame('docx', $metadata['output_type'] ?? null);
        $this->assertArrayNotHasKey('cedulas', $metadata);
    }

    public function test_generate_multiple_rows_returns_zip_and_persists_all(): void
    {
        $editor = $this->editorUser();
        $signatory = $this->seedSignatory();
        $this->seedExactlyOneTemplate('${CEDULA} ${NOMBRE}');

        $rows = [
            $this->validRow($signatory->id, '2011111111', 'Persona Uno'),
            $this->validRow($signatory->id, '2022222222', 'Persona Dos'),
        ];

        $response = $this->actingAs($editor)
            ->post(route('gestion-humana.inasistencias.generate'), [
                'rows' => $rows,
            ]);

        $response->assertOk();
        $response->assertDownload();
        $this->assertStringContainsString('.zip', (string) $response->headers->get('content-disposition'));

        $temp = tempnam(sys_get_temp_dir(), 'ina-zip-');
        $this->assertNotFalse($temp);
        file_put_contents($temp, $response->streamedContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($temp) === true);
        $this->assertSame(2, $zip->numFiles);
        $zip->close();
        @unlink($temp);

        $this->assertSame(2, InasistenciaCarta::query()->count());
    }

    public function test_build_for_inasistencias_exposes_all_business_variables(): void
    {
        $this->createProfile('1099007788', 'Laura Pérez', EmployeeFichaProfile::STATUS_ACTIVO, [
            'position_name' => 'Supervisora de seguridad',
            'residence_city_name' => 'Bucaramanga',
        ]);

        $builder = app(LetterVariableBuilder::class);
        $signatory = $this->seedSignatory();

        $variables = $builder->buildForInasistencias([
            'cedula' => '1099007788',
            'nombre_completo' => 'Laura Pérez',
            'cargo' => 'Supervisora de seguridad',
            'ciudad' => 'Bucaramanga',
            'fecha_ini_aus' => '2026-10-01',
            'dias' => 14,
            'signatory_id' => $signatory->id,
        ]);

        $this->assertSame('1099007788', $variables['CEDULA']);
        $this->assertSame('Laura Pérez', $variables['NOMBRE']);
        $this->assertSame('Laura Pérez', $variables['NOMBRE_COMPLETO']);
        $this->assertSame('Supervisora de seguridad', $variables['CARGO']);
        $this->assertSame('Bucaramanga', $variables['CIUDAD']);
        $this->assertSame('1 DE OCTUBRE DEL 2026', $variables['FECHA_INI_AUS']);
        $this->assertSame('14', $variables['DIAS']);
        $this->assertSame('Directora de GH', $variables['FIRMA']);
        $this->assertSame('DIR_GH', $variables['CARGO_FIRMA']);
        $this->assertNotSame('', $variables['FECHA']);
    }

    public function test_view_user_can_see_historico_but_not_generate(): void
    {
        $viewer = $this->viewerUser();
        InasistenciaCarta::factory()->create([
            'document_number' => '5555555555',
            'full_name' => 'Histórico Vista',
            'generated_by' => $viewer->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.inasistencias.historico'))
            ->assertOk()
            ->assertSee('Histórico Vista', false)
            ->assertSee('5555555555', false);

        $this->actingAs($viewer)
            ->postJson(route('gestion-humana.inasistencias.generate'), [
                'rows' => [$this->validRow()],
            ])
            ->assertForbidden();
    }

    public function test_import_preview_hydrates_rows(): void
    {
        $editor = $this->editorUser();
        $signatory = $this->seedSignatory();
        $this->createProfile('3011111111', 'Excel Activo', EmployeeFichaProfile::STATUS_ACTIVO, [
            'position_name' => 'Vigilante',
            'work_city_name' => 'Cali',
        ]);

        $path = $this->makeImportXlsx([
            ['3011111111', '', '2026-10-01', $signatory->name],
        ]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.inasistencias.import-preview'), [
                'file' => new UploadedFile($path, 'lote.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('rows.0.cedula', '3011111111')
            ->assertJsonPath('rows.0.nombre_completo', 'Excel Activo')
            ->assertJsonPath('rows.0.cargo', 'Vigilante')
            ->assertJsonPath('rows.0.ciudad', 'Cali')
            ->assertJsonPath('rows.0.fecha_ini_aus', '2026-10-01')
            ->assertJsonPath('rows.0.dias', 14)
            ->assertJsonPath('rows.0.signatory_id', $signatory->id);

        @unlink($path);
    }

    /**
     * @return array<string, mixed>
     */
    private function validRow(?int $signatoryId = null, string $cedula = '1099000001', string $nombre = 'Empleado Inasistencia'): array
    {
        return [
            'cedula' => $cedula,
            'nombre_completo' => $nombre,
            'cargo' => 'Guarda',
            'ciudad' => 'Cali',
            'fecha_ini_aus' => '2026-10-01',
            'signatory_id' => $signatoryId ?? 1,
        ];
    }

    private function boardOnlyUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.inasistencias',
        ]);

        return $user;
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.inasistencias',
            'inasistencias.view',
        ]);

        return $user;
    }

    private function editorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.inasistencias',
            'inasistencias.view',
            'inasistencias.edit',
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

    private function seedExactlyOneTemplate(string $content = '${CEDULA} ${NOMBRE}'): TerminationLetterDocumentTemplate
    {
        return $this->seedTemplates(1, $content)[0];
    }

    /**
     * @return list<TerminationLetterDocumentTemplate>
     */
    private function seedTemplates(int $count, string $content = '${CEDULA} ${NOMBRE}'): array
    {
        $type = WordDocumentType::query()->firstOrCreate(
            ['code' => 'inasistencias'],
            [
                'name' => 'Inasistencias',
                'is_active' => true,
                'sort_order' => 5,
            ],
        );

        $templates = [];
        for ($i = 1; $i <= $count; $i++) {
            $path = 'ficha-empleados/letter-templates/'.$type->id.'/tpl-ina-'.$i.'.docx';
            Storage::disk('local')->put($path, $this->makeDocxBinary($content));

            $templates[] = TerminationLetterDocumentTemplate::query()->create([
                'word_document_type_id' => $type->id,
                'label' => 'Plantilla Inasistencia '.$i,
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
        $sheet->fromArray(['CEDULA', 'NOMBRE', 'FECHA_INI_AUS', 'FIRMA'], null, 'A1');
        $rowNum = 2;
        foreach ($dataRows as $row) {
            $sheet->fromArray($row, null, 'A'.$rowNum);
            $rowNum++;
        }

        $temp = tempnam(sys_get_temp_dir(), 'ina-xlsx-');
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
