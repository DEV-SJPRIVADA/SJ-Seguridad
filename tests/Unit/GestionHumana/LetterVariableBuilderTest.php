<?php

namespace Tests\Unit\GestionHumana;

use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\EmployeeFichaProfile;
use App\Models\PersonalRequisitionFichaEntry;
use App\Services\GestionHumana\Letter\LetterVariableBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterVariableBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_includes_lugar_nacimiento_from_profile(): void
    {
        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => null,
            'hired_document' => '809999999',
            'hired_full_name' => 'Carta Lugar Nacimiento',
            'moved_to_ficha_at' => now(),
        ]);

        $profile = EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => '809999999',
            'full_name' => 'Carta Lugar Nacimiento',
            'birth_place' => 'Cali',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        $period = EmployeeFichaEmploymentPeriod::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'sequence' => 1,
            'status' => EmployeeFichaEmploymentPeriod::STATUS_ACTIVO,
            'hire_date' => now()->subYear()->toDateString(),
        ]);

        $variables = app(LetterVariableBuilder::class)->build($period, $entry, $profile);

        $this->assertSame('Cali', $variables['LUGAR_NACIMIENTO']);
        $this->assertArrayHasKey('LUGAR_NACIMIENTO', config('employee_ficha.letter_placeholders')['Datos del empleado (Perfil)']);
    }
}
