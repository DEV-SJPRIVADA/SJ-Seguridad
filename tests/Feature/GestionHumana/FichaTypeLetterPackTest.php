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

class FichaTypeLetterPackTest extends TestCase
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

    public function test_generate_generic_type_on_active_period_downloads_without_touching_letter_path(): void
    {
        $manager = $this->managerUser();
        $entry = $this->createActiveEntry();
        $period = $this->activePeriodFor($entry);
        $period->update([
            'termination_letter_path' => 'ficha-empleados/contratacion-letters/'.$period->id.'/prev.docx',
            'termination_letter_type' => 'docx',
        ]);
        Storage::disk('local')->put((string) $period->termination_letter_path, 'prev');

        $template = $this->seedTypeTemplate('cliente_interno', 'Cliente interno');
        $signatory = $this->seedSignatory();

        $response = $this->actingAs($manager)
            ->post(route('gestion-humana.ficha-empleados.employees.type-letters.generate', [
                'period' => $period,
                'typeCode' => 'cliente_interno',
            ]), [
                'template_ids' => [$template->id],
                'signatory_id' => $signatory->id,
            ]);

        $response->assertOk();
        $this->assertStringContainsString('.docx', (string) $response->headers->get('content-disposition'));

        $period->refresh();
        $this->assertSame(
            'ficha-empleados/contratacion-letters/'.$period->id.'/prev.docx',
            $period->termination_letter_path,
        );
        $this->assertNotEmpty(
            Storage::disk('local')->allFiles('ficha-empleados/type-letters/cliente_interno/'.$period->id),
        );

        $this->assertNotNull(
            AuditLog::query()
                ->where('event_type', 'ficha_type_letter_pack')
                ->where('action', 'generate')
                ->first(),
        );
    }

    public function test_generate_generic_type_on_closed_period_with_terminate_permission(): void
    {
        $terminator = $this->terminatorUser();
        $entry = $this->createTerminatedEntry($terminator);
        $period = $this->closedPeriodFor($entry);
        $existingPath = 'ficha-empleados/termination-letters/'.$period->id.'/desv.docx';
        $period->update([
            'termination_letter_path' => $existingPath,
            'termination_letter_type' => 'docx',
        ]);
        Storage::disk('local')->put($existingPath, 'desv');

        $template = $this->seedTypeTemplate('certificado', 'Certificado');
        $signatory = $this->seedSignatory();

        $response = $this->actingAs($terminator)
            ->post(route('gestion-humana.ficha-empleados.employees.type-letters.generate', [
                'period' => $period,
                'typeCode' => 'certificado',
            ]), [
                'template_ids' => [$template->id],
                'signatory_id' => $signatory->id,
            ]);

        $response->assertOk();
        $period->refresh();
        $this->assertSame($existingPath, $period->termination_letter_path);
        $this->assertTrue(Storage::disk('local')->exists($existingPath));
    }

    public function test_reserved_type_codes_return_not_found(): void
    {
        $manager = $this->managerUser();
        $entry = $this->createActiveEntry();
        $period = $this->activePeriodFor($entry);
        $signatory = $this->seedSignatory();

        $contratacion = WordDocumentType::query()->firstOrCreate(
            ['code' => config('employee_ficha.word_document_type_codes.contratacion')],
            ['name' => 'Contratacion', 'is_active' => true, 'sort_order' => 2],
        );

        $path = 'ficha-empleados/letter-templates/'.$contratacion->id.'/tpl.docx';
        Storage::disk('local')->put($path, $this->makeDocxBinary('${NOMBRE_COMPLETO}'));
        $template = TerminationLetterDocumentTemplate::query()->create([
            'word_document_type_id' => $contratacion->id,
            'label' => 'Plantilla contratacion',
            'sort_order' => 1,
            'template_path' => $path,
        ]);

        $this->actingAs($manager)
            ->post(route('gestion-humana.ficha-empleados.employees.type-letters.generate', [
                'period' => $period,
                'typeCode' => config('employee_ficha.word_document_type_codes.contratacion'),
            ]), [
                'template_ids' => [$template->id],
                'signatory_id' => $signatory->id,
            ])
            ->assertNotFound();
    }

    public function test_templates_endpoint_lists_only_requested_type(): void
    {
        $manager = $this->managerUser();
        $entry = $this->createActiveEntry();
        $period = $this->activePeriodFor($entry);

        $this->seedTypeTemplate('cliente_interno', 'Cliente interno', 'CI A');
        $this->seedTypeTemplate('certificado', 'Certificado', 'Cert A');

        $response = $this->actingAs($manager)
            ->getJson(route('gestion-humana.ficha-empleados.employees.type-letters.templates', [
                'period' => $period,
                'typeCode' => 'cliente_interno',
            ]));

        $response->assertOk();
        $labels = collect($response->json('templates'))->pluck('label')->all();
        $this->assertContains('CI A', $labels);
        $this->assertNotContains('Cert A', $labels);
    }

    private function managerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.ficha_empleados',
            'ficha_empleados.view',
            'ficha_empleados.manage',
        ]);

        return $user;
    }

    private function terminatorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.ficha_empleados',
            'ficha_empleados.view',
            'ficha_empleados.manage',
            'ficha_empleados.terminate',
        ]);

        return $user;
    }

    private function seedSignatory(): PayrollCatalogItem
    {
        return PayrollCatalogItem::query()->firstOrCreate(
            ['catalog_type' => 'firmas', 'code' => 'FIRMA-TYPE-1'],
            [
                'name' => 'Firmante prueba',
                'sort_order' => 1,
                'is_active' => true,
            ],
        );
    }

    private function seedTypeTemplate(string $code, string $name, string $label = 'Plantilla 1'): TerminationLetterDocumentTemplate
    {
        $type = WordDocumentType::query()->firstOrCreate(
            ['code' => $code],
            ['name' => $name, 'is_active' => true, 'sort_order' => 10],
        );

        $path = 'ficha-empleados/letter-templates/'.$type->id.'/'.uniqid('tpl-', true).'.docx';
        Storage::disk('local')->put($path, $this->makeDocxBinary('${NOMBRE_COMPLETO} ${DOCUMENTO}'));

        return TerminationLetterDocumentTemplate::query()->create([
            'word_document_type_id' => $type->id,
            'label' => $label,
            'sort_order' => 1,
            'template_path' => $path,
        ]);
    }

    private function activePeriodFor(PersonalRequisitionFichaEntry $entry): EmployeeFichaEmploymentPeriod
    {
        return EmployeeFichaEmploymentPeriod::query()
            ->where('personal_requisition_ficha_entry_id', $entry->id)
            ->where('status', EmployeeFichaEmploymentPeriod::STATUS_ACTIVO)
            ->firstOrFail();
    }

    private function closedPeriodFor(PersonalRequisitionFichaEntry $entry): EmployeeFichaEmploymentPeriod
    {
        return EmployeeFichaEmploymentPeriod::query()
            ->where('personal_requisition_ficha_entry_id', $entry->id)
            ->where('status', EmployeeFichaEmploymentPeriod::STATUS_CERRADO)
            ->firstOrFail();
    }

    private function createTerminatedEntry(User $terminator): PersonalRequisitionFichaEntry
    {
        $entry = $this->createActiveEntry();

        $this->actingAs($terminator)
            ->post(route('gestion-humana.ficha-empleados.employees.ficha.terminate', $entry), [
                'termination_cause_code' => 'RENUNCIA',
                'is_rehireable' => '1',
                'last_work_day' => now()->subDay()->toDateString(),
                'termination_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        return $entry->fresh(['profile']);
    }

    private function createActiveEntry(): PersonalRequisitionFichaEntry
    {
        $requester = User::factory()->create(['must_change_password' => false]);
        $mover = User::factory()->create(['must_change_password' => false]);

        $requisition = PersonalRequisition::query()->create([
            'code' => 'REQ-TYPE-LETTER-'.uniqid(),
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
            'cost_center' => 'CC-TYPE',
            'status' => PersonalRequisition::STATUS_CONTRATADO,
            'status_changed_at' => now(),
        ]);

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => '1987654321',
            'hired_full_name' => 'Empleado Tipo Carta',
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

        EmployeeFichaEmploymentPeriod::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'personal_requisition_id' => $requisition->id,
            'sequence' => 1,
            'status' => EmployeeFichaEmploymentPeriod::STATUS_ACTIVO,
            'hire_date' => now()->subMonth()->toDateString(),
            'position_name' => 'Vigilante',
            'salary' => 1500000,
            'opened_by' => $mover->id,
        ]);

        return $entry->fresh(['profile']);
    }

    private function makeDocxBinary(string $content): string
    {
        $temp = tempnam(sys_get_temp_dir(), 'type-letter-bin-');
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
