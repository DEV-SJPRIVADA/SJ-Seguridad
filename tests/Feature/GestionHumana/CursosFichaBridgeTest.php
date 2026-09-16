<?php

namespace Tests\Feature\GestionHumana;

use App\Models\CursoTipo;
use App\Models\EmployeeCurso;
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
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CursosFichaBridgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_ficha_view_can_list_employee_cursos(): void
    {
        $viewer = $this->fichaViewer();
        $entry = $this->createEntryWithProfile('1099001122', 'Ana Bridge');
        $tipo = CursoTipo::factory()->create(['tipo_curso' => 'ALTURAS']);

        EmployeeCurso::factory()->create([
            'document_number' => '1099001122',
            'full_name' => 'Ana Bridge',
            'curso_tipo_id' => $tipo->id,
            'numero_curso' => 'BR-1',
            'fecha_expedicion' => '2026-01-15',
        ]);

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.ficha-empleados.employees.cursos', $entry))
            ->assertOk()
            ->assertJsonPath('document_number', '1099001122')
            ->assertJsonPath('data.0.numero_curso', 'BR-1')
            ->assertJsonPath('data.0.tipo_curso', 'ALTURAS');
    }

    public function test_download_requires_matching_cedula(): void
    {
        Storage::fake('local');

        $viewer = $this->fichaViewer();
        $entry = $this->createEntryWithProfile('111', 'Uno');
        $other = $this->createEntryWithProfile('222', 'Dos');
        $tipo = CursoTipo::factory()->create();

        $curso = EmployeeCurso::factory()->create([
            'document_number' => '111',
            'curso_tipo_id' => $tipo->id,
            'numero_curso' => 'DOC-1',
        ]);

        $path = UploadedFile::fake()->create('cert.pdf', 20, 'application/pdf')
            ->storeAs('employee-cursos/'.$curso->id, 'cert.pdf', 'local');

        $curso->forceFill([
            'document_path' => $path,
            'document_original_name' => 'cert.pdf',
            'document_mime' => 'application/pdf',
            'document_size_bytes' => 20,
        ])->save();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.ficha-empleados.employees.cursos.document', [$entry, $curso]))
            ->assertOk();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.ficha-empleados.employees.cursos.document', [$other, $curso]))
            ->assertNotFound();
    }

    public function test_without_ficha_view_is_forbidden(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $entry = $this->createEntryWithProfile('333', 'Sin Permiso');

        $this->actingAs($user)
            ->getJson(route('gestion-humana.ficha-empleados.employees.cursos', $entry))
            ->assertForbidden();
    }

    public function test_edit_ficha_shows_cursos_button(): void
    {
        $manager = $this->fichaManager();
        $entry = $this->createEntryWithProfile('444', 'Con Boton');

        $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry))
            ->assertOk()
            ->assertSee('Consultar cursos', false)
            ->assertSee('ficha-employee-cursos', false);
    }

    private function fichaViewer(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.ficha_empleados',
            'ficha_empleados.view',
        ]);

        return $user;
    }

    private function fichaManager(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.ficha_empleados',
            'ficha_empleados.view',
            'ficha_empleados.manage',
        ]);

        return $user;
    }

    private function createEntryWithProfile(string $document, string $fullName): PersonalRequisitionFichaEntry
    {
        $requester = User::factory()->create(['must_change_password' => false]);

        $requisition = PersonalRequisition::query()->create([
            'code' => 'REQ-CUR-'.uniqid(),
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
            'cost_center' => 'CC-CUR',
            'status' => PersonalRequisition::STATUS_CONTRATADO,
            'status_changed_at' => now(),
        ]);

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => $document,
            'hired_full_name' => $fullName,
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $requester->id,
            'created_by' => $requester->id,
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => $document,
            'full_name' => $fullName,
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        return $entry->fresh(['profile']);
    }
}
