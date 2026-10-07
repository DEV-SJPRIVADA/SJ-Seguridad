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
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;
use ZipArchive;

class ClienteInternoCartasVacacionesGenerateTest extends TestCase
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

    public function test_view_only_user_gets_403_on_lookup_and_generate(): void
    {
        $user = $this->cartasViewerUser();

        $this->actingAs($user)
            ->postJson(route('gestion-humana.cliente-interno.cartas-vacaciones.lookup'), [
                'document_number' => '123',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson(route('gestion-humana.cliente-interno.cartas-vacaciones.generate'), [
                'rows' => [$this->validRow()],
            ])
            ->assertForbidden();
    }

    public function test_edit_user_sees_grid_and_lookup_active_inactive_not_found(): void
    {
        $editor = $this->cartasEditorUser();
        $this->createProfile('1098765432', 'Ana Activa', EmployeeFichaProfile::STATUS_ACTIVO);
        $this->createProfile('1098765433', 'Bruno Inactivo', EmployeeFichaProfile::STATUS_DESVINCULADO);

        $this->actingAs($editor)
            ->get(route('gestion-humana.cliente-interno.cartas-vacaciones'))
            ->assertOk()
            ->assertSee('Agregar varias cédulas', false)
            ->assertSee('Generar cartas', false);

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.cliente-interno.cartas-vacaciones.lookup'), [
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

    public function test_generate_validates_required_dates_and_duplicate_cedula(): void
    {
        $editor = $this->cartasEditorUser();
        $signatory = $this->seedSignatory();
        $this->seedExactlyOneTemplate();

        $base = $this->validRow($signatory->id);

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.cliente-interno.cartas-vacaciones.generate'), [
                'rows' => [[
                    ...$base,
                    'nombre_completo' => '',
                    'fecha_inicio' => null,
                ]],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rows.0.nombre_completo', 'rows.0.fecha_inicio']);

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.cliente-interno.cartas-vacaciones.generate'), [
                'rows' => [[
                    ...$base,
                    'fecha_inicio' => '2026-06-10',
                    'fecha_fin' => '2026-06-05',
                    'fecha_reintegro' => '2026-06-12',
                ]],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rows.0.fecha_fin']);

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.cliente-interno.cartas-vacaciones.generate'), [
                'rows' => [[
                    ...$base,
                    'fecha_inicio' => '2026-06-01',
                    'fecha_fin' => '2026-06-10',
                    'fecha_reintegro' => '2026-06-08',
                ]],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rows.0.fecha_reintegro']);

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.cliente-interno.cartas-vacaciones.generate'), [
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
        $editor = $this->cartasEditorUser();
        $signatory = $this->seedSignatory();
        $row = $this->validRow($signatory->id);

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.cliente-interno.cartas-vacaciones.generate'), [
                'rows' => [$row],
            ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'No hay plantilla activa de Cartas Vacaciones. Cargue una en Plantillas Word.',
            ]);

        $this->seedTemplates(2);

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.cliente-interno.cartas-vacaciones.generate'), [
                'rows' => [$row],
            ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'Hay más de una plantilla activa de Cartas Vacaciones. Deje solo una activa.',
            ]);
    }

    public function test_generate_one_row_returns_docx_and_audits_without_massive_pii(): void
    {
        $editor = $this->cartasEditorUser();
        $signatory = $this->seedSignatory();
        $template = $this->seedExactlyOneTemplate(
            '${CEDULA} ${NOMBRE_COMPLETO} ${FECHA_INICIO} ${FECHA_FIN} ${FECHA_REINTEGRO} ${PERIODOS} ${DIAS_DISFRUTADOS} ${FIRMA} ${CARGO_FIRMA}'
        );

        $row = $this->validRow($signatory->id, '1099000111', 'Carla Vacaciones');

        $response = $this->actingAs($editor)
            ->post(route('gestion-humana.cliente-interno.cartas-vacaciones.generate'), [
                'rows' => [$row],
            ]);

        $response->assertOk();
        $response->assertDownload();
        $this->assertStringContainsString('.docx', (string) $response->headers->get('content-disposition'));

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'cliente_interno',
            'event_type' => 'cartas_vacaciones_generate',
            'action' => 'generate',
        ]);

        $audit = AuditLog::query()
            ->where('event_type', 'cartas_vacaciones_generate')
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
        $editor = $this->cartasEditorUser();
        $signatory = $this->seedSignatory();
        $this->seedExactlyOneTemplate('${CEDULA} ${NOMBRE_COMPLETO}');

        $rows = [
            $this->validRow($signatory->id, '2011111111', 'Persona Uno'),
            $this->validRow($signatory->id, '2022222222', 'Persona Dos'),
        ];

        $response = $this->actingAs($editor)
            ->post(route('gestion-humana.cliente-interno.cartas-vacaciones.generate'), [
                'rows' => $rows,
            ]);

        $response->assertOk();
        $response->assertDownload();
        $this->assertStringContainsString('.zip', (string) $response->headers->get('content-disposition'));

        $temp = tempnam(sys_get_temp_dir(), 'cv-zip-');
        $this->assertNotFalse($temp);
        file_put_contents($temp, $response->streamedContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($temp) === true);
        $this->assertSame(2, $zip->numFiles);
        $zip->close();
        @unlink($temp);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'cliente_interno',
            'event_type' => 'cartas_vacaciones_generate',
            'action' => 'generate',
        ]);
    }

    public function test_firma_and_cargo_firma_come_from_catalog(): void
    {
        $builder = app(LetterVariableBuilder::class);
        $signatory = $this->seedSignatory();

        $variables = $builder->buildForCartasVacaciones([
            'cedula' => '1',
            'nombre_completo' => 'Test',
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-01-05',
            'fecha_reintegro' => '2026-01-06',
            'periodos' => '2025',
            'dias_disfrutados' => '5',
            'signatory_id' => $signatory->id,
        ]);

        $this->assertSame('Directora de GH', $variables['FIRMA']);
        $this->assertSame('DIR_GH', $variables['CARGO_FIRMA']);
        $this->assertSame('1 de Enero del 2026', $variables['FECHA_INICIO']);
    }

    public function test_build_for_cartas_vacaciones_fills_ficha_cargo_ciudad_and_cedula(): void
    {
        $this->createProfile('1099007788', 'Laura Pérez', EmployeeFichaProfile::STATUS_ACTIVO, [
            'position_name' => 'Supervisora de seguridad',
            'residence_city_name' => 'Bucaramanga',
        ]);

        $builder = app(LetterVariableBuilder::class);
        $signatory = $this->seedSignatory();

        $variables = $builder->buildForCartasVacaciones([
            'cedula' => '1099007788',
            'nombre_completo' => 'Laura Pérez (manual)',
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-01-05',
            'fecha_reintegro' => '2026-01-06',
            'periodos' => '2025',
            'dias_disfrutados' => '5',
            'signatory_id' => $signatory->id,
        ]);

        $this->assertSame('1099007788', $variables['CEDULA']);
        $this->assertSame('1099007788', $variables['DOCUMENTO']);
        $this->assertSame('Laura Pérez (manual)', $variables['NOMBRE_COMPLETO']);
        $this->assertSame('Supervisora de seguridad', $variables['CARGO']);
        $this->assertSame('Bucaramanga', $variables['CIUDAD_RESIDENCIA']);
        $this->assertSame('Bucaramanga', $variables['CIUDAD']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validRow(?int $signatoryId = null, string $cedula = '1099000001', string $nombre = 'Empleado Vacaciones'): array
    {
        return [
            'cedula' => $cedula,
            'nombre_completo' => $nombre,
            'fecha_inicio' => '2026-06-01',
            'fecha_fin' => '2026-06-15',
            'fecha_reintegro' => '2026-06-16',
            'periodos' => '2024-2025',
            'dias_disfrutados' => '15',
            'signatory_id' => $signatoryId ?? 1,
        ];
    }

    private function cartasViewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.cliente_interno',
            'cliente_interno.cartas_vacaciones.view',
        ]);

        return $user;
    }

    private function cartasEditorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.cliente_interno',
            'cliente_interno.cartas_vacaciones.edit',
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
            ['code' => 'cartas_vacaciones'],
            [
                'name' => 'Cartas Vacaciones',
                'is_active' => true,
                'sort_order' => 3,
            ],
        );

        $templates = [];
        for ($i = 1; $i <= $count; $i++) {
            $path = 'ficha-empleados/letter-templates/'.$type->id.'/tpl-cv-'.$i.'.docx';
            Storage::disk('local')->put($path, $this->makeDocxBinary($content));

            $templates[] = TerminationLetterDocumentTemplate::query()->create([
                'word_document_type_id' => $type->id,
                'label' => 'Plantilla CV '.$i,
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
