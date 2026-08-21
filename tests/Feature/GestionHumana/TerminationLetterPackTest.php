<?php

namespace Tests\Feature\GestionHumana;

use App\Models\AuditLog;
use App\Models\EmployeeFichaEmploymentPeriod;
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
use App\Models\TerminationLetterDocumentTemplate;
use App\Models\User;
use App\Models\WordDocumentType;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;
use ZipArchive;

class TerminationLetterPackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
        Storage::fake('local');
        Config::set('audit.enabled', true);
        Config::set('audit.queue', false);
    }

    public function test_generate_one_template_persists_docx(): void
    {
        $terminator = $this->terminatorUser();
        $entry = $this->createTerminatedEntry($terminator, 'RENUNCIA');
        $period = $this->closedPeriodFor($entry);
        $templates = $this->seedDesvinculacionTemplates(1);
        $signatory = $this->seedSignatory();

        $response = $this->actingAs($terminator)
            ->post(route('gestion-humana.ficha-empleados.employees.period.letters.generate', $period), [
                'template_ids' => [$templates[0]->id],
                'signatory_id' => $signatory->id,
            ]);

        $response->assertOk();
        $response->assertDownload();

        $period->refresh();
        $this->assertSame('docx', $period->termination_letter_type);
        $this->assertNotNull($period->termination_letter_path);
        $this->assertStringEndsWith('.docx', (string) $period->termination_letter_path);
        Storage::disk('local')->assertExists((string) $period->termination_letter_path);
    }

    public function test_generate_multiple_templates_persists_zip_and_replaces_previous(): void
    {
        $terminator = $this->terminatorUser();
        $entry = $this->createTerminatedEntry($terminator, 'DESPIDO');
        $period = $this->closedPeriodFor($entry);
        $templates = $this->seedDesvinculacionTemplates(2);
        $signatory = $this->seedSignatory();

        $this->actingAs($terminator)
            ->post(route('gestion-humana.ficha-empleados.employees.period.letters.generate', $period), [
                'template_ids' => [$templates[0]->id],
                'signatory_id' => $signatory->id,
            ])
            ->assertOk();

        $period->refresh();
        $previousPath = (string) $period->termination_letter_path;
        $this->assertSame('docx', $period->termination_letter_type);
        Storage::disk('local')->assertExists($previousPath);

        $response = $this->actingAs($terminator)
            ->post(route('gestion-humana.ficha-empleados.employees.period.letters.generate', $period), [
                'template_ids' => [$templates[0]->id, $templates[1]->id],
                'signatory_id' => $signatory->id,
            ]);

        $response->assertOk();
        $response->assertDownload();

        $period->refresh();
        $this->assertSame('zip', $period->termination_letter_type);
        $this->assertNotSame($previousPath, $period->termination_letter_path);
        Storage::disk('local')->assertMissing($previousPath);
        Storage::disk('local')->assertExists((string) $period->termination_letter_path);

        $zipPath = Storage::disk('local')->path((string) $period->termination_letter_path);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($zipPath) === true);
        $this->assertSame(2, $zip->numFiles);
        $zip->close();
    }

    public function test_templates_endpoint_lists_desvinculacion_templates_with_files(): void
    {
        $terminator = $this->terminatorUser();
        $entry = $this->createTerminatedEntry($terminator, 'FIN_CONTRATO');
        $period = $this->closedPeriodFor($entry);
        $templates = $this->seedDesvinculacionTemplates(2);

        $this->actingAs($terminator)
            ->getJson(route('gestion-humana.ficha-empleados.employees.period.letters.templates', $period))
            ->assertOk()
            ->assertJsonCount(2, 'templates')
            ->assertJsonFragment(['id' => $templates[0]->id, 'label' => $templates[0]->label]);
    }

    public function test_generate_fails_without_template_ids(): void
    {
        $terminator = $this->terminatorUser();
        $entry = $this->createTerminatedEntry($terminator, 'RENUNCIA');
        $period = $this->closedPeriodFor($entry);
        $this->seedDesvinculacionTemplates(1);

        $this->actingAs($terminator)
            ->post(route('gestion-humana.ficha-empleados.employees.period.letters.generate', $period), [])
            ->assertSessionHasErrors('template_ids');
    }

    public function test_generate_works_for_every_default_termination_cause(): void
    {
        $terminator = $this->terminatorUser();
        $templates = $this->seedDesvinculacionTemplates(1);
        $signatory = $this->seedSignatory();
        $causes = collect(config('employee_ficha.termination_cause_defaults', []))
            ->pluck('code')
            ->filter()
            ->values()
            ->all();

        $this->assertNotEmpty($causes);

        foreach ($causes as $causeCode) {
            $entry = $this->createTerminatedEntry($terminator, (string) $causeCode);
            $period = $this->closedPeriodFor($entry);

            $this->actingAs($terminator)
                ->post(route('gestion-humana.ficha-empleados.employees.period.letters.generate', $period), [
                    'template_ids' => [$templates[0]->id],
                    'signatory_id' => $signatory->id,
                ])
                ->assertOk()
                ->assertDownload();

            $period->refresh();
            $this->assertSame('docx', $period->termination_letter_type, 'Failed for cause '.$causeCode);
            $this->assertNotNull($period->termination_letter_path, 'Failed for cause '.$causeCode);
        }
    }

    public function test_download_serves_persisted_file_and_records_audit(): void
    {
        $terminator = $this->terminatorUser();
        $entry = $this->createTerminatedEntry($terminator, 'MUTUO_ACUERDO');
        $period = $this->closedPeriodFor($entry);
        $templates = $this->seedDesvinculacionTemplates(1);
        $signatory = $this->seedSignatory();

        $this->actingAs($terminator)
            ->post(route('gestion-humana.ficha-empleados.employees.period.letters.generate', $period), [
                'template_ids' => [$templates[0]->id],
                'signatory_id' => $signatory->id,
            ])
            ->assertOk();

        $period->refresh();
        $storedPath = (string) $period->termination_letter_path;

        $this->actingAs($terminator)
            ->get(route('gestion-humana.ficha-empleados.employees.period.letters.download', $period))
            ->assertOk()
            ->assertDownload(basename($storedPath));

        $generateLog = AuditLog::query()
            ->where('module', 'ficha_empleados')
            ->where('event_type', 'termination_letter_pack')
            ->where('action', 'generate')
            ->where('auditable_id', $period->id)
            ->firstOrFail();

        $this->assertSame([$templates[0]->id], $generateLog->metadata['template_ids']);
        $this->assertSame('docx', $generateLog->metadata['output_type']);

        $downloadLog = AuditLog::query()
            ->where('module', 'ficha_empleados')
            ->where('event_type', 'termination_letter_pack')
            ->where('action', 'download')
            ->where('auditable_id', $period->id)
            ->firstOrFail();

        $this->assertSame('docx', $downloadLog->metadata['output_type']);
        $this->assertSame($terminator->id, $downloadLog->user_id);
    }

    public function test_download_without_persisted_file_returns_not_found(): void
    {
        $terminator = $this->terminatorUser();
        $entry = $this->createTerminatedEntry($terminator, 'PERIODO_PRUEBA');
        $period = $this->closedPeriodFor($entry);

        $this->actingAs($terminator)
            ->get(route('gestion-humana.ficha-empleados.employees.period.letters.download', $period))
            ->assertNotFound();
    }

    public function test_manager_without_terminate_permission_cannot_generate_or_download_letters(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $terminator = $this->terminatorUser();
        $entry = $this->createTerminatedEntry($terminator, 'RENUNCIA');
        $period = $this->closedPeriodFor($entry);
        $templates = $this->seedDesvinculacionTemplates(1);
        $signatory = $this->seedSignatory();

        $this->actingAs($terminator)
            ->post(route('gestion-humana.ficha-empleados.employees.period.letters.generate', $period), [
                'template_ids' => [$templates[0]->id],
                'signatory_id' => $signatory->id,
            ])
            ->assertOk();

        $period->refresh();

        $this->actingAs($manager)
            ->post(route('gestion-humana.ficha-empleados.employees.period.letters.generate', $period), [
                'template_ids' => [$templates[0]->id],
                'signatory_id' => $signatory->id,
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->getJson(route('gestion-humana.ficha-empleados.employees.period.letters.templates', $period))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.period.letters.download', $period))
            ->assertForbidden();
    }

    public function test_edit_ficha_shows_generate_and_download_without_regenerate_button(): void
    {
        $terminator = $this->terminatorUser();
        $entry = $this->createTerminatedEntry($terminator, 'FIN_CONTRATO');
        $period = $this->closedPeriodFor($entry);
        $templates = $this->seedDesvinculacionTemplates(1);
        $signatory = $this->seedSignatory();

        $htmlBefore = $this->actingAs($terminator)
            ->get(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Generar cartas', $htmlBefore);
        $this->assertStringNotContainsString('Regenerar', $htmlBefore);
        $this->assertStringNotContainsString('Descargar cartas', $htmlBefore);

        $this->actingAs($terminator)
            ->post(route('gestion-humana.ficha-empleados.employees.period.letters.generate', $period), [
                'template_ids' => [$templates[0]->id],
                'signatory_id' => $signatory->id,
            ])
            ->assertOk();

        $htmlAfter = $this->actingAs($terminator)
            ->get(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Generar cartas', $htmlAfter);
        $this->assertStringContainsString('Descargar cartas', $htmlAfter);
        $this->assertStringNotContainsString('Regenerar', $htmlAfter);
    }

    public function test_rejects_template_from_non_desvinculacion_type(): void
    {
        $terminator = $this->terminatorUser();
        $entry = $this->createTerminatedEntry($terminator, 'DESPIDO');
        $period = $this->closedPeriodFor($entry);
        $signatory = $this->seedSignatory();

        $otherType = WordDocumentType::query()->create([
            'code' => 'nuevo_tipo',
            'name' => 'Nuevo Tipo',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $path = 'ficha-empleados/letter-templates/'.$otherType->id.'/otro.docx';
        Storage::disk('local')->put($path, $this->makeDocxBinary('[NOMBRE]'));

        $otherTemplate = TerminationLetterDocumentTemplate::query()->create([
            'word_document_type_id' => $otherType->id,
            'label' => 'Plantilla otro tipo',
            'sort_order' => 1,
            'template_path' => $path,
        ]);

        $this->actingAs($terminator)
            ->post(route('gestion-humana.ficha-empleados.employees.period.letters.generate', $period), [
                'template_ids' => [$otherTemplate->id],
                'signatory_id' => $signatory->id,
            ])
            ->assertSessionHasErrors('template_ids');
    }

    public function test_legacy_catalog_template_routes_are_gone(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $this->actingAs($manager)
            ->post('/gestion-humana/ficha-empleados/catalogos/termination-cause/RENUNCIA/cartas/aceptacion_renuncia/plantilla')
            ->assertNotFound();

        $this->actingAs($manager)
            ->get('/gestion-humana/ficha-empleados/catalogos/termination-cause/RENUNCIA/cartas/aceptacion_renuncia/plantilla')
            ->assertNotFound();

        $this->actingAs($manager)
            ->delete('/gestion-humana/ficha-empleados/catalogos/termination-cause/RENUNCIA/cartas/aceptacion_renuncia/plantilla')
            ->assertNotFound();
    }

    public function test_catalog_index_does_not_expose_termination_letter_template_admin(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $html = $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.catalogs.index', ['catalog' => 'termination_cause']))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Plantillas Word — cartas de desvinculacion', $html);
        $this->assertStringNotContainsString('termination-letter-template', $html);
    }

    private function terminatorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo(['ficha_empleados.manage', 'ficha_empleados.terminate']);

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

    private function closedPeriodFor(PersonalRequisitionFichaEntry $entry): EmployeeFichaEmploymentPeriod
    {
        return EmployeeFichaEmploymentPeriod::query()
            ->where('personal_requisition_ficha_entry_id', $entry->id)
            ->where('status', EmployeeFichaEmploymentPeriod::STATUS_CERRADO)
            ->firstOrFail();
    }

    private function createTerminatedEntry(User $terminator, string $causeCode): PersonalRequisitionFichaEntry
    {
        $entry = $this->createInFichaEntry(withActivePeriod: true);

        EmployeeFichaEmploymentPeriod::query()
            ->where('personal_requisition_ficha_entry_id', $entry->id)
            ->update([
                'salary' => 1423500,
                'contract_type_name' => 'Termino indefinido',
                'work_center_name' => 'Bogota',
            ]);

        $this->actingAs($terminator)
            ->post(route('gestion-humana.ficha-empleados.employees.ficha.terminate', $entry), [
                'termination_cause_code' => $causeCode,
                'is_rehireable' => '1',
                'last_work_day' => now()->subDay()->toDateString(),
                'termination_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        return $entry->fresh(['profile']);
    }

    /**
     * @return list<TerminationLetterDocumentTemplate>
     */
    private function seedDesvinculacionTemplates(int $count): array
    {
        $type = WordDocumentType::query()->firstOrCreate(
            ['code' => config('employee_ficha.word_document_type_codes.desvinculacion')],
            [
                'name' => 'Desvinculacion',
                'is_active' => true,
                'sort_order' => 1,
            ],
        );

        $templates = [];

        for ($i = 1; $i <= $count; $i++) {
            $path = 'ficha-empleados/letter-templates/'.$type->id.'/tpl-'.$i.'.docx';
            Storage::disk('local')->put($path, $this->makeDocxBinary('[NOMBRE] [CEDULA]'));

            $templates[] = TerminationLetterDocumentTemplate::query()->create([
                'word_document_type_id' => $type->id,
                'label' => 'Plantilla '.$i,
                'sort_order' => $i,
                'template_path' => $path,
            ]);
        }

        return $templates;
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

    private function createInFichaEntry(bool $withActivePeriod = false): PersonalRequisitionFichaEntry
    {
        $requisition = $this->createRequisition('REQ-LETTER-'.uniqid());
        $mover = User::factory()->create(['must_change_password' => false]);

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => '10'.random_int(10000000, 99999999),
            'hired_full_name' => 'Empleado Cartas Test',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $mover->id,
            'created_by' => $mover->id,
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => $entry->hired_document,
            'full_name' => $entry->hired_full_name,
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'hire_date' => now()->subMonth()->toDateString(),
            'position_name' => 'Vigilante',
        ]);

        if ($withActivePeriod) {
            EmployeeFichaEmploymentPeriod::query()->create([
                'personal_requisition_ficha_entry_id' => $entry->id,
                'personal_requisition_id' => $requisition->id,
                'sequence' => 1,
                'status' => EmployeeFichaEmploymentPeriod::STATUS_ACTIVO,
                'hire_date' => now()->subMonth()->toDateString(),
                'position_name' => 'Vigilante',
                'salary' => 1500000,
                'contract_type_name' => 'Termino indefinido',
                'opened_by' => $mover->id,
            ]);
        }

        $defaults = config('employee_ficha.termination_cause_defaults', []);
        foreach ($defaults as $index => $row) {
            $code = (string) ($row['code'] ?? '');
            if ($code === '') {
                continue;
            }

            PayrollCatalogItem::query()->firstOrCreate(
                ['catalog_type' => 'termination_cause', 'code' => $code],
                [
                    'name' => (string) ($row['name'] ?? $code),
                    'sort_order' => (int) ($row['sort_order'] ?? ($index + 1)),
                    'is_active' => true,
                ],
            );
        }

        return $entry->fresh(['profile']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createRequisition(string $code, array $overrides = []): PersonalRequisition
    {
        $requester = User::factory()->create(['must_change_password' => false]);

        return PersonalRequisition::query()->create(array_merge([
            'code' => $code,
            'requested_by' => $requester->id,
            'request_date' => now()->toDateString(),
            'leader_name' => $requester->name,
            'requesting_area_key' => 'gestion_humana',
            'position_id' => RequisitionPosition::query()->firstOrFail()->id,
            'sex' => 'masculino',
            'quantity' => 1,
            'operating_area_key' => 'gestion_humana',
            'request_reason_id' => RequisitionRequestReason::query()->firstOrFail()->id,
            'client_id' => RequisitionClient::query()->firstOrFail()->id,
            'city_id' => RequisitionCity::query()->firstOrFail()->id,
            'client_type_id' => RequisitionClientType::query()->firstOrFail()->id,
            'programming_type_id' => RequisitionProgrammingType::query()->firstOrFail()->id,
            'uniform_id' => RequisitionUniform::query()->firstOrFail()->id,
            'required_profile' => 'Perfil de prueba.',
            'service_structure' => 'Turno de prueba.',
            'cost_center' => 'CC-FICHA',
            'status' => PersonalRequisition::STATUS_CONTRATADO,
            'status_changed_at' => now(),
        ], $overrides));
    }
}
