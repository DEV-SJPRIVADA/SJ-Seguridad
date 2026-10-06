<?php

namespace Tests\Feature\GestionHumana;

use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\EmployeeFichaProfile;
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
use App\Models\WordDocumentType;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContratacionLetterGenerateModalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_edit_ficha_shows_generar_cartas_icon_with_type_payload(): void
    {
        WordDocumentType::query()->firstOrCreate(
            ['code' => config('employee_ficha.word_document_type_codes.contratacion')],
            ['name' => 'Contratacion', 'is_active' => true, 'sort_order' => 2],
        );
        WordDocumentType::query()->firstOrCreate(
            ['code' => config('employee_ficha.word_document_type_codes.desvinculacion')],
            ['name' => 'Desvinculacion', 'is_active' => true, 'sort_order' => 1],
        );
        WordDocumentType::query()->firstOrCreate(
            ['code' => 'certificado'],
            ['name' => 'Certificado', 'is_active' => true, 'sort_order' => 3],
        );

        $manager = $this->managerUser();
        $entry = $this->createActiveFichaEntry();

        $response = $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry));

        $response->assertOk();
        $response->assertSee('title="Generar Cartas"', false);
        $response->assertSee('aria-label="Generar Cartas"', false);
        $response->assertSee('ficha-generate-cartas', false);
        $response->assertSee('Tipo de documento', false);
        $response->assertSee('data-letter-types', false);

        $html = $response->getContent();
        $this->assertMatchesRegularExpression('/data-letter-types=\'([^\']+)\'/', $html);

        preg_match('/data-letter-types=\'([^\']+)\'/', $html, $matches);
        $types = json_decode(html_entity_decode($matches[1], ENT_QUOTES), true);

        $this->assertIsArray($types);
        $byCode = collect($types)->keyBy('code');

        $this->assertTrue((bool) data_get($byCode, config('employee_ficha.word_document_type_codes.contratacion').'.enabled'));
        $this->assertFalse((bool) data_get($byCode, config('employee_ficha.word_document_type_codes.desvinculacion').'.enabled'));
        $this->assertStringContainsString(
            'vínculo laboral cerrado',
            (string) data_get($byCode, config('employee_ficha.word_document_type_codes.desvinculacion').'.disabled_reason'),
        );
        $this->assertTrue((bool) data_get($byCode, 'certificado.enabled'));
        $this->assertNotEmpty((string) data_get($byCode, 'certificado.templates_url'));
        $this->assertStringContainsString('/tipos/certificado/', (string) data_get($byCode, 'certificado.generate_url'));
    }

    private function managerUser(): User
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

    private function createActiveFichaEntry(): PersonalRequisitionFichaEntry
    {
        $requester = User::factory()->create(['must_change_password' => false]);
        $mover = User::factory()->create(['must_change_password' => false]);

        $requisition = PersonalRequisition::query()->create([
            'code' => 'REQ-CARTAS-'.uniqid(),
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
            'cost_center' => 'CC-CARTAS',
            'status' => PersonalRequisition::STATUS_CONTRATADO,
            'status_changed_at' => now(),
        ]);

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => '1098765432',
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
}
