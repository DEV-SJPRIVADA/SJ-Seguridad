<?php

namespace Tests\Feature\GestionHumana;

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
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
    }

    public function test_generate_renuncia_pack_downloads_zip_with_three_documents(): void
    {
        $terminator = $this->terminatorUser();
        $entry = $this->createTerminatedRenunciaEntry($terminator);
        $period = EmployeeFichaEmploymentPeriod::query()
            ->where('personal_requisition_ficha_entry_id', $entry->id)
            ->where('status', EmployeeFichaEmploymentPeriod::STATUS_CERRADO)
            ->firstOrFail();

        $this->seedTemplatesWithFiles();

        $response = $this->actingAs($terminator)
            ->post(route('gestion-humana.ficha-empleados.employees.period.letters.generate', $period));

        $response->assertOk();
        $response->assertDownload();

        $period->refresh();
        $this->assertSame('zip', $period->termination_letter_type);
        $this->assertNotNull($period->termination_letter_path);
        Storage::disk('local')->assertExists((string) $period->termination_letter_path);

        $zipPath = Storage::disk('local')->path((string) $period->termination_letter_path);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($zipPath) === true);
        $this->assertSame(3, $zip->numFiles);
        $zip->close();
    }

    public function test_generate_fails_when_template_missing(): void
    {
        $terminator = $this->terminatorUser();
        $entry = $this->createTerminatedRenunciaEntry($terminator);
        $period = EmployeeFichaEmploymentPeriod::query()
            ->where('personal_requisition_ficha_entry_id', $entry->id)
            ->where('status', EmployeeFichaEmploymentPeriod::STATUS_CERRADO)
            ->firstOrFail();

        $this->actingAs($terminator)
            ->post(route('gestion-humana.ficha-empleados.employees.period.letters.generate', $period))
            ->assertSessionHasErrors('templates');
    }

    public function test_non_renuncia_causal_cannot_generate_letters_in_v1(): void
    {
        $terminator = $this->terminatorUser();
        $entry = $this->createInFichaEntry(withActivePeriod: true);

        $this->actingAs($terminator)
            ->post(route('gestion-humana.ficha-empleados.employees.ficha.terminate', $entry), [
                'termination_cause_code' => 'DESPIDO',
                'is_rehireable' => '0',
                'last_work_day' => now()->subDay()->toDateString(),
                'termination_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $period = EmployeeFichaEmploymentPeriod::query()
            ->where('personal_requisition_ficha_entry_id', $entry->id)
            ->where('status', EmployeeFichaEmploymentPeriod::STATUS_CERRADO)
            ->firstOrFail();

        $this->actingAs($terminator)
            ->post(route('gestion-humana.ficha-empleados.employees.period.letters.generate', $period))
            ->assertSessionHasErrors('termination_cause_code');
    }

    public function test_manager_without_terminate_permission_cannot_generate_letters(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $terminator = $this->terminatorUser();
        $entry = $this->createTerminatedRenunciaEntry($terminator);
        $period = EmployeeFichaEmploymentPeriod::query()
            ->where('personal_requisition_ficha_entry_id', $entry->id)
            ->where('status', EmployeeFichaEmploymentPeriod::STATUS_CERRADO)
            ->firstOrFail();

        $this->seedTemplatesWithFiles();

        $this->actingAs($manager)
            ->post(route('gestion-humana.ficha-empleados.employees.period.letters.generate', $period))
            ->assertForbidden();
    }

    public function test_manage_user_can_upload_termination_letter_template(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->givePermissionTo('ficha_empleados.manage');

        $template = TerminationLetterDocumentTemplate::query()
            ->where('termination_cause_code', 'RENUNCIA')
            ->where('document_key', 'aceptacion_renuncia')
            ->firstOrFail();

        $docx = $this->makeDocxFixture('[NOMBRE]');

        $this->actingAs($manager)
            ->post(route('gestion-humana.ficha-empleados.catalogs.termination-letter-template.upload', [
                'causeCode' => 'RENUNCIA',
                'documentKey' => 'aceptacion_renuncia',
            ]), [
                'template' => $docx,
            ])
            ->assertRedirect(route('gestion-humana.ficha-empleados.catalogs.index').'#section-termination_cause');

        $template->refresh();
        $this->assertNotNull($template->template_path);
        Storage::disk('local')->assertExists((string) $template->template_path);
    }

    private function terminatorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo(['ficha_empleados.manage', 'ficha_empleados.terminate']);

        return $user;
    }

    private function createTerminatedRenunciaEntry(User $terminator): PersonalRequisitionFichaEntry
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
                'termination_cause_code' => 'RENUNCIA',
                'is_rehireable' => '1',
                'last_work_day' => now()->subDay()->toDateString(),
                'termination_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        return $entry->fresh(['profile']);
    }

    private function seedTemplatesWithFiles(): void
    {
        $templates = TerminationLetterDocumentTemplate::query()
            ->where('termination_cause_code', 'RENUNCIA')
            ->ordered()
            ->get();

        foreach ($templates as $template) {
            $path = 'ficha-empleados/letter-templates/RENUNCIA/'.$template->document_key.'.docx';
            Storage::disk('local')->put($path, $this->makeDocxBinary('[NOMBRE] [CEDULA]'));
            $template->update(['template_path' => $path]);
        }
    }

    private function makeDocxFixture(string $content): UploadedFile
    {
        $temp = tempnam(sys_get_temp_dir(), 'letter-fixture-');
        $path = $temp.'.docx';
        @unlink($temp);
        file_put_contents($path, $this->makeDocxBinary($content));

        return new UploadedFile($path, 'plantilla.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
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

        foreach (['RENUNCIA', 'DESPIDO'] as $code) {
            PayrollCatalogItem::query()->firstOrCreate(
                ['catalog_type' => 'termination_cause', 'code' => $code],
                ['name' => $code, 'sort_order' => 1, 'is_active' => true],
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
